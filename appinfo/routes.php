<?php

declare(strict_types=1);

return [
	'routes' => [
		['name' => 'settings#index', 'url' => '/settings', 'verb' => 'GET'],
		['name' => 'settings#save', 'url' => '/settings', 'verb' => 'POST'],
		['name' => 'media#upload', 'url' => '/media/{slot}', 'verb' => 'POST'],
		['name' => 'media#get', 'url' => '/media/{fileName}', 'verb' => 'GET'],
	],
];
