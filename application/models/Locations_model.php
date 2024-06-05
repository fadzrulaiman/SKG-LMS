<?php
/**
 * This Class contains all the business logic and the persistence layer for the locations.
 * @copyright  Copyright (c) Fadzrul Aiman

 * @since         0.1.0
 */

if (!defined('BASEPATH')) { exit('No direct script access allowed'); }

/**
 * This Class contains all the business logic and the persistence layer for the locations.
 * A postion describes the kind of job of an employee. As Jorani is not an HRM System,
 * This information has no technical value, but can be useful for an HR Manager for
 * verification purposes or if a location grants some kind of entitilments.
 */
class Locations_model extends CI_Model {

    /**
     * Default constructor
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function __construct() {

    }

    /**
     * Get the list of locations or one location
     * @param int $id optional id of a location
     * @return array record of locations
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function getLocations($id = 0) {
        if ($id === 0) {
            $query = $this->db->get('locations');
            return $query->result_array();
        }
        $query = $this->db->get_where('locations', array('id' => $id));
        return $query->row_array();
    }

    /**
     * Get the name of a location
     * @param int $id Identifier of the location
     * @return string Name of the location
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function getName($id) {
        $record = $this->getLocations($id);
        if (!empty($record)) {
            return $record['name'];
        } else {
            return '';
        }
    }

    /**
     * Insert a new location
     * @param string $name Name of the location
     * @return int number of affected rows
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function setLocations($name) {
        $data = array(
            'name' => $name,
        );
        return $this->db->insert('locations', $data);
    }

    /**
     * Delete a location from the database
     * Cascade update all users having this location (filled with 0)
     * @param int $id identifier of the location record
     * @return bool TRUE if the operation was successful, FALSE otherwise
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function deleteLocation($id) {
        $delete = $this->db->delete('locations', array('id' => $id));
        $data = array(
            'location' => 0
        );
        $this->db->where('location', $id);
        $update = $this->db->update('users', $data);
        return $delete && $update;
    }

    /**
     * Update a given location in the database.
     * @param int $id Identifier of the database
     * @param string $name Name of the location
     * @param string $description Description of the location
     * @return type
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function updateLocations($id, $name) {
        $data = array(
            'name' => $name,
        );
        $this->db->where('id', $id);
        return $this->db->update('locations', $data);
    }
}
