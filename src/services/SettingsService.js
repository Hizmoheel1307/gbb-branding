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
