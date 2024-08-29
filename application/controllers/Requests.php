<?php
/**
 * This controller allows a manager to list and manage leave requests submitted to him
 * @copyright  Copyright (c) Fadzrul Aiman

 * @since         0.1.0
 */

if (!defined('BASEPATH')) { exit('No direct script access allowed'); }
require_once FCPATH . "API/notification_helper.php"; // Include the notification helper

/**
 * This class allows a manager to list and manage leave requests submitted to him.
 * Since 0.3.0, we expose the list of collaborators and allow a manager to access to some reports:
 *  - presence report of an employee.
 *  - counters of an employee (leave balance).
 *  - Yearly calendar of an employee.
 * But those reports are not served by this controller (either HR or Calendar controller).
 */
class Requests extends CI_Controller {

    /**
     * Default constructor
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function __construct() {
        parent::__construct();
        setUserContext($this);
        $this->load->model('leaves_model');
        $this->lang->load('requests', $this->language);
        $this->lang->load('global', $this->language);
    }

    /**
     * Display the list of all requests submitted to you
     * Status is submitted or accepted/rejected depending on the filter parameter.
     * @param string $filter Filter the list of submitted leave requests (all or requested)
     */
    public function index($filter = 'requested') {
        $this->auth->checkIfOperationIsAllowed('list_requests');
        $data = getUserContext($this);
        $this->load->model('types_model');
        $this->lang->load('datatable', $this->language);
        $this->load->helper('form');
        
        // Validate filter parameter
        $valid_filters = ['all', 'requested'];
        if (!in_array($filter, $valid_filters)) {
            show_error('Invalid filter value', 400);
        }
        
        $data['filter'] = $filter;
        $data['title'] = lang('requests_index_title');
        $showAll = ($filter === 'all');
        
        if ($this->config->item('enable_history') == TRUE) {
            $data['requests'] = $this->leaves_model->getLeavesRequestedToManagerWithHistory($this->session->userdata('id'), $showAll);
        } else {
            $data['requests'] = $this->leaves_model->getLeavesRequestedToManager($this->session->userdata('id'), $showAll);
        }
        
        $data['types'] = $this->types_model->getTypes();
        $data['showAll'] = $showAll;
        $data['flash_partial_view'] = $this->load->view('templates/flash', $data, TRUE);
        $this->load->view('templates/header', $data);
        $this->load->view('menu/index', $data);
        $this->load->view('requests/index', $data);
        $this->load->view('templates/footer');
    }


    /**
     * Display the list of leave bank requests
     * @param string $filter Filter the list of submitted leave bank requests (all or requested)
     */
    public function leavebank($filter = 'requested') {
        $this->auth->checkIfOperationIsAllowed('leavebank_requests');
        $data = getUserContext($this);
        $this->load->model('types_model');
        $this->lang->load('datatable', $this->language);
        $this->load->helper('form');
        
        // Validate filter parameter
        $valid_filters = ['all', 'requested'];
        if (!in_array($filter, $valid_filters)) {
            show_error('Invalid filter value', 400);
        }
        
        $data['filter'] = $filter;
        $data['title'] = 'Leave Bank Requests'; // Customize this title as needed
        $showAll = ($filter === 'all');
        
        if ($this->config->item('enable_history') == TRUE) {
            $data['requests'] = $this->leaves_model->getLeavesBankRequestedWithHistory($showAll);
        } else {
            $data['requests'] = $this->leaves_model->getLeavesBankRequested($showAll);
        }
        
        $data['types'] = $this->types_model->getTypes();
        $data['showAll'] = $showAll;
        $data['flash_partial_view'] = $this->load->view('templates/flash', $data, TRUE);
        $this->load->view('templates/header', $data);
        $this->load->view('menu/index', $data);
        $this->load->view('requests/leavebank', $data); // Ensure this view exists
        $this->load->view('templates/footer');
    }

