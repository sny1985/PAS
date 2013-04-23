<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\PostRequest;
use Acme\Bundle\PASBundle\Entity\User;

class PostRequestApprovalController extends Controller
{
	public function postApproveAction(Request $req)
	{
		$em = $this->getDoctrine()->getManager();
		$form = null;
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
				$oldRequest = $em->getRepository('AcmePASBundle:PostRequest')->findOneByRid($postRequest->getRid());
				$data = $form->getData();
				switch ($this->user->getRole()) {
					case 'chair':
						$oldRequest->setChairApproved($data['approval']);
						$oldRequest->setChairComment($data['comment']);
						break;
					case 'cfo':
						$oldRequest->setCfoApproved($data['approval']);
						$oldRequest->setCfoComment($data['comment']);
						break;
					case 'president':
						$oldRequest->setPresidentApproved($data['approval']);
						$oldRequest->setPresidentComment($data['comment']);
						break;
					case 'secretary':
						$oldRequest->setSecretaryApproved($data['approval']);
						$oldRequest->setSecretaryComment($data['comment']);
						break;
				}
				$em->flush();

				// send notice email to requester
				$message = \Swift_Message::newInstance()
							->setSubject('Payment Approval Notice Email')
							->setFrom('sny1985@gmail.com')
							->setTo($this->user->getEmail())
							->setBody($this->renderView('AcmePASBundle:Default:notice.html.twig', array('receiver' => $requester, 'type' => 'Payment Approval', 'link' => $this->generateUrl('pas_post_request_status', array('id' => $id, 'action' => 'query'), true))), 'text/html');
				$this->get('mailer')->send($message);

				// redirect to prevent resubmission
				return $this->redirect($this->generateUrl('pas_post_request_status', array('id' => $id)));
			} else {
				// HANDLE EXCEPTIONS ???
			}
		}

		return $this->render('AcmePASBundle:Default:post-request-query.html.twig', array('id' => $id, 'categories' => $category_array, 'currencies' => $currency_array, 'chair' => $selected_chair, 'secretary' => $secretary, 'cfo' => $cfo, 'president' => $president, 'requester' => $requester, 'user' => $this->user, 'role' => 'approver', 'request' => $postRequest, 'action' => 'approve', 'status' => $status, 'form' => $form->createView()));
	}
}