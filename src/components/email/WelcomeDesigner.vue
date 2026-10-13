<template>
	<div class="welcome-designer">
		<div class="preset-bar">
			<span>Start from a ready-made design in GovMail colours, then edit anything.</span>
			<button type="button" class="secondary" :disabled="loadingPreset" @click="loadPreset">
				{{ loadingPreset ? 'Loading…' : 'Load GovMail design' }}
			</button>
		</div>

		<fieldset>
			<legend>Sending</legend>
			<label class="checkbox-row">
				<input v-model="w.enabled" type="checkbox">
				Use this designed welcome email (instead of Nextcloud's default)
			</label>
			<label class="checkbox-row">
				<input v-model="w.first_login" type="checkbox">
				Send it automatically the first time a user signs in
			</label>
			<label class="checkbox-row">
				<input v-model="w.login_as_email" type="checkbox">
				If the account has no email address, send to the login name when it is an email address
			</label>
			<label class="checkbox-row" style="margin-left:24px">
				<input v-model="w.send_now" type="checkbox" :disabled="!w.first_login">
				Send immediately (within seconds) instead of waiting for the next cron run
			</label>
			<p class="field-hint">
				The email is sent in the background, so signing in is never slowed down. If it can't go out
				straight away (for example the mail server is unreachable), cron sends it a few minutes later.
				Admin-created accounts with "send welcome email" also get this design.
			</p>
		</fieldset>

		<fieldset>
			<legend>Top of the email</legend>
			<div class="field-row">
				<label>Subject</label>
				<input v-model="w.subject" type="text" placeholder="Welcome to {instance_name}">
			</div>
			<div class="field-row">
				<label>Heading</label>
				<input v-model="w.heading" type="text" placeholder="Welcome {displayname}">
			</div>
			<div class="field-row">
				<label>Introduction</label>
				<textarea v-model="w.intro" rows="4" />
				<p class="field-hint">Replaces Nextcloud's default welcome text. A blank line starts a new paragraph.</p>
			</div>
			<div class="field-row two">
				<div>
					<label>Main button text</label>
					<input v-model="w.button" type="text" placeholder="Open {instance_name}">
				</div>
				<div>
					<label>Second button</label>
					<select v-model="w.secondary_button">
						<option value="hide">Hide</option>
						<option value="default">Nextcloud default (Install client)</option>
						<option value="custom">Custom</option>
					</select>
				</div>
			</div>
			<div v-if="w.secondary_button === 'custom'" class="field-row two">
				<div>
					<label>Second button text</label>
					<input v-model="w.secondary_text" type="text" placeholder="Download the app">
				</div>
				<div>
					<label>Second button link</label>
					<input v-model="w.secondary_url" type="url" placeholder="https://…">
				</div>
			</div>
		</fieldset>

		<fieldset>
			<legend>Brand colours</legend>
			<div class="field-row two">
				<div>
					<label>Accent (buttons in steps, highlights)</label>
					<input v-model="w.accent" type="color">
				</div>
				<div>
					<label>Title colour on light sections</label>
					<input v-model="w.title_color" type="color">
				</div>
			</div>
			<p class="field-hint">The main button and header use your Theming colour. Dark section backgrounds switch to white text automatically.</p>
		</fieldset>

		<fieldset>
			<legend>Sections ({{ w.sections.length }})</legend>
			<p class="field-hint">
				Each section is a coloured block with an optional icon, title, text, picture and up to 8
				feature tiles in two columns. Images are embedded in the email (PNG/JPEG/GIF, max 1 MB each;
				keep the total under ~2 MB).
			</p>

			<div v-for="(s, i) in w.sections" :key="s.id" class="section-card" :style="{ borderLeftColor: s.bg }">
				<div class="section-card__head">
					<strong>Section {{ i + 1 }}<span v-if="s.title">: {{ s.title }}</span></strong>
					<span class="section-card__actions">
						<button type="button" :disabled="i === 0" title="Move up" @click="move(i, -1)">↑</button>
						<button type="button" :disabled="i === w.sections.length - 1" title="Move down" @click="move(i, 1)">↓</button>
						<button type="button" @click="toggle(s.id)">{{ open[s.id] ? 'Collapse' : 'Edit' }}</button>
						<button type="button" class="danger" @click="removeSection(i)">Delete</button>
					</span>
				</div>

				<div v-if="open[s.id]" class="section-card__body">
					<div class="field-row two">
						<div>
							<label>Background colour</label>
							<input v-model="s.bg" type="color">
						</div>
						<div>
							<label>Icon (shown above the title)</label>
							<ImagePicker v-model="s.icon" small label="Upload icon" :presets="presets" />
						</div>
					</div>
					<div class="field-row two">
						<div>
							<label>Layout</label>
							<select v-model="s.layout">
								<option value="stacked">Stacked (centred)</option>
								<option value="image-left">Picture left, text right</option>
								<option value="image-right">Text left, picture right</option>
								<option value="steps">Numbered steps</option>
							</select>
						</div>
						<div v-if="s.layout === 'stacked'">
							<label>Tiles per row</label>
							<select v-model.number="s.columns">
								<option :value="2">2</option>
								<option :value="3">3</option>
								<option :value="4">4 (icons with short titles)</option>
							</select>
						</div>
					</div>
					<div class="field-row">
						<label>Title</label>
						<input v-model="s.title" type="text">
					</div>
					<div class="field-row">
						<label>Text</label>
						<textarea v-model="s.text" rows="3" />
					</div>
					<div class="field-row">
						<label>Picture (full width, e.g. a screenshot)</label>
						<ImagePicker v-model="s.image" label="Upload picture" />
					</div>

					<div class="items">
						<label>Feature tiles ({{ s.items.length }}/8)</label>
						<div v-for="(it, j) in s.items" :key="j" class="item-row">
							<ImagePicker v-model="it.icon" small label="Icon" :presets="presets" />
							<input v-model="it.title" type="text" placeholder="Tile title">
							<textarea v-model="it.text" rows="2" placeholder="Short description" />
							<button type="button" class="danger" @click="s.items.splice(j, 1)">✕</button>
						</div>
						<button v-if="s.items.length < 8" type="button" class="secondary" @click="s.items.push({ icon: '', title: '', text: '' })">
							+ Add tile
						</button>
					</div>
				</div>
			</div>

			<button v-if="w.sections.length < 15" type="button" class="secondary" @click="addSection">+ Add section</button>
		</fieldset>

		<fieldset>
			<legend>Closing</legend>
			<div class="field-row">
				<label>Closing text (after the sections)</label>
				<textarea v-model="w.closing" rows="3" placeholder="For help, contact the GovMail service desk at support@…" />
			</div>
		</fieldset>
	</div>
</template>

<script>
import ImagePicker from './ImagePicker.vue'
import { fetchWelcomePreset } from '../../services/SettingsService.js'
import { toastSuccess, toastError } from '../../toast.js'

const PALETTE = ['#f1effc', '#f4f6fb', '#eaf4fd', '#e9f7f3', '#111111']

export default {
	name: 'WelcomeDesigner',
	components: { ImagePicker },
	props: {
		value: { type: Object, required: true },
		presets: { type: Array, default: () => [] },
	},
	data() {
		return { open: {}, loadingPreset: false }
	},
	computed: {
		w() {
			return this.value
		},
	},
	methods: {
		addSection() {
			const id = Math.random().toString(36).slice(2, 10)
			this.w.sections.push({
				id,
				bg: PALETTE[this.w.sections.length % PALETTE.length],
				icon: '',
				title: '',
				text: '',
				image: '',
				layout: 'stacked',
				columns: 2,
				items: [],
			})
			this.$set(this.open, id, true)
		},
		removeSection(i) {
			if (confirm('Delete this section?')) {
				this.w.sections.splice(i, 1)
			}
		},
		move(i, delta) {
			const list = this.w.sections
			const [s] = list.splice(i, 1)
			list.splice(i + delta, 0, s)
		},
		async loadPreset() {
			if (this.w.sections.length && !confirm('Replace the current welcome design with the GovMail design? Your "Sending" options are kept.')) {
				return
			}
			this.loadingPreset = true
			try {
				const { welcome } = await fetchWelcomePreset()
				Object.keys(welcome).forEach((k) => this.$set(this.w, k, welcome[k]))
				this.open = {}
				toastSuccess('GovMail design loaded. Click Save to keep it.')
			} catch (e) {
				toastError('Could not load the GovMail design.')
			} finally {
				this.loadingPreset = false
			}
		},
		toggle(id) {
			this.$set(this.open, id, !this.open[id])
		},
	},
}
</script>

<style scoped>
.preset-bar {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 12px;
	padding: 12px 16px;
	margin-bottom: 20px;
	border: 1px solid var(--color-border, #ddd);
	border-radius: var(--border-radius, 6px);
	font-size: 14px;
}
.section-card {
	border: 1px solid var(--color-border, #ddd);
	border-left: 6px solid #ccc;
	border-radius: var(--border-radius, 6px);
	padding: 10px 14px;
	margin-bottom: 12px;
	background: var(--color-main-background, #fff);
}
.section-card__head {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 8px;
}
.section-card__actions { display: flex; gap: 6px; }
.section-card__body { margin-top: 14px; }
.items { margin-top: 8px; }
.items > label { font-weight: 600; font-size: 14px; display: block; margin-bottom: 8px; }
.item-row {
	display: grid;
	grid-template-columns: auto 1fr 2fr auto;
	gap: 8px;
	align-items: start;
	margin-bottom: 8px;
}
.field-row.two {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 16px;
}
.field-row.two > div { display: flex; flex-direction: column; }
input[type='color'] { width: 64px; height: 36px; padding: 2px; }
</style>
