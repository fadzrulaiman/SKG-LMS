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
     * Set sick leave for a specific year.
     * @param int $year The year to set sick leave for.
     */
    public function set_sickleave($year) {
        $query = "
            INSERT INTO entitleddays (contract, employee, overtime, startdate, enddate, type, days)
            SELECT 
                NULL AS contract,
                u.id AS employee,
                NULL AS overtime,
                CONCAT($year, '-01-01') AS startdate,
                CONCAT($year, '-12-31') AS enddate,
                2 AS type,
                14 AS days
            FROM 
                users u
            LEFT JOIN 
                entitleddays ed ON u.id = ed.employee 
                AND ed.type = 2
                AND ed.startdate = CONCAT($year, '-01-01')
                AND ed.enddate = CONCAT($year, '-12-31')
            WHERE 
                TIMESTAMPDIFF(YEAR, u.employmentdate, CURDATE()) < 2
                AND u.active = 1
                AND ed.id IS NULL

            UNION ALL

            SELECT 
                NULL AS contract,
                u.id AS employee,
                NULL AS overtime,
                CONCAT($year, '-01-01') AS startdate,
                CONCAT($year, '-12-31') AS enddate,
                2 AS type,
                18 AS days
            FROM 
                users u
            LEFT JOIN 
                entitleddays ed ON u.id = ed.employee 
                AND ed.type = 2
                AND ed.startdate = CONCAT($year, '-01-01')
                AND ed.enddate = CONCAT($year, '-12-31')
            WHERE 
                TIMESTAMPDIFF(YEAR, u.employmentdate, CURDATE()) >= 2
                AND TIMESTAMPDIFF(YEAR, u.employmentdate, CURDATE()) < 5
                AND u.active = 1
                AND ed.id IS NULL

            UNION ALL

            SELECT 
                NULL AS contract,
                u.id AS employee,
                NULL AS overtime,
                CONCAT($year, '-01-01') AS startdate,
                CONCAT($year, '-12-31') AS enddate,
                2 AS type,
                22 AS days
            FROM 
                users u
            LEFT JOIN 
                entitleddays ed ON u.id = ed.employee 
                AND ed.type = 2
                AND ed.startdate = CONCAT($year, '-01-01')
                AND ed.enddate = CONCAT($year, '-12-31')
            WHERE 
                TIMESTAMPDIFF(YEAR, u.employmentdate, CURDATE()) >= 5
                AND u.active = 1
                AND ed.id IS NULL;
        ";

        $this->db->query($query);
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
    
}
