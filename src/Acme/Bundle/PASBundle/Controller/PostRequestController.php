<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Acme\Bundle\PASBundle\Entity\PostRequest;
use Acme\Bundle\PASBundle\Entity\User;

class PostRequestController extends Controller
{
	public function postRequestAction(Request $req)
	{
		$action = "submit";
		$em = $this->getDoctrine()->getManager();
		$hasInvoice = 0;
		$postRequest = new PostRequest();
		$preApproval = 0;
		$this->user = $this->getUser();

		// get category list from database
		$categories = $em->getRepository('AcmePASBundle:BudgetCategory')->findBy(array(), array('name' => 'ASC'));
		$category_array = array();
		foreach ($categories as $category) {
			$category_array[$category->getBcid()] = $category->getName();
		}

		// get currency type list from database
		$currencies = $em->getRepository('AcmePASBundle:CurrencyType')->findBy(array(), array('code' => 'ASC'));
		foreach ($currencies as $currency) {
			$currency_array['name'][$currency->getCtid()] = $currency->getName();
			$currency_array['code'][$currency->getCtid()] = $currency->getCode();
		}

		// get chairs, secretary, CFO & president from database
		$chair_array = array();
		$chair_array[0] = "Choose one chair";
		$cfo_array = array();
		$cfo_array[0] = "Choose one CFO";
		$president_array = array();
		$president_array[0] = "Choose one president";
		$secretary_array = array();
		$secretary_array[0] = "Choose one secretary";
		$users = $em->getRepository('AcmePASBundle:User')->findAll();
		foreach ($users as $user) {
			if ($user->getRole() == "chair") {
				$chair_array[$user->getUid()] = $user->getUsername();
			} else if ($user->getRole() == "cfo") {
				$cfo_array[$user->getUid()] = $user->getUsername();
			} else if ($user->getRole() == "president") {
				$president_array[$user->getUid()] = $user->getUsername();
			} else if ($user->getRole() == "secretary") {
				$secretary_array[$user->getUid()] = $user->getUsername();
			}
		}

		// if there is a query
		$param = $req->query->all();
		// has pre approval no.
		if (isset($param) && isset($param['prid'])) {
			$postRequest->setPreApproval(1);
			$postRequest->setPreApprovalNo(sprintf("%08d", $param['prid']));
		}
		// edit
		if (isset($param) && isset($param['id']) && isset($param['action'])) {
			$id = $param['id'];
			$action = $param['action'];
			$postRequest = $em->getRepository('AcmePASBundle:PostRequest')->findOneByRid($id);
			if ($postRequest) {
				// do not allow other people peek it
				if ($postRequest->getRequester() != $this->user->getUid()) {
					throw new HttpException(403, 'You are not allowed to change this request.');
				}
			}
		}
		// create form
		$form = $this->createFormBuilder($postRequest)
						->add('rid', 'hidden')
						->add('requester', 'choice', array('choices' => array($this->user->getUid() => $this->user->getUsername()), 'empty_value' => false, 'label' => 'Requester:'))
						->add('category', 'choice', array('choices' => $category_array, 'empty_value' => 'Choose one category', 'label' => 'Budget Category (class):'))
						->add('explanation', 'textarea', array('label' => 'Explanation of the Expense:', 'required' => false))
						->add('amount', 'money', array('currency' => false, 'label' => 'Amount (e.g. 200 or 199.99):'))
						->add('actualAmount', 'hidden', array('data' => 0))
						->add('curtype', 'choice', array('choices' => $currency_array['code'], 'empty_value' => 'Choose one type', 'label' => 'Currency Type:', 'preferred_choices' => array('empty_value')))
						->add('preApproval', 'choice', array('choices' => array(0 => 'No', 1 => 'Yes'), 'empty_value' => false, 'expanded' => true, 'label' => 'Pre Approved?', 'preferred_choices' => array($preApproval)))
						->add('preApprovalNo', 'text', array('label' => 'Pre-approval Number (e.g. 00000214):', 'required' => false))
						->add('paymentMethod', 'choice', array('choices' => array(1 => "Check (payment to US-based vendors only)", 2 => 'Wire Transfer'), 'empty_value' => 'Choose one method', 'label' => 'Payment Method:', 'preferred_choices' => array('empty_value')))
						->add('companyName', 'text', array('label' => 'Company Name (payable to):', 'required' => false))
						->add('attention', 'text', array('label' => 'Attention:', 'required' => false))
						->add('street', 'text', array('label' => 'Street:', 'required' => false))
						->add('city', 'text', array('label' => 'City:', 'required' => false))
						->add('state', 'text', array('label' => 'State:', 'required' => false))
						->add('zipcode', 'text', array('label' => 'Zip Code:', 'required' => false))
						->add('accountName', 'text', array('label' => 'Account Name:', 'required' => false))
						->add('bankName', 'text', array('label' => 'Bank Name:', 'required' => false))
						->add('accountNumber', 'text', array('label' => 'Account Number:', 'required' => false))
						->add('swiftCode', 'text', array('label' => 'Swift Code:', 'required' => false))
						->add('routingNumber', 'text', array('label' => 'Routing Number:', 'required' => false))
						->add('contactEmail', 'text', array('label' => 'Contact Email (if known):', 'required' => false))
						->add('hasInvoice', 'choice', array('choices' => array(0 => 'No', 1 => 'Yes'), 'empty_value' => false, 'expanded' => true, 'label' => 'Has Invoice?', 'preferred_choices' => array($hasInvoice)))
						->add('invoice', 'file', array('label' => 'Invoice:', 'required' => false))
						->add('invoicePath', 'hidden', array('data' => $postRequest->getInvoicePath()))
						->add('budget', 'hidden', array('data' => null))
						->add('level', 'choice', array('choices' => array(1 => 'Below or equal to 10,000 USD: by the Chair', 2 => 'Above 10,000 USD: by Secretary, President and CFO '), 'empty_value' => 'Choose one level', 'label' => 'Approval Level:', 'preferred_choices' => array('empty_value'), 'required' => false))
						->add('chairId', 'choice', array('choices' => $chair_array, 'empty_value' => false, 'label' => 'Chair:', 'required' => false))
						->add('chairApproved', 'hidden', array('data' => 0))
						->add('chairComment', 'hidden', array('data' => null))
						->add('secretaryId', 'choice', array('choices' => $secretary_array, 'label' => 'Secretary:'))
						->add('secretaryApproved', 'hidden', array('data' => 0))
						->add('secretaryComment', 'hidden', array('data' => null))
						->add('cfoId', 'choice', array('choices' => $cfo_array, 'empty_value' => false, 'label' => 'CFO:', 'required' => false))
						->add('cfoApproved', 'hidden', array('data' => 0))
						->add('cfoComment', 'hidden', array('data' => null))
						->add('presidentId', 'choice', array('choices' => $president_array, 'empty_value' => false, 'label' => 'President:', 'required' => false))
						->add('presidentApproved', 'hidden', array('data' => 0))
						->add('presidentComment', 'hidden', array('data' => null))
						->add('date', 'hidden', array('data' => date('d-m-Y')))
						->getForm();

		// if the HTTP method is POST, handle form submission
		if ($req->isMethod('POST')) {
			$form->bind($req);
			// validate the data and put into database
			// HANDLE MULTIPLE FILES UPLOADING ???
			if ($form->isValid()) {
				$postRequest->formatPreApprovalNo();
				$postRequest->uploadFiles();
				$post = $req->request->all();
				$action = $post['action'];
				if (isset($action) && $action == 'edit') {
					$oldRequest = $em->getRepository('AcmePASBundle:PostRequest')->findOneByRid($postRequest->getRid());
					$oldRequest->setCategory($postRequest->getCategory());
					$oldRequest->setExplanation($postRequest->getExplanation());
					$oldRequest->setAmount($postRequest->getAmount());
					$oldRequest->setCurtype($postRequest->getCurtype());
					$oldRequest->setPreApproval($postRequest->getPreApproval());
					$oldRequest->setPrid($postRequest->getPrid());
					$oldRequest->setPaymentMethod($postRequest->getPaymentMethod());
					$oldRequest->setCompanyName($postRequest->getCompanyName());
					$oldRequest->setAttention($postRequest->getAttention());
					$oldRequest->setStreet($postRequest->getStreet());
					$oldRequest->setCity($postRequest->getCity());
					$oldRequest->setState($postRequest->getState());
					$oldRequest->setZipcode($postRequest->getZipcode());
					$oldRequest->setAccountName($postRequest->getAccountName());
					$oldRequest->setBankName($postRequest->getBankName());
					$oldRequest->setAccountNumber($postRequest->getAccountNumber());
					$oldRequest->setSwiftCode($postRequest->getSwiftCode());
					$oldRequest->setRoutingNumber($postRequest->getRoutingNumber());
					$oldRequest->setContactEmail($postRequest->getContactEmail());
					$oldRequest->setHasInvoice($postRequest->getHasInvoice());
					$oldRequest->setInvoicePath($postRequest->getInvoicePath());
					$oldRequest->setBudget($postRequest->getBudget());
					$oldRequest->setLevel($postRequest->getLevel());
					$oldRequest->setChairId($postRequest->getChairId());
					$oldRequest->setCfoId($postRequest->getCfoId());
					$oldRequest->setPresidentId($postRequest->getPresidentId());
					$oldRequest->setSecretaryId($postRequest->getSecretaryId());
					$oldRequest->setDate($postRequest->getDate());
					$oldRequest->setChairApproved(0);
					$oldRequest->setCfoApproved(0);
					$oldRequest->setPresidentApproved(0);
					$oldRequest->setSecretaryApproved(0);
					$em->flush();
				} else {
					$em->persist($postRequest);
					$em->flush();
				}

				// if the request is a new one, there is no bid before insertion
				$id = $postRequest->getRid();

				// redirect to prevent resubmission
				return $this->redirect($this->generateUrl('pas_post_request_status', array('id' => $id, 'action' => 'submit')));
			}
		}

		// display form
		return $this->render('AcmePASBundle:Default:post-request.html.twig', array('form' => $form->createView(), 'action' => $action));
	}
}