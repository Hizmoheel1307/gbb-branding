import Vue from 'vue'
import AdminSettings from './components/AdminSettings.vue'

const el = document.getElementById('govmailbranding-admin-settings')
if (el) {
	// eslint-disable-next-line no-new
	new Vue({
		render: (h) => h(AdminSettings),
	}).$mount(el)
}
