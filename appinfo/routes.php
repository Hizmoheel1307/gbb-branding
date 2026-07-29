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
		['name' => 'media#get', 'url' => '/media/{fileName}', 'verb' => 'GET'],
	],
];
