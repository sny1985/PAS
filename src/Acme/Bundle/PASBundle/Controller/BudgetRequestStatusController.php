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
		$requester = null;
		$this->user = $this->getUser();

		// if the HTTP method is POST, handle form submission
		if ($req->isMethod('POST')) {
			// get id
			$param = $req->request->all();
			if (isset($param) && isset($param['id'])) {
				$id = $param['id'];
				$budgetRequest = $em->getRepository('AcmePASBundle:BudgetRequest')->findOneByBid($id);
				$requester = $em->getRepository('AcmePASBundle:User')->findOneByUid($budgetRequest->getHolder());
			}

			$sender = $em->getRepository('AcmePASBundle:User')->findOneByUid("0");
			$admin = $em->getRepository('AcmePASBundle:User')->findOneByRole("admin");
			$cfo = $em->getRepository('AcmePASBundle:User')->findOneByRole("cfo");
			$vtm = $em->getRepository('AcmePASBundle:User')->findOneByRole("vtm");

			$users = $em->getRepository('AcmePASBundle:User')->findAll();
			// get sender's email address
			// get CFO and VTM from database, assume only one cfo & one vtm in database
			foreach ($users as $user) {
				if ($user->getRole() == "cfo") {
					$cfo = $user;
				} else if ($user->getRole() == "vtm") {
					$vtm = $user;
				}
			}

			// send notice email to requester
			$message = \Swift_Message::newInstance()
						->setSubject('BDA Expense Budget Request Notice Email')
						->setFrom($sender->getEmail())
						->setTo($this->user->getEmail())
						->setCc($admin->getEmail())
						->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $requester, 'role' => 'requester', 'type' => 'BDA Expense Budget Request', 'link' => $this->generateUrl('pas_budget_request_status', array('id' => $id, 'action' => 'query'), true))), 'text/html');
			$this->get('mailer')->send($message);

			// send notice email to CFO
			$message = \Swift_Message::newInstance()
						->setSubject('BDA Expense Budget Request Notice Email')
						->setFrom($sender->getEmail())
						->setTo($cfo->getEmail())
						->setCc($vtm->getEmail())
						->setCc($admin->getEmail())
						->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $cfo, 'role' => 'cfo', 'type' => 'BDA Expense Budget Request', 'link' => $this->generateUrl('pas_budget_confirmation_form', array(), true))), 'text/html');
			$this->get('mailer')->send($message);

			// redirect to success page
			return $this->redirect($this->generateUrl('pas_success', array('form' => 'budget request')));
		}

		$param = $req->query->all();
		if (isset($param) && isset($param['id'])) {
			if (isset($param['id'])) {
				$id = $param['id'];
			}
			if (isset($param['action'])) {
				$action = $param['action'];
			}
			$budgetRequest = $em->getRepository('AcmePASBundle:BudgetRequest')->findOneByBid($id);
			$requester = $em->getRepository('AcmePASBundle:User')->findOneByUid($budgetRequest->getHolder());
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

		return $this->render('AcmePASBundle:Default:budget-request-query.html.twig', array('id' => $id, 'categories' => $category_array, 'currencies' => $currency_array, 'requester' => $requester, 'request' => $budgetRequest, 'action' => $action));
	}
}