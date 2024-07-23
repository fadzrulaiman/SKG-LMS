<?php
/**
 * This controller manages the setting of sick leave entitled days
 * and exports entitled days data to Excel.
 * 
 * @copyright  Copyright (c) Fadzrul Aiman
 * @since      0.1.0
 */
 use PhpOffice\PhpSpreadsheet\Spreadsheet;
 use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
if (!defined('BASEPATH')) { exit('No direct script access allowed'); }

class Setleave extends CI_Controller {
   
    /**
     * Default constructor
     * @autor Fadzrul Aiman
     */
    public function __construct() {
        parent::__construct();
        setUserContext($this);
        $this->load->model('leaves_model');
        $this->load->model('entitleddays_model');
        $this->load->model('users_model'); 
        $this->lang->load('entitleddays', $this->language);
    }

    /**
     * Display an ajax-based form that lists entitled days for setting sick leave
     * and allows updating the list by adding or removing one item
     * @autor Fadzrul Aiman
     */
    public function setsickleave() {
        $this->auth->checkIfOperationIsAllowed('entitleddays_contract');
        $data = getUserContext($this);
        $this->lang->load('datatable', $this->language);
        
        // Get the year from query parameters
        $year = $this->input->get('year');
        
        // Fetch data to display in the view
        $data['entitleddays'] = $this->fetch_sickentitleddays($year);
        $data['selected_year'] = $year;

        $data['title'] = lang('set_sickleave_title');
        $this->load->view('templates/header', $data);
        $this->load->view('menu/index', $data);
        $this->load->view('setleave/setsickleave', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Fetch entitled days for a specific year or all if year is not provided.
     * @param int $year optional year to filter entitled days by
     * @return array list of entitled days with employee names
     */
    private function fetch_sickentitleddays($year = null) {
        if ($year) {
            return $this->entitleddays_model->sickleave_entitleddays_year($year);
        } else {
            $entitledDays = $this->entitleddays_model->sickleave_entitleddays();
            foreach ($entitledDays as &$day) {
                $day['employee_name'] = $this->users_model->getName($day['employee']);
            }
            return $entitledDays;
        }
    }
  
    /**
     * Get entitled days for a specific year in JSON format.
     * @param int $year the year to filter entitled days by
     */
    public function sickleave_entitleddays_year($year) {
        $this->auth->checkIfOperationIsAllowed('entitleddays_contract');
    
        $entitledDays = $this->entitleddays_model->sickleave_entitleddays_year($year);
        echo json_encode(['data' => $entitledDays]);
    }

    /**
     * Set sick leave for a specific year and return updated data in JSON format.
     * @param int $year the year to set sick leave for
     */
    public function executesickleaveyear($year) {
        $this->auth->checkIfOperationIsAllowed('entitleddays_contract');
        
        // Call the model method to set sick leave for the specified year
        $this->entitleddays_model->set_sickleave($year);
    
        // Fetch updated data to return to the client
        $entitledDays = $this->fetch_sickentitleddays();
        echo json_encode(['status' => 'success', 'data' => $entitledDays]);
    }

    
    /**
     * Set nullsick leave for a specific year and return updated data in JSON format.
     * @param int $year the year to set sick leave for
     */
    public function executenullsickleaveyear($year) {
        $this->auth->checkIfOperationIsAllowed('entitleddays_contract');
        
        try {
            // Fetch updated data to return to the client
            $entitledDays = $this->fetch_nullsickentitleddays($year);
            echo json_encode(['status' => 'success', 'data' => $entitledDays]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
        }
    }

    /**
     * Fetch entitled days for a specific year or all if year is not provided.
     * @param int $year optional year to filter entitled days by
     * @return array list of entitled days with employee names
     */
    private function fetch_nullsickentitleddays($year = null) {
        if ($year) {
            return $this->entitleddays_model->nullsickleave_entitleddays_year($year);
        } else {
            $entitledDays = $this->entitleddays_model->nullsickleave_entitleddays();
            foreach ($entitledDays as &$day) {
                $day['employee_name'] = $this->users_model->getName($day['employee']);
            }
            return $entitledDays;
        }
    }
    
    /**
     * Export the entitled days for setting sick leave
     * @autor Fadzrul Aiman
     */
    public function exportsickleave() {
        $this->auth->checkIfOperationIsAllowed('entitleddays_contract');
        $data = getUserContext($this);
        $this->lang->load('datatable', $this->language);

        // Fetch filter criteria from GET parameters
        $year = $this->input->get('year');
        
        // Fetch data based on the filter criteria
        if ($year) {
            $entitledDays = $this->entitleddays_model->sickleave_entitleddays_year($year);
            $fileName = "Entitled_Sick_Leave_$year.xlsx";
        } else {
            $entitledDays = $this->entitleddays_model->sickleave_entitleddays();
            $fileName = "Entitled_Sick_Leave.xlsx";
        }

        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set header row
        $sheet->setCellValue('A1', 'Employee ID');
        $sheet->setCellValue('B1', 'Employee Name');
        $sheet->setCellValue('C1', 'Start Date');
        $sheet->setCellValue('D1', 'End Date');
        $sheet->setCellValue('E1', 'Days Entitled');

        // Populate data
        $rowNumber = 2; // Starting row for data
        foreach ($entitledDays as $row) {
            $sheet->setCellValue('A' . $rowNumber, $row['employee']);
            $sheet->setCellValue('B' . $rowNumber, $row['employee_name']);
            $sheet->setCellValue('C' . $rowNumber, $row['startdate']);
            $sheet->setCellValue('D' . $rowNumber, $row['enddate']);
            $sheet->setCellValue('E' . $rowNumber, $row['days']);
            $rowNumber++;
        }

        // Set headers to force download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        // Write file to output
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Display an ajax-based form that lists entitled days for setting annual leave
     * and allows updating the list by adding or removing one item
     * @autor Fadzrul Aiman
     */
    public function setannualleave() {
        $this->auth->checkIfOperationIsAllowed('entitleddays_contract');
        $data = getUserContext($this);
        $this->lang->load('datatable', $this->language);
        
        // Get the year from query parameters
        $year = $this->input->get('year');
        
        // Fetch data to display in the view
        $data['entitleddays'] = $this->fetch_annualentitleddays($year);
        $data['selected_year'] = $year;

        $data['title'] = lang('set_sickleave_title');
        $this->load->view('templates/header', $data);
        $this->load->view('menu/index', $data);
        $this->load->view('setleave/setannualleave', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Fetch entitled days for a specific year or all if year is not provided.
     * @param int $year optional year to filter entitled days by
     * @return array list of entitled days with employee names
     */
    private function fetch_annualentitleddays($year = null) {
        if ($year) {
            return $this->entitleddays_model->annualleave_entitleddays_year($year);
        } else {
            $entitledDays = $this->entitleddays_model->annualleave_entitleddays();
            foreach ($entitledDays as &$day) {
                $day['name'] = $this->users_model->getName($day['contract_name']);
            }
            return $entitledDays;
        }
    }

    /**
     * Get entitled days for a specific year in JSON format.
     * @param int $year the year to filter entitled days by
     */
    public function annualleave_entitleddays_year($year) {
        $this->auth->checkIfOperationIsAllowed('entitleddays_contract');
    
        $entitledDays = $this->entitleddays_model->annualleave_entitleddays_year($year);
        echo json_encode(['data' => $entitledDays]);
    }

    /**
     * Set sick leave for a specific year and return updated data in JSON format.
     * @param int $year the year to set sick leave for
     */
    public function executeannualleaveyear($year) {
        $this->auth->checkIfOperationIsAllowed('entitleddays_contract');
        
        // Call the model method to set sick leave for the specified year
        $this->entitleddays_model->set_annualleave($year);
    
        // Fetch updated data to return to the client
        $entitledDays = $this->fetch_annualentitleddays();
        echo json_encode(['status' => 'success', 'data' => $entitledDays]);
    }

    
    /**
     * Set nullsick leave for a specific year and return updated data in JSON format.
     * @param int $year the year to set sick leave for
     */
    public function executenullannualleaveyear($year) {
        $this->auth->checkIfOperationIsAllowed('entitleddays_contract');
        
        try {
            // Fetch updated data to return to the client
            $entitledDays = $this->fetch_nullannualentitleddays($year);
            echo json_encode(['status' => 'success', 'data' => $entitledDays]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
        }
    }    

    /**
     * Fetch entitled days for a specific year or all if year is not provided.
     * @param int $year optional year to filter entitled days by
     * @return array list of entitled days with employee names
     */
    private function fetch_nullannualentitleddays($year = null) {
        if ($year) {
            return $this->entitleddays_model->nullannualleave_entitleddays_year($year);
        } else {
            $entitledDays = $this->entitleddays_model->nullannualleave_entitleddays();
            foreach ($entitledDays as &$day) {
                $day['name'] = $this->users_model->getName($day['contract_name']);
            }
            return $entitledDays;
        }
    }

    /**
     * Export the entitled days for setting annual leave to an Excel file
     * @autor Fadzrul Aiman
     */
    public function exportannualleave() {
        $this->auth->checkIfOperationIsAllowed('entitleddays_contract');
        $data = getUserContext($this);
        $this->lang->load('datatable', $this->language);

        // Fetch filter criteria from GET parameters
        $year = $this->input->get('year');
        
        // Fetch data based on the filter criteria
        if ($year) {
            $entitledDays = $this->entitleddays_model->annualleave_entitleddays_year($year);
            $fileName = "Entitled_Annual_Leave_$year.xlsx";
        } else {
            $entitledDays = $this->entitleddays_model->annualleave_entitleddays();
            $fileName = "Entitled_Annual_Leave.xlsx";
        }

        // Create new Spreadsheet object
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set header row
        $sheet->setCellValue('A1', 'Contract ID');
        $sheet->setCellValue('B1', 'Contract Name');
        $sheet->setCellValue('C1', 'Start Date');
        $sheet->setCellValue('D1', 'End Date');
        $sheet->setCellValue('E1', 'Days Entitled');

        // Populate data
        $rowNumber = 2; // Starting row for data
        foreach ($entitledDays as $row) {
            $sheet->setCellValue('A' . $rowNumber, $row['contract_id']);
            $sheet->setCellValue('B' . $rowNumber, $row['contract_name']);
            $sheet->setCellValue('C' . $rowNumber, $row['startdate']);
            $sheet->setCellValue('D' . $rowNumber, $row['enddate']);
            $sheet->setCellValue('E' . $rowNumber, $row['days']);
            $rowNumber++;
        }

        // Set headers to force download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        // Write file to output
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // Display the leave bank page
    public function setleavebank() {
        $this->auth->checkIfOperationIsAllowed('entitleddays_contract');
        $data = getUserContext($this);
        $this->lang->load('datatable', $this->language);
        
        // Get the year from query parameters
        $year = $this->input->get('year');
        
        // Fetch data to display in the view
        $data['entitleddays'] = $this->fetch_leavebankentitleddays($year);
        $data['selected_year'] = $year;

        $data['title'] = lang('set_sickleave_title');
        $this->load->view('templates/header', $data);
        $this->load->view('menu/index', $data);
        $this->load->view('setleave/setleavebank', $data);
        $this->load->view('templates/footer');
    }

    // Fetch entitled days for a specific year or all if year is not provided.
    private function fetch_leavebankentitleddays($year = null) {
        if ($year) {
            return $this->entitleddays_model->get_leavebankbalances($year);
        } else {
            $entitledDays = $this->entitleddays_model->get_bankentitlement();
            foreach ($entitledDays as &$day) {
                $day['name'] = $this->users_model->getName($day['contract_name']);
            }
            return $entitledDays;
        }
    }

    // Method to calculate and insert entitled days
    public function executeleavebank() {
        $this->entitleddays_model->calculate_and_insert_entitled_days();
        $entitledDays = $this->fetch_leavebankentitleddays();
        echo json_encode(['status' => 'success', 'data' => $entitledDays]);
    }

    // Set nullsick leave for a specific year and return updated data in JSON format.
    public function leavebank_entitleddays_year($year) {
        $this->auth->checkIfOperationIsAllowed('entitleddays_contract');
        
        try {
            // Fetch updated data to return to the client
            $entitledDays = $this->fetch_nullannualentitleddays($year);
            echo json_encode(['status' => 'success', 'data' => $entitledDays]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * Export the entitled days for setting leave bank to an Excel file
     * @autor Fadzrul Aiman
     */
    public function exportleavebank() {
        $this->auth->checkIfOperationIsAllowed('entitleddays_contract');
        $data = getUserContext($this);
        $this->lang->load('datatable', $this->language);

        // Fetch filter criteria from GET parameters
        $year = $this->input->get('year');
        
        // Fetch data based on the filter criteria
        if ($year) {
            $entitledDays = $this->entitleddays_model->get_leavebankbalances($year);
            $fileName = "Entitled_Leave_Bank_$year.xlsx";
        } else {
            $entitledDays = $this->entitleddays_model->get_bankentitlement();
            $fileName = "Entitled_Leave_Bank.xlsx";
        }

        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set header row
        $sheet->setCellValue('A1', 'Employee ID');
        $sheet->setCellValue('B1', 'Employee Name');
        $sheet->setCellValue('C1', 'Contract Name');
        $sheet->setCellValue('D1', 'Annual Leave Balance Last Year');
        $sheet->setCellValue('E1', 'Leave Bank Balance Last Year');
        $sheet->setCellValue('F1', 'Unused Leave Last Year');
        $sheet->setCellValue('G1', 'Leave Bank Entitlement This Year');

        // Populate data
        $rowNumber = 2; // Starting row for data
        foreach ($entitledDays as $row) {
            $sheet->setCellValue('A' . $rowNumber, $row['employee_id']);
            $sheet->setCellValue('B' . $rowNumber, $row['fullname']);
            $sheet->setCellValue('C' . $rowNumber, $row['contract_name']);
            $sheet->setCellValue('D' . $rowNumber, $row['annual_leave_balance_last_year']);
            $sheet->setCellValue('E' . $rowNumber, $row['leave_bank_balance_last_year']);
            $sheet->setCellValue('F' . $rowNumber, $row['leave_burned']);
            $sheet->setCellValue('G' . $rowNumber, $row['leave_bank_entitlement_this_year']);
            $rowNumber++;
        }

        // Set headers to force download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        // Write file to output
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
?>