<?php
/**
 * This controller contains the actions allowing an employee to list and manage its leave requests
 * @copyright  Copyright (c) Fadzrul Aiman

 * @since         0.1.0
 */

if (!defined('BASEPATH')) { exit('No direct script access allowed'); }

//We can define custom triggers before saving the leave request into the database
require_once FCPATH . "local/triggers/leave.php";

/**
 * This class allows an employee to list and manage its leave requests
 * Since 0.4.3 a trigger is called at the creation, if the function triggerCreateLeaveRequest is defined
 * see content of /local/triggers/leave.php
 */
class Leaves extends CI_Controller {

    /**
     * Default constructor
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function __construct() {
        parent::__construct();
        setUserContext($this);
        $this->load->model('leaves_model');
        $this->load->model('types_model');
        $this->lang->load('leaves', $this->language);
        $this->lang->load('global', $this->language);
    }

    /**
     * Display the list of the leave requests of the connected user
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function index() {
        $this->auth->checkIfOperationIsAllowed('list_leaves');
        $data = getUserContext($this);
        $this->lang->load('datatable', $this->language);
        if ($this->config->item('enable_history') == TRUE){
          $data['leaves'] = $this->leaves_model->getLeavesOfEmployeeWithHistory($this->session->userdata('id'));
        } else {
          $data['leaves'] = $this->leaves_model->getLeavesOfEmployee($this->session->userdata('id'));
        }
        $data['types'] = $this->types_model->getTypes();
        $data['title'] = lang('leaves_index_title');
        $data['flash_partial_view'] = $this->load->view('templates/flash', $data, TRUE);
        $this->load->view('templates/header', $data);
        $this->load->view('menu/index', $data);
        $this->load->view('leaves/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Display the history of changes of a leave request
     * @param int $id Identifier of the leave request
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function history($id) {
        $this->auth->checkIfOperationIsAllowed('list_leaves');
        $data = getUserContext($this);
        $this->lang->load('datatable', $this->language);
        $data['leave'] = $this->leaves_model->getLeaves($id);
        $this->load->model('history_model');
        $data['events'] = $this->history_model->getLeaveRequestsHistory($id);
        $this->load->view('leaves/history', $data);
    }

    /**
     * Display the details of leaves taken/entitled for the connected user
     * @param string $refDate Date (e.g. 2011-10-05)
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function counters($refDate = NULL) {
        $this->auth->checkIfOperationIsAllowed('counters_leaves');
        $data = getUserContext($this);
        $this->lang->load('datatable', $this->language);
        if ($refDate != NULL) {
            $data['isDefault'] = 0;
        } else {
            $refDate = date("Y-m-d");
            $data['isDefault'] = 1;
        }
        $data['refDate'] = $refDate;
        $data['summary'] = $this->leaves_model->getLeaveBalanceForEmployee($this->user_id, FALSE, $refDate);

        if (!is_null($data['summary'])) {
            $data['title'] = lang('leaves_summary_title');
            $this->load->view('templates/header', $data);
            $this->load->view('menu/index', $data);
            $this->load->view('leaves/counters', $data);
            $this->load->view('templates/footer');
        } else {
            $this->session->set_flashdata('msg', lang('leaves_summary_flash_msg_error'));
            redirect('leaves');
        }
    }

/**
 * Display a leave request
 * @param string $source Page source (leaves, requests) (self, manager)
 * @param int $id identifier of the leave request
 * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
 */
public function view($source, $id) {
    $this->auth->checkIfOperationIsAllowed('view_leaves');
    $this->load->model('users_model');
    $this->load->model('status_model');
    $this->load->helper('form');
    $data = getUserContext($this);
    $data['leave'] = $this->leaves_model->getLeaveWithComments($id);
    if (empty($data['leave'])) {
        redirect('notfound');
    }
    // If the user is not HR, not manager, and not the creator of the leave
    // the employee can't see it, redirect to LR list
    if ($data['leave']['employee'] != $this->user_id) {
        if ((!$this->is_hr)) {
            $this->load->model('users_model');
            $employee = $this->users_model->getUsers($data['leave']['employee']);
            if ($employee['manager'] != $this->user_id) {
                $this->load->model('delegations_model');
                if (!$this->delegations_model->isDelegateOfManager($this->user_id, $employee['manager'])) {
                    log_message('error', 'User #' . $this->user_id . ' illegally tried to view leave #' . $id);
                    redirect('leaves');
                }
            }
        } //Admin
    } //Current employee
    $data['source'] = $source;
    // Overwrite source (for taking into account the tabular calendar)
    if ($this->input->get('source') != NULL) {
        $data['source'] = urldecode($this->input->get('source'));
    }

    $data['title'] = lang('leaves_view_html_title');
    if ($source == 'requests') {
        if (empty($employee)) {
            $this->load->model('users_model');
            $data['name'] = $this->users_model->getName($data['leave']['employee']);
        } else {
            $data['name'] = $employee['firstname'] . ' ' . $employee['lastname'];
        }
    } else {
        $data['name'] = '';
    }
    if (isset($data["leave"]["comments"])) {
        $last_comment = new stdClass();;
        foreach ($data["leave"]["comments"]->comments as $comments_item) {
            if ($comments_item->type == "comment") {
                $comments_item->author = $this->users_model->getName($comments_item->author);
                $comments_item->in = "in";
                $last_comment->in = "";
                $last_comment = $comments_item;
            } else if ($comments_item->type == "change") {
                $comments_item->status = $this->status_model->getName($comments_item->status_number);
            }
        }
    }
    // Pass attachment information to the view
    $data['attachment_path'] = $data['leave']['attachment'];

    $this->load->view('templates/header', $data);
    $this->load->view('menu/index', $data);
    $this->load->view('leaves/view', $data);
    $this->load->view('templates/footer');
}

