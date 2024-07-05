<?php
/**
 * This view exports into a Spreadsheet file the native report listing the approved leave requests of employees attached to an entity.
 * This report is launched by the user from the view reports/leaves.
 * @since 0.4.3
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle(mb_strimwidth(lang('reports_export_leaves_title'), 0, 28, "..."));  // Maximum 31 characters allowed in sheet title.

$month = $this->input->get("month") === FALSE ? 0 : (int)$this->input->get("month");
$year = $this->input->get("year") === FALSE ? 0 : (int)$this->input->get("year");
$entity = $this->input->get("entity") === FALSE ? 0 : $this->input->get("entity");
$children = filter_var($this->input->get("children"), FILTER_VALIDATE_BOOLEAN);
$requests = filter_var($this->input->get("requests"), FILTER_VALIDATE_BOOLEAN);

// Compute facts about dates and the selected month
if ($month == 0) {
    $start = sprintf('%d-01-01', $year);
    $end = sprintf('%d-12-31', $year);
    $total_days = date("z", mktime(0,0,0,12,31,$year)) + 1;
} else {
    $start = sprintf('%d-%02d-01', $year, $month);
    $lastDay = date("t", strtotime($start));    // Last day of selected month
    $end = sprintf('%d-%02d-%02d', $year, $month, $lastDay);
    $total_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
}

$types = array_filter($this->types_model->getTypes(), function($type) {
    return $type['id'] != 0;
});

$leave_requests = array();

// Iterate on all employees of the entity
$users = $this->organization_model->allEmployees($entity, $children);
$result = array();
foreach ($users as $user) {
    $result[$user->id]['Employee ID'] = $user->id;
    $result[$user->id]['Full Name'] = $user->firstname . ' ' . $user->lastname;

    $employmentdate = $user->employmentdate ?? '1970-01-01'; // Default to Jan 1, 1970, if employmentdate is null
    $date = new DateTime($employmentdate);
    $result[$user->id]['Employment Date'] = $date->format(lang('global_date_format'));    

    $result[$user->id]['Department'] = $user->department;
    $result[$user->id]['Position'] = $user->position;
    $result[$user->id]['Location'] = $user->location;
    $result[$user->id]['Contract'] = $user->contract;
    $non_working_days = $this->dayoffs_model->lengthDaysOffBetweenDates($user->contract_id, $start, $end);
    $opened_days = $total_days - $non_working_days;

    // If the user has selected All months
    if ($month == 0) {
        $leave_duration = 0;
        for ($ii = 1; $ii < 13; $ii++) {
            $linear = $this->leaves_model->linear($user->id, $ii, $year, FALSE, FALSE, TRUE, FALSE);
            $leave_duration += $this->leaves_model->monthlyLeavesDuration($linear);
            $leaves_detail = $this->leaves_model->monthlyLeavesByType($linear);
            // Initialize or increment leave types
            foreach ($types as $type) {
                if (!isset($result[$user->id][$type['name']])) {
                    $result[$user->id][$type['name']] = 0; // Ensure initialization as integer
                }
                if (array_key_exists($type['name'], $leaves_detail)) {
                    $leaveAmount = $leaves_detail[$type['name']];
                    if (is_numeric($leaveAmount)) {
                        $result[$user->id][$type['name']] += (int)$leaveAmount; // Cast to int to be safe
                    } else {
                        // Log or handle non-numeric values
                        error_log("Non-numeric leave amount encountered for " . $type['name']);
                    }
                }
            }
        }
        if ($requests) {
            $leave_requests[$user->id] = $this->leaves_model->getAcceptedLeavesBetweenDates($user->id, $start, $end);
        }
        $work_duration = $opened_days - $leave_duration;
    } else {
        $linear = $this->leaves_model->linear($user->id, $month, $year, FALSE, FALSE, TRUE, FALSE);
        $leave_duration = $this->leaves_model->monthlyLeavesDuration($linear);
        $work_duration = $opened_days - $leave_duration;
        $leaves_detail = $this->leaves_model->monthlyLeavesByType($linear);
        if ($requests) {
            $leave_requests[$user->id] = $this->leaves_model->getAcceptedLeavesBetweenDates($user->id, $start, $end);
        }
        // Init type columns
        foreach ($types as $type) {
            if (array_key_exists($type['name'], $leaves_detail)) {
                $result[$user->id][$type['name']] = $leaves_detail[$type['name']];
            } else {
                $result[$user->id][$type['name']] = '';
            }
        }
    }
    $result[$user->id]['Leave Duration'] = $leave_duration;
    $result[$user->id]['Total Days'] = $total_days;
    $result[$user->id]['Non-Working Days'] = $non_working_days;
    $result[$user->id]['Work Duration'] = $work_duration;
}

$max = 0;
$line = 2;
$i18n = array("id", "firstname", "lastname", "employmentdate", "department", "position", "location", "contract");

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
    // Display a nested table listing the leave requests
    if ($requests) {
        if (count($leave_requests[$user_id])) {
            $sheet->setCellValue('A' . $line, lang('leaves_index_thead_start_date'));
            $sheet->setCellValue('B' . $line, lang('leaves_index_thead_end_date'));
            $sheet->setCellValue('C' . $line, lang('leaves_index_thead_type'));
            $sheet->setCellValue('D' . $line, lang('leaves_index_thead_duration'));
            $sheet->getStyle('A' . $line . ':D' . $line)->getFont()->setBold(true);
            $sheet->getStyle('A' . $line . ':D' . $line)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $line++;
            // Iterate on leave requests
            foreach ($leave_requests[$user_id] as $request) {
                $date = new DateTime($request['startdate']);
                $startdate = $date->format(lang('global_date_format'));
                $date = new DateTime($request['enddate']);
                $enddate = $date->format(lang('global_date_format'));
                $sheet->setCellValue('A' . $line, $startdate );
                $sheet->setCellValue('B' . $line, $enddate );
                $sheet->setCellValue('C' . $line, $request['type']);
                $sheet->setCellValue('D' . $line, $request['duration']);
                $line++;
            }
        } else {
            $sheet->setCellValue('A' . $line, "----");
            $line++;
        }
    }
}

$colidx = columnName($max) . '1';
$sheet->getStyle('A1:' . $colidx)->getFont()->setBold(true);
$sheet->getStyle('A1:' . $colidx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Autofit
for ($ii=1; $ii <= $max; $ii++) {
    $col = columnName($ii);
    $sheet->getColumnDimension($col)->setAutoSize(TRUE);
}

writeSpreadsheet($spreadsheet, 'leave_requests_'. $month . '_' . $year);

?>
