<?php
/**
 * This view exports a tabular calendar of the leave taken by a group of users.
 * It builds a Spreadsheet file downloaded by the browser.
 * 
 * @copyright  Copyright (c) Fadzrul Aiman
 * @since      0.4.3
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Print the header with the values of the export parameters
$sheet->setTitle(mb_strimwidth(lang('calendar_tabular_export_title'), 0, 28, "..."));  // Maximum 31 characters allowed in sheet title.
$sheet->setCellValue('A1', lang('calendar_tabular_export_param_entity'));
$sheet->setCellValue('A2', lang('calendar_tabular_export_param_month'));
$sheet->setCellValue('A3', lang('calendar_tabular_export_param_year'));
$sheet->setCellValue('A4', lang('calendar_tabular_export_param_children'));
$sheet->getStyle('A1:A4')->getFont()->setBold(true);
$sheet->setCellValue('B1', $entityName);
$sheet->setCellValue('B2', $month . ' (' . $monthName . ')');
$sheet->setCellValue('B3', $year);
if ($children == TRUE) {
    $sheet->setCellValue('B4', lang('global_true'));
} else {
    $sheet->setCellValue('B4', lang('global_false'));
}

// Print two lines: the short name of all days for the selected month (horizontally aligned)
$start = $year . '-' . $month . '-' . '1';    // First date of selected month
$lastDay = date("t", strtotime($start));    // Last day of selected month
for ($ii = 1; $ii <= $lastDay; $ii++) {
    $dayNum = date("N", strtotime($year . '-' . $month . '-' . $ii));
    $col = columnName(3 + $ii);
    // Print day number
    $sheet->setCellValue($col . '9', $ii);
    // Print short name of the day
    switch ($dayNum) {
        case 1: $sheet->setCellValue($col . '8', lang('calendar_monday_short')); break;
        case 2: $sheet->setCellValue($col . '8', lang('calendar_tuesday_short')); break;
        case 3: $sheet->setCellValue($col . '8', lang('calendar_wednesday_short')); break;
        case 4: $sheet->setCellValue($col . '8', lang('calendar_thursday_short')); break;
        case 5: $sheet->setCellValue($col . '8', lang('calendar_friday_short')); break;
        case 6: $sheet->setCellValue($col . '8', lang('calendar_saturday_short')); break;
        case 7: $sheet->setCellValue($col . '8', lang('calendar_sunday_short')); break;
    }
}

// Label for employee name
$sheet->setCellValue('C8', lang('calendar_tabular_export_thead_employee'));
$sheet->mergeCells('C8:C9');
// The header is horizontally aligned
$col = columnName(3 + $lastDay);
$sheet->getStyle('C8:' . $col . '9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Box around the lines for each employee
$styleBox = [
    'borders' => [
        'top' => [
            'borderStyle' => Border::BORDER_THIN
        ],
        'bottom' => [
            'borderStyle' => Border::BORDER_THIN
        ]
    ]
];

// Box around a day
$dayBox = [
    'borders' => [
        'left' => [
            'borderStyle' => Border::BORDER_DASHDOT,
            'color' => ['rgb' => '808080']
        ],
        'right' => [
            'borderStyle' => Border::BORDER_DASHDOT,
            'color' => ['rgb' => '808080']
        ]
    ]
];

// Background colors for the calendar according to the type of leave
$styleBgPlanned = [
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'DDD']
    ]
];
$styleBgRequested = [
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'F89406']
    ]
];
$styleBgAccepted = [
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '468847']
    ]
];
$styleBgRejected = [
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'FF0000']
    ]
];
$styleBgDayOff = [
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '000000']
    ]
];

// Sort the $tabular array by organization_name
usort($tabular, function ($a, $b) {
    return strcmp($a->organization_name, $b->organization_name);
});

// Group employees by department
$departments = [];
foreach ($tabular as $employee) {
    $departments[$employee->organization_name][] = $employee;
}

$line = 10;
$canSeeType = TRUE;

// Iterate over each department
foreach ($departments as $department => $employees) {
    // Print department name
    $sheet->setCellValue('A' . $line, $department);
    $sheet->mergeCells('A' . $line . ':' . $col . $line);
    $sheet->getStyle('A' . $line . ':' . $col . $line)->getFont()->setBold(true);
    $line += 1;
    
    // Iterate over all employees of the department
    foreach ($employees as $employee) {
        // Merge the two lines containing the name of the employee and apply a border around it
        $sheet->setCellValue('C' . $line, $employee->name);
        $sheet->mergeCells('C' . $line . ':C' . ($line + 1));
        $col = columnName($lastDay + 3);
        $sheet->getStyle('C' . $line . ':' . $col . ($line + 1))->applyFromArray($styleBox);

        // Iterate over all days of the selected month
        $dayNum = 0;
        foreach ($employee->days as $day) {
            if (($is_hr == TRUE) ||
                ($is_admin == TRUE) ||
                ($employee->manager == $user_id) ||
                ($employee->id == $user_id)) {
                $canSeeType = TRUE;
            } else {
                $canSeeType = FALSE;
            }
            $dayNum++;
            $col = columnName(3 + $dayNum);
            if (strstr($day->display, ';')) { // Two statuses in the cell
                $statuses = explode(";", $day->status);
                $types = explode(";", $day->type);
                $sheet->getComment($col . $line)->getText()->createTextRun($types[0]);
                $sheet->getComment($col . ($line + 1))->getText()->createTextRun($types[1]);
                switch (intval($statuses[0])) {
                    case 1: $sheet->getStyle($col . $line)->applyFromArray($styleBgPlanned); break;  // Planned
                    case 2: $sheet->getStyle($col . $line)->applyFromArray($styleBgRequested); break;  // Requested
                    case 3: $sheet->getStyle($col . $line)->applyFromArray($styleBgAccepted); break;  // Accepted
                    case 4: $sheet->getStyle($col . $line)->applyFromArray($styleBgRejected); break;  // Rejected
                    case 5: $sheet->getStyle($col . $line)->applyFromArray($styleBgRejected); break;    //Cancellation
                    case 6: $sheet->getStyle($col . $line)->applyFromArray($styleBgRejected); break;    //Canceled
                    case 12: $sheet->getStyle($col . $line)->applyFromArray($styleBgDayOff); break;    //Day off
                    case 13: $sheet->getStyle($col . $line)->applyFromArray($styleBgDayOff); break;    //Day off
                }
                switch (intval($statuses[1])) {
                    case 1: $sheet->getStyle($col . ($line + 1))->applyFromArray($styleBgPlanned); break;  // Planned
                    case 2: $sheet->getStyle($col . ($line + 1))->applyFromArray($styleBgRequested); break;  // Requested
                    case 3: $sheet->getStyle($col . ($line + 1))->applyFromArray($styleBgAccepted); break;  // Accepted
                    case 4: $sheet->getStyle($col . ($line + 1))->applyFromArray($styleBgRejected); break;  // Rejected
                    case 5: $sheet->getStyle($col . ($line + 1))->applyFromArray($styleBgRejected); break;    //Cancellation
                    case 6: $sheet->getStyle($col . ($line + 1))->applyFromArray($styleBgRejected); break;    //Canceled
                    case 12: $sheet->getStyle($col . ($line + 1))->applyFromArray($styleBgDayOff); break;    //Day off
                    case 13: $sheet->getStyle($col . ($line + 1))->applyFromArray($styleBgDayOff); break;    //Day off
                }
                if ($displayTypes && $canSeeType) {
                    $acronyms = explode(";", $day->acronym);
                    $sheet->setCellValue($col . $line, $acronyms[0]);
                    $sheet->setCellValue($col . ($line + 1), $acronyms[1]);
                }
            } else { // Only one status in the cell
                switch ($day->display) {
                    case '1':   // All day
                        if ($displayTypes && $canSeeType) {
                            $sheet->setCellValue($col . $line, $day->acronym);
                            $sheet->setCellValue($col . ($line + 1), $day->acronym);
                        }
                        $sheet->getComment($col . $line)->getText()->createTextRun($day->type);
                        $sheet->getComment($col . ($line + 1))->getText()->createTextRun($day->type);
                        switch ($day->status) {
                            case 1: $sheet->getStyle($col . $line . ':' . $col . ($line + 1))->applyFromArray($styleBgPlanned); break;  // Planned
                            case 2: $sheet->getStyle($col . $line . ':' . $col . ($line + 1))->applyFromArray($styleBgRequested); break; // Requested
                            case 3: $sheet->getStyle($col . $line . ':' . $col . ($line + 1))->applyFromArray($styleBgAccepted); break;  // Accepted
                            case 4: $sheet->getStyle($col . $line . ':' . $col . ($line + 1))->applyFromArray($styleBgRejected); break;  // Rejected
                            case 5: $sheet->getStyle($col . $line . ':' . $col . ($line + 1))->applyFromArray($styleBgRejected); break;  // Cancellation
                            case 6: $sheet->getStyle($col . $line . ':' . $col . ($line + 1))->applyFromArray($styleBgRejected); break;  // Canceled
                        }
                        break;
                    case '2':   // AM
                        if ($displayTypes && $canSeeType) {
                            $sheet->setCellValue($col . $line, $day->acronym);
                        }
                        $sheet->getComment($col . $line)->getText()->createTextRun($day->type);
                        switch ($day->status) {
                            case 1: $sheet->getStyle($col . $line)->applyFromArray($styleBgPlanned); break;  // Planned
                            case 2: $sheet->getStyle($col . $line)->applyFromArray($styleBgRequested); break;  // Requested
                            case 3: $sheet->getStyle($col . $line)->applyFromArray($styleBgAccepted); break;  // Accepted
                            case 4: $sheet->getStyle($col . $line)->applyFromArray($styleBgRejected); break;  // Rejected
                            case 5: $sheet->getStyle($col . $line)->applyFromArray($styleBgRejected); break;  // Cancellation
                            case 6: $sheet->getStyle($col . $line)->applyFromArray($styleBgRejected); break;  // Canceled
                        }
                        break;
                    case '3':   // PM
                        if ($displayTypes && $canSeeType) {
                            $sheet->setCellValue($col . ($line + 1), $day->acronym);
                        }
                        $sheet->getComment($col . ($line + 1))->getText()->createTextRun($day->type);
                        switch ($day->status) {
                            case 1: $sheet->getStyle($col . ($line + 1))->applyFromArray($styleBgPlanned); break;  // Planned
                            case 2: $sheet->getStyle($col . ($line + 1))->applyFromArray($styleBgRequested); break;  // Requested
                            case 3: $sheet->getStyle($col . ($line + 1))->applyFromArray($styleBgAccepted); break;  // Accepted
                            case 4: $sheet->getStyle($col . ($line + 1))->applyFromArray($styleBgRejected); break;  // Rejected
                            case 5: $sheet->getStyle($col . ($line + 1))->applyFromArray($styleBgRejected); break;  // Cancellation
                            case 6: $sheet->getStyle($col . ($line + 1))->applyFromArray($styleBgRejected); break;  // Canceled
                        }
                        break;
                    case '4': // Full day off
                        $sheet->getStyle($col . $line . ':' . $col . ($line + 1))->applyFromArray($styleBgDayOff);
                        $sheet->getComment($col . $line)->getText()->createTextRun($day->type);
                        $sheet->getComment($col . ($line + 1))->getText()->createTextRun($day->type);
                        break;
                    case '12':  // AM off
                        $sheet->getStyle($col . $line)->applyFromArray($styleBgDayOff);
                        $sheet->getComment($col . $line)->getText()->createTextRun($day->type);
                        break;
                    case '13':   // PM off
                        $sheet->getStyle($col . ($line + 1))->applyFromArray($styleBgDayOff);
                        $sheet->getComment($col . ($line + 1))->getText()->createTextRun($day->type);
                        break;
                }
            } // Only one status in the cell
        } // Day
        $line += 2;
    } // Employee
} // Department

// Autofit for all columns containing the days
for ($ii = 1; $ii <= $lastDay; $ii++) {
    $col = columnName($ii + 3);
    $sheet->getStyle($col . '8:' . $col . ($line - 1))->applyFromArray($dayBox);
    $sheet->getColumnDimension($col)->setAutoSize(TRUE);
}
$sheet->getColumnDimension('A')->setAutoSize(TRUE);
$sheet->getColumnDimension('B')->setAutoSize(TRUE);
$sheet->getColumnDimension('C')->setWidth(40);

// Set layout to landscape and make the Excel sheet fit to the page
$sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
$sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
$sheet->getPageSetup()->setFitToPage(TRUE);
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(0);

writeSpreadsheet($spreadsheet, 'tabular');
?>
