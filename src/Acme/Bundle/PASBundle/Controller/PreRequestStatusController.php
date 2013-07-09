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
		$category = null;
		$currency = null;
		$em = $this->getDoctrine()->getManager();
		$id = 0;
		$preRequest = null;
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
				$preRequest = $em->getRepository('AcmePASBundle:PreRequest')->findOneByPrid($id);
				if (count($preRequest) > 0) {
					$requester = $em->getRepository('AcmePASBundle:User')->findOneByUid($preRequest->getRequester());
					$selected_chair = $em->getRepository('AcmePASBundle:User')->findOneByUid($preRequest->getChairId());
				}
			}

			// send notice email to requester
			$message = \Swift_Message::newInstance()
						->setSubject('Pre-Payment Request Notice Email')
						->setFrom($sender->getEmail())
						->setTo($requester->getEmail())
						->setCc($admin->getEmail())
						->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $requester, 'role' => 'requester', 'type' => 'Pre-Payment Request', 'link' => $this->generateUrl('pas_pre_request_status', array('id' => $id, 'action' => 'query'), true))), 'text/html');
			$this->get('mailer')->send($message);

			// send notice email to vtm
			$message = \Swift_Message::newInstance()
						->setSubject('Pre-Payment Request Notice Email')
						->setFrom($sender->getEmail())
						->setTo($vtm->getEmail())
						->setCc($admin->getEmail())
						->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $vtm, 'role' => 'vtm', 'type' => 'Pre-Payment Request', 'link' => $this->generateUrl('pas_pre_approval_form', array('id' => $id), true))), 'text/html');
			$this->get('mailer')->send($message);

			// redirect to prevent resubmission
			return $this->redirect($this->generateUrl('pas_success', array('form' => 'pre request')));
		}

		// show result
		$param = $req->query->all();
		if (isset($param) && isset($param['id'])) {
			$id = $param['id'];
			if (isset($param['action'])) {
				$action = $param['action'];
			}

			$preRequest = $em->getRepository('AcmePASBundle:PreRequest')->findOneByPrid($id);
			if (count($preRequest) > 0) {
				// get category list from database
				$category = $em->getRepository('AcmePASBundle:BudgetCategory')->findOneByBcid($preRequest->getCategory());

				// get currency type list from database
				$currency = $em->getRepository('AcmePASBundle:CurrencyType')->findOneByCtid($preRequest->getCurtype());

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

				$requester = $em->getRepository('AcmePASBundle:User')->findOneByUid($preRequest->getRequester());
				$selected_chair = $em->getRepository('AcmePASBundle:User')->findOneByUid($preRequest->getChairId());
			}
		}

		return $this->render('AcmePASBundle:Default:pre-request-query.html.twig', array('id' => $id, 'category' => $category, 'currency' => $currency, 'chair' => $selected_chair, 'secretary' => $secretary, 'cfo' => $cfo, 'president' => $president, 'requester' => $requester, 'role' => 'requester', 'request' => $preRequest, 'action' => $action, 'status' => $status));
	}
}