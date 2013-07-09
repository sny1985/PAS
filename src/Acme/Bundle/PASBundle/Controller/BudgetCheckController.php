<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\BudgetRequest;
use Acme\Bundle\PASBundle\Entity\BudgetCategory;
use Acme\Bundle\PASBundle\Services\CurrencyConverter;

class BudgetCheckController extends Controller
{
	public function budgetCheckAction(Request $req)
	{
		$budgetRequest = new BudgetRequest();
		$cc = $this->get('currency_converter');
		$em = $this->getDoctrine()->getManager();
		$year = date('Y');

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
			$currency_array['rate'][$key + 1] = $cc->updateRate($value->getCode());
		}
		
		$param = $req->query->all();
		if (isset($param)) {
			if (isset($param['year'])) {
				$year = $param['year'];
			}

			$start = new \DateTime($year . '-01-01');
			$end = new \DateTime($year . '-12-31');
			$budgetRequests = $em->createQuery('SELECT br FROM AcmePASBundle:BudgetRequest br WHERE br.startdate >= :start and br.startdate <= :end and br.requestType = 2 and br.approved = 1')->setParameters(array('start' => $start, 'end' => $end))->getResult();

			$budgets = array();
			$budgets["sum"] = 0;
			$budgets["categories"] = array();
			foreach ($budgetRequests as $request) {
				// record categories
				$category = $request->getCategory();
				if (array_search($category, $budgets["categories"]) == false) {
					array_push($budgets["categories"], $category);
				}
				// calculate total amount of each category
				if (!isset($budgets[$category]['amount'])) {
					$budgets[$category]['amount'] = 0;
				}
				$multiply = $request->getAmount() * $currency_array['rate'][$request->getCurtype()];
				$budgets[$category]['amount'] += $multiply;
				// calculate total amount of all categories yearly
				if (!isset($budgets[$year]["sum"])) {
					$budgets[$year]["sum"] = 0;
				}
				$budgets['sum'] += $multiply;
			}
		}

		return $this->render('AcmePASBundle:Default:budget-check.html.twig', array('categories' => $category_array, 'budgets' => $budgets, 'year' => $year));
	}
}