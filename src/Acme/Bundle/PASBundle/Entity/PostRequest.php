<?php

namespace Acme\Bundle\PASBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
* @ORM\Entity
* @ORM\Table(name="PostRequest")
*/
class PostRequest
{
	/**
	* @ORM\Id
	* @ORM\Column(type="integer")
	* @ORM\GeneratedValue(strategy="AUTO")
	* @Assert\Range(min="0")
	*/
	protected $rid;

	/**
	* @ORM\Column(name="uid", type="string", length=80)
	* @Assert\NotNull(message="Requester should not be null.")
	* @Assert\Range(min="0")
	*/
	protected $requester;

	/**
	* @ORM\Column(name="bcid", type="smallint")
	* @Assert\NotNull(message="Budget Category should not be null.")
	* @Assert\Range(min="0")
	*/
	protected $category;

	/**
	* @ORM\Column(type="text", nullable=true)
	*/
	protected $explanation;

	/**
	* @ORM\Column(type="decimal", scale=2)
	* @Assert\NotNull(message="Amount should not be null.")
	* @Assert\Type(type="numeric", message="The value {{ value }} is not a valid {{ type }}.")
	* @Assert\Range(min="0")
	*/
	protected $amount;

	/**
	* @ORM\Column(type="decimal", scale=2)
	* @Assert\NotNull(message="Actual Amount should not be null.")
	* @Assert\Type(type="numeric", message="The value {{ value }} is not a valid {{ type }}.")
	* @Assert\Range(min="0")
	*/
	protected $actualAmount;

	/**
	* @ORM\Column(name="ctid", type="smallint")
	* @Assert\NotNull(message="Currency Type should not be null.")
	* @Assert\Range(min="0")
	*/
	protected $curtype;

	/**
	* @ORM\Column(type="smallint")
	* @Assert\NotNull(message="Pre-Approval should not be null.")
	* @Assert\Range(min="0", max="1")
	*/
	protected $preApproval;

	/**
	* @Assert\Type(type="numeric", message="The value {{ value }} is not a valid {{ type }}.")
	* @Assert\Range(min="0")
	*/
	protected $preApprovalNo;

	/**
	* @ORM\Column(type="integer", nullable=true)
	*/
	protected $prid;

	/**
	* @ORM\Column(type="smallint")
	* @Assert\NotNull(message="Payment Method should not be null.")
	* @Assert\Range(min="0")
	*/
	protected $paymentMethod;

	/**
	* @ORM\Column(type="string", length=80, nullable=true)
	*/
	protected $companyName;

	/**
	* @ORM\Column(type="string", length=80, nullable=true)
	*/
	protected $attention;

	/**
	* @ORM\Column(type="string", length=60, nullable=true)
	*/
	protected $street;

	/**
	* @ORM\Column(type="string", length=40, nullable=true)
	*/
	protected $city;

	/**
	* @ORM\Column(type="string", length=20, nullable=true)
	*/
	protected $state;

	/**
	* @ORM\Column(type="string", length=10, nullable=true)
	*/
	protected $zipcode;

	/**
	* @ORM\Column(type="string", length=80, nullable=true)
	* @Assert\Email(message="The email '{{ value }}' is not a valid email.")
	*/
	protected $contactEmail;

	/**
	* @ORM\Column(type="string", length=80, nullable=true)
	*/
	protected $accountName;

	/**
	* @ORM\Column(type="string", length=80, nullable=true)
	*/
	protected $bankName;

	/**
	* @ORM\Column(type="string", length=80, nullable=true)
	*/
	protected $accountNumber;

	/**
	* @ORM\Column(type="string", length=80, nullable=true)
	*/
	protected $swiftCode;

	/**
	* @ORM\Column(type="string", length=80, nullable=true)
	*/
	protected $routingNumber;

	// ???
	protected $invoice;

	/**
	* @ORM\Column(type="text", nullable=true)
	*/
	protected $invoicePath;

	/**
	* @ORM\Column(type="string", length=64)
	* @Assert\NotNull(message="Budget should not be null.")
	*/
	protected $budget;

	/**
	* @ORM\Column(type="smallint", nullable=true)
	*/
	protected $level;

	/**
	* @ORM\Column(type="smallint")
	* @Assert\NotNull(message="Chair should not be null.")
	* @Assert\Range(min="0")
	*/
	protected $chairId;

	/**
	* @ORM\Column(type="smallint")
	* @Assert\NotNull(message="Chair's Approval should not be null.")
	* @Assert\Range(min="0")
	*/
	protected $chairApproved;

	/**
	* @ORM\Column(type="text", nullable=true)
	*/
	protected $chairComment;

	/**
	* @ORM\Column(type="smallint")
	* @Assert\NotNull(message="CFO should not be null.")
	* @Assert\Range(min="0")
	*/
	protected $cfoId;

	/**
	* @ORM\Column(type="smallint")
	* @Assert\NotNull(message="CFO's Approval should not be null.")
	* @Assert\Range(min="0")
	*/
	protected $cfoApproved;

	/**
	* @ORM\Column(type="text", nullable=true)
	*/
	protected $cfoComment;

