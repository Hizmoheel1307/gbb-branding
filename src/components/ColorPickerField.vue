<template>
	<div class="color-picker-field">
		<button
			type="button"
			class="color-picker-field__swatch"
			:style="{ background: value, color: textColor }"
			@click="openPicker">
			<span class="color-picker-field__icon" aria-hidden="true">🎨</span>
			<span>{{ label }}</span>
		</button>
		<button
			type="button"
			class="color-picker-field__reset"
			title="Reset to default"
			@click="$emit('reset')">
			↩
		</button>
		<input
			ref="nativeInput"
			type="color"
			class="color-picker-field__native-input"
			:value="value"
			@input="$emit('update', $event.target.value)">
	</div>
</template>

<script>
export default {
	name: 'ColorPickerField',
	props: {
		label: { type: String, required: true },
		value: { type: String, default: '#0082c9' },
	},
	computed: {
		textColor() {
			// Pick black or white text based on the swatch color's brightness,
			// same idea Nextcloud's own picker uses so the label stays readable.
			const hex = (this.value || '#0082c9').replace('#', '')
			if (hex.length !== 6) {
				return '#ffffff'
			}
			const r = parseInt(hex.substring(0, 2), 16)
			const g = parseInt(hex.substring(2, 4), 16)
			const b = parseInt(hex.substring(4, 6), 16)
			const brightness = (r * 299 + g * 587 + b * 114) / 1000
			return brightness > 155 ? '#000000' : '#ffffff'
		},
	},
	methods: {
		openPicker() {
			this.$refs.nativeInput.click()
		},
	},
}
</script>

<style scoped>
.color-picker-field {
	display: flex;
	align-items: center;
	gap: 8px;
	position: relative;
	margin-bottom: 8px;
}
.color-picker-field__swatch {
	flex: 1;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8px;
	padding: 10px 16px;
	border: 1px solid var(--color-border-dark, #d4d4d4);
	border-radius: var(--border-radius, 6px);
	font-size: 14px;
	font-weight: 600;
	cursor: pointer;
}
.color-picker-field__icon {
	font-size: 14px;
}
.color-picker-field__reset {
	background: none;
	border: 1px solid var(--color-border-dark, #d4d4d4);
	border-radius: var(--border-radius, 6px);
	width: 36px;
	height: 36px;
	cursor: pointer;
	font-size: 16px;
	color: var(--color-text-maxcontrast);
}
.color-picker-field__native-input {
	position: absolute;
	width: 1px;
	height: 1px;
	opacity: 0;
	pointer-events: none;
}
</style>
