<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\BudgetCategory;
use Acme\Bundle\PASBundle\Entity\BudgetRequest;
use Acme\Bundle\PASBundle\Entity\User;

// ??? DUE TO LACK OF MYSQL YEAR FUNCTION SUPPORT, MANY OPERATIONS HAVE TO BE DONE IN PHP CODE

class BudgetConfirmationController extends Controller
{
	public function budgetConfirmAction(Request $req)
	{
		$em = $this->getDoctrine()->getManager();

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

		// update approved field of unapproved records, and send emails to these just approved requesters
		if ($req->isMethod('POST')) {
			foreach ($unapproved_array as $unapproved) {
				$q = $em->createQuery("update AcmePASBundle:BudgetRequest br set br.approved = 1 where br.bid = $unapproved")->execute();

				$user = $em->createQuery("select u from AcmePASBundle:User u, AcmePASBundle:BudgetRequest br where u.uid = br.holder and br.bid = $unapproved")->execute();
				$message = \Swift_Message::newInstance()
							->setSubject('BDA Expense Budget Approval Notice Email')
							->setFrom('sny1985@gmail.com')
							->setTo( $user[0]->getEmail())
							->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $user[0], 'type' => 'BDA Expense Budget Approval', 'link' => $this->generateUrl('pas_budget_request_form', array('action' => 'query', 'id' => $unapproved), true))), 'text/html');
					// show submission result
					$this->get('mailer')->send($message);
			}
		}

		// if there is a query, show details
		$param = $req->query->all();
		if (isset($param) && isset($param['cid']) && isset($param['year'])) {
			$cid = $param['cid'];
			// find all requests with selected category in the selected year
			$budgetRequests = $em->getRepository('AcmePASBundle:BudgetRequest')->findByCategory($cid);
			$budgets = array();
			$sum = 0;
			foreach ($budgetRequests as $request) {
				if ($param['year'] == $request->getStartDate()->format('Y')) {
					$request->setAmount(sprintf("%0.2f", $request->getAmount() * $currency_array['rate'][$request->getCurtype()]));
					array_push($budgets, $request);
					$sum += $request->getAmount();
				}
			}

			return $this->render('AcmePASBundle:Default:budget-confirm-details.html.twig', array('cid' => $cid, 'categories' => $category_array, 'requests' => $budgets, 'sum' => sprintf("%0.2f", $sum)));
		}

		// if there is no query, show summary
		// grouped by year, category and date
		$budgetRequests = $em->getRepository('AcmePASBundle:BudgetRequest')->findAll();
		$year_array = array();
		$budgets = array();
		$sum = 0;
		$unapproved_array = array();
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
			// find the date of last request in each category
			if (!isset($budgets[$year][$category]['lastdate']) || ($budgets[$year][$category]['lastdate']->format('U') < $request->getDate()->format('U'))) {
				$budgets[$year][$category]['lastdate'] = $request->getDate();
			}
			// calculate total amount of each category
			if (!isset($budgets[$year][$category]['amount'])) {
				$budgets[$year][$category]['amount'] = 0;
			}
			$budgets[$year][$category]['amount'] += $request->getAmount() * $currency_array['rate'][$request->getCurtype()];
			// calculate total amount of all categories yearly
			if (!isset($budgets[$year]["sum"])) {
				$budgets[$year]["sum"] = 0;
			}
			$budgets[$year]['sum'] += $request->getAmount() * $currency_array['rate'][$request->getCurtype()];
			// find unapproved requests
			if (!$request->getApproved()) {
				array_push($unapproved_array, $request->getBid());
			}
		}

		foreach ($year_array as $year) {
			foreach ($budgets[$year]['categories'] as $category) {
				$budgets[$year][$category]['amount'] = sprintf("%0.2f", $budgets[$year][$category]['amount']);
			}
			$budgets[$year]['sum'] = sprintf("%0.2f", $budgets[$year]['sum']);
		}

		return $this->render('AcmePASBundle:Default:budget-confirm-summary.html.twig', array('categories' => $category_array, 'years' => $year_array, 'requests' => $budgets));
	}
}