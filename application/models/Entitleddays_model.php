<?php
/**
 * This class contains the business logic and manages the persistence of entitled days.
 * @copyright  Copyright (c) Fadzrul Aiman

 * @since         0.1.0
 */

if (!defined('BASEPATH')) { exit('No direct script access allowed'); }

/**
 * This class contains the business logic and manages the persistence of entitled days.
 * Entitled days are a kind of leave credit given at a contract (many employees) or at employee level.
 */
class Entitleddays_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {
        
    }

    /**
     * Get the list of entitled days or one entitled day record associated to a contract
     * @param int $id optional id of a contract
     * @return array record of entitled days
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function getEntitledDaysForContract($contract) {
        $this->db->select('entitleddays.*, types.name as type_name');
        $this->db->from('entitleddays');
        $this->db->join('types', 'types.id = entitleddays.type');
        $this->db->order_by("startdate", "desc");
        $this->db->where('contract =', $contract);
        return $this->db->get()->result_array();
    }
    
    /**
     * Get the list of entitled days or one entitled day record associated to an employee
     * @param int $id optional id of an employee
     * @return array record of entitled days
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function getEntitledDaysForEmployee($employee) {
        $this->db->select('entitleddays.*, types.name as type_name');
        $this->db->from('entitleddays');
        $this->db->join('types', 'types.id = entitleddays.type');
        $this->db->order_by("startdate", "desc");
        $this->db->where('employee =', $employee);
        return $this->db->get()->result_array();
    }
    
    /**
     * Insert a new entitled days record (for a contract) into the database and return the id
     * @param int $contract_id contract identifier
     * @param date $startdate Start Date
     * @param date $enddate End Date
     * @param int $days number of days to be added
     * @param int $type Leave type (of the entitled days line)
     * @param int $description Description of the entitled days line
     * @return int last inserted id
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function addEntitledDaysToContract($contract_id, $startdate, $enddate, $days, $type, $description) {
        $data = array(
            'contract' => $contract_id,
            'startdate' => $startdate,
            'enddate' => $enddate,
            'days' => $days,
            'type' => $type,
            'description' => $description
        );
        $this->db->insert('entitleddays', $data);
        return $this->db->insert_id();
    }

    /**
     * Insert a new entitled days record (for an employee) into the database and return the id
     * @param int $user_id employee identifier
     * @param date $startdate Start Date
     * @param date $enddate End Date
     * @param int $days number of days to be added
     * @param int $type Leave type (of the entitled days line)
     * @param int $description Description of the entitled days line
     * @return int last inserted id
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function addEntitledDaysToEmployee($user_id, $startdate, $enddate, $days, $type, $description) {
        $data = array(
            'employee' => $user_id,
            'startdate' => $startdate,
            'enddate' => $enddate,
            'days' => $days,
            'type' => $type,
            'description' => $description
        );
        $this->db->insert('entitleddays', $data);
        return $this->db->insert_id();
    }
    
    /**
     * Delete an entitled days record from the database (for an employee or a contract)
     * @param int $id identifier of the entitleddays record
     * @return int number of rows affected
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function deleteEntitledDays($id) {
        return $this->db->delete('entitleddays', array('id' => $id));
    }

    /**
     * Delete entitled days attached to a user
     * @param int $id identifier of an employee
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function deleteEntitledDaysCascadeUser($id) {
        $this->db->delete('entitleddays', array('employee' => $id));
    }
    
    /**
     * Delete a entitled days attached to a contract
     * @param int $id identifier of a contract
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function deleteEntitledDaysCascadeContract($id) {
        $this->db->delete('entitleddays', array('contract' => $id));
    }
    
    /**
     * Update a record of entitled days (for an employee or a contract)
     * @param int $id line of entitled days identifier (row id)
     * @param date $startdate Start Date
     * @param date $enddate End Date
     * @param int $days number of days to be added
     * @param int $type Leave type (of the entitled days line)
     * @param int $description Description of the entitled days line
     * @return number of affected rows
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function updateEntitledDays($id, $startdate, $enddate, $days, $type, $description) {
        $data = array(
            'startdate' => $startdate,
            'enddate' => $enddate,
            'days' => $days,
            'type' => $type,
            'description' => $description
        );

        $this->db->where('id', $id);
        return $this->db->update('entitleddays', $data);
    }
    
    /**
     * Increase an entitled days row
     * @param int $id row identifier
     * @param float $step increment step
     * @return int number of affected rows
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function increase($id, $step) {
        if (!is_numeric($step)) $step = 1;
        $this->db->set('days', 'days + ' . $step, FALSE);
        $this->db->where('id', $id);
        return $this->db->update('entitleddays');
    }
    
    /**
     * Decrease an entitled days row
     * @param int $id row identifier
     * @param float $step increment step
     * @return int number of affected rows
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function decrease($id, $step) {
        if (!is_numeric($step)) $step = 1;
        $this->db->set('days', 'days - ' . $step, FALSE);
        $this->db->where('id', $id);
        return $this->db->update('entitleddays');
    }
    
    /**
     * Modify the the amount of days for a given entitled days row
     * @param int $id row identifier
     * @param float $days credit in days
     * @return int number of affected rows
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function updateNbOfDaysOfEntitledDaysRecord($id, $days) {
        if (!is_numeric($days)) $days = 1;
        $this->db->set('days', $days);
        $this->db->where('id', $id);
        return $this->db->update('entitleddays');
    }
    
    /**
     * Purge the table by deleting the records prior $toDate
     * @param date $toDate 
     * @return int number of affected rows
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function purgeEntitleddays($toDate) {
        $this->db->where('enddate <= ', $toDate);
        return $this->db->delete('entitleddays');
    }

    /**
     * Count the number of rows into the table
     * @return int number of rows
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function count() {
        $this->db->select('count(*) as number', FALSE);
        $this->db->from('entitleddays');
        $result = $this->db->get();
        return $result->row()->number;
    }
    
    /**
     * List all entitlements overflowing (more than one year).
     * @return array List of possible duplicated leave requests
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function detectOverflow() {
        //Note: the query below detects deletion problems:
        //SELECT * FROM entitleddays 
        //LEFT OUTER JOIN users ON entitleddays.employee = users.id 
        //LEFT OUTER JOIN contracts ON entitleddays.contract = contracts.id 
        //WHERE users.firstname IS NULL AND contracts.name IS NULL
        $this->db->select('CONCAT(users.firstname, \' \', users.lastname) as user_label', FALSE);
        $this->db->select('contracts.name as contract_label');
        $this->db->select('entitleddays.*');
        $this->db->from('entitleddays');
        $this->db->join('users', 'users.id = entitleddays.employee', 'left outer');
        $this->db->join('contracts', 'entitleddays.contract = contracts.id', 'left outer');
        $this->db->where('TIMESTAMPDIFF(YEAR, `startdate`, `enddate`) > 0');   //More than a year
        $this->db->order_by("contracts.id", "asc"); 
        $this->db->order_by("users.id", "asc");
        return $this->db->get()->result_array();
    }

    /**
     * Set sick leave for a specific year.
     * @param int $year The year to set sick leave for.
     */
    public function set_sickleave($year) {
        $startdate = $year . '-01-01';
        $enddate = $year . '-12-31';

        // Subquery for calculating sick leave days based on employment duration
        $this->db->select('NULL AS contract, u.id AS employee, NULL AS overtime, \'' . $startdate . '\' AS startdate, \'' . $enddate . '\' AS enddate, 2 AS type', false);
        $this->db->select('
            CASE 
                WHEN TIMESTAMPDIFF(YEAR, u.employmentdate, CURDATE()) < 2 THEN 14
                WHEN TIMESTAMPDIFF(YEAR, u.employmentdate, CURDATE()) < 5 THEN 18
                ELSE 22
            END AS days', false);
        $this->db->from('users u');
        $this->db->join('entitleddays ed', 'u.id = ed.employee AND ed.type = 2 AND ed.startdate = \'' . $startdate . '\' AND ed.enddate = \'' . $enddate . '\'', 'left');
        $this->db->where('u.active', 1);
        $this->db->where('ed.id IS NULL');

        $subquery = $this->db->get_compiled_select();

        // Main query to insert into entitleddays
        $this->db->query('
            INSERT INTO entitleddays (contract, employee, overtime, startdate, enddate, type, days) ' . $subquery);
    }

    /**
     * Get all entitled days.
     */
    public function sickleave_entitleddays() {
        $this->db->select('u.id as employee, u.firstname, u.lastname, ed.startdate, ed.enddate, ed.days');
        $this->db->from('entitleddays ed');
        $this->db->join('users u', 'u.id = ed.employee');
        $this->db->where('YEAR(ed.startdate)', date('Y'));
        $this->db->where('ed.type', 2);  // Assuming type 2 is for sick leave
        $query = $this->db->get();

        $result = $query->result_array();
        foreach ($result as &$row) {
            $row['employee_name'] = $row['firstname'] . ' ' . $row['lastname'];
        }
        return $result;
    }

    /**
     * Get entitled days for a specific year.
     * @param int $year The year to filter entitled days by.
     */
    public function sickleave_entitleddays_year($year) {
        $this->db->select('u.id as employee, u.firstname, u.lastname, ed.startdate, ed.enddate, ed.days');
        $this->db->from('entitleddays ed');
        $this->db->join('users u', 'u.id = ed.employee');
        $this->db->where('YEAR(ed.startdate)', $year);
        $this->db->where('ed.type', 2);  // Assuming type 2 is for sick leave
        $query = $this->db->get();

        $result = $query->result_array();
        foreach ($result as &$row) {
            $row['employee_name'] = $row['firstname'] . ' ' . $row['lastname'];
        }
        return $result;
    }

    public function nullsickleave_entitleddays_year($year) {
        $this->db->select('u.id as employee, u.firstname, u.lastname, ed.startdate, ed.enddate, ed.days, CONCAT(u.firstname, " ", u.lastname) as employee_name');
        $this->db->from('users u');
        $this->db->join('entitleddays ed', 'u.id = ed.employee AND YEAR(ed.startdate) = ' . $this->db->escape($year) . ' AND ed.type = 2', 'left');
        $this->db->where('ed.days IS NULL');
        $query = $this->db->get();
        
        return $query->result_array();
    }
    
    public function nullsickleave_entitleddays() {
        $current_year = date('Y');
        $this->db->select('u.id as employee, u.firstname, u.lastname, ed.startdate, ed.enddate, ed.days, CONCAT(u.firstname, " ", u.lastname) as employee_name');
        $this->db->from('users u');
        $this->db->join('entitleddays ed', 'u.id = ed.employee AND YEAR(ed.startdate) = ' . $this->db->escape($current_year) . ' AND ed.type = 2', 'left');
        $this->db->where('ed.days IS NULL');
        $query = $this->db->get();
        
        return $query->result_array();
    }

    /**
     * Get annual entitled days for a specific year.
     * @param int $year The year to filter entitled days by.
     */
    public function annualleave_entitleddays_year($year) {
        $this->db->select('c.id as contract_id, c.name as contract_name, ed.startdate, ed.enddate, ed.days');
        $this->db->from('entitleddays ed');
        $this->db->join('users u', 'u.id = ed.employee', 'left');
        $this->db->join('contracts c', 'u.contract = c.id OR ed.contract = c.id', 'left');
        $this->db->where('YEAR(ed.startdate)', $year);
        $this->db->where('ed.type', 1); 
        $this->db->where('ed.contract IS NOT NULL', null, false); // Ensuring contract is not NULL
        $query = $this->db->get();

        return $query->result_array();
    }

    /**
     * Get all annual entitled days.
     */
    public function annualleave_entitleddays() {
        $this->db->select('c.id as contract_id, c.name as contract_name, ed.startdate, ed.enddate, ed.days');
        $this->db->from('entitleddays ed');
        $this->db->join('users u', 'u.id = ed.employee', 'left');
        $this->db->join('contracts c', 'u.contract = c.id OR ed.contract = c.id', 'left');
        $this->db->where('YEAR(ed.startdate)', date('Y'));
        $this->db->where('ed.type', 1); 
        $this->db->where('ed.contract IS NOT NULL', null, false); // Ensuring contract is not NULL
        $query = $this->db->get();

        return $query->result_array();    
    }

    public function nullannualleave_entitleddays() {
        $current_year = date('Y');
        $this->db->select('u.id as contract_id, u.name as contract_name, ed.startdate, ed.enddate, ed.days');
        $this->db->from('contracts u');
        $this->db->join('entitleddays ed', 'u.id = ed.contract AND YEAR(ed.startdate) = ' . $this->db->escape($current_year) . ' AND ed.type = 1', 'left');
        $this->db->where('ed.days IS NULL');
        $this->db->where('u.id !=', 0);
        $query = $this->db->get();
        
        return $query->result_array();
    }
    
    public function nullannualleave_entitleddays_year($year) {
        $this->db->select('u.id as contract_id, u.name as contract_name, ed.startdate, ed.enddate, ed.days');
        $this->db->from('contracts u');
        $this->db->join('entitleddays ed', 'u.id = ed.contract AND YEAR(ed.startdate) = ' . $this->db->escape($year) . ' AND ed.type = 1', 'left');
        $this->db->where('ed.days IS NULL');
        $this->db->where('u.id !=', 0);
        $query = $this->db->get();
        
        return $query->result_array();
    }

    // Set annual leave entitlements for a specific year.
    public function set_annualleave($year) {
        $startdate = $year . '-01-01';
        $enddate = $year . '-12-31';

        // Define the case statement for the number of days
        $case_statement = "(CASE 
                                WHEN contracts.id = 1 THEN 32
                                WHEN contracts.id = 2 THEN 22
                                WHEN contracts.id = 3 THEN 24
                                WHEN contracts.id = 4 THEN 18
                                ELSE 0
                            END) AS days";

        // Subquery to check if entitleddays already exists
        $subquery = $this->db->select('1')
                             ->from('entitleddays ed')
                             ->where('ed.contract = contracts.id')
                             ->where('ed.type', 1)
                             ->where('ed.startdate', $startdate)
                             ->where('ed.enddate', $enddate)
                             ->get_compiled_select();

        // Main query to insert annual leave entitlements
        $this->db->select('contracts.id AS contract, NULL AS employee, NULL AS overtime, \'' . $startdate . '\' AS startdate, \'' . $enddate . '\' AS enddate, 1 AS type, ' . $case_statement, false)
                 ->from('contracts')
                 ->where('contracts.id !=', 0)
                 ->where('NOT EXISTS (' . $subquery . ')', null, false);

        $insert_query = $this->db->get_compiled_select();
        $this->db->query('INSERT INTO entitleddays (contract, employee, overtime, startdate, enddate, type, days) ' . $insert_query);
    }

    // Retrieve the balance of leave types 1 and 3 for each employee from the previous year
    public function get_leave_balances() {
        $query = $this->db->query("
        SELECT 
        u.id AS employee_id,
        u.contract AS contract_id,
        COALESCE(SUM(CASE WHEN e.type = 1 THEN e.days ELSE 0 END), 0) - 
            (SELECT COALESCE(SUM(l.duration), 0) 
             FROM leaves l 
             WHERE l.employee = u.id 
               AND l.type = 1 
               AND l.status IN (2, 3, 7) 
               AND YEAR(l.startdate) = YEAR(CURDATE()) - 1) AS leave_type_1_balance,
        COALESCE(SUM(CASE WHEN e.type = 3 THEN e.days ELSE 0 END), 0) - 
            (SELECT COALESCE(SUM(l.duration), 0) 
             FROM leaves l 
             WHERE l.employee = u.id 
               AND l.type = 3 
               AND l.status IN (2, 3, 7) 
               AND YEAR(l.startdate) = YEAR(CURDATE()) - 1) AS leave_type_3_balance
    FROM 
        users u
    LEFT JOIN 
        entitleddays e ON u.id = e.employee OR (u.contract = e.contract AND e.employee IS NULL)
    WHERE 
        YEAR(e.startdate) = YEAR(CURDATE()) - 1
    GROUP BY 
        u.id, u.contract;
");
        return $query->result_array();
    }

    // Check if entitled days already exist for the employee and type in the current year
    public function entitled_days_exist($employee_id, $type) {
        $query = $this->db->get_where('entitleddays', array(
            'employee' => $employee_id,
            'type' => $type,
            'startdate' => date('Y-01-01'),
            'enddate' => date('Y-12-31')
        ));
        return $query->num_rows() > 0;
    }

    // Insert entitled days for leave type 3
    public function insert_entitled_days($employee_id, $contract_id, $entitled_days) {
        // Check if entitled days already exist
        if ($this->entitled_days_exist($employee_id, 3)) {
            return false; // Entitled days already exist, do not insert
        }
        
        $previous_year = date('Y') - 1;
        $data = array(
            'employee' => $employee_id,
            'type' => 3,
            'days' => $entitled_days,
            'startdate' => date('Y-01-01'),
            'enddate' => date('Y-12-31'),
            'description' => 'Carry forward balance from ' . $previous_year
        );
        return $this->db->insert('entitleddays', $data);
    }

    // Calculate and insert entitled days for each employee
    public function calculate_and_insert_entitled_days() {
        $max_leave_days = [
            1 => 32,
            2 => 22,
            3 => 24,
            4 => 18,
        ];

        $leave_data = $this->get_leave_balances();

        foreach ($leave_data as $data) {
            $employee_id = $data['employee_id'];
            $contract_id = $data['contract_id'];
            $leave_balance = $data['leave_type_1_balance'] + $data['leave_type_3_balance'];
            
            // Determine the maximum leave days based on the contract
            $max_days = isset($max_leave_days[$contract_id]) ? $max_leave_days[$contract_id] : 0;
            
            // Ensure the leave balance does not exceed the maximum days
            $entitled_days = min($leave_balance, $max_days);
            
            // Insert the entitled days for leave type 3
            $this->insert_entitled_days($employee_id, $contract_id, $entitled_days);
        }
    }

    // Retrieve the leave entitlements and balances
    public function get_bankentitlement() {
        $this->db->select("
            users.id AS employee_id,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname,
            contracts.name AS contract_name,
            -- Annual leave balance for last year
            (COALESCE(eal.days, 0) + COALESCE(cal.days, 0) - COALESCE(alu.duration, 0)) AS annual_leave_balance_last_year,
            -- Leave bank balance for last year
            (COALESCE(elb.days, 0) + COALESCE(clb.days, 0) - COALESCE(lbu.duration, 0)) AS leave_bank_balance_last_year,
            -- Leave bank entitlement for current year
            (COALESCE(elbc.days, 0) + COALESCE(clbc.days, 0)) AS leave_bank_entitlement_this_year,
            -- Burned leave
            ((COALESCE(eal.days, 0) + COALESCE(cal.days, 0) - COALESCE(alu.duration, 0)) + 
             (COALESCE(elb.days, 0) + COALESCE(clb.days, 0) - COALESCE(lbu.duration, 0)) - 
             (COALESCE(elbc.days, 0) + COALESCE(clbc.days, 0))) AS leave_burned
        ", false);
        $this->db->from('users');
        $this->db->join('contracts', 'users.contract = contracts.id', 'left');
        // Entitlement for annual leave last year
        $this->db->join('(SELECT employee, SUM(days) AS days FROM entitleddays WHERE type = 1 AND YEAR(enddate) = YEAR(CURDATE()) - 1 GROUP BY employee) eal', 'eal.employee = users.id', 'left');
        $this->db->join('(SELECT contract, SUM(days) AS days FROM entitleddays WHERE type = 1 AND YEAR(enddate) = YEAR(CURDATE()) - 1 GROUP BY contract) cal', 'cal.contract = users.contract', 'left');
        // Entitlement for leave bank last year
        $this->db->join('(SELECT employee, SUM(days) AS days FROM entitleddays WHERE type = 3 AND YEAR(enddate) = YEAR(CURDATE()) - 1 GROUP BY employee) elb', 'elb.employee = users.id', 'left');
        $this->db->join('(SELECT contract, SUM(days) AS days FROM entitleddays WHERE type = 3 AND YEAR(enddate) = YEAR(CURDATE()) - 1 GROUP BY contract) clb', 'clb.contract = users.contract', 'left');
        // Entitlement for leave bank current year
        $this->db->join('(SELECT employee, SUM(days) AS days FROM entitleddays WHERE type = 3 AND YEAR(enddate) = YEAR(CURDATE()) GROUP BY employee) elbc', 'elbc.employee = users.id', 'left');
        $this->db->join('(SELECT contract, SUM(days) AS days FROM entitleddays WHERE type = 3 AND YEAR(enddate) = YEAR(CURDATE()) GROUP BY contract) clbc', 'clbc.contract = users.contract', 'left');
        // Leave taken for annual leave last year
        $this->db->join('(SELECT employee, SUM(duration) AS duration FROM leaves WHERE type = 1 AND YEAR(startdate) = YEAR(CURDATE()) - 1 AND status IN (2, 3, 7) GROUP BY employee) alu', 'alu.employee = users.id', 'left');
        // Leave taken for leave bank last year
        $this->db->join('(SELECT employee, SUM(duration) AS duration FROM leaves WHERE type = 3 AND YEAR(startdate) = YEAR(CURDATE()) - 1 AND status IN (2, 3, 7) GROUP BY employee) lbu', 'lbu.employee = users.id', 'left');
        $this->db->where('users.active', 1);
        $this->db->order_by('users.id', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }

    // Function to get the leave balances for each employee based on a specific year
    public function get_leavebankbalances($year) {
        $previous_year = $year - 1;
        $this->db->select("
            users.id AS employee_id,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname,
            contracts.name AS contract_name,
            -- Annual leave balance for last year
            (COALESCE(eal.days, 0) + COALESCE(cal.days, 0) - COALESCE(alu.duration, 0)) AS annual_leave_balance_last_year,
            -- Leave bank balance for last year
            (COALESCE(elb.days, 0) + COALESCE(clb.days, 0) - COALESCE(lbu.duration, 0)) AS leave_bank_balance_last_year,
            -- Leave bank entitlement for current year
            (COALESCE(elbc.days, 0) + COALESCE(clbc.days, 0)) AS leave_bank_entitlement_this_year,
            -- Burned leave
            ((COALESCE(eal.days, 0) + COALESCE(cal.days, 0) - COALESCE(alu.duration, 0)) + 
             (COALESCE(elb.days, 0) + COALESCE(clb.days, 0) - COALESCE(lbu.duration, 0)) - 
             (COALESCE(elbc.days, 0) + COALESCE(clbc.days, 0))) AS leave_burned
        ", false);
        $this->db->from('users');
        $this->db->join('contracts', 'users.contract = contracts.id', 'left');
        // Entitlement for annual leave last year
        $this->db->join("(SELECT employee, SUM(days) AS days FROM entitleddays WHERE type = 1 AND YEAR(enddate) = $previous_year GROUP BY employee) eal", 'eal.employee = users.id', 'left');
        $this->db->join("(SELECT contract, SUM(days) AS days FROM entitleddays WHERE type = 1 AND YEAR(enddate) = $previous_year GROUP BY contract) cal", 'cal.contract = users.contract', 'left');
        // Entitlement for leave bank last year
        $this->db->join("(SELECT employee, SUM(days) AS days FROM entitleddays WHERE type = 3 AND YEAR(enddate) = $previous_year GROUP BY employee) elb", 'elb.employee = users.id', 'left');
        $this->db->join("(SELECT contract, SUM(days) AS days FROM entitleddays WHERE type = 3 AND YEAR(enddate) = $previous_year GROUP BY contract) clb", 'clb.contract = users.contract', 'left');
        // Entitlement for leave bank current year
        $this->db->join("(SELECT employee, SUM(days) AS days FROM entitleddays WHERE type = 3 AND YEAR(enddate) = $year GROUP BY employee) elbc", 'elbc.employee = users.id', 'left');
        $this->db->join("(SELECT contract, SUM(days) AS days FROM entitleddays WHERE type = 3 AND YEAR(enddate) = $year GROUP BY contract) clbc", 'clbc.contract = users.contract', 'left');
        // Leave taken for annual leave last year
        $this->db->join("(SELECT employee, SUM(duration) AS duration FROM leaves WHERE type = 1 AND YEAR(startdate) = $previous_year AND status IN (2, 3, 7) GROUP BY employee) alu", 'alu.employee = users.id', 'left');
        // Leave taken for leave bank last year
        $this->db->join("(SELECT employee, SUM(duration) AS duration FROM leaves WHERE type = 3 AND YEAR(startdate) = $previous_year AND status IN (2, 3, 7) GROUP BY employee) lbu", 'lbu.employee = users.id', 'left');
        $this->db->where('users.active', 1);
        $this->db->order_by('users.id', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }
    
}