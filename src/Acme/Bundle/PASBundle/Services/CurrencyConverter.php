<?php

namespace Acme\Bundle\PASBundle\Services;

use Symfony\Component\HttpFoundation\JsonResponse;
use Acme\Bundle\PASBundle\Entity\CurrencyType;

class CurrencyConverter
{
	protected $doctrine;

	public function __construct($doc)
	{
		$this->doctrine = $doc;
	}

	public function updateRate($curtype)
	{
		$em = $this->doctrine->getManager();
		$rate = 1.0;
		$interval = 60 * 10; // 60 * 10 seconds, i.e. 10 minutes

		if (isset($curtype)) {
			$currency = $em->getRepository('AcmePASBundle:CurrencyType')->findOneByCode($curtype);

			// if the data is outdated, fetch new one and update database
			$lastUpdated = $currency->getLastUpdated();
			$now = new \DateTime();
			$seconds = $now->getTimestamp() - $lastUpdated->getTimestamp();
			if ($seconds > $interval) {
				$url = "http://www.google.com/ig/calculator?hl=en&q=1.00" . $curtype . "=?USD";
				$result = file_get_contents($url);
				$result = json_decode(preg_replace('/(\w+):/i', '"\1":', $result));
				if ($result->icc == true) {
					$rs = explode(' ', $result->rhs);
					$rate = (float)$rs[0];
				}

				$currency->setRate($rate);
				$currency->setLastUpdated(new \DateTime());
				$em->flush();
			} else {
				$rate = $currency->getRate();
			}
		}

		return new JsonResponse(array('rate' => $rate));
	}
}