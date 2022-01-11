<?php
namespace RigUser\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Zend\Mail;

class UserController extends AbstractActionController
{
	/**
	 * @var Zend\Authentication\AuthenticationService
	 */
	protected $authService;
	/**
	 * @var RigUser\Model\UserEntity|boolean false
	 */
	protected $user = false;
	/**
	 * @var boolean
	 */
	protected $isAdmin = false;
	/**
	 * @var boolean
	 */
	protected $isModerator = false;
	
	function onDispatch(MvcEvent $e)
	{
		$e->getApplication()->getServiceManager()->get('viewHelperManager')->get('headTitle')->append(ucfirst(str_replace('-', ' ', $e->getRouteMatch()->getParam('action'))));
		
		$this->authService = $this->getServiceLocator()->get('Auth');
		
		if ($this->authService->hasIdentity())
		{
			$this->user = $this->authService->getStorage()->read();
			$this->isAdmin = $this->user->getRoleId() == 1 ? true : false;
			$this->isModerator = $this->user->getRoleId() == 2 ? true : false;
		}
		
		parent::onDispatch($e);
	}
	
	function loginAction()
	{
		if ($this->user)
		{
			return $this->redirect()->toRoute('user', array('action' => 'edit'));
		}
		
		$form = $this->getServiceLocator()->get('LoginForm')->setAttribute('action', $this->url()->fromRoute('user', array('action' => 'login')));
		$request = $this->getRequest();
		
		if ($request->isPost() && $form->setData($request->getPost())->isValid())
		{
			$this->auth($form->getData());
			
			//not implemented in this module
			if ($lastUrl = (new Container('lastUrl'))->lastUrl)
			{
				return $this->redirect()->toUrl($lastUrl);
			}
			return $this->redirect()->toRoute('user', array('action' => 'edit'));
		}
		
		return array(
			'form' => $form,
		);
	}
	
	function logoutAction()
	{
		$this->authService->getStorage()->clear();
		$this->authService->clearIdentity();
		
		return $this->redirect()->toRoute('user', array('action' => 'login'));
	}
	
	function editAction()
	{
		if (!$this->user)
		{
			return $this->redirect()->toRoute('user', array('action' => 'login'));
		}
		
		$form = $this->getServiceLocator()->get('EditForm')->setAttribute('action', $this->url()->fromRoute('user', array('action' => 'edit')));
		$request = $this->getRequest();
		
		if ($request->isPost() && $form->setData($request->getPost())->isValid())
		{
			$credentials = array(
				'email' => $this->user->getEmail(),
				'password' => $form->getData()->getPassword(),
			);
			
			if ($this->auth($credentials) && $this->getServiceLocator()->get('UserMapper')->updateUser($form->getData()))
			{
				$this->auth($credentials);
				$this->flashMessenger()->addMessage('Account updated.');
				
				return $this->redirect()->toRoute('user', array('action' => 'edit'));
			}
		}
		
		return array(
			'form' => $form,
			'user' => $this->user,
		);
	}
	
	function changePasswordAction()
	{
		if (!$this->user)
		{
			return $this->redirect()->toRoute('user', array('action' => 'login'));
		}
		
		$form = $this->getServiceLocator()->get('ChangePasswordForm')->setAttribute('action', $this->url()->fromRoute('user', array('action' => 'change-password')));
		$request = $this->getRequest();
		
		if ($request->isPost() && $form->setData($request->getPost())->isValid())
		{
			$auth = $this->auth(array(
				'email' => $this->user->getEmail(),
				'password' => $request->getPost('password'),
			));
			
			if ($auth && $this->getServiceLocator()->get('UserMapper')->changePassword($this->user->getId(), $request->getPost('new_password')))
			{
				$this->flashMessenger()->addMessage('Password changed.');
				
				return $this->redirect()->toRoute('user', array('action' => 'edit'));
			}
		}
		
		$view = new ViewModel(array('form' => $form));
		$view->setTemplate('partial/form.phtml');
		
		return $view;
	}
	
