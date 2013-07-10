<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\PostRequest;
use Acme\Bundle\PASBundle\Entity\PreRequest;
use Acme\Bundle\PASBundle\Entity\User;
use Acme\Bundle\PASBundle\Services\CurrencyConverter;

class ProgressExportingController extends Controller
{
	public function exportAction(Request $req)
	{
		$cc = $this->get('currency_converter');
		$em = $this->getDoctrine()->getManager();
		$id = null;

		// get currency type list from database and get the rate to USD
		$currencies = $em->getRepository('AcmePASBundle:CurrencyType')->findAll();
		foreach ($currencies as $key => $value) {
			$currency_array['name'][$key + 1] = $value->getName();
			$currency_array['code'][$key + 1] = $value->getCode();
			$currency_array['rate'][$key + 1] = $cc->updateRate($value->getCode());
		}

		$param = $req->query->all();
		if (isset($param) && isset($param['id'])) {
			$id = $param['id'];

			$postRequest = $em->getRepository('AcmePASBundle:PostRequest')->findOneByRid($id);
			$requester = $em->getRepository('AcmePASBundle:User')->findOneByUid($postRequest->getRequester())->getUsername();

			$prid = $postRequest->getPrid();
			if ($prid != null) {
				$prid = sprintf("%08d", intval($prid));
				$preRequest = $em->getRepository('AcmePASBundle:PreRequest')->findOneByPrid($prid);
				$postRequests = $em->getRepository('AcmePASBundle:PostRequest')->findByPrid($prid);
			} else {
				$preRequest = $postRequest;
				$postRequests = array($postRequest);
			}

			$title = "Expense Budget Progress Report - pre-payment #" . $prid;

			// create new PHPExcel object
			$excelObj = $this->get('xls.service_xls5')->excelObj;

			// set document properties
			$excelObj->getProperties()
						->setCreator("Blu-ray Disc Association")
						->setLastModifiedBy("Blu-ray Disc Association")
						->setTitle($title)
						->setSubject($title)
						->setDescription($title)
						->setCategory("Report");

			// add table header
			$excelObj->setActiveSheetIndex(0)
						->setCellValue("A1", "Requester $requester")
						->setCellValue("A3", "Pre-approval No.")
						->setCellValue("B3", "Explanation")
						->setCellValue("C3", "Amount (Requested Currency)")
						->setCellValue("D3", "Amount (USD)");

			$row = 4;

			// fill data
			$total = sprintf("%.2f", $preRequest->getAmount());
			$totalActual = sprintf("%.2f",$preRequest->getAmount() * $currency_array['rate'][$preRequest->getCurtype()]);
			$totalCompleted = 0;
			$totalCompletedActual = 0;
			for ($i = 0, $num = count($postRequests); $i < $num; $i++, $row++) {
				if ($i == 0) $excelObj->getActiveSheet()->setCellValue("A$row", "#".sprintf("%08d", intval($postRequests[$i]->getRid()))); // add # before id to prevent number-conversion
				$excelObj->getActiveSheet()->setCellValue("B$row", $postRequests[$i]->getExplanation());
				$excelObj->getActiveSheet()->setCellValue("C$row", $postRequests[$i]->getAmount() . " " . $currency_array['code'][$postRequests[$i]->getCurtype()]);
				$actual = sprintf("%.2f", $postRequests[$i]->getActualAmount());
				$excelObj->getActiveSheet()->setCellValue("D$row", $actual);
				$totalCompletedActual += $actual;
			}

			$progress = $totalCompletedActual * 100.0 / $totalActual;

			$excelObj->getActiveSheet()->setCellValue("A$row", "Total");
			$excelObj->getActiveSheet()->setCellValue("D$row", $totalCompletedActual);
			$row++;

			$excelObj->getActiveSheet()->setCellValue("A$row", "Budget of this item");
			$excelObj->getActiveSheet()->setCellValue("C$row", $total . " " . $currency_array['code'][$preRequest->getCurtype()]);
			$excelObj->getActiveSheet()->setCellValue("D$row", $totalActual);
			$row++;

			$excelObj->getActiveSheet()->setCellValue("A$row", "Budget Progress");
			$excelObj->getActiveSheet()->setCellValue("D$row", sprintf("%.2f", $progress)."%");

			// rename worksheet
			$excelObj->getActiveSheet()->setTitle("Requester $requester");

			// set active sheet index to the first sheet, so Excel opens this as the first sheet
			$excelObj->setActiveSheetIndex(0);

			$title = str_replace(' ', '_', $title);
			$filename = $title . "_" . date('mdY');

			// create the response and redirect output to a client’s web browser (Excel5)
			$response = $this->get('xls.service_xls5')->getResponse();
			$response->headers->set('Content-Type', 'application/vnd.ms-excel; charset=utf-8');
			$response->headers->set("Content-Disposition", "attachment;filename=" . $filename . ".xls");
			$response->headers->set("Cache-Control", "max-age=0");
			return $response;
		}

		return $this->render('AcmePASBundle:Default:failure.html.twig', array('id' => $id));
	}
}