    /**
     * Create a new comment or append a comment to the comments
     * on a leave request
     * @param int $id Id of the leave request
     * @param string $source Page where we redirect after posting
     * @author Emilien NICOLAS <milihhard1996@gmail.com>
     */
    public function createComment($id, $source = "leaves/leaves"){
      $this->auth->checkIfOperationIsAllowed('view_leaves');
      $data = getUserContext($this);
      $oldComment = $this->leaves_model->getCommentsLeave($id);
      $newComment = new stdClass;
      $newComment->type = "comment";
      $newComment->author = $this->session->userdata('id');
      $newComment->value = $this->input->post('comment', TRUE);
      $newComment->date = date("Y-n-j");
      if ($oldComment != NULL){
        array_push($oldComment->comments, $newComment);
      }else {
        $oldComment = new stdClass;
        $oldComment->comments = array($newComment);
      }
      $json = json_encode($oldComment);
      $this->leaves_model->addComments($id, $json);
      if(isset($_GET['source'])){
        $source = $_GET['source'];
      }
      redirect("/$source/$id");
    }

    public function create() {
        // Check if the user has the permission to create leave requests
        $this->auth->checkIfOperationIsAllowed('create_leaves');
        
        // Get user context and load necessary helpers and libraries
        $data = getUserContext($this);
        $this->load->helper('form');
        $this->load->library('form_validation');
        
        // Set page title and help link
        $data['title'] = lang('leaves_create_title');
    
        // Form validation rules
        $this->form_validation->set_rules('startdate', lang('leaves_create_field_start'), 'required|strip_tags');
        $this->form_validation->set_rules('startdatetype', 'Start Date type', 'required|strip_tags');
        $this->form_validation->set_rules('enddate', lang('leaves_create_field_end'), 'required|strip_tags');
        $this->form_validation->set_rules('enddatetype', 'End Date type', 'required|strip_tags');
        $this->form_validation->set_rules('duration', lang('leaves_create_field_duration'), 'required|strip_tags');
        $this->form_validation->set_rules('type', lang('leaves_create_field_type'), 'required|strip_tags');
        $this->form_validation->set_rules('cause', lang('leaves_create_field_cause'), 'strip_tags');
        $this->form_validation->set_rules('status', lang('leaves_create_field_status'), 'required|strip_tags');
    
        // Check if the form validation passes
        if ($this->form_validation->run() === FALSE) {
            // Load models
            $this->load->model('leaves_model');
            $this->load->model('contracts_model');
            
            // Fetch leave balances and types for the employee
            $employeeId = $this->session->userdata('id');
            $data['leaveBalances'] = $this->leaves_model->getLeaveBalanceForAllTypes($employeeId);
            $leaveTypesDetails = $this->contracts_model->getLeaveTypesDetailsOTypesForUser($employeeId);
            
            // Populate leave form data
            $data['defaultType'] = $leaveTypesDetails->defaultType;
            $data['credit'] = $leaveTypesDetails->credit;
            $data['types'] = $leaveTypesDetails->types;
            
            // Load views
            $this->load->view('templates/header', $data);
            $this->load->view('menu/index', $data);
            $this->load->view('leaves/create', $data); // Pass data to the view
            $this->load->view('templates/footer');
        } else {
            // Handle attachment upload
            $attachment_path = $this->handleAttachmentUpload();
            
            // Create the leave request in the database
            $leave_id = $this->leaves_model->setLeaves($this->session->userdata('id'), $attachment_path);
            
            // Set a flash message indicating success
            $this->session->set_flashdata('msg', lang('leaves_create_flash_msg_success'));
            
            // If the leave request status is 'requested', send an email to the manager
            if ($this->input->post('status') == LMS_REQUESTED) {
                $this->sendMailOnLeaveRequestCreation($leave_id);
            }
            
            // Redirect to the source page or the leaves page
            if (isset($_GET['source'])) {
                redirect($_GET['source']);
            } else {
                redirect('leaves');
            }
        }
    }
                