	function changeEmailAction()
	{
		if (!$this->user)
		{
			return $this->redirect()->toRoute('user', array('action' => 'login'));
		}
		
		$form = $this->getServiceLocator()->get('ChangeEmailForm')->setAttribute('action', $this->url()->fromRoute('user', array('action' => 'change-email')));
		$request = $this->getRequest();
		
		if ($request->isPost() && $form->setData($request->getPost())->isValid())
		{
			$data = $form->getData();
			
			$auth = $this->auth(array(
				'email' => $this->user->getEmail(),
				'password' => $data->getPassword(),
			));
			
			if ($auth)
			{
				$data->setId($this->user->getId());
				$data->setName($this->user->getName());
				$data->setToken(md5($data->getId() . rand(1, 99999)));

				if ($this->getServiceLocator()->get('UserMapper')->changeEmail($data))
				{
					$url = $this->url()->fromRoute('user', array('action' => 'confirm-email', 'param' => $data->getToken()), array('force_canonical' => true));
					$this->sendEmail($data, 'Confirm your email', "Please follow this link to activate your account:\n".$url);
					$this->flashMessenger()->addMessage('Verification email sent.');

					return $this->redirect()->toRoute('user', array('action' => 'logout'));
				}
			}
		}
		
		$view = new ViewModel(array(
			'message' => 'change_email_confirmation',
			'form' => $form,
		));
		$view->setTemplate('partial/form.phtml');
		
		return $view;
	}
	
	function registerAction()
	{
		if ($this->user)
		{
			return $this->redirect()->toRoute('user', array('action' => 'edit'));
		}
		
		$form = $this->getServiceLocator()->get('RegisterForm')->setAttribute('action', $this->url()->fromRoute('user', array('action' => 'register')));
		$request = $this->getRequest();
		
		if ($request->isPost() && $form->setData($request->getPost())->isValid())
		{
			$data = $form->getData();
			$emailRegistration = $this->getServiceLocator()->get('EnableEmailRegistration');
			
			if ($emailRegistration)
			{
				$data->setRoleId(3);
				$data->setPending(1);
				$data->setToken(md5($data->getEmail() . rand(1, 99999)));
				$msg = 'Verification email sent.';
			}
			else
			{
				$data->setRoleId(-1);
				$data->setPending(0);
				$msg = 'Account created. You will be notified by email when it has been activated.';
			}
			
			if ($this->getServiceLocator()->get('UserMapper')->insertUser($data))
			{
				if ($emailRegistration)
				{
					$url = $this->url()->fromRoute('user', array('action' => 'confirm-email', 'param' => $data->getToken()), array('force_canonical' => true));
					$this->sendEmail($data, 'Activate your account', "Please follow this link to activate your account:\n".$url);
				}
				
				$this->flashMessenger()->addMessage($msg);
				
				return $this->redirect()->toRoute('user', array('action' => 'login'));
			}
		}
		
		$view = new ViewModel(array('form' => $form));
		$view->setTemplate('partial/form.phtml');
		
		return $view;
	}
	
	function forgotPasswordAction()
	{
		$form = $this->getServiceLocator()->get('ForgotPasswordForm')->setAttribute('action', $this->url()->fromRoute('user', array('action' => 'forgot-password')));
		$request = $this->getRequest();
		
		if ($request->isPost() && $form->setData($request->getPost())->isValid())
		{
			$mapper = $this->getServiceLocator()->get('UserMapper');
			$user = $mapper->getByEmail($form->getData()->getEmail());
			
			if ($user)
			{
				$user->setToken(md5($user->getId() . rand(1, 99999)));
				
				if ($mapper->initPasswordReset($user))
				{
					$url = $this->url()->fromRoute('user', array('action' => 'reset-password', 'param' => $user->getToken()), array('force_canonical' => true));
					$this->sendEmail($user, 'Password reset', "Please follow this link to change your password:\n".$url);
				}
			}
			
			$this->flashMessenger()->addMessage('If you entered the correct email address, you will receive a verification email.');
			
			return $this->redirect()->toRoute('user', array('action' => 'login'));
		}
		
		$view = new ViewModel(array('form' => $form));
		$view->setTemplate('partial/form.phtml');
		
		return $view;
	}
	
	function resetPasswordAction()
	{
		if (!$token = $this->params()->fromRoute('param', null))
		{
			return $this->redirect()->toRoute('user');
		}
		
		$form = $this->getServiceLocator()->get('ResetPasswordForm')->setAttribute('action', $this->url()->fromRoute('user', array('action' => 'reset-password', 'param' => $token)));
		$request = $this->getRequest();
		
		if ($request->isPost() && $form->setData($request->getPost())->isValid())
		{
			$form->getData()->setToken($token);
			
			if ($this->getServiceLocator()->get('UserMapper')->resetPassword($form->getData()))
			{
				$this->flashMessenger()->addMessage('Password reset.');
				
				return $this->redirect()->toRoute('user', array('action' => 'login'));
			}
		}
		
		$view = new ViewModel(array('form' => $form));
		$view->setTemplate('partial/form.phtml');
		
		return $view;
	}
	
