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
		$action = null;
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
			} else if ($user->getRole() == "vtm") {
				$vtm = $user;
			}
		}

		// if the HTTP method is POST, handle form submission
		if ($req->isMethod('POST')) {
			// get id
			$param = $req->request->all();
			if (isset($param) && isset($param['id'])) {
				$id = $param['id'];
				$preRequest = $em->getRepository('AcmePASBundle:PreRequest')->findOneByPrid($id);
				if ($preRequest) {
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

			// get sender's email address
			$sender = $users[0]->getEmail();

			// send notice email to requester
			$message = \Swift_Message::newInstance()
						->setSubject('Pre-Payment Request Notice Email')
						->setFrom($sender)
						->setTo($requester->getEmail())
						->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $requester, 'role' => 'requester', 'type' => 'Pre-Payment Request', 'link' => $this->generateUrl('pas_pre_request_status', array('id' => $id, 'action' => 'query'), true))), 'text/html');
			$this->get('mailer')->send($message);

			// send notice email to vtm
			$message = \Swift_Message::newInstance()
						->setSubject('Pre-Payment Request Notice Email')
						->setFrom($sender)
						->setTo($vtm->getEmail())
						->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $vtm, 'role' => 'vtm', 'type' => 'Pre-Payment Request', 'link' => $this->generateUrl('pas_pre_approval_form', array('id' => $id), true))), 'text/html');
			$this->get('mailer')->send($message);

			// redirect to prevent resubmission
			return $this->redirect($this->generateUrl('pas_success', array('form' => 'pre request')));
		}

		// show result
		$param = $req->query->all();
		if (isset($param) && isset($param['id'])) {
			$id = $param['id'];
			if (isset($param['action']))
				$action = $param['action'];
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

		return $this->render('AcmePASBundle:Default:pre-request-query.html.twig', array('id' => $id, 'categories' => $category_array, 'currencies' => $currency_array, 'chair' => $selected_chair, 'secretary' => $secretary, 'cfo' => $cfo, 'president' => $president, 'requester' => $requester, 'role' => 'requester', 'request' => $preRequest, 'action' => $action, 'status' => $status));
	}
}