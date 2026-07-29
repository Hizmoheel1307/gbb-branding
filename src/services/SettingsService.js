import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export async function fetchSettings() {
	const response = await axios.get(generateUrl('/apps/govmailbranding/settings'))
	return response.data
}

export async function saveSettings(values) {
	const response = await axios.post(generateUrl('/apps/govmailbranding/settings'), { values })
	return response.data
}

export async function fetchThemingSettings() {
	const response = await axios.get(generateUrl('/apps/govmailbranding/theming-settings'))
	return response.data
}

export async function saveThemingSettings(values) {
	const response = await axios.post(generateUrl('/apps/govmailbranding/theming-settings'), { values })
	return response.data
}

export async function uploadThemingImage(key, file) {
	const formData = new FormData()
	formData.append('file', file)
	const response = await axios.post(
		generateUrl(`/apps/govmailbranding/theming-image/${key}`),
		formData,
		{ headers: { 'Content-Type': 'multipart/form-data' } },
	)
	return response.data
}

export async function uploadMedia(slot, file) {
	const formData = new FormData()
	formData.append('file', file)
	const response = await axios.post(
		generateUrl(`/apps/govmailbranding/media/${slot}`),
		formData,
		{ headers: { 'Content-Type': 'multipart/form-data' } },
	)
	return response.data
}
