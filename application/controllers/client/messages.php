<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Messages related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Messages extends MY_Controller
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
        $this->data['template_file'] = PATHS_CLIENT_THEME . 'project.messages.html';

        //css settings
        $this->data['vars']['css_submenu_projects'] = 'style="display:block; visibility:visible;"';
        $this->data['vars']['css_menu_projects'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_project_messages'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';

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
        $this->__commonClient_LoggedInCheck();

        //get project id
        $this->project_id = $this->uri->segment(3);

        /** CLIENT-RESOURCE-OWNERSHIP VALIDATION **/
        if (!in_array($this->project_id, $this->data['my_clients_project_array'])) {
            redirect('/client/error/permission-denied');
        }

        //set project_id for global use in template
        $this->data['vars']['project_id'] = $this->project_id;

        //check if project exists & set some basic data
        $this->__commonAll_ProjectBasics($this->project_id);

        //get the action from url
        $action = $this->uri->segment(4);

        //route the rrequest
        switch ($action) {

            case 'view':
                $this->__messagesView();
                break;

            case 'add-message':
                $this->__addMessage();
                break;

            case 'add-reply':
                $this->__addReply();
                break;

            default:
                $this->__messagesView();
                break;
        }

        //css - active tab
        $this->data['vars']['css_active_tab_messages'] = 'side-menu-main-active';

        //load view
        $this->__flmView('client/main');

    }

    /**
     * view project messages
     */
    function __messagesView()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * /client/messages/2/view/1
        * (2)->controller
        * (3)->project id
        * (4)->route
        * (5)->offset
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //explicitly set messaged template file (needed)
        $this->data['template_file'] = PATHS_CLIENT_THEME . 'project.messages.html';

        //uri segments
        $offset = (is_numeric($this->uri->segment(5))) ? $this->uri->segment(5) : 0;

        //correct offset for incoming method calls from __editMessages() and __editReplies()
        if ($this->uri->segment(4) == 'edit-message' || $this->uri->segment(4) == 'edit-reply') {
            $offset = (is_numeric($this->uri->segment(6))) ? $this->uri->segment(6) : 0;
        }

        //set offset for use in template
        $this->data['vars']['offset'] = $offset;

        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'messages';
        $this->data['blocks']['messages'] = $this->messages_model->listMessages($offset, 'search', $this->project_id);


        //count results rows - used by pagination class
        $rows_count = $this->messages_model->listMessages($offset, 'count', $this->project_id);


        //pagination
        $config = pagination_default_config(); //
        $config['base_url'] = site_url("client/messages/" . $this->project_id . "/view");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['messages_limit'];
        $config['uri_segment'] = 5; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //visibility
        if ($rows_count > 0) {
            //show side menu
            $this->data['visible']['wi_project_messages'] = 1;
        } else {
            //show mothing found
            $this->notifications('wi_tabs_notification', $this->data['lang']['lang_no_results_found']);
        }

        //prepare data
        $this->data['blocks']['messages'] = $this->__prepMessagesView($this->data['blocks']['messages']);
    }

    /**
     * additional data preparations for __messagesView() data
     *
     */
    function __prepMessagesView($thedata = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if data is not empty
        if (count($thedata) == 0 || !is_array($thedata)) {
            return $thedata;
        }

        /* -----------------------PREPARE FILES DATA ----------------------------------------/
        *  Loop through all the files in this array and for each file:
        *  -----------------------------------------------------------
        *  (1) add visibility for the [control] buttons
        *  (2) process user names (message posted by)
        *  (3) strip unwanted html tags from message_text
        *  (4) process avatar images
        *  -----------------------------------------------------------
        *  (1) above is base on what rights I have on the message, i.e:
        *           - am I the message poster
        *           - am I the project leader
        *           - am I a system administrator
        *
        * [NOTES]
        * Usage is during conditional loading at TBS->MERGEBLOCK level and should be as follows:
        * <!--[onshow;block=div;when [files.wi_files_control_buttons;block=tr] == 1;comm]-->
        * --AS OPPOSED TO--
        * <!--[onshow;block=div;when [files.wi_files_control_buttons] == 1;comm]-->
        *
        *------------------------------------------------------------------------------------*/
        for ($i = 0; $i < count($thedata); $i++) {

            //-----(1) VISIBILITY OF CONTROL BUTTONS--------------------------------\\

            //default visibility
            $visibility_control = 0;

            //add my rights into $thedata array
            $thedata[$i]['wi_messages_control_buttons'] = $visibility_control;

            //-----(2) PROCESS (TEAM/CLIENT) USER NAMES--------------------------------\\

            //--team member---------------------
            if ($thedata[$i]['messages_by'] == 'team') {

                //is the users data available
                if ($thedata[$i]['team_profile_full_name'] != '') {

                    //trim max lenght
                    $fullname = trim_string_length($thedata[$i]['team_profile_full_name'], 20);
                    $user_id = $thedata[$i]['team_profile_id'];
                    //create users name label
                    $thedata[$i]['uploaded_by'] = '<a class="links-blue iframeModal"
                                                      data-height="250" 
                                                      data-width="100%"
                                                      data-toggle="modal"
                                                      data-target="#modalIframe"
                                                      data-modal-window-title="' . $this->data['lang']['lang_user_profile'] . '" 
                                                      data-src="' . site_url("client/people/team/$user_id") . '"
                                                      href="#">' . $fullname . '</a>';

                } else {

                    //this user is unavailable (has been deleted etc)
                    $thedata[$i]['uploaded_by'] = '<span class="tooltips" 
                                                       data-original-title="' . $this->data['lang']['lang_userd_details_unavailable'] . '">
                                                       ' . $this->data['lang']['lang_unavailable'] . '</span>';
                }
            }

            //--client user----------------------
            if ($thedata[$i]['messages_by'] == 'client') {

                //is the users data available
                if ($thedata[$i]['client_users_full_name'] != '') {

                    //trim max lenght
                    $fullname = trim_string_length($thedata[$i]['client_users_full_name'], 20);
                    $user_id = $thedata[$i]['client_users_id'];
                    //create html
                    $thedata[$i]['uploaded_by'] = '<a class="links-blue iframeModal"
                                                      data-height="250" 
                                                      data-width="100%"
                                                      data-toggle="modal"
                                                      data-target="#modalIframe"
                                                      data-modal-window-title="' . $this->data['lang']['lang_user_profile'] . '" 
                                                      data-src="' . site_url("client/people/client/$user_id") . '"
                                                      href="#">' . $fullname . '</a>';

                } else {

                    //this user is unavailable (has been deleted etc)
                    $thedata[$i]['uploaded_by'] = '<span class="tooltips" 
                                                       data-original-title="' . $this->data['lang']['lang_userd_details_unavailable'] . '">
                                                       ' . $this->data['lang']['lang_unavailable'] . '</span>';
                }
            }

            //(2) STRIP UNWANTED CKEDITOR HTML TAGS-----------------------------\\

            //remove </p> tags
            $unwanted = array('</p>');
            $thedata[$i]['messages_text'] = str_replace($unwanted, '', $thedata[$i]['messages_text']);
            //replace <p> with </br> tags
            $thedata[$i]['messages_text'] = str_replace('<p>', '</br>', $thedata[$i]['messages_text']);

            //-------(4) PROCESS AVATAR IMAGES----------------------------------\\

            //team member
            if ($thedata[$i]['messages_by'] == 'team') {
                $thedata[$i]['avatar_filename'] = $thedata[$i]['team_profile_avatar_filename'];
            }

            //client member
            if ($thedata[$i]['messages_by'] == 'client') {
                $thedata[$i]['avatar_filename'] = $thedata[$i]['client_users_avatar_filename'];
            }

            //-------(4) INJECT REPLIES ARRAY----------------------------------\\
            $replies = $this->message_replies_model->getReplies($thedata[$i]['messages_id']);

            if (is_array($replies)) {
                //prepare replies
                $thedata[$i]['replies'] = $this->__prepMessageReplies($replies);
            }
        }

        //retrun the data
        return $thedata;
    }

    /**
     * additional data preparations for [message replies]
     *
     */
    function __prepMessageReplies($thedata = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if data is not empty
        if (count($thedata) == 0 || !is_array($thedata)) {
            return $thedata;
        }

        /* -----------------------PREPARE FILES DATA ----------------------------------------/
        *  Loop through all the files in this array and for each file:
        *  -----------------------------------------------------------
        *  (1) add visibility for the [control] buttons
        *  (2) process user names (message posted by)
        *  (3) strip unwanted html tags from message_text
        *  (4) process avatar images
        *  -----------------------------------------------------------
        *  (1) above is base on what rights I have on the message, i.e:
        *           - am I the message poster
        *           - am I the project leader
        *           - am I a system administrator
        *
        * [NOTES]
        * Usage is during conditional loading at TBS->MERGEBLOCK level and should be as follows:
        * <!--[onshow;block=div;when [files.wi_files_control_buttons;block=tr] == 1;comm]-->
        * --AS OPPOSED TO--
        * <!--[onshow;block=div;when [files.wi_files_control_buttons] == 1;comm]-->
        *
        *------------------------------------------------------------------------------------*/
        for ($i = 0; $i < count($thedata); $i++) {

            //-----(1) VISIBILITY OF CONTROL BUTTONS--------------------------------\\

            //default visibility
            $visibility_control = 0;

            //grant rights if I am the one who posted the message
            if ($this->data['vars']['my_id'] == $thedata[$i]['messages_replies_by_id']) {
                $visibility_control = 1;
            }

            //grant visibility if I am an admin or I am the project leader
            if ($this->data['vars']['my_group'] == 1 || $this->data['vars']['my_id'] == $this->data['vars']['project_leaders_id']) {
                $visibility_control = 1;
            }

            //add my rights into $thedata array
            $thedata[$i]['wi_messages_replies_control_buttons'] = $visibility_control;

            //-----(2) PROCESS (TEAM/CLIENT) USER NAMES--------------------------------\\

            //--team member---------------------
            if ($thedata[$i]['messages_replies_by'] == 'team') {

                //is the users data available
                if ($thedata[$i]['team_profile_full_name'] != '') {

                    //trim max lenght
                    $fullname = trim_string_length($thedata[$i]['team_profile_full_name'], 20);
                    $user_id = $thedata[$i]['team_profile_id'];
                    //create users name label
                    $thedata[$i]['uploaded_by'] = '<a class="links-blue iframeModal"
                                                      data-height="250" 
                                                      data-width="100%"
                                                      data-toggle="modal"
                                                      data-target="#modalIframe"
                                                      data-modal-window-title="' . $this->data['lang']['lang_user_profile'] . '" 
                                                      data-src="' . site_url("client/people/team/$user_id") . '"
                                                      href="#">' . $fullname . '</a>';

                } else {

                    //this user is unavailable (has been deleted etc)
                    $thedata[$i]['uploaded_by'] = '<span class="tooltips" 
                                                       data-original-title="' . $this->data['lang']['lang_userd_details_unavailable'] . '">
                                                       ' . $this->data['lang']['lang_unavailable'] . '</span>';
                }
            }

            //--client user----------------------
            if ($thedata[$i]['messages_replies_by'] == 'client') {

                //is the users data available
                if ($thedata[$i]['client_users_full_name'] != '') {

                    //trim max lenght
                    $fullname = trim_string_length($thedata[$i]['client_users_full_name'], 20);
                    $user_id = $thedata[$i]['client_users_id'];
                    //create html
                    $thedata[$i]['uploaded_by'] = '<a class="links-blue iframeModal"
                                                      data-height="250" 
                                                      data-width="100%"
                                                      data-toggle="modal"
                                                      data-target="#modalIframe"
                                                      data-modal-window-title="' . $this->data['lang']['lang_user_profile'] . '" 
                                                      data-src="' . site_url("client/people/client/$user_id") . '"
                                                      href="#">' . $fullname . '</a>';

                } else {

                    //this user is unavailable (has been deleted etc)
                    $thedata[$i]['uploaded_by'] = '<span class="tooltips" 
                                                       data-original-title="' . $this->data['lang']['lang_userd_details_unavailable'] . '">
                                                       ' . $this->data['lang']['lang_unavailable'] . '</span>';
                }
            }

            //-------(3) PROCESS AVATAR IMAGES----------------------------------\\

            //team member
            if ($thedata[$i]['messages_replies_by'] == 'team') {
                $thedata[$i]['avatar_filename'] = $thedata[$i]['team_profile_avatar_filename'];
            }

            //client member
            if ($thedata[$i]['messages_replies_by'] == 'client') {
                $thedata[$i]['avatar_filename'] = $thedata[$i]['client_users_avatar_filename'];
            }

            //(4) STRIP UNWANTED CKEDITOR HTML TAGS-----------------------------\\

            //remove </p> tags
            $unwanted = array('</p>');
            $thedata[$i]['messages_replies_text'] = str_replace($unwanted, '', $thedata[$i]['messages_replies_text']);
            //replace <p> with </br> tags
            $thedata[$i]['messages_replies_text'] = str_replace('<p>', '</br>', $thedata[$i]['messages_replies_text']);

        }

        //retrun the data
        return $thedata;
    }

    /**
     * add project message
     *
     */
    function __addMessage()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if any post data (avoid direct url access)
        if (!isset($_POST['messages_text'])) {
            redirect('/client/messages/' . $this->project_id . '/view');
        }

        /** CLIENT-RESOURCE-OWNERSHIP VALIDATION **/
        if (!in_array($this->input->post('messages_project_id'), $this->data['my_clients_project_array'])) {
            redirect('/client/error/permission-denied');
        }

        //flow control
        $next = true;

        //validate post
        if ($next) {
            if ($this->input->post('messages_text') == '') {

                //show message
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_fill_in_all_required_fields']);
                //halt
                $next = false;
            }
        }

        //validate hidden fields
        if ($next) {

            //array of hidden fields and their check type
            $hidden_fields = array(
                'messages_project_id' => 'numeric',
                'messages_by_id' => 'numeric',
                'messages_by' => 'string');

            //loop through and validate each hidden field
            foreach ($hidden_fields as $key => $value) {

                if (($value == 'numeric' && !is_numeric($_POST[$key])) || ($value == 'string' && $_POST[$key] == '')) {

                    //log this error
                    log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Adding new message failed: Required hidden form field ($key) missing or invalid]");
                    $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                    //halt
                    $next = false;
                }
            }
        }

        //update database
        if ($next) {

            if ($this->messages_model->addMessage()) {

                //show success
                $this->session->set_flashdata('notice-success', $this->data['lang']['lang_request_has_been_completed']);

                //events tracker
                $this->__eventsTracker('posted-message', array());

                //email notification
                $this->__emailer('mailqueue_new_message', array('message' => $this->input->post('messages_text')));

                /** --------------------------emailer - v2----------------------------------------------------------*/
                //vars - project basic
                $vars = $this->__emailtagsProjectData($this->input->post('messages_project_id'));

                //vars - file specific
                $vars['message'] = $this->input->post('messages_text');
                $vars['message_posted_by'] = $this->input->post('messages_by');


                //email - team
                $this->__emailer('mailqueue_project_message_admin', $vars);
                /** --------------------------emailer - v2----------------------------------------------------------*/

            } else {
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                //halt
                $next = false;
            }
            //debug

        }
        //redirect to view
        $this->__easyRedirect('add-message', 'view');
    }

    /**
     * add project message reply
     *
     */
    function __addReply()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if any post data (avoid direct url access)
        if (!isset($_POST['messages_replies_text'])) {
            redirect('/client/messages/' . $this->project_id . '/view');
        }

        //flow control
        $next = true; //validate post
        if ($this->input->post('messages_replies_text') == '') {

            //show message
            $this->session->set_flashdata('notice-error', $this->data['lang']['lang_fill_in_all_required_fields']);
            //halt
            $next = false;
        }

        //validate hidden fields
        if ($next) {

            //array of hidden fields and their check type
            $hidden_fields = array(
                'messages_replies_message_id' => 'numeric',
                'messages_replies_by_id' => 'numeric',
                'messages_replies_project_id' => 'numeric',
                'messages_replies_by' => 'string');

            //loop through and validate each hidden field
            foreach ($hidden_fields as $key => $value) {

                if (($value == 'numeric' && !is_numeric($_POST[$key])) || ($value == 'string' && $_POST[$key] == '')) {

                    //log this error
                    log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Adding new message reply failed: Required hidden form field ($key) missing or invalid]");
                    $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                    //halt
                    $next = false;
                }
            }
        }

        //update database
        if ($next) {

            if ($this->message_replies_model->addMessage()) {

                //show success
                $this->session->set_flashdata('notice-success', $this->data['lang']['lang_request_has_been_completed']);

                //events tracker
                $this->__eventsTracker('posted-message-reply', array());

                //email notification
                $this->__emailer('mailqueue_new_message', array('message' => $this->input->post('messages_replies_text')));


                /** --------------------------emailer - v2----------------------------------------------------------*/
                //vars - project basic
                $vars = $this->__emailtagsProjectData($this->input->post('messages_replies_project_id'));

                //vars - file specific
                $vars['message'] = $this->input->post('messages_replies_text');
                $vars['message_posted_by'] = $this->input->post('messages_replies_by');


                //email - team
                $this->__emailer('mailqueue_project_message_admin', $vars);
                /** --------------------------emailer - v2----------------------------------------------------------*/

            } else {
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);

                //halt
                $next = false;
            }
            //debug

        }
        //redirect to view
        $this->__easyRedirect('add-reply', 'view');
    }

    /**
     * validates forms for various methods in this class
     * @param	string $form identify the form to validate
     */
    function __flmFormValidation($form = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //form validation
        if ($form == 'edit_message') {

            //check required fields
            $fields = array('messages_text' => $this->data['lang']['lang_message']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //everything ok
            return true;
        }

        //---------------validate form post data--------------------------
        if ($form == 'edit_reply') {

            //check required fields
            $fields = array('messages_replies_text' => $this->data['lang']['lang_message']);
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
     * log any error message into the log file
     *
     */
    function __errorLogging($theline = '', $thefunction = '', $thefile = '', $themessage = 'system error')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;
        $message_log = "[FILE: $thefile]  [LINE: $theline]  [FUNCTION: $thefunction]  [MESSAGE: $themessage]";
        log_message('error', $message_log);
    }

    /**
     * records new project events (timeline)
     *
     * @param	string   $type identify the loop to run in this function
     * @param   array    $vents_data an optional array that can be used to directly pass data]      
     */
    function __eventsTracker($type = '', $events_data = array())
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //--------------record a new event-----------------------
        if ($type == 'posted-message') {

            //build data array
            $events = array();
            $events['project_events_project_id'] = $this->project_id;
            $events['project_events_type'] = 'project-message';
            $events['project_events_details'] = regex_remove_lines_spaces(strip_tags($this->input->post('messages_text')));
            $events['project_events_action'] = 'lang_tl_new_message';
            $events['project_events_target_id'] = 0;
            $events['project_events_user_id'] = $this->data['vars']['my_id'];
            $events['project_events_user_type'] = 'client';
            $events['project_events_link'] = 'projectmessage_' . $this->project_id;

            //add data to database
            $this->project_events_model->addEvent($events);

        }

        //--------------record a new event-----------------------
        if ($type == 'posted-message-reply') {

            //build data array
            $events = array();
            $events['project_events_project_id'] = $this->project_id;
            $events['project_events_type'] = 'project-message';
            $events['project_events_details'] = regex_remove_lines_spaces(strip_tags($this->input->post('messages_replies_text')));
            $events['project_events_action'] = 'lang_tl_new_message';
            $events['project_events_target_id'] = 0;
            $events['project_events_user_id'] = $this->data['vars']['my_id'];
            $events['project_events_user_type'] = 'client';
            $events['project_events_link'] = 'projectmessage_' . $this->project_id;

            //add data to database
            $this->project_events_model->addEvent($events);

        }

    }

    // -- __emailer-------------------------------------------------------------------------------------------------------
    /**
     * send out an email
     *
     * @access	private
     * @param	string
     * @return	void
     */
    function __emailer($email = '', $vars = array())
    {

        //common variables
        $this->data['email_vars']['todays_date'] = $this->data['vars']['todays_date'];

        //------------------------------------queue email in database-------------------------------
        /** THIS WIL NOT SEND BUT QUEUE THE EMAILS*/
        if ($email == 'mailqueue_project_message_admin') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('project_message_admin');


            //exit if email is not enabled
            if ($template['status'] != 'enabled') {
                return;
            }

            //loop through all project members (mailing list) - send to team only
            for ($i = 0; $i < count($this->data['vars']['project_mailing_list']); $i++) {

                //email team members only
                if ($this->data['vars']['project_mailing_list'][$i]['user_type'] == 'team') {

                    //dynamic vars
                    $vars['to_name'] = $this->data['vars']['project_mailing_list'][$i]['name'];
                    $vars['url_dashboard'] = $this->data['vars']['site_url_admin'];
                    $vars['url_message'] = $this->data['vars']['site_url_admin'] . '/messages/' . $this->project_id . '/view';

                    //sqldata vars
                    $sqldata['email_queue_message'] = parse_email_template($template['message'], $vars);
                    $sqldata['email_queue_subject'] = $template['subject'];
                    $sqldata['email_queue_email'] = $this->data['vars']['project_mailing_list'][$i]['email'];

                    //add to email queue database 
                    $this->email_queue_model->addToQueue($sqldata);

                }
            }
        }
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

/* End of file messages.php */
/* Location: ./application/controllers/client/messages.php */
