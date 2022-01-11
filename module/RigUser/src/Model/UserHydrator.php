<?php
namespace RigUser\Model;

use Zend\Stdlib\Hydrator\ClassMethods;

class UserHydrator extends ClassMethods
{
	function extract($object)
	{
		return parent::extract($object);
	}
	
	function hydrate(array $data, $object)
	{
		$object = parent::hydrate($data, $object);
		
		if (isset($data['role_name']))
		{
			$object->roleName = $data['role_name'];
		}
		
		return $object;
	}
}