<?php
namespace RigUser\Model;

interface UserMapperInterface
{
	function getAll();
	function getByEmail($email);
	function getById($id);
	function getAdmins();
	function getModerators();
	function getUnapproved();
	
	function insertUser(UserEntity $entity);
	function updateUser(UserEntity $entity);
	function deleteUser($id);
	function changePassword($id, $password);
	function changeEmail(UserEntity $entity);
	function initPasswordReset(UserEntity $user);
	function resetPassword(UserEntity $user);
	function confirmEmail($token);
	
	function changeRole($id, $roleId);
	function approveAll();
}