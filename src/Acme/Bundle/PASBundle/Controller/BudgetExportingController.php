<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\BudgetRequest;
use Acme\Bundle\PASBundle\Entity\User;
use Acme\Bundle\PASBundle\Services\CurrencyConverter;

class BudgetExportingController extends Controller
{
	public function exportAction(Request $req)
	{
		$cc = $this->get('currency_converter');
		$cid = 0;
		$em = $this->getDoctrine()->getManager();
		$type = 2;
		$year = date('Y');

		// get user list from database
		$users = $em->getRepository('AcmePASBundle:User')->findAll();
		$user_array = array();
		foreach ($users as $key => $value) {
			$user_array[$key + 1] = $value->getUserName();
		}

		// get category list from database
		$categories = $em->getRepository('AcmePASBundle:BudgetCategory')->findAll();
		$category_array = array();
		foreach ($categories as $key => $value) {
			$category_array[$key + 1] = $value->getName();
		}

		// get currency list from database and get the rate to USD
		$currencies = $em->getRepository('AcmePASBundle:CurrencyType')->findAll();
		foreach ($currencies as $key => $value) {
			$currency_array['name'][$key + 1] = $value->getName();
			$currency_array['code'][$key + 1] = $value->getCode();
			$cc->updateRate($value->getCode());
			$currency_array['rate'][$key + 1] = $value->getRate();
		}

		$param = $req->query->all();
		if (isset($param)) {
			if (isset($param['type'])) {
				$type = $param['type'];
			}

			if (isset($param['year'])) {
				$year = $param['year'];
			}

			if (isset($param['cid'])) {
				$cid = $param['cid'];
			}
		}

		// get the budget requests in specific year
		$start = new \DateTime($year . '-01-01');
		$end = new \DateTime($year . '-12-31');
		$budgetRequests = $em->createQuery('SELECT br FROM AcmePASBundle:BudgetRequest br WHERE br.startdate >= :start and br.startdate <= :end and br.requestType = :type')->setParameters(array('start' => $start, 'end' => $end, 'type' => $type))->getResult();

		$title = "FY " . $year .  ($type == 1 ? " Estimation" : " Budget Request") . " Report - " . ($cid == 0 ? "summary" : $category_array[$cid] . " details");

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
					->setCellValue("A1", "Budget Id")
					->setCellValue("B1", "Holder")
					->setCellValue("C1", "Category")
					->setCellValue("D1", "Starting Date")
					->setCellValue("E1", "Ending Date")
					->setCellValue("F1", "Abstract")
					->setCellValue("G1", "Details")
					->setCellValue("H1", "Amount")
					->setCellValue("I1", "Currency Type")
					->setCellValue("J1", "Amount (USD)")
					->setCellValue("K1", "Submission Date")
					->setCellValue("L1", "Approved?");

		$row = 2;

		// fill data
		foreach ($budgetRequests as $budget) {
			if ($cid != 0 && $budget->getCategory() != $cid) {
				continue;
			}
			$excelObj->getActiveSheet()->setCellValue("A$row", $budget->getBid());
			$excelObj->getActiveSheet()->setCellValue("B$row", $user_array[$budget->getHolder()]);
			$excelObj->getActiveSheet()->setCellValue("C$row", $category_array[$budget->getCategory()]);
			$excelObj->getActiveSheet()->setCellValue("D$row", $budget->getStartDate()->format('m/d/Y'));
			$excelObj->getActiveSheet()->setCellValue("E$row", $budget->getEndDate()->format('m/d/Y'));
			$excelObj->getActiveSheet()->setCellValue("F$row", $budget->getAbstract());
			$excelObj->getActiveSheet()->setCellValue("G$row", $budget->getDetails());
			$excelObj->getActiveSheet()->setCellValue("H$row", sprintf("%.2f", $budget->getAmount()));
			$excelObj->getActiveSheet()->setCellValue("I$row", $currency_array['code'][$budget->getCurtype()]);
			$excelObj->getActiveSheet()->setCellValue("J$row", sprintf("%.2f", $budget->getAmount() * $currency_array['rate'][$budget->getCurtype()]));
			$excelObj->getActiveSheet()->setCellValue("K$row", $budget->getDate()->format('m/d/Y'));
			$excelObj->getActiveSheet()->setCellValue("L$row", $budget->getApproved() ? "Yes" : "No");
			$row++;
		}

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
}