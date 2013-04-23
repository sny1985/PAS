<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class UserController extends Controller
{
	public function userAction()
	{
		
	
		return $this->render('AcmePASBundle:Default:user.html.twig');
	}
}