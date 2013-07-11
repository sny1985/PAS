<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\PostRequest;
use Acme\Bundle\PASBundle\Entity\User;
use Acme\Bundle\PASBundle\Services\CurrencyConverter;

class PostRequestStatusController extends Controller
{
	public function postRequestReviewAction(Request $req)
	{
		$action = null;
		$category = null;
		$cc = $this->get('currency_converter');
		$currency = null;
		$em = $this->getDoctrine()->getManager();
		$id = 0;
		$postRequest = null;
		$requester = null;
		$role = "requester";
		$selected_chair = null;
		$status = null;
		$this->user = $this->getUser();

		// get secretary, CFO, president and VTM from database
		$sender = $em->getRepository('AcmePASBundle:User')->findOneByUid("0");
		$admin = $em->getRepository('AcmePASBundle:User')->findOneByRole("admin");
		$cfo = $em->getRepository('AcmePASBundle:User')->findOneByRole("cfo");
		$president = $em->getRepository('AcmePASBundle:User')->findOneByRole("president");
		$secretary = $em->getRepository('AcmePASBundle:User')->findOneByRole("secretary");
		$vtm = $em->getRepository('AcmePASBundle:User')->findOneByRole("vtm");

		// if the HTTP method is POST, handle form submission
		if ($req->isMethod('POST')) {
			// get id
			$param = $req->request->all();
			if (isset($param) && isset($param['id'])) {
				$id = $param['id'];
				$postRequest = $em->getRepository('AcmePASBundle:PostRequest')->findOneByRid($id);
				if (count($postRequest) > 0) {
					$requester = $em->getRepository('AcmePASBundle:User')->findOneByUid($postRequest->getRequester());
					$selected_chair = $em->getRepository('AcmePASBundle:User')->findOneByUid($postRequest->getChairId());

					if (isset($param['actual_amount'])) {
						$postRequest->setActualAmount($param['actual_amount']);
						$em->flush();

						// send notice email to requester
						$message = \Swift_Message::newInstance()
									->setSubject('Payment Approval Notice Email')
									->setFrom($sender->getEmail())
									->setTo($requester->getEmail())
									->setCc($admin->getEmail())
									->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $requester, 'role' => 'requester', 'type' => 'Payment Approval', 'link' => $this->generateUrl('pas_post_request_status', array('id' => $id, 'action' => 'query'), true))), 'text/html');
						$this->get('mailer')->send($message);

						// redirect to prevent resubmission
						return $this->redirect($this->generateUrl('pas_success', array('form' => 'post approval')));
					}
				}
			}

			// send notice email to requester
			$message = \Swift_Message::newInstance()
						->setSubject('Payment Request Notice Email')
						->setFrom($sender->getEmail())
						->setTo($requester->getEmail())
						->setCc($admin->getEmail())
						->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $requester, 'role' => 'requester', 'type' => 'Payment Request', 'link' => $this->generateUrl('pas_post_request_status', array('id' => $id, 'action' => 'query'), true))), 'text/html');
			$this->get('mailer')->send($message);

			// send notice email to vtm
			$message = \Swift_Message::newInstance()
						->setSubject('Payment Request Notice Email')
						->setFrom($sender->getEmail())
						->setTo($vtm->getEmail())
						->setCc($admin->getEmail())
						->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $vtm, 'role' => 'vtm', 'type' => 'Payment Request', 'link' => $this->generateUrl('pas_post_approval_form', array('id' => $id), true))), 'text/html');
			$this->get('mailer')->send($message);

			// redirect to prevent resubmission
			return $this->redirect($this->generateUrl('pas_success', array('form' => 'post request')));
		}

		// show results
		$param = $req->query->all();
		if (isset($param) && isset($param['id'])) {
			$id = $param['id'];
			if (isset($param['action'])) {
				$action = $param['action'];
			}
		
			$postRequest = $em->getRepository('AcmePASBundle:PostRequest')->findOneByRid($id);
			if (count($postRequest) > 0) {
				// get category list from database
				$category = $em->getRepository('AcmePASBundle:BudgetCategory')->findOneByBcid($postRequest->getCategory());

				// get currency type list from database
				$currency = $em->getRepository('AcmePASBundle:CurrencyType')->findOneByCtid($postRequest->getCurtype());
				$currency->setRate($cc->updateRate($currency->getCode()));

				$cApproved = $postRequest->getCfoApproved();
				$level = $postRequest->getLevel();
				if ($level == 1) {
					$chApproved = $postRequest->getChairApproved();
					if ($cApproved == 2 || $chApproved == 2) {
						$status = 2;
					} else if ($cApproved == 1 && $chApproved == 1) {
						$status = 1;
					} else {
						$status = 0;
					}
				} else if ($level == 2) {
					$pApproved = $postRequest->getPresidentApproved();
					$sApproved = $postRequest->getSecretaryApproved();
					if ($cApproved == 2 || $pApproved == 2 || $sApproved == 2) {
						$status = 2;
					} else if ($cApproved == 1 && $pApproved == 1 && $sApproved == 1) {
						$status = 1;
					} else {
						$status = 0;
					}
				} else {
					$status = $cApproved;
				}
		
				$requester = $em->getRepository('AcmePASBundle:User')->findOneByUid($postRequest->getRequester());
				$selected_chair = $em->getRepository('AcmePASBundle:User')->findOneByUid($postRequest->getChairId());
			}
		}

		if ($this->user->getRole() == "vtm")
			$role = "vtm";

		return $this->render('AcmePASBundle:Default:post-request-query.html.twig', array('id' => $id, 'category' => $category, 'currency' => $currency, 'chair' => $selected_chair, 'secretary' => $secretary, 'cfo' => $cfo, 'president' => $president, 'requester' => $requester, 'role' => $role, 'request' => $postRequest, 'action' => $action, 'status' => $status));
	}
}