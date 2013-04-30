<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class SuccessController extends Controller
{
	public function successAction(Request $req)
	{
		$form = null;

		$param = $req->query->all();
		if (isset($param) && isset($param['form'])) {
			$form = $param['form'];
		}

		return $this->render('AcmePASBundle:Default:success.html.twig', array('form' => $form));
	}
}