<?php

return [
	'name'        => 'SocialLogin',
	'group'       => 'Cms',
	'version'     => '1.0',
	'title'       => 'sl-plugin-title',
	'description' => 'sl-plugin-desc',
	'author'      => 'Mai Vu',
	'authorEmail' => 'mvanvu@gmail.com',
	'authorUrl'   => 'https://github.com/mvanvu',
	'updateUrl'   => null,
	'params'      => [
		[
			'name'    => 'facebookLogin',
			'type'    => 'Switcher',
			'label'   => 'sl-fb-login',
			'filters' => ['yesNo'],
			'value'   => 'Y',
		],
		[
			'name'    => 'facebookAppId',
			'type'    => 'Text',
			'label'   => 'sl-fb-app-id',
			'filters' => ['string', 'trim'],
			'showOn'  => 'facebookLogin:Y',
		],
		[
			'name'    => 'facebookAppSecret',
			'type'    => 'Text',
			'label'   => 'sl-fb-app-secret',
			'filters' => ['string', 'trim'],
			'showOn'  => 'facebookLogin:Y',
		],
		[
			'name'    => 'googleLogin',
			'type'    => 'Switcher',
			'label'   => 'sl-gg-login',
			'filters' => ['yesNo'],
			'value'   => 'Y',
		],
		[
			'name'    => 'googleClientId',
			'type'    => 'Text',
			'label'   => 'sl-gg-client-id',
			'filters' => ['string', 'trim'],
			'showOn'  => 'googleLogin:Y',
		],
		[
			'name'    => 'googleClientSecret',
			'type'    => 'Text',
			'label'   => 'sl-gg-client-secret',
			'filters' => ['string', 'trim'],
			'showOn'  => 'googleLogin:Y',
		],
	],
];