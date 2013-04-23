<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class HomeController extends Controller
{
	public function homeAction()
	{
		return $this->render('AcmePASBundle:Default:home.html.twig');
	}
}