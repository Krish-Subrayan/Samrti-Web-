<?php

class Cloning extends MY_Controller
{

    // --  constructor- -------------------------------------------------------------------------------------------------------
    public function __construct()
    {

        parent::__construct();

        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'iframe.cloneproject.html';


    }

    // -- index -------------------------------------------------------------------------------------------------------
    /**
     * This is our re-routing function and is the inital function called
     *
     * @access	public
     * @param	void
     * @return	void
     */
    function index()
    {


        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //create pulldown lists
        $this->__pulldownLists();

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {

            case 'clone-project':
                $this->__cloneProject();
                break;

            case 'clone-project-start':
                $this->__cloneProjectStart();
                break;

            default:
                $this->__cloneProjectStart();
        }

        //load view
        $this->__flmView('admin/main');
    }


    // -- __cloneProjectStart- -------------------------------------------------------------------------------------------------------
    /**
     * start
     */

    function __cloneProjectStart()
    {

        //flow control
        $next = true;

        //project id
        $project_id = $this->uri->segment(4);


        if ($next) {
            $this->data['reg_fields'][] = 'project';
            if ($this->data['fields']['project'] = $this->clone_model->getProject($project_id)) {
                $this->data['visible']['clone_project_form'] = 1;
            } else {
                $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_found']);
            }
        }

        //project id
        $this->data['vars']['cloning_project_id'] = $project_id;
    }


    // -- __cloneProject- -------------------------------------------------------------------------------------------------------
    /**
     * clone a project
     */

