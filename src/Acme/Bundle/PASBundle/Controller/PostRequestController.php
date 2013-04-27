<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\PostRequest;
use Acme\Bundle\PASBundle\Entity\User;

class PostRequestController extends Controller
{
	public function postRequestAction(Request $req)
	{
		$actoin = null;
		$em = $this->getDoctrine()->getManager();
		$form = null;
		$postRequest = new PostRequest();
		$preApproval = 0;
		$sender = "nshi@caistudio.com";
		$session = $this->get("session");
		$this->user = $this->getUser();

		// get category list from database
		$categories = $em->getRepository('AcmePASBundle:BudgetCategory')->findAll();
		$category_array = array();
		foreach ($categories as $key => $value) {
			$category_array[$key + 1] = $value->getName();
		}

		// get currency type list from database
		$currencies = $em->getRepository('AcmePASBundle:CurrencyType')->findAll();
		foreach ($currencies as $key => $value) {
			$currency_array['name'][$key + 1] = $value->getName();
			$currency_array['code'][$key + 1] = $value->getCode();
		}

		// get chairs, secretary, CFO & president from database
		$chairs = array();
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
				array_push($chairs, $user);
				$chair_array[$user->getUid()] = $user->getUsername();
			} else if ($user->getRole() == "cfo") {
				$cfo = $user;
				$cfo_array[$user->getUid()] = $user->getUsername();
			} else if ($user->getRole() == "president") {
				$president = $user;
				$president_array[$user->getUid()] = $user->getUsername();
			} else if ($user->getRole() == "secretary") {
				$secretary = $user;
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
		if (isset($param) && isset($param['action']) && isset($param['id'])) {
			$action = $param['action'];
			$id = $param['id'];
			$postRequest = $em->getRepository('AcmePASBundle:PostRequest')->findOneByRid($id);
			if ($action == 'edit' && $postRequest) {
				$session->set('action', 'edit');
			}
		}

		// create form
		$form = $this->createFormBuilder($postRequest)
						->add('rid', 'hidden')
						->add('requester', 'choice', array('choices' => array($this->user->getUid() => $this->user->getUsername()), 'empty_value' => false, 'label' => 'Requester:'))
						->add('category', 'choice', array('choices' => $category_array, 'empty_value' => 'Choose one category', 'label' => 'Budget Category (class):'))
						->add('explanation', 'textarea', array('label' => 'Explanation of the Expense:', 'required' => false))
						->add('amount', 'money', array('currency' => false, 'label' => 'Amount:'))
						->add('curtype', 'choice', array('choices' => $currency_array['code'], 'empty_value' => 'Choose one type', 'label' => 'Currency Type:', 'preferred_choices' => array('empty_value')))
						->add('preApproval', 'choice', array('choices' => array(0 => 'No', 1 => 'Yes'), 'empty_value' => false, 'expanded' => true, 'label' => 'Pre Approved?', 'preferred_choices' => array($preApproval)))
						->add('preApprovalNo', 'text', array('label' => 'Pre-approval Number:', 'required' => false))
						->add('paymentMethod', 'choice', array('choices' => array(1 => "Check (payment to US-based vendors only)", 2 => 'Wire Transfer'), 'empty_value' => 'Choose one method', 'label' => 'Payment Method:', 'preferred_choices' => array('empty_value')))
						->add('companyName', 'text', array('label' => 'Company Name (payable to):', 'required' => false))
						->add('attention', 'text', array('label' => 'Attention:', 'required' => false))
						->add('address', 'text', array('label' => 'Address:', 'required' => false))
						->add('accountName', 'text', array('label' => 'Account Name:', 'required' => false))
						->add('bankName', 'text', array('label' => 'Bank Name:', 'required' => false))
						->add('accountNumber', 'text', array('label' => 'Account Number:', 'required' => false))
						->add('swiftCode', 'text', array('label' => 'Swift Code:', 'required' => false))
						->add('routingNumber', 'text', array('label' => 'Routing Number:', 'required' => false))
						->add('contactEmail', 'text', array('label' => 'Contact Email (if known):', 'required' => false))
						->add('invoice', 'file', array('label' => 'Invoice:','required' => false))
						->add('level', 'choice', array('choices' => array(1 => 'Below or equal to US$10,000: by the Chair', 2 => 'Above US$10,000: by Secretary, President and CFO '), 'empty_value' => 'Choose one level', 'label' => 'Approval Level:', 'preferred_choices' => array('empty_value'), 'required' => false))
						->add('chairId', 'choice', array('choices' => $chair_array, 'empty_value' => false, 'label' => 'Chair:', 'required' => false))
						->add('chairApproved', 'hidden', array('data' => 0))
						->add('chairComment', 'hidden', array('data' => null))
						->add('secretaryId', 'choice', array('choices' => $secretary_array, 'empty_value' => false, 'label' => 'Secretary:', 'required' => false))
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
			// validate the data and insert into database
			// HANDLE MULTIPLE FILES UPLOADING ???
			if ($form->isValid()) {
				$postRequest->formatPreApprovalNo();
				$postRequest->uploadFiles();
				$action = $session->get('action');
				if (isset($action) && $action == 'edit') {
					$session->remove('action');
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
					$oldRequest->setAddress($postRequest->getAddress());
					$oldRequest->setAccountName($postRequest->getAccountName());
					$oldRequest->setBankName($postRequest->getBankName());
					$oldRequest->setAccountNumber($postRequest->getAccountNumber());
					$oldRequest->setSwiftCode($postRequest->getSwiftCode());
					$oldRequest->setRoutingNumber($postRequest->getRoutingNumber());
					$oldRequest->setContactEmail($postRequest->getContactEmail());
					$oldRequest->setInvoicePath($postRequest->getInvoicePath());
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

				foreach ($users as $user) {
					if ($user->getUid() == $postRequest->getChairId()) {
						$selected_chair = $user;
					}
				}

				// send notice email to requester
				$message = \Swift_Message::newInstance()
							->setSubject('Payment Request Notice Email')
							->setFrom($sender)
							->setTo($this->user->getEmail())
							->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $this->user, 'role' => 'requester', 'type' => 'Payment Request', 'link' => $this->generateUrl('pas_post_request_status', array('id' => $id), true))), 'text/html');
				$this->get('mailer')->send($message);