    /**
     * Edit a leave request
     * @param int $id Identifier of the leave request
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function edit($id) {
        $this->auth->checkIfOperationIsAllowed('edit_leaves');
        $this->load->model('users_model');
        $this->load->model('status_model');
        $data = getUserContext($this);
        $data['leave'] = $this->leaves_model->getLeaveWithComments($id);
        //Check if exists
        if (empty($data['leave'])) {
            redirect('notfound');
        }
        //If the user is not its own manager and if the leave is
        //already requested, the employee can't modify it
        if (!$this->is_hr) {
            if (($this->session->userdata('manager') != $this->user_id) &&
                $data['leave']['status'] != LMS_PLANNED) {
                if ($this->config->item('edit_rejected_requests') == FALSE ||
                    $data['leave']['status'] != LMS_REJECTED) {//Configuration switch that allows editing the rejected leave requests
                    log_message('error', 'User #' . $this->user_id . ' illegally tried to edit leave #' . $id);
                    $this->session->set_flashdata('msg', lang('leaves_edit_flash_msg_error'));
                    redirect('leaves');
                }
            }
        } //Admin

        $this->load->helper('form');
        $this->load->library('form_validation');
        $this->form_validation->set_rules('startdate', lang('leaves_edit_field_start'), 'required|strip_tags');
        $this->form_validation->set_rules('startdatetype', 'Start Date type', 'required|strip_tags');
        $this->form_validation->set_rules('enddate', lang('leaves_edit_field_end'), 'required|strip_tags');
        $this->form_validation->set_rules('enddatetype', 'End Date type', 'required|strip_tags');
        $this->form_validation->set_rules('duration', lang('leaves_edit_field_duration'), 'required|strip_tags');
        $this->form_validation->set_rules('type', lang('leaves_edit_field_type'), 'required|strip_tags');
        $this->form_validation->set_rules('cause', lang('leaves_edit_field_cause'), 'strip_tags');
        $this->form_validation->set_rules('status', lang('leaves_edit_field_status'), 'required|strip_tags');

        if ($this->form_validation->run() === FALSE) {
            $data['title'] = lang('leaves_edit_html_title');
            $data['id'] = $id;
            $this->load->model('contracts_model');
            $leaveTypesDetails = $this->contracts_model->getLeaveTypesDetailsOTypesForUser($data['leave']['employee'], $data['leave']['type']);
            $data['defaultType'] = $leaveTypesDetails->defaultType;
            $data['credit'] = $leaveTypesDetails->credit;
            $data['types'] = $leaveTypesDetails->types;
            $this->load->model('users_model');
            $data['name'] = $this->users_model->getName($data['leave']['employee']);
            if (isset($data["leave"]["comments"])){
            $last_comment = new stdClass();;
            foreach ($data["leave"]["comments"]->comments as $comments_item) {
                if($comments_item->type == "comment"){
                $comments_item->author = $this->users_model->getName($comments_item->author);
                $comments_item->in = "in";
                $last_comment->in="";
                $last_comment=$comments_item;
                } else if($comments_item->type == "change"){
                $comments_item->status = $this->status_model->getName($comments_item->status_number);
                }
            }
            }
                // Pass attachment information to the view
        $data['attachment_path'] = $data['leave']['attachment'];

            $this->load->view('templates/header', $data);
            $this->load->view('menu/index', $data);
            $this->load->view('leaves/edit', $data);
            $this->load->view('templates/footer');
        } else {
            //Prevent thugs to auto validate their leave requests
            if (!$this->is_hr && !$this->is_admin) {
                if ($this->input->post('status') == LMS_ACCEPTED) {
                    log_message('error', 'User #' . $this->session->userdata('id') . 
                        ' tried to submit a LR with an wrong status = ' . $this->input->post('status'));
                    $_POST['status'] = LMS_REQUESTED;
                }
                if ($this->input->post('status') == LMS_CANCELED) {
                    log_message('error', 'User #' . $this->session->userdata('id') . 
                        ' tried to submit a LR with an wrong status = ' . $this->input->post('status'));
                    $_POST['status'] = LMS_CANCELLATION;
                }
            }

            // Handle attachment upload
            $attachment_path = $this->handleAttachmentUpload();

            //Users must use an existing leave type, otherwise
            //force leave type to default leave type
            $this->load->model('contracts_model');
            $leaveTypesDetails = $this->contracts_model->getLeaveTypesDetailsOTypesForUser($this->session->userdata('id'));
            if (!array_key_exists($this->input->post('type'), $leaveTypesDetails->types)) {
                log_message('error', 'User #' . $this->session->userdata('id') . ' tried to submit an wrong LR type = ' . 
                $this->input->post('type'));
                $_POST['type'] = $leaveTypesDetails->defaultType;
                log_message('debug', 'LR type forced to ' . $leaveTypesDetails->defaultType); 
            }

            $this->leaves_model->updateLeaves($id, $attachment_path); // Pass attachment path to the model
            $this->session->set_flashdata('msg', lang('leaves_edit_flash_msg_success'));
            //If the status is requested or cancellation, send an email to the manager
            if ($this->input->post('status') == LMS_REQUESTED) {
                $this->sendMailOnLeaveRequestCreation($id);
            }
            if ($this->input->post('status') == LMS_CANCELLATION) {
                $this->sendMailOnLeaveRequestCreation($id);
            }
            if (isset($_GET['source'])) {
                redirect($_GET['source']);
            } else {
                redirect('leaves');
            }
        }
    }    
    private function handleAttachmentUpload() {
        $attachment_path = ''; // Initialize attachment path
    
        if (!empty($_FILES['attachment']['name'])) {
            $config['upload_path'] = 'assets/uploads/'; // Specify the upload directory
            $config['allowed_types'] = 'gif|jpg|png|jpeg|pdf'; // Specify the allowed file types
            $config['max_size'] = 5048; // Specify the maximum file size in kilobytes
    
            $this->load->library('upload', $config);
    
            if (!$this->upload->do_upload('attachment')) {
                // Handle the upload error
                $error = $this->upload->display_errors();
                // You can log the error or display it to the user
            } else {
                // File uploaded successfully
                $attachment_data = $this->upload->data();
                $attachment_path = 'assets/uploads/' . $attachment_data['file_name']; // Get the relative path of the uploaded file
    
                // Check if the uploaded file is an image
                if (in_array($attachment_data['file_type'], ['image/jpeg', 'image/png', 'image/gif'])) {
                    $this->addWatermark($attachment_path);
                }
            }
        }
    
        return $attachment_path;
    }
    
    private function addWatermark($filePath) {
        // Load the image
        $imageType = exif_imagetype($filePath);
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($filePath);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($filePath);
                break;
            default:
                return; // Unsupported image type
        }
    
        // Load the watermark image
        $watermark = imagecreatefrompng('assets/images/watermark.png'); // Adjust the path to your watermark image
    
        // Get the dimensions of both images
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);
        $watermarkWidth = imagesx($watermark);
        $watermarkHeight = imagesy($watermark);
    
        // Apply 50% transparency to the watermark
        $tempWatermark = imagecreatetruecolor($watermarkWidth, $watermarkHeight);
        imagealphablending($tempWatermark, false);
        imagesavealpha($tempWatermark, true);
        $transparent = imagecolorallocatealpha($tempWatermark, 0, 0, 0, 127);
        imagefilledrectangle($tempWatermark, 0, 0, $watermarkWidth, $watermarkHeight, $transparent);
    
        // Merge watermark with 50% transparency
        imagecopy($tempWatermark, $watermark, 0, 0, 0, 0, $watermarkWidth, $watermarkHeight);
        imagefilter($tempWatermark, IMG_FILTER_COLORIZE, 0, 0, 0, 63); // 50% transparency
    
        // Tile the watermark across the entire image
        for ($y = 0; $y < $imageHeight; $y += $watermarkHeight) {
            for ($x = 0; $x < $imageWidth; $x += $watermarkWidth) {
                imagecopy($image, $tempWatermark, $x, $y, 0, 0, $watermarkWidth, $watermarkHeight);
            }
        }
    
        // Save the image
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                imagejpeg($image, $filePath);
                break;
            case IMAGETYPE_PNG:
                imagepng($image, $filePath);
                break;
            case IMAGETYPE_GIF:
                imagegif($image, $filePath);
                break;
        }
    
        // Free up memory
        imagedestroy($image);
        imagedestroy($watermark);
        imagedestroy($tempWatermark);
    }
    
    
    /**
     * change a the status of a planned request to  requested
     * @param int $id id of the leave
     * @author Emilien NICOLAS <milihhard1996@gmail.com>
     */
     public function requestLeave($id){
       $leave = $this->leaves_model->getLeaves($id);
       if (empty($leave)) {
           redirect('notfound');
       } else {
           //Only the connected user can reject its own requests
           if ($this->user_id != $leave['employee']){
               $this->session->set_flashdata('msg', lang('leaves_cancellation_flash_msg_error'));
               redirect('leaves');
           }
           //We can cancel a leave request only with a status 'Accepted'
           if ($leave['status'] == LMS_PLANNED) {
               $this->leaves_model->switchStatus($id, LMS_REQUESTED);
               $this->sendMailOnLeaveRequestCreation($id);
               $this->session->set_flashdata('msg', lang('leaves_cancellation_flash_msg_success'));
               redirect('leaves');
           } else {
               $this->session->set_flashdata('msg', lang('leaves_cancellation_flash_msg_error'));
               redirect('leaves');
           }
       }
     }