    /**
     * Accept a leave request
     * @param int $id leave request identifier
     */
    public function accept($id) {
        $this->auth->checkIfOperationIsAllowed('accept_requests');
        $this->load->model('users_model');
        $this->load->model('delegations_model');
        $leave = $this->leaves_model->getLeaves($id);
        if (empty($leave)) {
            redirect('notfound');
        }
        $employee = $this->users_model->getUsers($leave['employee']);
        $is_delegate = $this->delegations_model->isDelegateOfManager($this->user_id, $employee['manager']);
        if (($this->user_id == $employee['manager']) || ($this->is_hr) || ($is_delegate)) {
            $this->leaves_model->switchStatus($id, LMS_ACCEPTED);
    
            // Call the function to send push notification to user
            $this->sendPushNotificationToUserOnApproval($id, 'Leave Request'); //Send notification to user
    
            $this->sendMail($id, LMS_REQUESTED_ACCEPTED);
            $this->session->set_flashdata('msg', lang('requests_accept_flash_msg_success'));
            
            // Redirect back to the original page if possible
            $referrer = $this->input->server('HTTP_REFERER', TRUE);
            if ($referrer) {
                redirect($referrer);
            } else {
                redirect('leaves');
            }
        } else {
            log_message('error', 'User #' . $this->user_id . ' illegally tried to accept leave #' . $id);
            $this->session->set_flashdata('msg', lang('requests_accept_flash_msg_error'));
            redirect('leaves');
        }
    }

    
/**
 * Accept a leave request
 * @param int $id leave request identifier
 */
public function leavebankaccept($id) {
    $this->auth->checkIfOperationIsAllowed('accept_requests');
    $this->load->model('users_model');
    $this->load->model('delegations_model');
    $this->load->model('leaves_model'); // Ensure leaves_model is loaded

    $leave = $this->leaves_model->getLeaves($id);
    if (empty($leave)) {
        redirect('notfound');
        }
        $employee = $this->users_model->getUsers($leave['employee']);
        $is_delegate = $this->delegations_model->isDelegateOfManager($this->user_id, $employee['manager']);
        if (($this->user_id == $employee['manager']) || ($this->is_hr) || ($is_delegate)) {
            $this->leaves_model->switchStatus($id, LMS_REQUESTEDBANK);
            $this->sendMail($id, LMS_LEAVEBANK_MANAGER_ACCEPTED);
            $this->sendMailOnLeaveBankRequestCreation($id);
            $this->session->set_flashdata('msg', lang('requests_accept_flash_msg_success'));

            // Call the function to send push notification to user and hr
            $this->sendPushNotificationToUserOnApprovalBank($id, 'Leave Request'); //Send notification to user
            $this->sendPushNotificationOnLeaveRequest($id, 'Leave Request'); //Send notification to Hr

            if (isset($_GET['source'])) {
                redirect($_GET['source']);



            } else {
                redirect('requests');
            }
        }
    }

    public function approveAll() {
        $this->auth->checkIfOperationIsAllowed('accept_requests');
        $this->load->model('users_model');
        $this->load->model('delegations_model');
        $this->load->model('leaves_model');
    
        $manager_id = $this->session->userdata('id'); // Assuming the manager's ID is stored in session
        $requests = $this->leaves_model->getAllPendingLeaves($manager_id);
    
        foreach ($requests as $leave) {
            $employee_id = $this->getArrayValue($leave, 'employee');
            $leave_id = $this->getArrayValue($leave, 'id');
            $leave_type = $this->getArrayValue($leave, 'type');
            $leave_status = $this->getArrayValue($leave, 'status');
    
            if ($employee_id && $leave_id && $leave_type) {
                $employee = $this->users_model->getUsers($employee_id);
                $employee_manager = $this->getArrayValue($employee, 'manager');
                $employee_id = $this->getArrayValue($employee, 'id');
    
                if ($employee_manager && $employee_id) {
                    $is_delegate = $this->delegations_model->isDelegateOfManager($this->user_id, $employee_manager);
                    if (($this->user_id == $employee_manager) || ($this->is_hr) || ($is_delegate)) {
                        if ($leave_type == LEAVE_BANK_TYPE_ID) {
                            $this->leaves_model->switchStatus($leave_id, LMS_REQUESTEDBANK);
                            $this->sendMail($leave_id, LMS_LEAVEBANK_MANAGER_ACCEPTED);
                            $this->sendMailOnLeaveBankRequestCreation($leave_id);
                            // Call the function to send push notification to user and hr
                            $this->sendPushNotificationToUserOnApprovalBank($leave_id, 'Leave Request');
                            $this->sendPushNotificationOnLeaveRequest($leave_id, 'Leave Request');
                        } else {
                            $this->leaves_model->switchStatus($leave_id, LMS_ACCEPTED);
                            $this->sendMail($leave_id, LMS_REQUESTED_ACCEPTED);
                            // Call the function to send push notification to user
                            $this->sendPushNotificationToUserOnApproval($leave_id, 'Leave Request');
                        }
                    } else {
                        log_message('error', 'User #' . $this->user_id . ' illegally tried to accept leave #' . $leave_id);
                        $this->session->set_flashdata('msg', lang('requests_accept_flash_msg_error'));
                    }
                } else {
                    log_message('error', 'Manager or Employee ID missing for leave #' . $leave_id);
                    log_message('debug', 'Employee Data: ' . json_encode($employee));
                }
            } else {
                log_message('error', 'Employee data missing for leave #' . $leave_id);
                log_message('debug', 'Leave Data: ' . json_encode($leave));
            }
        }
    
        $this->session->set_flashdata('msg', lang('requests_index_approve_all'));
        redirect('requests');
    }
    
