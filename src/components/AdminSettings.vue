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
				@save="onSave" />
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
import { saveSettings, uploadMedia } from '../services/SettingsService.js'
import GeneralBrandingTab from './tabs/GeneralBrandingTab.vue'

const TABS = [
	{ id: 'general', label: 'General Branding' },
	{ id: 'theming', label: 'Theming' },
	{ id: 'logo', label: 'Logo Management' },
	{ id: 'login', label: 'Login Branding' },
	{ id: 'colors', label: 'Colors' },
	{ id: 'email', label: 'Email Branding' },
	{ id: 'dashboard', label: 'Dashboard Branding' },
	{ id: 'css', label: 'Custom CSS' },
	{ id: 'js', label: 'Custom JavaScript' },
	{ id: 'advanced', label: 'Advanced' },
]

export default {
	name: 'AdminSettings',
	components: { GeneralBrandingTab },
	data() {
		return {
			tabs: TABS,
			activeTab: 'general',
			values: loadState('govmailbranding', 'settings') || {},
			errors: {},
			saving: false,
			saveMessage: '',
		}
	},
	methods: {
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
		async onSave(keys) {
			this.saving = true
			this.errors = {}
			this.saveMessage = ''
			const payload = {}
			keys.forEach((k) => {
				payload[k] = this.values[k] ?? ''
			})
			try {
				await saveSettings(payload)
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
.govmailbranding-settings__tabs {
	display: flex;
	flex-wrap: wrap;
	gap: 4px;
	border-bottom: 1px solid var(--color-border);
	margin-bottom: 16px;
}
.govmailbranding-settings__tab {
	background: none;
	border: none;
	padding: 8px 12px;
	cursor: pointer;
	border-bottom: 2px solid transparent;
	color: var(--color-text-maxcontrast);
}
.govmailbranding-settings__tab.active {
	border-bottom-color: var(--color-primary-element);
	color: var(--color-main-text);
	font-weight: bold;
}
.govmailbranding-settings__placeholder {
	padding: 24px;
	color: var(--color-text-maxcontrast);
}
.govmailbranding-settings__message {
	margin-top: 12px;
	padding: 8px 12px;
	background: var(--color-success, #46ba61);
	color: #fff;
	border-radius: var(--border-radius, 4px);
	display: inline-block;
}
</style>
