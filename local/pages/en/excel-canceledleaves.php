<?php
// This script exports canceled leave requests to an Excel file using PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Load the necessary language files
$ci =& get_instance();
$ci->lang->load('requests', $ci->language);
$ci->lang->load('global', $ci->language);

// Retrieve the selected month and year from the form submission, defaulting to "All" for month and the current year if not provided
$selected_month = $ci->input->get('cboMonth') !== null ? $ci->input->get('cboMonth') : 0;
$selected_year = $ci->input->get('cboYear') ? $ci->input->get('cboYear') : date('Y');

// Create a new Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Canceled Leave Requests');

// Set the Excel header
$sheet->setCellValue('A1', lang('requests_index_thead_id'));
$sheet->setCellValue('B1', lang('requests_index_thead_fullname'));
$sheet->setCellValue('C1', lang('requests_index_thead_startdate'));
$sheet->setCellValue('D1', lang('requests_index_thead_enddate'));
$sheet->setCellValue('E1', lang('requests_index_thead_duration'));
$sheet->setCellValue('F1', lang('requests_index_thead_type'));
$sheet->setCellValue('G1', lang('requests_index_thead_status'));
$sheet->getStyle('A1:G1')->getFont()->setBold(true);
$sheet->getStyle('A1:G1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Database query to fetch canceled leave requests
$ci->db->select('users.firstname, users.lastname, leaves.*');
$ci->db->select('status.name as status_name, types.name as type_name');
$ci->db->from('leaves');
$ci->db->join('status', 'leaves.status = status.id');
$ci->db->join('types', 'leaves.type = types.id');
$ci->db->join('users', 'leaves.employee = users.id');
$ci->db->where('leaves.status', 6);  // Filter for canceled leave requests

// Add date filtering based on the selected month and year
if ($selected_month != 0) {
    $ci->db->group_start();
    $ci->db->where('MONTH(leaves.startdate)', $selected_month);
    $ci->db->where('YEAR(leaves.startdate)', $selected_year);
    $ci->db->group_end();
    $ci->db->or_group_start();
    $ci->db->where('MONTH(leaves.enddate)', $selected_month);
    $ci->db->where('YEAR(leaves.enddate)', $selected_year);
    $ci->db->group_end();
} else {
    // Filter for the entire year if "All" is selected for the month
    $start_date = $selected_year . '-01-01';
    $end_date = $selected_year . '-12-31';
    $ci->db->group_start();
    $ci->db->where('leaves.startdate >=', $start_date);
    $ci->db->where('leaves.startdate <=', $end_date);
    $ci->db->group_end();
}

$ci->db->order_by('users.lastname, users.firstname, leaves.startdate', 'desc');
$rows = $ci->db->get()->result_array();

// Populate the spreadsheet with the data
$line = 2;
foreach ($rows as $row) {
    $date = new DateTime($row['startdate']);
    $startdate = $date->format(lang('global_date_format'));
    $date = new DateTime($row['enddate']);
    $enddate = $date->format(lang('global_date_format'));
    $sheet->setCellValue('A' . $line, $row['id']);
    $sheet->setCellValue('B' . $line, $row['firstname'] . ' ' . $row['lastname']);
    $sheet->setCellValue('C' . $line, $startdate . ' (' . lang($row['startdatetype']). ')');
    $sheet->setCellValue('D' . $line, $enddate . ' (' . lang($row['enddatetype']) . ')');
    $sheet->setCellValue('E' . $line, $row['duration']);
    $sheet->setCellValue('F' . $line, $row['type_name']);
    $sheet->setCellValue('G' . $line, lang($row['status_name']));
    $line++;
}

// Auto-size the columns for better readability
foreach(range('A', 'G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(TRUE);
}

// Export the spreadsheet to an Excel file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="canceled_leave_requests.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