    /**
     * Send an email reminder (so as to remind to the manager that he
     * must either accept/reject a request or a cancellation)
     * @param int $id Identifier of the leave request
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function reminder($id) {
        $this->auth->checkIfOperationIsAllowed('create_leaves');
        $data = getUserContext($this);
        $leave = $this->leaves_model->getLeaves($id);
        switch($leave['status']) {
            case LMS_REQUESTED: //Requested
                $this->sendMailOnLeaveRequestCreation($id, TRUE);
                break;
            case LMS_CANCELLATION: //Cancellation
                $this->sendMailOnLeaveRequestCancellation($id, TRUE);
                break;
        }
        $this->session->set_flashdata('msg', lang('leaves_reminder_flash_msg_success'));
        if (isset($_GET['source'])) {
            redirect($_GET['source']);
        } else {
            redirect('leaves');
        }
    }

    /**
     * Send a leave request creation email to the manager of the connected employee
     * @param int $id Leave request identifier
     * @param int $reminder In case where the employee wants to send a reminder
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    private function sendMailOnLeaveRequestCreation($id, $reminder=FALSE) {
        $this->load->model('users_model');
        $this->load->model('types_model');
        $this->load->model('delegations_model');
        //We load everything from DB as the LR can be edited from HR/Employees
        $leave = $this->leaves_model->getLeaves($id);
        $user = $this->users_model->getUsers($leave['employee']);
        $manager = $this->users_model->getUsers($user['manager']);
        if (empty($manager['email'])) {
            $this->session->set_flashdata('msg', lang('leaves_create_flash_msg_error'));
        } else {
            //Send an e-mail to the manager
            $this->load->library('email');
            $this->load->library('polyglot');
            $usr_lang = $this->polyglot->code2language($manager['language']);

            //We need to instance an different object as the languages of connected user may differ from the UI lang
            $lang_mail = new CI_Lang();
            $lang_mail->load('email', $usr_lang);
            $lang_mail->load('global', $usr_lang);

            if ($reminder) {
                $this->sendGenericMail($leave, $user, $manager, $lang_mail,
                    $lang_mail->line('email_leave_request_reminder') . ' ' .
                    $lang_mail->line('email_leave_request_creation_title'),
                    $lang_mail->line('email_leave_request_reminder') . ' ' .
                    $lang_mail->line('email_leave_request_creation_subject'),
                    'request');
            } else {
                $this->sendGenericMail($leave, $user, $manager, $lang_mail,
                    $lang_mail->line('email_leave_request_creation_title'),
                    $lang_mail->line('email_leave_request_creation_subject'),
                    'request');
            }
        }
    }

    /**
     * Send a notification to the manager of the connected employee when the
     * leave request has been canceled by its collaborator.
     * @param int $id Leave request identifier
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    private function sendMailOnLeaveRequestCanceled($id) {
        $this->load->model('users_model');
        $this->load->model('types_model');
        $this->load->model('delegations_model');
        //We load everything from DB as the LR can be edited from HR/Employees
        $leave = $this->leaves_model->getLeaves($id);
        $user = $this->users_model->getUsers($leave['employee']);
        $manager = $this->users_model->getUsers($user['manager']);
        if (empty($manager['email'])) {
            //TODO: create specific error message when the employee has no manager
            $this->session->set_flashdata('msg', lang('leaves_cancel_flash_msg_error'));
        } else {
            //Send an e-mail to the manager
            $this->load->library('email');
            $this->load->library('polyglot');
            $usr_lang = $this->polyglot->code2language($manager['language']);

            //We need to instance an different object as the languages of connected user may differ from the UI lang
            $lang_mail = new CI_Lang();
            $lang_mail->load('email', $usr_lang);
            $lang_mail->load('global', $usr_lang);

            $this->sendGenericMail($leave, $user, $manager, $lang_mail,
                $lang_mail->line('email_leave_request_cancellation_title'),
                $lang_mail->line('email_leave_request_cancellation_subject'),
                'cancelled');
        }
    }

    /**
     * Send a leave request cancellation email to the manager of the connected employee
     * @param int $id Leave request identifier
     * @param int $reminder In case where the employee wants to send a reminder
     * @author Guillaume Blaquiere <guillaume.blaquiere@gmail.com>
     */
    private function sendMailOnLeaveRequestCancellation($id, $reminder=FALSE) {
        $this->load->model('users_model');
        $this->load->model('types_model');
        $this->load->model('delegations_model');
        //We load everything from DB as the LR can be edited from HR/Employees
        $leave = $this->leaves_model->getLeaves($id);
        $user = $this->users_model->getUsers($leave['employee']);
        $manager = $this->users_model->getUsers($user['manager']);
        if (empty($manager['email'])) {
            $this->session->set_flashdata('msg', lang('leaves_cancel_flash_msg_error'));
        } else {
            //Send an e-mail to the manager
            $this->load->library('email');
            $this->load->library('polyglot');
            $usr_lang = $this->polyglot->code2language($manager['language']);

            //We need to instance an different object as the languages of connected user may differ from the UI lang
            $lang_mail = new CI_Lang();
            $lang_mail->load('email', $usr_lang);
            $lang_mail->load('global', $usr_lang);

            if ($reminder) {
                $this->sendGenericMail($leave, $user, $manager, $lang_mail,
                    $lang_mail->line('email_leave_request_reminder') . ' ' .
                    $lang_mail->line('email_leave_request_cancellation_title'),
                    $lang_mail->line('email_leave_request_reminder') . ' ' .
                    $lang_mail->line('email_leave_request_cancellation_subject'),
                    'request');
            } else {
                $this->sendGenericMail($leave, $user, $manager, $lang_mail,
                    $lang_mail->line('email_leave_request_cancellation_title'),
                    $lang_mail->line('email_leave_request_cancellation_subject'),
                    'cancel');
            }
        }
    }

