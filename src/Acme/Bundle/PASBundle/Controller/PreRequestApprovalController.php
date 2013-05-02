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
		$em = $this->getDoctrine()->getManager();
		$id = null;
		$sendEmail = false;
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

		// if there is a query
		$param = $req->query->all();
		// approve
		if (isset($param) && isset($param['id'])) {
			$id = $param['id'];
			$preRequest = $em->getRepository('AcmePASBundle:PreRequest')->findOneByPrid($id);
			if ($preRequest) {
				// do not allow other people peek it
				$user_id = $this->user->getUid();
				$chair_id = $preRequest->getChairId();
				$cfo_id = $preRequest->getCfoId();
				$president_id = $preRequest->getPresidentId();
				$secretary_id = $preRequest->getSecretaryId();
				if ($user_id != $chair_id && $user_id != $cfo_id && $user_id != $president_id && $user_id != $secretary_id) {
					throw new HttpException(403, 'You are not allowed to approve this request.');
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

		// create form
		$form = $this->createFormBuilder()
						->add('approval', 'choice', array('choices' => array(1 => 'Approve', 2 => 'Pending'), 'expanded' => true, 'multiple' => false))
						->add('comment', 'textarea', array('label' => 'Comment:', 'required' => false))
						->getForm();

		// if the HTTP method is POST, handle form submission
		if ($req->isMethod('POST')) {
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
					// get sender's email address
					$sender = $users[0]->getEmail();

					// send notice email to requester
					$message = \Swift_Message::newInstance()
								->setSubject('Pre-Payment Approval Notice Email')
								->setFrom($sender)
								->setTo($requester->getEmail())
								->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $requester, 'role' => 'requester', 'type' => 'Pre-Payment Approval', 'link' => $this->generateUrl('pas_pre_request_status', array('id' => $id, 'action' => 'query'), true))), 'text/html');
					$this->get('mailer')->send($message);
				}

				// redirect to prevent resubmission
				return $this->redirect($this->generateUrl('pas_success', array('form' => 'pre approval')));
			}
		}

		return $this->render('AcmePASBundle:Default:pre-request-query.html.twig', array('id' => $id, 'categories' => $category_array, 'currencies' => $currency_array, 'chair' => $selected_chair, 'secretary' => $secretary, 'cfo' => $cfo, 'president' => $president, 'requester' => $requester, 'role' => 'approver', 'request' => $preRequest, 'action' => 'approve', 'status' => $status, 'form' => $form->createView()));
	}
}