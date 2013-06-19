<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\PostRequest;
use Acme\Bundle\PASBundle\Entity\PreRequest;
use Acme\Bundle\PASBundle\Entity\User;

class PaymentExportingController extends Controller
{
	public function exportAction(Request $req)
	{
		$em = $this->getDoctrine()->getManager();
		$id = null;

		// get currency type list from database and get the rate to USD
		$currencies = $em->getRepository('AcmePASBundle:CurrencyType')->findAll();
		foreach ($currencies as $key => $value) {
			$currency_array['name'][$key + 1] = $value->getName();
			$currency_array['code'][$key + 1] = $value->getCode();
			// get rate from google
			$currency_array['rate'][$key + 1] = 1;
			$url = "http://www.google.com/ig/calculator?hl=en&q=1" . $value->getCode() . "=?USD";
			$result = file_get_contents($url);
			$result = json_decode(preg_replace('/(\w+):/i', '"\1":', $result));
			if ($result->icc == true) {
				$rs = explode(' ', $result->rhs);
				$currency_array['rate'][$key + 1] = (double)$rs[0];
			}
		}

		$param = $req->query->all();
		if (isset($param) && isset($param['id'])) {
			$id = sprintf("%08d", intval($param['id']));
			$preRequest = $em->getRepository('AcmePASBundle:PreRequest')->findOneByPrid($id);
			$postRequests = $em->getRepository('AcmePASBundle:PostRequest')->findByPrid($id);
			$requester = $em->getRepository('AcmePASBundle:User')->findByUid($preRequest->getRequester());
			$requester = $requester[0]->getUsername();

			// create new PHPExcel object
			$excelObj = $this->get('xls.service_xls5')->excelObj;

			// set document properties
			$excelObj->getProperties()
						->setCreator("Blu-ray Disc Association")
						->setLastModifiedBy("Blu-ray Disc Association")
						->setTitle("Expense Budget Progress - $id")
						->setSubject("Expense Budget Progress")
						->setDescription("Expense Budget Progress of $id for $requester")
						->setKeywords("office 2005 openxml php")
						->setCategory("Expense Budget Progress");

			// add table header
			$excelObj->setActiveSheetIndex(0)
						->setCellValue("A1", "Requester $requester")
						->setCellValue("A3", "Pre-approval No.")
						->setCellValue("B3", "Abstract")
						->setCellValue("C3", "Amount (Requested Currency)")
						->setCellValue("D3", "Amount (USD)");

			$row = 4;

			// fill data
			$total = $preRequest->getAmount() * $currency_array['rate'][$preRequest->getCurtype()];
			$totalCompleted = 0;
			for ($i = 0, $num = count($postRequests); $i < $num; $i++, $row++) {
				if ($i == 0) $excelObj->getActiveSheet()->setCellValue("A$row", "#".$id); // add # before id to prevent number-conversion
				$excelObj->getActiveSheet()->setCellValue("B$row", $postRequests[$i]->getExplanation());
				$excelObj->getActiveSheet()->setCellValue("C$row", sprintf("%.2f", $postRequests[$i]->getAmount()));
				$excelObj->getActiveSheet()->setCellValue("D$row", sprintf("%.2f", $postRequests[$i]->getAmount() * $currency_array['rate'][$postRequests[$i]->getCurtype()]));
				$totalCompleted += $postRequests[$i]->getAmount();
			}
			$progress = $totalCompleted * 100.0 / $total;

			$excelObj->getActiveSheet()->setCellValue("A$row", "Total");
			$excelObj->getActiveSheet()->setCellValue("D$row", $totalCompleted);
			$row++;

			$excelObj->getActiveSheet()->setCellValue("A$row", "Budget of this item");
			$excelObj->getActiveSheet()->setCellValue("D$row", $total);
			$row++;

			$excelObj->getActiveSheet()->setCellValue("A$row", "Budget Progress");
			$excelObj->getActiveSheet()->setCellValue("D$row", sprintf("%.2f", $progress)."%");

			// rename worksheet
			$excelObj->getActiveSheet()->setTitle("Requester $requester");

			// set active sheet index to the first sheet, so Excel opens this as the first sheet
			$excelObj->setActiveSheetIndex(0);

			// create the response and redirect output to a client’s web browser (Excel5)
			$response = $this->get('xls.service_xls5')->getResponse();
			$response->headers->set('Content-Type', 'application/vnd.ms-excel; charset=utf-8');
			$response->headers->set("Content-Disposition", "attachment;filename=expense_budget_progress_" . $id ."_" . date('mdY') . ".xls");
			$response->headers->set("Cache-Control", "max-age=0");
			return $response;
		}

		return $this->render('AcmePASBundle:Default:failure.html.twig', array('id' => $id));
	}
}