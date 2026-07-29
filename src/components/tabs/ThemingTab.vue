<template>
	<form class="theming-tab" @submit.prevent="handleSave">
		<p class="theming-tab__note">
			These fields write directly into Nextcloud's own Theming settings —
			saving here changes the branding your users see across the whole instance.
		</p>

		<fieldset>
			<legend>Instance Identity</legend>
			<div v-for="field in textFields" :key="field.key" class="field-row">
				<label :for="field.key">{{ field.label }}</label>
				<input
					:id="field.key"
					:type="field.type || 'text'"
					:value="values[field.key]"
					@input="$emit('update', field.key, $event.target.value)">
				<p v-if="errors[field.key]" class="field-error">
					{{ errors[field.key] }}
				</p>
			</div>
		</fieldset>

		<fieldset>
			<legend>Background and Colour</legend>
			<div v-for="field in colorFields" :key="field.key" class="field-row">
				<ColorPickerField
					:label="field.label"
					:value="values[field.key] || field.default"
					@update="(v) => $emit('update', field.key, v)"
					@reset="$emit('update', field.key, field.default)" />
				<p v-if="errors[field.key]" class="field-error">
					{{ errors[field.key] }}
				</p>
			</div>
		</fieldset>

		<fieldset>
			<legend>Images</legend>
			<p class="theming-tab__note">
				These upload directly into Nextcloud's Theming app storage and take
				effect instance-wide immediately.
			</p>
			<div v-for="field in imageFields" :key="field.key" class="field-row image-field">
				<label>{{ field.label }}</label>
				<img
					v-if="images[field.key]"
					:src="images[field.key]"
					class="image-preview"
					:alt="field.label">
				<input type="file" accept="image/*" @change="(e) => handleFile(field.key, e)">
			</div>
		</fieldset>

		<button type="submit" :disabled="saving">
			{{ saving ? 'Saving…' : 'Save Theming' }}
		</button>
	</form>
</template>

<script>
import ColorPickerField from '../ColorPickerField.vue'

const TEXT_FIELDS = [
	{ key: 'theming_application_name', label: 'Application Name' },
	{ key: 'theming_web_link', label: 'Web Link', type: 'url' },
	{ key: 'theming_slogan', label: 'Slogan' },
	{ key: 'theming_legal_notice', label: 'Legal Notice', type: 'url' },
	{ key: 'theming_privacy_policy', label: 'Privacy Policy', type: 'url' },
]

const COLOR_FIELDS = [
	{ key: 'theming_primary_color', label: 'Primary color', default: '#0082c9' },
	{ key: 'theming_background_color', label: 'Background color', default: '#ffffff' },
]

const IMAGE_FIELDS = [
	{ key: 'logo', label: 'Logo' },
	{ key: 'header_logo', label: 'Header Logo' },
	{ key: 'favicon', label: 'Favicon' },
	{ key: 'background', label: 'Background Image' },
]

export default {
	name: 'ThemingTab',
	components: { ColorPickerField },
	props: {
		values: { type: Object, required: true },
		images: { type: Object, default: () => ({}) },
		errors: { type: Object, default: () => ({}) },
		saving: { type: Boolean, default: false },
	},
	data() {
		return {
			textFields: TEXT_FIELDS,
			colorFields: COLOR_FIELDS,
			imageFields: IMAGE_FIELDS,
		}
	},
	methods: {
		handleFile(key, event) {
			const file = event.target.files[0]
			if (file) {
				this.$emit('upload', key, file)
			}
		},
		handleSave() {
			const keys = [
				...this.textFields.map((f) => f.key),
				...this.colorFields.map((f) => f.key),
			]
			this.$emit('save', keys)
		},
	},
}
</script>

<style scoped>
.theming-tab__note {
	background: var(--color-primary-element-light, #e8f3f8);
	padding: 12px 16px;
	border-radius: var(--border-radius, 6px);
	font-size: 13px;
	color: var(--color-main-text);
	margin-bottom: 16px;
}
.image-field {
	margin-bottom: 20px;
}
.image-field input[type='file'] {
	font-size: 13px;
}
.image-preview {
	display: block;
	max-width: 150px;
	max-height: 80px;
	object-fit: contain;
	margin-bottom: 8px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius, 6px);
	padding: 4px;
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
.field-row input {
	width: 100%;
	box-sizing: border-box;
	padding: 10px 12px;
	border: 1px solid var(--color-border-dark, #d4d4d4);
	border-radius: var(--border-radius, 6px);
	font-size: 14px;
	background: var(--color-main-background, #fff);
	color: var(--color-main-text, #222);
}
.field-row input:focus {
	outline: none;
	border-color: var(--color-primary-element, #0082c9);
	box-shadow: 0 0 0 2px rgba(0, 130, 201, 0.15);
}
.field-error {
	color: var(--color-error, #e9322d);
	font-size: 0.85em;
	margin: 6px 0 0;
}
fieldset {
	border: none;
	padding: 0;
	margin: 0 0 32px;
}
legend {
	font-size: 16px;
	font-weight: bold;
	margin-bottom: 16px;
	padding-bottom: 8px;
	border-bottom: 1px solid var(--color-border);
	width: 100%;
}
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
