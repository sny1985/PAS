<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\BudgetRequest;

class BudgetRequestsListController extends Controller
{
	public function budgetRequestsListAction(Request $req)
	{
		$em = $this->getDoctrine()->getManager();
		$type = 2;
		$this->user = $this->getUser();
		$year = date('Y');

		// get currency type list from database
		$currencies = $em->getRepository('AcmePASBundle:CurrencyType')->findAll();
		foreach ($currencies as $key => $value) {
			$currency_array['name'][$key + 1] = $value->getName();
			$currency_array['code'][$key + 1] = $value->getCode();
		}

		// get user list from database
		$users = $em->getRepository('AcmePASBundle:User')->findAll();
		foreach ($users as $key => $value) {
			$user_array[$key] = $value->getUsername();
		}

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

			if ($this->user->getRole() == 'admin' || $this->user->getRole() == 'cfo' || $this->user->getRole() == 'vtm' || $this->user->getRole() == 'president' || $this->user->getRole() == 'secretary' || $this->user->getRole() == 'chair') {
				$budgetRequests = $em->createQuery('SELECT br FROM AcmePASBundle:BudgetRequest br WHERE br.startdate >= :start and br.startdate <= :end and br.requestType = :type')->setParameters(array('start' => $start, 'end' => $end, 'type' => $type))->getResult();
			} else {
				$budgetRequests = $em->createQuery('SELECT br FROM AcmePASBundle:BudgetRequest br WHERE br.startdate >= :start and br.startdate <= :end and br.holder = :holder and br.requestType = :type')->setParameters(array('start' => $start, 'end' => $end, 'holder' => $this->user->getUid(), 'type' => $type))->getResult();
			}
		}

		return $this->render('AcmePASBundle:Default:budget-requests-list.html.twig', array('currencies' => $currency_array, 'holders' => $user_array, 'requests' => $budgetRequests, 'type' => $type, 'year' => $year));
	}
}