				// send notice email to approvers
				if ($postRequest->getChairId()) {
					$message = \Swift_Message::newInstance()
								->setSubject('Payment Request Notice Email')
								->setFrom($sender)
								->setTo($selected_chair->getEmail())
								->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $selected_chair, 'role' => 'chair', 'type' => 'Payment Request', 'link' => $this->generateUrl('pas_post_approval_form', array('id' => $id), true))), 'text/html');
					$this->get('mailer')->send($message);
				}
				if ($postRequest->getCfoId()) {
					$message = \Swift_Message::newInstance()
								->setSubject('Payment Request Notice Email')
								->setFrom($sender)
								->setTo($cfo->getEmail())
								->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $cfo, 'role' => 'cfo', 'type' => 'Payment Request', 'link' => $this->generateUrl('pas_post_approval_form', array('id' => $id), true))), 'text/html');
					$this->get('mailer')->send($message);
				}
				if ($postRequest->getPresidentId()) {
					$message = \Swift_Message::newInstance()
								->setSubject('Payment Request Notice Email')
								->setFrom($sender)
								->setTo($president->getEmail())
								->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $president, 'role' => 'president', 'type' => 'Payment Request', 'link' => $this->generateUrl('pas_post_approval_form', array('id' => $id), true))), 'text/html');
					$this->get('mailer')->send($message);
				}
				if ($postRequest->getSecretaryId()) {
					$message = \Swift_Message::newInstance()
								->setSubject('Payment Request Notice Email')
								->setFrom($sender)
								->setTo($secretary->getEmail())
								->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $secretary, 'role' => 'secretary', 'type' => 'Payment Request', 'link' => $this->generateUrl('pas_post_approval_form', array('id' => $id), true))), 'text/html');
					$this->get('mailer')->send($message);
				}

				// redirect to prevent resubmission
				return $this->redirect($this->generateUrl('pas_post_request_status', array('id' => $id, 'action' => $action)));
			} else {
				// HANDLE EXCEPTIONS ???
			}
		}

		// display form
		return $this->render('AcmePASBundle:Default:post-request.html.twig', array('form' => $form->createView()));
	}
}