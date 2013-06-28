<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\PreRequest;

class PreRequestsListController extends Controller
{
	public function preRequestsListAction(Request $req)
	{
		$em = $this->getDoctrine()->getManager();
		$type = 2;
		$this->user = $this->getUser();
		$year = date('Y');

		// get currency type list from database and get the rate to USD
		$currencies = $em->getRepository('AcmePASBundle:CurrencyType')->findAll();
		foreach ($currencies as $key => $value) {
			$currency_array['name'][$key + 1] = $value->getName();
			$currency_array['code'][$key + 1] = $value->getCode();
		}

		$param = $req->query->all();
		if (isset($param)) {
			if (isset($param['year'])) {
				$year = $param['year'];
			}

			// get the budget requests in specific year
			$start = new \DateTime($year . '-01-01');
			$end = new \DateTime($year . '-12-31');

			if ($this->user->getRole() == 'admin' || $this->user->getRole() == 'cfo' || $this->user->getRole() == 'vtm' || $this->user->getRole() == 'president' || $this->user->getRole() == 'secretary' || $this->user->getRole() == 'chair') {
				$preRequests = $em->createQuery('SELECT pr FROM AcmePASBundle:PreRequest pr WHERE pr.date >= :start and pr.date <= :end')->setParameters(array('start' => $start, 'end' => $end))->getResult();
			} else {
				$preRequests = $em->createQuery('SELECT pr FROM AcmePASBundle:PreRequest pr WHERE pr.date >= :start and pr.date <= :end and pr.prid = :requester')->setParameters(array('start' => $start, 'end' => $end, 'requester' => $this->user->getUid()))->getResult();
			}
		}

		return $this->render('AcmePASBundle:Default:pre-requests-list.html.twig', array('currencies' => $currency_array, 'requests' => $preRequests, 'year' => $year));
	}
}