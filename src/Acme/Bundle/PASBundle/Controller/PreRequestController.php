<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\BudgetApplication;
use Acme\Bundle\PASBundle\Entity\PreRequest;
use Acme\Bundle\PASBundle\Entity\User;

class PreRequestController extends Controller
{
	public function preRequestAction(Request $req)
	{
		$actoin = null;
		$em = $this->getDoctrine()->getManager();
		$form = null;
		$preRequest = new PreRequest();
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
			// get rate from google
			$currency_array['rate'][$key + 1] = 1;
			$url = "http://www.google.com/ig/calculator?hl=en&q=1" . $value->getCode() . "=?USD";
			$result = file_get_contents($url);
			$result = json_decode(preg_replace('/(\w+):/i', '"\1":', $result));
			if ($result->icc == true) {
				$rs = explode(' ', $result->rhs);
				$currency_array['rate'][$key + 1] = (double)$rs[0];
			}
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

		$budgetRequests = $em->getRepository('AcmePASBundle:BudgetRequest')->findAll();
		$year_array = array();
		$budgets = array();
		$sum = 0;
		foreach ($budgetRequests as $request) {
			// find years and place them in order
			$year = $request->getStartdate()->format('Y');
			if (array_key_exists($year, $year_array) == false) {
				$year_array[$year] = $year;
			}
			ksort($year_array);
			// record categories
			if (!isset($budgets[$year]["categories"])) {
				$budgets[$year]["categories"] = array();
			}
			$category = $request->getCategory();
			if (array_search($category, $budgets[$year]["categories"]) == false) {
				array_push($budgets[$year]["categories"], $category); 
			}
			// calculate total amount of each category
			if (!isset($budgets[$year][$category]['amount'])) {
				$budgets[$year][$category]['amount'] = 0;
			}
			$budgets[$year][$category]['amount'] += $request->getAmount() * $currency_array['rate'][$request->getCurtype()];
		}

		foreach ($year_array as $year) {
			foreach ($budgets[$year]['categories'] as $category) {
				$budgets[$year][$category]['amount'] = sprintf("%0.2f", $budgets[$year][$category]['amount']);
			}
		}

		// if there is a query
		$param = $req->query->all();
		// edit
		if (isset($param) && isset($param['action']) && isset($param['id'])) {
			$action = $param['action'];
			$id = $param['id'];
			$preRequest = $em->getRepository('AcmePASBundle:PreRequest')->findOneByPrid($id);
			if ($action == 'edit' && $preRequest) {
				$session->set('action', 'edit');
			}
		}

		// create form
		$form = $this->createFormBuilder($preRequest)
						->add('prid', 'hidden')
						->add('requester', 'choice', array('choices' => array($this->user->getUid() => $this->user->getUsername()), 'empty_value' => false, 'label' => 'Requester:'))
						->add('category', 'choice', array('choices' => $category_array, 'empty_value' => 'Choose one category', 'label' => 'Budget Category (class):'))
						->add('explanation', 'textarea', array('label' => 'Explanation of the Expense:', 'required' => false))
						->add('amount', 'money', array('currency' => false, 'label' => 'Amount:'))
						->add('curtype', 'choice', array('choices' => $currency_array['code'], 'empty_value' => 'Choose one type', 'label' => 'Currency Type:', 'preferred_choices' => array('empty_value')))
						->add('budgetCategory', 'hidden', array('data' => null))
						->add('level', 'choice', array('choices' => array(1 => 'Below or equal to US$10,000: by the Chair', 2 => 'Above US$10,000: by Secretary, President and CFO '), 'empty_value' => 'Choose one level', 'label' => 'Approval Level:', 'preferred_choices' => array('empty_value')))
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
			if ($form->isValid()) {
				$action = $session->get('action');
				if (isset($action) && $action == 'edit') {
					$session->remove('action');
					
var_dump($preRequest);					
					$oldRequest = $em->getRepository('AcmePASBundle:PreRequest')->findOneByPrid($preRequest->getPrid());
var_dump($oldRequest);
					$oldRequest->setCategory($preRequest->getCategory());
					$oldRequest->setExplanation($preRequest->getExplanation());
					$oldRequest->setAmount($preRequest->getAmount());
					$oldRequest->setCurtype($preRequest->getCurtype());
					$oldRequest->setBudgetCategory($preRequest->getBudgetCategory());
					$oldRequest->setLevel($preRequest->getLevel());
					$oldRequest->setChairId($preRequest->getChairId());
					$oldRequest->setCfoId($preRequest->getCfoId());
					$oldRequest->setPresidentId($preRequest->getPresidentId());
					$oldRequest->setSecretaryId($preRequest->getSecretaryId());
					$oldRequest->setDate($preRequest->getDate());
					$oldRequest->setChairApproved(0);
					$oldRequest->setCfoApproved(0);
					$oldRequest->setPresidentApproved(0);
					$oldRequest->setSecretaryApproved(0);
					$em->flush();
				} else {
					$em->persist($preRequest);
					$em->flush();
				}

				// if the request is a new one, there is no bid before insertion
				$id = $preRequest->getPrid();

				foreach ($users as $user) {
					if ($user->getUid() == $preRequest->getChairId()) {
						$selected_chair = $user;
					}
				}

				// send notice email to requester
				$message = \Swift_Message::newInstance()
							->setSubject('Pre-Payment Request Notice Email')
							->setFrom($sender)
							->setTo($this->user->getEmail())
							->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $this->user, 'role' => 'requester', 'type' => 'Pre-Payment Request', 'link' => $this->generateUrl('pas_pre_request_status', array('id' => $id), true))), 'text/html');
				$this->get('mailer')->send($message);

				// send notice email to approvers
				if ($preRequest->getChairId()) {
					$message = \Swift_Message::newInstance()
								->setSubject('Pre-Payment Request Notice Email')
								->setFrom($sender)
								->setTo($selected_chair->getEmail())
								->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $selected_chair, 'role' => 'chair', 'type' => 'Pre-Payment Request', 'link' => $this->generateUrl('pas_pre_approval_form', array('id' => $id), true))), 'text/html');
					$this->get('mailer')->send($message);
				}
				if ($preRequest->getCfoId()) {
					$message = \Swift_Message::newInstance()
								->setSubject('Pre-Payment Request Notice Email')
								->setFrom($sender)
								->setTo($cfo->getEmail())
								->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $cfo, 'role' => 'cfo', 'type' => 'Pre-Payment Request', 'link' => $this->generateUrl('pas_pre_approval_form', array('id' => $id), true))), 'text/html');
					$this->get('mailer')->send($message);
				}
				if ($preRequest->getPresidentId()) {
					$message = \Swift_Message::newInstance()
								->setSubject('Pre-Payment Request Notice Email')
								->setFrom($sender)
								->setTo($president->getEmail())
								->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $president, 'role' => 'president', 'type' => 'Pre-Payment Request', 'link' => $this->generateUrl('pas_pre_approval_form', array('id' => $id), true))), 'text/html');
					$this->get('mailer')->send($message);
				}
				if ($preRequest->getSecretaryId()) {
					$message = \Swift_Message::newInstance()
								->setSubject('Pre-Payment Request Notice Email')
								->setFrom($sender)
								->setTo($secretary->getEmail())
								->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $secretary, 'role' => 'secretary', 'type' => 'Pre-Payment Request', 'link' => $this->generateUrl('pas_pre_approval_form', array('id' => $id), true))), 'text/html');
					$this->get('mailer')->send($message);
				}

				// redirect to prevent resubmission
				return $this->redirect($this->generateUrl('pas_pre_request_status', array('id' => $id, 'action' => $action)));
			} else {
				// HANDLE EXCEPTIONS ???
			}
		}

		// display form
		return $this->render('AcmePASBundle:Default:pre-request.html.twig', array('form' => $form->createView(), 'categories' => $category_array, 'years' => $year_array, 'budgets' => $budgets));
	}
}