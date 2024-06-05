<?php
/**
 * This view builds a Spreadsheet file containing the list of locations.
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

$sheet->setTitle(mb_strimwidth(lang('locations_export_title'), 0, 28, "..."));  //Maximum 31 characters allowed in sheet title.
$sheet->setCellValue('A1', lang('locations_export_thead_id'));
$sheet->setCellValue('B1', lang('locations_export_thead_name'));

$locations = $this->locations_model->getlocations();
$line = 2;
foreach ($locations as $location) {
    $sheet->setCellValue('A' . $line, $location['id']);
    $sheet->setCellValue('B' . $line, $location['name']);
    $line++;
}

//Autofit
foreach(range('A', 'C') as $colD) {
    $sheet->getColumnDimension($colD)->setAutoSize(TRUE);
}

writeSpreadsheet($spreadsheet, 'locations');
