<template>
	<form class="general-branding-tab" @submit.prevent="handleSave">
		<fieldset>
			<legend>General Info</legend>
			<div v-for="field in textFields" :key="field.key" class="field-row">
				<label :for="field.key">{{ field.label }}</label>
				<textarea
					v-if="field.type === 'textarea'"
					:id="field.key"
					:value="values[field.key]"
					@input="$emit('update', field.key, $event.target.value)" />
				<input
					v-else
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
			<legend>Images</legend>
			<div v-for="field in imageFields" :key="field.key" class="field-row image-field">
				<label>{{ field.label }}</label>
				<img
					v-if="values[field.key]"
					:src="mediaUrl(values[field.key])"
					class="image-preview"
					:alt="field.label">
				<input type="file" accept="image/*" @change="(e) => handleFile(field.key, e)">
			</div>
		</fieldset>

		<button type="submit" :disabled="saving">
			{{ saving ? 'Saving…' : 'Save General Branding' }}
		</button>
	</form>
</template>

<script>
import { generateUrl } from '@nextcloud/router'

const TEXT_FIELDS = [
	{ key: 'general_portal_name', label: 'Portal Name' },
	{ key: 'general_company_name', label: 'Company Name' },
	{ key: 'general_support_email', label: 'Support Email', type: 'email' },
	{ key: 'general_support_phone', label: 'Support Phone' },
	{ key: 'general_website', label: 'Website', type: 'url' },
	{ key: 'general_footer_text', label: 'Footer Text', type: 'textarea' },
	{ key: 'general_application_name', label: 'Application Name' },
	{ key: 'general_web_link', label: 'Web Link', type: 'url' },
	{ key: 'general_slogan', label: 'Slogan' },
	{ key: 'general_legal_notice', label: 'Legal Notice', type: 'textarea' },
	{ key: 'general_privacy_policy', label: 'Privacy Policy', type: 'textarea' },
]

const IMAGE_FIELDS = [
	{ key: 'general_logo', label: 'Logo' },
	{ key: 'general_header_logo', label: 'Header Logo' },
	{ key: 'general_favicon', label: 'Favicon' },
	{ key: 'general_background_image', label: 'Background Image' },
]

export default {
	name: 'GeneralBrandingTab',
	props: {
		values: { type: Object, required: true },
		errors: { type: Object, default: () => ({}) },
		saving: { type: Boolean, default: false },
	},
	data() {
		return {
			textFields: TEXT_FIELDS,
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
		mediaUrl(fileName) {
			return generateUrl(`/apps/govmailbranding/media/${fileName}`)
		},
		handleSave() {
			const keys = [
				...this.textFields.map((f) => f.key),
				...this.imageFields.map((f) => f.key),
			]
			this.$emit('save', keys)
		},
	},
}
</script>

<style scoped>
.general-branding-tab {
	width: 100%;
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
.field-row input[type='text'],
.field-row input[type='email'],
.field-row input[type='url'],
.field-row textarea {
	width: 100%;
	box-sizing: border-box;
	padding: 10px 12px;
	border: 1px solid var(--color-border-dark, #d4d4d4);
	border-radius: var(--border-radius, 6px);
	font-size: 14px;
	background: var(--color-main-background, #fff);
	color: var(--color-main-text, #222);
	transition: border-color 0.15s ease;
}
.field-row input:focus,
.field-row textarea:focus {
	outline: none;
	border-color: var(--color-primary-element, #0082c9);
	box-shadow: 0 0 0 2px rgba(0, 130, 201, 0.15);
}
.field-row textarea {
	min-height: 90px;
	resize: vertical;
	font-family: inherit;
}
.field-error {
	color: var(--color-error, #e9322d);
	font-size: 0.85em;
	margin: 6px 0 0;
}
.image-field input[type='file'] {
	font-size: 13px;
}
.image-preview {
	max-width: 150px;
	max-height: 80px;
	object-fit: contain;
	margin-bottom: 8px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius, 6px);
	padding: 4px;
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
	transition: opacity 0.15s ease;
}
button[type='submit']:hover:not(:disabled) {
	opacity: 0.9;
}
button[type='submit']:disabled {
	opacity: 0.6;
	cursor: not-allowed;
}
</style>
