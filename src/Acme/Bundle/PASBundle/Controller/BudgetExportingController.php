<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\BudgetRequest;
use Acme\Bundle\PASBundle\Entity\User;

class BudgetExportingController extends Controller
{
	public function exportAction(Request $req)
	{
		$em = $this->getDoctrine()->getManager();

		// get category list from database
		$users = $em->getRepository('AcmePASBundle:User')->findAll();
		$user_array = array();
		foreach ($users as $user) {
			$user_array[$user->getUid()] = $user->getUsername();
		}

		// get category list from database
		$categories = $em->getRepository('AcmePASBundle:BudgetCategory')->findAll();
		$category_array = array();
		foreach ($categories as $key => $value) {
			$category_array[$key + 1] = $value->getName();
		}

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

		$budgetRequests = $em->getRepository('AcmePASBundle:BudgetRequest')->findAll();

		// create new PHPExcel object
		$excelObj = $this->get('xls.service_xls5')->excelObj;

		// set document properties
		$excelObj->getProperties()
					->setCreator("Blu-ray Disc Association")
					->setLastModifiedBy("Blu-ray Disc Association")
					->setTitle("Expense Budget Report")
					->setSubject("Expense Budget Report")
					->setDescription("Expense Budget Report")
					->setKeywords("office 2005 openxml php")
					->setCategory("Expense Budget Report");

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
					->setCellValue("J1", "Actual Amount (USD)")
					->setCellValue("K1", "Submission Date")
					->setCellValue("L1", "Approved?");

		$row = 2;

		// fill data
		foreach ($budgetRequests as $budget) {
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

		// create the response and redirect output to a client’s web browser (Excel5)
		$response = $this->get('xls.service_xls5')->getResponse();
		$response->headers->set('Content-Type', 'application/vnd.ms-excel; charset=utf-8');
		$response->headers->set("Content-Disposition", "attachment;filename=expense_budget_report_" . date('mdY') . ".xls");
		$response->headers->set("Cache-Control", "max-age=0");
		return $response;
	}
}