/**
 * Pure title/icon rewrite rules for browser notifications. Shared by the
 * page script (notifications.js) and the admin preview; no side effects.
 */

export function rewriteTitle(title, cfg) {
	if (typeof title !== 'string' || !cfg || !cfg.enabled) {
		return title
	}
	const trimmed = title.trim()
	for (const rule of cfg.rules || []) {
		if (rule.from && rule.to && trimmed === rule.from) {
			return rule.to
		}
	}
	if (cfg.replaceWord && cfg.name) {
		return title.replace(/\bNextcloud\b/g, cfg.name)
	}
	return title
}

export function rewriteOptions(options, cfg) {
	if (!cfg || !cfg.enabled || !cfg.icon || !options || typeof options.icon !== 'string') {
		return options
	}
	// Only replace Nextcloud's generic icons, never a sender's avatar.
	if (/mail-notification\.png|\/core\/img\/|\/img\/(app|notifications?)[^/]*\.(png|svg)/.test(options.icon)) {
		return { ...options, icon: cfg.icon }
	}
	return options
}
