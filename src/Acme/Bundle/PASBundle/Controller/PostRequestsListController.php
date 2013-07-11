<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\PostRequest;

class PostRequestsListController extends Controller
{
	public function postRequestsListAction(Request $req)
	{
		$em = $this->getDoctrine()->getManager();
		$postRequests = null;
		$this->user = $this->getUser();
		$year = date('Y');

		// get currency type list from database
		$currencies = $em->getRepository('AcmePASBundle:CurrencyType')->findAll();
		foreach ($currencies as $currency) {
			$currency_array['name'][$currency->getCtid()] = $currency->getName();
			$currency_array['code'][$currency->getCtid()] = $currency->getCode();
		}

		// get user list from database
		$users = $em->getRepository('AcmePASBundle:User')->findAll();
		foreach ($users as $user) {
			$user_array[$user->getUid()] = $user->getName();
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
				$postRequests = $em->createQuery('SELECT pr FROM AcmePASBundle:PostRequest pr WHERE pr.date >= :start and pr.date <= :end')->setParameters(array('start' => $start, 'end' => $end))->getResult();
			} else {
				$postRequests = $em->createQuery('SELECT pr FROM AcmePASBundle:PostRequest pr WHERE pr.date >= :start and pr.date <= :end and pr.requester = :requester')->setParameters(array('start' => $start, 'end' => $end, 'requester' => $this->user->getUid()))->getResult();
			}
		}

		return $this->render('AcmePASBundle:Default:post-requests-list.html.twig', array('currencies' => $currency_array, 'users' => $user_array, 'requests' => $postRequests, 'year' => $year));
	}
}