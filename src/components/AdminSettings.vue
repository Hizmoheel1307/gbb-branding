<template>
	<div class="govmailbranding-settings">
		<div class="govmailbranding-settings__tabs">
			<button
				v-for="tab in tabs"
				:key="tab.id"
				:class="['govmailbranding-settings__tab', { active: activeTab === tab.id }]"
				type="button"
				@click="activeTab = tab.id">
				{{ tab.label }}
			</button>
		</div>

		<div class="govmailbranding-settings__panel">
			<GeneralBrandingTab
				v-if="activeTab === 'general'"
				:values="values"
				:errors="errors"
				:saving="saving"
				@update="onFieldUpdate"
				@upload="onImageUpload"
				@save="(keys) => onSave(keys, 'general')" />
			<ThemingTab
				v-else-if="activeTab === 'theming'"
				:values="values"
				:images="themingImages"
				:errors="errors"
				:saving="saving"
				@update="onFieldUpdate"
				@upload="onThemingImageUpload"
				@save="(keys) => onSave(keys, 'theming')" />
			<EmailBrandingTab
				v-else-if="activeTab === 'email'"
				:values="values"
				:errors="errors"
				:saving="saving"
				@update="onFieldUpdate"
				@save="(keys) => onSave(keys, 'general')" />
			<div v-else class="govmailbranding-settings__placeholder">
				This section is coming soon.
			</div>
		</div>

		<div v-if="saveMessage" class="govmailbranding-settings__message">
			{{ saveMessage }}
		</div>
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { fetchSettings, saveSettings, fetchThemingSettings, saveThemingSettings, uploadMedia, uploadThemingImage } from '../services/SettingsService.js'
import GeneralBrandingTab from './tabs/GeneralBrandingTab.vue'
import ThemingTab from './tabs/ThemingTab.vue'
import EmailBrandingTab from './tabs/EmailBrandingTab.vue'

const TABS = [
	{ id: 'general', label: 'General Branding' },
	{ id: 'theming', label: 'Theming' },
	{ id: 'login', label: 'Login Branding' },
	{ id: 'email', label: 'Email Branding' },
	{ id: 'dashboard', label: 'Dashboard Branding' },
	{ id: 'css', label: 'Custom CSS' },
	{ id: 'js', label: 'Custom JavaScript' },
	{ id: 'advanced', label: 'Advanced' },
]

export default {
	name: 'AdminSettings',
	components: { GeneralBrandingTab, ThemingTab, EmailBrandingTab },
	data() {
		return {
			tabs: TABS,
			activeTab: 'general',
			values: loadState('govmailbranding', 'settings') || {},
			themingImages: {},
			errors: {},
			saving: false,
			saveMessage: '',
		}
	},
	mounted() {
		this.refreshSettings()
	},
	methods: {
		async refreshSettings() {
			try {
				const [ours, theming] = await Promise.all([fetchSettings(), fetchThemingSettings()])
				this.values = { ...this.values, ...ours, ...theming.values }
				this.themingImages = { ...this.themingImages, ...theming.images }
			} catch (e) {
				// initial state already seeded this.values with sane defaults; safe to ignore
			}
		},
		onFieldUpdate(key, value) {
			this.$set(this.values, key, value)
		},
		async onImageUpload(slot, file) {
			try {
				const result = await uploadMedia(slot, file)
				this.$set(this.values, slot, result.fileName)
				this.saveMessage = 'Image uploaded.'
			} catch (e) {
				this.saveMessage = 'Image upload failed.'
			}
		},
		async onThemingImageUpload(key, file) {
			try {
				const result = await uploadThemingImage(key, file)
				this.$set(this.themingImages, key, result.url)
				this.saveMessage = 'Image uploaded and applied instance-wide.'
			} catch (e) {
				this.saveMessage = (e.response && e.response.data && e.response.data.error) || 'Image upload failed.'
			}
		},
		async onSave(keys, target = 'general') {
			this.saving = true
			this.errors = {}
			this.saveMessage = ''
			const payload = {}
			keys.forEach((k) => {
				payload[k] = this.values[k] ?? ''
			})
			try {
				if (target === 'theming') {
					await saveThemingSettings(payload)
				} else {
					await saveSettings(payload)
				}
				this.saveMessage = 'Settings saved.'
			} catch (e) {
				if (e.response && e.response.data && e.response.data.errors) {
					this.errors = e.response.data.errors
				} else {
					this.saveMessage = 'Save failed.'
				}
			} finally {
				this.saving = false
			}
		},
	},
}
</script>

<style scoped>
.govmailbranding-settings {
	max-width: 900px;
	margin: 0 auto;
	padding: 0 16px;
}
.govmailbranding-settings__tabs {
	display: flex;
	flex-wrap: wrap;
	gap: 4px;
	border-bottom: 1px solid var(--color-border);
	margin-bottom: 24px;
	padding-bottom: 0;
}
.govmailbranding-settings__tab {
	background: none;
	border: none;
	padding: 10px 16px;
	cursor: pointer;
	border-bottom: 2px solid transparent;
	color: var(--color-text-maxcontrast);
	font-size: 14px;
	white-space: nowrap;
}
.govmailbranding-settings__tab.active {
	border-bottom-color: var(--color-primary-element);
	color: var(--color-main-text);
	font-weight: bold;
}
.govmailbranding-settings__panel {
	max-width: 500px;
	margin: 0 auto;
}
.govmailbranding-settings__placeholder {
	padding: 40px 24px;
	text-align: center;
	color: var(--color-text-maxcontrast);
}
.govmailbranding-settings__message {
	margin: 16px auto 0;
	padding: 8px 12px;
	background: var(--color-success, #46ba61);
	color: #fff;
	border-radius: var(--border-radius, 4px);
	display: block;
	text-align: center;
	max-width: 500px;
}
</style>
