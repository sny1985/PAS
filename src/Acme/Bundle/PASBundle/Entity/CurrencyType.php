<?php

namespace Acme\Bundle\PASBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
* @ORM\Entity
* @ORM\Table(name="CurrencyType")
*/
class CurrencyType
{
	/**
	* @ORM\Id
	* @ORM\Column(type="integer")
	* @ORM\GeneratedValue(strategy="AUTO")
	* @Assert\NotNull(message="Currency Type id should not be null.")
	* @Assert\Range(min="0")
	*/
	protected $ctid;

	/**
	* @ORM\Column(type="string", length=40, unique=true)
	* @Assert\NotNull(message="Currency Type name should not be null.")
	* @Assert\Length(max="40", maxMessage = "The name cannot be longer than {{ limit }} characters length.")
	*/
	protected $name;

	/**
	* @ORM\Column(type="string", length=5, unique=true)
	* @Assert\NotNull()
	* @Assert\Length(max="5", maxMessage="The name cannot be longer than {{ limit }} characters length.")
	*/
	protected $code;

	public function getCtid() {
		return $this->ctid;
	}

	public function setCtid($ctid) {
		$this->ctid = $ctid;
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function getCode() {
		return $this->code;
	}

	public function setCode($code) {
		$this->code = $code;
	}
}

?>