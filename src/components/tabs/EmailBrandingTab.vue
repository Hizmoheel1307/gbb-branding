<template>
	<form class="email-branding-tab" @submit.prevent="handleSave">
		<p class="email-branding-tab__note">
			Logo and header color already come from the Theming tab — every
			system email inherits those automatically. This tab only covers the
			footer.
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

		<button type="submit" :disabled="saving">
			{{ saving ? 'Saving…' : 'Save Email Branding' }}
		</button>
	</form>
</template>

<script>
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
	methods: {
		handleSave() {
			this.$emit('save', KEYS)
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
