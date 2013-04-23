<?php

namespace Acme\Bundle\PASBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
* @ORM\Entity
* @ORM\Table(name="BudgetCategory")
*/
class BudgetCategory
{
	/**
	* @ORM\Id
	* @ORM\Column(type="integer")
	* @ORM\GeneratedValue(strategy="AUTO")
	* @Assert\NotNull(message="Budget Category id should not be null.")
	* @Assert\Range(min="0")
	*/
	protected $bcid;

	/**
	* @ORM\Column(type="string", length=20, unique=true)
	* @Assert\NotNull(message="Budget Category name should not be null.")
	* @Assert\Length(max="20", maxMessage="The name cannot be longer than {{ limit }} characters length.")
	*/
	protected $name;

	public function getBcid() {
		return $this->bcid;
	}

	public function setBcid($bcid) {
		$this->bcid = $bcid;
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}
}

?>