    /**
     * Send a generic email from the collaborator to the manager (delegate in copy) when a leave request is created or cancelled
     * @param $leave Leave request
     * @param $user Connected employee
     * @param $manager Manger of connected employee
     * @param $lang_mail Email language library
     * @param $title Email Title
     * @param $detailledSubject Email detailled Subject
     * @param $emailModel template email to use
     * @author Guillaume Blaquiere <guillaume.blaquiere@gmail.com>
     *
     */
    private function sendGenericMail($leave, $user, $manager, $lang_mail, $title, $detailledSubject, $emailModel) {

        $date = new DateTime($leave['startdate']);
        $startdate = $date->format($lang_mail->line('global_date_format'));
        $date = new DateTime($leave['enddate']);
        $enddate = $date->format($lang_mail->line('global_date_format'));

        $comments=$leave['comments'];
        $comment = '';
        if(!empty($comments)){
          $comments=json_decode($comments);
          foreach ($comments->comments as $comments_item) {
            if($comments_item->type == "comment"){
              $comment = $comments_item->value;
            }
          }
        }
        log_message('info', "comment : " . $comment);
        $this->load->library('parser');
        $data = array(
            'Title' => $title,
            'Firstname' => $user['firstname'],
            'Lastname' => $user['lastname'],
            'StartDate' => $startdate,
            'EndDate' => $enddate,
            'StartDateType' => $lang_mail->line($leave['startdatetype']),
            'EndDateType' => $lang_mail->line($leave['enddatetype']),
            'Type' => $this->types_model->getName($leave['type']),
            'Duration' => $leave['duration'],
            'Balance' => $this->leaves_model->getLeavesTypeBalanceForEmployee($leave['employee'] , $leave['type_name'], $leave['startdate']),
            'Reason' => $leave['cause'],
            'BaseUrl' => $this->config->base_url(),
            'LeaveId' => $leave['id'],
            'UserId' => $this->user_id,
            'Comments' => $comment
        );
        $message = $this->parser->parse('emails/' . $manager['language'] . '/'.$emailModel, $data, TRUE);

        $to = $manager['email'];
        $subject = $detailledSubject . ' ' . $user['firstname'] . ' ' . $user['lastname'];
        //Copy to the delegates, if any
        $cc = NULL;
        $delegates = $this->delegations_model->listMailsOfDelegates($manager['id']);
        if ($delegates != '') {
            $cc = $delegates;
        }

        sendMailByWrapper($this, $subject, $message, $to, $cc);
    }