    function __cloneProject()
    {

        //flow control
        $next = true;

        //project posted data
        $project_id = $this->uri->segment(4);
        $client_id = $this->input->post('clone_client_id');
        $start_date = $this->input->post('clone_start_date');
        $end_date = $this->input->post('clone_end_date');
        $clone_milestones_tasks = $this->input->post('clone_milestones_tasks');

        profiling("info", __file__, __function__, __line__, "[cloning project started] - project_id($project_id) - client_id ($client_id) - end_date($end_date)");

        //validate form
        if ($next) {
            if (!$this->__formValidation('clone_project')) {
                //show error
                $this->session->set_flashdata('notice-error-html', $this->form_processor->error_message);
                //halt
                $next = false;
            }
        }


        //check end date is not behind start date
        if ($next) {
            if (strtotime($end_date) < strtotime($start_date)) {
                //show error
                $this->session->set_flashdata('notice-error-html', $this->data['lang']['lang_the_end_date_before_start']);
                //halt
                $next = false;
            }
        }


        /** -------------------------------------------------
         * [DATA] - get the project
         *-------------------------------------------------*/
        if ($next) {
            if (!$this->data['clone_project'] = $this->clone_model->getProject($project_id)) {
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                $next = false;
                profiling("error", __file__, __function__, __line__, "[cloning project] - getting project failed - SQL:" . $this->sql_last_query_and_error);
            }
        }


        /** -------------------------------------------------
         * [DATA] - get the project members
         *-------------------------------------------------*/
        if ($next) {
            if (!$this->data['clone_members'] = $this->clone_model->getMembers($project_id)) {
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                $next = false;
                profiling("error", __file__, __function__, __line__, "[cloning project] - getting project members - SQL:" . $this->sql_last_query_and_error);
            }
        }


        /** -------------------------------------------------
         * [DATA] - get the project milestones
         *-------------------------------------------------*/
        if ($next) {
            if (!$this->data['clone_milestones'] = $this->clone_model->getMilestones($project_id)) {
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                $next = false;
                profiling("error", __file__, __function__, __line__, "[cloning project] - getting project milestones - SQL:" . $this->sql_last_query_and_error);
            }
        }


        /** -------------------------------------------------
         * [DATA] - get the project client
         *-------------------------------------------------*/
        if ($next) {
            if (!$this->data['clone_client'] = $this->clone_model->getClient($client_id)) {
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                $next = false;
                profiling("error", __file__, __function__, __line__, "[cloning project] - getting client failed - SQL:" . $this->sql_last_query_and_error);
            }
        }


        /** ------------------------------------------------------------------------------
         * [CREATE PROJECT]
         * ------------------------------------------------------------------------------*/
        if ($next) {

            //sql vars
            $sqldata = array();
            foreach ($this->data['clone_project'] as $key => $value) {
                $sqldata[$key] = $value;
            }

            //specific sqldata
            $sqldata['projects_date_created'] = $start_date;
            $sqldata['project_deadline'] = $end_date;
            $sqldata['projects_clients_id'] = $client_id;
            $sqldata['projects_title'] = $this->input->post('clone_title');


            //add project to database
            if (!$new_project_id = $this->clone_model->addProject($sqldata)) {
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                $next = false;
                profiling("error", __file__, __function__, __line__, "[cloning project] - adding project to database failed failed - SQL:" . $this->sql_last_query_and_error);

            } else {

                /** project days difference (offset) **/
                $source_start_date = date_create($this->data['clone_project']['projects_date_created']);
                $proposed_start_date = date_create($start_date);
                $diff = date_diff($source_start_date, $proposed_start_date);
                $this->data['clone_project']['date_difference'] = $diff->format("%R%a days"); //e.g '+120 days' OR '-12 days'


                //save for use in timeline
                $this->data['new_project'] = $sqldata;
                $this->data['new_project']['projects_id'] = $new_project_id;


                profiling("info", __file__, __function__, __line__, "[cloning project started] - project added. new_project_id($new_project_id) - client_id($client_id) - date_offest(" . $this->data['clone_project']['date_difference'] . ")");
            }


        }


        /** ------------------------------------------------------------------------------
         * [CREATE PROJECT MEMBERS]
         * ------------------------------------------------------------------------------*/

        if ($next) {

            //loop throug members and add
            for ($i = 0; $i < count($this->data['clone_members']); $i++) {

                profiling("info", __file__, __function__, __line__, "[cloning project] - adding member id(" . $sqldata['project_members_team_id'] . ") - new_project_id($new_project_id) ");
                $sqldata = array();
                $sqldata['project_members_team_id'] = $this->data['clone_members'][$i]['project_members_team_id'];
                $sqldata['project_members_project_lead'] = $this->data['clone_members'][$i]['project_members_project_lead'];
                $sqldata['project_members_project_id'] = $new_project_id;

                if (!$this->clone_model->addMembers($sqldata)) {
                    $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                    $next = false;
                    profiling("error", __file__, __function__, __line__, "[cloning project] - adding project members to database failed failed - SQL:" . $this->sql_last_query_and_error);
                    //exit loop
                    break;
                }
            }
        }


        /** ------------------------------------------------------------------------------
         * [CREATE PROJECT MILESTONES]
         * ------------------------------------------------------------------------------*/

        if ($next && $this->input->post('clone_milestones_tasks') == 'on') {


            profiling("info", __file__, __function__, __line__, "[cloning project] - milestones found (" . count($this->data['clone_milestones']) . ") new milestone id($new_nilestone_id) project id($new_project_id)");

            //loop throug members and add
            for ($i = 0; $i < count($this->data['clone_milestones']); $i++) {

                profiling("info", __file__, __function__, __line__, "[cloning project] - adding milestone - original id(" . $this->data['clone_milestones'][$i]['milestones_id'] . ") - new_project_id($new_project_id) ");

                $sqldata = array();
                $sqldata['milestones_title'] = $this->data['clone_milestones'][$i]['milestones_title'];
                $sqldata['milestones_start_date'] = $this->data['clone_milestones'][$i]['milestones_start_date'];
                $sqldata['milestones_end_date'] = $this->data['clone_milestones'][$i]['milestones_end_date'];
                $sqldata['milestones_created_by'] = $this->data['clone_milestones'][$i]['milestones_created_by'];
                $sqldata['milestones_status'] = $this->data['clone_milestones'][$i]['milestones_status'];
                $sqldata['milestones_client_id'] = $client_id;
                $sqldata['milestones_project_id'] = $new_project_id;

                //adjust dates?
                if ($this->input->post('auto_update_dates') == 'on') {
                    $date_start = '';
                    $date_end = '';
                    //original dates (from clone)
                    $date_start = $this->data['clone_milestones'][$i]['milestones_start_date'];
                    $date_end = $this->data['clone_milestones'][$i]['milestones_end_date'];
                    $offest_diff = $this->data['clone_project']['date_difference'];
                    //adjusted dates
                    $sqldata['milestones_start_date'] = date("Y-m-d", strtotime($date_start . $offest_diff));
                    $sqldata['milestones_end_date'] = date("Y-m-d", strtotime($date_end . $offest_diff));
                    profiling("info", __file__, __function__, __line__, "[cloning project] - offsetting milestone dates: offset($offest_diff) - original start date($date_start) new start date(" . $sqldata['milestones_start_date'] . ") original end date($date_end) new end date(" . $sqldata['milestones_start_date'] . ") project id($new_project_id)");
                }

                //reset status
                if ($this->input->post('reset_task_status') == 'on') {
                    $sqldata['milestones_status'] = 'pending';
                }

                //add to database
                if ($new_nilestone_id = $this->clone_model->addMilestones($sqldata)) {
                    profiling("info", __file__, __function__, __line__, "[cloning project] - new milestone added id($new_nilestone_id) project id($new_project_id)");
                    //flow
                    $next_tasks = true;
                } else {
                    $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                    $next = false;
                    profiling("error", __file__, __function__, __line__, "[cloning project] - adding project to database failed failed - SQL:" . $this->sql_last_query_and_error);
                    //flow
                    $next_tasks = false;
                    //exit loop
                    break;
                }


                /** ---------------------------------------------------------------------
                 * add tasks to milestone (custom array)
                 * ----------------------------------------------------------------------*/
                if (is_numeric($new_nilestone_id) && $next_tasks) {

                    //get the original milestones id
                    $original_milestone_id = $this->data['clone_milestones'][$i]['milestones_id'];

                    //get the milestone's tasks and add to new array for use later
                    if ($this->data['all_tasks'] = $this->clone_model->getMilestoneTasks($original_milestone_id)) {

                        profiling("info", __file__, __function__, __line__, "[cloning project] - task found (" . count($this->data['all_tasks']) . ") new milestone id($new_nilestone_id) project id($new_project_id)");

                        //loop through all tasks and add to future array
                        for ($x = 0; $x < count($this->data['all_tasks']); $x++) {


                            //add all tasks data
                            foreach ($this->data['all_tasks'][$x] as $key => $value) {
                                $this->data['clone_tasks'][$x][$key] = $value;
                            }

                            //reset status
                            if ($this->input->post('reset_task_status') == 'on') {
                                $this->data['clone_tasks'][$x]['tasks_status'] = 'pending';
                            }

                            //correct data
                            $this->data['clone_tasks'][$x]['tasks_project_id'] = $new_project_id;
                            $this->data['clone_tasks'][$x]['tasks_client_id'] = $client_id;
                            $this->data['clone_tasks'][$x]['tasks_milestones_id'] = $new_nilestone_id;

                            //adjust dates?
                            if ($this->input->post('auto_update_dates') == 'on') {
                                //original dates (from clone)
                                $date_start = $this->data['clone_tasks'][$x]['tasks_start_date'];
                                $date_end = $this->data['clone_tasks'][$x]['tasks_end_date'];
                                $offest_diff = $this->data['clone_project']['date_difference'];
                                //adjusted dates
                                $this->data['clone_tasks'][$x]['tasks_start_date'] = date("Y-m-d", strtotime($date_start . $offest_diff));
                                $this->data['clone_tasks'][$x]['tasks_end_date'] = date("Y-m-d", strtotime($date_end . $offest_diff));
                                profiling("info", __file__, __function__, __line__, "[cloning project] - offsetting task dates: offset($offest_diff) - original start date($date_start) new start date(" . $this->data['clone_tasks'][$x]['tasks_start_date'] . ") original end date($date_end) new end date(" . $this->data['clone_tasks'][$x]['tasks_end_date'] . ") project id($new_project_id)");
                            }

                            profiling("info", __file__, __function__, __line__, "[cloning project] - task added to array - origianl task id(" . $this->data['clone_tasks'][$i]['tasks_id'] . ") new milestone id($new_nilestone_id) project id($new_project_id)");
                        }

                    } else {
                        $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                        $next = false;
                        profiling("error", __file__, __function__, __line__, "[cloning project] - getting project tasks - SQL:" . $this->sql_last_query_and_error);
                        //exit loop
                        break;
                    }


                }
            }
        }


        /** ------------------------------------------------------------------------------
         * [CREATE PROJECT TASKS]
         * ------------------------------------------------------------------------------*/

        if ($next && $this->input->post('clone_milestones_tasks') == 'on') {

            //loop throug members and add
            for ($i = 0; $i < count($this->data['clone_tasks']); $i++) {

                profiling("info", __file__, __function__, __line__, "[cloning project] - adding milestone id(" . $this->data['clone_milestones'][$i]['milestones_id'] . ") - new_project_id($new_project_id) ");

                $sqldata = array();
                $sqldata['tasks_milestones_id'] = $this->data['clone_tasks'][$i]['tasks_milestones_id'];
                $sqldata['tasks_project_id'] = $this->data['clone_tasks'][$i]['tasks_project_id'];
                $sqldata['tasks_client_id'] = $this->data['clone_tasks'][$i]['tasks_client_id'];
                $sqldata['tasks_assigned_to_id'] = $this->data['clone_tasks'][$i]['tasks_assigned_to_id'];
                $sqldata['tasks_text'] = $this->data['clone_tasks'][$i]['tasks_text'];
                $sqldata['tasks_start_date'] = $this->data['clone_tasks'][$i]['tasks_start_date'];
                $sqldata['tasks_end_date'] = $this->data['clone_tasks'][$i]['tasks_end_date'];
                $sqldata['tasks_created_by_id'] = $this->data['clone_tasks'][$i]['tasks_created_by_id'];
                $sqldata['tasks_status'] = $this->data['clone_tasks'][$i]['tasks_status'];
                $sqldata['tasks_description'] = $this->data['clone_tasks'][$i]['tasks_description'];
                $sqldata['tasks_created_by'] = $this->data['clone_tasks'][$i]['tasks_created_by'];
                $sqldata['tasks_client_access'] = $this->data['clone_tasks'][$i]['tasks_client_access'];

                //add to database
                if ($new_task_id = $this->clone_model->addTask($sqldata)) {
                    profiling("info", __file__, __function__, __line__, "[cloning project] - new task added id($new_task_id) milestone_id(" . $sqldata['tasks_milestones_id'] . ") project id(" . $sqldata['tasks_project_id'] . ")");
                    //flow
                    $next_tasks = true;
                } else {
                    $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                    $next = false;
                    profiling("error", __file__, __function__, __line__, "[cloning project] - adding task to database failed failed - SQL:" . $this->sql_last_query_and_error);
                    //flow
                    $next_tasks = false;
                    //exit loop
                    break;
                }

            }
        }


        /** ------------------------------------------------------------------------------
         * [REFRESH] - refresh the project status
         * ------------------------------------------------------------------------------*/
        if ($next) {
            $this->refresh->updateProjectPercentage($new_project_id);
        }


        /** ------------------------------------------------------------------------------
         * [SOMETHING WENT WRONG] - delete all items
         * ------------------------------------------------------------------------------*/
        if (!$next && is_numeric($new_project_id)) {

            $this->clone_model->deleteProject($new_project_id);
            $this->clone_model->deleteProjectMembers($new_project_id);
            $this->clone_model->deleteMilestones($new_project_id);
            $this->clone_model->deleteTasks($new_project_id);

        }


        //redirect finished
        if ($next) {
            //events tracker
            $this->__eventsTracker('add-project');
            $this->notifications('wi_notification', $this->data['lang']['lang_request_has_been_completed']);
        } else {
            //redirect to view
            $this->__easyRedirect('clone-project', 'clone-project-start');
        }
    }


