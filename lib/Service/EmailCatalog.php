<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Service;

/**
 * Every email Nextcloud 34 core and its bundled apps send, keyed by the
 * template ID passed to IMailer::createEMailTemplate(). Placeholders are the
 * keys of the $data array each sender passes in (verified against the
 * stable34 sources), so {filename} etc. are real values, not guesses.
 *
 * 'sample' is used only for the admin preview / test send of emails that
 * can't be triggered on demand. It mirrors Nextcloud's default English text.
 */
final class EmailCatalog {
	public const WELCOME = 'settings.Welcome';

	/** Placeholders available in every email. */
	public const GLOBAL_PLACEHOLDERS = ['instance_name', 'instance_url'];

	public static function all(): array {
		return [
			self::WELCOME => [
				'label' => 'Welcome email',
				'group' => 'Account',
				'placeholders' => ['displayname', 'userid', 'email', 'link'],
				'sample' => [],
			],
			'core.ResetPassword' => [
				'label' => 'Password reset',
				'group' => 'Account',
				'placeholders' => ['link'],
				'sample' => [
					'data' => ['link' => '{instance_url}/index.php/lostpassword/reset/form/sample'],
					'subject' => '{instance_name} password reset',
					'heading' => 'Password reset',
					'body' => 'Click the following button to reset your password. If you have not requested the password reset, then ignore this email.',
					'button' => 'Reset your password',
				],
			],
			'settings.PasswordChanged' => [
				'label' => 'Password changed',
				'group' => 'Account',
				'placeholders' => ['displayname', 'emailAddress', 'instanceUrl'],
				'sample' => [
					'data' => ['displayname' => 'Jane Doe', 'emailAddress' => 'jane.doe@example.gov.ng', 'instanceUrl' => '{instance_url}'],
					'subject' => 'Password for Jane Doe changed on {instance_name}',
					'heading' => 'Password changed for Jane Doe',
					'body' => 'Your password on {instance_name} was changed. If you did not request this, please contact an administrator.',
					'button' => '',
				],
			],
			'settings.EmailChanged' => [
				'label' => 'Email address changed',
				'group' => 'Account',
				'placeholders' => ['displayname', 'newEMailAddress', 'oldEMailAddress', 'instanceUrl'],
				'sample' => [
					'data' => ['displayname' => 'Jane Doe', 'newEMailAddress' => 'jane.new@example.gov.ng', 'oldEMailAddress' => 'jane@example.gov.ng', 'instanceUrl' => '{instance_url}'],
					'subject' => 'Email address for Jane Doe changed on {instance_name}',
					'heading' => 'Email address changed for Jane Doe',
					'body' => 'Your email address on {instance_name} was changed. If you did not request this, please contact an administrator.',
					'button' => '',
				],
			],
			'core.EmailVerification' => [
				'label' => 'Email verification',
				'group' => 'Account',
				'placeholders' => ['link'],
				'sample' => [
					'data' => ['link' => '{instance_url}/index.php/verifyemail/sample'],
					'subject' => '{instance_name} email verification',
					'heading' => 'Email verification',
					'body' => 'Click the following button to confirm your email.',
					'button' => 'Confirm your email',
				],
			],
			'auth.RemoteWipeStarted' => [
				'label' => 'Remote wipe started',
				'group' => 'Account',
				'placeholders' => [],
				'sample' => ['data' => [], 'subject' => 'Remote wipe started', 'heading' => 'Wiping of device "Office laptop" has started', 'body' => 'A remote wipe was started on device "Office laptop".', 'button' => ''],
			],
			'auth.RemoteWipeFinished' => [
				'label' => 'Remote wipe finished',
				'group' => 'Account',
				'placeholders' => [],
				'sample' => ['data' => [], 'subject' => 'Remote wipe finished', 'heading' => 'Wiping of device "Office laptop" has finished', 'body' => 'The remote wipe on "Office laptop" has finished.', 'button' => ''],
			],
			'files_sharing.RecipientNotification' => [
				'label' => 'File or folder shared with a user',
				'group' => 'Sharing',
				'placeholders' => ['filename', 'link', 'initiator', 'expiration', 'shareWith'],
				'sample' => [
					'data' => ['filename' => 'Budget 2026.xlsx', 'link' => '{instance_url}/f/123', 'initiator' => 'John Musa', 'expiration' => '', 'shareWith' => 'jane.doe'],
					'subject' => 'John Musa shared Budget 2026.xlsx with you',
					'heading' => 'John Musa shared Budget 2026.xlsx with you',
					'body' => '',
					'button' => 'Open Budget 2026.xlsx',
				],
			],
			'sharebymail.RecipientNotification' => [
				'label' => 'File shared by email (external recipient)',
				'group' => 'Sharing',
				'placeholders' => ['filename', 'link', 'initiator', 'expiration', 'shareWith', 'note'],
				'sample' => [
					'data' => ['filename' => 'Budget 2026.xlsx', 'link' => '{instance_url}/s/AbCdEf', 'initiator' => 'John Musa', 'expiration' => '', 'shareWith' => 'partner@example.com', 'note' => 'Please review before Friday.'],
					'subject' => 'John Musa shared Budget 2026.xlsx with you',
					'heading' => 'John Musa shared Budget 2026.xlsx with you',
					'body' => '',
					'button' => 'Open shared item',
				],
			],
			'sharebymail.RecipientPasswordNotification' => [
				'label' => 'Share password (to recipient)',
				'group' => 'Sharing',
				'placeholders' => ['filename', 'password', 'initiator', 'initiatorEmail', 'shareWith'],
				'sample' => [
					'data' => ['filename' => 'Budget 2026.xlsx', 'password' => 'Xy7-sample', 'initiator' => 'John Musa', 'initiatorEmail' => 'john.musa@example.gov.ng', 'shareWith' => 'partner@example.com'],
					'subject' => 'Password to access Budget 2026.xlsx shared to you by John Musa',
					'heading' => 'Password to access Budget 2026.xlsx',
					'body' => 'It is protected with the following password: Xy7-sample',
					'button' => '',
				],
			],
			'sharebymail.OwnerPasswordNotification' => [
				'label' => 'Share password (copy to owner)',
				'group' => 'Sharing',
				'placeholders' => ['filename', 'password', 'initiator', 'initiatorEmail', 'shareWith'],
				'sample' => [
					'data' => ['filename' => 'Budget 2026.xlsx', 'password' => 'Xy7-sample', 'initiator' => 'John Musa', 'initiatorEmail' => 'john.musa@example.gov.ng', 'shareWith' => 'partner@example.com'],
					'subject' => 'Password to access Budget 2026.xlsx shared by you with partner@example.com',
					'heading' => 'Password to access Budget 2026.xlsx',
					'body' => 'You just shared Budget 2026.xlsx with partner@example.com. The password is: Xy7-sample',
					'button' => '',
				],
			],
			'defaultShareProvider.sendNote' => [
				'label' => 'Note added to a share',
				'group' => 'Sharing',
				'placeholders' => [],
				'sample' => ['data' => [], 'subject' => 'John Musa added a note to a file shared with you', 'heading' => 'John Musa added a note to a file shared with you', 'body' => 'Please review before Friday.', 'button' => 'Open Budget 2026.xlsx'],
			],
			'shareByMail.sendNote' => [
				'label' => 'Note added to an email share',
				'group' => 'Sharing',
				'placeholders' => [],
				'sample' => ['data' => [], 'subject' => 'John Musa added a note to a file shared with you', 'heading' => 'John Musa added a note to a file shared with you', 'body' => 'Please review before Friday.', 'button' => 'Open Budget 2026.xlsx'],
			],
			'files_sharing.SharesReminder' => [
				'label' => 'Reminder: empty shared folder',
				'group' => 'Sharing',
				'placeholders' => ['folder'],
				'sample' => ['data' => ['folder' => 'Project files'], 'subject' => 'Remember to upload the files to Project files', 'heading' => 'Remember to upload the files to Project files', 'body' => 'You have shared the folder Project files but it is still empty.', 'button' => 'Open Project files'],
			],
			'dav.calendarInvite.REQUEST' => [
				'label' => 'Calendar invitation',
				'group' => 'Calendar',
				'placeholders' => ['attendee_name', 'invitee_name', 'meeting_title', 'meeting_when', 'meeting_location', 'meeting_url', 'meeting_description'],
				'sample' => [
					'data' => ['attendee_name' => 'Jane Doe', 'invitee_name' => 'John Musa', 'meeting_title' => 'Quarterly review', 'meeting_when' => 'Monday, 20 October 2026 10:00 - 11:00 (Africa/Lagos)', 'meeting_location' => 'Conference room 2', 'meeting_url' => '', 'meeting_description' => ''],
					'subject' => 'Invitation: Quarterly review',
					'heading' => 'John Musa has invited you to "Quarterly review"',
					'body' => 'Monday, 20 October 2026 10:00 - 11:00 (Africa/Lagos) · Conference room 2',
					'button' => '',
				],
			],
			'dav.calendarInvite.CANCEL' => [
				'label' => 'Calendar invitation cancelled',
				'group' => 'Calendar',
				'placeholders' => ['attendee_name', 'invitee_name', 'meeting_title', 'meeting_when', 'meeting_location'],
				'sample' => [
					'data' => ['attendee_name' => 'Jane Doe', 'invitee_name' => 'John Musa', 'meeting_title' => 'Quarterly review', 'meeting_when' => 'Monday, 20 October 2026 10:00 - 11:00 (Africa/Lagos)', 'meeting_location' => 'Conference room 2'],
					'subject' => 'Cancelled: Quarterly review',
					'heading' => '"Quarterly review" has been cancelled',
					'body' => 'Monday, 20 October 2026 10:00 - 11:00 (Africa/Lagos)',
					'button' => '',
				],
			],
			'dav.calendarInvite.REPLY' => [
				'label' => 'Calendar invitation reply',
				'group' => 'Calendar',
				'placeholders' => ['attendee_name', 'invitee_name', 'meeting_title', 'meeting_when'],
				'sample' => [
					'data' => ['attendee_name' => 'John Musa', 'invitee_name' => 'Jane Doe', 'meeting_title' => 'Quarterly review', 'meeting_when' => 'Monday, 20 October 2026 10:00 - 11:00 (Africa/Lagos)'],
					'subject' => 'Re: Quarterly review',
					'heading' => 'Jane Doe has accepted your invitation',
					'body' => 'Monday, 20 October 2026 10:00 - 11:00 (Africa/Lagos)',
					'button' => '',
				],
			],
			'dav.calendarReminder' => [
				'label' => 'Calendar event reminder',
				'group' => 'Calendar',
				'placeholders' => [],
				'sample' => ['data' => [], 'subject' => 'Notification: Quarterly review', 'heading' => 'Quarterly review', 'body' => 'Monday, 20 October 2026 10:00 - 11:00 · Conference room 2', 'button' => ''],
			],
			'settings.TestEmail' => [
				'label' => 'Test email (Basic settings)',
				'group' => 'Administration',
				'placeholders' => ['displayname'],
				'sample' => ['data' => ['displayname' => 'Admin'], 'subject' => 'Email setting test', 'heading' => 'Well done, Admin!', 'body' => 'If you received this email, the email configuration seems to be correct.', 'button' => ''],
			],
			'encryption.encryptAllPassword' => [
				'label' => 'Encryption password',
				'group' => 'Administration',
				'placeholders' => ['user', 'password'],
				'sample' => ['data' => ['user' => 'jane.doe', 'password' => 'Xy7-sample'], 'subject' => 'Encrypt all files: your password', 'heading' => 'Your encryption password', 'body' => 'Your password is: Xy7-sample', 'button' => ''],
			],
		];
	}

	public static function get(string $id): ?array {
		return self::all()[$id] ?? null;
	}

	/**
	 * Emails sent by other apps (Mail, Talk, Activity, Deck...) that we only
	 * learn about at runtime. They get a generic entry.
	 */
	public static function generic(string $id): array {
		return [
			'label' => $id,
			'group' => 'Other apps',
			'placeholders' => [],
			'sample' => ['data' => [], 'subject' => 'Notification from {instance_name}', 'heading' => 'Notification', 'body' => 'This is how a notification from another app looks.', 'button' => ''],
		];
	}

	/** IDs that carry information users need (passwords, links) in their body text. */
	public static function bodyCarriesEssentials(string $id): bool {
		return in_array($id, [
			'sharebymail.RecipientPasswordNotification',
			'sharebymail.OwnerPasswordNotification',
			'encryption.encryptAllPassword',
			'dav.calendarInvite.REQUEST',
			'dav.calendarInvite.CANCEL',
			'dav.calendarInvite.REPLY',
			'dav.calendarReminder',
			'defaultShareProvider.sendNote',
			'shareByMail.sendNote',
		], true);
	}
}
