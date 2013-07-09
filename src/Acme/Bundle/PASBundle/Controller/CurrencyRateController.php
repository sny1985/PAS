<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Acme\Bundle\PASBundle\Services\CurrencyConverter;

class CurrencyRateController extends Controller
{
	public function getRateAction(Request $req)
	{
		$amount = 1.0;
		$cc = $this->get('currency_converter');
		$curtype = 'USD';

		$param = $req->query->all();
		if (isset($param) && isset($param['amount']) && isset($param['curtype'])) {
			$amount = $param['amount'];
			$curtype = $param['curtype'];
		}

		return new JsonResponse(array('amount' => $cc->updateRate($curtype) * $amount));
	}
}