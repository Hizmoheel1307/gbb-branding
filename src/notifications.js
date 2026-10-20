/**
 * Rebrands browser (desktop) notifications on every signed-in page.
 *
 * Nextcloud apps hard-code their notification titles (the Mail app shows
 * "Nextcloud Mail"). This wraps window.Notification and
 * ServiceWorkerRegistration#showNotification so the title and icon are
 * rewritten with the rules from GovMail Branding > Notifications just
 * before the browser shows them. Everything else passes through untouched.
 *
 * Kept dependency-free and tiny: it loads on every page.
 */

import { rewriteTitle, rewriteOptions } from './notificationRules.js'

const STATE_ID = 'initial-state-govmailbranding-notification_branding'

let config = null

export function readConfig(doc = document) {
	if (config !== null) {
		return config
	}
	try {
		const el = doc.getElementById(STATE_ID)
		if (!el) {
			return null // not rendered yet - try again next time
		}
		const bytes = Uint8Array.from(atob(el.value), (c) => c.charCodeAt(0))
		config = JSON.parse(new TextDecoder().decode(bytes))
	} catch (e) {
		config = { enabled: false }
	}
	return config
}

function rewrite(title, options) {
	const cfg = readConfig()
	try {
		return [rewriteTitle(title, cfg), rewriteOptions(options, cfg)]
	} catch (e) {
		return [title, options]
	}
}

export function install(win = window) {
	const Native = win.Notification
	if (typeof Native === 'function' && !Native.__govmailBranded) {
		const Branded = new Proxy(Native, {
			construct(target, args) {
				const [title, options] = rewrite(args[0], args[1])
				return Reflect.construct(target, [title, options], target)
			},
			get(target, prop) {
				if (prop === '__govmailBranded') {
					return true
				}
				// Static members (permission getter, requestPermission) need the
				// real constructor as `this`.
				const value = Reflect.get(target, prop, target)
				return typeof value === 'function' && prop !== 'prototype' ? value.bind(target) : value
			},
		})
		try {
			win.Notification = Branded
		} catch (e) {
			// read-only in this browser - leave notifications as they are
		}
	}

	const Registration = win.ServiceWorkerRegistration
	if (Registration && Registration.prototype.showNotification && !Registration.prototype.showNotification.__govmailBranded) {
		const original = Registration.prototype.showNotification
		const patched = function(title, options) {
			const [t, o] = rewrite(title, options)
			return original.call(this, t, o)
		}
		patched.__govmailBranded = true
		Registration.prototype.showNotification = patched
	}
}

if (typeof window !== 'undefined') {
	install(window)
	// Lets the admin page apply freshly saved rules without a reload.
	window.__govmailSetNotificationConfig = (cfg) => {
		config = cfg
	}
}
