<?php
/**
 * This view exports into a Spreadsheet file the native report listing the approved leave requests of employees attached to an entity.
 * This report is launched by the user from the view reports/leaves.
 * @copyright  Copyright (c) Fadzrul Aiman

 * @since         0.4.3
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle(mb_strimwidth(lang('reports_export_leaves_title'), 0, 28, "..."));  //Maximum 31 characters allowed in sheet title.

$start_date = $this->input->get("start_date") ? $this->input->get("start_date") : date('Y-m-d');
$end_date = $this->input->get("end_date") ? $this->input->get("end_date") : date('Y-m-d', strtotime("+1 month", strtotime($start_date)));

$entity = $this->input->get("entity") === FALSE ? 0 : $this->input->get("entity");
$children = filter_var($this->input->get("children"), FILTER_VALIDATE_BOOLEAN);
$requests = filter_var($this->input->get("requests"), FILTER_VALIDATE_BOOLEAN);
// Calculate total days between dates
$date1 = new DateTime($start_date);
$date2 = new DateTime($end_date);
$total_days = $date2->diff($date1)->format("%a") + 1;
$types = $this->types_model->getTypes();
$leave_requests = array();

//Iterate on all employees of the entity
$users = $this->organization_model->allEmployees($entity, $children);
$result = array();
foreach ($users as $user) {
    $result[$user->id]['Employee ID'] = $user->id;
    $result[$user->id]['Full Name'] = $user->firstname . ' ' . $user->lastname;
    $result[$user->id]['Date Hired'] = empty($user->datehired) ? '' : (new DateTime($user->datehired))->format($this->lang->line('global_date_format'));
    $result[$user->id]['Department'] = $user->department;
    $result[$user->id]['Position'] = $user->position;
    $result[$user->id]['Contract'] = $user->contract;
    $non_working_days = $this->dayoffs_model->lengthDaysOffBetweenDates($user->contract_id, $start_date, $end_date);
    $opened_days = $total_days - $non_working_days;
    $linear = $this->leaves_model->linearbydate($user->id, $start_date, $end_date, FALSE, FALSE, TRUE, FALSE);
    $leave_duration = $this->leaves_model->dateRangeLeaveDuration($linear);
    $work_duration = $opened_days - $leave_duration;
    $leaves_detail = $this->leaves_model->dateRangeLeaveByType($linear);        
    if ($requests) $leave_requests[$user->id] = $this->leaves_model->getAcceptedLeavesBetweenDates($user->id, $start_date, $end_date);
            // Initialize type columns
    foreach ($types as $type) {
        $result[$user->id][$type['name']] = array_key_exists($type['name'], $leaves_detail) ? $leaves_detail[$type['name']] : 0;
    }
    
    $result[$user->id]['Leave Duration'] = $leave_duration;
    $result[$user->id]['Total Days'] = $total_days;
    $result[$user->id]['Weekend & Public Holiday Days'] = $non_working_days;
    $result[$user->id]['Work Days'] = $work_duration;
}

$max = 0;
$line = 2;
$i18n = array("id", "firstname", "lastname", "datehired", "department", "position", "contract");
foreach ($result as $user_id => $row) {
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
    //Display a nested table listing the leave requests
    if ($requests) {
        if (count($leave_requests[$user_id])) {
        $sheet->setCellValue('A' . $line, lang('leaves_index_thead_start_date'));
        $sheet->setCellValue('B' . $line, lang('leaves_index_thead_end_date'));
        $sheet->setCellValue('C' . $line, lang('leaves_index_thead_type'));
        $sheet->setCellValue('D' . $line, lang('leaves_index_thead_duration'));
        $sheet->getStyle('A' . $line . ':D' . $line)->getFont()->setBold(true);
        $sheet->getStyle('A' . $line . ':D' . $line)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $line++;

        // Iterate over each leave requests
        foreach ($leave_requests[$user_id] as $request) {
            $sheet->setCellValue('A' . $line, $request['startdate'] . ' (' . lang($request['startdatetype']). ')');
            $sheet->setCellValue('B' . $line, $request['enddate'] . ' (' . lang($request['enddatetype']). ')');
            $sheet->setCellValue('C' . $line, $request['type']);
            $sheet->setCellValue('D' . $line, $request['duration']);
            $line++;
        }
    } else {
        // If no leave, insert a placeholder
        $sheet->setCellValue('A' . $line, "");
        $line++;
    }
}
}

$colidx = columnName($max) . '1';
$sheet->getStyle('A1:' . $colidx)->getFont()->setBold(true);
$sheet->getStyle('A1:' . $colidx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

//Autofit
for ($ii=1; $ii <$max; $ii++) {
    $col = columnName($ii);
    $sheet->getColumnDimension($col)->setAutoSize(TRUE);
}

writeSpreadsheet($spreadsheet, 'Leave Request From '. $start_date . ' To ' . $end_date);