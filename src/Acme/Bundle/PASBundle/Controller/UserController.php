<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\User;

class UserController extends Controller
{
	public function userAction(Request $req)
	{
		$em = $this->getDoctrine()->getManager();
		$factory = $this->get('security.encoder_factory');
		$user = new User();
		$encoder = $factory->getEncoder($user);

		$param = $req->query->all();
		if (isset($param) && isset($param['id'])) {
			$id = $param['id'];
			$user = $em->getRepository('AcmePASBundle:User')->findOneByUid($id);
		}

		$role_array = array('cfo' => 'cfo', 'president' => 'president', 'secretary' => 'secretary', 'chair' => 'chair', 'vtm' => 'vtm', 'requester' => 'requester', 'admin' => 'admin');

		// create form
		$form = $this->createFormBuilder($user)
						->add('uid', 'hidden')
						->add('username', 'text', array('label' => 'Username:'))
						->add('firstname', 'text', array('label' => 'First Name:'))
						->add('middlename', 'text', array('label' => 'Middle Name:', 'required' => false))
						->add('lastname', 'text', array('label' => 'Last Name:'))
						->add('title', 'text', array('label' => 'Title:', 'required' => false))
						->add('password', 'text', array('label' => 'Password:'))
						->add('email', 'email', array('label' => 'Email Address:'))
						->add('role', 'choice', array('choices' => $role_array, 'empty_value' => 'Choose one role', 'label' => 'Role:', 'preferred_choices' => array('empty_value')))
						->getForm();

		if ($req->isMethod('POST')) {
			$form->bind($req);
			// validate the data
			if ($form->isValid()) {
				// add salt to password
				$password = $encoder->encodePassword($user->getPassword(), $user->getSalt());
				$user->setPassword($password);

				$id = $user->getUid();
				// put into database
				if ($id) {
					// update database
					$oldUser = $em->getRepository('AcmePASBundle:User')->findOneByUid($id);
					$oldUser->setUsername($user->getUsername());
					$oldUser->setUsername($user->getFirstname());
					$oldUser->setUsername($user->getMiddlename());
					$oldUser->setUsername($user->getLastname());
					$oldUser->setUsername($user->getTitle());
					$oldUser->setSalt($user->getSalt());
					$oldUser->setPassword($user->getPassword());
					$oldUser->setEmail($user->getEmail());
					$oldUser->setRole($user->getRole());
					$oldUser->setIsActive($user->getIsActive());
					$em->flush();
				} else {
					// insert
					$em->persist($user);
					$em->flush();
				}

				return $this->redirect($this->generateUrl('pas_user'));
			}
		}

		return $this->render('AcmePASBundle:Default:user.html.twig', array('form' => $form->createView()));
	}
}