<?php

namespace MaiVu\Hummingbird\Plugin\Cms\SocialLogin;

use Phalcon\Mvc\Router;
use Facebook\Facebook;
use Google_Client;
use MaiVu\Hummingbird\Lib\Helper\Asset;
use MaiVu\Hummingbird\Lib\Helper\State;
use MaiVu\Hummingbird\Lib\Helper\Uri;
use MaiVu\Hummingbird\Lib\Helper\Text;
use MaiVu\Hummingbird\Lib\Plugin;

class SocialLogin extends Plugin
{
	public function onConstruct()
	{
		require_once __DIR__ . '/vendor/autoload.php';
		Asset::addFiles(
			[
				__DIR__ . '/Asset/Css/social-login.css',
				__DIR__ . '/Asset/Js/social-login.js',
			]
		);
	}

	public function getFBConnection()
	{
		return new Facebook(
			[
				'app_id'     => $this->config->get('params.facebookAppId'),
				'app_secret' => $this->config->get('params.facebookAppSecret'),
			]
		);
	}

	public function getCallBackUrl($prefix)
	{
		return Uri::getHost() . '/social-login/' . $prefix . '-callback/';
	}

	public function getGGConnection()
	{
		$client = new Google_Client();
		$client->setClientId($this->config->get('params.googleClientId'));
		$client->setClientSecret($this->config->get('params.googleClientSecret'));
		$client->setRedirectUri($this->getCallBackUrl('gg'));
		$client->addScope('email');
		$client->addScope('profile');

		return $client;
	}

	public function onAfterLoginForm()
	{
		State::set('socialLoginUriParams',
			[
				'language' => Text::_('locale.sef'),
				'forward'  => Uri::getActive(),
			]
		);

		$fbLoginUrl = $ggLoginUrl = '';
		$fbLogin    = $this->config->get('params.facebookLogin') === 'Y';
		$ggLogin    = $this->config->get('params.googleLogin') === 'Y';

		if ($fbLogin)
		{
			$fbLoginUrl = $this->getFBConnection()
				->getRedirectLoginHelper()
				->getLoginUrl($this->getCallBackUrl('fb'), ['public_profile', 'email']);
		}

		if ($ggLogin)
		{
			$ggLoginUrl = $this->getGGConnection()->createAuthUrl();
		}

		if ($fbLogin || $ggLogin)
		{
			return $this->getRenderer()
				->getPartial('social.login.buttons',
					[
						'pluginConfig' => $this->config,
						'fbLoginUrl'   => $fbLoginUrl,
						'ggLoginUrl'   => $ggLoginUrl,
					]
				);
		}
	}

	public function onBeforeServiceSetRouter(Router $router)
	{
		$router->add('/social-login/fb-callback/:params',
			[
				'controller' => 'social_login',
				'action'     => 'callback',
				'provider'   => 'facebook',
				'params'     => 1,
			]
		);

		$router->add('/social-login/gg-callback/:params',
			[
				'controller' => 'social_login',
				'action'     => 'callback',
				'provider'   => 'google',
				'params'     => 1,
			]
		);
	}
}