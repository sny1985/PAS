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
		$type = 2;
		$this->user = $this->getUser();
		$year = date('Y');

		// get category list from database
		$categories = $em->getRepository('AcmePASBundle:BudgetCategory')->findBy(array(), array('name' => 'ASC'));
		$category_array = array();
		foreach ($categories as $category) {
			$category_array[$category->getBcid()] = $category->getName();
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
			if (isset($param['type'])) {
				$type = $param['type'];
			}
			if (isset($param['year'])) {
				$year = $param['year'];
			}

			// get the budget requests in specific year
			$start = new \DateTime($year . '-01-01');
			$end = new \DateTime($year . '-12-31');
			$budgetRequests = $em->createQuery('SELECT br FROM AcmePASBundle:BudgetRequest br WHERE br.startdate >= :start and br.startdate <= :end and br.requestType = :type')->setParameters(array('start' => $start, 'end' => $end, 'type' => $type))->getResult();

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

				$content = $this->renderView('AcmePASBundle:Default:budget-confirm-details.html.twig', array('cid' => $cid, 'categories' => $category_array, 'type' => $type, 'year' => $year, 'requests' => $budgets, 'sum' => $sum));
			} else {
				$budgets = array();
				$budgets["sum"] = 0;
				$budgets["categories"] = array_keys($category_array);
				$cat = array();
				foreach ($budgetRequests as $request) {
					// record categories
					$category = $request->getCategory();
					if (array_search($category, $cat) == false) {
						array_push($cat, $category);
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
				}

				$content = $this->renderView('AcmePASBundle:Default:budget-confirm-summary.html.twig', array('categories' => $category_array, 'type' => $type, 'year' => $year, 'requests' => $budgets));
			}
		}
		
		// update approved field of unapproved records, and send emails to these just approved requesters
		if ($req->isMethod('POST')) {
			$to_approve = array();
			$post = $req->request->all();

			if (isset($post['year'])) {
				$year = $post['year'];
			}
			$start = new \DateTime($year . '-01-01');
			$end = new \DateTime($year . '-12-31');

			if (isset($post['categories_to_approve'])) {
				foreach ($post['categories_to_approve'] as $bcid) {
					$budgetRequests = $em->createQuery('SELECT br FROM AcmePASBundle:BudgetRequest br WHERE br.category = :bcid and br.startdate >= :start and br.startdate <= :end and br.requestType = 2 and br.approved = 0')->setParameters(array('bcid' => $bcid, 'start' => $start, 'end' => $end))->getResult();
					foreach ($budgetRequests as $budgetRequest) {
						array_push($to_approve, $budgetRequest);
					}
				}
			} else if (isset($post['requests_to_approve'])) {
				foreach ($post['requests_to_approve'] as $bid) {
					$budgetRequests = $em->createQuery('SELECT br FROM AcmePASBundle:BudgetRequest br WHERE br.bid = :bid and br.startdate >= :start and br.startdate <= :end and br.requestType = 2 and br.approved = 0')->setParameters(array('bid' => $bid, 'start' => $start, 'end' => $end))->getResult();
					foreach ($budgetRequests as $budgetRequest) {
						array_push($to_approve, $budgetRequest);
					}
				}
			}

			$sender = $em->getRepository('AcmePASBundle:User')->findOneByUid("0");
			$admin = $em->getRepository('AcmePASBundle:User')->findOneByRole("admin");
			$vtm = $em->getRepository('AcmePASBundle:User')->findOneByRole("vtm");

			// approve all selected requests and send notice email
			foreach ($to_approve as $unapproved) {
				$q = $em->createQuery("UPDATE AcmePASBundle:BudgetRequest br SET br.approved = 1 WHERE br.bid = :bid")->setParameters(array('bid' => $unapproved->getBid()))->execute();

				$user = $em->createQuery("select u from AcmePASBundle:User u, AcmePASBundle:BudgetRequest br where u.uid = br.holder and br.bid = :bid")->setParameters(array('bid' => $unapproved))->execute();
				$message = \Swift_Message::newInstance()
							->setSubject('BDA Expense Budget Approval Notice Email')
							->setFrom($sender->getEmail())
							->setTo($user[0]->getEmail())
							->setCc($vtm->getEmail())
							->setCc($admin->getEmail())
							->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $user[0], 'role' => 'requester', 'type' => 'BDA Expense Budget Approval', 'link' => $this->generateUrl('pas_budget_request_status', array('action' => 'query', 'id' => $unapproved->getBid()), true))), 'text/html');
				$this->get('mailer')->send($message);
			}

			// redirect to success page
			return $this->redirect($this->generateUrl('pas_success', array('form' => 'budget approval')));
		}

		return new Response($content);
	}
}