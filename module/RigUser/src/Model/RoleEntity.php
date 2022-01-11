<?php
namespace RigUser\Model;

use Zend\Stdlib\Hydrator\ClassMethods;

class RoleEntity
{
	protected $id, $name;
	
	function extract()
	{
		return (new ClassMethods())->extract($this);
	}
	
	function setId($id)
	{
		$this->id = $id;
	}
	
	function setName($name)
	{
		$this->name = $name;
	}
	
	function getId()
	{
		return $this->id;
	}
	
	function getName()
	{
		return $this->name;
	}
}