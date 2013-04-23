<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\PostRequest;
use Acme\Bundle\PASBundle\Entity\PreRequest;
use Acme\Bundle\PASBundle\Entity\User;

class XLSExportingController extends Controller
{
	public function exportAction(Request $req)
	{
		$em = $this->getDoctrine()->getManager();
		$id = null;

		$param = $req->query->all();
		if (isset($param) && isset($param['id'])) {
			$id = $param['id'];
			$preRequest = $em->getRepository('AcmePASBundle:PreRequest')->findOneByPrid($id);
			$postRequests = $em->getRepository('AcmePASBundle:PostRequest')->findByPrid($id);
			$requester = $em->getRepository('AcmePASBundle:User')->findByUid($preRequest->getRequester())[0]->getUsername();

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
						->setCellValue("D3", "Amount (US$)");

			$row = 4;

			// fill data
			$total = $preRequest->getAmount();
			$totalCompleted = 0;
			$progress = $totalCompleted / ($total * 100.0);
			for ($i = 0, $num = count($postRequests); $i < $num; $i++, $row++) {
				if ($i == 0) $excelObj->getActiveSheet()->setCellValue("A$row", $id);
				$excelObj->getActiveSheet()->setCellValue("B$row", $postRequests[$i]->getAbstract());
				$excelObj->getActiveSheet()->setCellValue("C$row", $postRequests[$i]->getAmount());
				$excelObj->getActiveSheet()->setCellValue("D$row", $postRequests[$i]->getAmount());
				$totalCompleted += $postRequests[$i]->getAmount();
			}

			$excelObj->getActiveSheet()->setCellValue("A$row", "Total");
			$excelObj->getActiveSheet()->setCellValue("D$row", $totalCompleted);
			$row++;

			$excelObj->getActiveSheet()->setCellValue("A$row", "Budget of this item");
			$excelObj->getActiveSheet()->setCellValue("D$row", $total);
			$row++;

			$excelObj->getActiveSheet()->setCellValue("A$row", "Budget Progress");
			$excelObj->getActiveSheet()->setCellValue("D$row", $progress);

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

		return $this->render('AcmePASBundle:Default:xls.html.twig', array('id' => $id));
	}
}