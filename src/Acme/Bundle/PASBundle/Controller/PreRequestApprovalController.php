<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Acme\Bundle\PASBundle\Entity\PreRequest;
use Acme\Bundle\PASBundle\Entity\User;

class PreRequestApprovalController extends Controller
{
	public function preApproveAction(Request $req)
	{
		$category = null;
		$currency = null;
		$em = $this->getDoctrine()->getManager();
		$id = 0;
		$preRequest = null;
		$requester = null;
		$selected_chair = null;
		$sendEmail = false;
		$status = null;
		$this->user = $this->getUser();

		// get secretary, CFO, president and VTM from database
		$sender = $em->getRepository('AcmePASBundle:User')->findOneByUid("0");
		$admin = $em->getRepository('AcmePASBundle:User')->findOneByRole("admin");
		$cfo = $em->getRepository('AcmePASBundle:User')->findOneByRole("cfo");
		$president = $em->getRepository('AcmePASBundle:User')->findOneByRole("president");
		$secretary = $em->getRepository('AcmePASBundle:User')->findOneByRole("secretary");
		$vtm = $em->getRepository('AcmePASBundle:User')->findOneByRole("vtm");

		// if there is a query
		$param = $req->query->all();
		// approve
		if (isset($param) && isset($param['id'])) {
			$id = $param['id'];
			$preRequest = $em->getRepository('AcmePASBundle:PreRequest')->findOneByPrid($id);
			if (count($preRequest) > 0) {
				// do not allow other people peek it
				$user_id = $this->user->getUid();
				$chair_id = $preRequest->getChairId();
				$cfo_id = $preRequest->getCfoId();
				$president_id = $preRequest->getPresidentId();
				$secretary_id = $preRequest->getSecretaryId();
				if ($user_id != $chair_id && $user_id != $cfo_id && $user_id != $president_id && $user_id != $secretary_id && $this->user->getRole() != "vtm") {
					throw new HttpException(403, 'You are not allowed to approve this request.');
				}

				// get category list from database
				$category = $em->getRepository('AcmePASBundle:BudgetCategory')->findOneByBcid($preRequest->getCategory());

				// get currency type list from database
				$currency = $em->getRepository('AcmePASBundle:CurrencyType')->findOneByCtid($preRequest->getCurtype());

				$requester = $em->getRepository('AcmePASBundle:User')->findOneByUid($preRequest->getRequester());
				$selected_chair = $em->getRepository('AcmePASBundle:User')->findOneByUid($preRequest->getChairId());
			}
		}

		// create form
		$form = $this->createFormBuilder()
						->add('approval', 'choice', array('choices' => array(1 => 'Approve', 2 => 'Pending'), 'expanded' => true, 'multiple' => false))
						->add('comment', 'textarea', array('label' => 'Comment:', 'required' => false))
						->getForm();

		// if the HTTP method is POST, handle form submission
		if ($req->isMethod('POST')) {
			if ($this->user->getRole() == "vtm") {
				// send notice email to approvers
				if ($preRequest->getChairId()) {
					$message = \Swift_Message::newInstance()
								->setSubject('Pre-Payment Request Notice Email')
								->setFrom($sender->getEmail())
								->setTo($selected_chair->getEmail())
								->setCc($admin->getEmail())
								->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $selected_chair, 'role' => 'chair', 'type' => 'Pre-Payment Request', 'link' => $this->generateUrl('pas_pre_approval_form', array('id' => $id), true))), 'text/html');
					$this->get('mailer')->send($message);
				}
				if ($preRequest->getCfoId()) {
					$message = \Swift_Message::newInstance()
								->setSubject('Pre-Payment Request Notice Email')
								->setFrom($sender->getEmail())
								->setTo($cfo->getEmail())
								->setCc($admin->getEmail())
								->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $cfo, 'role' => 'cfo', 'type' => 'Pre-Payment Request', 'link' => $this->generateUrl('pas_pre_approval_form', array('id' => $id), true))), 'text/html');
					$this->get('mailer')->send($message);
				}
				if ($preRequest->getPresidentId()) {
					$message = \Swift_Message::newInstance()
								->setSubject('Pre-Payment Request Notice Email')
								->setFrom($sender->getEmail())
								->setTo($president->getEmail())
								->setCc($admin->getEmail())
								->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $president, 'role' => 'president', 'type' => 'Pre-Payment Request', 'link' => $this->generateUrl('pas_pre_approval_form', array('id' => $id), true))), 'text/html');
					$this->get('mailer')->send($message);
				}
				if ($preRequest->getSecretaryId()) {
					$message = \Swift_Message::newInstance()
								->setSubject('Pre-Payment Request Notice Email')
								->setFrom($sender->getEmail())
								->setTo($secretary->getEmail())
								->setCc($admin->getEmail())
								->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $secretary, 'role' => 'secretary', 'type' => 'Pre-Payment Request', 'link' => $this->generateUrl('pas_pre_approval_form', array('id' => $id), true))), 'text/html');
					$this->get('mailer')->send($message);
				}
			} else {
				$form->bind($req);
				// validate the data and update database
				if ($form->isValid()) {
					$oldRequest = $em->getRepository('AcmePASBundle:PreRequest')->findOneByPrid($preRequest->getPrid());
					$data = $form->getData();
					switch ($this->user->getRole()) {
						case 'chair':
							if ($oldRequest->getChairId() == $this->user->getUid()) {
								$oldRequest->setChairApproved($data['approval']);
								$oldRequest->setChairComment($data['comment']);
								$sendEmail = true;
							}
							break;
						case 'cfo':
							if ($oldRequest->getCfoId() == $this->user->getUid()) {
								$oldRequest->setCfoApproved($data['approval']);
								$oldRequest->setCfoComment($data['comment']);
								$sendEmail = true;
							}
							break;
						case 'president':
							if ($oldRequest->getPresidentId() == $this->user->getUid()) {
								$oldRequest->setPresidentApproved($data['approval']);
								$oldRequest->setPresidentComment($data['comment']);
								$sendEmail = true;
							}
							break;
						case 'secretary':
							if ($oldRequest->getSecretaryId() == $this->user->getUid()) {
								$oldRequest->setSecretaryApproved($data['approval']);
								$oldRequest->setSecretaryComment($data['comment']);
								$sendEmail = true;
							}
							break;
					}
					$em->flush();

					if ($sendEmail) {
						// send notice email to requester
						$message = \Swift_Message::newInstance()
									->setSubject('Pre-Payment Approval Notice Email')
									->setFrom($sender->getEmail())
									->setTo($requester->getEmail())
									->setCc($vtm->getEmail())
									->setCc($admin->getEmail())
									->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $requester, 'role' => 'requester', 'type' => 'Pre-Payment Approval', 'link' => $this->generateUrl('pas_pre_request_status', array('id' => $id, 'action' => 'query'), true))), 'text/html');
						$this->get('mailer')->send($message);
					}
				}
			}

			// redirect to prevent resubmission
			return $this->redirect($this->generateUrl('pas_success', array('form' => 'pre approval')));
		}

		return $this->render('AcmePASBundle:Default:pre-request-query.html.twig', array('id' => $id, 'category' => $category, 'currency' => $currency, 'chair' => $selected_chair, 'secretary' => $secretary, 'cfo' => $cfo, 'president' => $president, 'requester' => $requester, 'role' => $this->user->getRole(), 'request' => $preRequest, 'action' => 'approve', 'status' => $status, 'form' => $form->createView()));
	}
}