<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Service;

/**
 * Ready-made GovMail welcome email, following the structure of the IceWarp
 * welcome email (feature blocks, icon rows, split picture/text, numbered
 * steps, dark highlight) in GovMail green, describing features this
 * Nextcloud deployment actually has (Dashboard, Mail, Talk, Files,
 * Calendar, Contacts, Office Online).
 *
 * Icons are the bundled img/email/*.png set ("p-..." ids). Picture slots are
 * left empty for real GovMail screenshots, which admins upload in the tab.
 */
final class WelcomePreset {
	public static function govmail(): array {
		return [
			'subject' => 'Welcome to {instance_name}',
			'heading' => 'Welcome {displayname}',
			'intro' => 'Your {instance_name} account is ready. Here is a short tour of what you can do.',
			'button' => 'Open {instance_name}',
			'secondary_button' => 'hide',
			'secondary_text' => '',
			'secondary_url' => '',
			'accent' => '#1B8018',
			'title_color' => '#14451A',
			'closing' => "Need help? Contact the GovMail service desk.\n\n**Your {instance_name} team**\nPowered by Galaxy Backbone",
			'sections' => [
				[
					'id' => 'dash', 'layout' => 'stacked', 'columns' => 3, 'bg' => '#EEF6EE',
					'icon' => 'p-app-grid',
					'title' => 'Everything starts on your Dashboard.',
					'text' => 'Your Dashboard is your personal workspace: your latest mail, upcoming meetings, recent files and conversations, all on one screen, with every app one click away.',
					'image' => '',
					'items' => [
						['icon' => 'p-app-mail', 'title' => 'Mail', 'text' => 'Official email'],
						['icon' => 'p-app-chat', 'title' => 'Talk', 'text' => 'Chat and video calls'],
						['icon' => 'p-app-folder', 'title' => 'Files', 'text' => 'Storage and sharing'],
						['icon' => 'p-app-calendar', 'title' => 'Calendar', 'text' => 'Meetings and events'],
						['icon' => 'p-app-contacts', 'title' => 'Contacts', 'text' => 'Address book'],
						['icon' => 'p-app-doc', 'title' => 'Office Online', 'text' => 'Documents, spreadsheets, slides'],
					],
				],
				[
					'id' => 'dash2', 'layout' => 'image-right', 'columns' => 2, 'bg' => '#EEF6EE',
					'icon' => '',
					'title' => 'Pick up where you left off',
					'text' => 'Widgets show your unread mail, today\'s events and recently changed files. Choose the ones you need and arrange them your way.',
					'image' => '',
					'items' => [],
				],
				[
					'id' => 'mail', 'layout' => 'stacked', 'columns' => 2, 'bg' => '#F3F6F3',
					'icon' => 'p-app-mail',
					'title' => 'Secure email, designed for collaboration.',
					'text' => '{instance_name} keeps your official mail on government infrastructure managed by Galaxy Backbone, and you can reach it from anywhere.',
					'image' => '',
					'items' => [
						['icon' => 'p-tile-inbox', 'title' => 'Priority inbox', 'text' => 'Important messages from people you work with are shown first.'],
						['icon' => 'p-tile-mail', 'title' => 'Conversations', 'text' => 'Replies are grouped into threads so you can follow a discussion at a glance.'],
						['icon' => 'p-tile-calendar', 'title' => 'Meeting invitations', 'text' => 'Accept or decline invitations straight from the message.'],
						['icon' => 'p-tile-search', 'title' => 'Fast search', 'text' => 'Find any message by sender, subject or content.'],
					],
				],
				[
					'id' => 'talk', 'layout' => 'stacked', 'columns' => 2, 'bg' => '#EAF4EE',
					'icon' => 'p-app-chat',
					'title' => 'Chat and meet in Talk.',
					'text' => 'Keep project discussions in one place, share documents and start a call with one click.',
					'image' => '',
					'items' => [
						['icon' => 'p-tile-people', 'title' => 'Conversations', 'text' => 'Create a conversation for each team or project.'],
						['icon' => 'p-tile-video', 'title' => 'Video calls', 'text' => 'Meet face to face from your browser, no extra software needed.'],
						['icon' => 'p-tile-devices', 'title' => 'Screen sharing', 'text' => 'Show everyone in the call what is on your screen.'],
						['icon' => 'p-tile-link', 'title' => 'Invite guests', 'text' => 'Bring in external participants with a secure link.'],
					],
				],
				[
					'id' => 'files', 'layout' => 'stacked', 'columns' => 2, 'bg' => '#F0F6F0',
					'icon' => 'p-app-folder',
					'title' => 'Your files, safe and shareable.',
					'text' => 'Store documents in {instance_name}, open them on any device and share them with colleagues or external partners.',
					'image' => '',
					'items' => [
						['icon' => 'p-tile-share', 'title' => 'Share links', 'text' => 'Send a link instead of a heavy attachment.'],
						['icon' => 'p-tile-lock', 'title' => 'Password protection', 'text' => 'Protect any shared link with a password.'],
						['icon' => 'p-tile-clock', 'title' => 'Expiry dates', 'text' => 'Links stop working on the date you choose.'],
						['icon' => 'p-tile-shield', 'title' => 'Version history', 'text' => 'Restore an earlier version of a file at any time.'],
					],
				],
				[
					'id' => 'office', 'layout' => 'stacked', 'columns' => 2, 'bg' => '#EAF4EE',
					'icon' => 'p-app-doc',
					'title' => 'Edit documents together with Office Online.',
					'text' => 'Open and edit Word documents, Excel spreadsheets and PowerPoint presentations right in your browser. Nothing to install, and your files never leave {instance_name}.',
					'image' => '',
					'items' => [
						['icon' => 'p-tile-doc', 'title' => 'Documents', 'text' => 'Create and edit Word files (.docx) with familiar formatting tools.'],
						['icon' => 'p-tile-sheet', 'title' => 'Spreadsheets', 'text' => 'Work on Excel workbooks (.xlsx) with formulas, filters and charts.'],
						['icon' => 'p-tile-slides', 'title' => 'Presentations', 'text' => 'Build and present PowerPoint slides (.pptx) from any browser.'],
						['icon' => 'p-tile-people', 'title' => 'Co-authoring', 'text' => 'Edit the same file with colleagues at the same time and see their changes live.'],
						['icon' => 'p-tile-pencil', 'title' => 'Comments and track changes', 'text' => 'Review drafts together without emailing copies back and forth.'],
						['icon' => 'p-tile-download', 'title' => 'Export to PDF', 'text' => 'Download any document as PDF or in Microsoft Office format.'],
					],
				],
				[
					'id' => 'cal', 'layout' => 'stacked', 'columns' => 2, 'bg' => '#F3F6F3',
					'icon' => 'p-app-calendar',
					'title' => 'Plan your week with Calendar and Contacts.',
					'text' => 'Schedule meetings, check colleagues\' availability and keep your contacts in sync.',
					'image' => '',
					'items' => [
						['icon' => 'p-tile-people', 'title' => 'Shared calendars', 'text' => 'Share a calendar with your team or unit.'],
						['icon' => 'p-tile-mail', 'title' => 'Invitations', 'text' => 'Invite colleagues and partners by email.'],
						['icon' => 'p-tile-bell', 'title' => 'Reminders', 'text' => 'Get notified before every meeting.'],
						['icon' => 'p-tile-search', 'title' => 'Address book', 'text' => 'Find colleagues across your organisation.'],
					],
				],
				[
					'id' => 'anywhere', 'layout' => 'stacked', 'columns' => 2, 'bg' => '#0F3D0E',
					'icon' => 'p-app-globe',
					'title' => 'Work from anywhere.',
					'text' => 'Open {instance_url} in any modern browser on your computer, tablet or phone.',
					'image' => '',
					'items' => [
						['icon' => 'p-tile-devices', 'title' => 'Any device', 'text' => 'Laptop, tablet or phone.'],
						['icon' => 'p-tile-globe', 'title' => 'Always in sync', 'text' => 'Mail, files and calendar update everywhere at once.'],
					],
				],
				[
					'id' => 'start', 'layout' => 'steps', 'columns' => 2, 'bg' => '#EEF6EE',
					'icon' => 'p-app-shield',
					'title' => 'Get started in 3 steps',
					'text' => '',
					'image' => '',
					'items' => [
						['icon' => '', 'title' => 'Sign in', 'text' => 'Go to {instance_url} and sign in with **{userid}** and your email password.'],
						['icon' => '', 'title' => 'Make it yours', 'text' => 'Choose your Dashboard widgets and add a profile picture.'],
						['icon' => '', 'title' => 'Secure your account', 'text' => 'Review your devices and sessions under Personal settings > Security.'],
					],
				],
			],
		];
	}
}
