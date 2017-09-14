<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all ajax related functions
 * same as ajax.php, just split as ajax.phpwas getting too big
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Ajaxtwo extends MY_Controller
{

    // --  constructor- -------------------------------------------------------------------------------------------------------
    public function __construct()
    {

        parent::__construct();

        //reduce error reporting to only critical
        @error_reporting(E_ERROR);

        //turn off codeigniter profiler (which may be on in MY_Controller) during debug mode
        $this->output->enable_profiler(false);

    }

    // -- index -------------------------------------------------------------------------------------------------------
    /**
     * This is our re-routing function and is the inital function called
     */
    function index()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //---check if logged in, using this local function and not one in MY_Controller--------
        if ($this->uri->segment(2) == 'team') {
            $this->__flmUserLoggedInCheck('team');
        }

        //---check if logged in, using this local function and not one in MY_Controller--------
        if ($this->uri->segment(2) == 'client') {
            $this->__flmUserLoggedInCheck('client');
        }

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {

            case 'editable-task-details':
                $this->__editableTaskDetails();
                break;

            case 'editable-task':
                $this->__editableTask();
                break;

            case 'update-task-status':
                $this->__updateTaskStatus();
                break;


            default:
                $this->__default($action);
                break;
        }

        //log debug data
        $this->__ajaxdebugging();

    }

    // -- __flmUserLoggedInCheck- -------------------------------------------------------------------------------------------------------
    /**
     * checks if user is logged in, else redirects
     */

    function __flmUserLoggedInCheck($user_type = 'team')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //-----set for admin------------------------
        if ($user_type == 'team') {
            //is user logged in..else redirect to login page
            if (!is_numeric($this->session->userdata('team_profile_id'))) {

                //create json response
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_session_timed_out'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log debug data
                $this->__ajaxdebugging();

                //load the view for json echo
                $this->__flmView('common/json');

                //now die and exit
                die('Session timed out - Please login again');
            }

        }

        //-----set for admin------------------------
        if ($user_type == 'client') {
            //is user logged in..else redirect to login page
            if (!is_numeric($this->session->userdata('client_users_clients_id'))) {

                //create json response
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_session_timed_out'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);

                //log debug data
                $this->__ajaxdebugging();

                //load the view for json echo
                $this->__flmView('common/json');

                //now die and exit
                die('Session timed out - Please login again');
            }
        }

    }

    // -- __default- -------------------------------------------------------------------------------------------------------
    /**
     * if nothing was passed in url
     */

    function __default($action = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        header('HTTP/1.0 400 Bad Request', true, 400);
        $this->jsondata = array(
            'result' => 'error',
            'message' => 'An error has occurred',
            'debug_line' => __line__);

        //log this error
        log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Routing errror. Specified method/action ($action) not found]");

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- __editableTaskDetails- -------------------------------------------------------------------------------------------------------
    /**
     * edit various aspects of a task
     */

    function __editableTaskDetails()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //collect data sent by editable.js
        $task_id = $this->input->post('pk');
        $name = $this->input->post('name');
        $value = $this->input->post('value');

        //-----basic validation first---------------------------------------------------------------
        if ($next) {
            if (!is_numeric($task_id) || $name == '' || $value == '') {
                //show error
                echo $this->data['lang']['lang_an_error_has_occurred'] . ': ln-' . __line__;
                header('HTTP/1.0 400 Bad Request', true, 400);
                //halt
                $next = false;
            }
        }

        //-----validate task id---------------------------------------------------------------
        if ($next) {
            if (!$task = $this->tasks_model->getTask($task_id)) {
                //show error
                echo $this->data['lang']['lang_an_error_has_occurred'] . ': ln-' . __line__;
                header('HTTP/1.0 400 Bad Request', true, 400);
                //halt
                $next = false;
            }
        }

        //-----get related project---------------------------------------------------------------
        if ($next) {
            if (!$project = $this->projects_model->projectDetails($task['tasks_project_id'])) {
                //show error
                echo $this->data['lang']['lang_an_error_has_occurred'] . ': ln-' . __line__;
                header('HTTP/1.0 400 Bad Request', true, 400);
                //halt
                $next = false;
            }
        }


        //-----set basic data and permissions--------------------------------------------------------
        if ($next) {

            //set core task details
            $project_leader_id = $project['projects_team_lead_id'];
            $my_id = $this->data['vars']['my_id'];
            $my_user_type = $this->data['vars']['my_user_type'];
            $my_group = $this->data['vars']['my_group'];
            $assiged_to_id = $task['tasks_assigned_to_id'];

            //default permission
            $i_can_assign = false;
            $i_can_edit = false;

            //permission: I can edit this task
            if (($assiged_to_id == $my_id && $this->data['permission']['edit_item_my_project_my_tasks'] == 1) || $my_group == 1 || $project_leader_id == $my_id) {
                $i_can_edit = true;
            }

            //permission: I can assign this task
            if ($my_group == 1 || $my_id == $project_leader_id) {
                $i_can_assign = true;
            }
        }


        /** ---------------------------------------------------------------------------------------
         * EDIT TASK ASSIGNED TO USER
         * ---------------------------------------------------------------------------------------*/
        if ($next && $name == 'assigned-to') {

            //PERMISSION CHECK
            if (!$i_can_assign) {
                //show error
                echo $this->data['lang']['lang_permission_denied_info'];
                header('HTTP/1.0 400 Bad Request', true, 400);
                //halt
                $next = false;
            }

            //update new assigned to
            if ($next) {
                if ($this->tasks_model->assignTask($task_id, $value)) {
                    $this->tasks_model->debug_data;
                    //success
                    header('HTTP/1.0 200 OK', true, 200);
                } else {
                    //show error
                    echo $this->data['lang']['lang_an_error_has_occurred'] . ': ln-' . __line__;
                    header('HTTP/1.0 400 Bad Request', true, 400);
                }
            }
        }

        /** ---------------------------------------------------------------------------------------
         * EDIT TASK DUE DATE
         * ---------------------------------------------------------------------------------------*/
        if ($next && $name == 'due-date') {

            //PERMISSION CHECK
            if (!$i_can_edit) {
                //show error
                echo $this->data['lang']['lang_permission_denied_info'];
                header('HTTP/1.0 400 Bad Request', true, 400);
                //halt
                $next = false;
            }

            //check date is not before start date
            if ($next) {
                if (strtotime($value) < strtotime($task['tasks_start_date'])) {
                    //show error
                    echo $this->data['lang']['lang_end_date_cannot_be_before_start_date'];
                    header('HTTP/1.0 400 Bad Request', true, 400);
                    //halt
                    $next = false;
                }
            }

            //update due task due date
            if ($next) {
                if ($this->tasks_model->updateDueDate($task_id, $value)) {
                    //success
                    header('HTTP/1.0 200 OK', true, 200);
                } else {
                    //show error
                    echo $this->data['lang']['lang_an_error_has_occurred'] . ': ln-' . __line__;
                    header('HTTP/1.0 400 Bad Request', true, 400);
                }
            }
        }

        /** ---------------------------------------------------------------------------------------
         * EDIT TASK TITLE
         * ---------------------------------------------------------------------------------------*/
        if ($next && $name == 'task-title') {
            //PERMISSION CHECK
            if (!$i_can_edit) {
                //show error
                echo $this->data['lang']['lang_permission_denied_info'];
                header('HTTP/1.0 400 Bad Request', true, 400);
                //halt
                $next = false;
            }

            //update due task due date
            if ($next) {
                if ($this->tasks_model->updateDetails($task_id, 'title', $value)) {
                    //success
                    header('HTTP/1.0 200 OK', true, 200);
                } else {
                    //show error
                    echo $this->data['lang']['lang_an_error_has_occurred'] . ': ln-' . __line__;
                    header('HTTP/1.0 400 Bad Request', true, 400);
                }
            }
        }


        /** ---------------------------------------------------------------------------------------
         * EDIT MILESTONE
         * ---------------------------------------------------------------------------------------*/
        if ($next && $name == 'milestone') {

            //PERMISSION CHECK
            if (!$i_can_edit) {
                //show error
                echo $this->data['lang']['lang_permission_denied_info'];
                header('HTTP/1.0 400 Bad Request', true, 400);
                //halt
                $next = false;
            }

            //update due task due date
            if ($next) {
                if ($this->tasks_model->updateDetails($task_id, 'milestone', $value)) {
                    //success
                    header('HTTP/1.0 200 OK', true, 200);
                } else {
                    //show error
                    echo $this->data['lang']['lang_an_error_has_occurred'] . ': ln-' . __line__;
                    header('HTTP/1.0 400 Bad Request', true, 400);
                }
            }
        }
    }


    // -- __editableTask- -------------------------------------------------------------------------------------------------------
    /**
     * edit a task description or title
     */

    function __editableTask()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //get data
        $task_id = $this->input->post('content_id');
        $new_value = $this->input->post('new_content');
        $type = $this->input->post('post_var1'); //('description', 'title')

        /* -------------------GENERAL PERMISSIONS-------------------*/
        if (!$this->__permissionsCheckTask($task_id, 'edit', 'team')) {
            //create json response
            $this->jsondata = array(
                'result' => 'error',
                'message' => $this->data['lang']['lang_permission_denied'],
                'debug_line' => __line__);
            header('HTTP/1.0 400 Bad Request', true, 400);
            //halt
            $next = false;
        }


        //edit task
        if ($next) {
            if ($this->tasks_model->updateDetails($task_id, $type, $new_value)) {
                $this->jsondata = array(
                    'result' => 'success',
                    'message' => $this->data['lang']['lang_request_has_been_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);
            } else {
                //create json response
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);
                //halt
                $next = false;
            }
        }

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json
        $this->__flmView('common/json');

    }

    // -- __updateTaskStatus- -------------------------------------------------------------------------------------------------------
    /**
     * update a tasks status
     */

    function __updateTaskStatus()
    {

        //flow control
        $next = true;


        //get data
        $task_id = $this->uri->segment(4);
        $new_value = ($this->uri->segment(5) == 'behind') ? 'behind schedule' : $this->uri->segment(5);
        $valid_status = array(
            'pending',
            'completed',
            'behind schedule');


        //validate input
        if (!is_numeric($task_id) || !in_array($new_value, $valid_status)) {
            //create json response
            $this->jsondata = array(
                'result' => 'error',
                'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                'debug_line' => __line__);
            header('HTTP/1.0 400 Bad Request', true, 400);
            //halt
            $next = false;
        }

        /* -------------------GENERAL PERMISSIONS-------------------*/
        if ($next) {
            if (!$this->__permissionsCheckTask($task_id, 'edit', 'team')) {
                //create json response
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_permission_denied'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);
                //halt
                $next = false;
            }
        }


        //get task
        if ($next) {
            if (!$task = $this->tasks_model->getTask($task_id)) {
                //create json response
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);
                //halt
                $next = false;
            }
        }

        //edit task
        if ($next) {
            if ($this->tasks_model->updateDetails($task_id, 'status', $new_value)) {
                //refresh all task status (for this project)
                //reason is because for none 'complete' type updates,we just recorded as 'pending'
                //so lets refesh and automatically set it right
                $this->refresh->taskStatus($task['tasks_project_id']);
            } else {
                //create json response
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);
                //halt
                $next = false;
            }
        }


        //get task again (we need the new/real status)
        if ($next) {
            if ($task = $this->tasks_model->getTask($task_id)) {
                //json response
                $this->jsondata = array(
                    'success' => 1,
                    'message' => 'success',
                    'task_status' => $task['tasks_status'],
                    'debug_line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);
            } else {
                //create json response
                $this->jsondata = array(
                    'result' => 'error',
                    'message' => $this->data['lang']['lang_request_could_not_be_completed'],
                    'debug_line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);
                //halt
                $next = false;
            }
        }


        //refresh project progress
        if ($next) {
            $this->refresh->updateProjectPercentage($task['tasks_project_id']);
            $this->data['debug'][] = $this->refresh->debug_data;
        }


        //log debug data
        $this->__ajaxdebugging();

        //load the view for json
        $this->__flmView('common/json');

    }


    // -- fmlView-------------------------------------------------------------------------------------------------------
    /**
     * loads json outputting view
     */
    function __flmView($view = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //sent to TBS engine
        $this->load->view($view, array('data' => $this->jsondata));
    }


    // -- DEBUGGING --------------------------------------------------------------------------------------------------------------
    /**
     * - ajax runs in the background, so we want to do as much logging as possibe for debugging
     * 
     */
    function __ajaxdebugging()
    {

        //url segmets array
        $this->data['url_segments'] = $this->uri->segment_array();

        //format debug data for log file
        ob_start();
        print_r($this->data);
        print_r($this->jsondata);
        $all_data = ob_get_contents();
        ob_end_clean();

        //write to logi file
        if ($this->config->item('debug_mode') == 2 || $this->config->item('debug_mode') == 1) {
            log_message('debug', "AJAX-LOG:: BIG DATA $all_data");
        }
    }
}

/* End of file ajax.php */
/* Location: ./application/controllers/admin/ajax.php */
