<template>
	<div class="email-templates-tab">
		<p class="intro-note">
			Change the wording of every email {{ instanceLabel }} sends, and design the welcome email.
			Use placeholders like <code>{displayname}</code> and formatting like <code>**bold**</code> and
			<code>[link text](https://…)</code>. Anything left blank keeps Nextcloud's default text.
		</p>

		<p v-if="loadError" class="error-text">{{ loadError }}</p>
		<p v-else-if="!loaded" class="field-hint">Loading…</p>

		<template v-else>
			<details v-if="header" class="header-card">
				<summary>
					<strong>Header and spacing</strong>
					<span class="field-hint">applies to every email</span>
				</summary>
				<label class="checkbox-row">
					<input v-model="header.enabled" type="checkbox">
					Use the GovMail header (larger logo, no circle behind it)
				</label>
				<fieldset :disabled="!header.enabled">
					<div class="field-row two">
						<div>
							<label>Logo width: {{ header.logo_width }} px</label>
							<input v-model.number="header.logo_width" type="range" min="60" max="400" step="10">
						</div>
						<div>
							<label>Behind the logo</label>
							<select v-model="header.background">
								<option value="none">Nothing (white)</option>
								<option value="color">Colour band</option>
							</select>
							<input v-if="header.background === 'color'" v-model="header.background_color" type="color" style="margin-top:6px">
						</div>
					</div>
					<label class="checkbox-row">
						<input v-model="header.compact" type="checkbox">
						Compact spacing between logo, heading, text and buttons
					</label>
				</fieldset>
				<button type="button" class="primary" :disabled="busy !== ''" @click="saveHeaderSettings">Save header</button>
			</details>

			<div class="picker">
				<label for="email-picker">Email</label>
				<select id="email-picker" v-model="selectedId" @change="onSelect">
					<optgroup v-for="group in groups" :key="group.name" :label="group.name">
						<option v-for="e in group.emails" :key="e.id" :value="e.id">
							{{ e.label }}{{ isCustomised(e.id) ? ' ●' : '' }}
						</option>
					</optgroup>
				</select>
				<span class="field-hint">● = customised</span>
			</div>

			<!-- Welcome email -->
			<WelcomeDesigner v-if="isWelcome" v-model="welcome" :presets="presetImages" />

			<!-- Any other email -->
			<div v-else-if="current" class="override-form">
				<label class="checkbox-row">
					<input v-model="form.enabled" type="checkbox">
					Customise this email
				</label>

				<fieldset :disabled="!form.enabled">
					<div class="field-row">
						<label>Subject</label>
						<input v-model="form.subject" type="text" :placeholder="current.defaults.subject || 'Nextcloud default'">
					</div>
					<div class="field-row">
						<label>Heading</label>
						<input v-model="form.heading" type="text" :placeholder="current.defaults.heading || 'Nextcloud default'">
					</div>
					<div class="field-row">
						<label>Your text</label>
						<div class="radio-row">
							<label><input v-model="form.body_mode" type="radio" value="keep"> Keep default text only</label>
							<label><input v-model="form.body_mode" type="radio" value="before"> Add before default text</label>
							<label><input v-model="form.body_mode" type="radio" value="after"> Add at the end</label>
							<label><input v-model="form.body_mode" type="radio" value="replace"> Replace default text</label>
						</div>
						<textarea
							v-model="form.body"
							rows="5"
							:disabled="form.body_mode === 'keep'"
							:placeholder="current.defaults.body || 'Your text'" />
						<p v-if="form.body_mode === 'replace' && current.essentialBody" class="warn-text">
							This email's default text carries details users need (passwords, dates, notes).
							If you replace it, include them with placeholders, or use "Add before" instead.
						</p>
					</div>
					<div class="field-row two">
						<div>
							<label>Button text</label>
							<input v-model="form.button" type="text" :placeholder="current.defaults.button || 'Nextcloud default'">
						</div>
						<label class="checkbox-row bottom">
							<input v-model="form.hide_secondary_button" type="checkbox">
							Hide second button (where there is one)
						</label>
					</div>
				</fieldset>
			</div>

			<div class="placeholders">
				<span class="field-hint">Placeholders for this email:</span>
				<code v-for="p in placeholders" :key="p" class="chip" title="Click to copy" @click="copy(p)">{{ '{' + p + '}' }}</code>
				<span v-if="copied" class="field-hint">Copied {{ copied }}</span>
			</div>

			<div class="actions">
				<button type="button" class="primary" :disabled="busy !== ''" @click="save">
					{{ busy === 'save' ? 'Saving…' : 'Save' }}
				</button>
				<button type="button" class="secondary" :disabled="busy !== ''" @click="saveAndPreview">
					{{ busy === 'preview' ? 'Rendering…' : 'Save & preview' }}
				</button>
				<button v-if="!isWelcome && isCustomised(selectedId)" type="button" class="secondary" :disabled="busy !== ''" @click="reset">
					Reset to default
				</button>
				<span class="spacer" />
				<input v-model="testTo" type="email" placeholder="Test address (default: yours)">
				<button type="button" class="secondary" :disabled="busy !== ''" @click="sendTest">
					{{ busy === 'test' ? 'Sending…' : 'Send test' }}
				</button>
			</div>

			<div v-if="preview" class="preview">
				<div class="preview__bar">
					<span><strong>Subject:</strong> {{ preview.subject }}</span>
					<span class="field-hint">{{ preview.sizeKb }} KB<span v-if="preview.sizeKb > 1500"> - large, some providers may clip it</span></span>
					<button type="button" class="link-button" @click="showText = !showText">{{ showText ? 'HTML view' : 'Plain-text view' }}</button>
				</div>
				<pre v-if="showText" class="preview__text">{{ preview.text }}</pre>
				<iframe v-else class="preview__frame" sandbox="" :srcdoc="preview.html" title="Email preview" />
				<p class="field-hint">
					Preview uses sample data{{ isWelcome ? ' and your own account' : '' }}. Images are embedded in the real email.
				</p>
			</div>
		</template>
	</div>
</template>

<script>
import WelcomeDesigner from '../email/WelcomeDesigner.vue'
import { toastSuccess, toastError } from '../../toast.js'
import {
	fetchEmailTemplates, saveEmailHeader, saveEmailOverride, resetEmailOverride, saveWelcomeEmail, previewEmail, sendTestTemplate,
} from '../../services/SettingsService.js'

const WELCOME = 'settings.Welcome'
const GROUP_ORDER = ['Account', 'Sharing', 'Calendar', 'Administration', 'Other apps']
const EMPTY = { enabled: true, subject: '', heading: '', body_mode: 'keep', body: '', button: '', hide_secondary_button: false }

export default {
	name: 'EmailTemplatesTab',
	components: { WelcomeDesigner },
	props: {
		instanceLabel: { type: String, default: 'GovMail' },
	},
	data() {
		return {
			loaded: false,
			loadError: '',
			emails: [],
			overrides: {},
			globalPlaceholders: [],
			presetImages: [],
			header: null,
			welcome: null,
			selectedId: WELCOME,
			form: { ...EMPTY },
			busy: '',
			message: '',
			messageOk: true,
			preview: null,
			showText: false,
			testTo: '',
			copied: '',
		}
	},
	computed: {
		isWelcome() {
			return this.selectedId === WELCOME
		},
		current() {
			return this.emails.find((e) => e.id === this.selectedId) || null
		},
		groups() {
			const byGroup = {}
			this.emails.forEach((e) => {
				(byGroup[e.group] = byGroup[e.group] || []).push(e)
			})
			return Object.keys(byGroup)
				.sort((a, b) => GROUP_ORDER.indexOf(a) - GROUP_ORDER.indexOf(b))
				.map((name) => ({ name, emails: byGroup[name] }))
		},
		placeholders() {
			return [...(this.current ? this.current.placeholders : []), ...this.globalPlaceholders]
		},
	},
	mounted() {
		this.load()
	},
	methods: {
		async load() {
			try {
				const data = await fetchEmailTemplates()
				this.emails = data.emails
				this.overrides = data.overrides || {}
				this.globalPlaceholders = data.globalPlaceholders || []
				this.presetImages = data.presetImages || []
				this.header = data.header || null
				this.welcome = data.welcome
				this.onSelect()
				this.loaded = true
			} catch (e) {
				this.loadError = 'Could not load email templates.'
				toastError(this.loadError)
			}
		},
		isCustomised(id) {
			if (id === WELCOME) {
				return !!(this.welcome && this.welcome.enabled)
			}
			return !!(this.overrides[id] && this.overrides[id].enabled)
		},
		onSelect() {
			this.preview = null
			this.message = ''
			if (!this.isWelcome) {
				this.form = { ...EMPTY, ...(this.overrides[this.selectedId] || {}) }
			}
		},
		flash(text, ok = true) {
			ok ? toastSuccess(text) : toastError(text)
		},
		errorOf(e, fallback) {
			return (e.response && e.response.data && e.response.data.error) || fallback
		},
		async save() {
			this.busy = 'save'
			try {
				await this.persist()
				this.flash('Saved.')
			} catch (e) {
				this.flash(this.errorOf(e, 'Save failed.'), false)
			} finally {
				this.busy = ''
			}
		},
		async persist() {
			if (this.isWelcome) {
				const res = await saveWelcomeEmail(this.welcome)
				this.welcome = res.welcome
			} else {
				const res = await saveEmailOverride(this.selectedId, this.form)
				this.$set(this.overrides, this.selectedId, res.override)
			}
		},
		async saveHeaderSettings() {
			this.busy = 'save'
			try {
				const res = await saveEmailHeader(this.header)
				this.header = res.header
				this.flash('Header saved. It applies to all emails.')
			} catch (e) {
				this.flash(this.errorOf(e, 'Could not save the header.'), false)
			} finally {
				this.busy = ''
			}
		},
		async saveAndPreview() {
			this.busy = 'preview'
			try {
				await this.persist()
				this.preview = await previewEmail(this.selectedId)
				this.flash('Saved. Preview is shown below.')
			} catch (e) {
				this.flash(this.errorOf(e, 'Preview failed.'), false)
			} finally {
				this.busy = ''
			}
		},
		async reset() {
			if (!confirm('Remove the customisation for this email?')) {
				return
			}
			this.busy = 'save'
			try {
				await resetEmailOverride(this.selectedId)
				this.$delete(this.overrides, this.selectedId)
				this.onSelect()
				this.flash('Reset to Nextcloud default.')
			} finally {
				this.busy = ''
			}
		},
		async sendTest() {
			this.busy = 'test'
			try {
				await this.persist()
				const res = await sendTestTemplate(this.selectedId, this.testTo.trim())
				this.flash(res.ok ? 'Test sent to ' + res.to + '.' : (res.error || 'Sending failed.'), !!res.ok)
			} catch (e) {
				this.flash(this.errorOf(e, 'Sending failed.'), false)
			} finally {
				this.busy = ''
			}
		},
		copy(p) {
			const text = '{' + p + '}'
			try {
				navigator.clipboard.writeText(text)
				this.copied = text
				setTimeout(() => { this.copied = '' }, 1500)
			} catch (e) {
				// clipboard not available; the chip text is still visible
			}
		},
	},
}
</script>

<style>
/* Shared by the tab and its child components */
.email-templates-tab fieldset {
	border: none;
	padding: 0;
	margin: 0 0 24px;
}
.email-templates-tab fieldset[disabled] { opacity: 0.55; }
.email-templates-tab legend {
	font-weight: 700;
	font-size: 15px;
	margin-bottom: 12px;
}
.email-templates-tab .field-row {
	margin-bottom: 16px;
	display: flex;
	flex-direction: column;
}
.email-templates-tab .field-row label,
.email-templates-tab .picker label {
	font-weight: 600;
	margin-bottom: 6px;
	font-size: 14px;
}
.email-templates-tab .field-hint {
	font-size: 12px;
	color: var(--color-text-maxcontrast, #767676);
	margin: 4px 0;
}
.email-templates-tab input[type='text'],
.email-templates-tab input[type='email'],
.email-templates-tab input[type='url'],
.email-templates-tab textarea,
.email-templates-tab select {
	width: 100%;
	box-sizing: border-box;
	padding: 8px 10px;
	border: 1px solid var(--color-border-dark, #d4d4d4);
	border-radius: var(--border-radius, 6px);
	font-size: 14px;
	font-family: inherit;
	background: var(--color-main-background, #fff);
	color: var(--color-main-text, #222);
}
.email-templates-tab textarea { resize: vertical; }
.email-templates-tab .checkbox-row {
	display: flex;
	align-items: center;
	gap: 8px;
	font-size: 14px;
	margin-bottom: 10px;
	cursor: pointer;
}
.email-templates-tab .checkbox-row.bottom { align-self: end; margin-bottom: 10px; }
.email-templates-tab .checkbox-row input { width: auto; margin: 0; }
.email-templates-tab button {
	padding: 8px 14px;
	border-radius: var(--border-radius, 6px);
	border: 1px solid var(--color-border-dark, #d4d4d4);
	background: var(--color-main-background, #fff);
	color: var(--color-main-text, #222);
	cursor: pointer;
	font-size: 13px;
}
.email-templates-tab button:disabled { opacity: 0.5; cursor: not-allowed; }
.email-templates-tab button.primary {
	background: var(--color-primary-element, #0082c9);
	border-color: var(--color-primary-element, #0082c9);
	color: var(--color-primary-element-text, #fff);
	font-weight: 600;
}
.email-templates-tab button.danger { color: var(--color-error, #c9302c); }
.email-templates-tab .link-button { border: none; background: none; color: var(--color-primary-element, #0082c9); padding: 0; }
.email-templates-tab .error-text { color: var(--color-error, #c9302c); font-size: 13px; }
.email-templates-tab .ok-text { color: var(--color-success, #2d7b41); font-size: 13px; }
.email-templates-tab .warn-text { color: var(--color-warning-text, #8a5a00); font-size: 13px; margin-top: 6px; }
</style>

<style scoped>
.intro-note {
	background: var(--color-primary-element-light, #e8f3f8);
	padding: 12px 16px;
	border-radius: var(--border-radius, 6px);
	font-size: 13px;
	margin-bottom: 20px;
}
.header-card {
	border: 1px solid var(--color-border, #ddd);
	border-radius: var(--border-radius, 6px);
	padding: 12px 16px;
	margin-bottom: 20px;
}
.header-card summary {
	cursor: pointer;
	display: flex;
	gap: 10px;
	align-items: baseline;
	margin-bottom: 4px;
}
.header-card[open] summary { margin-bottom: 14px; }
.picker {
	display: flex;
	flex-direction: column;
	margin-bottom: 20px;
	max-width: 520px;
}
.radio-row {
	display: flex;
	flex-wrap: wrap;
	gap: 6px 18px;
	margin-bottom: 8px;
	font-size: 14px;
	font-weight: 400;
}
.radio-row label { font-weight: 400 !important; display: flex; gap: 6px; align-items: center; }
.field-row.two {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 16px;
}
.field-row.two > div { display: flex; flex-direction: column; }
.placeholders {
	display: flex;
	flex-wrap: wrap;
	gap: 6px;
	align-items: center;
	margin: 8px 0 16px;
}
.chip {
	background: var(--color-background-dark, #ededed);
	padding: 2px 8px;
	border-radius: 999px;
	font-size: 12px;
	cursor: pointer;
}
.actions {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	align-items: center;
}
.actions .spacer { flex: 1; }
.actions input { width: 260px !important; }
.preview {
	margin-top: 20px;
	border: 1px solid var(--color-border, #ddd);
	border-radius: var(--border-radius, 6px);
	padding: 12px;
}
.preview__bar {
	display: flex;
	flex-wrap: wrap;
	gap: 12px;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 10px;
	font-size: 13px;
}
.preview__frame {
	width: 100%;
	height: 720px;
	border: 1px solid var(--color-border, #ddd);
	border-radius: 4px;
	background: #fff;
}
.preview__text {
	white-space: pre-wrap;
	font-size: 13px;
	max-height: 720px;
	overflow: auto;
	background: var(--color-background-hover, #f5f5f5);
	padding: 12px;
	border-radius: 4px;
}
</style>
