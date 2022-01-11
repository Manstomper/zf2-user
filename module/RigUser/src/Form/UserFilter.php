<?php
namespace RigUser\Form;

use Zend\InputFilter\InputFilter;

class UserFilter extends InputFilter
{
	protected $dbAdapter, $id;
	
	/**
	 * @param Zend\Db\Adapter\AdapterServiceFactory|null $dbAdapter
	 * @param integer|string|null $id Authenticated user
	 */
	function __construct($dbAdapter = null, $id = null)
	{
		$this->dbAdapter = $dbAdapter;
		$this->id = $id;
	}
	
	function authEmail()
	{
		$this->add(array(
			'name' => 'email',
			'required' => true,
		));
	}
	
	function emailExists()
	{
		$this->add(array(
			'name' => 'email',
			'required' => true,
			'validators' => array(
				array(
					'name' => 'Db\RecordExists',
					'options' => array(
						'table' => 'user',
						'field' => 'email',
						'adapter' => $this->dbAdapter,
					),
				),
			),
		));
	}
	
	function newEmail()
	{
		$this->add(array(
			'name' => 'email',
			'required' => true,
			'validators' => array(
				array(
					'name' => 'Db\NoRecordExists',
					'options' => array(
						'table' => 'user',
						'field' => 'email',
						'adapter' => $this->dbAdapter,
					),
				),
			),
		));
	}
	
	function authPassword()
	{
		$this->add(array(
			'name' => 'password',
			'required' => true,
		));
	}
	
	/**
	 * @param boolean $prefix Default false; set to true if changing password
	 */
	function newPassword($prefix = false)
	{
		$this->add(array(
			'name' => ($prefix ? 'new_' : null) . 'password',
			'required' => true,
			'validators' => array(
				array(
					'name' => 'StringLength',
					'options' => array(
						'min' => 6,
						'max' => 100,
					),
				),
			),
		));
		
		$this->add(array(
			'name' => 'verify_password',
			'required' => true,
			'validators' => array(
				array(
					'name' => 'Identical',
					'options' => array(
						'token' => ($prefix ? 'new_' : null) . 'password',
						'messages' => array(
							\Zend\Validator\Identical::NOT_SAME => 'Passwords do not match.',
						),
					),
				),
			),
		));
	}
	
	function other()
	{
		$this->add(array(
			'name' => 'name',
			'required' => true,
			'filters' => array(
				array('name' => 'StripTags'),
				array('name' => 'Null'),
			),
			'validators' => array(
				array(
					'name' => 'NotEmpty',
				),
				array(
					'name' => 'StringLength',
					'options' => array(
						'max' => 40,
					),
				),
			),
		));
		
		//@todo validate
		$this->add(array(
			'name' => 'web',
			'required' => false,
			'filters' => array(
				array('name' => 'Null'),
			),
		));
	}
}