    // Helper method to handle the case where the array key might not be set
    private function getArrayValue($array, $key, $default = null) {
        return isset($array[$key]) ? $array[$key] : $default;
    }
    
    /**
     * Reject a leave request
     * @param int $id leave request identifier
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function reject($id) {
        $this->auth->checkIfOperationIsAllowed('reject_requests');
        $this->load->model('users_model');
        $this->load->model('delegations_model');
        $leave = $this->leaves_model->getLeaves($id);
        if (empty($leave)) {
            redirect('notfound');
        }
        $employee = $this->users_model->getUsers($leave['employee']);
        $is_delegate = $this->delegations_model->isDelegateOfManager($this->user_id, $employee['manager']);
        if (($this->user_id == $employee['manager']) || ($this->is_hr)  || ($is_delegate)) {
            if(isset($_POST['comment'])){
              $this->leaves_model->switchStatusAndComment($id, LMS_REJECTED, $_POST['comment']);
            } else {
              $this->leaves_model->switchStatus($id, LMS_REJECTED);
            }
            $this->sendMail($id, LMS_REQUESTED_REJECTED);
            // Redirect back to the original page if possible
            $referrer = $this->input->server('HTTP_REFERER', TRUE);
            // Call the function to send push notification
            $this->sendPushNotificationToUserOnReject($id, 'Leave Request');
            if ($referrer) {
                redirect($referrer);
            } else {
                redirect('leaves');
            }
        } else {
            log_message('error', 'User #' . $this->user_id . ' illegally tried to reject leave #' . $id);
            $this->session->set_flashdata('msg', lang('requests_reject_flash_msg_error'));
            redirect('leaves');
        }
    }

    /**
     * Accept the cancellation of a leave request
     * @param int $id leave request identifier
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function acceptCancellation($id) {
        $this->auth->checkIfOperationIsAllowed('accept_requests');
        $this->load->model('users_model');
        $this->load->model('delegations_model');
        $leave = $this->leaves_model->getLeaves($id);
        if (empty($leave)) {
            redirect('notfound');
        }
        $employee = $this->users_model->getUsers($leave['employee']);
        $is_delegate = $this->delegations_model->isDelegateOfManager($this->user_id, $employee['manager']);
        if (($this->user_id == $employee['manager']) || ($this->is_hr)  || ($is_delegate)) {
            $this->leaves_model->switchStatus($id, LMS_CANCELED);
            $this->sendMail($id, LMS_CANCELLATION_CANCELED);
            $this->session->set_flashdata('msg', lang('requests_cancellation_accept_flash_msg_success'));
            if (isset($_GET['source'])) {
                redirect($_GET['source']);
            } else {
                redirect('requests');
            }
        } else {
            log_message('error', 'User #' . $this->user_id . ' illegally tried to accept the cancellation of leave #' . $id);
            $this->session->set_flashdata('msg', lang('requests_cancellation_accept_flash_msg_error'));
            redirect('leaves');
        }
    }

    /**
     * Reject the cancellation of a leave request
     * @param int $id leave request identifier
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function rejectCancellation($id) {
        $this->auth->checkIfOperationIsAllowed('reject_requests');
        $this->load->model('users_model');
        $this->load->model('delegations_model');
        $leave = $this->leaves_model->getLeaves($id);
        if (empty($leave)) {
            redirect('notfound');
        }
        $employee = $this->users_model->getUsers($leave['employee']);
        $is_delegate = $this->delegations_model->isDelegateOfManager($this->user_id, $employee['manager']);
        if (($this->user_id == $employee['manager']) || ($this->is_hr)  || ($is_delegate)) {
            //$this->leaves_model->switchStatus($id, LMS_ACCEPTED);
            if(isset($_POST['comment'])){
              $this->leaves_model->switchStatusAndComment($id, LMS_ACCEPTED, $_POST['comment']);
            } else {
              $this->leaves_model->switchStatus($id, LMS_ACCEPTED);
            }
            $this->sendMail($id, LMS_CANCELLATION_REQUESTED);
            $this->session->set_flashdata('msg', lang('requests_cancellation_reject_flash_msg_success'));
            if (isset($_GET['source'])) {
                redirect($_GET['source']);
            } else {
                redirect('requests');
            }
        } else {
            log_message('error', 'User #' . $this->user_id . ' illegally tried to accept the cancellation of leave #' . $id);
            $this->session->set_flashdata('msg', lang('requests_cancellation_reject_flash_msg_error'));
            redirect('leaves');
        }
    }

    /**
     * Send a generic email from the collaborator to the HR when a leave request is created or cancelled
     * @param $leave Leave request
     * @param $user Connected employee
     * @param $HR HR Admin
     * @param $lang_mail Email language library
     * @param $title Email Title
     * @param $detailledSubject Email detailled Subject
     * @param $emailModel template email to use
     * @param $status Status of the leave request
     */
    private function sendGenericMail($leave, $user, $HR, $lang_mail, $title, $detailledSubject, $emailModel, $status) {

        $date = new DateTime($leave['startdate']);
        $startdate = $date->format($lang_mail->line('global_date_format'));
        $date = new DateTime($leave['enddate']);
        $enddate = $date->format($lang_mail->line('global_date_format'));

        $comments = $leave['comments'];
        $comment = '';
        if (!empty($comments)) {
            $comments = json_decode($comments);
            foreach ($comments->comments as $comments_item) {
                if ($comments_item->type == "comment") {
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
            'Balance' => $this->leaves_model->getLeavesTypeBalanceForEmployee($leave['employee'], $leave['type_name'], $leave['startdate']),
            'Reason' => $leave['cause'],
            'BaseUrl' => $this->config->base_url(),
            'LeaveId' => $leave['id'],
            'UserId' => $this->user_id,
            'Comments' => $comment,
            'Status' => $status['name'] // Add status to the data array
        );
        $message = $this->parser->parse('emails/' . $HR['language'] . '/' . $emailModel, $data, TRUE);

        $to = $HR['email'];
        $subject = $detailledSubject . ' ' . $user['firstname'] . ' ' . $user['lastname'];
        // Copy to the delegates, if any
        $cc = NULL;
        $delegates = $this->delegations_model->listMailsOfDelegates($HR['id']);
        if ($delegates != '') {
            $cc = $delegates;
        }

        sendMailByWrapper($this, $subject, $message, $to, $cc);
    }



    /**
     * Send a leave request creation email to the HR
     * @param int $id Leave request identifier
     * @param int $reminder In case where the employee wants to send a reminder
     */
    private function sendMailOnLeaveBankRequestCreation($id, $reminder = FALSE) {
        $this->load->model('users_model');
        $this->load->model('types_model');
        $this->load->model('delegations_model');
        $this->load->model('status_model');
        // We load everything from DB as the LR can be edited from HR/Employees
        $leave = $this->leaves_model->getLeaves($id);
        $user = $this->users_model->getUsers($leave['employee']);
        $status = $this->status_model->getStatus($leave['status']); // Assuming you have a status field in the leave record

        // Fetch users with role == 3
        $HR_users = $this->users_model->getUsersByRole(3);
        
        if (empty($HR_users)) {
            $this->session->set_flashdata('msg', lang('leaves_create_flash_msg_error'));
        } else {
            // Send an e-mail to each user with role == 3
            $this->load->library('email');
            $this->load->library('polyglot');

            foreach ($HR_users as $HR) {
                $usr_lang = $this->polyglot->code2language($HR['language']);
        
                // We need to instance a different object as the languages of connected user may differ from the UI lang
                $lang_mail = new CI_Lang();
                $lang_mail->load('email', $usr_lang);
                $lang_mail->load('global', $usr_lang);
        
                if ($reminder) {
                    $this->sendGenericMail($leave, $user, $HR, $lang_mail,
                        $lang_mail->line('email_leave_request_reminder') . ' ' .
                        $lang_mail->line('email_leave_bank_request_creation_title'),
                        $lang_mail->line('email_leave_request_reminder') . ' ' .
                        $lang_mail->line('email_leave_bank_request_creation_subject'),
                        'bankrequest', $status);
                } else {
                    $this->sendGenericMail($leave, $user, $HR, $lang_mail,
                        $lang_mail->line('email_leave_bank_request_creation_title'),
                        $lang_mail->line('email_leave_bank_request_creation_subject'),
                        'bankrequest', $status);
                }
            }
        }
    }

    private function sendPushNotificationToUserOnApproval($leave_id, $title) { 
        // Send Push Notification to User
        $this->load->model('users_model');
        $this->load->model('leaves_model');
        $this->load->model('user_fcm_tokens_model'); // Load the model for the new table
        
        // Log the function call
        log_message('debug', "sendPushNotificationToUserOnApproval called with leave_id: $leave_id, title: $title");
        
        // Get leave and user details
        $leave = $this->leaves_model->getLeaves($leave_id);
        
        // Check if $leave is not null
        if (!$leave) {
            log_message('error', "Leave data not found for leave_id: $leave_id");
            return; // Exit the function to prevent further errors
        }
        
        $user = $this->users_model->getUsers($leave['employee']);
        
        // Check if $user is not null
        if (!$user) {
            log_message('error', "User data not found for employee_id: " . $leave['employee']);
            return; // Exit the function to prevent further errors
        }
        
        // Prepare notification data
        $data = [
            'leaveId' => $leave_id,
            'userId' => $user['id'],
            'leaveStatus' => $leave['status'],
            'screen' => 'Leave History',
        ];
        
        // Get all FCM tokens for the user
        $user_fcm_tokens = $this->user_fcm_tokens_model->getFcmTokensByUserId($user['id']);
        
        // Log the retrieved FCM tokens
        log_message('debug', "FCM tokens for user {$user['id']}: " . json_encode($user_fcm_tokens));
        
        // Check if any tokens were retrieved
        if (!empty($user_fcm_tokens)) {
            foreach ($user_fcm_tokens as $fcm_token) {
                // Log each token and attempt to send notification
                log_message('debug', "Sending push notification to token: $fcm_token");
                $result = sendPushNotification($fcm_token, $title, "[SKG-LMS] Your leave request has been approved", $data);
                // Log the result of the notification sending
                log_message('debug', "Push notification result: " . json_encode($result));
            }
        } else {
            // Log a message if no tokens were found
            log_message('debug', "No FCM tokens found for user {$user['id']}");
        }
    }
    
    private function sendPushNotificationToUserOnReject($leave_id, $title) { //Send Push Notification to User
        $this->load->model('users_model');
        $this->load->model('leaves_model');
        $this->load->model('user_fcm_tokens_model'); // Load the model for the new table
        
        // Log the function call
        log_message('debug', "sendPushNotificationToUserOnApproval called with leave_id: $leave_id, title: $title");
        
        // Get leave and user details
        $leave = $this->leaves_model->getLeaves($leave_id);
        $user = $this->users_model->getUsers($leave['employee']);
        
        // Prepare notification data
        $data = [
            'leaveId' => $leave_id,
            'userId' => $user['id'],
            'leaveStatus' => $leave['status'],
            'screen' => 'Leave History',
        ];
        
        // Get all FCM tokens for the user
        $user_fcm_tokens = $this->user_fcm_tokens_model->getFcmTokensByUserId($user['id']);
        
        // Log the retrieved FCM tokens
        log_message('debug', "FCM tokens for user {$user['id']}: " . json_encode($user_fcm_tokens));
        
        // Check if any tokens were retrieved
        if (!empty($user_fcm_tokens)) {
            foreach ($user_fcm_tokens as $fcm_token) {
                // Log each token and attempt to send notification
                log_message('debug', "Sending push notification to token: $fcm_token");
                $result = sendPushNotification($fcm_token, $title, "[SKG-LMS] Your leave request has been approved", $data);
                // Log the result of the notification sending
                log_message('debug', "Push notification result: " . json_encode($result));
            }
        } else {
            // Log a message if no tokens were found
            log_message('debug', "No FCM tokens found for user {$user['id']}");
        }
    }

    private function sendPushNotificationToUserOnApprovalBank($leave_id, $title) { //Send Push Notification to User
        $this->load->model('users_model');
        $this->load->model('leaves_model');
        $this->load->model('user_fcm_tokens_model'); // Load the model for the new table
        
        // Log the function call
        log_message('debug', "sendPushNotificationToUserOnApproval called with leave_id: $leave_id, title: $title");
        
        // Get leave and user details
        $leave = $this->leaves_model->getLeaves($leave_id);
        $user = $this->users_model->getUsers($leave['employee']);
        
        // Prepare notification data
        $data = [
            'leaveId' => $leave_id,
            'userId' => $user['id'],
            'leaveStatus' => $leave['status'],
            'screen' => 'Leave History',
        ];
        
        // Get all FCM tokens for the user
        $user_fcm_tokens = $this->user_fcm_tokens_model->getFcmTokensByUserId($user['id']);
        
        // Log the retrieved FCM tokens
        log_message('debug', "FCM tokens for user {$user['id']}: " . json_encode($user_fcm_tokens));
        
        // Check if any tokens were retrieved
        if (!empty($user_fcm_tokens)) {
            foreach ($user_fcm_tokens as $fcm_token) {
                // Log each token and attempt to send notification
                log_message('debug', "Sending push notification to token: $fcm_token");
                $result = sendPushNotification($fcm_token, $title, "[SKG-LMS] Waiting for Hr approval", $data);
                // Log the result of the notification sending
                log_message('debug', "Push notification result: " . json_encode($result));
            }
        } else {
            // Log a message if no tokens were found
            log_message('debug', "No FCM tokens found for user {$user['id']}");
        }
    }

    private function sendPushNotificationOnLeaveRequest($leave_id, $title) { //Send Push Notification to Hr 
        // Load the necessary models
        $this->load->model('users_model');
        $this->load->model('leaves_model');
        $this->load->model('user_fcm_tokens_model');
        
        // Get leave details
        $leave = $this->leaves_model->getLeaves($leave_id);
        $user = $this->users_model->getUsers($leave['employee']);
        
        // Prepare notification data
        $data = [
            'leaveId' => $leave_id,
            'userId' => $user['id'],
            'leaveStatus' => $leave['status'],
            'screen' => 'Leave Bank Approval',
        ];
        
        // Get all users with role 3
        $hr_users = $this->users_model->getUsersByRole(3);
        
        // Send push notification to all FCM tokens of users with role 3
        if (!empty($hr_users)) {
            foreach ($hr_users as $hr_user) {
                $hr_fcm_tokens = $this->user_fcm_tokens_model->getFcmTokensByUserId($hr_user['id']);
                if (!empty($hr_fcm_tokens)) {
                    foreach ($hr_fcm_tokens as $fcm_token) {
                        sendPushNotification($fcm_token, $title, "[SKG-LMS] Leave Request from {$user['firstname']} {$user['lastname']}.", $data);
                    }
                }
            }
        }
    }


    /**
     * Display the list of all requests submitted to the line manager (Status is submitted)
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function collaborators() {
        $this->auth->checkIfOperationIsAllowed('list_collaborators');
        $data = getUserContext($this);
        $this->lang->load('datatable', $this->language);
        $data['title'] = lang('requests_collaborators_title');
        $this->load->model('users_model');
        $data['collaborators'] = $this->users_model->getCollaboratorsOfManager($this->user_id);
        $data['flash_partial_view'] = $this->load->view('templates/flash', $data, TRUE);
        $this->load->view('templates/header', $data);
        $this->load->view('menu/index', $data);
        $this->load->view('requests/collaborators', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Display the list of delegations
     * @param int $id Identifier of the manager (from HR/Employee) or 0 if self
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function delegations($id = 0) {
        if ($id == 0) $id = $this->user_id;
        //Self modification or by HR
        if (($this->user_id == $id) || ($this->is_hr)) {
            $data = getUserContext($this);
            $this->lang->load('datatable', $this->language);
            $data['title'] = lang('requests_delegations_title');
            $this->load->model('users_model');
            $data['name'] = $this->users_model->getName($id);
            $data['id'] = $id;
            $this->load->model('delegations_model');
            $data['delegations'] = $this->delegations_model->listDelegationsForManager($id);
            $this->load->view('templates/header', $data);
            $this->load->view('menu/index', $data);
            $this->load->view('requests/delegations', $data);
            $this->load->view('templates/footer');
        } else {
            log_message('error', 'User #' . $this->user_id . ' illegally tried to access to list_delegations');
            $this->session->set_flashdata('msg', sprintf(lang('global_msg_error_forbidden'), 'list_delegations'));
            redirect('leaves');
        }
    }

    /**
     * Ajax endpoint : Delete a delegation for a manager
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function deleteDelegations() {
        $manager = $this->input->post('manager_id', TRUE);
        $delegation = $this->input->post('delegation_id', TRUE);
        if (($this->user_id != $manager) && ($this->is_hr == FALSE)) {
            $this->output->set_header("HTTP/1.1 403 Forbidden");
        } else {
            if (isset($manager) && isset($delegation)) {
                $this->output->set_content_type('text/plain');
                $this->load->model('delegations_model');
                $this->delegations_model->deleteDelegation($delegation);
                $this->output->set_output($delegation);
            } else {
                $this->output->set_header("HTTP/1.1 422 Unprocessable entity");
            }
        }
    }

    /**
     * Ajax endpoint : Add a delegation for a manager
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function addDelegations() {
        $manager = $this->input->post('manager_id', TRUE);
        $delegate = $this->input->post('delegate_id', TRUE);
        if (($this->user_id != $manager) && ($this->is_hr === FALSE)) {
            $this->output->set_header("HTTP/1.1 403 Forbidden");
        } else {
            if (isset($manager) && isset($delegate)) {
                $this->output->set_content_type('text/plain');
                $this->load->model('delegations_model');
                if (!$this->delegations_model->isDelegateOfManager($delegate, $manager)) {
                    $id = $this->delegations_model->addDelegate($manager, $delegate);
                    $this->output->set_output($id);
                } else {
                    $this->output->set_output('null');
                }
            } else {
                $this->output->set_header("HTTP/1.1 422 Unprocessable entity");
            }
        }
    }

    /**
     * Create a leave request in behalf of a collaborator
     * @param int $id Identifier of the employee
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function createleave($id) {
        $this->lang->load('hr', $this->language);
        $this->load->model('users_model');
        $employee = $this->users_model->getUsers($id);
        if (($this->user_id != $employee['manager']) && ($this->is_hr === FALSE)) {
            log_message('error', 'User #' . $this->user_id . ' illegally tried to access to collaborators/leave/create  #' . $id);
            $this->session->set_flashdata('msg', lang('requests_summary_flash_msg_forbidden'));
            redirect('leaves');
        } else {
            $data = getUserContext($this);
            $this->load->helper('form');
            $this->load->library('form_validation');
            $data['title'] = lang('hr_leaves_create_title');
            $data['form_action'] = 'requests/createleave/' . $id;
            $data['source'] = 'requests/collaborators';
            $data['employee'] = $id;

            $this->form_validation->set_rules('startdate', lang('hr_leaves_create_field_start'), 'required|strip_tags');
            $this->form_validation->set_rules('startdatetype', 'Start Date type', 'required|strip_tags');
            $this->form_validation->set_rules('enddate', lang('leaves_create_field_end'), 'required|strip_tags');
            $this->form_validation->set_rules('enddatetype', 'End Date type', 'required|strip_tags');
            $this->form_validation->set_rules('duration', lang('hr_leaves_create_field_duration'), 'required|strip_tags');
            $this->form_validation->set_rules('type', lang('hr_leaves_create_field_type'), 'required|strip_tags');
            $this->form_validation->set_rules('cause', lang('hr_leaves_create_field_cause'), 'strip_tags');
            $this->form_validation->set_rules('status', lang('hr_leaves_create_field_status'), 'required|strip_tags');

            $data['credit'] = 0;
            $default_type = $this->config->item('default_leave_type');
            $default_type = $default_type == FALSE ? 0 : $default_type;
            if ($this->form_validation->run() === FALSE) {
                $this->load->model('contracts_model');
                $leaveTypesDetails = $this->contracts_model->getLeaveTypesDetailsOTypesForUser($id);
                $data['defaultType'] = $leaveTypesDetails->defaultType;
                $data['credit'] = $leaveTypesDetails->credit;
                $data['types'] = $leaveTypesDetails->types;
                $this->load->model('users_model');
                $data['name'] = $this->users_model->getName($id);
                $this->load->view('templates/header', $data);
                $this->load->view('menu/index', $data);
                $this->load->view('hr/createleave');
                $this->load->view('templates/footer');
            } else {
                $this->leaves_model->setLeaves($id);       //We don't use the return value
                $this->session->set_flashdata('msg', lang('hr_leaves_create_flash_msg_success'));
                //No mail is sent, because the manager would set the leave status to accepted
                redirect('requests/collaborators');
            }
        }
    }

    /**
     * Send a leave request email to the employee that requested the leave.
     * @param int $id Leave request identifier
     * @param int $transition Transition in the workflow of leave request
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    private function sendMail($id, $transition)
    {
        $this->load->model('users_model');
        $this->load->model('organization_model');
        $this->load->model('status_model');
        $leave = $this->leaves_model->getLeaves($id);
        $employee = $this->users_model->getUsers($leave['employee']);
        $supervisor = $this->organization_model->getSupervisor($employee['organization']);
        $status = $this->status_model->getStatus($leave['status']); // Assuming you have a status field in the leave record
    
        // Send an e-mail to the employee
        $this->load->library('email');
        $this->load->library('polyglot');
        $usr_lang = $this->polyglot->code2language($employee['language']);
    
        // We need to instance a different object as the languages of connected user may differ from the UI lang
        $lang_mail = new CI_Lang();
        $lang_mail->load('email', $usr_lang);
        $lang_mail->load('global', $usr_lang);
    
        $date = new DateTime($leave['startdate']);
        $startdate = $date->format($lang_mail->line('global_date_format'));
        $date = new DateTime($leave['enddate']);
        $enddate = $date->format($lang_mail->line('global_date_format'));
    
        switch ($transition) {
            case LMS_REQUESTED_ACCEPTED:
                $title = $lang_mail->line('email_leave_request_validation_title');
                $subject = $lang_mail->line('email_leave_request_accept_subject');
                break;
            case LMS_REQUESTED_REJECTED:
                $title = $lang_mail->line('email_leave_request_validation_title');
                $subject = $lang_mail->line('email_leave_request_reject_subject');
                break;
            case LMS_LEAVEBANK_MANAGER_ACCEPTED:
                $title = $lang_mail->line('email_leave_request_pending_title');
                $subject = $lang_mail->line('email_leave_bank_request_subject');
                break;
            case LMS_CANCELLATION_REQUESTED:
                $title = $lang_mail->line('email_leave_request_cancellation_title');
                $subject = $lang_mail->line('email_leave_cancel_reject_subject');
                break;
            case LMS_CANCELLATION_CANCELED:
                $title = $lang_mail->line('email_leave_request_cancellation_title');
                $subject = $lang_mail->line('email_leave_cancel_accept_subject');
                break;
        }
    
        $comment = '';
        if (!empty($leave['comments'])) {
            $comments = json_decode($leave['comments']);
            if (isset($comments->comments)) {
                foreach ($comments->comments as $comments_item) {
                    if ($comments_item->type == "comment") {
                        $comment = $comments_item->value;
                    }
                }
            }
        }
    
        $data = array(
            'Title' => $title,
            'Firstname' => $employee['firstname'],
            'Lastname' => $employee['lastname'],
            'StartDate' => $startdate,
            'EndDate' => $enddate,
            'StartDateType' => $lang_mail->line($leave['startdatetype']),
            'EndDateType' => $lang_mail->line($leave['enddatetype']),
            'Cause' => $leave['cause'],
            'Type' => $leave['type_name'],
            'Comments' => $comment,
            'Status' => $status['name'] // Add status to the data array
        );
    
        $this->load->library('parser');
        switch ($transition) {
            case LMS_REQUESTED_ACCEPTED:
                $message = $this->parser->parse('emails/' . $employee['language'] . '/request_accepted', $data, TRUE);
                break;
            case LMS_REQUESTED_REJECTED:
                $message = $this->parser->parse('emails/' . $employee['language'] . '/request_rejected', $data, TRUE);
                break;
            case LMS_LEAVEBANK_MANAGER_ACCEPTED:
                $message = $this->parser->parse('emails/' . $employee['language'] . '/manager_approved', $data, TRUE);
                break;
            case LMS_CANCELLATION_REQUESTED:
                $message = $this->parser->parse('emails/' . $employee['language'] . '/cancel_rejected', $data, TRUE);
                $supervisor = NULL; // No need to warn the supervisor as nothing changes
                break;
            case LMS_CANCELLATION_CANCELED:
                $message = $this->parser->parse('emails/' . $employee['language'] . '/cancel_accepted', $data, TRUE);
                break;
        }
        sendMailByWrapper($this, $subject, $message, $employee['email'], is_null($supervisor) ? NULL : $supervisor->email);
    }
    

    /**
     * Export the list of all leave requests (sent to the connected user) into an Excel file
     * @param string $filter Filter the list of submitted leave requests (all or requested)
     * @author Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function export($filter = 'requested') {
        $data['filter'] = $filter;
        $this->load->view('requests/export', $data);
    }

    /**
     * Leave balance report limited to the subordinates of the connected manager
     * Status is submitted or accepted/rejected depending on the filter parameter.
     * @param int $dateTmp (Timestamp) date of report
     * @autor Fadzrul Aiman<daniel.fadzrul@gmail.com>
     */
    public function balance($dateTmp = NULL) {
        $this->auth->checkIfOperationIsAllowed('list_requests');
        $data = getUserContext($this);
        $this->lang->load('datatable', $this->language);
        $data['title'] = lang('requests_balance_title');

        if ($dateTmp === NULL) {
            $refDate = date("Y-m-d");
            $data['isDefault'] = 1;
        } else {
            $refDate = date("Y-m-d", $dateTmp);
            $data['isDefault'] = 0;
        }
        $data['refDate'] = $refDate;

        $this->load->model('types_model');
        $all_types = $this->types_model->getTypes();

        // Filter out the type with id 0
        $data['types'] = array_filter($all_types, function($type) {
            return $type['id'] != 0;
        });

        $result = array();
        $this->load->model('users_model');
        $users = $this->users_model->getCollaboratorsOfManager($this->user_id);
        foreach ($users as $user) {
            $result[$user['id']]['firstname'] = $user['firstname'] . ' ' . $user['lastname'];
            $date = new DateTime(empty($user['employmentdate']) ? "0000-00-00" : $user['employmentdate']);
            $result[$user['id']]['employmentdate'] = $date->format(lang('global_date_format'));
            $result[$user['id']]['position'] = $user['position_name'];
            foreach ($data['types'] as $type) {
                $result[$user['id']][$type['name']] = '';
            }

            $summary = $this->leaves_model->getLeaveBalanceForEmployee($user['id'], TRUE, $refDate);
            if (!is_null($summary) && count($summary) > 0) {
                foreach ($summary as $key => $value) {
                    $result[$user['id']][$key] = round($value[1] - $value[0], 3, PHP_ROUND_HALF_DOWN);
                }
            }
        }
        $data['result'] = $result;

        $this->load->view('templates/header', $data);
        $this->load->view('menu/index', $data);
        $this->load->view('requests/balance', $data);
        $this->load->view('templates/footer');
    }
    }