    /**
     * records new project events (timeline)
     *
     * @param	string   $type identify the loop to run in this function
     * @param   array    $vents_data an optional array that can be used to directly pass data]      
     */
    function __eventsTracker($type = '')
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //--------------record a new event-----------------------
        if ($type == 'add-project') {
            //build data array
            $events = array();
            $events['project_events_project_id'] = $this->data['new_project']['projects_id'];
            $events['project_events_type'] = 'project';
            $events['project_events_details'] = $this->data['new_project']['projects_title'];
            $events['project_events_action'] = 'lang_tl_created_new_project';
            $events['project_events_target_id'] = $this->data['new_project']['projects_id'];
            $events['project_events_user_id'] = $this->data['vars']['my_id'];
            $events['project_events_user_type'] = 'team';
            $events['project_events_link'] = 'project_' . $this->data['new_project']['projects_id'];

            //add data to database
            $this->project_events_model->addEvent($events);
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
        $this->data['controller_profiling'][] = __function__;

        //[all_clients]
        $data = $this->clients_model->allClients('clients_company_name', 'ASC');
        $this->data['lists']['all_clients'] = create_pulldown_list($data, 'clients', 'id');
    }


    // -- __formValidation---------------------------------------------------------------------------------------------
    /**
     * - validates forms for various methods in this class
     * - where it returns false, pre-created error message is available $this->form_processor->error_message
     * - error message can then be used in calling method to diplay error widget.
     *
     * @access	private
     * @param	string
     * @return	void
     */
    function __formValidation($form = '')
    {

        //----------------------------------validate a form--------------------------------------
        if ($form == 'clone_project') {

            //check required fields
            $fields = array(
                'clone_title' => $this->data['lang']['lang_title'],
                'clone_client_id' => $this->data['lang']['lang_client'],
                'clone_start_date' => $this->data['lang']['lang_start_date'],
                'clone_end_date' => $this->data['lang']['lang_end_date']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //everything ok
            return true;
        }

        //nothing specified - return false & error message
        $this->form_processor->error_message = $this->data['lang']['lang_form_validation_error'];
        return false;
    }


    /**
     * loads the view
     *
     * @param string $view the view to load
     */
    function __flmView($view = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']);

        //complete the view
        $this->__commonAll_View($view);
    }
}

/* End of file template.php */
/* Location: ./application/controller/admin/template.php */
