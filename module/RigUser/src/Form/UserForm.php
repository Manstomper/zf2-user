<?php
namespace RigUser\Form;

use Zend\Form\Form;

class UserForm extends Form
{
	function __construct()
	{
		parent::__construct('userForm');
	
		$this->setAttribute('method', 'post');
	}
	
	function email()
	{
		$this->add(array(
			'name' => 'email',
			'type' => 'Email',
			'options' => array(
				'label' => 'Email',
			),
			'attributes' => array(
				'required' => 'required'
			),
		));
	}
	
	function password()
	{
		$this->add(array(
			'name' => 'password',
			'type' => 'Password',
			'options' => array(
				'label' => 'Password',
			),
			'attributes' => array(
				'required' => 'required'
			),
		));
	}
	
	function verifyPassword()
	{
		$this->add(array(
			'name' => 'verify_password',
			'type' => 'Password',
			'options' => array(
				'label' => 'Repeat password',
			),
			'attributes' => array(
				'required' => 'required'
			),
		));
	}
	
	function newPassword()
	{
		$this->add(array(
			'name' => 'new_password',
			'type' => 'Password',
			'options' => array(
				'label' => 'New password',
			),
			'attributes' => array(
				'required' => 'required'
			),
		));
	}
	
	function other()
	{
		$this->add(array(
			'name' => 'name',
			'type' => 'Text',
			'options' => array(
				'label' => 'Name',
			),
			'attributes' => array(
				'required' => 'required',
			),
		));
		
		$this->add(array(
			'name' => 'web',
			'type' => 'Url',
			'options' => array(
				'label' => 'Website',
			),
		));
		
	}
}