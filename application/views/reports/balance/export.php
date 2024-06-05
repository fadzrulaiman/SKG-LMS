<?php
/**
 * This view builds a Spreadsheet file of the native report 'balance of leave requests'.
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

$sheet->setTitle(mb_strimwidth(lang('reports_export_balance_title'), 0, 28, "..."));  //Maximum 31 characters allowed in sheet title.
$result = array();
$summary = array();
$types = $this->types_model->getTypes();
$users = $this->organization_model->allEmployees($_GET['entity'], $include_children);
foreach ($users as $user) {
    $result[$user->id]['Identifier'] = $user->identifier;
    $result[$user->id]['First Name'] = $user->firstname;
    $result[$user->id]['Last Name'] = $user->lastname;
    $result[$user->id]['Date Hired'] = $user->datehired;
    $result[$user->id]['Department'] = $user->department;
    $result[$user->id]['Position'] = $user->position;
    $result[$user->id]['Location'] = $user->location;
    $result[$user->id]['Contract'] = $user->contract;
    //Init type columns
    foreach ($types as $type) {
        if ($type['id'] != 0) {
        $result[$user->id][$type['name']] = '';
        }
    }

    $summary = $this->leaves_model->getLeaveBalanceForEmployee($user->id, TRUE, $refDate);
    if (!is_null($summary)) {
      if (count($summary) > 0 ) {
          foreach ($summary as $key => $value) {
              $result[$user->id][$key] = round($value[1] - $value[0], 3, PHP_ROUND_HALF_DOWN);
          }
      }
    }
}

$max = 0;
$line = 2;
$i18n = array("identifier", "firstname", "lastname", "datehired", "department", "position", "location", "contract");
foreach ($result as $row) {
    $index = 1;
    foreach ($row as $key => $value) {
        if ($line == 2) {
            $colidx = columnName($index) . '1';
            if (in_array($key, $i18n)) {
                $sheet->setCellValue($colidx, lang($key));
            } else {
                $sheet->setCellValue($colidx, $key);
            }
            $max++;
        }
        $colidx = columnName($index) . $line;
        $sheet->setCellValue($colidx, $value);
        $index++;
    }
    $line++;
}

$colidx = columnName($max) . '1';
$sheet->getStyle('A1:' . $colidx)->getFont()->setBold(true);
$sheet->getStyle('A1:' . $colidx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

//Autofit
for ($ii=1; $ii <$max; $ii++) {
    $col = columnName($ii);
    $sheet->getColumnDimension($col)->setAutoSize(TRUE);
}

writeSpreadsheet($spreadsheet, 'leave_balance');
