<template>
	<div class="notifications-tab">
		<p class="intro-note">
			Browser notifications from Nextcloud apps use hard-coded names: new mail shows as
			<strong>"Nextcloud Mail"</strong>. These rules rename them for every signed-in user.
			The browser name ("Google Chrome") and the site address under the message come from the
			browser itself and can't be changed.
		</p>

		<p v-if="!s" class="field-hint">Loading…</p>
		<template v-else>
			<label class="checkbox-row">
				<input v-model="s.enabled" type="checkbox">
				Rebrand browser notifications
			</label>

			<fieldset :disabled="!s.enabled">
				<legend>Rename notification titles</legend>
				<div v-for="(rule, i) in s.rules" :key="i" class="rule-row">
					<input v-model="rule.from" type="text" placeholder="Title shown today, e.g. Nextcloud Mail">
					<span class="arrow">→</span>
					<input v-model="rule.to" type="text" placeholder="{instance_name}">
					<button type="button" class="danger" title="Remove" @click="s.rules.splice(i, 1)">✕</button>
				</div>
				<button type="button" class="secondary" @click="s.rules.push({ from: '', to: '{instance_name}' })">+ Add rule</button>
				<p class="field-hint">
					Exact match on the whole title. <code>{instance_name}</code> is your Theming name
					(<strong>{{ instanceName }}</strong>).
				</p>

				<label class="checkbox-row" style="margin-top:14px">
					<input v-model="s.replace_word" type="checkbox">
					Also replace the word "Nextcloud" in any other notification title with {{ instanceName }}
				</label>

				<legend style="margin-top:20px">Icon</legend>
				<div class="icons">
					<label v-for="ic in icons" :key="ic.id" :class="['icon-choice', { active: s.icon === ic.id }]">
						<input v-model="s.icon" type="radio" :value="ic.id">
						<img v-if="ic.id !== 'keep'" :src="iconUrl(ic.id)" alt="">
						<span v-else class="keep">App default</span>
						<span class="icon-label">{{ ic.label }}</span>
					</label>
				</div>
				<p class="field-hint">Replaces Nextcloud's grey envelope and other generic app icons. Senders' avatars are kept.</p>
			</fieldset>

			<div class="preview-wrap">
				<span class="field-hint">Preview of a new-mail notification:</span>
				<div class="toast-preview">
					<div class="toast-preview__browser">Google Chrome</div>
					<div class="toast-preview__body">
						<img :src="previewIcon" alt="">
						<div>
							<div class="toast-preview__title">{{ previewTitle }}</div>
							<div class="toast-preview__text">Kehinde Hizmoheel</div>
							<div class="toast-preview__text">Quarterly report</div>
							<div class="toast-preview__site">{{ host }}</div>
						</div>
					</div>
				</div>
			</div>

			<div class="actions">
				<button type="button" class="primary" :disabled="busy !== ''" @click="save">
					{{ busy === 'save' ? 'Saving…' : 'Save' }}
				</button>
				<button type="button" class="secondary" :disabled="busy !== ''" @click="test">
					Save &amp; show a test notification
				</button>
			</div>
			<p class="field-hint">
				Users get the new names the next time they open or reload a page.
			</p>
		</template>
	</div>
</template>

<script>
import { generateFilePath, imagePath } from '@nextcloud/router'
import { fetchNotificationBranding, saveNotificationBranding } from '../../services/SettingsService.js'
import { rewriteTitle } from '../../notificationRules.js'
import { toastSuccess, toastError, toastInfo } from '../../toast.js'

