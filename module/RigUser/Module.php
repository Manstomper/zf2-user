<?php
namespace RigUser;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Stdlib\Hydrator\ClassMethods;
use Zend\Authentication;
use RigUser\Model;
use RigUser\Form;

class Module
{
	public function onBootstrap(\Zend\Mvc\MvcEvent $e)
	{
		$moduleRouteListener = new \Zend\Mvc\ModuleRouteListener();
		$moduleRouteListener->attach($e->getApplication()->getEventManager());
	}
	
	public function getAutoloaderConfig()
	{
		return array(
			'Zend\Loader\StandardAutoloader' => array(
				'namespaces' => array(
					__NAMESPACE__ => __DIR__ . '/src/',
				),
			),
		);
	}
	
	public function getConfig()
	{
		return array(
			'router' => array(
				'routes' => array(
					'user' => array(
						'type' => 'Segment',
						'options' => array(
							'route' => '/user[/:action[/:param[/:id]]]',
							'defaults' => array(
								'__NAMESPACE__' => 'RigUser\Controller',
								'controller' => 'User',
								'action' => 'login',
							),
							'constraints' => array(
								'action' => '[a-z-]+',
								'param' => '[a-zA-Z0-9-]+',
								'id' => '[a-zA-Z0-9-_]+',
							),
						),
					),
				),
			),
			'controllers' => array(
				'invokables' => array(
					'RigUser\Controller\User' => 'RigUser\Controller\UserController'
				),
			),
			'translator' => array(
				'locale' => 'en_US',
				'translation_file_patterns' => array(
					array(
						'type' => 'PHP array',
						'base_dir' => __DIR__ . '/language',
						'pattern' => '%s.php',
					),
				),
			),
			'view_manager' => array(
				'template_path_stack' => array(
					__DIR__ . '/view',
				),
			),
		);
	}
	
	public function getServiceConfig()
	{
		return array(
			'factories' => array(
				'EncryptionKey' => function()
				{
					return '5foS4NgLMBvO3-hVuKNRnV7AqSvIfaiq';
				},
				'EnableEmailRegistration' => function()
				{
					return true;
				},
				'Auth' => function($sm)
				{
					$adapter = new Authentication\Adapter\DbTable($sm->get('dbAdapter'), 'user', 'email', 'password', 'SHA2(CONCAT(?, \'' . $sm->get('EncryptionKey') . '\'), 256) AND role_id > 0 AND pending = 0');
					$authService = new Authentication\AuthenticationService(new Authentication\Storage\Session(), $adapter);
					
					return $authService;
				},
				'RoleMapper' => function($sm)
				{
					$resultSet = new HydratingResultSet(new ClassMethods(), new Model\RoleEntity());
					return new TableGateway('role', $sm->get('dbAdapter'), null, $resultSet);
				},
				'UserMapper' => function($sm)
				{
					$resultSet = new HydratingResultSet(new Model\UserHydrator(), new Model\UserEntity());
					$tableGateway = new TableGateway('user', $sm->get('dbAdapter'), null, $resultSet);
					
					return new Model\UserMapper($tableGateway);
				},
				'LoginForm' => function()
				{
					$form = new Form\UserForm();
					$form->email();
					$form->password();
					$form->add((new \Zend\Form\Element\Submit('send'))->setValue('Submit'));
					
					$inputFilter = new Form\UserFilter();
					$inputFilter->authEmail();
					$inputFilter->authPassword();
					$form->setInputFilter($inputFilter);
					
					return $form;
				},
				'RegisterForm' => function($sm)
				{
					$form = new Form\UserForm();
					$form->email();
					$form->other();
					$form->password();
					$form->verifyPassword();
					$form->add((new \Zend\Form\Element\Submit('send'))->setValue('Submit'));
					$form->setHydrator(new Model\UserHydrator());
					$form->bind(new Model\UserEntity());
					
					$inputFilter = new Form\UserFilter($sm->get('dbAdapter'));
					$inputFilter->newEmail();
					$inputFilter->other();
					$inputFilter->newPassword();
					$form->setInputFilter($inputFilter);
					
					return $form;
				},
				'EditForm' => function($sm)
				{
					$userEntity = $sm->get('UserMapper')->getByEmail($sm->get('Auth')->getStorage()->read()->getEmail());
					$form = new Form\UserForm();
					$form->other();
					$form->password();
					$form->add((new \Zend\Form\Element\Submit('send'))->setValue('Submit'));
					$form->setHydrator(new Model\UserHydrator());
					$form->bind($userEntity);
					
					$inputFilter = new Form\UserFilter($sm->get('dbAdapter'), $userEntity->getId());
					$inputFilter->other();
					$inputFilter->authPassword();
					$form->setInputFilter($inputFilter);
					
					return $form;
				},
				'ChangeEmailForm' => function($sm)
				{
					$form = new Form\UserForm();
					$form->email();
					$form->get('email')->setLabel('New email');
					$form->password();
					$form->add((new \Zend\Form\Element\Submit('send'))->setValue('Submit'));
					$form->setHydrator(new Model\UserHydrator());
					$form->bind(new Model\UserEntity());
					
					$inputFilter = new Form\UserFilter($sm->get('dbAdapter'));
					$inputFilter->newEmail();
					$inputFilter->authPassword();
					$form->setInputFilter($inputFilter);
					
					return $form;
				},
				'ChangePasswordForm' => function()
				{
					$form = new Form\UserForm();
					$form->password();
					$form->newPassword();
					$form->verifyPassword();
					$form->add((new \Zend\Form\Element\Submit('send'))->setValue('Submit'));
					
					$inputFilter = new Form\UserFilter();
					$inputFilter->authPassword();
					$inputFilter->newPassword(true);
					$form->setInputFilter($inputFilter);
					
					return $form;
				},
				'ForgotPasswordForm' => function($sm)
				{
					$form = new Form\UserForm();
					$form->email();
					$form->add((new \Zend\Form\Element\Submit('send'))->setValue('Submit'));
					$form->setHydrator(new Model\UserHydrator());
					$form->bind(new Model\UserEntity());
					
					$inputFilter = new Form\UserFilter($sm->get('dbAdapter'));
					$inputFilter->authEmail();
					$form->setInputFilter($inputFilter);
					
					return $form;
				},
				'ResetPasswordForm' => function()
				{
					$form = new Form\UserForm();
					$form->email();
					$form->password();
					$form->verifyPassword();
					$form->add((new \Zend\Form\Element\Submit('send'))->setValue('Submit'));
					$form->setHydrator(new Model\UserHydrator());
					$form->bind(new Model\UserEntity());
					
					$inputFilter = new Form\UserFilter();
					$inputFilter->newPassword();
					$form->setInputFilter($inputFilter);
					
					return $form;
				},
				'RemoveAccountForm' => function()
				{
					$form = new Form\UserForm();
					$form->password();
					$form->add((new \Zend\Form\Element\Submit('send'))->setValue('Submit'));
					
					$inputFilter = new Form\UserFilter();
					$inputFilter->authPassword();
					$form->setInputFilter($inputFilter);
					
					return $form;
				},
			),
		);
	}
}