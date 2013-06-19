<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Acme\Bundle\PASBundle\Entity\BudgetCategory;
use Acme\Bundle\PASBundle\Entity\BudgetRequest;
use Acme\Bundle\PASBundle\Entity\User;
use Acme\Bundle\PASBundle\Services\CurrencyConverter;

class BudgetConfirmationController extends Controller
{
	public function budgetConfirmAction(Request $req)
	{
		$cc = $this->get('currency_converter');
		$content = null;
		$em = $this->getDoctrine()->getManager();
		$this->user = $this->getUser();

		// get category list from database
		$categories = $em->getRepository('AcmePASBundle:BudgetCategory')->findAll();
		foreach ($categories as $key => $value) {
			$category_array[$key + 1] = $value->getName();
		}

		// get currency type list from database and get the rate to USD
		$currencies = $em->getRepository('AcmePASBundle:CurrencyType')->findAll();
		foreach ($currencies as $key => $value) {
			$currency_array['name'][$key + 1] = $value->getName();
			$currency_array['code'][$key + 1] = $value->getCode();
			$cc->updateRate($value->getCode());
			$currency_array['rate'][$key + 1] = $value->getRate();
		}

		// if there is only a year in query, show summary; if there is a cid as well, show details
		$param = $req->query->all();
		if (isset($param)) {
			if (isset($param['year'])) {
				$year = $param['year'];
			} else {
				$year = date('Y');
			}

			// get the budget requests in specific year
			$start = new \DateTime($year . '-01-01');
			$end = new \DateTime($year . '-12-31');
			$budgetRequests = $em->createQuery('SELECT br FROM AcmePASBundle:BudgetRequest br WHERE br.startdate >= :start and br.startdate <= :end')->setParameters(array('start' => $start, 'end' => $end))->getResult();

			if (isset($param['cid']) && isset($param['year'])) {
				$cid = $param['cid'];
				$budgets = array();
				$sum = 0;
				foreach ($budgetRequests as $request) {
					// find all requests with selected category in the selected year
					if ($request->getCategory() == $cid) {
						$request->setAmount($request->getAmount() * $currency_array['rate'][$request->getCurtype()]);
						array_push($budgets, $request);
						$sum += $request->getAmount();
					}
				}

				$content = $this->renderView('AcmePASBundle:Default:budget-confirm-details.html.twig', array('cid' => $cid, 'categories' => $category_array, 'year' => $year, 'requests' => $budgets, 'sum' => $sum));
			} else {
				$budgets = array();
				$budgets["sum"] = 0;
				$budgets["categories"] = array();
				$unapproved_array = array();
				foreach ($budgetRequests as $request) {
					// record categories
					$category = $request->getCategory();
					if (array_search($category, $budgets["categories"]) == false) {
						array_push($budgets["categories"], $category);
					}
					// find the date of last request in each category
					if (!isset($budgets[$category]['lastdate']) || ($budgets[$category]['lastdate']->format('U') < $request->getDate()->format('U'))) {
						$budgets[$category]['lastdate'] = $request->getDate();
					}
					// calculate total amount of each category
					if (!isset($budgets[$category]['amount'])) {
						$budgets[$category]['amount'] = 0;
					}
					$multiply = $request->getAmount() * $currency_array['rate'][$request->getCurtype()];
					$budgets[$category]['amount'] += $multiply;
					// calculate total amount of all categories
					$budgets['sum'] += $multiply;
					// find out if approved or not
					$budgets[$category]['approved'] = $request->getApproved();
					// find unapproved requests
					if (!$request->getApproved()) {
						array_push($unapproved_array, $request->getBid());
					}
				}

				$content = $this->renderView('AcmePASBundle:Default:budget-confirm-summary.html.twig', array('categories' => $category_array, 'year' => $year, 'requests' => $budgets));
			}
		}
		
		// update approved field of unapproved records, and send emails to these just approved requesters
		// APPROVE BUDGETS ONLY IN SELECTED FY ???
		if ($req->isMethod('POST')) {
			// get sender's email address
			$sender = $em->getRepository('AcmePASBundle:User')->findOneByUid(0)->getEmail();
			// get vtm
			$vtm = $em->getRepository('AcmePASBundle:User')->findOneByRole("vtm");

			foreach ($unapproved_array as $unapproved) {
				$q = $em->createQuery("update AcmePASBundle:BudgetRequest br set br.approved = 1 where br.bid = $unapproved")->execute();

				$user = $em->createQuery("select u from AcmePASBundle:User u, AcmePASBundle:BudgetRequest br where u.uid = br.holder and br.bid = $unapproved")->execute();
				$message = \Swift_Message::newInstance()
							->setSubject('BDA Expense Budget Approval Notice Email')
							->setFrom($sender)
							->setTo($user[0]->getEmail())
							->setCc($vtm->getEmail())
							->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $user[0], 'role' => 'requester', 'type' => 'BDA Expense Budget Approval', 'link' => $this->generateUrl('pas_budget_request_status', array('action' => 'query', 'id' => $unapproved), true))), 'text/html');
					// show submission result
					$this->get('mailer')->send($message);
			}

			// redirect to success page
			return $this->redirect($this->generateUrl('pas_success', array('form' => 'budget approval')));
		}

		return new Response($content);
	}
}