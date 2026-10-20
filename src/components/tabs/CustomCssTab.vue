<template>
	<form class="custom-css-tab" @submit.prevent="$emit('save', keys)">
		<fieldset>
			<legend>Fixes for the Mail app</legend>
			<label class="checkbox-row">
				<input
					type="checkbox"
					:checked="on('fix_mail_preview')"
					@change="toggle('fix_mail_preview', $event.target.checked)">
				<span>
					<strong>Never cut off the message preview</strong><br>
					<span class="field-hint">Mail limits the 2-line preview to a fixed height, so with larger
						text on phones the second line is cut in half and runs into the next message.</span>
				</span>
			</label>
			<label class="checkbox-row">
				<input
					type="checkbox"
					:checked="on('fix_mail_touch_actions')"
					@change="toggle('fix_mail_touch_actions', $event.target.checked)">
				<span>
					<strong>Hide the hover buttons on touch screens</strong><br>
					<span class="field-hint">The read / important / delete buttons are meant for mouse hover.
						On phones and tablets they stay on top of the message after a tap. The same actions
						remain available inside the opened message.</span>
				</span>
			</label>
		</fieldset>

		<fieldset>
			<legend>Custom CSS</legend>
			<p class="field-hint">
				Applied on every signed-in page for all users. Test changes carefully: a wrong rule can hide
				parts of Nextcloud. Remove the CSS and save to undo.
			</p>
			<textarea
				:value="values.custom_css"
				rows="14"
				spellcheck="false"
				placeholder="/* Example: make the top bar logo larger */&#10;#header .logo { width: 140px !important; }"
				@input="$emit('update', 'custom_css', $event.target.value)" />
			<p class="field-hint">{{ (values.custom_css || '').length }} / 50,000 characters</p>
		</fieldset>

		<button type="submit" class="primary" :disabled="saving">
			{{ saving ? 'Saving…' : 'Save' }}
		</button>
		<p class="field-hint">Users see changes the next time they open or reload a page.</p>
	</form>
</template>

<script>
export default {
	name: 'CustomCssTab',
	props: {
		values: { type: Object, required: true },
		saving: { type: Boolean, default: false },
	},
	data() {
		return { keys: ['fix_mail_preview', 'fix_mail_touch_actions', 'custom_css'] }
	},
	methods: {
		on(key) {
			return this.values[key] !== '0'
		},
		toggle(key, checked) {
			this.$emit('update', key, checked ? '1' : '0')
		},
	},
}
</script>

<style scoped>
fieldset { border: none; padding: 0; margin: 0 0 24px; }
legend { font-weight: 700; font-size: 15px; margin-bottom: 12px; }
.checkbox-row { display: flex; align-items: flex-start; gap: 10px; font-size: 14px; margin-bottom: 14px; cursor: pointer; max-width: 760px; }
.checkbox-row input { margin-top: 3px; width: auto; }
.field-hint { font-size: 12px; color: var(--color-text-maxcontrast, #767676); margin: 4px 0; }
textarea {
	width: 100%;
	max-width: 900px;
	box-sizing: border-box;
	padding: 10px 12px;
	border: 1px solid var(--color-border-dark, #d4d4d4);
	border-radius: var(--border-radius, 6px);
	font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
	font-size: 13px;
	background: var(--color-main-background, #fff);
	color: var(--color-main-text, #222);
}
button.primary {
	padding: 10px 18px;
	border: none;
	border-radius: var(--border-radius, 6px);
	background: var(--color-primary-element, #0082c9);
	color: var(--color-primary-element-text, #fff);
	font-weight: 600;
	cursor: pointer;
}
button.primary:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
