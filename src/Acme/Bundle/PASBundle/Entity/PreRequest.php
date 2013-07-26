<?php

namespace Acme\Bundle\PASBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
* @ORM\Entity
* @ORM\Table(name="PreRequest")
*/
class PreRequest
{
	/**
	* @ORM\Id
	* @ORM\Column(type="integer")
	* @ORM\GeneratedValue(strategy="AUTO")
	* @Assert\Range(min="0")
	*/
	protected $prid;

	/**
	* @ORM\Column(name="uid", type="string", length=80)
	* @Assert\NotNull(message="Requester is needed.")
	* @Assert\Range(min="0")
	*/
	protected $requester;

	/**
	* @ORM\Column(name="bcid", type="smallint")
	* @Assert\NotNull(message="Budget Category is needed.")
	* @Assert\Range(min="0")
	*/
	protected $category;

	/**
	* @ORM\Column(type="text", nullable=true)
	*/
	protected $explanation;

	/**
	* @ORM\Column(type="decimal", scale=2)
	* @Assert\NotNull(message="Amount is needed.")
	* @Assert\Type(type="numeric", message="The value {{ value }} is not a valid {{ type }}.")
	* @Assert\Range(min="0")
	*/
	protected $amount;

	/**
	* @ORM\Column(name="ctid", type="smallint")
	* @Assert\NotNull(message="Currency Type is needed.")
	* @Assert\Range(min="0")
	*/
	protected $curtype;

	/**
	* @ORM\Column(type="string", length=64)
	* @Assert\NotNull(message="Budget is needed.")
	*/
	protected $selectedBudget;

	/**
	* @ORM\Column(type="smallint")
	* @Assert\NotNull(message="Request Level is needed.")
	* @Assert\Range(min="0")
	*/
	protected $level;

	/**
	* @ORM\Column(type="smallint")
	* @Assert\NotNull(message="Chair is needed.")
	* @Assert\Range(min="0")
	*/
	protected $chairId;

	/**
	* @ORM\Column(type="smallint")
	* @Assert\NotNull(message="Chair's Approval is needed.")
	* @Assert\Range(min="0")
	*/
	protected $chairApproved;

	/**
	* @ORM\Column(type="text", nullable=true)
	*/
	protected $chairComment;

	/**
	* @ORM\Column(type="smallint")
	* @Assert\NotNull(message="CFO is needed.")
	* @Assert\Range(min="0")
	*/
	protected $cfoId;

	/**
	* @ORM\Column(type="smallint")
	* @Assert\NotNull(message="CFO's Approval is needed.")
	* @Assert\Range(min="0")
	*/
	protected $cfoApproved;

	/**
	* @ORM\Column(type="text", nullable=true)
	*/
	protected $cfoComment;

	/**
	* @ORM\Column(type="smallint")
	* @Assert\NotNull(message="President is needed.")
	* @Assert\Range(min="0")
	*/
	protected $presidentId;

	/**
	* @ORM\Column(type="smallint")
	* @Assert\NotNull(message="President's Approval is needed.")
	* @Assert\Range(min="0")
	*/
	protected $presidentApproved;

	/**
	* @ORM\Column(type="text", nullable=true)
	*/
	protected $presidentComment;

	/**
	* @ORM\Column(type="smallint")
	* @Assert\NotNull(message="Secretary is needed.")
	* @Assert\Range(min="0")
	*/
	protected $secretaryId;

	/**
	* @ORM\Column(type="smallint")
	* @Assert\NotNull(message="Secretary's Approval is needed.")
	* @Assert\Range(min="0")
	*/
	protected $secretaryApproved;

	/**
	* @ORM\Column(type="text", nullable=true)
	*/
	protected $secretaryComment;

	/**
	* @ORM\Column(type="date")
	* @Assert\NotNull(message="Submission date is needed.")
	* @Assert\Date()
	*/
	protected $date;

	public function getPrid() {
		return $this->prid;
	}

	public function setPrid($prid) {
		$this->prid = $prid;
	}

	public function getRequester() {
		return $this->requester;
	}

	public function setRequester($requester) {
		$this->requester = $requester;
	}

	public function getCategory() {
		return $this->category;
	}

	public function setCategory($category) {
		$this->category = $category;
	}

	public function getExplanation() {
		return $this->explanation;
	}

	public function setExplanation($explanation) {
		$this->explanation = $explanation;
	}

	public function getAmount() {
		return $this->amount;
	}

	public function setAmount($amount) {
		$this->amount = $amount;
	}

	public function getCurtype() {
		return $this->curtype;
	}

	public function setCurtype($curtype) {
		$this->curtype = $curtype;
	}

	public function getSelectedBudget() {
		return $this->selectedBudget;
	}

	public function setSelectedBudget($selectedBudget) {
		$this->selectedBudget = $selectedBudget;
	}

	public function getLevel() {
		return $this->level;
	}

	public function setLevel($level) {
		$this->level = $level;
	}

	public function getChairId() {
		return $this->chairId;
	}

	public function setChairId($id) {
		$this->chairId = $id;
	}

	public function getChairApproved() {
		return $this->chairApproved;
	}

	public function setChairApproved($approved) {
		$this->chairApproved = $approved;
	}

	public function getChairComment() {
		return $this->chairComment;
	}

	public function setChairComment($comment) {
		$this->chairComment = $comment;
	}

	public function getCfoId() {
		return $this->cfoId;
	}

	public function setCfoId($id) {
		$this->cfoId = $id;
	}

	public function getCfoApproved() {
		return $this->cfoApproved;
	}

	public function setCfoApproved($approved) {
		$this->cfoApproved = $approved;
	}

	public function getCfoComment() {
		return $this->cfoComment;
	}

	public function setCfoComment($comment) {
		$this->cfoComment = $comment;
	}

	public function getPresidentId() {
		return $this->presidentId;
	}

	public function setPresidentId($id) {
		$this->presidentId = $id;
	}

	public function getPresidentApproved() {
		return $this->presidentApproved;
	}

	public function setPresidentApproved($approved) {
		$this->presidentApproved = $approved;
	}

	public function getPresidentComment() {
		return $this->presidentComment;
	}

	public function setPresidentComment($comment) {
		$this->presidentComment = $comment;
	}

	public function getSecretaryId() {
		return $this->secretaryId;
	}

	public function setSecretaryId($id) {
		$this->secretaryId = $id;
	}

	public function getSecretaryApproved() {
		return $this->secretaryApproved;
	}

	public function setSecretaryApproved($approved) {
		$this->secretaryApproved = $approved;
	}

	public function getSecretaryComment() {
		return $this->secretaryComment;
	}

	public function setSecretaryComment($comment) {
		$this->secretaryComment = $comment;
	}

	public function getDate() {
		return $this->date;
	}

	public function setDate($date) {
		if (gettype($date) == "string") {
			$this->date = new \DateTime($date);
		} else {
			$this->date = $date;
		}
	}
}

?>