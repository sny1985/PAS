<?php

namespace Acme\Bundle\PASBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

/**
* @ORM\Entity
* @ORM\Table(name="User")
*/
class User implements AdvancedUserInterface, \Serializable
{
	/**
	* @ORM\Id
	* @ORM\Column(type="integer")
	* @ORM\GeneratedValue(strategy="AUTO")
	* @Assert\NotNull(message="User id should not be null.")
	* @Assert\Range(min="0")
	*/
	private $uid;

	/**
	* @ORM\Column(type="string", length=80, unique=true)
	* @Assert\NotNull(message="User name should not be null.")
	* @Assert\Length(max="80", maxMessage="The name cannot be longer than {{ limit }} characters length.")
	*/
	private $username;

	/**
	* @ORM\Column(type="string", length=32)
	* @Assert\Length(max="32", maxMessage="The name cannot be longer than {{ limit }} characters length.")
	*/
	private $salt;

	/**
	* @ORM\Column(type="string", length=40)
	* @Assert\NotNull(message="User password should not be null.")
	* @Assert\Length(max="40", maxMessage="The name cannot be longer than {{ limit }} characters length.")
	*/
	private $password;

	/**
	* @ORM\Column(type="string", length=80, unique=true)
	* @Assert\NotNull(message="User email address should not be null.")
	* @Assert\Email(checkMX=true)
	* @Assert\Length(max = "80", maxMessage = "The name cannot be longer than {{ limit }} characters length.")
	*/
	private $email;

	/**
	* @ORM\Column(type="string", length=10)
	* @Assert\NotNull(message="User role should not be null.")
	* @Assert\Length(max="10", maxMessage="The name cannot be longer than {{ limit }} characters length.")
	*/
	private $role;

	/**
	* @ORM\Column(type="boolean")
	* @Assert\NotNull(message="User active status should not be null.")
	* @Assert\Type(type="bool")
	*/
	private $isActive;

	public function __construct() {
		$this->isActive = true;
		$this->salt = md5(uniqid(null, true));
	}

	public function getUid() {
		return $this->uid;
	}
	
	public function setUid($uid) {
		$this->uid = $uid;
	}

	/**
	* @inheritDoc
	*/
	public function getUsername() {
		return $this->username;
	}
	
	public function setUsername($username) {
		$this->username = $username;
	}

	/**
	* @inheritDoc
	*/
	public function getSalt() {
		return $this->salt;
	}

	public function setSalt($salt) {
		$this->salt = $salt;
	}

	/**
	* @inheritDoc
	*/
	public function getPassword() {
		return $this->password;
	}

	public function setPassword($password) {
		$this->password = $password;
	}

	public function getEmail() {
		return $this->email;
	}

	public function setEmail($email) {
		$this->email = $email;
	}

	public function getRole() {
		return $this->role;
	}

	public function setRole($role) {
		$this->role = $role;
	}

	/**
	* @inheritDoc
	*/
	public function getRoles() {
		if ($this->role == "admin") {
			return array("ROLE_ADMIN");
		} else if ($this->role == "requester") {
			return array("ROLE_REQUESTER");
		} else if ($this->role == "cfo") {
			return array("ROLE_CFO");
		} else {
			return array("ROLE_APPROVER");
		}
	}

	public function getIsActive() {
		return $this->roles;
	}

	public function setIsActive($isActive) {
		$this->isActive = $isActive;
	}

	/**
	* @inheritDoc
	*/
	public function eraseCredentials()
	{
	}

	/**
	* @see \Serializable::serialize()
	*/
	public function serialize() {
	return serialize(array($this->uid,));
	}

	/**
	* @see \Serializable::unserialize()
	*/
	public function unserialize($serialized) {
		list ($this->uid,) = unserialize($serialized);
	}
	
	public function isAccountNonExpired() {
		return true;
	}

	public function isAccountNonLocked() {
		return true;
	}

	public function isCredentialsNonExpired() {
		return true;
	}

	public function isEnabled() {
		return $this->isActive;
	}
}

?>