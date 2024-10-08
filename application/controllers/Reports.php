<?php
/**
 * This controller serves the list of custom reports and the system reports.
 * @copyright  Copyright (c) Fadzrul Aiman

 * @since         0.2.0
 */

if (!defined('BASEPATH')) { exit('No direct script access allowed'); }

/**
 * This classe loads:
 *  - the list of custom reports described into local/reports/*.ini
 *  - the system reports implemented into Jorani.
 * The custom reports need to be implemented into local/pages/{lang}/ (see Controller Page)
 */
class Reports extends CI_Controller {

    /**
     * Default constructor
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function __construct() {
        parent::__construct();
        setUserContext($this);
        $this->lang->load('reports', $this->language);
        $this->lang->load('global', $this->language);
    }

    /**
     * List the available custom reports (provided they are described into local/reports/*.ini)
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function index() {
        $this->auth->checkIfOperationIsAllowed('report_list');
        $data = getUserContext($this);
        $this->lang->load('datatable', $this->language);

        $reports = array();
        //List all the available reports
        $files = glob(FCPATH . '/local/reports/*.ini');
        foreach($files as $file) {
            $ini_array = parse_ini_file($file, TRUE);
            //Test if the report is available for the language being used
            if (array_key_exists($this->language_code, $ini_array)) {
                //If available, push the report into the list to be displayed with a description
                $reports[$ini_array[$this->language_code]['name']] = array(
                    basename($file),
                    $ini_array[$this->language_code]['description'],
                    $ini_array['configuration']['path'],
                );
            }
        }

        $data['title'] = lang('reports_index_title');
        $data['reports'] = $reports;
        $this->load->view('templates/header', $data);
        $this->load->view('menu/index', $data);
        $this->load->view('reports/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Landing page of the shipped-in balance report
     * @param string $refTmp Optional Unix timestamp (set a date of reference for the report).
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function balance($refTmp = NULL) {
        $this->auth->checkIfOperationIsAllowed('native_report_balance');
        $data = getUserContext($this);
        $refDate = date("Y-m-d");
        if ($refTmp != NULL) {
            $refDate = date("Y-m-d", $refTmp);
        }
        $data['refDate'] = $refDate;
        $data['title'] = lang('reports_balance_title');
        $this->load->view('templates/header', $data);
        $this->load->view('menu/index', $data);
        $this->load->view('reports/balance/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Ajax end-point : execute the balance report
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function executeBalanceReport() {
        $this->auth->checkIfOperationIsAllowed('native_report_balance');
        $this->load->model('leaves_model');
        $this->load->model('types_model');
        $this->load->model('organization_model');
        $result = array();
        $types = $this->types_model->getTypes();
        $this->lang->load('global', $this->language);

        $refDate = date("Y-m-d");
        if (isset($_GET['refDate']) && $_GET['refDate'] != NULL) {
            $refDate = date("Y-m-d", $_GET['refDate']);
        }
        $include_children = filter_var($_GET['children'], FILTER_VALIDATE_BOOLEAN);
        $users = $this->organization_model->allEmployees($_GET['entity'], $include_children);
        foreach ($users as $user) {
            $result[$user->id]['Employee ID'] = $user->id;
            $result[$user->id]['Full Name'] = $user->firstname . ' ' . $user->lastname;


    // Add a check for null or empty employmentdate
    if (!empty($user->employmentdate)) {
        $date = new DateTime($user->employmentdate);
        $result[$user->id]['Employment Date'] = $date->format(lang('global_date_format'));
    } else {
        $result[$user->id]['Employment Date'] = ''; // Or any default value you prefer
    }            $result[$user->id]['Employment Date'] = $date->format(lang('global_date_format'));
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

        $table = '';
        $thead = '';
        $tbody = '';
        $line = 2;
        $i18n = array("id", "firstname", "lastname", "employmentdate", "department", "position", "location", "contract");
        foreach ($result as $row) {
            $index = 1;
            $tbody .= '<tr>';
            foreach ($row as $key => $value) {
                if ($line == 2) {
                    if (in_array($key, $i18n)) {
                        $thead .= '<th>' . lang($key) . '</th>';
                    } else {
                        $thead .= '<th>' . $key . '</th>';
                    }
                }
                $tbody .= '<td>' . $value . '</td>';
                $index++;
            }
            $tbody .= '</tr>';
            $line++;
        }

        //Check if there is any diagnostic alert on balance (LR without entitlments)
        $alerts = $this->leaves_model->detectBalanceProblems();
        if (count($alerts)) {
            $table = "<div class='alert'>" .
                     "<button type='button' class='close' data-dismiss='alert'>&times;</button>" .
                     "<a href='" . base_url() . "admin/diagnostic#balance'>" .
                     "<i class='mdi mdi-alert'></i>" .
                     "&nbsp;Error</a>" .
                     "</div>";
        }
        $table .= '<table class="table table-bordered table-hover">' .
                    '<thead>' .
                        '<tr>' .
                            $thead .
                        '</tr>' .
                    '</thead>' .
                    '<tbody>' .
                        $tbody .
                    '</tbody>' .
                '</table>';
        $this->output->set_output($table);
    }

    /**
     * Export the balance report into Excel
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function exportBalanceReport() {
        $this->auth->checkIfOperationIsAllowed('native_report_balance');
        $this->load->model('leaves_model');
        $this->load->model('types_model');
        $this->load->model('organization_model');
        $data['refDate'] = date("Y-m-d");
        if (isset($_GET['refDate']) && $_GET['refDate'] != NULL) {
            $data['refDate'] = date("Y-m-d", $_GET['refDate']);
        }
        $data['include_children'] = filter_var($_GET['children'], FILTER_VALIDATE_BOOLEAN);
        $this->load->view('reports/balance/export', $data);
    }

    /**
     * Landing page of the shipped-in leaves report
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     * @since 0.4.3
     */
    public function leaves() {
        $this->auth->checkIfOperationIsAllowed('native_report_leaves');
        $data = getUserContext($this);
        $data['title'] = lang('reports_leaves_title');
        $this->load->view('templates/header', $data);
        $this->load->view('menu/index', $data);
        $this->load->view('reports/leaves/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Report leaves request for a month and an entity
     * This report is inspired by the monthly presence report, but applicable to a set of employees.
     * @since 0.4.3
     */
    public function executeLeavesReport() {
        $this->auth->checkIfOperationIsAllowed('native_report_leaves');
        $this->lang->load('leaves', $this->language);

        $month = $this->input->get("month") === FALSE ? 0 : (int) $this->input->get("month");
        $year = $this->input->get("year") === FALSE ? 0 : (int) $this->input->get("year");
        $entity = $this->input->get("entity") === FALSE ? 0 : $this->input->get("entity");
        $children = filter_var($this->input->get("children"), FILTER_VALIDATE_BOOLEAN);
        $requests = filter_var($this->input->get("requests"), FILTER_VALIDATE_BOOLEAN);

        // Compute facts about dates and the selected month
        if ($month == 0) {
            $start = sprintf('%d-01-01', $year);
            $end = sprintf('%d-12-31', $year);
            $total_days = date("z", mktime(0, 0, 0, 12, 31, $year)) + 1;
        } else {
            $start = sprintf('%d-%02d-01', $year, $month);
            $end = date('Y-m-t', strtotime($start));  // More direct and avoids an extra variable
            $total_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        }

        $this->load->model('organization_model');
        $this->load->model('leaves_model');
        $this->load->model('types_model');
        $this->load->model('dayoffs_model');
        $types = $this->types_model->getTypes();

        // Filter out types with id 0
        $filtered_types = array_filter($types, function($type) {
            return $type['id'] != 0;
        });

        // Iterate on all employees of the entity
        $users = $this->organization_model->allEmployees($entity, $children);
        $result = array();
        $leave_requests = array();

        foreach ($users as $user) {
            $result[$user->id]['Employee ID'] = $user->id;
            $result[$user->id]['Full Name'] = $user->firstname . ' ' . $user->lastname;

            
            // Add a check for null or empty employmentdate
            if (!empty($user->employmentdate)) {
                $date = new DateTime($user->employmentdate);
                $result[$user->id]['Employment Date'] = $date->format($this->lang->line('global_date_format'));
            } else {
                $result[$user->id]['Employment Date'] = '';
            }

            $result[$user->id]['Department'] = $user->department;
            $result[$user->id]['Position'] = $user->position;
            $result[$user->id]['Location'] = $user->location;
            $result[$user->id]['Contract'] = $user->contract;
            $non_working_days = $this->dayoffs_model->lengthDaysOffBetweenDates($user->contract_id, $start, $end);
            $opened_days = $total_days - $non_working_days;

            foreach ($filtered_types as $type) {
                $result[$user->id][$type['name']] = 0;  // Initialize with 0 instead of an empty string
            }

            // If the user has selected all months
            $leave_duration = 0;
            if ($month == 0) {
                for ($ii = 1; $ii < 13; $ii++) {
                    $linear = $this->leaves_model->linear($user->id, $ii, $year, FALSE, FALSE, TRUE, FALSE);
                    $leave_duration += $this->leaves_model->monthlyLeavesDuration($linear);
                    $leaves_detail = $this->leaves_model->monthlyLeavesByType($linear);
                    
                    foreach ($filtered_types as $type) {
                        if (array_key_exists($type['name'], $leaves_detail)) {
                            // Check if value in `leaves_detail` is numeric before adding
                            if (is_numeric($leaves_detail[$type['name']])) {
                                $result[$user->id][$type['name']] += $leaves_detail[$type['name']];
                            } else {
                                error_log("Expected a numeric value for leaves detail, got: " . $leaves_detail[$type['name']]);
                                $result[$user->id][$type['name']] = '';
                            }
                        }
                    }
                }
                if ($requests) $leave_requests[$user->id] = $this->leaves_model->getAcceptedLeavesBetweenDates($user->id, $start, $end);
                $work_duration = $opened_days - $leave_duration;
            } else {
                $linear = $this->leaves_model->linear($user->id, $month, $year, FALSE, FALSE, TRUE, FALSE);
                $leave_duration = $this->leaves_model->monthlyLeavesDuration($linear);
                $work_duration = $opened_days - $leave_duration;
                $leaves_detail = $this->leaves_model->monthlyLeavesByType($linear);
                if ($requests) $leave_requests[$user->id] = $this->leaves_model->getAcceptedLeavesBetweenDates($user->id, $start, $end);
                
                // Init type columns
                foreach ($filtered_types as $type) {
                    if (array_key_exists($type['name'], $leaves_detail)) {
                        $result[$user->id][$type['name']] = $leaves_detail[$type['name']];
                    } else {
                        $result[$user->id][$type['name']] = '';
                    }
                }
            }

            $result[$user->id]['Leave Duration'] = $leave_duration;
            $result[$user->id]['Total Days'] = $total_days;
            $result[$user->id]['Weekend & Public Holiday Days'] = $non_working_days;
            $result[$user->id]['Work Days'] = $work_duration;
        }

        $table = '';
        $thead = '';
        $tbody = '';
        $line = 2;
        $i18n = array("id", "firstname", "lastname", "employmentdate", "department", "position", "location", "contract");
        
        foreach ($result as $user_id => $row) {
            $index = 1;
            $tbody .= '<tr>';
            foreach ($row as $key => $value) {
                if ($line == 2) {
                    if (in_array($key, $i18n)) {
                        $thead .= '<th>' . lang($key) . '</th>';
                    } else {
                        $thead .= '<th>' . $key . '</th>';
                    }
                }
                $tbody .= '<td>' . $value . '</td>';
                $index++;
            }
            $tbody .= '</tr>';
            
            // Display a nested table listing the leave requests
            if ($requests) {
                if (count($leave_requests[$user_id])) {
                    $tbody .= '<tr><td colspan="' . count($row) . '">';
                    $tbody .= '<table class="table table-bordered table-hover" style="width: auto !important;">';
                    $tbody .= '<thead><tr>';
                    $tbody .= '<th>' . lang('leaves_index_thead_id'). '</th>';
                    $tbody .= '<th>' . lang('leaves_index_thead_start_date'). '</th>';
                    $tbody .= '<th>' . lang('leaves_index_thead_end_date'). '</th>';
                    $tbody .= '<th>' . lang('leaves_index_thead_type'). '</th>';
                    $tbody .= '<th>' . lang('leaves_index_thead_duration'). '</th>';
                    $tbody .= '</tr></thead>';
                    $tbody .= '<tbody>';
                    
                    // Iterate on leave requests
                    foreach ($leave_requests[$user_id] as $request) {
                        $date = new DateTime($request['startdate']);
                        $startdate = $date->format($this->lang->line('global_date_format'));
                        $date = new DateTime($request['enddate']);
                        $enddate = $date->format($this->lang->line('global_date_format'));
                        $tbody .= '<tr>';
                        $tbody .= '<td><a href="' . base_url() . 'leaves/view/'. $request['id']. '" target="_blank">'. $request['id']. '</a></td>';
                        $tbody .= '<td>'. $startdate . '</td>';
                        $tbody .= '<td>'. $enddate . '</td>';
                        $tbody .= '<td>'. $request['type'] . '</td>';
                        $tbody .= '<td>'. $request['duration'] . '</td>';
                        $tbody .= '</tr>';
                    }
                    $tbody .= '</tbody>';
                    $tbody .= '</table>';
                    $tbody .= '</td></tr>';
                } else {
                    $tbody .= '<tr><td colspan="' . count($row) . '">----</td></tr>';
                }
            }
            $line++;
        }
        $table = '<table class="table table-bordered table-hover">' .
                    '<thead>' .
                        '<tr>' .
                            $thead .
                        '</tr>' .
                    '</thead>' .
                    '<tbody>' .
                        $tbody .
                    '</tbody>' .
                '</table>';
        $this->output->set_output($table);
    }

    /**
     * Export the leaves report into Excel
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     * @since 0.4.3
     */
    public function exportLeavesReport() {
        $this->auth->checkIfOperationIsAllowed('native_report_leaves');
        $this->lang->load('leaves', $this->language);
        $this->load->model('organization_model');
        $this->load->model('leaves_model');
        $this->load->model('types_model');
        $this->load->model('dayoffs_model');
        $data['refDate'] = date("Y-m-d");
        if (isset($_GET['refDate']) && $_GET['refDate'] != NULL) {
            $data['refDate'] = date("Y-m-d", $_GET['refDate']);
        }
        $data['include_children'] = filter_var($_GET['children'], FILTER_VALIDATE_BOOLEAN);
        $this->load->view('reports/leaves/export', $data);
    }
    /**
     * Report leaves request by date range and an entity
     * This report is inspired by the monthly presence report, but applicable to a set of employee.
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     * @since 0.4.3
     */
    public function leavesbydate() {
        $this->auth->checkIfOperationIsAllowed('native_report_leaves');
        $data = getUserContext($this);
        $data['title'] = lang('reports_leaves_title');
        $this->load->view('templates/header', $data);
        $this->load->view('menu/index', $data);
        $this->load->view('reports/leavesbydate/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Landing page of the shipped-in date range leaves report
     * @since 0.4.3
     */
    public function executeLeavesByDateReport() {
        $this->auth->checkIfOperationIsAllowed('native_report_leaves');
        $this->lang->load('leaves', $this->language);

        // Debug: Log loaded language lines
        log_message('debug', 'Loaded language lines: ' . json_encode($this->lang->language));
        
        // Get start and end dates from the input, defaulting to a reasonable range if not provided
        $start_date = $this->input->get("start_date") ? $this->input->get("start_date") : date('Y-m-d');
        $end_date = $this->input->get("end_date") ? $this->input->get("end_date") : date('Y-m-d', strtotime("+1 month", strtotime($start_date)));

        $entity = $this->input->get("entity") === FALSE ? 0 : $this->input->get("entity");
        $children = filter_var($this->input->get("children"), FILTER_VALIDATE_BOOLEAN);
        $requests = filter_var($this->input->get("requests"), FILTER_VALIDATE_BOOLEAN);

        $this->load->model('organization_model');
        $this->load->model('leaves_model');
        $this->load->model('types_model');
        $this->load->model('dayoffs_model');
        $types = $this->types_model->getTypes();

        // Calculate total days between dates
        $date1 = new DateTime($start_date);
        $date2 = new DateTime($end_date);
        $total_days = $date2->diff($date1)->format("%a") + 1;

        $users = $this->organization_model->allEmployees($entity, $children);
        $result = array();
        $leave_requests = array();

        foreach ($users as $user) {
            $result[$user->id]['Employee ID'] = $user->id;
            $result[$user->id]['Full Name'] = $user->firstname . ' ' . $user->lastname;
            $result[$user->id]['Employment Date'] = empty($user->employmentdate) ? '' : (new DateTime($user->employmentdate))->format($this->lang->line('global_date_format'));
            $result[$user->id]['Department'] = $user->department;
            $result[$user->id]['Position'] = $user->position;
            $result[$user->id]['Location'] = $user->location;
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
                if ($type['id'] != 0) {
                    $result[$user->id][$type['name']] = array_key_exists($type['name'], $leaves_detail) ? $leaves_detail[$type['name']] : 0;
                }        
            }

            $result[$user->id]['Leave Duration'] = $leave_duration;
            $result[$user->id]['Total Days'] = $total_days;
            $result[$user->id]['Weekend & Public Holiday Days'] = $non_working_days;
            $result[$user->id]['Work Days'] = $work_duration;
        }

        // Check for any missing language lines before generating the table
        foreach ($this->lang->language as $key => $value) {
            if (empty($key) || empty($value)) {
                log_message('error', 'Missing language line for key: ' . $key);
            }
        }

        // Generate HTML table output
        $table = $this->generateHTMLTable($result, $leave_requests, $requests);

        $this->output->set_output($table);
    }
    

    /**
     * Function to generate html table after execute
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     * @since 0.4.3
     */
    private function generateHTMLTable($result, $leave_requests, $requests) {
        // Start table with responsive and accessible design
        $table = '<div class="table-responsive"><table class="table table-bordered table-hover">';
        $thead = '<thead><tr>';
        $tbody = '<tbody>';
        
        // Flag to ensure the header is added only once
        $headersAdded = false;
    
        foreach ($result as $user_id => $row) {
            if (!$headersAdded) {
                foreach ($row as $key => $value) {
                    $thead .= '<th scope="col">' . lang($key) . '</th>';
                }
                $thead .= '</tr></thead>';
                $headersAdded = true;  // Set flag to true after adding headers
            }
            $tbody .= '<tr>';
            foreach ($row as $key => $value) {
                $tbody .= '<td>' . htmlspecialchars((string) $value) . '</td>';  // Cast value to string
            }
            $tbody .= '</tr>';
            
            // Embed leave requests if available
            if ($requests && isset($leave_requests[$user_id]) && count($leave_requests[$user_id])) {
                $tbody .= $this->embedLeaveRequests($leave_requests[$user_id]);
            }
        }
        $tbody .= '</tbody></table></div>';
        
        // Combine parts to form the final table
        $table .= $thead . $tbody;
        return $table;
    }

    /**
     * Private function to embed leave request into the table
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     * @since 0.4.3
     */
    private function embedLeaveRequests($leave_requests) {
        // Generate nested HTML table for leave requests within a collapsible panel
        $tbody = '<tr><td colspan="9"><div class="table-responsive"><table class="table table-bordered table-hover">';
        $tbody .= '<thead><tr><th>ID</th><th>Start Date</th><th>End Date</th><th>Type</th><th>Duration</th></tr></thead><tbody>';
        
        foreach ($leave_requests as $request) {
            $tbody .= '<tr>';
            $tbody .= '<td><a href="' . base_url() . 'leaves/view/' . $request['id'] . '" target="_blank" aria-label="View details for leave ID ' . htmlspecialchars($request['id']) . '">' . htmlspecialchars($request['id']) . '</a></td>';
            $tbody .= '<td>' . htmlspecialchars(date("d M Y", strtotime($request['startdate']))) . '</td>';
            $tbody .= '<td>' . htmlspecialchars(date("d M Y", strtotime($request['enddate']))) . '</td>';
            $tbody .= '<td>' . htmlspecialchars($request['type']) . '</td>';
            $tbody .= '<td>' . htmlspecialchars($request['duration']) . ' days</td>';
            $tbody .= '</tr>';
        }
        
        $tbody .= '</tbody></table></div></td></tr>';
        return $tbody;
    }        
    /**
     * Export the date range leaves report into Excel
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     * @since 0.4.3
     */
    public function exportLeavesByDateReport() {
        $this->auth->checkIfOperationIsAllowed('native_report_leaves');
        $this->lang->load('leaves', $this->language);
        $this->load->model('organization_model');
        $this->load->model('leaves_model');
        $this->load->model('types_model');
        $this->load->model('dayoffs_model');
        $data['refDate'] = date("Y-m-d");
        if (isset($_GET['refDate']) && $_GET['refDate'] != NULL) {
            $data['refDate'] = date("Y-m-d", $_GET['refDate']);
        }
        $data['include_children'] = filter_var($_GET['children'], FILTER_VALIDATE_BOOLEAN);
        $this->load->view('reports/leavesbydate/export', $data);
    }
}
