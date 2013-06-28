<?php

namespace Acme\Bundle\PASBundle\Services;


class NoticeMailer
{
	protected $doctrine;

	public function __construct($doc)
	{
		$this->doctrine = $doc;
	}

	public function SendNoticeEmail($to, $cc, $subject, $content)
	{
		$em = $this->doctrine->getManager();
		$from = 
		$bcc = 
		
		$message = \Swift_Message::newInstance()
					->setSubject($subject)
					->setFrom($from)
					->setTo($to)
					->setCC($cc)
					->setBcc($bcc)
					->setBody($content);
					
		// show submission result
		$this->get('mailer')->send($message);
	}
}