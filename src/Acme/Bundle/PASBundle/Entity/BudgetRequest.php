<?php

namespace Acme\Bundle\PASBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
* @ORM\Entity
* @ORM\Table(name="BudgetRequest")
*/
class BudgetRequest
{
	/**
	* @ORM\Id
	* @ORM\Column(type="integer")
	* @ORM\GeneratedValue(strategy="AUTO")
	* @Assert\Range(min="0")
	*/
	protected $bid;

	/**
	* @ORM\Column(name="uid", type="smallint")
	* @Assert\NotNull(message="Budget Requester should not be null.")
	* @Assert\Range(min="0")
	*/
	protected $holder;

	/**
	* @ORM\Column(type="smallint")
	* @Assert\NotNull(message="Request Type should not be null.")
	* @Assert\Range(min="0", max="1")
	*/
	protected $requestType;

	/**
	* @ORM\Column(name="bcid", type="smallint")
	* @Assert\NotNull(message="Budget Category should not be null.")
	* @Assert\Range(min="0")
	*/
	protected $category;

	/**
	* @ORM\Column(type="date")
	*/
	protected $startdate;

	/**
	* @Assert\NotNull(message="Starting month should not be null.")
	*/
	protected $startmonth;

	/**
	* @Assert\NotNull(message="Starting year should not be null.")
	*/
	protected $startyear;

	/**
	* @ORM\Column(type="date")
	*/
	protected $enddate;

	/**
	* @Assert\NotNull(message="Ending month should not be null.")
	*/
	protected $endmonth;

	/**
	* @Assert\NotNull(message="Ending year should not be null.")
	*/
	protected $endyear;

	/**
	* @ORM\Column(type="text", nullable=true)
	*/
	protected $abstract;

	/**
	* @ORM\Column(type="text", nullable=true)
	*/
	protected $details;

	/**
	* @ORM\Column(type="decimal", scale=2)
	* @Assert\NotNull(message="Amount should not be null.")
	* @Assert\Range(min="0")
	*/
	protected $amount;

	/**
	* @ORM\Column(name="ctid", type="smallint")
	* @Assert\NotNull(message="Currency Type should not be null.")
	* @Assert\Range(min="0")
	*/
	protected $curtype;

	/**
	* @ORM\Column(type="date")
	* @Assert\NotNull(message="Submission date should not be null.")
	* @Assert\Date()
	*/
	protected $date;

	/**
	* @ORM\Column(type="smallint")
	* @Assert\NotNull(message="Approval status should not be null.")
	*/
	protected $approved;

	public function getBid() {
		return $this->bid;
	}

	public function setBid($bid) {
		$this->bid = $bid;
	}

	public function getHolder() {
		return $this->holder;
	}

	public function setHolder($holder) {
		$this->holder = $holder;
	}

	public function getRequestType() {
		return $this->requestType;
	}

	public function setRequestType($requestType) {
		$this->requestType = $requestType;
	}

	public function getCategory() {
		return $this->category;
	}

	public function setCategory($category) {
		$this->category = $category;
	}

	public function getStartdate() {
		return $this->startdate;
	}

	public function setStartdate($date) {
		$this->startdate = $date;
	}

	public function getStartmonth() {
		return $this->startmonth;
	}

	public function setStartmonth($month) {
		$this->startmonth = $month;
	}

	public function getStartyear() {
		return $this->startyear;
	}

	public function setStartyear($year) {
		$this->startyear = $year;
	}

	public function getEnddate() {
		return $this->enddate;
	}

	public function setEnddate($date) {
		$this->enddate = $date;
	}

	public function getEndmonth() {
		return $this->endmonth;
	}

	public function setEndmonth($month) {
		$this->endmonth = $month;
	}

	public function getEndyear() {
		return $this->endyear;
	}

	public function setEndyear($year) {
		$this->endyear = $year;
	}

	public function getAbstract() {
		return $this->abstract;
	}

	public function setAbstract($abstract) {
		$this->abstract = $abstract;
	}

	public function getDetails() {
		return $this->details;
	}

	public function setDetails($details) {
		$this->details = $details;
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

	public function getApproved() {
		return $this->approved;
	}

	public function setApproved($approved) {
		$this->approved = $approved;
	}

	public function getActivityDuration() {
		$start = explode(" ", $this->startdate->format("n Y"));
		$this->startmonth = $start[0];
		$this->startyear = $start[1];

		$end = explode(" ", $this->enddate->format("n Y"));
		$this->endmonth = $end[0];
		$this->endyear = $end[1];
	}

	public function setActivityDuration() {
		// use current month/year if not specified
		if (null == $this->startmonth || null == $this->startyear || null == $this->endmonth || null == $this->endyear) {
			$this->startmonth = $this->endmonth = date('m');
			$this->startyear = $this->endyear = date('Y');
		}

		// use starting date if the ending date is not legal
		if ($this->startyear > $this->endyear || ($this->startyear == $this->endyear && $this->startmonth > $this->endmonth)) {
			$this->endmonth = $this->startmonth;
			$this->endyear = $this->startyear;
		}

		$this->startdate = new \DateTime(date("d-m-Y", strtotime("1-$this->startmonth-$this->startyear")));
		$this->enddate = new \DateTime(date("t-m-Y", strtotime("1-$this->endmonth-$this->endyear")));
	}
}

?>