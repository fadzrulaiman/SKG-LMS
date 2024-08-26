<?php
/**
 * This view builds an Excel5 file containing the list of leave requests declared by the connected employee.
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

$sheet->setTitle(mb_strimwidth(lang('leaves_export_title'), 0, 28, "..."));  //Maximum 31 characters allowed in sheet title.
$sheet->setCellValue('A1', lang('leaves_export_thead_id'));
$sheet->setCellValue('B1', lang('leaves_export_thead_start_date'));
$sheet->setCellValue('C1', lang('leaves_export_thead_end_date'));
$sheet->setCellValue('D1', lang('leaves_export_thead_duration'));
$sheet->setCellValue('E1', lang('leaves_export_thead_type'));
$sheet->setCellValue('F1', lang('leaves_export_thead_status'));
$sheet->setCellValue('G1', lang('leaves_export_thead_cause'));
$sheet->getStyle('A1:G1')->getFont()->setBold(true);
$sheet->getStyle('A1:G1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$leaves = $this->leaves_model->getLeavesOfEmployee($this->user_id);
$line = 2;
foreach ($leaves as $leave) {
    $date = new DateTime($leave['startdate']);
    $startdate = $date->format(lang('global_date_format'));
    $date = new DateTime($leave['enddate']);
    $enddate = $date->format(lang('global_date_format'));
    $sheet->setCellValue('A' . $line, $leave['id']);
    $sheet->setCellValue('B' . $line, $startdate);
    $sheet->setCellValue('C' . $line, $enddate);
    $sheet->setCellValue('D' . $line, $leave['duration']);
    $sheet->setCellValue('E' . $line, $leave['type_name']);
    $sheet->setCellValue('F' . $line, lang($leave['status_name']));
    $sheet->setCellValue('G' . $line, $leave['cause']);
    $line++;
}

//Autofit
foreach(range('A', 'G') as $colD) {
    $sheet->getColumnDimension($colD)->setAutoSize(TRUE);
}

writeSpreadsheet($spreadsheet, 'leaves');