    /**
     * Delete a leave request
     * @param int $id identifier of the leave request
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function delete($id) {
        $can_delete = FALSE;
        //Test if the leave request exists
        $leaves = $this->leaves_model->getLeaves($id);
        if (empty($leaves)) {
            redirect('notfound');
        } else {
            if ($this->is_hr) {
                $can_delete = TRUE;
            } else {
                if (($leaves['status'] == LMS_PLANNED) &&
                        $leaves['employee'] == $this->user_id) {
                    $can_delete = TRUE;
                }
                if ($this->config->item('delete_rejected_requests') == TRUE ||
                    $leaves['status'] == LMS_REJECTED) {
                    $can_delete = TRUE;
                }
            }
            if ($can_delete === TRUE) {
                $this->leaves_model->deleteLeave($id);
            } else {
                $this->session->set_flashdata('msg', lang('leaves_delete_flash_msg_error'));
                if (isset($_GET['source'])) {
                    redirect($_GET['source']);
                } else {
                    redirect('leaves');
                }
            }
        }
        $this->session->set_flashdata('msg', lang('leaves_delete_flash_msg_success'));
        if (isset($_GET['source'])) {
            redirect($_GET['source']);
        } else {
            redirect('leaves');
        }
    }

    /**
     * Ask for the cancellation of a leave request. Extend the workflow with
     * cancellation and canceled steps.
     * Change of behavior (compared to prior versions):
     *  - Manager and HR do not cancel leave requests, they reject them.
     *  - Only the connected user can reject its own requests.
     *  - If the cancellation request is accepted, it goes on accepted
     * @param int $id identifier of the leave request
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function cancellation($id) {
        //Test if the leave request exists
        $leave = $this->leaves_model->getLeaves($id);
        if (empty($leave)) {
            redirect('notfound');
        } else {
            //Only the connected user can reject its own requests
            if ($this->user_id != $leave['employee']){
                $this->session->set_flashdata('msg', lang('leaves_cancellation_flash_msg_error'));
                redirect('leaves');
            }
            //We can cancel a leave request only with a status 'Accepted'
            if ($leave['status'] == LMS_ACCEPTED) {
                $this->leaves_model->switchStatus($id, LMS_CANCELLATION);
                $this->sendMailOnLeaveRequestCancellation($id);
                $this->session->set_flashdata('msg', lang('leaves_cancellation_flash_msg_success'));
                redirect('leaves');
            } else {
                $this->session->set_flashdata('msg', lang('leaves_cancellation_flash_msg_error'));
                redirect('leaves');
            }
        }
    }

    /**
     * Allows the employee to cancel a requested leave request.
     * Only the connected user can reject its own requests.
     * Send a notification to the line manager.
     * Next status is 'Canceled'
     * @param int $id identifier of the leave request
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function cancel($id) {
        //Test if the leave request exists
        $leave = $this->leaves_model->getLeaves($id);
        if (empty($leave)) {
            redirect('notfound');
        } else {
            //Only the connected user can reject its own requests
            if ($this->user_id != $leave['employee']){
                $this->session->set_flashdata('msg', lang('leaves_cancellation_flash_msg_error'));
                redirect('leaves');
            }
            //We can cancel a leave request only with a status 'Requested'
            if ($leave['status'] == LMS_REQUESTED) {
                $this->leaves_model->switchStatus($id, LMS_CANCELED);
                $this->sendMailOnLeaveRequestCanceled($id);
                $this->session->set_flashdata('msg', lang('requests_cancellation_accept_flash_msg_success'));
                redirect('leaves');
            } else {
                $this->session->set_flashdata('msg', lang('leaves_cancellation_flash_msg_error'));
                redirect('leaves');
            }
        }
    }

    /**
     * Export the list of all leaves into an Excel file
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function export() {
        $this->load->view('leaves/export');
    }

    /**
     * Ajax endpoint : Send a list of fullcalendar events
     * @param int $id employee id or connected user (from session)
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function individual($id = 0) {
        header("Content-Type: application/json");
        $start = $this->input->get('start', TRUE);
        $end = $this->input->get('end', TRUE);
        if ($id == 0) $id =$this->session->userdata('id');
        echo $this->leaves_model->individual($id, $start, $end);
    }

    /**
     * Ajax endpoint : Send a list of fullcalendar events
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function workmates() {
        header("Content-Type: application/json");
        $start = $this->input->get('start', TRUE);
        $end = $this->input->get('end', TRUE);
        echo $this->leaves_model->workmates($this->session->userdata('manager'), $start, $end);
    }

    /**
     * Ajax endpoint : Send a list of fullcalendar events
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function collaborators() {
        header("Content-Type: application/json");
        $start = $this->input->get('start', TRUE);
        $end = $this->input->get('end', TRUE);
        echo $this->leaves_model->collaborators($this->user_id, $start, $end);
    }

    /**
     * Ajax endpoint : Send a list of fullcalendar events
     * @param int $entity_id Entity identifier
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function organization($entity_id) {
        header("Content-Type: application/json");
        $start = $this->input->get('start', TRUE);
        $end = $this->input->get('end', TRUE);
        $children = filter_var($this->input->get('children', TRUE), FILTER_VALIDATE_BOOLEAN);
        $statuses = $this->input->get('statuses');
        echo $this->leaves_model->department($entity_id, $start, $end, $children, $statuses);
    }

    /**
     * Ajax endpoint : Send a list of fullcalendar events
     * @param int $list_id List identifier
     * @author Emilien NICOLAS <milihhard1996@gmail.com>
     */
    public function listEvents($list_id){
      header("Content-Type: application/json");
      $start = $this->input->get('start', TRUE);
      $end = $this->input->get('end', TRUE);
      $statuses = $this->input->get('statuses');
      echo $this->leaves_model->getListRequest($list_id, $start, $end, $statuses);
    }

