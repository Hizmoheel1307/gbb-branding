<?php

declare(strict_types=1);

return [
	'routes' => [
		['name' => 'settings#index', 'url' => '/settings', 'verb' => 'GET'],
		['name' => 'settings#save', 'url' => '/settings', 'verb' => 'POST'],
		['name' => 'theming_settings#index', 'url' => '/theming-settings', 'verb' => 'GET'],
		['name' => 'theming_settings#save', 'url' => '/theming-settings', 'verb' => 'POST'],
		['name' => 'theming_image#upload', 'url' => '/theming-image/{key}', 'verb' => 'POST'],
		['name' => 'media#upload', 'url' => '/media/{slot}', 'verb' => 'POST'],
		['name' => 'email#status', 'url' => '/email/status', 'verb' => 'GET'],
		['name' => 'email#setOverride', 'url' => '/email/override', 'verb' => 'POST'],
		['name' => 'email#sendTest', 'url' => '/email/test', 'verb' => 'POST'],
		['name' => 'email_templates#index', 'url' => '/email-templates', 'verb' => 'GET'],
		['name' => 'email_templates#saveOverride', 'url' => '/email-templates/override', 'verb' => 'POST'],
		['name' => 'email_templates#resetOverride', 'url' => '/email-templates/override/reset', 'verb' => 'POST'],
		['name' => 'email_templates#saveWelcome', 'url' => '/email-templates/welcome', 'verb' => 'POST'],
		['name' => 'email_templates#uploadImage', 'url' => '/email-templates/image', 'verb' => 'POST'],
		['name' => 'email_templates#image', 'url' => '/email-templates/image/{id}', 'verb' => 'GET'],
		['name' => 'email_templates#preview', 'url' => '/email-templates/preview', 'verb' => 'POST'],
		['name' => 'email_templates#sendTest', 'url' => '/email-templates/test', 'verb' => 'POST'],
		['name' => 'media#get', 'url' => '/media/{fileName}', 'verb' => 'GET'],
	],
];
