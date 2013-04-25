<?php
	ini_set('include_path', ini_get('include_path') . ':/usr/lib/php/pear');
	require_once('Services/JSON.php');

	$json = new Services_JSON();
	$usd_amount['usd'] = 0;

	if (isset($_GET['amount']) && isset($_GET['curtype'])) {
		$amount = htmlentities($_GET['amount']);
		$curtype = htmlentities($_GET['curtype']);
		$url = "http://www.google.com/ig/calculator?hl=en&q=" . $amount . $curtype . "=?USD";
		$result = $json->decode(file_get_contents($url));
		if ($result->icc == true) {
			$rs = explode(' ', $result->rhs);
			$usd_amount['usd'] = (double)$rs[0];
		}
	}

	header('Content-Type: application/json');

	print($json->encode($usd_amount));
?>