    /**
     * Ajax endpoint : Send a list of fullcalendar events
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function department() {
        header("Content-Type: application/json");
        $this->load->model('organization_model');
        $department = $this->organization_model->getDepartment($this->user_id);
        $start = $this->input->get('start', TRUE);
        $end = $this->input->get('end', TRUE);
        echo $this->leaves_model->department($department->id, $start, $end);
    }

    /**
     * Ajax endpoint. Result varies according to input :
     *  - difference between the entitled and the taken days
     *  - try to calculate the duration of the leave
     *  - try to detect overlapping leave requests
     *  If the user is linked to a contract, returns end date of the yearly leave period or NULL
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function validate() {
        header("Content-Type: application/json");
        $id = $this->input->post('id', TRUE);
        $type = $this->input->post('type', TRUE);

        // Helper function to process and validate date inputs
        function processDate($inputDate) {
            if (!empty($inputDate)) {
                $dateObject = DateTime::createFromFormat('Y-m-d', $inputDate);
                if ($dateObject && $dateObject->format('Y-m-d') === $inputDate) {
                    return preg_replace("([^0-9-])", "", $inputDate);
                }
            }
            return '1970-01-01'; // Default date if input is invalid or null
        }

        // Process start and end dates
        $startdate = processDate($this->input->post('startdate', TRUE));
        $enddate = processDate($this->input->post('enddate', TRUE));

        $startdatetype = $this->input->post('startdatetype', TRUE); // Mandatory field checked by frontend
        $enddatetype = $this->input->post('enddatetype', TRUE);     // Mandatory field checked by frontend
        $leave_id = intval($this->input->post('leave_id', TRUE));

        $leaveValidator = new stdClass();
        $deductDayOff = FALSE;

        if (isset($id) && isset($type)) {
            $typeObject = $this->types_model->getTypeByName($type);
            $deductDayOff = $typeObject['deduct_days_off'];
            $leaveValidator->credit = $this->leaves_model->getLeavesTypeBalanceForEmployee($id, $type, $startdate);
        }

        if (isset($id) && $startdate !== '1970-01-01' && $enddate !== '1970-01-01') {
            $leaveValidator->overlap = $this->leaves_model->detectOverlappingLeaves(
                $id, $startdate, $enddate, $startdatetype, $enddatetype, $leave_id ?? null
            );
        }

        // Contract boundaries handling
        $this->load->model('contracts_model');
        $startentdate = NULL;
        $endentdate = NULL;
        $hasContract = $this->contracts_model->getBoundaries($id, $startentdate, $endentdate);
        $leaveValidator->PeriodStartDate = $startentdate;
        $leaveValidator->PeriodEndDate = $endentdate;
        $leaveValidator->hasContract = $hasContract;

        if (isset($id) && $startdate !== '1970-01-01' && $enddate !== '1970-01-01' && $hasContract === TRUE) {
            $this->load->model('dayoffs_model');
            $leaveValidator->listDaysOff = $this->dayoffs_model->listOfDaysOffBetweenDates($id, $startdate, $enddate);
            $result = $this->leaves_model->actualLengthAndDaysOff(
                $id, $startdate, $enddate, $startdatetype, $enddatetype, $leaveValidator->listDaysOff, $deductDayOff
            );
            $leaveValidator->overlapDayOff = $result['overlapping'];
            $leaveValidator->lengthDaysOff = $result['daysoff'];
            $leaveValidator->length = $result['length'];
        } elseif ($hasContract === FALSE) {
            $leaveValidator->length = $this->leaves_model->length($id, $startdate, $enddate, $startdatetype, $enddatetype);
        }

        // Repeat start and end dates of the leave request
        $leaveValidator->RequestStartDate = $startdate;
        $leaveValidator->RequestEndDate = $enddate;

        echo json_encode($leaveValidator);
    }
}