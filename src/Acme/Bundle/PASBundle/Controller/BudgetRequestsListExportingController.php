<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\BudgetRequest;
use Acme\Bundle\PASBundle\Entity\User;

class BudgetRequestsListExportingController extends Controller
{
	public function exportAction(Request $req)
	{
		$budgetRequests = null;
		$em = $this->getDoctrine()->getManager();
		$type = 2;
		$this->user = $this->getUser();
		$year = date('Y');

		// get category list from database, in ascending order
		$categories = $em->getRepository('AcmePASBundle:BudgetCategory')->findAll();
		foreach ($categories as $category) {
			$category_array[$category->getBcid()] = $category->getName();
		}

		// get currency type list from database and get the rate to USD
		$currencies = $em->getRepository('AcmePASBundle:CurrencyType')->findAll();
		foreach ($currencies as $currency) {
			$currency_array['name'][$currency->getCtid()] = $currency->getName();
			$currency_array['code'][$currency->getCtid()] = $currency->getCode();
		}

		// get user list from database
		$users = $em->getRepository('AcmePASBundle:User')->findAll();
		foreach ($users as $user) {
			$user_array['name'][$user->getUid()] = $user->getUsername();
			$user_array['email'][$user->getUid()] = $user->getEmail();
		}

		$param = $req->query->all();
		if (isset($param)) {
			if (isset($param['type'])) {
				$type = $param['type'];
			}
			if (isset($param['year'])) {
				$year = $param['year'];
			}

			// get the budget requests in specific year
			$start = new \DateTime($year . '-01-01');
			$end = new \DateTime($year . '-12-31');

			if ($this->user->getRole() == 'admin' || $this->user->getRole() == 'cfo' || $this->user->getRole() == 'vtm' || $this->user->getRole() == 'president' || $this->user->getRole() == 'secretary') {
				$budgetRequests = $em->createQuery('SELECT br FROM AcmePASBundle:BudgetRequest br WHERE br.startdate >= :start and br.startdate <= :end and br.requestType = :type')->setParameters(array('start' => $start, 'end' => $end, 'type' => $type))->getResult();
			} else {
				$budgetRequests = $em->createQuery('SELECT br FROM AcmePASBundle:BudgetRequest br WHERE br.startdate >= :start and br.startdate <= :end and br.holder = :holder and br.requestType = :type')->setParameters(array('start' => $start, 'end' => $end, 'holder' => $this->user->getUid(), 'type' => $type))->getResult();
			}
		}

		$title = "FY" . $year . ($type == 1 ? " Budget Estimation" : " Budget Request") . " List Report";

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
					->setCellValue("A1", "Request No.")
					->setCellValue("B1", "Holder")
					->setCellValue("C1", "Holder's Email")
					->setCellValue("D1", "Category")
					->setCellValue("E1", "Starting Date")
					->setCellValue("F1", "Ending Date")
					->setCellValue("G1", "Abstract")
					->setCellValue("H1", "Details")
					->setCellValue("I1", "Amount")
					->setCellValue("J1", "Status")
					->setCellValue("K1", "Submission Date");

		$row = 2;

		// fill data
		foreach ($budgetRequests as $request) {
			$excelObj->getActiveSheet()->setCellValue("A$row", sprintf("#%08d", $request->getBid()));
			$excelObj->getActiveSheet()->setCellValue("B$row", $user_array['name'][$request->getHolder()]);
			$excelObj->getActiveSheet()->setCellValue("C$row", $user_array['email'][$request->getHolder()]);
			$excelObj->getActiveSheet()->setCellValue("D$row", $category_array[$request->getCategory()]);
			$excelObj->getActiveSheet()->setCellValue("E$row", $request->getStartDate()->format('m/d/Y'));
			$excelObj->getActiveSheet()->setCellValue("F$row", $request->getEndDate()->format('m/d/Y'));
			$excelObj->getActiveSheet()->setCellValue("G$row", $request->getAbstract());
			$excelObj->getActiveSheet()->setCellValue("H$row", $request->getDetails());
			$excelObj->getActiveSheet()->setCellValue("I$row", sprintf("%.2f", $request->getAmount()) . " " . $currency_array['code'][$request->getCurtype()]);
			$excelObj->getActiveSheet()->setCellValue("J$row", $request->getApproved() ? "Approved" : "Waiting for approval");
			$excelObj->getActiveSheet()->setCellValue("K$row", $request->getDate()->format('m/d/Y'));
			$row++;
		}

		// rename worksheet
		$excelObj->getActiveSheet()->setTitle(($type == 1 ? "Budget Estimation" : "Budget Request") . " List");

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