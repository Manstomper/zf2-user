<?php
namespace RigUser\Model;

class UserEntity
{
	protected $id, $role_id, $email, $password, $name, $web, $pending, $token, $login_attempts;

	function extract()
	{
		return (new UserHydrator())->extract($this);
	}

	public function getId()
	{
		return $this->id;
	}

	public function getRoleId()
	{
		return $this->role_id;
	}

	public function getEmail()
	{
		return $this->email;
	}

	public function getPassword()
	{
		return $this->password;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getWeb()
	{
		return $this->web;
	}

	public function getPending()
	{
		return $this->pending;
	}

	public function getToken()
	{
		return $this->token;
	}

	public function getLoginAttempts()
	{
		return $this->login_attempts;
	}

	public function setId($id)
	{
		$this->id = $id;
	}

	public function setRoleId($role_id)
	{
		$this->role_id = $role_id;
	}

	public function setEmail($email)
	{
		$this->email = $email;
	}

	public function setPassword($password)
	{
		$this->password = $password;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function setWeb($web)
	{
		$this->web = $web;
	}

	public function setPending($pending)
	{
		$this->pending = $pending;
	}

	public function setToken($token)
	{
		$this->token = $token;
	}

	public function setLoginAttempts($login_attempts)
	{
		$this->login_attempts = $login_attempts;
	}
}