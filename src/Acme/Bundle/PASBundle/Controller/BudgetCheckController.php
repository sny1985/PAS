<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\BudgetRequest;
use Acme\Bundle\PASBundle\Entity\BudgetCategory;

class BudgetCheckController extends Controller
{
	public function budgetCheckAction(Request $req)
	{
		$budgetRequest = new BudgetRequest();
		$em = $this->getDoctrine()->getManager();

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

		$budgetRequests = $em->getRepository('AcmePASBundle:BudgetRequest')->findAll();
		$year_array = array();
		$budgets = array();
		$sum = 0;
		foreach ($budgetRequests as $request) {
			// show approved requests only
			if ($request->getApproved() != 1)
				continue;

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
			// calculate total amount of all categories yearly
			if (!isset($budgets[$year]["sum"])) {
				$budgets[$year]["sum"] = 0;
			}
			$budgets[$year]['sum'] += $request->getAmount() * $currency_array['rate'][$request->getCurtype()];
		}

		foreach ($year_array as $year) {
			foreach ($budgets[$year]['categories'] as $category) {
				$budgets[$year][$category]['amount'] = sprintf("%0.2f", $budgets[$year][$category]['amount']);
			}
			$budgets[$year]['sum'] = sprintf("%0.2f", $budgets[$year]['sum']);
		}

		return $this->render('AcmePASBundle:Default:budget-check.html.twig', array('categories' => $category_array, 'years' => $year_array, 'budgets' => $budgets));
	}
}