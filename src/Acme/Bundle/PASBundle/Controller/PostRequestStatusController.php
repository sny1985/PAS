<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\PostRequest;
use Acme\Bundle\PASBundle\Entity\User;

class PostRequestStatusController extends Controller
{
	public function postRequestReviewAction(Request $req)
	{
		$em = $this->getDoctrine()->getManager();
		$id = null;
		$postRequest = new PostRequest();
		$requester = null;
		$selected_chair = null;
		$status = null;
		$this->user = $this->getUser();

		// get category list from database
		$categories = $em->getRepository('AcmePASBundle:BudgetCategory')->findAll();
		$category_array = array();
		foreach ($categories as $key => $value) {
			$category_array[$key + 1] = $value->getName();
		}

		// get currency type list from database
		$currencies = $em->getRepository('AcmePASBundle:CurrencyType')->findAll();
		foreach ($currencies as $key => $value) {
			$currency_array['name'][$key + 1] = $value->getName();
			$currency_array['code'][$key + 1] = $value->getCode();
		}

		// get chairs, secretary, CFO & president from database
		$chairs = array();
		$users = $em->getRepository('AcmePASBundle:User')->findAll();
		foreach ($users as $user) {
			if ($user->getRole() == "chair") {
				array_push($chairs, $user);
			} else if ($user->getRole() == "cfo") {
				$cfo = $user;
			} else if ($user->getRole() == "president") {
				$president = $user;
			} else if ($user->getRole() == "secretary") {
				$secretary = $user;
			}
		}

		// if there is a query
		$param = $req->query->all();
		// approve
		if (isset($param) && isset($param['id'])) {
			$id = $param['id'];
			$postRequest = $em->getRepository('AcmePASBundle:PostRequest')->findOneByRid($id);
			if ($postRequest) {
				foreach ($users as $user) {
					if ($user->getUid() == $postRequest->getRequester()) {
						$requester = $user;
					}
					if ($user->getUid() == $postRequest->getChairId()) {
						$selected_chair = $user;
					}
				}
			}
		}

		// show result
		$param = $req->query->all();
		if (isset($param) && isset($param['id'])) {
			$id = $param['id'];
			$postRequest = $em->getRepository('AcmePASBundle:PostRequest')->findOneByRid($id);
			if ($postRequest) {
				$level = $postRequest->getLevel();
				if ($level == 1) {
					$status = $postRequest->getChairApproved();
				} else {
					$cApproved = $postRequest->getCfoApproved();
					$pApproved = $postRequest->getPresidentApproved();
					$sApproved = $postRequest->getSecretaryApproved();
					if ($cApproved == 2 || $pApproved == 2 || $sApproved == 2) {
						$status = 2;
					} else if ($cApproved == 1 && $pApproved == 1 && $sApproved == 1) {
						$status = 1;
					} else {
						$status = 0;
					}
				}

				foreach ($users as $user) {
					if ($user->getUid() == $postRequest->getRequester()) {
						$requester = $user;
					}
					if ($user->getUid() == $postRequest->getChairId()) {
						$selected_chair = $user;
					}
				}
			}
		}

		return $this->render('AcmePASBundle:Default:post-request-query.html.twig', array('id' => $id, 'categories' => $category_array, 'currencies' => $currency_array, 'chair' => $selected_chair, 'secretary' => $secretary, 'cfo' => $cfo, 'president' => $president, 'requester' => $requester, 'user' => $this->user, 'role' => 'requester', 'request' => $postRequest, 'action' => 'query', 'status' => $status));
	}
}