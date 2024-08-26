<?php
/**
 * This view builds a Spreadsheet file containing the list of leave requests (that a manager must validate).
 * @copyright  Copyright (c) Fadzrul Aiman

 * @since         0.2.0
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle(mb_strimwidth(lang('requests_export_title'), 0, 28, "..."));  //Maximum 31 characters allowed in sheet title.
$sheet->setCellValue('A1', lang('requests_export_thead_id'));
$sheet->setCellValue('B1', lang('requests_export_thead_fullname'));
$sheet->setCellValue('C1', lang('requests_export_thead_startdate'));
$sheet->setCellValue('D1', lang('requests_export_thead_enddate'));
$sheet->setCellValue('E1', lang('requests_export_thead_duration'));
$sheet->setCellValue('F1', lang('requests_export_thead_type'));
$sheet->setCellValue('G1', lang('requests_export_thead_cause'));
$sheet->setCellValue('H1', lang('requests_export_thead_status'));
$sheet->getStyle('A1:H1')->getFont()->setBold(true);
$sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

($filter == 'all')? $showAll = TRUE : $showAll = FALSE;
$requests = $this->leaves_model->getLeavesRequestedToManager($this->user_id, $showAll);
$line = 2;
foreach ($requests as $request) {
    $date = new DateTime($request['startdate']);
    $startdate = $date->format(lang('global_date_format'));
    $date = new DateTime($request['enddate']);
    $enddate = $date->format(lang('global_date_format'));
    $sheet->setCellValue('A' . $line, $request['leave_id']);
    $sheet->setCellValue('B' . $line, $request['firstname'] . ' ' . $request['lastname']);
    $sheet->setCellValue('C' . $line, $startdate);
    $sheet->setCellValue('D' . $line, $enddate);
    $sheet->setCellValue('E' . $line, $request['duration']);
    $sheet->setCellValue('F' . $line, $request['type_name']);
    $sheet->setCellValue('G' . $line, $request['cause']);
    $sheet->setCellValue('H' . $line, lang($request['status_name']));
    $line++;
}

//Autofit
foreach(range('A', 'H') as $colD) {
    $sheet->getColumnDimension($colD)->setAutoSize(TRUE);
}

writeSpreadsheet($spreadsheet, 'requests');
