/**
 * Small pop-up notifications ("toasts") in the top-right corner, styled with
 * Nextcloud's theme variables. Success messages close after a few seconds;
 * errors stay longer and can be closed with the × button.
 *
 *   import { toastSuccess, toastError } from '../toast.js'
 *   toastSuccess('Saved.')
 *   toastError('Save failed: …')
 */

const CONTAINER_ID = 'govmailbranding-toasts'

function container() {
	let el = document.getElementById(CONTAINER_ID)
	if (!el) {
		el = document.createElement('div')
		el.id = CONTAINER_ID
		el.setAttribute('aria-live', 'polite')
		Object.assign(el.style, {
			position: 'fixed',
			top: '64px',
			right: '20px',
			zIndex: '100000',
			display: 'flex',
			flexDirection: 'column',
			gap: '10px',
			maxWidth: '420px',
		})
		document.body.appendChild(el)
	}
	return el
}

export function toast(message, type = 'success', timeout = null) {
	const colors = {
		success: 'var(--color-success, #2d7b41)',
		error: 'var(--color-error, #c9302c)',
		info: 'var(--color-primary-element, #0082c9)',
	}
	const item = document.createElement('div')
	item.setAttribute('role', type === 'error' ? 'alert' : 'status')
	Object.assign(item.style, {
		display: 'flex',
		alignItems: 'flex-start',
		gap: '12px',
		padding: '12px 14px',
		background: 'var(--color-main-background, #fff)',
		color: 'var(--color-main-text, #222)',
		borderLeft: '5px solid ' + (colors[type] || colors.info),
		borderRadius: 'var(--border-radius-large, 10px)',
		boxShadow: '0 4px 18px rgba(0,0,0,0.18)',
		fontSize: '14px',
		lineHeight: '1.4',
		opacity: '0',
		transform: 'translateY(-6px)',
		transition: 'opacity .2s, transform .2s',
	})

	const icon = document.createElement('span')
	icon.textContent = type === 'error' ? '✕' : (type === 'success' ? '✓' : 'ℹ')
	Object.assign(icon.style, { color: colors[type] || colors.info, fontWeight: '700' })

	const text = document.createElement('span')
	text.textContent = message
	text.style.flex = '1'
	text.style.wordBreak = 'break-word'

	const close = document.createElement('button')
	close.type = 'button'
	close.textContent = '×'
	close.setAttribute('aria-label', 'Close')
	Object.assign(close.style, {
		border: 'none',
		background: 'none',
		cursor: 'pointer',
		fontSize: '18px',
		lineHeight: '1',
		padding: '0 2px',
		color: 'var(--color-text-maxcontrast, #767676)',
		minHeight: 'auto',
	})

	const remove = () => {
		item.style.opacity = '0'
		setTimeout(() => item.remove(), 200)
	}
	close.addEventListener('click', remove)

	item.append(icon, text, close)
	container().appendChild(item)
	requestAnimationFrame(() => {
		item.style.opacity = '1'
		item.style.transform = 'none'
	})
	setTimeout(remove, timeout ?? (type === 'error' ? 10000 : 4000))
}

export const toastSuccess = (m) => toast(m, 'success')
export const toastError = (m) => toast(m, 'error')
export const toastInfo = (m) => toast(m, 'info')
