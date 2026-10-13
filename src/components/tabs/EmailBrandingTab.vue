<template>
	<form class="email-branding-tab" @submit.prevent="handleSave">
		<p class="email-branding-tab__note">
			Logo and header color already come from the Theming tab — every
			system email inherits those automatically. This tab covers the
			footer, and lets you verify delivery with a real test email.
		</p>

		<fieldset>
			<div class="field-row">
				<label for="email_footer">Footer Text</label>
				<textarea
					id="email_footer"
					:value="values.email_footer"
					placeholder="e.g. GovMail Digital Workspace - a safe home for your data"
					@input="$emit('update', 'email_footer', $event.target.value)" />
			</div>

			<div class="field-row">
				<label for="email_signature">Signature</label>
				<input
					id="email_signature"
					type="text"
					:value="values.email_signature"
					placeholder="e.g. The GovMail Team"
					@input="$emit('update', 'email_signature', $event.target.value)">
			</div>

			<div class="field-row">
				<label for="email_social_links">Social Links</label>
				<p class="field-hint">
					One per line, as: Label, https://example.com
				</p>
				<textarea
					id="email_social_links"
					:value="values.email_social_links"
					placeholder="Twitter, https://twitter.com/yourorg&#10;LinkedIn, https://linkedin.com/company/yourorg"
					@input="$emit('update', 'email_social_links', $event.target.value)" />
			</div>
		</fieldset>

		<fieldset>
			<legend>Personal Settings Page</legend>
			<p class="field-hint" style="margin-top:-8px;">
				These hide Nextcloud's own promo banner, credit line, and social
				icons on the user's Personal Settings page — not the email footer above.
			</p>
			<label class="checkbox-row">
				<input
					type="checkbox"
					:checked="values.email_hide_promo_banner === '1'"
					@change="$emit('update', 'email_hide_promo_banner', $event.target.checked ? '1' : '')">
				Hide Nextcloud Promotion Banner
			</label>
			<label class="checkbox-row">
				<input
					type="checkbox"
					:checked="values.email_hide_community_footer === '1'"
					@change="$emit('update', 'email_hide_community_footer', $event.target.checked ? '1' : '')">
				Hide Community Footer
			</label>
			<label class="checkbox-row">
				<input
					type="checkbox"
					:checked="values.email_hide_social_links === '1'"
					@change="$emit('update', 'email_hide_social_links', $event.target.checked ? '1' : '')">
				Hide Social Media Links
			</label>
		</fieldset>

		<fieldset class="delivery">
			<legend>Delivery check</legend>
			<p class="field-hint">
				Save your changes first, then send a real email to confirm the
				branded footer is what users actually receive.
			</p>

			<div v-if="status" class="status-row">
				<span :class="['badge', 'badge--' + badge.tone]">{{ badge.label }}</span>
				<span class="status-text">{{ badge.text }}</span>
			</div>
			<p v-else-if="statusError" class="error-text">{{ statusError }}</p>
			<p v-else class="field-hint">Checking status…</p>

			<p v-if="status && status.lastError" class="error-text">{{ status.lastError }}</p>

			<label v-if="status" class="checkbox-row">
				<input
					type="checkbox"
					:checked="status.enabled"
					:disabled="toggling"
					@change="toggleOverride($event.target.checked)">
				Apply branded footer to all Nextcloud emails
			</label>

			<div class="field-row test-row">
				<label for="test_email_to">Send test email to</label>
				<div class="inline">
					<input
						id="test_email_to"
						v-model="testTo"
						type="email"
						placeholder="Leave empty to use your own account email">
					<button type="button" :disabled="sending" @click="runTest">
						{{ sending ? 'Sending…' : 'Send test' }}
					</button>
				</div>
			</div>

			<div v-if="lastTest" :class="['test-result', lastTest.ok ? 'ok' : 'fail']">
				<strong>{{ lastTest.ok ? (lastTest.branded ? 'Sent, branded' : 'Sent, but NOT branded') : 'Send failed' }}</strong>
				<span v-if="lastTest.to"> to {{ lastTest.to }}</span>
				<span> from {{ lastTest.host }}<span v-if="lastTest.via"> via {{ lastTest.via }}</span>, {{ formatTime(lastTest.at) }}</span>
				<div v-if="lastTest.error" class="error-text">{{ lastTest.error }}</div>
				<div v-if="lastTest.templateClass" class="field-hint">Template: {{ lastTest.templateClass }}</div>
				<div v-if="lastTest.ok" class="field-hint">Open the email and check the footer, signature and links.</div>
			</div>
		</fieldset>

		<button type="submit" :disabled="saving">
			{{ saving ? 'Saving…' : 'Save Email Branding' }}
		</button>
	</form>
</template>

<script>
import { fetchEmailStatus, setEmailOverride, sendTestEmail } from '../../services/SettingsService.js'

const BADGES = {
	active: { tone: 'ok', label: 'Active', text: 'Branded template is in use.' },
	fallback: { tone: 'warn', label: 'Fallback', text: 'Configured, but Nextcloud is using the stock template. Emails go out unbranded.' },
	pending: { tone: 'warn', label: 'Not applied', text: 'Switched on, but not applied on this server yet.' },
	foreign: { tone: 'warn', label: 'Conflict', text: 'Another app\'s template class owns this setting.' },
	stale: { tone: 'warn', label: 'Stale', text: 'Switched off, but config.php still points at the branded template.' },
	disabled: { tone: 'off', label: 'Off', text: 'Stock Nextcloud emails are used.' },
}

