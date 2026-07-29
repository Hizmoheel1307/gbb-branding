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
.field-row {
	margin-bottom: 12px;
	display: flex;
	flex-direction: column;
	max-width: 400px;
}
.field-row label {
	font-weight: 600;
	margin-bottom: 4px;
}
.field-error {
	color: var(--color-error);
	font-size: 0.85em;
	margin: 2px 0 0;
}
.image-preview {
	max-width: 150px;
	max-height: 80px;
	object-fit: contain;
	margin-bottom: 6px;
	border: 1px solid var(--color-border);
}
fieldset {
	border: none;
	padding: 0;
	margin: 0 0 24px;
}
legend {
	font-size: 1.1em;
	font-weight: bold;
	margin-bottom: 8px;
}
</style>
