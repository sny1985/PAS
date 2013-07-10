<?php

namespace Acme\Bundle\PASBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Acme\Bundle\PASBundle\Entity\PreRequest;
use Acme\Bundle\PASBundle\Entity\User;

class PreRequestsListExportingController extends Controller
{
	public function exportAction(Request $req)
	{
		$em = $this->getDoctrine()->getManager();
		$preRequests = null;
		$this->user = $this->getUser();
		$year = date('Y');

		// get category list from database, in ascending order
		// $categories = $em->getRepository('AcmePASBundle:BudgetCategory')->findAll();
		// foreach ($categories as $key => $value) {
		// 	$category_array[$key + 1] = $value->getName();
		// }

		// get currency type list from database and get the rate to USD
		$currencies = $em->getRepository('AcmePASBundle:CurrencyType')->findAll();
		foreach ($currencies as $key => $value) {
			$currency_array['name'][$key + 1] = $value->getName();
			$currency_array['code'][$key + 1] = $value->getCode();
		}

		// get user list from database
		$users = $em->getRepository('AcmePASBundle:User')->findAll();
		foreach ($users as $key => $value) {
			$user_array['name'][$key] = $value->getUsername();
			$user_array['email'][$key] = $value->getEmail();
		}

		$param = $req->query->all();
		if (isset($param)) {
			if (isset($param['year'])) {
				$year = $param['year'];
			}

			// get the budget requests in specific year
			$start = new \DateTime($year . '-01-01');
			$end = new \DateTime($year . '-12-31');

			if ($this->user->getRole() == 'admin' || $this->user->getRole() == 'cfo' || $this->user->getRole() == 'vtm' || $this->user->getRole() == 'president' || $this->user->getRole() == 'secretary' || $this->user->getRole() == 'chair') {
				$preRequests = $em->createQuery('SELECT pr FROM AcmePASBundle:PreRequest pr WHERE pr.date >= :start and pr.date <= :end')->setParameters(array('start' => $start, 'end' => $end))->getResult();
			} else {
				$preRequests = $em->createQuery('SELECT pr FROM AcmePASBundle:PreRequest pr WHERE pr.date >= :start and pr.date <= :end and pr.requester = :requester')->setParameters(array('start' => $start, 'end' => $end, 'requester' => $this->user->getUid()))->getResult();
			}
		}

		$title = "FY" . $year . " Pre-payment Requests List Report";

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
					->setCellValue("B1", "Requester")
					->setCellValue("C1", "Requester's Email")
					->setCellValue("D1", "Explanation")
					->setCellValue("E1", "Selected Budget")
					->setCellValue("F1", "Amount")
					->setCellValue("G1", "Status")
					->setCellValue("H1", "Submission Date");

		$row = 2;

		// fill data
		foreach ($preRequests as $request) {
			$selectedBudget = explode('-', $request->getSelectedBudget());
			$level = $request->getLevel();
			if ($level == 1) {
				$status = $request->getChairApproved();
			} else {
				$cApproved = $request->getCfoApproved();
				$pApproved = $request->getPresidentApproved();
				$sApproved = $request->getSecretaryApproved();
				if ($cApproved == 2 || $pApproved == 2 || $sApproved == 2) {
					$status = 2;
				} else if ($cApproved == 1 && $pApproved == 1 && $sApproved == 1) {
					$status = 1;
				} else {
					$status = 0;
				}
			}
			if ($status == 1) {
				$statusText = "Approved";
			} else if ($status == 2) {
				$statusText = "Pending";
			} else {
				$statusText = "Waiting for approval";
			}

			$excelObj->getActiveSheet()->setCellValue("A$row", sprintf("#%08d", intval($request->getPrid())));
			$excelObj->getActiveSheet()->setCellValue("B$row", $user_array['name'][$request->getRequester()]);
			$excelObj->getActiveSheet()->setCellValue("C$row", $user_array['email'][$request->getRequester()]);
			$excelObj->getActiveSheet()->setCellValue("D$row", $request->getExplanation());
			$excelObj->getActiveSheet()->setCellValue("E$row", $selectedBudget[1]);
			$excelObj->getActiveSheet()->setCellValue("F$row", sprintf("%.2f", $request->getAmount()) . " " . $currency_array['code'][$request->getCurtype()]);
			$excelObj->getActiveSheet()->setCellValue("G$row", $statusText);
			$excelObj->getActiveSheet()->setCellValue("H$row", $request->getDate()->format('m/d/Y'));
			$row++;
		}

		// rename worksheet
		$excelObj->getActiveSheet()->setTitle("Pre-payment Requests List");

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