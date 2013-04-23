<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\PreRequest;
use Acme\Bundle\PASBundle\Entity\User;

class PreRequestStatusController extends Controller
{
	public function preRequestReviewAction(Request $req)
	{
		$em = $this->getDoctrine()->getManager();
		$id = null;
		$preRequest = new PreRequest();
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

		// show result
		$param = $req->query->all();
		if (isset($param) && isset($param['id'])) {
			$id = $param['id'];
			$preRequest = $em->getRepository('AcmePASBundle:PreRequest')->findOneByPrid($id);
			if ($preRequest) {
				$level = $preRequest->getLevel();
				if ($level == 1) {
					$status = $preRequest->getChairApproved();
				} else {
					$cApproved = $preRequest->getCfoApproved();
					$pApproved = $preRequest->getPresidentApproved();
					$sApproved = $preRequest->getSecretaryApproved();
					if ($cApproved == 2 || $pApproved == 2 || $sApproved == 2) {
						$status = 2;
					} else if ($cApproved == 1 && $pApproved == 1 && $sApproved == 1) {
						$status = 1;
					} else {
						$status = 0;
					}
				}

				foreach ($users as $user) {
					if ($user->getUid() == $preRequest->getRequester()) {
						$requester = $user;
					}
					if ($user->getUid() == $preRequest->getChairId()) {
						$selected_chair = $user;
					}
				}
			}
		}

		return $this->render('AcmePASBundle:Default:pre-request-query.html.twig', array('id' => $id, 'categories' => $category_array, 'currencies' => $currency_array, 'chair' => $selected_chair, 'secretary' => $secretary, 'cfo' => $cfo, 'president' => $president, 'requester' => $requester, 'user' => $this->user, 'role' => 'requester', 'request' => $preRequest, 'action' => 'query', 'status' => $status));
	}
}