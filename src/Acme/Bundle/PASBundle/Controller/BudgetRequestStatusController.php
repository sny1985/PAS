<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\BudgetRequest;
use Acme\Bundle\PASBundle\Entity\BudgetCategory;
use Acme\Bundle\PASBundle\Entity\CurrencyType;
use Acme\Bundle\PASBundle\Entity\User;

class BudgetRequestStatusController extends Controller
{
	public function budgetRequestReviewAction(Request $req)
	{
		$action = null;
		$budgetRequest = null;
		$em = $this->getDoctrine()->getManager();
		$id = null;
		$this->user = $this->getUser();

		// if the HTTP method is POST, handle form submission
		if ($req->isMethod('POST')) {
			// get id
			$param = $req->request->all();
			if (isset($param) && isset($param['id']))
				$id = $param['id'];

			$users = $em->getRepository('AcmePASBundle:User')->findAll();
			// get sender's email address
			$sender = $users[0]->getEmail();

			// get CFO from database, assume only one cfo in database
			
			foreach ($users as $user) {
				if ($user->getRole() == "cfo") {
					$cfo = $user;
				}
			}

			// send notice email to requester
			$message = \Swift_Message::newInstance()
						->setSubject('BDA Expense Budget Request Notice Email')
						->setFrom($sender)
						->setTo($this->user->getEmail())
						->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $this->user, 'role' => 'requester', 'type' => 'BDA Expense Budget Request', 'link' => $this->generateUrl('pas_budget_request_status', array('id' => $id, 'action' => 'query'), true))), 'text/html');
			$this->get('mailer')->send($message);

			// send notice email to CFO
			$message = \Swift_Message::newInstance()
						->setSubject('BDA Expense Budget Request Notice Email')
						->setFrom($sender)
						->setTo($cfo->getEmail())
						->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $cfo, 'role' => 'cfo', 'type' => 'BDA Expense Budget Request', 'link' => $this->generateUrl('pas_budget_confirmation_form', array(), true))), 'text/html');
			$this->get('mailer')->send($message);

			// redirect to success page
			return $this->redirect($this->generateUrl('pas_success', array('form' => 'budget request')));
		}

		$param = $req->query->all();
		if (isset($param) && isset($param['id'])) {
			$id = $param['id'];
			if (isset($param['action']))
				$action = $param['action'];
			$budgetRequest = $em->getRepository('AcmePASBundle:BudgetRequest')->findOneByBid($id);

			// do not allow other people peek it
			if ($budgetRequest && $budgetRequest->getHolder() != $this->user->getUid()) {
				throw $this->createNotFoundException('You are not allowed to view this request.');
			}
		}

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

		return $this->render('AcmePASBundle:Default:budget-request-query.html.twig', array('id' => $id, 'categories' => $category_array, 'currencies' => $currency_array, 'requester' => $this->user, 'request' => $budgetRequest, 'action' => $action));
	}
}