<?php
/**
 * This view builds a Spreadsheet file containing the list of users.
 * @copyright  Copyright (c) Fadzrul Aiman
 * @since 0.2.0
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle(mb_strimwidth(lang('users_export_title'), 0, 28, "..."));  // Maximum 31 characters allowed in sheet title.
$sheet->setCellValue('A1', lang('users_export_thead_id'));
$sheet->setCellValue('B1', lang('users_export_thead_fullname'));  // Change to Full Name
$sheet->setCellValue('C1', lang('users_export_thead_email'));
$sheet->setCellValue('D1', lang('users_export_thead_manager'));

$sheet->getStyle('A1:D1')->getFont()->setBold(true);
$sheet->getStyle('A1:D1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$users = $this->users_model->getUsers();
$line = 2;
foreach ($users as $user) {
    $sheet->setCellValue('A' . $line, $user['id']);
    $sheet->setCellValue('B' . $line, $user['firstname'] . ' ' . $user['lastname']);  // Combine firstname and lastname
    $sheet->setCellValue('C' . $line, $user['email']);
    $sheet->setCellValue('D' . $line, $user['manager']);
    $line++;
}

// Autofit
foreach(range('A', 'D') as $colD) {
    $sheet->getColumnDimension($colD)->setAutoSize(TRUE);
}

writeSpreadsheet($spreadsheet, 'users');
