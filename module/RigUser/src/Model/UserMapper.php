<?php
namespace RigUser\Model;

use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\TableGateway\TableGateway;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Db\Sql\Expression;

class UserMapper extends AbstractTableGateway implements UserMapperInterface, ServiceLocatorAwareInterface
{
	/**
	 * @var Zend\ServiceManager\ServiceManager
	 */
	protected $sm;
	
	final function __construct(TableGateway $table)
	{
		$this->table = $table;
	}
	
	/**
	 * @return Zend\Db\ResultSet\HydratingResultSet
	 * 
	 * @TODO work on all sql queries
	 */
	function getAll()
	{
		return $this->table->select(function($select)
		{
			$select->columns(array('id', 'email', 'name', 'web', 'role_id', 'pending'));
			$select->join('role', 'user.role_id = role.id', array('role_name' => 'name'), 'left');
			$select->order(new Expression('CASE WHEN pending=1 THEN -1 WHEN role_id=1 THEN 3 WHEN role_id=3 THEN 1 ELSE role_id END DESC'));
		})
		->buffer();
	}
	
	/**
	 * @param string $email
	 * @return RigUser\Model\UserEntity|false
	 */
	function getByEmail($email)
	{
		return $this->table->select(function($select) use($email)
		{
			$select->columns(array('id', 'role_id', 'email', 'name', 'web', 'pending'));
			$select->join('role', 'user.role_id = role.id', array('role_name' => 'name'), 'left');
			$select->where(array('email' => $email));
		})
		->current();
	}
	
	/**
	 * @param integer|string $id
	 * @return RigUser\Model\UserEntity|false
	 */
	function getById($id)
	{
		return $this->table->select(function($select) use($id)
		{
			$select->columns(array('id', 'email', 'name', 'web', 'role_id', 'pending'));
			$select->join('role', 'user.role_id = role.id', array('role_name' => 'name'), 'left');
			$select->where(array('user.id' => $id));
		})
		->current();
	}
	
	/**
	 * @return Zend\Db\ResultSet\HydratingResultSet
	 */
	function getAdmins()
	{
		return $this->table->select('role_id = 1');
	}
	
	/**
	 * @return Zend\Db\ResultSet\HydratingResultSet
	 */
	function getModerators()
	{
		return $this->table->select('role_id = 2');
	}
	
	/**
	 * @return Zend\Db\ResultSet\HydratingResultSet
	 */
	function getUnapproved()
	{
		return $this->table->select('role_id = -1');
	}
	
	/**
	 * @param RigUser\Model\UserEntity $entity
	 * @return integer Affected rows
	 */
	function insertUser(UserEntity $entity)
	{
		$entity->setPassword(hash('sha256', $entity->getPassword() . $this->getServiceLocator()->get('EncryptionKey')));
		
		return $this->table->insert($entity->extract());
	}
	
	/**
	 * @param RigUser\Model\UserEntity $entity
	 * @return integer Affected rows
	 */
	function updateUser(UserEntity $entity)
	{
		$where = array('id' => $entity->getId());
		
		$entity = $entity->extract();
		unset($entity['id']);
		unset($entity['email']);
		unset($entity['password']);
		unset($entity['role_id']);
		unset($entity['pending']);
		
		return $this->table->update($entity, $where);
	}
	
	/**
	 * @param integer|string $id
	 * @param string $password New password
	 * @return integer Affected rows
	 */
	function changePassword($id, $password)
	{
		return $this->table->update(array(
			'password' => hash('sha256', $password . $this->getServiceLocator()->get('EncryptionKey')),
		), array(
			'id' => $id,
		));
	}
	
	/**
	 * @param RigUser\Model\UserEntity $entity
	 * @return integer Affected rows
	 */
	function changeEmail(UserEntity $entity)
	{
		return $this->table->update(array(
			'email' => $entity->getEmail(),
			'pending' => 1,
			'token' => $entity->getToken(),
		), array(
			'id' => $entity->getId(),
		));
	}
	
	/**
	 * @param integer|string $id
	 * @return integer Affected rows
	 */
	function deleteUser($id)
	{
		//@TODO rethink this
		try
		{
			$result = $this->table->delete(array('id' => $id));
		}
		catch (\Exception $e)
		{
			$user = new UserEntity();
			$user->setEmail('');
			$user->setPassword('');
			$user->setName('user' . $id);
			$user->setRoleId(-3);
			
			$user = $user->extract();
			unset($user['id']);
			
			$result = $this->table->update($user, array('id' => $id));
		}
		
		return $result;
	}
	
	/**
	 * @param RigUser\Model\UserEntity $user
	 * @return integer Affected rows
	 */
	function initPasswordReset(UserEntity $user)
	{
		return $this->table->update(array('token' => $user->getToken()), array('email' => $user->getEmail()));
	}
	
	/**
	 * @param RigUser\Model\UserEntity $user
	 * @return integer Affected rows
	 */
	function resetPassword(UserEntity $user)
	{
		$set = array(
			'password' => hash('sha256', $user->getPassword() . $this->getServiceLocator()->get('EncryptionKey')),
		);
		
		$where = array(
			'email' => $user->getEmail(),
			'token' => $user->getToken(),
		);
		
		$result = $this->table->update($set, $where);
		
		if ($result)
		{
			$this->table->update(array('token' => null), $where);
		}
		
		return $result;
	}
	
	/**
	 * @param string $token
	 * @return integer Affected rows
	 */
	function confirmEmail($token)
	{
		return $this->table->update(array(
			'pending' => 0,
			'token' => null,
		), array(
			'pending' => 1,
			'token' => $token,
		));
	}
	
	/**
	 * @param integer|string $id
	 * @param integer|string $roleId
	 * @return integer Affected rows
	 */
	function changeRole($id, $roleId)
	{
		return $this->table->update(array('role_id' => $roleId), array('id' => $id));
	}
	
	/**
	 * @return integer Affected rows
	 */
	function approveAll()
	{
		return $this->table->update(array('role_id' => 3), array('role_id' => -1));
	}
	
	function setServiceLocator(ServiceLocatorInterface $sm)
	{
		$this->sm = $sm;
	}
	
	function getServiceLocator()
	{
		return $this->sm;
	}
}