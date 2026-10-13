<template>
	<div class="image-picker">
		<div v-if="value" class="image-picker__preview" :class="{ small }">
			<img :src="url" alt="">
			<button type="button" class="link-button" @click="$emit('input', '')">Remove</button>
		</div>
		<span v-else class="image-picker__choose">
			<label class="image-picker__upload" :class="{ small, busy }">
				<input type="file" accept="image/png,image/jpeg,image/gif" :disabled="busy" @change="onFile">
				{{ busy ? 'Uploading…' : label }}
			</label>
			<button v-if="presets.length" type="button" class="link-button" @click="library = !library">
				{{ library ? 'Close library' : 'Icon library' }}
			</button>
		</span>
		<div v-if="library && !value" class="image-picker__library">
			<button
				v-for="id in presets"
				:key="id"
				type="button"
				:title="id.replace('p-', '')"
				@click="pick(id)">
				<img :src="thumb(id)" alt="">
			</button>
		</div>
		<p v-if="error" class="image-picker__error">{{ error }}</p>
	</div>
</template>

<script>
import { uploadEmailImage, emailImageUrl } from '../../services/SettingsService.js'
import { toastError } from '../../toast.js'

export default {
	name: 'ImagePicker',
	props: {
		value: { type: String, default: '' },
		label: { type: String, default: 'Upload image' },
		small: { type: Boolean, default: false },
		presets: { type: Array, default: () => [] },
	},
	data() {
		return { busy: false, error: '', library: false }
	},
	computed: {
		url() {
			return emailImageUrl(this.value)
		},
	},
	methods: {
		thumb(id) {
			return emailImageUrl(id)
		},
		pick(id) {
			this.library = false
			this.$emit('input', id)
		},
		async onFile(event) {
			const file = event.target.files && event.target.files[0]
			event.target.value = ''
			if (!file) {
				return
			}
			this.busy = true
			this.error = ''
			try {
				const result = await uploadEmailImage(file)
				this.$emit('input', result.id)
			} catch (e) {
				this.error = (e.response && e.response.data && e.response.data.error) || 'Upload failed.'
				toastError(this.error)
			} finally {
				this.busy = false
			}
		},
	},
}
</script>

<style scoped>
.image-picker__upload {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	padding: 8px 14px;
	border: 1px dashed var(--color-border-dark, #bbb);
	border-radius: var(--border-radius, 6px);
	cursor: pointer;
	font-size: 13px;
	color: var(--color-text-maxcontrast, #666);
}
.image-picker__upload input { display: none; }
.image-picker__upload.busy { opacity: 0.6; cursor: wait; }
.image-picker__preview {
	display: flex;
	align-items: center;
	gap: 10px;
}
.image-picker__preview img {
	max-width: 160px;
	max-height: 90px;
	border-radius: 4px;
	border: 1px solid var(--color-border, #ddd);
	background: #fff;
}
.image-picker__preview.small img { max-width: 40px; max-height: 40px; }
.image-picker__choose { display: inline-flex; gap: 10px; align-items: center; }
.image-picker__library {
	display: grid;
	grid-template-columns: repeat(auto-fill, 44px);
	gap: 6px;
	margin-top: 8px;
	padding: 8px;
	border: 1px solid var(--color-border, #ddd);
	border-radius: var(--border-radius, 6px);
	max-width: 420px;
	background: var(--color-main-background, #fff);
}
.image-picker__library button {
	width: 44px;
	height: 44px;
	padding: 4px;
	border: 1px solid transparent;
	border-radius: 6px;
	background: none;
	cursor: pointer;
}
.image-picker__library button:hover { border-color: var(--color-primary-element, #0082c9); }
.image-picker__library img { width: 34px; height: 34px; }
.image-picker__error { color: var(--color-error, #c9302c); font-size: 12px; margin: 4px 0 0; }
.link-button {
	background: none;
	border: none;
	color: var(--color-primary-element, #0082c9);
	cursor: pointer;
	padding: 0;
	font-size: 13px;
}
</style>
