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

export async function fetchEmailStatus() {
	const response = await axios.get(generateUrl('/apps/govmailbranding/email/status'))
	return response.data
}

export async function setEmailOverride(enabled) {
	const response = await axios.post(generateUrl('/apps/govmailbranding/email/override'), { enabled })
	return response.data
}

// Resolves with { result, status } even when the send fails (HTTP 422),
// so the UI can show the server's error message.
export async function sendTestEmail(to) {
	try {
		const response = await axios.post(generateUrl('/apps/govmailbranding/email/test'), { to })
		return response.data
	} catch (error) {
		if (error.response && error.response.data && error.response.data.result) {
			return error.response.data
		}
		throw error
	}
}

// ---- Email Templates tab ----

const ET = '/apps/govmailbranding/email-templates'

export async function fetchEmailTemplates() {
	return (await axios.get(generateUrl(ET))).data
}

export async function saveEmailOverride(emailId, override) {
	return (await axios.post(generateUrl(ET + '/override'), { emailId, override })).data
}

export async function resetEmailOverride(emailId) {
	return (await axios.post(generateUrl(ET + '/override/reset'), { emailId })).data
}

export async function saveWelcomeEmail(welcome) {
	return (await axios.post(generateUrl(ET + '/welcome'), { welcome })).data
}

export async function uploadEmailImage(file) {
	const formData = new FormData()
	formData.append('file', file)
	return (await axios.post(generateUrl(ET + '/image'), formData, { headers: { 'Content-Type': 'multipart/form-data' } })).data
}

export function emailImageUrl(id) {
	return generateUrl(ET + '/image/' + id)
}

export async function previewEmail(emailId) {
	return (await axios.post(generateUrl(ET + '/preview'), { emailId })).data
}

// Resolves with the server's result even on HTTP 422 so the UI can show why.
export async function sendTestTemplate(emailId, to) {
	try {
		return (await axios.post(generateUrl(ET + '/test'), { emailId, to })).data
	} catch (error) {
		if (error.response && error.response.data) {
			return { ok: false, ...error.response.data }
		}
		throw error
	}
}

export async function fetchWelcomePreset() {
	return (await axios.get(generateUrl(ET + '/welcome-preset'))).data
}

export async function saveEmailHeader(header) {
	return (await axios.post(generateUrl(ET + '/header'), { header })).data
}
