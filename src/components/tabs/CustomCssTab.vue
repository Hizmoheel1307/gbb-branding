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
			<legend>Message subject size in Mail</legend>
			<label class="checkbox-row">
				<input
					type="checkbox"
					:checked="on('fix_mail_subject')"
					@change="toggle('fix_mail_subject', $event.target.checked)">
				<span>
					<strong>Use these sizes for the subject above an opened message</strong><br>
					<span class="field-hint">Mail uses Nextcloud's large heading size, which on phones wraps over several lines.</span>
				</span>
			</label>
			<div class="size-grid" :class="{ off: !on('fix_mail_subject') }">
				<label>
					<span>Computer (wider than 1024 px)</span>
					<span class="num"><input type="number" min="12" max="40" :value="num('mail_subject_size_desktop', 20)" @input="setNum('mail_subject_size_desktop', $event.target.value)"> px</span>
				</label>
				<label>
					<span>Phone and tablet</span>
					<span class="num"><input type="number" min="12" max="40" :value="num('mail_subject_size_mobile', 18)" @input="setNum('mail_subject_size_mobile', $event.target.value)"> px</span>
				</label>
				<label>
					<span>Lines on phone (0 = no limit)</span>
					<span class="num"><input type="number" min="0" max="6" :value="num('mail_subject_lines_mobile', 2)" @input="setNum('mail_subject_lines_mobile', $event.target.value)"> lines</span>
				</label>
			</div>
			<div class="subject-preview">
				<span class="field-hint">Preview</span>
				<div class="phone">
					<div :style="previewStyle('mobile')">FW: KVP delivery – please complete the following in one go before acceptance</div>
				</div>
				<div class="desktop">
					<div :style="previewStyle('desktop')">FW: KVP delivery – please complete the following in one go before acceptance</div>
				</div>
			</div>
			<p class="field-hint">A subject cut short ends with "…"; users see it in full by opening the message's details or hovering over it.</p>
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
		return {
			keys: [
				'fix_mail_preview', 'fix_mail_touch_actions', 'fix_mail_subject',
				'mail_subject_size_desktop', 'mail_subject_size_mobile', 'mail_subject_lines_mobile', 'custom_css',
			],
		}
	},
	methods: {
		on(key) {
			return this.values[key] !== '0'
		},
		toggle(key, checked) {
			this.$emit('update', key, checked ? '1' : '0')
		},
		num(key, fallback) {
			const v = parseInt(this.values[key], 10)
			return Number.isNaN(v) ? fallback : v
		},
		setNum(key, value) {
			this.$emit('update', key, String(value).replace(/[^0-9]/g, ''))
		},
		previewStyle(kind) {
			const size = kind === 'mobile' ? this.num('mail_subject_size_mobile', 18) : this.num('mail_subject_size_desktop', 20)
			const lines = this.num('mail_subject_lines_mobile', 2)
			const enabled = this.on('fix_mail_subject')
			const style = { fontSize: (enabled ? size : 28) + 'px', lineHeight: 1.3, fontWeight: 700 }
			if (kind === 'mobile' && enabled && lines > 0) {
				Object.assign(style, { display: '-webkit-box', WebkitLineClamp: lines, WebkitBoxOrient: 'vertical', overflow: 'hidden' })
			}
			if (kind === 'desktop') {
				Object.assign(style, { whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' })
			}
			return style
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
.size-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 220px)); gap: 16px; margin: 4px 0 14px 28px; }
.size-grid.off { opacity: 0.5; pointer-events: none; }
.size-grid label { display: flex; flex-direction: column; gap: 6px; font-size: 13px; font-weight: 600; }
.size-grid .num { font-weight: 400; display: flex; align-items: center; gap: 6px; }
.size-grid input { width: 80px; padding: 6px 8px; border: 1px solid var(--color-border-dark, #d4d4d4); border-radius: var(--border-radius, 6px); }
.subject-preview { display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap; margin-left: 28px; }
.subject-preview > .field-hint { width: 100%; }
.subject-preview .phone { width: 300px; padding: 12px 14px; border: 8px solid #222; border-radius: 22px; background: #fff; color: #222; }
.subject-preview .desktop { width: 520px; max-width: 100%; padding: 12px 16px; border: 1px solid var(--color-border, #ddd); border-radius: 6px; background: #fff; color: #222; }
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
