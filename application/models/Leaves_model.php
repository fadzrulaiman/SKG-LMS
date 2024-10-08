<?php
/**
 * This Model contains all the business logic and the persistence layer for leave request objects.
 * @copyright  Copyright (c) Fadzrul Aiman

 * @since         0.1.0
 */

if (!defined('BASEPATH')) { exit('No direct script access allowed'); }

/**
 * This class contains the business logic and manages the persistence of leave requests.
 */
class Leaves_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {

    }

    /**
     * Get the list of all leave requests or one leave
     * @param int $id Id of the leave request
     * @return array list of records
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function getLeaves($id = 0) {
        $this->db->select('leaves.*');
        $this->db->select('status.name as status_name, types.name as type_name');
        $this->db->from('leaves');
        $this->db->join('status', 'leaves.status = status.id');
        $this->db->join('types', 'leaves.type = types.id');
        if ($id === 0) {
            return $this->db->get()->result_array();
        }
        $this->db->where('leaves.id', $id);
        return $this->db->get()->row_array();
    }

    /**
     * Get the the list of leaves requested for a given employee
     * Id are replaced by label
     * @param int $employee ID of the employee
     * @return array list of records
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function getLeavesOfEmployee($employee) {
        $this->db->select('leaves.*');
        $this->db->select('status.id as status, status.name as status_name');
        $this->db->select('types.name as type_name');
        $this->db->from('leaves');
        $this->db->join('status', 'leaves.status = status.id');
        $this->db->join('types', 'leaves.type = types.id');
        $this->db->where('leaves.employee', $employee);
        $this->db->order_by('leaves.id', 'desc');
        return $this->db->get()->result_array();
    }

    /**
     * Get the list of history of an employee
     * @param int $employee Id of the employee
     * @return array list of records
     * @author Emilien NICOLAS <milihhard1996@gmail.com>
     */
    public function getLeavesOfEmployeeWithHistory($employee){
      $employee = intval($employee);
      return $this->db->query("SELECT leaves.*, status.name as status_name, types.name as type_name, lastchange.date as change_date, requested.date as request_date
        FROM `leaves`
        inner join status ON leaves.status = status.id
        inner join types ON leaves.type = types.id
        left outer join (
          SELECT id, MAX(change_date) as date
          FROM leaves_history
          GROUP BY id
        ) lastchange ON leaves.id = lastchange.id
        left outer join (
          SELECT id, MIN(change_date) as date
          FROM leaves_history
          WHERE leaves_history.status = 2
          GROUP BY id
        ) requested ON leaves.id = requested.id
        WHERE leaves.employee = $employee")->result_array();
    }

    /**
     * Return a list of Accepted leaves between two dates and for a given employee
     * @param int $employee ID of the employee
     * @param string $start Start date
     * @param string $end End date
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function getAcceptedLeavesBetweenDates($employee, $start, $end) {
        $this->db->select('leaves.*, types.name as type');
        $this->db->from('leaves');
        $this->db->join('status', 'leaves.status = status.id');
        $this->db->join('types', 'leaves.type = types.id');
        $this->db->where('employee', $employee);
        $this->db->where("(startdate <= STR_TO_DATE('" . $end . "', '%Y-%m-%d') AND enddate >= STR_TO_DATE('" . $start . "', '%Y-%m-%d'))");
        $this->db->where('leaves.status', LMS_ACCEPTED);
        $this->db->order_by('startdate', 'asc');
        return $this->db->get()->result_array();
    }

    /**
     * Try to calculate the length of a leave using the start and and date of the leave
     * and the non working days defined on a contract
     * @param int $employee Identifier of the employee
     * @param date $start start date of the leave request
     * @param date $end end date of the leave request
     * @param string $startdatetype start date type of leave request being created (Morning or Afternoon)
     * @param string $enddatetype end date type of leave request being created (Morning or Afternoon)
     * @return float length of leave
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function length($employee, $start, $end, $startdatetype, $enddatetype) {
        $this->db->select('sum(CASE `type` WHEN 1 THEN 1 WHEN 2 THEN 0.5 WHEN 3 THEN 0.5 END) as days');
        $this->db->from('users');
        $this->db->join('dayoffs', 'users.contract = dayoffs.contract');
        $this->db->where('users.id', $employee);
        $this->db->where('date >=', $start);
        $this->db->where('date <=', $end);
        $result = $this->db->get()->result_array();
        $startTimeStamp = strtotime($start." UTC");
        $endTimeStamp = strtotime($end." UTC");
        $timeDiff = abs($endTimeStamp - $startTimeStamp);
        $numberDays = $timeDiff / 86400;  // 86400 seconds in one day
        if (count($result) != 0) { //Test if some non working days are defined on a contract
            return $numberDays - $result[0]['days'];
        } else {
            //Special case when the leave request is half a day long,
            //we assume that the non-working day is not at the same time than the leave request
            if ($startdatetype == $enddatetype) {
                return 0.5;
            } else {
                return $numberDays;
            }
        }
    }

    /**
     * Calculate the actual length of a leave request by taking into account the non-working days
     * Detect overlapping with non-working days. It returns a K/V arrays of 3 items.
     * @param int $employee Identifier of the employee
     * @param date $startdate start date of the leave request
     * @param date $enddate end date of the leave request
     * @param string $startdatetype start date type of leave request being created (Morning or Afternoon)
     * @param string $enddatetype end date type of leave request being created (Morning or Afternoon)
     * @param array $daysoff List of non-working days
     * @param bool $deductDayOff Deduct days off when evaluating the actual length
     * @return array (length=>length of leave, overlapping=>excat match with a non-working day, daysoff=>sum of days off)
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function actualLengthAndDaysOff($employee, $startdate, $enddate,
            $startdatetype, $enddatetype, $daysoff, $deductDayOff = FALSE) {
        $startDateObject = DateTime::createFromFormat('Y-m-d H:i:s', $startdate . ' 00:00:00');
        $endDateObject = DateTime::createFromFormat('Y-m-d H:i:s', $enddate . ' 00:00:00');
        $iDate = clone $startDateObject;

        //Simplify the reading (and logic) by decomposing into atomic variables
        if ($startdate == $enddate) $oneDay = TRUE; else $oneDay = FALSE;
        if ($startdatetype == 'Morning') $start_morning = TRUE; else $start_morning = FALSE;
        if ($startdatetype == 'Afternoon') $start_afternoon = TRUE; else $start_afternoon = FALSE;
        if ($enddatetype == 'Morning') $end_morning = TRUE; else $end_morning = FALSE;
        if ($enddatetype == 'Afternoon') $end_afternoon = TRUE; else $end_afternoon = FALSE;

        //Iteration between start and end dates of the leave request
        $lengthDaysOff = 0;
        $length = 0;
        $hasDayOff = FALSE;
        $overlapDayOff = FALSE;
        while ($iDate <= $endDateObject)
        {
            if ($iDate == $startDateObject) $first_day = TRUE; else $first_day = FALSE;
            $isDayOff = FALSE;
            //Iterate on the list of days off with two objectives:
            // - Compute sum of days off between the two dates
            // - Detect if the leave request exactly overlaps with a day off
            foreach ($daysoff as $dayOff) {
                $dayOffObject = DateTime::createFromFormat('Y-m-d H:i:s', $dayOff['date'] . ' 00:00:00');
                if ($dayOffObject == $iDate) {
                    $lengthDaysOff+=$dayOff['length'];
                    $isDayOff = TRUE;
                    $hasDayOff = TRUE;
                    switch ($dayOff['type']) {
                        case 1: //1 : All day
                            if ($oneDay && $start_morning && $end_afternoon && $first_day)
                                $overlapDayOff = TRUE;
                                if ($deductDayOff) $length++;
                            break;
                        case 2: //2 : Morning
                            if ($oneDay && $start_morning && $end_morning && $first_day)
                                $overlapDayOff = TRUE;
                            else
                                if ($deductDayOff) $length++; else $length+=0.5;
                            break;
                        case 3: //3 : Afternnon
                            if ($oneDay && $start_afternoon && $end_afternoon && $first_day)
                                $overlapDayOff = TRUE;
                            else
                                if ($deductDayOff) $length++; else $length+=0.5;
                            break;
                        default:
                            break;
                    }
                    break;
                }
            }
            if (!$isDayOff) {
                if ($oneDay) {
                    if ($start_morning && $end_afternoon) $length++;
                    if ($start_morning && $end_morning) $length+=0.5;
                    if ($start_afternoon && $end_afternoon) $length+=0.5;
                } else {
                    if ($iDate == $endDateObject) $last_day = TRUE; else $last_day = FALSE;
                    if (!$first_day && !$last_day) $length++;
                    if ($first_day && $start_morning) $length++;
                    if ($first_day && $start_afternoon) $length+=0.5;
                    if ($last_day && $end_morning) $length+=0.5;
                    if ($last_day && $end_afternoon) $length++;
                }
                $overlapDayOff = FALSE;
            }
            $iDate->modify('+1 day');   //Next day
        }

        //Other obvious cases of overlapping
        if ($hasDayOff && ($length == 0)) {
            $overlapDayOff = TRUE;
        }
        return array('length' => $length, 'daysoff' => $lengthDaysOff, 'overlapping' => $overlapDayOff);
    }

    /**
     * Get all entitled days applicable to the reference date (to contract and employee)
     * Compute Min and max date by type
     * @param int $employee Employee identifier
     * @param int $contract contract identifier
     * @param string $refDate Date of execution
     * @return array Array of entitled days associated to the key type id
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function getSumEntitledDays($employee, $contract, $refDate) {
        $this->db->select('types.id as type_id, types.name as type_name');
        $this->db->select('SUM(entitleddays.days) as entitled');
        $this->db->select('MIN(startdate) as min_date');
        $this->db->select('MAX(enddate) as max_date');
        $this->db->from('entitleddays');
        $this->db->join('types', 'types.id = entitleddays.type');
        $this->db->group_by('types.id');
        $this->db->where('entitleddays.startdate <= ', $refDate);
        $this->db->where('entitleddays.enddate >= ', $refDate);
        $where = ' (entitleddays.contract=' . $contract .
                       ' OR entitleddays.employee=' . $employee . ')';
        $this->db->where($where, NULL, FALSE);   //Not very safe, but can't do otherwise
        $results = $this->db->get()->result_array();
        //Create an associated array have the leave type as key
        $entitled_days = array();
        foreach ($results as $result) {
            $entitled_days[$result['type_id']] = $result;
        }
        return $entitled_days;
    }

    /**
     * Compute the leave balance of an employee (used by report and counters)
     * @param int $id ID of the employee
     * @param bool $sum_extra TRUE: sum compensate summary
     * @param string $refDate tmp of the Date of reference (or current date if NULL)
     * @return array computed aggregated taken/entitled leaves
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function getLeaveBalanceForEmployee($id, $sum_extra = FALSE, $refDate = NULL) {
        //Determine if we use current date or another date
        if ($refDate == NULL) {
            $refDate = date("Y-m-d");
        }

        //Compute the current leave period and check if the user has a contract
        $this->load->model('contracts_model');
        $hasContract = $this->contracts_model->getBoundaries($id, $startentdate, $endentdate, $refDate);
        if ($hasContract) {
            $this->load->model('types_model');
            $this->load->model('users_model');
            //Fill a list of all existing leave types
            $summary = array();
            $types = $this->types_model->getTypes();
            foreach ($types as $type) {
                $summary[$type['name']][0] = 0; //Taken
                $summary[$type['name']][1] = 0; //Entitled
                $summary[$type['name']][2] = ''; //Description
            }

            //Get the sum of entitled days
            $user = $this->users_model->getUsers($id);
            $entitlements = $this->getSumEntitledDays($id, $user['contract'], $refDate);

            foreach ($entitlements as $entitlement) {
                //Get the total of taken leaves grouped by type
                $this->db->select('SUM(leaves.duration) as taken, types.name as type');
                $this->db->from('leaves');
                $this->db->join('types', 'types.id = leaves.type');
                $this->db->where('leaves.employee', $id);
                $this->db->where_in('leaves.status', array(LMS_ACCEPTED, LMS_CANCELLATION));
                $this->db->where('leaves.startdate >= ', $entitlement['min_date']);
                $this->db->where('leaves.enddate <=', $entitlement['max_date']);
                $this->db->where('leaves.type', $entitlement['type_id']);
                $this->db->group_by("leaves.type");
                $taken_days = $this->db->get()->result_array();
                //Count the number of taken days
                foreach ($taken_days as $taken) {
                    $summary[$taken['type']][0] = (float) $taken['taken']; //Taken
                }
                //Report the number of available days
                $summary[$entitlement['type_name']][3] = $entitlement['type_id'];
                $summary[$entitlement['type_name']][1] = (float) $entitlement['entitled'];
            }
            
            //List all planned leaves in a third column
            //planned leave requests are not deducted from credit
            foreach ($entitlements as $entitlement) {
                //Get the total of taken leaves grouped by type
                $this->db->select('SUM(leaves.duration) as planned, types.name as type');
                $this->db->from('leaves');
                $this->db->join('types', 'types.id = leaves.type');
                $this->db->where('leaves.employee', $id);
                $this->db->where('leaves.status', LMS_PLANNED);
                $this->db->where('leaves.startdate >= ', $entitlement['min_date']);
                $this->db->where('leaves.enddate <=', $entitlement['max_date']);
                $this->db->where('leaves.type', $entitlement['type_id']);
                $this->db->group_by("leaves.type");
                $planned_days = $this->db->get()->result_array();
                //Count the number of planned days
                foreach ($planned_days as $planned) {
                    $summary[$planned['type']][3] = $entitlement['type_id'];
                    $summary[$planned['type']][4] = (float) $planned['planned']; //Planned
                    $summary[$planned['type']][2] = 'x'; //Planned
                }
                //Report the number of available days
                $summary[$entitlement['type_name']][1] = (float) $entitlement['entitled'];
            }

            // List all requested leaves in a fourth column
            // Leave requests having a requested status are not deducted from credit
            foreach ($entitlements as $entitlement) {
                // Get the total of taken leaves grouped by type
                $this->db->select('SUM(leaves.duration) as requested, types.name as type');
                $this->db->from('leaves');
                $this->db->join('types', 'types.id = leaves.type');
                $this->db->where('leaves.employee', $id);
                $this->db->where_in('leaves.status', [LMS_REQUESTED, LMS_REQUESTEDBANK]); 
                $this->db->where('leaves.startdate >=', $entitlement['min_date']);
                $this->db->where('leaves.enddate <=', $entitlement['max_date']);
                $this->db->where('leaves.type', $entitlement['type_id']);
                $this->db->group_by('leaves.type');
                $requested_days = $this->db->get()->result_array();
                
                // Count the number of planned days
                foreach ($requested_days as $requested) {
                    $summary[$requested['type']][3] = $entitlement['type_id'];
                    $summary[$requested['type']][5] = (float) $requested['requested']; // requested
                    $summary[$requested['type']][2] = 'x'; // requested
                }
                
                // Report the number of available days
                $summary[$entitlement['type_name']][1] = (float) $entitlement['entitled'];
            }

            //Remove all lines having taken and entitled set to set to 0
            foreach ($summary as $key => $value) {
                if ($value[0]==0 && $value[1]==0  && $value[2]!='x') {
                    unset($summary[$key]);
                }
            }
            return $summary;
        } else { //User attached to no contract
            return NULL;
        }
    }
    public function HRgetLeaveBalanceForEmployee($id, $sum_extra = FALSE, $refDate = NULL) {
        // Determine if we use current date or another date
        if ($refDate == NULL) {
            $refDate = date("Y-m-d");
        }
    
        // Compute the current leave period and check if the user has a contract
        $this->load->model('contracts_model');
        $hasContract = $this->contracts_model->getBoundaries($id, $startentdate, $endentdate, $refDate);
        if ($hasContract) {
            $this->load->model('types_model');
            $this->load->model('users_model');
            // Fill a list of all existing leave types
            $summary = array();
            $types = $this->types_model->getTypes();
            foreach ($types as $type) {
                $summary[$type['name']][0] = 0; // Taken
                $summary[$type['name']][1] = 0; // Entitled
                $summary[$type['name']][2] = ''; // Description
            }
    
            // Get the sum of entitled days
            $user = $this->users_model->getUsers($id);
            $entitlements = $this->getSumEntitledDays($id, $user['contract'], $refDate);
    
            foreach ($entitlements as $entitlement) {
                // Get the total of taken leaves grouped by type
                $this->db->select('SUM(leaves.duration) as taken, types.name as type');
                $this->db->from('leaves');
                $this->db->join('types', 'types.id = leaves.type');
                $this->db->where('leaves.employee', $id);
                $this->db->where_in('leaves.status', array(LMS_ACCEPTED, LMS_CANCELLATION));
                $this->db->where('leaves.startdate >= ', $entitlement['min_date']);
                $this->db->where('leaves.enddate <=', $entitlement['max_date']);
                $this->db->where('leaves.type', $entitlement['type_id']);
                $this->db->group_by("leaves.type");
                $taken_days = $this->db->get()->result_array();
                // Count the number of taken days
                foreach ($taken_days as $taken) {
                    $summary[$taken['type']][0] = (float) $taken['taken']; // Taken
                }
                // Report the number of available days
                $summary[$entitlement['type_name']][3] = $entitlement['type_id'];
                $summary[$entitlement['type_name']][1] = (float) $entitlement['entitled'];
            }
            
            // List all planned leaves in a third column
            // planned leave requests are not deducted from credit
            foreach ($entitlements as $entitlement) {
                // Get the total of taken leaves grouped by type
                $this->db->select('SUM(leaves.duration) as planned, types.name as type');
                $this->db->from('leaves');
                $this->db->join('types', 'types.id = leaves.type');
                $this->db->where('leaves.employee', $id);
                $this->db->where('leaves.status', LMS_PLANNED);
                $this->db->where('leaves.startdate >= ', $entitlement['min_date']);
                $this->db->where('leaves.enddate <=', $entitlement['max_date']);
                $this->db->where('leaves.type', $entitlement['type_id']);
                $this->db->group_by("leaves.type");
                $planned_days = $this->db->get()->result_array();
                // Count the number of planned days
                foreach ($planned_days as $planned) {
                    $summary[$planned['type']][3] = $entitlement['type_id'];
                    $summary[$planned['type']][4] = (float) $planned['planned']; // Planned
                    $summary[$planned['type']][2] = 'x'; // Planned
                }
                // Report the number of available days
                $summary[$entitlement['type_name']][1] = (float) $entitlement['entitled'];
            }
    
            // List all requested leaves in a fourth column
            // leave requests having a requested status are not deducted from credit
            foreach ($entitlements as $entitlement) {
                // Get the total of taken leaves grouped by type
                $this->db->select('SUM(leaves.duration) as requested, types.name as type');
                $this->db->from('leaves');
                $this->db->join('types', 'types.id = leaves.type');
                $this->db->where('leaves.employee', $id);
                $this->db->where('leaves.status', LMS_REQUESTED);
                $this->db->where('leaves.startdate >= ', $entitlement['min_date']);
                $this->db->where('leaves.enddate <=', $entitlement['max_date']);
                $this->db->where('leaves.type', $entitlement['type_id']);
                $this->db->group_by("leaves.type");
                $requested_days = $this->db->get()->result_array();
                // Count the number of requested days
                foreach ($requested_days as $requested) {
                    $summary[$requested['type']][3] = $entitlement['type_id'];
                    $summary[$requested['type']][5] = (float) $requested['requested']; // requested
                    $summary[$requested['type']][2] = 'x'; // requested
                }
                // Report the number of available days
                $summary[$entitlement['type_name']][1] = (float) $entitlement['entitled'];
            }
    
            // Remove all lines having taken and entitled set to set to 0
            foreach ($summary as $key => $value) {
                if ($value[0] == 0 && $value[1] == 0  && $value[2] != 'x') {
                    unset($summary[$key]);
                }
            }
            return $summary;
        } else { // User attached to no contract
            return NULL;
        }
    }
        

    /**
     * Get the number of days a user can take for a given leave type
     * @param int $id employee identifier
     * @param string $type leave type name
     * @param date $startdate Start date of leave request or null
     * @return int number of available days or NULL if the user has no contract
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function getLeavesTypeBalanceForEmployee($id, $type, $startdate = NULL) {
        $summary = $this->getLeaveBalanceForEmployee($id, TRUE, $startdate);
        //return entitled days - taken (for a given leave type)
        if (is_null($summary)) {
            return NULL;
        } else {
            if (array_key_exists($type, $summary)) {
                return ($summary[$type][1] - $summary[$type][0]);
            } else {
                return 0;
            }
        }
    }

/**
 * Retrieve leave balances for each type for a specific employee, excluding type_id 0.
 * @param int $employeeId Employee identifier
 * @return array List of leave balances per type
 */
public function getLeaveBalanceForAllTypes($employeeId) {
    // Ensure employee ID is provided
    if (!isset($employeeId) || empty($employeeId)) {
        return []; // Return an empty array if the employee ID is not specified
    }

    // Get the current year
    $currentYear = date('Y');

    // Create a subquery to calculate entitled days for the current year, accounting for both employee and contract matching
    $entitledDaysSubquery = $this->db->select('e.type, FLOOR(SUM(e.days)) AS entitled')
        ->from('entitleddays e')
        ->join('users u', 'u.id = e.employee OR (e.employee IS NULL AND u.contract = e.contract)', 'left')
        ->where('u.id', (int)$employeeId)  // Ensuring we are looking at the right user
        ->where("YEAR(e.startdate) =", $currentYear)
        ->where("YEAR(e.enddate) =", $currentYear)
        ->group_by('e.type')
        ->get_compiled_select();

    // Main query to calculate the leave balance per type for the current year
    $this->db->select('
        t.id AS type_id,
        t.name AS type_name,
        COALESCE(ed.entitled, 0) AS entitled,
        FLOOR(COALESCE(SUM(IF(l.status IN (2, 3), l.duration, 0)), 0)) AS taken,
        (COALESCE(ed.entitled, 0) - FLOOR(COALESCE(SUM(IF(l.status IN (2, 3), l.duration, 0)), 0))) AS balance
    ')
        ->from('types t')
        ->join("($entitledDaysSubquery) ed", 't.id = ed.type', 'left')
        ->join('leaves l', 'l.employee = ' . (int)$employeeId . ' AND l.type = t.id AND YEAR(l.startdate) = ' . $currentYear . ' AND l.status IN (2, 3)', 'left')
        ->where('t.id !=', 0)
        ->group_by('t.id, t.name, ed.entitled')
        ->order_by('t.id');

    // Execute the query and return results
    $query = $this->db->get();
    return $query->result_array();
}




    /**
     * Detect if the leave request overlaps with another request of the employee
     * @param int $id employee id
     * @param date $startdate start date of leave request being created
     * @param date $enddate end date of leave request being created
     * @param string $startdatetype start date type of leave request being created (Morning or Afternoon)
     * @param string $enddatetype end date type of leave request being created (Morning or Afternoon)
     * @param int $leave_id When this function is used for editing a leave request, we must not collide with this leave request
     * @return boolean TRUE if another leave request has been emmitted, FALSE otherwise
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function detectOverlappingLeaves($id, $startdate, $enddate, $startdatetype, $enddatetype, $leave_id=NULL) {
        $overlapping = FALSE;
        $this->db->where('employee', $id);
        $this->db->where_not_in('status', array(6, 4));
        $this->db->where('(startdate <= DATE(\'' . $enddate . '\') AND enddate >= DATE(\'' . $startdate . '\'))');
        if (!is_null($leave_id)) {
            $this->db->where('id != ', $leave_id);
        }
        $leaves = $this->db->get('leaves')->result();

        if ($startdatetype == "Morning") {
            $startTmp = strtotime($startdate." 08:00:00 UTC");
        } else {
            $startTmp = strtotime($startdate." 12:01:00 UTC");
        }
        if ($enddatetype == "Morning") {
            $endTmp = strtotime($enddate." 12:00:00 UTC");
        } else {
            $endTmp = strtotime($enddate." 18:00:00 UTC");
        }

        foreach ($leaves as $leave) {
            if ($leave->startdatetype == "Morning") {
                $startTmpDB = strtotime($leave->startdate." 08:00:00 UTC");
            } else {
                $startTmpDB = strtotime($leave->startdate." 12:01:00 UTC");
            }
            if ($leave->enddatetype == "Morning") {
                $endTmpDB = strtotime($leave->enddate." 12:00:00 UTC");
            } else {
                $endTmpDB = strtotime($leave->enddate." 18:00:00 UTC");
            }
            if (($startTmpDB <= $endTmp) && ($endTmpDB >= $startTmp)) {
                $overlapping = TRUE;
            }
        }
        return $overlapping;
    }

/**
 * Create a leave request with an attachment
 * @param int $employeeId Identifier of the employee
 * @return int ID of the newly created leave request in the database
 * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
 */
public function setLeaves($employeeId, $attachmentPath) {
    // Capture leave request data
    $data = array(
        'startdate' => $this->input->post('startdate'),
        'startdatetype' => $this->input->post('startdatetype'),
        'enddate' => $this->input->post('enddate'),
        'enddatetype' => $this->input->post('enddatetype'),
        'duration' => abs($this->input->post('duration')),
        'type' => $this->input->post('type'),
        'cause' => $this->input->post('cause'),
        'status' => $this->input->post('status'),
        'employee' => $employeeId,
        'attachment' => $attachmentPath // Add attachment path to data array
    );

    // Insert the leave request into the database
    $this->db->insert('leaves', $data);
    $newId = $this->db->insert_id();

    // Trace the modification if the feature is enabled
    if ($this->config->item('enable_history') === TRUE) {
        $this->load->model('history_model');
        $this->history_model->setHistory(1, 'leaves', $newId, $employeeId);
    }

    return $newId;
}

/**
 * Create the same leave request for a list of employees
 * @param int $type Identifier of the leave type
 * @param float $duration duration of the leave
 * @param string $startdate Start date (MySQL format YYYY-MM-DD)
 * @param string $enddate End date (MySQL format YYYY-MM-DD)
 * @param string $startdatetype Start date type of the leave (Morning/Afternoon)
 * @param string $enddatetype End date type of the leave (Morning/Afternoon)
 * @param string $cause Identifier of the leave
 * @param int $status status of the leave
 * @param array $employees List of DB Ids of the affected employees
 * @param string $attachment_path Path to the attachment file
 * @return int Result
 * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
 */
public function createRequestForUserList($type, $duration, $startdate, $enddate, $startdatetype, $enddatetype, $cause, $status, $employees, $attachment_path) {
    $affectedRows = 0;
    if ($this->config->item('enable_history') === TRUE) {
        foreach ($employees as $id) {
            $this->createLeaveByApi($this->input->post('startdate'),
                    $this->input->post('enddate'),
                    $this->input->post('status'),
                    $id,
                    $this->input->post('cause'),
                    $this->input->post('startdatetype'),
                    $this->input->post('enddatetype'),
                    abs($this->input->post('duration')),
                    $this->input->post('type'),
                    $attachment_path); // Pass attachment path to createLeaveByApi method
            $affectedRows++;
        }
    } else {
        $data = array();
        foreach ($employees as $id) {
            $data[] = array(
                'startdate' => $this->input->post('startdate'),
                'startdatetype' => $this->input->post('startdatetype'),
                'enddate' => $this->input->post('enddate'),
                'enddatetype' => $this->input->post('enddatetype'),
                'duration' => abs($this->input->post('duration')),
                'type' => $this->input->post('type'),
                'cause' => $this->input->post('cause'),
                'status' => $this->input->post('status'),
                'employee' => $id,
                'attachment' => $attachment_path // Include attachment path in data array
            );
        }
        $affectedRows = $this->db->insert_batch('leaves', $data);
    }
    return $affectedRows;
}

    /**
     * Create a leave request (suitable for API use)
     * @param string $startdate Start date (MySQL format YYYY-MM-DD)
     * @param string $enddate End date (MySQL format YYYY-MM-DD)
     * @param int $status Status of leave (see table status or doc)
     * @param int $employee Identifier of the employee
     * @param string $cause Optional reason of the leave
     * @param string $startdatetype Start date type (Morning/Afternoon)
     * @param string $enddatetype End date type (Morning/Afternoon)
     * @param float $duration duration of the leave request
     * @param int $type Type of leave (except compensate, fully customizable by user)
     * @param string $comments (optional) JSON encoded comment
     * @param string $document Base64 encoded document
     * @return int id of the newly acreated leave request into the db
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function createLeaveByApi($startdate, $enddate, $status, $employee, $cause,
            $startdatetype, $enddatetype, $duration, $type,
            $comments = NULL,
            $document = NULL) {

        $data = array(
            'startdate' => $startdate,
            'enddate' => $enddate,
            'status' => $status,
            'employee' => $employee,
            'cause' => $cause,
            'startdatetype' => $startdatetype,
            'enddatetype' => $enddatetype,
            'duration' => abs($duration),
            'type' => $type
        );
        if (!empty($comments)) $data['comments'] = $comments;
        if (!empty($document)) $data['document'] = $document;
        $this->db->insert('leaves', $data);
        $newId = $this->db->insert_id();

        //Trace the modification if the feature is enabled
        if ($this->config->item('enable_history') === TRUE) {
            $this->load->model('history_model');
            $this->history_model->setHistory(1, 'leaves', $newId, $this->session->userdata('id'));
        }
        return $newId;
    }

/**
 * Update a leave request in the database with the values posted by an HTTP POST
 * @param int $leaveId Identifier of the leave request
 * @param string $attachment_path Path to the attachment file
 * @param int $userId Identifier of the user (optional)
 * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
 */
public function updateLeaves($leaveId, $attachment_path = '', $userId = 0) {
    if ($userId == 0) {
        $userId = $this->session->userdata('id');
    }
    
    // Prepare comment on status change
    $json = $this->prepareCommentOnStatusChanged($leaveId, $this->input->post('status'));
    
    // Handle comment addition
    if ($this->input->post('comment') != NULL) {
        $jsonDecode = json_decode($json);
        $commentObject = new stdClass;
        $commentObject->type = "comment";
        $commentObject->author = $userId;
        $commentObject->value = $this->input->post('comment');
        $commentObject->date = date("Y-n-j");
        
        // Update comments array
        if (isset($jsonDecode)) {
            array_push($jsonDecode->comments, $commentObject);
        } else {
            $jsonDecode->comments = array($commentObject);
        }
        
        $json = json_encode($jsonDecode);
    }
    
    // Prepare data for update
    $data = array(
        'startdate' => $this->input->post('startdate'),
        'startdatetype' => $this->input->post('startdatetype'),
        'enddate' => $this->input->post('enddate'),
        'enddatetype' => $this->input->post('enddatetype'),
        'duration' => abs($this->input->post('duration')),
        'type' => $this->input->post('type'),
        'cause' => $this->input->post('cause'),
        'status' => $this->input->post('status'),
        'comments' => $json
    );

    // Add attachment path to data if provided
    if (!empty($attachment_path)) {
        $data['attachment'] = $attachment_path;
    }

    // Perform database update
    $this->db->where('id', $leaveId);
    $this->db->update('leaves', $data);

    // Trace the modification if the feature is enabled
    if ($this->config->item('enable_history') === TRUE) {
        $this->load->model('history_model');
        $this->history_model->setHistory(2, 'leaves', $leaveId, $userId);
    }
}

    /**
     * Delete a leave from the database
     * @param int $leaveId leave request identifier
     * @param int $userId Identifier of the user (optional)
     * @return int number of affected rows
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function deleteLeave($leaveId, $userId = 0) {
        //Trace the modification if the feature is enabled
        if ($this->config->item('enable_history') === TRUE) {
            if ($userId == 0) {
                $userId = $this->session->userdata('id');
            }
            $this->load->model('history_model');
            $this->history_model->setHistory(3, 'leaves', $leaveId, $userId);
        }
        return $this->db->delete('leaves', array('id' => $leaveId));
    }

    /**
     * Switch the status of a leave request. You may use one of the constants
     * listed into config/constants.php
     * @param int $id leave request identifier
     * @param int $status Next Status
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function switchStatus($id, $status) {
        $json = $this->prepareCommentOnStatusChanged($id, $status);
        $data = array(
            'status' => $status,
            'comments' => $json
        );
        $this->db->where('id', $id);
        $this->db->update('leaves', $data);

        //Trace the modification if the feature is enabled
        if ($this->config->item('enable_history') === TRUE) {
            $this->load->model('history_model');
            $this->history_model->setHistory(2, 'leaves', $id, $this->session->userdata('id'));
        }
    }

    /**
     * Switch the status of a leave request and a comment. You may use one of the constants
     * listed into config/constants.php
     * @param int $id leave request identifier
     * @param int $status Next Status
     * @param int $comment New comment
     * @author Emilien NICOLAS <milihhard1996@gmail.com>
     */
    public function switchStatusAndComment($id, $status, $comment) {
        $json_parsed = $this->getCommentsLeave($id);
        $commentObject = new stdClass;
        $commentObject->type = "comment";
        $commentObject->author = $this->session->userdata('id');
        $commentObject->value = $comment;
        $commentObject->date = date("Y-n-j");
        if (isset($json_parsed)){
          array_push($json_parsed->comments, $commentObject);
        }else {
            $json_parsed = new stdClass;
            $json_parsed->comments = array($commentObject);
        }
        $comment_change = new stdClass;
        $comment_change->type = "change";
        $comment_change->status_number = $status;
        $comment_change->date = date("Y-n-j");
        if (isset($json_parsed)){
          array_push($json_parsed->comments, $comment_change);
        }else {
          $json_parsed->comments = array($comment_change);
        }
        $json = json_encode($json_parsed);
        $data = array(
            'status' => $status,
            'comments' => $json
        );
        $this->db->where('id', $id);
        $this->db->update('leaves', $data);

        //Trace the modification if the feature is enabled
        if ($this->config->item('enable_history') === TRUE) {
            $this->load->model('history_model');
            $this->history_model->setHistory(2, 'leaves', $id, $this->session->userdata('id'));
        }
    }

    /**
     * Delete leaves attached to a user
     * @param int $employee identifier of an employee
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function deleteLeavesCascadeUser($employee) {
        //Select the leaves of a users (if history feature is enabled)
        if ($this->config->item('enable_history') === TRUE) {
            $this->load->model('history_model');
            $leaves = $this->getLeavesOfEmployee($employee);
            //TODO in fact, should we cascade delete ?
            foreach ($leaves as $leave) {
                $this->history_model->setHistory(3, 'leaves', $leave['id'], $this->session->userdata('id'));
            }
        }
        return $this->db->delete('leaves', array('employee' => $employee));
    }

    /**
     * Leave requests of All leave request of the user (suitable for FullCalendar widget)
     * @param int $user_id connected user
     * @param string $start Unix timestamp / Start date displayed on calendar
     * @param string $end Unix timestamp / End date displayed on calendar
     * @return string JSON encoded list of full calendar events
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function individual($user_id, $start = "", $end = "") {
        $this->db->select('leaves.*, types.name as type');
        $this->db->join('types', 'leaves.type = types.id');
        $this->db->where('employee', $user_id);
        $this->db->where('(leaves.startdate <= DATE(' . $this->db->escape($end) . ') AND leaves.enddate >= DATE(' . $this->db->escape($start) . '))');
        $this->db->order_by('startdate', 'desc');
        $this->db->limit(1024);  //Security limit
        $events = $this->db->get('leaves')->result();

        $jsonevents = array();
        foreach ($events as $entry) {

            if ($entry->startdatetype == "Morning") {
                $startdate = $entry->startdate . 'T07:00:00';
            } else {
                $startdate = $entry->startdate . 'T12:00:00';
            }

            if ($entry->enddatetype == "Morning") {
                $enddate = $entry->enddate . 'T12:00:00';
            } else {
                $enddate = $entry->enddate . 'T18:00:00';
            }

            $imageUrl = '';
            $allDay = FALSE;
            $startdatetype =  $entry->startdatetype;
            $enddatetype = $entry->enddatetype;
            if ($startdate == $enddate) { //Deal with invalid start/end date
                $imageUrl = base_url() . 'assets/images/date_error.png';
                $startdate = $entry->startdate . 'T07:00:00';
                $enddate = $entry->enddate . 'T18:00:00';
                $startdatetype = "Morning";
                $enddatetype = "Afternoon";
                $allDay = TRUE;
            }

            $color = '#ff0000';
            switch ($entry->status)
            {
                case 1: $color = '#999'; break;     // Planned
                case 2: $color = '#f89406'; break;  // Requested
                case 7: $color = '#f89406'; break;  // Requested
                case 3: $color = '#468847'; break;  // Accepted
                case 4: $color = '#ff0000'; break;  // Rejected
            }

            $jsonevents[] = array(
                'id' => $entry->id,
                'title' => $entry->type,
                'imageurl' => $imageUrl,
                'start' => $startdate,
                'color' => $color,
                'allDay' => $allDay,
                'end' => $enddate,
                'startdatetype' => $startdatetype,
                'enddatetype' => $enddatetype
            );
        }
        return json_encode($jsonevents);
    }

    /**
     * Leave requests of All users having the same manager (suitable for FullCalendar widget)
     * @param int $user_id id of the manager
     * @param string $start Unix timestamp / Start date displayed on calendar
     * @param string $end Unix timestamp / End date displayed on calendar
     * @return string JSON encoded list of full calendar events
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function workmates($user_id, $start = "", $end = "") {
        $this->db->join('users', 'users.id = leaves.employee');
        $this->db->where('users.manager', $user_id);
        $this->db->where('leaves.status < ', LMS_REJECTED);       //Exclude rejected requests
        $this->db->where('(leaves.startdate <= DATE(' . $this->db->escape($end) . ') AND leaves.enddate >= DATE(' . $this->db->escape($start) . '))');
        $this->db->order_by('startdate', 'desc');
        $this->db->limit(1024);  //Security limit
        $events = $this->db->get('leaves')->result();

        $jsonevents = array();
        foreach ($events as $entry) {
            if ($entry->startdatetype == "Morning") {
                $startdate = $entry->startdate . 'T07:00:00';
            } else {
                $startdate = $entry->startdate . 'T12:00:00';
            }

            if ($entry->enddatetype == "Morning") {
                $enddate = $entry->enddate . 'T12:00:00';
            } else {
                $enddate = $entry->enddate . 'T18:00:00';
            }

            $imageUrl = '';
            $allDay = FALSE;
            $startdatetype =  $entry->startdatetype;
            $enddatetype = $entry->enddatetype;
            if ($startdate == $enddate) { //Deal with invalid start/end date
                $imageUrl = base_url() . 'assets/images/date_error.png';
                $startdate = $entry->startdate . 'T07:00:00';
                $enddate = $entry->enddate . 'T18:00:00';
                $startdatetype = "Morning";
                $enddatetype = "Afternoon";
                $allDay = TRUE;
            }

            $color = '#ff0000';
            switch ($entry->status)
            {
                case 1: $color = '#999'; break;     // Planned
                case 2: $color = '#f89406'; break;  // Requested
                case 7: $color = '#f89406'; break;  // Requested
                case 3: $color = '#468847'; break;  // Accepted
                case 4: $color = '#ff0000'; break;  // Rejected
            }

            $jsonevents[] = array(
                'id' => $entry->id,
                'title' => $entry->firstname .' ' . $entry->lastname,
                'imageurl' => $imageUrl,
                'start' => $startdate,
                'color' => $color,
                'allDay' => $allDay,
                'end' => $enddate,
                'startdatetype' => $startdatetype,
                'enddatetype' => $enddatetype
            );
        }
        return json_encode($jsonevents);
    }

    /**
     * Leave requests of All users having the same manager (suitable for FullCalendar widget)
     * @param int $user_id id of the manager
     * @param string $start Unix timestamp / Start date displayed on calendar
     * @param string $end Unix timestamp / End date displayed on calendar
     * @return string JSON encoded list of full calendar events
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function collaborators($user_id, $start = "", $end = "") {
        $this->db->join('users', 'users.id = leaves.employee');
        $this->db->where('users.manager', $user_id);
        $this->db->where('(leaves.startdate <= DATE(' . $this->db->escape($end) . ') AND leaves.enddate >= DATE(' . $this->db->escape($start) . '))');
        $this->db->order_by('startdate', 'desc');
        $this->db->limit(1024);  //Security limit
        $events = $this->db->get('leaves')->result();

        $jsonevents = array();
        foreach ($events as $entry) {
            if ($entry->startdatetype == "Morning") {
                $startdate = $entry->startdate . 'T07:00:00';
            } else {
                $startdate = $entry->startdate . 'T12:00:00';
            }

            if ($entry->enddatetype == "Morning") {
                $enddate = $entry->enddate . 'T12:00:00';
            } else {
                $enddate = $entry->enddate . 'T18:00:00';
            }

            $imageUrl = '';
            $allDay = FALSE;
            $startdatetype =  $entry->startdatetype;
            $enddatetype = $entry->enddatetype;
            if ($startdate == $enddate) { //Deal with invalid start/end date
                $imageUrl = base_url() . 'assets/images/date_error.png';
                $startdate = $entry->startdate . 'T07:00:00';
                $enddate = $entry->enddate . 'T18:00:00';
                $startdatetype = "Morning";
                $enddatetype = "Afternoon";
                $allDay = TRUE;
            }

            $color = '#ff0000';
            switch ($entry->status)
            {
                case 1: $color = '#999'; break;     // Planned
                case 2: $color = '#f89406'; break;  // Requested
                case 7: $color = '#f89406'; break;  // Requested
                case 3: $color = '#468847'; break;  // Accepted
                case 4: $color = '#ff0000'; break;  // Rejected
            }

            $jsonevents[] = array(
                'id' => $entry->id,
                'title' => $entry->firstname .' ' . $entry->lastname,
                'imageurl' => $imageUrl,
                'start' => $startdate,
                'color' => $color,
                'allDay' => $allDay,
                'end' => $enddate,
                'startdatetype' => $startdatetype,
                'enddatetype' => $enddatetype
            );
        }
        return json_encode($jsonevents);
    }

    /**
     * Leave requests of All users of a department (suitable for FullCalendar widget)
     * @param int $entity_id Entity identifier (the department)
     * @param string $start Unix timestamp / Start date displayed on calendar
     * @param string $end Unix timestamp / End date displayed on calendar
     * @param bool $children Include sub department in the query
     * @param string $statusFilter optional filter on status
     * @return string JSON encoded list of full calendar events
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function department($entity_id, $start = "", $end = "", $children = FALSE, $statusFilter = NULL) {
        $this->db->select('users.firstname, users.lastname, users.manager');
        $this->db->select('leaves.*');
        $this->db->select('types.name as type, types.acronym as acronym');
        $this->db->from('organization');
        $this->db->join('users', 'users.organization = organization.id');
        $this->db->join('leaves', 'leaves.employee = users.id');
        $this->db->join('types', 'leaves.type = types.id');
        $this->db->where('(leaves.startdate <= DATE(' . $this->db->escape($end) . ') AND leaves.enddate >= DATE(' . $this->db->escape($start) . '))');
        if ($children === TRUE) {
            $this->load->model('organization_model');
            $list = $this->organization_model->getAllChildren($entity_id);
            $ids = array();
            if ($list[0]['id'] != '') {
                $ids = explode(",", $list[0]['id']);
                array_push($ids, $entity_id);
                $this->db->where_in('organization.id', $ids);
            } else {
                $this->db->where('organization.id', $entity_id);
            }
        } else {
            $this->db->where('organization.id', $entity_id);
        }
        //$this->db->where('leaves.status != ', 4); //Exclude rejected requests
        if ($statusFilter != NULL) {
            $statuses = explode ('|', $statusFilter);
            $this->db->where_in('status', $statuses );
        }
        $this->db->order_by('startdate', 'desc');
        $this->db->limit(1024);  //Security limit
        $events = $this->db->get()->result();
        $jsonevents = array();
        foreach ($events as $entry) {
            //Date of event
            if ($entry->startdatetype == "Morning") {
                $startdate = $entry->startdate . 'T07:00:00';
            } else {
                $startdate = $entry->startdate . 'T12:00:00';
            }

            if ($entry->enddatetype == "Morning") {
                $enddate = $entry->enddate . 'T12:00:00';
            } else {
                $enddate = $entry->enddate . 'T18:00:00';
            }
            $imageUrl = '';
            $allDay = FALSE;
            $startdatetype =  $entry->startdatetype;
            $enddatetype = $entry->enddatetype;
            if ($startdate == $enddate) { //Deal with invalid start/end date
                $imageUrl = base_url() . 'assets/images/date_error.png';
                $startdate = $entry->startdate . 'T07:00:00';
                $enddate = $entry->enddate . 'T18:00:00';
                $startdatetype = "Morning";
                $enddatetype = "Afternoon";
                $allDay = TRUE;
            }

            $color = '#ff0000';
            switch ($entry->status)
            {
                case 1: $color = '#999'; break;     // Planned
                case 2: $color = '#f89406'; break;  // Requested
                case 7: $color = '#f89406'; break;  // Requested
                case 3: $color = '#468847'; break;  // Accepted
                case 4: $color = '#ff0000'; break;  // Rejected
                default: $color = '#ff0000'; break;  // Cancellation and Canceled
            }
            $title = $entry->firstname .' ' . $entry->lastname;
            //If the connected user can access to the leave request
            //(self, HR admin and manager), add a link and the acronym
            $url = '';
            if (($entry->employee == $this->session->userdata('id')) ||
                ($entry->manager == $this->session->userdata('id')) ||
                    ($this->session->userdata('is_hr') === TRUE)) {
                $url = base_url() . 'leaves/leaves/' . $entry->id;
                if (!empty($entry->acronym)) {
                    $title .= ' - ' . $entry->acronym;
                }
            } else {
                //Don't display rejected and cancel* leave requests for other employees
                if ($entry->status > 3) {
                    continue;
                }
            }

            //Create the JSON representation of the event
            $jsonevents[] = array(
                'id' => $entry->id,
                'title' => $title,
                'imageurl' => $imageUrl,
                'start' => $startdate,
                'color' => $color,
                'allDay' => $allDay,
                'end' => $enddate,
                'startdatetype' => $startdatetype,
                'enddatetype' => $enddatetype,
                'url' => $url
            );
        }
        return json_encode($jsonevents);
    }

    /**
     * Leave requests of All users of a list (suitable for FullCalendar widget)
     * @param int $list_id List identifier
     * @param string $start Unix timestamp / Start date displayed on calendar
     * @param string $end Unix timestamp / End date displayed on calendar
     * @param bool $children Include sub department in the query
     * @param string $statusFilter optional filter on status
     * @return string JSON encoded list of full calendar events
     * @author Emilien NICOLAS <milihhard1996@gmail.com>
     */
    public function getListRequest($list_id, $start = "", $end = "", $statusFilter = NULL) {
      $this->db->select('users.firstname, users.lastname, users.manager');
      $this->db->select('leaves.*');
      $this->db->select('types.name as type, types.acronym as acronym');
      $this->db->from('organization');
      $this->db->join('users', 'users.organization = organization.id');
      $this->db->join('leaves', 'leaves.employee = users.id');
      $this->db->join('types', 'leaves.type = types.id');
      $this->db->join('org_lists_employees', 'org_lists_employees.user = users.id');
      $this->db->where('(leaves.startdate <= DATE(' . $this->db->escape($end) . ') AND leaves.enddate >= DATE(' . $this->db->escape($start) . '))');
      $this->db->where('org_lists_employees.list', $list_id);
      //$this->db->where('leaves.status != ', 4); //Exclude rejected requests
      if ($statusFilter != NULL) {
          $statuses = explode ('|', $statusFilter);
          $this->db->where_in('status', $statuses );
      }
      $this->db->order_by('startdate', 'desc');
      $this->db->limit(1024);  //Security limit
      $events = $this->db->get()->result();
      return $this->transformToEvent($events);
    }

    private function transformToEvent($events){
      $jsonevents = array();
      foreach ($events as $entry) {
          //Date of event
          if ($entry->startdatetype == "Morning") {
              $startdate = $entry->startdate . 'T07:00:00';
          } else {
              $startdate = $entry->startdate . 'T12:00:00';
          }

          if ($entry->enddatetype == "Morning") {
              $enddate = $entry->enddate . 'T12:00:00';
          } else {
              $enddate = $entry->enddate . 'T18:00:00';
          }
          $imageUrl = '';
          $allDay = FALSE;
          $startdatetype =  $entry->startdatetype;
          $enddatetype = $entry->enddatetype;
          if ($startdate == $enddate) { //Deal with invalid start/end date
              $imageUrl = base_url() . 'assets/images/date_error.png';
              $startdate = $entry->startdate . 'T07:00:00';
              $enddate = $entry->enddate . 'T18:00:00';
              $startdatetype = "Morning";
              $enddatetype = "Afternoon";
              $allDay = TRUE;
          }

          $color = '#ff0000';
          switch ($entry->status)
          {
              case 1: $color = '#999'; break;     // Planned
              case 2: $color = '#f89406'; break;  // Requested
              case 7: $color = '#f89406'; break;  // Requested
              case 3: $color = '#468847'; break;  // Accepted
              case 4: $color = '#ff0000'; break;  // Rejected
              default: $color = '#ff0000'; break;  // Cancellation and Canceled
          }
          $title = $entry->firstname .' ' . $entry->lastname;
          //If the connected user can access to the leave request
          //(self, HR admin and manager), add a link and the acronym
          $url = '';
          if (($entry->employee == $this->session->userdata('id')) ||
              ($entry->manager == $this->session->userdata('id')) ||
                  ($this->session->userdata('is_hr') === TRUE)) {
              $url = base_url() . 'leaves/leaves/' . $entry->id;
              if (!empty($entry->acronym)) {
                  $title .= ' - ' . $entry->acronym;
              }
          } else {
              //Don't display rejected and cancel* leave requests for other employees
              if ($entry->status > 3) {
                  continue;
              }
          }

          //Create the JSON representation of the event
          $jsonevents[] = array(
              'id' => $entry->id,
              'title' => $title,
              'imageurl' => $imageUrl,
              'start' => $startdate,
              'color' => $color,
              'allDay' => $allDay,
              'end' => $enddate,
              'startdatetype' => $startdatetype,
              'enddatetype' => $enddatetype,
              'url' => $url
          );
      }
      return json_encode($jsonevents);
    }

    /**
     * Leave requests of All users of an entity
     * @param int $entity_id Entity identifier (the department)
     * @param bool $children Include sub department in the query
     * @return array List of leave requests (DB records)
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function entity($entity_id, $children = FALSE) {
        $this->db->select('users.firstname, users.lastname,  leaves.*, types.name as type');
        $this->db->from('organization');
        $this->db->join('users', 'users.organization = organization.id');
        $this->db->join('leaves', 'leaves.employee  = users.id');
        $this->db->join('types', 'leaves.type = types.id');
        if ($children === TRUE) {
            $this->load->model('organization_model');
            $list = $this->organization_model->getAllChildren($entity_id);
            $ids = array();
            if (count($list) > 0) {
                $ids = explode(",", $list[0]['id']);
            }
            array_push($ids, $entity_id);
            $this->db->where_in('organization.id', $ids);
        } else {
            $this->db->where('organization.id', $entity_id);
        }
        $this->db->where('leaves.status != ', 4);       //Exclude rejected requests
        $this->db->order_by('startdate', 'desc');
        $events = $this->db->get()->result_array();
        return $events;
    }

    /**
     * List all leave requests submitted to the connected user (or if delegate of a manager)
     * Can be filtered with "Requested" status.
     * @param int $manager connected user
     * @param bool $all TRUE all requests, FALSE otherwise
     * @return array Recordset (can be empty if no requests or not a manager)
     */
    public function getLeavesRequestedToManager($manager, $all = FALSE) {
        // Load the delegations model to get managers delegating to the connected user
        $this->load->model('delegations_model');
        $ids = $this->delegations_model->listManagersGivingDelegation($manager);

        // Select relevant fields from the leaves, users, status, and types tables
        $this->db->select('leaves.id as leave_id, users.*, leaves.*, types.name as type_label');
        $this->db->select('status.name as status_name, types.name as type_name');
        $this->db->join('status', 'leaves.status = status.id');
        $this->db->join('types', 'leaves.type = types.id');
        $this->db->join('users', 'users.id = leaves.employee');

        // If the connected user has delegations, include those managers in the filter
        if (count($ids) > 0) {
            array_push($ids, $manager);
            $this->db->where_in('users.manager', $ids);
        } else {
            $this->db->where('users.manager', $manager);
        }

        // Filter to only include requested or cancellation status if $all is FALSE
        if ($all == FALSE) {
            $this->db->group_start();
            $this->db->where('leaves.status', LMS_REQUESTED);
            $this->db->or_where('leaves.status', LMS_CANCELLATION);
            $this->db->group_end();
        }

        // Order the results by the start date in descending order
        $this->db->order_by('leaves.startdate', 'desc');

        // Execute the query and return the result set
        $query = $this->db->get('leaves');
        return $query->result_array();
    }
    
    public function getAllPendingLeaves($user_id) {
        $this->db->select('leaves.*, users.manager');
        $this->db->from('leaves');
        $this->db->join('users', 'users.id = leaves.employee');
        $this->db->join('delegations', 'delegations.manager_id = users.manager', 'left');
        $this->db->group_start();
        $this->db->where('leaves.status', LMS_REQUESTED);
        $this->db->group_end();
        $this->db->group_start();
        $this->db->where('users.manager', $user_id);
        $this->db->or_where('delegations.delegate_id', $user_id);
        $this->db->group_end();
        $query = $this->db->get();
        return $query->result_array();
    }    
    
    public function getAllPendingLeaveBank() {
        $this->db->select('leaves.*, users.manager');
        $this->db->from('leaves');
        $this->db->join('users', 'users.id = leaves.employee');
        $this->db->where('leaves.status', LMS_REQUESTEDBANK);
        $query = $this->db->get();
        return $query->result_array();
    }
     
    
    /**
     * Get the list of history of an employee
     * @param int $manager Id of the employee
     * @param bool $all TRUE all requests, FALSE otherwise
     * @return array list of records
     * @author Emilien NICOLAS <milihhard1996@gmail.com>
     */
    public function getLeavesRequestedToManagerWithHistory($manager, $all = FALSE){
      $this->load->model('delegations_model');
      $manager = intval($manager);
      $query="SELECT leaves.id as leave_id, users.*, leaves.*, types.name as type_label, status.name as status_name, types.name as type_name, lastchange.date as change_date, requested.date as request_date
        FROM `leaves`
        inner join status ON leaves.status = status.id
        inner join types ON leaves.type = types.id
        inner join users ON users.id = leaves.employee
        left outer join (
          SELECT id, MAX(change_date) as date
          FROM leaves_history
          GROUP BY id
        ) lastchange ON leaves.id = lastchange.id
        left outer join (
          SELECT id, MIN(change_date) as date
          FROM leaves_history
          WHERE leaves_history.status = 2
          GROUP BY id
        ) requested ON leaves.id = requested.id";
      //Case of manager having delegations
      $ids = $this->delegations_model->listManagersGivingDelegation($manager);
      if (count($ids) > 0) {
        array_push($ids, $manager);
        $query .= " WHERE users.manager IN (" . implode(",", $ids) . ")";
      } else {
        $query .= " WHERE users.manager = $manager";
      }
      if ($all == FALSE) {
        $query .= " AND (leaves.status = " . LMS_REQUESTED .
                " OR leaves.status = " . LMS_CANCELLATION . ")";
      }
      $query=$query . " order by leaves.startdate DESC;";
      $this->db->query('SET SQL_BIG_SELECTS=1');
      return $this->db->query($query)->result_array();
    }

    /**
     * Count leave requests submitted to the connected user (or if delegate of a manager)
     * @param int $manager connected user
     * @return int number of requests
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function countLeavesRequestedToManager($manager) {
        $this->load->model('delegations_model');
        $ids = $this->delegations_model->listManagersGivingDelegation($manager);
        $this->db->select('count(*) as number', FALSE);
        $this->db->join('users', 'users.id = leaves.employee');
        $this->db->where_in('leaves.status', array(LMS_REQUESTED, LMS_CANCELLATION));

        if (count($ids) > 0) {
            array_push($ids, $manager);
            $this->db->where_in('users.manager', $ids);
        } else {
            $this->db->where('users.manager', $manager);
        }
        $result = $this->db->get('leaves');
        return $result->row()->number;
    }

    /**
     * List all leave bank requests with a specific type or status.
     * @param bool $all TRUE for all leave bank requests, FALSE for requested leave bank requests
     * @return array Recordset (can be empty if no requests)
     */
    public function getLeavesBankRequested($allleavebank = FALSE) {
        $this->db->select('leaves.id as leave_id, users.*, leaves.*, types.name as type_label');
        $this->db->select('status.name as status_name, types.name as type_name');
        $this->db->join('status', 'leaves.status = status.id');
        $this->db->join('types', 'leaves.type = types.id');
        $this->db->join('users', 'users.id = leaves.employee');
        
        // Filter based on whether all leave bank or just leave bank requests
        if ($allleavebank) {
            // Show all leave bank requests (type = 3)
            $this->db->where('leaves.type', 3);
        } else {
            // Show only leave bank requests with status = 7
            $this->db->where('leaves.status', 7);
        }
        
        $this->db->order_by('leaves.startdate', 'desc');
        $query = $this->db->get('leaves');
        return $query->result_array();
    }


    /**
     * Get the list of leave bank request history with a specific type or status.
     * @param bool $all TRUE for all leave bank requests, FALSE for requested leave bank requests
     * @return array list of records
     */
    public function getLeavesBankRequestedWithHistory($allleavebank = FALSE) {
        $query = "SELECT leaves.id as leave_id, users.*, leaves.*, types.name as type_label, 
                status.name as status_name, types.name as type_name, lastchange.date as change_date, 
                requested.date as request_date
                FROM `leaves`
                INNER JOIN status ON leaves.status = status.id
                INNER JOIN types ON leaves.type = types.id
                INNER JOIN users ON users.id = leaves.employee
                LEFT OUTER JOIN (
                    SELECT id, MAX(change_date) as date
                    FROM leaves_history
                    GROUP BY id
                ) lastchange ON leaves.id = lastchange.id
                LEFT OUTER JOIN (
                    SELECT id, MIN(change_date) as date
                    FROM leaves_history
                    WHERE leaves_history.status = 7
                    GROUP BY id
                ) requested ON leaves.id = requested.id
                WHERE ";

        // Filter based on whether all leave bank or just leave bank requests
        if ($allleavebank) {
            $query .= "leaves.type = 3"; // Show all leave bank requests (type = 3)
        } else {
            $query .= "leaves.status = 7"; // Show only leave bank requests with status = 7
        }

        $query .= " ORDER BY leaves.startdate DESC";
        $this->db->query('SET SQL_BIG_SELECTS=1');
        return $this->db->query($query)->result_array();
    }

    /**
     * Count leave requests submitted to the connected user (or if delegate of a manager)
     * @param int $hr connected user
     * @return int number of requests
     */
    public function countLeavesRequestedToHR($hr) {
        $this->db->from('leaves');
        $this->db->where('leaves.status', 7); // Status is 7
        return $this->db->count_all_results();
    }


    /**
     * Purge the table by deleting the records prior $toDate
     * @param date $toDate
     * @return int number of affected rows
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function purgeLeaves($toDate) {
        //TODO : if one day we use this function, should what should we do with the history feature?
        $this->db->where(' <= ', $toDate);
        return $this->db->delete('leaves');
    }

    /**
     * Count the number of rows into the table
     * @return int number of rows
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function count() {
        $this->db->select('count(*) as number', FALSE);
        $this->db->from('leaves');
        $result = $this->db->get();
        return $result->row()->number;
    }

    /**
     * All leaves between two timestamps, no filters
     * @param string $startDate Start date displayed on calendar
     * @param string $endDate End date displayed on calendar
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function all($startDate, $endDate) {
        $this->db->select("users.id as user_id, users.firstname, users.lastname, leaves.*", FALSE);
        $this->db->join('users', 'users.id = leaves.employee');
        $this->db->where('( (leaves.startdate >= ' . $this->db->escape($startDate) . ' AND leaves.enddate <= ' . $this->db->escape($endDate) . ')' .
                                   ' OR (leaves.startdate <= ' . $this->db->escape($endDate) . ' AND leaves.enddate >= ' . $this->db->escape($endDate) . '))');
        $this->db->order_by('startdate', 'desc');
        return $this->db->get('leaves')->result();
    }

    /**
     * Leave requests of users of a department(s)
     * @param int $entity Entity identifier (the department)
     * @param int $month Month number
     * @param int $year Year number
     * @param bool $children Include sub department in the query
     * @param string $statusFilter optional filter on status
     * @param boolean $calendar Is this function called to display a calendar
     * @return array Array of objects containing leave details
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function tabular(&$entity=-1, &$month=0, &$year=0, &$children=TRUE, $statusFilter=NULL, $calendar=FALSE) {
        //Find default values for parameters (passed by ref)
        if ($month==0) $month = date("m");
        if ($year==0) $year = date("Y");
        $children = filter_var($children, FILTER_VALIDATE_BOOLEAN);
        //If no entity was selected, select the entity of the connected user or the root of the organization
        if ($entity == -1) {
            if (!$this->session->userdata('logged_in')) {
                $entity = 0;
            } else {
                $this->load->model('users_model');
                $user = $this->users_model->getUsers($this->session->userdata('id'));
                $entity = is_null($user['organization'])?0:$user['organization'];
            }
        }
        $tabular = array();

        //We must show all users of the departement
        $this->load->model('organization_model');
        $employees = $this->organization_model->allEmployees($entity, $children);
        foreach ($employees as $employee) {
            if ($statusFilter != NULL) {
                $statuses = explode ('|', $statusFilter);
                $tabular[$employee->id] = $this->linear($employee->id,
                        $month,
                        $year,
                        in_array("1", $statuses),
                        in_array("2", $statuses),
                        in_array("3", $statuses),
                        in_array("4", $statuses),
                        in_array("5", $statuses),
                        in_array("6", $statuses),
                        $calendar);
            } else {
                $tabular[$employee->id] = $this->linear($employee->id, $month, $year,
                        TRUE, TRUE, TRUE, FALSE, TRUE, FALSE, $calendar);
            }
        }
        return $tabular;
    }

    /**
     * Leave requests of users of a list (custom list built by user)
     * @param int $list List identifier
     * @param int $month Month number
     * @param int $year Year number
     * @param string $statusFilter optional filter on status
     * @return array Array of objects containing leave details
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function tabularList($list, &$month=0, &$year=0, $statusFilter=NULL) {
        //Find default values for parameters (passed by ref)
        if ($month==0) $month = date("m");
        if ($year==0) $year = date("Y");
        $tabular = array();

        //We must show all users of the departement
        $this->load->model('lists_model');
        $employees = $this->lists_model->getListOfEmployees($list);
        foreach ($employees as $employee) {
            if ($statusFilter != NULL) {
                $statuses = explode ('|', $statusFilter);
                $tabular[$employee['id']] = $this->linear($employee['id'],
                        $month,
                        $year,
                        in_array("1", $statuses),
                        in_array("2", $statuses),
                        in_array("3", $statuses),
                        in_array("4", $statuses),
                        in_array("5", $statuses),
                        in_array("6", $statuses));
            } else {
                $tabular[$employee['id']] = $this->linear($employee['id'], $month, $year, TRUE, TRUE, TRUE, FALSE, TRUE);
            }
        }
        return $tabular;
    }

    /**
     * Count the total duration of leaves for the month. Only accepted leaves are taken into account
     * @param array $linear linear calendar for one employee
     * @return int total of leaves duration
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function monthlyLeavesDuration($linear) {
        $total = 0;
        foreach ($linear->days as $day) {
          if (strstr($day->display, ';')) {
              $display = explode(";", $day->display);
              if ($display[0] == '2') $total += 0.5;
              if ($display[0] == '3') $total += 0.5;
              if ($display[1] == '2') $total += 0.5;
              if ($display[1] == '3') $total += 0.5;
          } else {
              if ($day->display == 2) $total += 0.5;
              if ($day->display == 3) $total += 0.5;
              if ($day->display == 1) $total += 1;
          }
        }
        return $total;
    }

    /**
     * Count the total duration of leaves for the month, grouped by leave type.
     * Only accepted leaves are taken into account.
     * @param array $linear linear calendar for one employee
     * @return array key/value array (k:leave type label, v:sum for the month)
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function monthlyLeavesByType($linear) {
        $by_types = array();
        foreach ($linear->days as $day) {
          if (strstr($day->display, ';')) {
              $display = explode(";", $day->display);
              $type = explode(";", $day->type);
              if ($display[0] == '2') array_key_exists($type[0], $by_types) ? $by_types[$type[0]] += 0.5: $by_types[$type[0]] = 0.5;
              if ($display[0] == '3') array_key_exists($type[0], $by_types) ? $by_types[$type[0]] += 0.5: $by_types[$type[0]] = 0.5;
              if ($display[1] == '2') array_key_exists($type[1], $by_types) ? $by_types[$type[1]] += 0.5: $by_types[$type[1]] = 0.5;
              if ($display[1] == '3') array_key_exists($type[1], $by_types) ? $by_types[$type[1]] += 0.5: $by_types[$type[1]] = 0.5;
          } else {
              if ($day->display == 2) array_key_exists($day->type, $by_types) ? $by_types[$day->type] += 0.5: $by_types[$day->type] = 0.5;
              if ($day->display == 3) array_key_exists($day->type, $by_types) ? $by_types[$day->type] += 0.5: $by_types[$day->type] = 0.5;
              if ($day->display == 1) array_key_exists($day->type, $by_types) ? $by_types[$day->type] += 1: $by_types[$day->type] = 1;
          }
        }
        return $by_types;
    }
    
    /**
     * Count the total duration of leaves for the month, grouped by leave type.
     * Only accepted leaves are taken into account.
     * @param array $linearbydate linear calendar for one employee
     * @return array key/value array (k:leave type label, v:sum for the month)
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function dateRangeLeaveByType($linearbydate) {
        $by_types = array();
        foreach ($linearbydate->days as $day) {
            if (strstr($day->display, ';')) {
                $display = explode(";", $day->display);
                $type = explode(";", $day->type);
                for ($i = 0; $i < count($display); $i++) {
                    if ($display[$i] == '2' || $display[$i] == '3') {
                        array_key_exists($type[$i], $by_types) ? $by_types[$type[$i]] += 0.5 : $by_types[$type[$i]] = 0.5;
                    }
                    if ($display[$i] == '1') {
                        array_key_exists($type[$i], $by_types) ? $by_types[$type[$i]] += 1 : $by_types[$type[$i]] = 1;
                    }
                }
            } else {
                if ($day->display == 2 || $day->display == 3) array_key_exists($day->type, $by_types) ? $by_types[$day->type] += 0.5 : $by_types[$day->type] = 0.5;
                if ($day->display == 1) array_key_exists($day->type, $by_types) ? $by_types[$day->type] += 1 : $by_types[$day->type] = 1;
            }
        }
        return $by_types;
    }
    
    /**
     * Count the total duration of leaves by the date range, grouped by leave type.
     * Only accepted leaves are taken into account.
     * @param array $linearbydate linear calendar for one employee
     * @return array key/value array (k:leave type label, v:sum for the month)
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function dateRangeLeaveDuration($linearbydate) {
        $total = 0;
        foreach ($linearbydate->days as $day) {
            if (strstr($day->display, ';')) {
                $display = explode(";", $day->display);
                foreach ($display as $disp) {
                    if ($disp == '2' || $disp == '3') $total += 0.5;
                    if ($disp == '1') $total += 1;
                }
            } else {
                if ($day->display == 2 || $day->display == 3) $total += 0.5;
                if ($day->display == 1) $total += 1;
            }
        }
        return $total;
    }
    
    /**
     * Leave requests of users of a department(s)
     * @param int $employee Employee identifier
     * @param int $start_date Start Date
     * @param int $end_date End Date
     * @param boolean $planned Include leave requests with status planned
     * @param boolean $requested Include leave requests with status requested
     * @param boolean $accepted Include leave requests with status accepted
     * @param boolean $rejected Include leave requests with status rejected
     * @param boolean $cancellation Include leave requests with status cancellation
     * @param boolean $canceled Include leave requests with status canceled
     * @param boolean $calendar Is this function called to display a calendar
     * @return array Array of objects containing leave details
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function linearbydate($employee_id, $start_date, $end_date,
    $planned = FALSE, $requested = FALSE, $accepted = FALSE,
    $rejected = FALSE, $cancellation = FALSE, $canceled = FALSE,
    $calendar = FALSE) {

    $this->load->model('dayoffs_model');
    $this->load->model('users_model');
    $employee = $this->users_model->getUsers($employee_id);

    $user = new stdClass;
    $user->name = $employee['firstname'] . ' ' . $employee['lastname'];
    $user->manager = (int) $employee['manager'];
    $user->id = (int) $employee['id'];
    $user->days = array();

    // Initialize days array
    $period = new DatePeriod(
        new DateTime($start_date),
        new DateInterval('P1D'),
        (new DateTime($end_date))->modify('+1 day')
    );

    foreach ($period as $day) {
        $dateKey = $day->format('Y-m-d');
        $dayInfo = new stdClass;
        $dayInfo->id = 0;
        $dayInfo->type = '';
        $dayInfo->acronym = '';
        $dayInfo->status = '';
        $dayInfo->display = 0;
        $user->days[$dateKey] = $dayInfo;
    }

    // Query for day offs
    $dayoffs = $this->dayoffs_model->lengthDaysOffBetweenDatesForEmployee($employee_id, $start_date, $end_date);
    foreach ($dayoffs as $dayoff) {
        $date = new DateTime($dayoff->date);
        $dateKey = $date->format('Y-m-d');
        if (isset($user->days[$dateKey])) {
            $user->days[$dateKey]->display = (string) $dayoff->type + 3;
            $user->days[$dateKey]->status = (string) $dayoff->type + 10;
            $user->days[$dateKey]->type = $dayoff->title;
        }
    }

    // Build the query for all leaves
    $this->db->select('leaves.*, types.acronym, types.name as type, users.manager as manager');
    $this->db->from('leaves');
    $this->db->join('types', 'leaves.type = types.id');
    $this->db->join('users', 'leaves.employee = users.id');
    $this->db->where('leaves.startdate <=', $end_date);
    $this->db->where('leaves.enddate >=', $start_date);

    // Filtering by leave status
    if (!$planned) $this->db->where('leaves.status !=', LMS_PLANNED);
    if (!$requested) $this->db->where('leaves.status !=', LMS_REQUESTED);
    if (!$accepted) $this->db->where('leaves.status !=', LMS_ACCEPTED);
    if (!$rejected) $this->db->where('leaves.status !=', LMS_REJECTED);
    if (!$cancellation) $this->db->where('leaves.status !=', LMS_CANCELLATION);
    if (!$canceled) $this->db->where('leaves.status !=', LMS_CANCELED);

    $this->db->where('leaves.employee', $employee_id);
    $this->db->order_by('startdate', 'asc');
    $this->db->order_by('startdatetype', 'desc');
    $events = $this->db->get()->result();

    // Processing each leave event
    foreach ($events as $entry) {
        $eventStartDate = new DateTime($entry->startdate);
        $eventEndDate = new DateTime($entry->enddate);
        $eventPeriod = new DatePeriod(
            $eventStartDate,
            new DateInterval('P1D'),
            $eventEndDate->modify('+1 day')
        );

        foreach ($eventPeriod as $date) {
            $dateKey = $date->format('Y-m-d');
            if (isset($user->days[$dateKey])) {
                $user->days[$dateKey]->id = $entry->id;
                $user->days[$dateKey]->type = $entry->type;
                $user->days[$dateKey]->acronym = $entry->acronym;
                $user->days[$dateKey]->status = $entry->status;
                $user->days[$dateKey]->display = 1; // Simplified assumption
            }
        }
    }

    return $user;
    }

    /**
     * Leave requests of users of a department(s)
     * @param int $employee Employee identifier
     * @param int $month Month number
     * @param int $year Year number
     * @param boolean $planned Include leave requests with status planned
     * @param boolean $requested Include leave requests with status requested
     * @param boolean $accepted Include leave requests with status accepted
     * @param boolean $rejected Include leave requests with status rejected
     * @param boolean $cancellation Include leave requests with status cancellation
     * @param boolean $canceled Include leave requests with status canceled
     * @param boolean $calendar Is this function called to display a calendar
     * @return array Array of objects containing leave details
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function linear($employee_id, $month, $year,
            $planned = FALSE, $requested = FALSE, $accepted = FALSE,
            $rejected = FALSE, $cancellation = FALSE, $canceled = FALSE,
            $calendar = FALSE) {
        $start = $year . '-' . $month . '-' .  '1';    //first date of selected month
        $lastDay = date("t", strtotime($start));    //last day of selected month
        $end = $year . '-' . $month . '-' . $lastDay;    //last date of selected month

        //We must show all users of the departement
        $this->load->model('dayoffs_model');
        $this->load->model('users_model');
        $this->load->model('organization_model');    
        $employee = $this->users_model->getUsers($employee_id);
        $user = new stdClass;
        $user->name = $employee['firstname'] . ' ' . $employee['lastname'];
        $user->organization_name = $this->organization_model->getname($employee['organization']); // Fetch and assign organization name
        $user->manager = (int) $employee['manager'];  //To enable hiding confidential info in view
        $user->id = (int) $employee['id'];
        $user->days = array();

        //Init all days of the month to working day
        for ($ii = 1; $ii <= $lastDay; $ii++) {
            $day = new stdClass;
            $day->id = 0;
            $day->type = '';
            $day->acronym = '';
            $day->status = '';
            $day->display = 0; //working day
            $user->days[$ii] = $day;
        }

        //Force all day offs (mind the case of employees having no leave)
        $dayoffs = $this->dayoffs_model->lengthDaysOffBetweenDatesForEmployee($employee_id, $start, $end);
        foreach ($dayoffs as $dayoff) {
            $iDate = new DateTime($dayoff->date);
            $dayNum = intval($iDate->format('d'));
            $user->days[$dayNum]->display = (string) $dayoff->type + 3;
            $user->days[$dayNum]->status = (string) $dayoff->type + 10;
            $user->days[$dayNum]->type = $dayoff->title;
        }

        //Build the complex query for all leaves
        $this->db->select('leaves.*');
        $this->db->select('types.acronym, types.name as type');
        $this->db->select('users.manager as manager');
        $this->db->from('leaves');
        $this->db->join('types', 'leaves.type = types.id');
        $this->db->join('users', 'leaves.employee = users.id');
        $this->db->where('(leaves.startdate <= DATE(' . $this->db->escape($end) . ') AND leaves.enddate >= DATE(' . $this->db->escape($start) . '))');
        if (!$planned) $this->db->where('leaves.status != ', LMS_PLANNED);
        if (!$requested) $this->db->where('leaves.status != ', LMS_REQUESTED);
        if (!$accepted) $this->db->where('leaves.status != ', LMS_ACCEPTED);
        if (!$rejected) $this->db->where('leaves.status != ', LMS_REJECTED);
        if (!$cancellation) $this->db->where('leaves.status != ', LMS_CANCELLATION);
        if (!$canceled) $this->db->where('leaves.status != ', LMS_CANCELED);

        $this->db->where('leaves.employee = ', $employee_id);
        $this->db->order_by('startdate', 'asc');
        $this->db->order_by('startdatetype', 'desc');
        $events = $this->db->get()->result();
        $limitDate = DateTime::createFromFormat('Y-m-d H:i:s', $end . ' 00:00:00');
        $floorDate = DateTime::createFromFormat('Y-m-d H:i:s', $start . ' 00:00:00');

        $this->load->model('dayoffs_model');
        foreach ($events as $entry) {
            //Hide forbidden entries in calendars
            if ($calendar) {
                //Don't display rejected and cancel* leave requests for other employees
                if (($entry->employee != $this->session->userdata('id')) &&
                    ($entry->manager != $this->session->userdata('id')) &&
                        ($this->session->userdata('is_hr') === FALSE)) {
                    if ($entry->status > LMS_ACCEPTED) {
                        continue;
                    }
                }
            }

            //Note that $eventStartDate and $eventEndDate are related to the leave request event
            //But $startDate and $endDate are the first and last days being displayed on the calendar
            $eventStartDate = DateTime::createFromFormat('Y-m-d H:i:s', $entry->startdate . ' 00:00:00');
            $startDate = clone $eventStartDate;
            if ($startDate < $floorDate) $startDate = $floorDate;
            $iDate = clone $startDate;
            $eventEndDate = DateTime::createFromFormat('Y-m-d H:i:s', $entry->enddate . ' 00:00:00');
            $endDate = clone $eventEndDate;
            if ($endDate > $limitDate) $endDate = $limitDate;

            //Iteration between 2 dates
            while ($iDate <= $endDate)
            {
                if ($iDate > $limitDate) break;     //The calendar displays the leaves on one month
                if ($iDate < $startDate) continue;  //The leave starts before the first day of the calendar
                $dayNum = intval($iDate->format('d'));

                //Simplify the reading (and logic) by using atomic variables
                if ($eventStartDate == $eventEndDate) $oneDay = TRUE; else $oneDay = FALSE;
                if ($entry->startdatetype == 'Morning') $start_morning = TRUE; else $start_morning = FALSE;
                if ($entry->startdatetype == 'Afternoon') $start_afternoon = TRUE; else $start_afternoon = FALSE;
                if ($entry->enddatetype == 'Morning') $end_morning = TRUE; else $end_morning = FALSE;
                if ($entry->enddatetype == 'Afternoon') $end_afternoon = TRUE; else $end_afternoon = FALSE;
                if ($iDate == $eventStartDate) $first_day = TRUE; else $first_day = FALSE;
                if ($iDate == $eventEndDate) $last_day = TRUE; else $last_day = FALSE;

                //Display (different from contract/calendar)
                //0 - Working day  _
                //1 - All day           []
                //2 - Morning        |\
                //3 - Afternoon      /|
                //4 - All Day Off       []
                //5 - Morning Day Off   |\
                //6 - Afternoon Day Off /|
                //9 - Error in start/end types

                //Length of leave request is one day long
                if ($oneDay && $start_morning && $end_afternoon) $display = '1';
                if ($oneDay && $start_morning && $end_morning) $display = '2';
                if ($oneDay && $start_afternoon && $end_afternoon) $display = '3';
                if ($oneDay && $start_afternoon && $end_morning) $display = '9';
                //Length of leave request is one day long is more than one day
                //We are in the middle of a long leave request
                if (!$oneDay && !$first_day && !$last_day) $display = '1';
                //First day of a long leave request
                if (!$oneDay && $first_day && $start_morning) $display = '1';
                if (!$oneDay && $first_day && $start_afternoon) $display = '3';
                //Last day of a long leave request
                if (!$oneDay && $last_day && $end_afternoon) $display = '1';
                if (!$oneDay && $last_day && $end_morning) $display = '2';

                //Check if another leave was defined on this day
                if ($user->days[$dayNum]->display != '4') { //Except full day off
                    if ($user->days[$dayNum]->display != 0) { //Overlapping with a day off or another request
                        if (($user->days[$dayNum]->display == 2) ||
                                ($user->days[$dayNum]->display == 5)) { //Respect Morning/Afternoon order
                            $user->days[$dayNum]->id .= ';' . $entry->id;
                            $user->days[$dayNum]->type .= ';' . $entry->type;
                            $user->days[$dayNum]->display .= ';' . $display;
                            $user->days[$dayNum]->status .= ';' . $entry->status;
                            $user->days[$dayNum]->acronym .= ';' . $entry->acronym;
                        } else {
                            $user->days[$dayNum]->id = $entry->id . ';' . $user->days[$dayNum]->id;
                            $user->days[$dayNum]->type = $entry->type . ';' . $user->days[$dayNum]->type;
                            $user->days[$dayNum]->display = $display . ';' . $user->days[$dayNum]->display;
                            $user->days[$dayNum]->status = $entry->status . ';' . $user->days[$dayNum]->status;
                            $user->days[$dayNum]->acronym .= $entry->acronym . ';' . $user->days[$dayNum]->acronym;
                        }
                    } else  {   //All day entry
                        $user->days[$dayNum]->id = $entry->id;
                        $user->days[$dayNum]->type = $entry->type;
                        $user->days[$dayNum]->display = $display;
                        $user->days[$dayNum]->status = $entry->status;
                        $user->days[$dayNum]->acronym = $entry->acronym;
                    }
                }
                $iDate->modify('+1 day');   //Next day
            }
        }
        return $user;
    }

    /**
     * List all duplicated leave requests (exact same dates, status, etc.)
     * Note: this doesn't detect overlapping requests.
     * @return array List of duplicated leave requests
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function detectDuplicatedRequests() {
        $this->db->select('leaves.id, CONCAT(users.firstname, \' \', users.lastname) as user_label', FALSE);
        $this->db->select('leaves.startdate, types.name as type_label');
        $this->db->from('leaves');
        $this->db->join('(SELECT * FROM leaves) dup', 'leaves.employee = dup.employee' .
                ' AND leaves.startdate = dup.startdate' .
                ' AND leaves.enddate = dup.enddate' .
                ' AND leaves.startdatetype = dup.startdatetype' .
                ' AND leaves.enddatetype = dup.enddatetype' .
                ' AND leaves.status = dup.status' .
                ' AND leaves.id != dup.id', 'inner');
        $this->db->join('users', 'users.id = leaves.employee', 'inner');
        $this->db->join('types', 'leaves.type = types.id', 'inner');
        $this->db->where('leaves.status', 3);   //Accepted
        $this->db->order_by("users.id", "asc");
        $this->db->order_by("leaves.startdate", "desc");
        return $this->db->get()->result_array();
    }

    /**
     * List all leave requests with a wrong date type (starting afternoon and ending morning of the same day)
     * @return array List of wrong leave requests
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function detectWrongDateTypes() {
        $this->db->select('leaves.*, CONCAT(users.firstname, \' \', users.lastname) as user_label', FALSE);
        $this->db->select('status.name as status_label');
        $this->db->from('leaves');
        $this->db->join('users', 'users.id = leaves.employee', 'inner');
        $this->db->join('status', 'leaves.status = status.id', 'inner');
        $this->db->where('leaves.startdatetype', 'Afternoon');
        $this->db->where('leaves.enddatetype', 'Morning');
        $this->db->where('leaves.startdate = leaves.enddate');
        $this->db->order_by("users.id", "asc");
        $this->db->order_by("leaves.startdate", "desc");
        return $this->db->get()->result_array();
    }

    /**
     * List of leave requests for which they are not entitled days on contracts or employee
     * Note: this might be an expected behaviour (avoid to track them into the balance report).
     * @return array List of duplicated leave requests
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function detectBalanceProblems() {
        $query = $this->db->query('SELECT CONCAT(users.firstname, \' \', users.lastname) AS user_label,
        contracts.id, contracts.name AS contract_label,
        types.name AS type_label,
        status.name AS status_label,
        leaves.*
        FROM leaves
        inner join users on leaves.employee = users.id
        inner join contracts on users.contract = contracts.id
        inner join types on types.id = leaves.type
        inner join status on status.id = leaves.status
        LEFT OUTER JOIN entitleddays
            ON (entitleddays.type = leaves.type AND
                (users.id = entitleddays.employee OR contracts.id = entitleddays.contract)
                        and entitleddays.startdate <= leaves.enddate AND entitleddays.enddate >= leaves.startdate)
        WHERE entitleddays.type IS NULL
        ORDER BY users.id ASC, leaves.startdate DESC', FALSE);
        return $query->result_array();
    }

    /**
     * List of leave requests overlapping on two yearly periods.
     * @return array List of overlapping leave requests
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function detectOverlappingProblems() {
        $query = $this->db->query('SELECT CONCAT(users.firstname, \' \', users.lastname) AS user_label,
            contracts.id AS contract_id, contracts.name AS contract_label,
            status.name AS status_label,
            leaves.*
            FROM leaves
            inner join users on leaves.employee = users.id
            inner join contracts on users.contract = contracts.id
            inner join status on status.id = leaves.status
            WHERE leaves.startdate < CAST(CONCAT(YEAR(leaves.enddate), \'-\', REPLACE(contracts.startentdate, \'/\', \'-\')) AS DATE)
            ORDER BY users.id ASC, leaves.startdate DESC', FALSE);
        return $query->result_array();
    }

    /**
     * Get one leave with his comment
     * @param int $leaveId Id of the leave request
     * @return array list of records
     * @author Emilien NICOLAS <milihhard1996@gmail.com>
     */
    public function getLeaveWithComments($leaveId = 0) {
        $this->db->select('leaves.*');
        $this->db->select('status.name as status_name, types.name as type_name');
        $this->db->from('leaves');
        $this->db->join('status', 'leaves.status = status.id');
        $this->db->join('types', 'leaves.type = types.id');
        $this->db->where('leaves.id', $leaveId);
        $leave = $this->db->get()->row_array();
        if(!empty($leave['comments'])){
          $leave['comments'] = json_decode($leave['comments']);
        } else {
          $leave['comments'] = null;
        }
        return $leave;
    }

    /**
     * Get the JSON representation of comments posted on a leave request
     * @param int $id Id of the leave request
     * @return array list of records
     * @author Emilien NICOLAS <milihhard1996@gmail.com>
     */
    public function getCommentsLeaveJson($id){

      $this->db->select('leaves.comments');
      $this->db->from('leaves');
      $this->db->where('leaves.id', "$id");
      return $this->db->get()->row_array();
    }

    /**
     * Get one leave with his comment
     * @param int $id Id of the leave request
     * @return array list of records
     * @author Emilien NICOLAS <milihhard1996@gmail.com>
     */
    public function getCommentsLeave($id){
      $request = $this->getCommentsLeaveJson($id);
      $json = $request["comments"];
      if(!empty($json)){
        return json_decode($json);
      } else {
        return null;
      }
    }

    private function getCommentLeaveAndStatus($id){
      $this->db->select('leaves.comments, leaves.status');
      $this->db->from('leaves');
      $this->db->where('leaves.id', "$id");
      $request = $this->db->get()->row_array();
      $json = $request["comments"];
      if(!empty($json)){
        $request["comments"] = json_decode($json);
      } else {
        $request["comments"] = null;
      }
      return $request;
    }

    /**
    * Update the comment of a Leave
    * @param int $id Id of the leave
    * @param string $json new json for the comments of the leave
    * @author Emilien NICOLAS <milihhard1996@gmail.com>
    */
    public function addComments($id, $json){
      $data = array(
          'comments' => $json
      );
      $this->db->where('id', $id);
      $this->db->update('leaves', $data);

      //Trace the modification if the feature is enabled
      if ($this->config->item('enable_history') === TRUE) {
          $this->load->model('history_model');
          $this->history_model->setHistory(2, 'leaves', $id, $this->session->userdata('id'));
      }
    }

    /**
    * Prepare the Json when the status is updated
    * @param int $id Id of the leave
    * @param int $status status which is updated
    * @return string json modified with the new status
    * @author Emilien NICOLAS <milihhard1996@gmail.com>
    */
    private function prepareCommentOnStatusChanged($id,$status){
      $request = $this->getCommentLeaveAndStatus($id);
      if($request['status'] === $status){
        return json_encode($request['comments']);
      } else {
        $json_parsed = $request['comments'];
        $comment_change = new stdClass;
        $comment_change->type = "change";
        $comment_change->status_number = $status;
        $comment_change->date = date("Y-n-j");
        if (isset($json_parsed)){
          array_push($json_parsed->comments, $comment_change);
        }else {
          $json_parsed = new stdClass;
          $json_parsed->comments = array($comment_change);
        }
        return json_encode($json_parsed);
      }
    }
}