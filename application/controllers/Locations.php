<?php
/**
 * This controller serves all the actions performed on locations
 * @copyright  Copyright (c) Fadzrul Aiman

 * @since         0.1.0
 */

if (!defined('BASEPATH')) { exit('No direct script access allowed'); }

/**
 * This controller serves all the actions performed on locations
 * A postion qualifies the job of an employee.
 * The list of postion is managed by the HR department.
 */
class Locations extends CI_Controller {

    /**
     * Default constructor
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function __construct() {
        parent::__construct();
        setUserContext($this);
        $this->load->model('locations_model');
        $this->lang->load('locations', $this->language);
    }

    /**
     * Display list of locations
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function index() {
        $this->auth->checkIfOperationIsAllowed('list_locations');
        $data = getUserContext($this);
        $this->lang->load('datatable', $this->language);
        $data['locations'] = $this->locations_model->getLocations();
        $data['title'] = lang('locations_index_title');
        $data['flash_partial_view'] = $this->load->view('templates/flash', $data, TRUE);
        $this->load->view('templates/header', $data);
        $this->load->view('menu/index', $data);
        $this->load->view('locations/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Display a popup showing the list of locations
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function select() {
        $this->auth->checkIfOperationIsAllowed('list_locations');
        $data = getUserContext($this);
        $this->lang->load('datatable', $this->language);
        $data['locations'] = $this->locations_model->getLocations();
        $this->load->view('locations/select', $data);
    }

    /**
     * Display a form that allows adding a location
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function create() {
        $this->auth->checkIfOperationIsAllowed('create_locations');
        $data = getUserContext($this);
        $this->load->helper('form');
        $this->load->library('form_validation');
        $data['title'] = lang('locations_create_title');

        $this->form_validation->set_rules('name', lang('locations_create_field_name'), 'required|strip_tags');

        if ($this->form_validation->run() === FALSE) {
            $this->load->view('templates/header', $data);
            $this->load->view('menu/index', $data);
            $this->load->view('locations/create', $data);
            $this->load->view('templates/footer');
        } else {
            $this->locations_model->setLocations($this->input->post('name'));
            $this->session->set_flashdata('msg', lang('locations_create_flash_msg'));
            redirect('locations');
        }
    }

    /**
     * Display a form that allows to edit a location
     * @param int $id location identifier
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function edit($id) {
        $this->auth->checkIfOperationIsAllowed('edit_locations');
        $data = getUserContext($this);
        $this->load->helper('form');
        $this->load->library('form_validation');
        $data['title'] = lang('locations_edit_title');
        $data['location'] = $this->locations_model->getLocations($id);
        //Check if exists
        if (empty($data['location'])) {
            redirect('notfound');
        }
        $this->form_validation->set_rules('name', lang('locations_edit_field_name'), 'required|strip_tags');

        if ($this->form_validation->run() === FALSE) {
            $this->load->view('templates/header', $data);
            $this->load->view('menu/index', $data);
            $this->load->view('locations/edit', $data);
            $this->load->view('templates/footer');
        } else {
            $this->locations_model->updateLocations($id, $this->input->post('name'));
            $this->session->set_flashdata('msg', lang('locations_edit_flash_msg'));
            redirect('locations');
        }
    }

    /**
     * Delete a location
     * @param int $id location identifier
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function delete($id) {
        $this->auth->checkIfOperationIsAllowed('delete_locations');
        $this->locations_model->deleteLocation($id);
        $this->session->set_flashdata('msg', lang('locations_delete_flash_msg'));
        redirect('locations');
    }

    /**
     * Export the list of all locations into an Excel file
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function export() {
        $this->auth->checkIfOperationIsAllowed('export_locations');
        $this->load->view('locations/export');
    }
}
