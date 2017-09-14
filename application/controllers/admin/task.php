<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all single Task related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Task extends MY_Controller
{

    /**
     * constructor method
     */
    public function __construct()
    {

        parent::__construct();

        //profiling::
        $this->data['controller_profiling'][] = __function__;

        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'iframe.task.html';

        $this->load->helper('download');
    }

    /**
     * This is our re-routing function and is the inital function called
     *
     * 
     */
    function index()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //get project id
        $this->project_id = $this->uri->segment(3);
        $this->task_id = $this->uri->segment(5);

        //set project_id for global use in template
        $this->data['vars']['project_id'] = $this->project_id;

        //refresh project
        $this->refresh->updateProjectPercentage($this->project_id);

        //check if project exists & set some basic data
        $this->__commonAll_ProjectBasics($this->project_id);

        //javascript allowed files array
        js_allowedFileTypes();

        //javascript file size limit
        js_fileSizeLimit();

        //get the action from url
        $action = $this->uri->segment(4);


        //route the rrequest
        switch ($action) {

            case 'view':
                $this->__viewTask();
                break;

            case 'download':
                $this->__downloadFile();
                break;

            default:
                $this->__viewTask();
                break;

        }

        //load view
        $this->__flmView('admin/main');

    }


    // -- __viewTask- -------------------------------------------------------------------------------------------------------
    /**
     * some notes
     */

    function __viewTask()
    {

        //flow control
        $next = true;

        /** ----------------------------------------------------------------
         * [PERMISSIONS] [TEAM]
         * ----------------------------------------------------------------*/
        if ($next) {
            if (!$this->__permissionsCheckTask($this->task_id, 'view', 'team')) {
                $next = false;
                $this->notifications('wi_notification', $this->data['lang']['lang_permission_denied']);
            }
        }


        /** ----------------------------------------------------------------
         * [PERMISSIONS] [CLIENT]
         * ----------------------------------------------------------------*/
        if ($next) {
            if ($this->data['vars']['my_user_type'] == 'client') {
                if (!$this->__permissionsCheckTask($this->task_id, 'view', 'client')) {
                    $next = false;
                    $this->notifications('wi_notification', $this->data['lang']['lang_permission_denied']);
                }
            }
        }


        //get task
        if ($next) {
            if (!$task = $this->tasks_model->getTask($this->task_id)) {
                $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_found']);
                //halt
                $next = false;
            }

        }

        //get task assigned to details
        if ($next) {
            $this->data['reg_fields'][] = 'task_assigned';
            $this->data['fields']['task_assigned'] = $this->teamprofile_model->teamMemberDetails($task['tasks_assigned_to_id']);

        }

        //get task assigned to details
        if ($next) {
            $this->data['reg_fields'][] = 'task_created';
            $this->data['fields']['task_created'] = $this->teamprofile_model->teamMemberDetails($task['tasks_created_by_id']);

        }

        //create editable.js data set for project members
        $this->data['vars']['editable_team_list'] = '';
        for ($i = 0; $i < count($this->data['vars']['project_members_team']); $i++) {
            $this->data['vars']['editable_team_list'] .= "{value: '" . $this->data['vars']['project_members_team'][$i]['team_profile_id'] . "', text: '" . $this->data['vars']['project_members_team'][$i]['team_profile_full_name'] . "'},";
        }

        //create editable.js data set for milestone
        if ($next) {
            $milestones = $this->milestones_model->allMilestones('milestones_title', 'ASC', $this->project_id);
            for ($i = 0; $i < count($milestones); $i++) {
                $this->data['vars']['editable_milestones_list'] .= "{value: '" . $milestones[$i]['milestones_id'] . "', text: '" . $milestones[$i]['milestones_title'] . "'},";
                //set milestone name
                if ($task['tasks_milestones_id'] == $milestones[$i]['milestones_id']) {
                    $this->data['vars']['current_task_milestone'] = $milestones[$i]['milestones_title'];
                }
            }
        }


        //show task
        if ($next) {
            //set task to data
            $this->data['reg_fields'][] = 'task';
            $this->data['fields']['task'] = $task;
            $this->data['visible']['wi_task'] = 1;
        }

        //get task files
        if ($next) {
            if ($files = $this->task_files_model->getFiles(array('task_files_task_id' => $this->task_id))) {
                $this->data['reg_blocks'][] = 'taskfiles';
                $this->data['blocks']['taskfiles'] = $files;
                $this->data['visible']['wi_task_files'] = 1;
            } else {
                $this->data['visible']['wi_task_files'] = 0;
            }
        }
    }


    // -- __downloadFile- -------------------------------------------------------------------------------------------------------
    /**
     * download a file
     */

    function __downloadFile()
    {

        //flow control
        $next = true;


        //file details
        $file_id = $this->uri->segment(5);

        //get file detail
        if ($next) {
            if (!$file = $this->task_files_model->getFile($file_id)) {
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_requested_item_not_found']);
                $next = false;
            }
        }

        /** ----------------------------------------------------------------
         * [PERMISSIONS] [TEAM]
         * ----------------------------------------------------------------*/
        if ($next) {
            if (!$this->__permissionsCheckTask($file['task_files_task_id'], 'view', 'team')) {
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_permission_denied']);
                $next = false;
            }
        }

        //set file path
        if ($next) {
            $file_path = FILES_TASKS_FOLDER . $file['task_files_task_id'] . '/' . $file['task_files_foldername'] . '/' . $file['task_files_name'];
            if (!is_file($file_path)) {
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_requested_item_not_found']);
                $next = false;
            }
        }

        //force download
        if ($next) {
            //force browser to download file
            $file_data = file_get_contents($file_path); // Read the file's contents
            force_download($file['task_files_name'], $file_path);
        } else {
            $redirect_url = $this->data['vars']['site_url'] . 'admin/task/' . $file['task_files_project_id'] . '/view/' . $file['task_files_task_id'];
            redirect($redirect_url);
        }
    }


    /**
     * Generates various pulldown (<option>...</option>) lists for ready use in HTML
     * Output is set to e.g. $this->data['lists']['milestones']
     *
     */
    function __pulldownLists()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //[all_milestones]
        $data = $this->milestones_model->allMilestones('milestones_title', 'ASC', $this->project_id);

        $this->data['lists']['all_milestones'] = create_pulldown_list($data, 'milestones', 'id'); //[all_team_members]
    }


    /**
     * loads the view
     *
     * @param string $view the view to load
     */
    function __flmView($view = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']); //complete the view
        $this->__commonAll_View($view);
    }

}

/* End of file tasks.php */
/* Location: ./application/controllers/admin/tasks.php */
