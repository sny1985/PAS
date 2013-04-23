<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class NoticeEmailController extends Controller
{
    public function noticeAction()
    {
    	$receiver = "";
    	$type = "BDA Expense Budget"; // "Pre-payment request";
    	$status = "";
    	$link = $this->generateUrl('pas_budget_confirmation_form', array('id' => 1), true);
    
        return $this->render('AcmePASBundle:Default:notice.html.twig', array('receiver' => $receiver, 'type' => $type, 'status' => $status, 'link' => $link));
    }
}