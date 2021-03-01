<?php

namespace App\Plugin\Cms;

use App\Factory\WebApplication;
use App\Helper\Console;
use App\Helper\Router;
use App\Helper\State;
use App\Helper\Text;
use App\Helper\Uri;
use App\Plugin\Plugin;
use Facebook\Facebook;
use Google_Client;

class SocialLogin extends Plugin
{
	public function onBootCms(WebApplication $app)
	{
		$router = Router::getInstance();
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

		if ($fbLogin || $ggLogin)
		{
			require_once PLUGIN_PATH . '/Cms/SocialLogin/vendor/autoload.php';
			$this->addAssets('css/social-login.css');

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

	public function install()
	{
		Console::getInstance()->composer('install', __DIR__);
	}

	public function update()
	{
		Console::getInstance()->composer('update', __DIR__);
	}
}