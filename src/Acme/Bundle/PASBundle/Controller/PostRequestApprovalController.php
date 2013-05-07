<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Acme\Bundle\PASBundle\Entity\PostRequest;
use Acme\Bundle\PASBundle\Entity\User;

class PostRequestApprovalController extends Controller
{
	public function postApproveAction(Request $req)
	{
		$em = $this->getDoctrine()->getManager();
		$id = null;
		$sendEmail = false;
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
			} else if ($user->getRole() == "vtm") {
				$vtm = $user;
			}
		}

		// if there is a query
		$param = $req->query->all();
		// approve
		if (isset($param) && isset($param['id'])) {
			$id = $param['id'];
			$postRequest = $em->getRepository('AcmePASBundle:PostRequest')->findOneByRid($id);
			if ($postRequest) {
				// do not allow other people peek it
				$user_id = $this->user->getUid();
				$chair_id = $postRequest->getChairId();
				$cfo_id = $postRequest->getCfoId();
				$president_id = $postRequest->getPresidentId();
				$secretary_id = $postRequest->getSecretaryId();
				if ($user_id != $chair_id && $user_id != $cfo_id && $user_id != $president_id && $user_id != $secretary_id && $this->user->getRole() != "vtm") {
					throw new HttpException(403, 'You are not allowed to approve this request.');
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

		// create form
		$form = $this->createFormBuilder()
						->add('approval', 'choice', array('choices' => array(1 => 'Approve', 2 => 'Pending'), 'expanded' => true, 'multiple' => false))
						->add('comment', 'textarea', array('label' => 'Comment:', 'required' => false))
						->getForm();

		// if the HTTP method is POST, handle form submission
		if ($req->isMethod('POST')) {
			// get sender's email address
			$sender = $users[0]->getEmail();

			if ($this->user->getRole() == "vtm") {
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
			} else {
				$form->bind($req);
				// validate the data and update database
				if ($form->isValid()) {
					$oldRequest = $em->getRepository('AcmePASBundle:PostRequest')->findOneByRid($postRequest->getRid());
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
						// send notice email to vtm
						$message = \Swift_Message::newInstance()
									->setSubject('Payment Approval Notice Email')
									->setFrom($sender)
									->setTo($vtm->getEmail())
									->setCc($vtm->getEmail())
									->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $requester, 'role' => 'requester', 'type' => 'Payment Approval', 'link' => $this->generateUrl('pas_post_request_status', array('id' => $id, 'action' => 'query'), true))), 'text/html');
						$this->get('mailer')->send($message);
					}
				}
			}

			// redirect to prevent resubmission
			return $this->redirect($this->generateUrl('pas_success', array('form' => 'post approval')));
		}

		return $this->render('AcmePASBundle:Default:post-request-query.html.twig', array('id' => $id, 'categories' => $category_array, 'currencies' => $currency_array, 'chair' => $selected_chair, 'secretary' => $secretary, 'cfo' => $cfo, 'president' => $president, 'requester' => $requester, 'role' => $this->user->getRole(), 'request' => $postRequest, 'action' => 'approve', 'status' => $status, 'form' => $form->createView()));
	}
}