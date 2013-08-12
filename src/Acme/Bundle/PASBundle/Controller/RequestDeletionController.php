<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\BudgetRequest;
use Acme\Bundle\PASBundle\Entity\PreRequest;
use Acme\Bundle\PASBundle\Entity\PostRequest;


class RequestDeletionController extends Controller
{
	public function deleteAction(Request $req)
	{
		$em = $this->getDoctrine()->getManager();
		$request = null;
		$request_id = 0;
		$request_type = "";

		$param = $req->query->all();
		if (isset($param)) {
			if (isset($param['req_type'])) {
				$request_type = $param['req_type'];
			}
			if (isset($param['id'])) {
				$request_id = $param['id'];
			}
		}

		if ($request_type == "budget") {
			$request = $em->getRepository('AcmePASBundle:BudgetRequest')->find($request_id);
		} else if ($request_type == "pre") {
			$request = $em->getRepository('AcmePASBundle:PreRequest')->find($request_id);
		} else if ($request_type == "post") {
			$request = $em->getRepository('AcmePASBundle:PostRequest')->find($request_id);
		}

		if ($request) {
			$em->remove($request);
			$em->flush();
		}

		return $this->redirect($this->generateUrl('pas_homepage'));
	}
}