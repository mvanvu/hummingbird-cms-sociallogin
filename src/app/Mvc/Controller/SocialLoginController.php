<?php

namespace App\Mvc\Controller;

use App\Helper\Config;
use App\Helper\Event;
use App\Helper\State;
use App\Helper\Text;
use App\Helper\Uri;
use App\Helper\User as Auth;
use App\Mvc\Model\User as UserModel;
use App\Plugin\Cms\SocialLogin;
use Facebook\Exceptions\FacebookSDKException;
use Google_Service_Oauth2;
use Google_Service_Oauth2_Userinfoplus;
use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Dispatcher;

class SocialLoginController extends Controller
{
	/** @var SocialLogin */
	protected $pluginHandler;

	public function beforeExecuteRoute(Dispatcher $dispatcher)
	{
		if (!($plugin = Event::getPlugin('Cms', 'SocialLogin')))
		{
			$dispatcher->forward(
				[
					'controller' => 'error',
					'action'     => 'show',
				]
			);

			return false;
		}

		$this->pluginHandler = Event::getHandler($plugin);
	}

	public function callbackAction()
	{
		$redirect = $this->getRedirectUri();

		if (!Auth::is('guest'))
		{
			return $this->response->redirect($redirect, true);
		}

		require_once PLUGIN_PATH . '/Cms/SocialLogin/vendor/autoload.php';
		$config   = $this->pluginHandler->getConfig();
		$provider = $this->dispatcher->getParam('provider');

		if ($provider === 'facebook' && $config->get('params.facebookLogin') === 'Y')
		{
			return $this->fbCallback();
		}

		if ($provider === 'google' && $config->get('params.googleLogin') === 'Y')
		{
			return $this->ggCallback();
		}

		return $this->response->redirect($redirect, true);
	}

	protected function getRedirectUri()
	{
		$params   = State::get('socialLoginUriParams', []);
		$language = isset($params['language']) ? $params['language'] : null;

		if (isset($params['forward']))
		{
			$uri = Uri::fromUrl($params['forward']);

			if (!$uri->isInternal())
			{
				$uri = Uri::getInstance(['uri' => 'user/account', 'language' => $language]);
			}
		}
		else
		{
			$uri = Uri::getInstance(['uri' => 'user/account', 'language' => $language]);
		}

		return $uri->toString(false);
	}

	protected function fbCallback()
	{
		$fb     = $this->pluginHandler->getFBConnection();
		$helper = $fb->getRedirectLoginHelper();
		$helper->getPersistentDataHandler()->set('state', $this->request->get('state'));
		$redirect = $this->getRedirectUri();

		try
		{
			$accessToken = $helper->getAccessToken();
		}
		catch (FacebookSDKException $e)
		{
			$this->dispatcher->forward(
				[
					'controller' => 'error',
					'action'     => 'show',
					'params'     => [
						'title'   => 'Facebook SDK returned an error',
						'code'    => $e->getCode(),
						'message' => $e->getMessage(),
					],
				]
			);

			return false;
		}

		if (empty($accessToken))
		{
			if ($helper->getError())
			{
				$this->dispatcher->forward(
					[
						'controller' => 'error',
						'action'     => 'show',
						'params'     => [
							'code'    => $helper->getErrorCode(),
							'title'   => $helper->getError(),
							'message' => $helper->getErrorDescription(),
						],
					]
				);

				return false;
			}

			$this->dispatcher->forward(
				[
					'controller' => 'error',
					'action'     => 'show',
				]
			);

			return false;
		}

		$oAuth2Client  = $fb->getOAuth2Client();
		$tokenMetadata = $oAuth2Client->debugToken($accessToken);

		try
		{
			$tokenMetadata->validateAppId($fb->getApp()->getId());
			$tokenMetadata->validateExpiration();

			if (!$accessToken->isLongLived())
			{
				$accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
			}

			$response = $fb->get('/me?fields=id,name,email', $accessToken->getValue());
			$fbUser   = $response->getGraphUser();
			$this->login($fbUser['id'], $fbUser['name'], $fbUser['email']);
		}
		catch (FacebookSDKException $e)
		{
			$this->dispatcher->forward(
				[
					'controller' => 'error',
					'action'     => 'show',
					'params'     => [
						'title'   => 'Facebook SDK returned an error',
						'message' => $e->getMessage(),
					],
				]
			);

			return false;
		}

		return $this->response->redirect($redirect);
	}

	protected function login($id, $name, $email)
	{
		if ($user = $this->findUserByEmail($email))
		{
			if ($user->active === 'Y')
			{
				Auth::getInstance($user)->setActive();
			}
			else
			{
				$this->flashSession->error(Text::_('sl-user-was-banned-msg', ['email' => $email]));
			}
		}
		else
		{
			State::setMark('user.registering', true);
			$newUser  = new UserModel;
			$userData = [
				'id'       => 0,
				'name'     => $name,
				'email'    => $email,
				'username' => $email,
				'password' => $this->security->hash($id . ':' . $name . ':' . $email),
				'role'     => 'R',
				'active'   => 'Y',
				'token'    => null,
				'params'   => [
					'timezone' => Config::get('timezone', 'UTC'),
					'avatar'   => '',
				],
			];

			$params = [
				'conditions' => 'username = :username:',
				'bind'       => [
					'username' => $userData['username'],
				],
			];

			if (UserModel::findFirst($params))
			{
				$parts                      = explode('@', $userData['username']);
				$userData['username']       = $parts[0];
				$params['bind']['username'] = $userData['username'];

				if (UserModel::findFirst($params))
				{
					$pattern                    = '/[\x00-\x1F\x7F<>"\'%&]/';
					$userData['username']       = preg_replace($pattern, '', $userData['name']);
					$params['bind']['username'] = $userData['username'];

					if (UserModel::findFirst($params))
					{
						$userData['username'] .= $id;
					}
				}
			}

			if ($newUser->assign($userData)->save())
			{
				Auth::getInstance($newUser)->setActive();
			}
		}
	}

	protected function findUserByEmail($email)
	{
		return UserModel::findFirst(
			[
				'conditions' => 'email = :email:',
				'bind'       => [
					'email' => $email,
				],
			]
		);
	}

	protected function ggCallback()
	{
		$gg    = $this->pluginHandler->getGGConnection();
		$token = $gg->fetchAccessTokenWithAuthCode($this->request->get('code'));

		if (!empty($token['access_token']))
		{
			$gg->setAccessToken($token['access_token']);

			// get profile info
			$ggOauth = new Google_Service_Oauth2($gg);
			$ggUser  = $ggOauth->userinfo->get();

			if ($ggUser instanceof Google_Service_Oauth2_Userinfoplus)
			{
				$this->login($ggUser->id, $ggUser->name, $ggUser->email);
			}
		}

		return $this->response->redirect($this->getRedirectUri(), true);
	}
}