	/**
	* @ORM\Column(type="smallint")
	* @Assert\NotNull(message="President should not be null.")
	* @Assert\Range(min="0")
	*/
	protected $presidentId;

	/**
	* @ORM\Column(type="smallint")
	* @Assert\NotNull(message="President's Approval should not be null.")
	* @Assert\Range(min="0")
	*/
	protected $presidentApproved;

	/**
	* @ORM\Column(type="text", nullable=true)
	*/
	protected $presidentComment;

	/**
	* @ORM\Column(type="smallint")
	* @Assert\NotNull(message="Secretary should not be null.")
	* @Assert\Range(min="0")
	*/
	protected $secretaryId;

	/**
	* @ORM\Column(type="smallint")
	* @Assert\NotNull(message="Secretary's Approval should not be null.")
	* @Assert\Range(min="0")
	*/
	protected $secretaryApproved;

	/**
	* @ORM\Column(type="text", nullable=true)
	*/
	protected $secretaryComment;

	/**
	* @ORM\Column(type="date")
	* @Assert\NotNull(message="Submission date should not be null.")
	* @Assert\Date()
	*/
	protected $date;

	public function getRid() {
		return $this->rid;
	}

	public function setRid($rid) {
		$this->rid = $rid;
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

	public function getActualAmount() {
		return $this->actualAmount;
	}

	public function setActualAmount($actualAmount) {
		$this->actualAmount = $actualAmount;
	}

	public function getCurtype() {
		return $this->curtype;
	}

	public function setCurtype($curtype) {
		$this->curtype = $curtype;
	}

	public function getPreApproval() {
		return $this->preApproval;
	}

	public function setPreApproval($preApproval) {
		$this->preApproval = $preApproval;
	}

	public function getPreApprovalNo() {
		if ($this->prid)
			$this->preApprovalNo = sprintf("%08d", $this->prid);

		return $this->preApprovalNo;
	}

	public function setPreApprovalNo($preApprovalNo) {
		$this->preApprovalNo = $preApprovalNo;
	}

	public function getPrid() {
		return $this->prid;
	}

	public function setPrid($prid) {
		$this->prid = $prid;
	}

	public function getPaymentMethod() {
		return $this->paymentMethod;
	}

	public function setPaymentMethod($method) {
		$this->paymentMethod = $method;
	}

	public function getCompanyName() {
		return $this->companyName;
	}

	public function setCompanyName($name) {
		$this->companyName = $name;
	}

	public function getAttention() {
		return $this->attention;
	}

	public function setAttention($attention) {
		$this->attention = $attention;
	}

	public function getStreet() {
		return $this->street;
	}

	public function setStreet($street) {
		$this->street = $street;
	}

	public function getCity() {
		return $this->city;
	}

	public function setCity($city) {
		$this->city = $city;
	}

	public function getState() {
		return $this->state;
	}

	public function setState($state) {
		$this->state = $state;
	}

	public function getZipcode() {
		return $this->zipcode;
	}

	public function setZipcode($zipcode) {
		$this->zipcode = $zipcode;
	}

	public function getContactEmail() {
		return $this->contactEmail;
	}

	public function setContactEmail($email) {
		$this->contactEmail = $email;
	}

	public function getAccountName() {
		return $this->accountName;
	}

	public function setAccountName($name) {
		$this->accountName = $name;
	}

	public function getBankName() {
		return $this->bankName;
	}

	public function setBankName($name) {
		$this->bankName = $name;
	}

	public function getAccountNumber() {
		return $this->accountNumber;
	}

	public function setAccountNumber($no) {
		$this->accountNumber = $no;
	}

	public function getSwiftCode() {
		return $this->swiftCode;
	}

	public function setSwiftCode($code) {
		$this->swiftCode = $code;
	}

	public function getRoutingNumber() {
		return $this->routingNumber;
	}

	public function setRoutingNumber($no) {
		$this->routingNumber = $no;
	}

	public function getInvoice() {
		return $this->invoice;
	}

	public function setInvoice($invoice) {
		$this->invoice = $invoice;
	}

	public function getInvoicePath() {
		return $this->invoicePath;
	}

	public function setInvoicePath($path) {
		$this->invoicePath = $path;
	}

	public function getBudget() {
		return $this->budget;
	}

	public function setBudget($budget) {
		$this->budget = $budget;
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

	public function formatPreApprovalNo() {
		if ($this->preApprovalNo != null) {
			$this->prid = $this->preApprovalNo;
		} else {
			$this->prid = null;
		}
	}

	public function uploadFiles() {
		/* only one file is allowed to upload */

		// if no files are uploaded, leave it blank
		if (null === $this->invoice) {
			return;
		}

		$this->invoicePath = 'invoice_' . $this->preApprovalNo . '_' . time() . '.' . $this->invoice->guessExtension(); // invoice_preapprovalno_timestamp.extension
		$this->invoice->move(__DIR__.'/../../../../../uploads/documents', __DIR__.'/../../../../../uploads/documents/' . $this->invoicePath);
		$this->invoice = null;
	}
}

?>