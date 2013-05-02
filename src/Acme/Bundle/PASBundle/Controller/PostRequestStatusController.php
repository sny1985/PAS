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

		// show results
		$param = $req->query->all();
		if (isset($param) && isset($param['id'])) {
			$id = $param['id'];
			if (isset($param['action']))
				$action = $param['action'];
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

		// if the HTTP method is POST, handle form submission
		if ($req->isMethod('POST')) {
			// get id
			$param = $req->request->all();
			if (isset($param) && isset($param['id'])) {
				$id = $param['id'];
				$postRequest = $em->getRepository('AcmePASBundle:PostRequest')->findOneByRid($id);
			}

			foreach ($users as $user) {
				if ($user->getUid() == $postRequest->getRequester()) {
					$requester = $user;
				}
				if ($user->getUid() == $postRequest->getChairId()) {
					$selected_chair = $user;
				}
			}

			// get sender's email address
			$sender = $users[0]->getEmail();

			// send notice email to requester
			$message = \Swift_Message::newInstance()
						->setSubject('Payment Request Notice Email')
						->setFrom($sender)
						->setTo($requester->getEmail())
						->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $this->user, 'role' => 'requester', 'type' => 'Payment Request', 'link' => $this->generateUrl('pas_post_request_status', array('id' => $id, 'action' => 'query'), true))), 'text/html');
			$this->get('mailer')->send($message);

			// send notice email to approvers
			if ($postRequest->getChairId()) {
				$message = \Swift_Message::newInstance()
							->setSubject('Payment Request Notice Email')
							->setFrom($sender)
							->setTo($selected_chair->getEmail())
							->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $selected_chair, 'role' => 'chair', 'type' => 'Payment Request', 'link' => $this->generateUrl('pas_post_approval_form', array('id' => $id), true))), 'text/html');
				$this->get('mailer')->send($message);
			}
			if ($postRequest->getCfoId()) {
				$message = \Swift_Message::newInstance()
							->setSubject('Payment Request Notice Email')
							->setFrom($sender)
							->setTo($cfo->getEmail())
							->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $cfo, 'role' => 'cfo', 'type' => 'Payment Request', 'link' => $this->generateUrl('pas_post_approval_form', array('id' => $id), true))), 'text/html');
				$this->get('mailer')->send($message);
			}
			if ($postRequest->getPresidentId()) {
				$message = \Swift_Message::newInstance()
							->setSubject('Payment Request Notice Email')
							->setFrom($sender)
							->setTo($president->getEmail())
							->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $president, 'role' => 'president', 'type' => 'Payment Request', 'link' => $this->generateUrl('pas_post_approval_form', array('id' => $id), true))), 'text/html');
				$this->get('mailer')->send($message);
			}
			if ($postRequest->getSecretaryId()) {
				$message = \Swift_Message::newInstance()
							->setSubject('Payment Request Notice Email')
							->setFrom($sender)
							->setTo($secretary->getEmail())
							->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $secretary, 'role' => 'secretary', 'type' => 'Payment Request', 'link' => $this->generateUrl('pas_post_approval_form', array('id' => $id), true))), 'text/html');
				$this->get('mailer')->send($message);
			}

			// redirect to prevent resubmission
			return $this->redirect($this->generateUrl('pas_success', array('form' => 'post request')));
		}

		return $this->render('AcmePASBundle:Default:post-request-query.html.twig', array('id' => $id, 'categories' => $category_array, 'currencies' => $currency_array, 'chair' => $selected_chair, 'secretary' => $secretary, 'cfo' => $cfo, 'president' => $president, 'requester' => $requester, 'role' => 'requester', 'request' => $postRequest, 'action' => $action, 'status' => $status));
	}
}