const KEYS = [
	'email_footer', 'email_signature', 'email_social_links',
	'email_hide_promo_banner', 'email_hide_community_footer', 'email_hide_social_links',
]

export default {
	name: 'EmailBrandingTab',
	props: {
		values: { type: Object, required: true },
		errors: { type: Object, default: () => ({}) },
		saving: { type: Boolean, default: false },
	},
	data() {
		return {
			status: null,
			statusError: '',
			toggling: false,
			sending: false,
			testTo: '',
			testResult: null,
		}
	},
	computed: {
		badge() {
			if (!this.status) {
				return BADGES.disabled
			}
			const b = BADGES[this.status.state] || BADGES.disabled
			return { ...b, text: b.text + ' (server: ' + this.status.host + ')' }
		},
		lastTest() {
			return this.testResult || (this.status && this.status.lastTest) || null
		},
	},
	mounted() {
		this.loadStatus()
	},
	methods: {
		handleSave() {
			this.$emit('save', KEYS)
		},
		async loadStatus() {
			try {
				this.status = await fetchEmailStatus()
				this.statusError = ''
			} catch (e) {
				this.statusError = 'Could not load email branding status.'
			}
		},
		async toggleOverride(enabled) {
			this.toggling = true
			try {
				this.status = await setEmailOverride(enabled)
			} catch (e) {
				this.statusError = 'Could not change the setting.'
			} finally {
				this.toggling = false
			}
		},
		async runTest() {
			this.sending = true
			try {
				const data = await sendTestEmail(this.testTo.trim())
				this.testResult = data.result
				if (data.status) {
					this.status = data.status
				}
			} catch (e) {
				this.testResult = { ok: false, error: 'Request failed: ' + (e.message || 'unknown error'), at: new Date().toISOString() }
			} finally {
				this.sending = false
			}
		},
		formatTime(iso) {
			return iso ? new Date(iso).toLocaleString() : ''
		},
	},
}
</script>

<style scoped>
.email-branding-tab__note {
	background: var(--color-primary-element-light, #e8f3f8);
	padding: 12px 16px;
	border-radius: var(--border-radius, 6px);
	font-size: 13px;
	color: var(--color-main-text);
	margin-bottom: 24px;
}
.field-row {
	margin-bottom: 20px;
	display: flex;
	flex-direction: column;
	width: 100%;
}
.field-row label {
	font-weight: 600;
	margin-bottom: 6px;
	font-size: 14px;
}
.field-hint {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
	margin: -2px 0 6px;
}
.field-row input,
.field-row textarea {
	width: 100%;
	box-sizing: border-box;
	padding: 10px 12px;
	border: 1px solid var(--color-border-dark, #d4d4d4);
	border-radius: var(--border-radius, 6px);
	font-size: 14px;
	background: var(--color-main-background, #fff);
	color: var(--color-main-text, #222);
	font-family: inherit;
}
.field-row textarea {
	min-height: 90px;
	resize: vertical;
}
.field-row input:focus,
.field-row textarea:focus {
	outline: none;
	border-color: var(--color-primary-element, #0082c9);
	box-shadow: 0 0 0 2px rgba(0, 130, 201, 0.15);
}
fieldset {
	border: none;
	padding: 0;
	margin: 0 0 24px;
}
.checkbox-row {
	display: flex;
	align-items: center;
	gap: 8px;
	font-size: 14px;
	margin-bottom: 12px;
	cursor: pointer;
}
.checkbox-row input[type='checkbox'] {
	width: auto;
	margin: 0;
}
.status-row {
	display: flex;
	align-items: center;
	gap: 10px;
	margin-bottom: 12px;
	font-size: 14px;
}
.badge {
	padding: 2px 10px;
	border-radius: 999px;
	font-size: 12px;
	font-weight: 600;
}
.badge--ok { background: var(--color-success, #2d7b41); color: #fff; }
.badge--warn { background: var(--color-warning, #c28900); color: #fff; }
.badge--off { background: var(--color-background-dark, #ededed); color: var(--color-main-text, #222); }
.error-text {
	color: var(--color-error, #c9302c);
	font-size: 13px;
	margin: 4px 0 8px;
}
.inline {
	display: flex;
	gap: 8px;
}
.inline input { flex: 1; }
.inline button {
	padding: 0 16px;
	border-radius: var(--border-radius, 6px);
	border: 1px solid var(--color-border-dark, #d4d4d4);
	background: var(--color-main-background, #fff);
	color: var(--color-main-text, #222);
	cursor: pointer;
	white-space: nowrap;
}
.inline button:disabled { opacity: 0.6; cursor: not-allowed; }
.test-result {
	border-left: 4px solid;
	padding: 10px 12px;
	font-size: 13px;
	background: var(--color-background-hover, #f5f5f5);
	border-radius: var(--border-radius, 6px);
}
.test-result.ok { border-color: var(--color-success, #2d7b41); }
.test-result.fail { border-color: var(--color-error, #c9302c); }
button[type='submit'] {
	width: 100%;
	padding: 12px;
	font-size: 14px;
	font-weight: 600;
	border: none;
	border-radius: var(--border-radius, 6px);
	background: var(--color-primary-element, #0082c9);
	color: #fff;
	cursor: pointer;
}
button[type='submit']:disabled {
	opacity: 0.6;
	cursor: not-allowed;
}
</style>