export default {
	name: 'NotificationsTab',
	props: {
		instanceLabel: { type: String, default: '' },
	},
	data() {
		return {
			s: null,
			page: null,
			busy: '',
			host: window.location.host,
			icons: [
				{ id: 'mail', label: 'Mail' },
				{ id: 'grid', label: 'Workspace' },
				{ id: 'bell', label: 'Bell' },
				{ id: 'chat', label: 'Chat' },
				{ id: 'calendar', label: 'Calendar' },
				{ id: 'keep', label: '' },
			],
		}
	},
	computed: {
		instanceName() {
			return (this.page && this.page.name) || this.instanceLabel || 'GovMail Digital Workspace'
		},
		pageConfig() {
			if (!this.s) {
				return null
			}
			return {
				enabled: this.s.enabled,
				replaceWord: this.s.replace_word,
				name: this.instanceName,
				rules: this.s.rules.map((r) => ({ from: r.from, to: (r.to || '').replace('{instance_name}', this.instanceName) })),
				icon: this.s.icon === 'keep' ? '' : this.iconUrl(this.s.icon),
			}
		},
		previewTitle() {
			return rewriteTitle('Nextcloud Mail', this.pageConfig)
		},
		previewIcon() {
			return this.s && this.s.enabled && this.s.icon !== 'keep' ? this.iconUrl(this.s.icon) : this.mailIcon()
		},
	},
	async mounted() {
		try {
			const data = await fetchNotificationBranding()
			this.s = data.settings
			this.page = data.page
		} catch (e) {
			toastError('Could not load notification settings.')
		}
	},
	methods: {
		iconUrl(id) {
			return imagePath('govmailbranding', 'email/app-' + id + '.png')
		},
		mailIcon() {
			try {
				return generateFilePath('mail', 'img', 'mail-notification.png')
			} catch (e) {
				return this.iconUrl('mail')
			}
		},
		async persist() {
			const data = await saveNotificationBranding(this.s)
			this.s = data.settings
			this.page = data.page
			if (typeof window.__govmailSetNotificationConfig === 'function') {
				window.__govmailSetNotificationConfig(data.page)
			}
		},
		async save() {
			this.busy = 'save'
			try {
				await this.persist()
				toastSuccess('Notification branding saved.')
			} catch (e) {
				toastError('Save failed. Please try again.')
			} finally {
				this.busy = ''
			}
		},
		async test() {
			this.busy = 'test'
			try {
				await this.persist()
				if (!('Notification' in window)) {
					toastError('This browser does not support notifications.')
					return
				}
				const permission = Notification.permission === 'granted' ? 'granted' : await Notification.requestPermission()
				if (permission !== 'granted') {
					toastError('Notifications are blocked for this site. Allow them in the browser (padlock icon > Notifications) and try again.')
					return
				}
				// Exactly what the Mail app does; the rules rename it on the way out.
				// eslint-disable-next-line no-new
				new Notification('Nextcloud Mail', { body: 'Kehinde Hizmoheel\nTest notification', icon: this.mailIcon() })
				toastInfo('Saved. A test notification was sent to your desktop.')
			} catch (e) {
				toastError('Could not show a test notification: ' + (e.message || e))
			} finally {
				this.busy = ''
			}
		},
	},
}
</script>

<style scoped>
.intro-note {
	background: var(--color-primary-element-light, #e8f3f8);
	padding: 12px 16px;
	border-radius: var(--border-radius, 6px);
	font-size: 13px;
	margin-bottom: 20px;
}
fieldset { border: none; padding: 0; margin: 0 0 20px; }
fieldset[disabled] { opacity: 0.55; }
legend { font-weight: 700; font-size: 15px; margin-bottom: 10px; }
.checkbox-row { display: flex; align-items: center; gap: 8px; font-size: 14px; margin-bottom: 12px; cursor: pointer; }
.checkbox-row input { width: auto; margin: 0; }
.field-hint { font-size: 12px; color: var(--color-text-maxcontrast, #767676); margin: 6px 0; }
.rule-row { display: grid; grid-template-columns: 1fr auto 1fr auto; gap: 8px; align-items: center; margin-bottom: 8px; max-width: 760px; }
.rule-row input {
	width: 100%;
	box-sizing: border-box;
	padding: 8px 10px;
	border: 1px solid var(--color-border-dark, #d4d4d4);
	border-radius: var(--border-radius, 6px);
	font-size: 14px;
}
.arrow { color: var(--color-text-maxcontrast, #767676); }
button {
	padding: 8px 14px;
	border-radius: var(--border-radius, 6px);
	border: 1px solid var(--color-border-dark, #d4d4d4);
	background: var(--color-main-background, #fff);
	color: var(--color-main-text, #222);
	cursor: pointer;
	font-size: 13px;
}
button:disabled { opacity: 0.5; cursor: not-allowed; }
button.primary { background: var(--color-primary-element, #0082c9); border-color: var(--color-primary-element, #0082c9); color: var(--color-primary-element-text, #fff); font-weight: 600; }
button.danger { color: var(--color-error, #c9302c); }
.icons { display: flex; flex-wrap: wrap; gap: 10px; }
.icon-choice {
	display: flex; flex-direction: column; align-items: center; gap: 4px;
	width: 84px; padding: 8px; border: 2px solid transparent; border-radius: 10px; cursor: pointer; font-size: 12px;
}
.icon-choice.active { border-color: var(--color-primary-element, #0082c9); }
.icon-choice input { display: none; }
.icon-choice img { width: 44px; height: 44px; }
.icon-choice .keep { width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; text-align: center; font-size: 11px; background: #888; color: #fff; border-radius: 8px; }
.preview-wrap { margin: 10px 0 20px; }
.toast-preview {
	width: 380px; max-width: 100%; margin-top: 6px; padding: 12px 16px;
	background: #f3f3f3; color: #1a1a1a; border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,0.18);
	font-family: 'Segoe UI', Roboto, Arial, sans-serif;
}
.toast-preview__browser { font-size: 13px; margin-bottom: 10px; }
.toast-preview__body { display: flex; gap: 16px; align-items: flex-start; }
.toast-preview__body img { width: 56px; height: 56px; border-radius: 8px; object-fit: contain; }
.toast-preview__title { font-size: 15px; font-weight: 600; }
.toast-preview__text { font-size: 14px; color: #444; }
.toast-preview__site { font-size: 12px; color: #666; margin-top: 4px; }
.actions { display: flex; gap: 8px; flex-wrap: wrap; }
</style>
