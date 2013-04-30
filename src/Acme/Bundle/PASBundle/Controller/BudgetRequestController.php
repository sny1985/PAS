<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Acme\Bundle\PASBundle\Entity\BudgetRequest;
use Acme\Bundle\PASBundle\Entity\BudgetCategory;
use Acme\Bundle\PASBundle\Entity\CurrencyType;
use Acme\Bundle\PASBundle\Entity\User;

class BudgetRequestController extends Controller
{
	public function budgetRequestAction(Request $req)
	{
		$actoin = null;
		$budgetRequest = new BudgetRequest();
		$em = $this->getDoctrine()->getManager();
		$form = null;
		$id = null;
		$session = $this->get("session");
		$this->user = $this->getUser();

		// get category list from database
		$categories = $em->getRepository('AcmePASBundle:BudgetCategory')->findAll();
		foreach ($categories as $key => $value) {
			$category_array[$key + 1] = $value->getName();
		}

		// get currency type list from database
		$currencies = $em->getRepository('AcmePASBundle:CurrencyType')->findAll();
		foreach ($currencies as $key => $value) {
			$currency_array['name'][$key + 1] = $value->getName();
			$currency_array['code'][$key + 1] = $value->getCode();
		}

		// if there is a query and the action is query, then show record; otherwise edit the record in the form
			$param = $req->query->all();
		if (isset($param) && isset($param['action']) && isset($param['id'])) {
			$action = $param['action'];
			$id = $param['id'];
			$budgetRequest = $em->getRepository('AcmePASBundle:BudgetRequest')->findOneByBid($id);
			if ($action == 'edit' && $budgetRequest) {
				$session->set('action', 'edit');
				$budgetRequest->getActivityDuration();
			}
		}

		$month_array = array(null => 'Choose one month', 1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December');
		$year_array = array(null => 'Choose one year', date('Y') => date('Y'), date('Y') + 1 => date('Y') + 1, date('Y') + 2 => date('Y') + 2);

		// create form
		$form = $this->createFormBuilder($budgetRequest)
						->add('bid', 'hidden')
						->add('holder', 'choice', array('choices' => array($this->user->getUid() => $this->user->getUsername()), 'empty_value' => false, 'label' => 'Budget Holder:'))
						->add('category', 'choice', array('choices' => $category_array, 'empty_value' => 'Choose one category', 'label' => 'Budget Category (class):', 'preferred_choices' => array('empty_value')))
						->add('startmonth', 'choice', array('choices' => $month_array, 'empty_value' => 'Choose one month', 'label' => 'Starting Date of Activity:', 'preferred_choices' => array('empty_value')))
						->add('startyear', 'choice', array('choices' => $year_array, 'empty_value' => 'Choose one month', 'preferred_choices' => array('empty_value')))
						->add('endmonth', 'choice', array('choices' => $month_array, 'empty_value' => 'Choose one month', 'label' => 'Ending Date of Activity:', 'preferred_choices' => array('empty_value')))
						->add('endyear', 'choice', array('choices' => $year_array, 'empty_value' => 'Choose one month', 'preferred_choices' => array('empty_value')))
						->add('abstract', 'textarea', array('label' => 'Abstract of activity:', 'required' => false))
						->add('details', 'textarea', array('label' => 'Details:', 'required' => false))
						->add('amount', 'money', array('currency' => false, 'label' => 'Amount:'))
						->add('curtype', 'choice', array('choices' => $currency_array['code'], 'empty_value' => 'Choose one type', 'label' => 'Currency Type:', 'preferred_choices' => array('empty_value')))
						->add('date', 'hidden', array('data' => date('d-m-Y')))
						->add('approved', 'hidden', array('data' => 0))
						->getForm();

		// if the HTTP method is POST, handle form submission
		if ($req->isMethod('POST')) {
			$form->bind($req);

			// compose activity starting date and ending date
			$budgetRequest->setActivityDuration();

			// validate the data
			if ($form->isValid()) {
				// put into database
				$action = $session->get('action');
				if (isset($action) && $action == 'edit') {
					$session->remove('action');
					// update database
					$oldRequest = $em->getRepository('AcmePASBundle:BudgetRequest')->findOneByBid($budgetRequest->getBid());
					$oldRequest->setCategory($budgetRequest->getCategory());
					$oldRequest->setStartdate($budgetRequest->getStartdate());
					$oldRequest->setEnddate($budgetRequest->getEnddate());
					$oldRequest->setAbstract($budgetRequest->getAbstract());
					$oldRequest->setDetails($budgetRequest->getDetails());
					$oldRequest->setAmount($budgetRequest->getAmount());
					$oldRequest->setCurtype($budgetRequest->getCurtype());
					$oldRequest->setDate($budgetRequest->getDate());
					$oldRequest->setApproved(0);
					$em->flush();
				} else {
					// insert
					$em->persist($budgetRequest);
					$em->flush();
				}

				// if the request is a new one, there is no bid before insertion
				$id = $budgetRequest->getBid();

				// fetch data from database and go to success page
				$budgetRequest = $em->getRepository('AcmePASBundle:BudgetRequest')->findOneByBid($id);
				// redirect to confirmation page
				return $this->redirect($this->generateUrl('pas_budget_request_status', array('id' => $id, 'action' => 'submit')));
			}
		}

		// display form
		return $this->render('AcmePASBundle:Default:budget-request.html.twig', array('form' => $form->createView()));
	}
}