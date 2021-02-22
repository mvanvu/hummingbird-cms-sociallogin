<?php

namespace App\Plugin\Cms;

use App\Helper\Asset;
use App\Helper\Console;
use App\Helper\State;
use App\Helper\Text;
use App\Helper\Uri;
use App\Plugin\Plugin;
use Facebook\Facebook;
use Google_Client;
use Phalcon\Mvc\Router;

class SocialLogin extends Plugin
{
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
			$this->addAssets('css/social-login.css');

			return $this->getRenderer()
				->getPartial('social-login-buttons',
					[
						'pluginConfig' => $this->config,
						'fbLoginUrl'   => $fbLoginUrl,
						'ggLoginUrl'   => $ggLoginUrl,
					]
				);
		}
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
		$client = new Google_Client;
		$client->setClientId($this->config->get('params.googleClientId'));
		$client->setClientSecret($this->config->get('params.googleClientSecret'));
		$client->setRedirectUri($this->getCallBackUrl('gg'));
		$client->addScope('email');
		$client->addScope('profile');

		return $client;
	}

	public function onInitRouter(Router $router)
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

	public function install()
	{
		Console::getInstance()->composer('install', __DIR__);
	}
}