<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Acme\Bundle\PASBundle\Entity\CurrencyType;
use Acme\Bundle\PASBundle\Entity\PreRequest;
use Acme\Bundle\PASBundle\Entity\User;
use Acme\Bundle\PASBundle\Services\CurrencyConverter;

class PreRequestController extends Controller
{
	public function preRequestAction(Request $req)
	{
		$action = "submit";
		$cc = $this->get('currency_converter');
		$em = $this->getDoctrine()->getManager();
		$preRequest = new PreRequest();
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
			$currency_array['rate'][$currency->getCtid()] = $cc->updateRate($currency->getCode());
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
		// edit
		if (isset($param) && isset($param['id']) && isset($param['action'])) {
			$id = $param['id'];
			$action = $param['action'];
			$preRequest = $em->getRepository('AcmePASBundle:PreRequest')->findOneByPrid($id);
			// do not allow other people peek it
			if ($preRequest->getRequester() != $this->user->getUid()) {
				throw new HttpException(403, 'You are not allowed to change this request.');
			}
		}

		// create form
		$form = $this->createFormBuilder($preRequest)
						->add('prid', 'hidden')
						->add('requester', 'choice', array('choices' => array($this->user->getUid() => $this->user->getUsername()), 'empty_value' => false, 'label' => 'Requester:'))
						->add('category', 'choice', array('choices' => $category_array, 'empty_value' => 'Choose one category', 'label' => 'Budget Category (class):'))
						->add('explanation', 'textarea', array('label' => 'Explanation of the Expense:', 'required' => false))
						->add('amount', 'money', array('currency' => false, 'label' => 'Amount (e.g. 200 or 199.99):'))
						->add('curtype', 'choice', array('choices' => $currency_array['code'], 'empty_value' => 'Choose one type', 'label' => 'Currency Type:', 'preferred_choices' => array('empty_value')))
						->add('selectedBudget', 'hidden', array('data' => null))
						->add('level', 'choice', array('choices' => array(1 => 'Below or equal to 10,000 USD: by the Chair', 2 => 'Above 10,000 USD: by Secretary, President and CFO '), 'empty_value' => 'Choose one level', 'label' => 'Approval Level:', 'preferred_choices' => array('empty_value')))
						->add('chairId', 'choice', array('choices' => $chair_array, 'label' => 'Chair:'))
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
			// validate the data and put into database
			if ($form->isValid()) {
				$post = $req->request->all();
				$action = $post['action'];
				if (isset($action) && $action == 'edit') {
					$oldRequest = $em->getRepository('AcmePASBundle:PreRequest')->findOneByPrid($preRequest->getPrid());
					$oldRequest->setCategory($preRequest->getCategory());
					$oldRequest->setExplanation($preRequest->getExplanation());
					$oldRequest->setAmount($preRequest->getAmount());
					$oldRequest->setCurtype($preRequest->getCurtype());
					$oldRequest->setSelectedBudget($preRequest->getSelectedBudget());
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

				// redirect to confirmation page
				return $this->redirect($this->generateUrl('pas_pre_request_status', array('id' => $id, 'action' => 'submit')));
			}
		}

		// display form
		return $this->render('AcmePASBundle:Default:pre-request.html.twig', array('form' => $form->createView(), 'categories' => $category_array, 'action' => $action));
	}
}