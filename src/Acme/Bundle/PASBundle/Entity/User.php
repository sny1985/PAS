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
	* @Assert\Range(min="0")
	*/
	private $uid;

	/**
	* @ORM\Column(type="string", length=80, unique=true)
	* @Assert\NotNull(message="User name should not be null.")
	* @Assert\Length(max="80", maxMessage="The username cannot be longer than {{ limit }} characters length.")
	*/
	private $username;

	/**
	* @ORM\Column(type="string", length=40)
	* @Assert\NotNull(message="Fist name should not be null.")
	* @Assert\Length(max="40", maxMessage="The first name cannot be longer than {{ limit }} characters length.")
	*/
	private $firstname;

	/**
	* @ORM\Column(type="string", length=40)
	* @Assert\Length(max="40", maxMessage="The middle name cannot be longer than {{ limit }} characters length.")
	*/
	private $middlename;

	/**
	* @ORM\Column(type="string", length=40)
	* @Assert\NotNull(message="Last name should not be null.")
	* @Assert\Length(max="40", maxMessage="The last name cannot be longer than {{ limit }} characters length.")
	*/
	private $lastname;

	/**
	* @ORM\Column(type="string", length=10)
	* @Assert\Length(max="40", maxMessage="The title cannot be longer than {{ limit }} characters length.")
	*/
	private $title;

	/**
	* @ORM\Column(type="string", length=32)
	*/
	private $salt;

	/**
	* @ORM\Column(type="string", length=40)
	* @Assert\NotNull(message="Password should not be null.")
	* @Assert\Length(max="40", maxMessage="The name cannot be longer than {{ limit }} characters length.")
	*/
	private $password;

	/**
	* @ORM\Column(type="string", length=80, unique=true)
	* @Assert\NotNull(message="Email address should not be null.")
	* @Assert\Email(checkMX=true)
	* @Assert\Length(max = "80", maxMessage = "The name cannot be longer than {{ limit }} characters length.")
	*/
	private $email;

	/**
	* @ORM\Column(type="string", length=10)
	* @Assert\NotNull(message="Role should not be null.")
	* @Assert\Length(max="10", maxMessage="The name cannot be longer than {{ limit }} characters length.")
	*/
	private $role;

	/**
	* @ORM\Column(type="boolean")
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

	public function getFirstname() {
		return $this->firstname;
	}

	public function setFirstname($firstname) {
		$this->firstname = $firstname;
	}

	public function getMiddlename() {
		return $this->middlename;
	}

	public function setMiddlename($middlename) {
		$this->middlename = $middlename;
	}

	public function getLastname() {
		return $this->lastname;
	}

	public function setLastname($lastname) {
		$this->lastname = $lastname;
	}

	public function getTitle() {
		return $this->title;
	}

	public function setTitle($title) {
		$this->title = $title;
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
		return $this->isActive;
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