	function deleteAccountAction()
	{
		if (!$this->user || $this->user->getRoleId() != 3)
		{
			return $this->redirect()->toRoute('user', array('action' => 'login'));
		}
		
		$request = $this->getRequest();
		
		if ($request->isPost())
		{
			$auth = $this->auth = array(
				'email' => $this->user->getEmail(),
				'password' => $request->getPost('password'),
			);
			
			if ($auth && $this->getServiceLocator()->get('UserMapper')->deleteUser($this->user->getId()))
			{
				$this->flashMessenger()->addMessage('Account removed.');
			
				return $this->redirect()->toRoute('user', array('action' => 'logout'));
			}
		}
		
		$view = new ViewModel(array(
			'message' => 'delete_account_confirmation',
			'form' => $this->getServiceLocator()->get('RemoveAccountForm'),
		));
		$view->setTemplate('partial/form.phtml');
		
		return $view;
	}
	
	function confirmEmailAction()
	{
		if (!$token = $this->params()->fromRoute('param', null))
		{
			return $this->redirect()->toRoute('user');
		}
		
		$result = $this->getServiceLocator()->get('UserMapper')->confirmEmail($token);
		$this->flashMessenger()->addMessage(($result ? 'Account activated.' : 'Unspecified error.'));
		
		return $this->redirect()->toRoute('user', array('action' => 'login'));
	}
	
	function manageAction()
	{
		if (!$this->isAdmin)
		{
			return $this->redirect()->toRoute('user');
		}
		
		$mapper = $this->getServiceLocator()->get('UserMapper');
		
		return array(
			'users' => $mapper->getAll(),
			'unapproved' => $mapper->getUnapproved()->count(),
			'allowDemoteAdmin' => $mapper->getAdmins()->count() > 1 && $this->user->getId() == 1 ? true : false,
		);
	}
	
	function changeStatusAction()
	{
		if (!$this->isAdmin)
		{
			return $this->redirect()->toRoute('user');
		}
		
		$mapper = $this->getServiceLocator()->get('UserMapper');
		$user = $mapper->getById($this->params()->fromRoute('id'));
		$role = $this->params()->fromRoute('param');
		
		$result = $role == 'delete' ? $mapper->deleteUser($user->getId()) : $mapper->changeRole($user->getId(), $role);
		
		if ($result)
		{
			$this->sendEmail($user, 'Changes to your account', 'role_changed_from_' . $user->getRoleId() . '_to_' . $role);
			$this->flashMessenger()->addMessage('User updated.');
		}
		else
		{
			$this->flashMessenger()->addMessage('Failed to update.');
		}
		
		return $this->redirect()->toRoute('user', array('action' => 'manage'));
	}
	
	function approveAllAction()
	{
		if (!$this->isAdmin)
		{
			return $this->redirect()->toRoute('user');
		}
		
		$mapper = $this->getServiceLocator()->get('UserMapper');
		$users = $mapper->getUnapproved();
		
		if ($mapper->approveAll())
		{
			foreach ($users as $user)
			{
				$this->sendEmail($user, 'Changes to your account', 'role_changed_from_-1_to_3');
			}

			$this->flashMessenger()->addMessage('Users approved.');
		}
		
		return $this->redirect()->toRoute('user', array('action' => 'manage'));
	}
	
	/**
	 * @param array $data email, password
	 * @return boolean
	 */
	protected function auth(array $data)
	{
		$this->authService->getAdapter()->setIdentity($data['email'])->setCredential($data['password']);
		$result = $this->authService->authenticate();
		
		if ($result->isValid())
		{
			$data = $this->getServiceLocator()->get('UserMapper')->getByEmail($result->getIdentity());
			$this->authService->getStorage()->write($data);
			
			return true;
		}
		
		$this->authService->clearIdentity();
		$this->authService->getStorage()->clear();
		$this->flashMessenger()->addMessage($result->getMessages()[0]);
		
		return false;
	}
	
	/**
	 * @param RigUser\Model\UserEntity $user
	 * @param string $subject
	 * @param string $body
	 */
	protected function sendEmail($user, $subject, $body)
	{
		$translator = $this->getServiceLocator()->get('translator');
		
		$mail = new Mail\Message();
		$mail->setFrom('admin@admin.com', 'Admin');
		$mail->setTo($user->getEmail());
		$mail->setSubject($translator->translate($subject));
		$mail->setBody(sprintf($translator->translate('email_heading'), $user->getName()) . "\n\n" . $translator->translate($body));
		
		(new Mail\Transport\Sendmail())->send($mail);
	}
}