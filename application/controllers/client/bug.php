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
class Bug extends MY_Controller
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
        $this->data['template_file'] = PATHS_CLIENT_THEME . 'iframe.bug.html';

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
        $this->bug_id = $this->uri->segment(5);

        //set project_id for global use in template
        $this->data['vars']['project_id'] = $this->project_id;
        $this->data['vars']['bug_id'] = $this->bug_id;


        //check if project exists & set some basic data
        $this->__commonAll_ProjectBasics($this->project_id);

        //get the action from url
        $action = $this->uri->segment(4);


        //route the rrequest
        switch ($action) {

            case 'view':
                $this->__viewBug();
                break;

            case 'add-comment':
                $this->__addComment();
                break;

            case 'reopen-bug':
                $this->__reopenBug();
                break;

            default:
                $this->__viewBug();
                break;
        }

        //load view
        $this->__flmView('admin/main');

    }


    // -- __viewTask- -------------------------------------------------------------------------------------------------------
    /**
     * some notes
     */

    function __viewBug()
    {

        //flow control
        $next = true;

        /** CLIENT CHECK PERMISSION **/
        if (!$this->permissions->bugsView($this->bug_id)) {
            $this->notifications('wi_notification', $this->data['lang']['lang_permission_denied_info']);
            //halt
            $next = false;
        }

        //get bug
        if ($next) {
            //get data
            $this->data['reg_fields'][] = 'bug';
            $this->data['fields']['bug'] = $this->bugs_model->getBug($this->bug_id);
            
            //check errors
            if (!$this->data['fields']['bug']) {
                $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_found']);
            }
        }

        //get bug comments
        if ($next) {
            //get data
            $this->data['reg_blocks'][] = 'bug_comments';
            $this->data['blocks']['bug_comments'] = $this->bug_comments_model->getBugComments($this->bug_id);
            

            //process data
            $this->data['blocks']['bug_comments'] = $this->prepBugComments($this->data['blocks']['bug_comments']);
            
            //update client unread comments
            $this->bugs_model->updateUnreadCommentsClient($this->bug_id, 'no');

        }

        //show bug
        if ($next) {
            $this->data['visible']['show_bug'] = 1;
        }

        //final prep
        $this->data['fields']['bug'] = $this->__prepBugView($this->data['fields']['bug']);
    }


    /**
     * additional data preparations for a single bug
     *
     */
    function __prepBugView($thedata = '')
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if data is not empty
        if (!is_array($thedata)) {
            return $thedata;
        }
        //task is assigned to me and I have edit rights
        if ($thedata['bugs_reported_by'] == 'team') {
            $thedata['submitted_by_id'] = $thedata['bugs_reported_by_id'];
            $thedata['submitted_by_name'] = $thedata['team_name'];
            $thedata['submitted_by_avatar'] = $thedata['team_avatar'];
        } else {
            $thedata['submitted_by_id'] = $thedata['bugs_reported_by_id'];
            $thedata['submitted_by_name'] = $thedata['client_users_full_name'];
            $thedata['submitted_by_avatar'] = $thedata['client_users_avatar_filename'];
        }

        //return the processed array
        return $thedata;
    }
    
    /**
     * additional data preparations for bug comments
     *
     */
    function prepBugComments($thedata = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if data is not empty
        if (count($thedata) == 0 || !is_array($thedata)) {
            return $thedata;
        }

        //add commentors data
        for ($i = 0; $i < count($thedata); $i++) {

            //add team members details
            if ($thedata[$i]['bug_comments_user_type'] == 'team') {
                if ($thedata[$i]['team_profile_full_name'] != '') {
                    $thedata[$i]['comment_by'] = '<label class="label label-info">' . $thedata[$i]['team_profile_full_name'] . '</label>';
                } else {
                    $thedata[$i]['comment_by'] = '<label class="label label-default">' . $this->data['lang']['lang_unavailable'] . '</label>';

                }
                $thedata[$i]['avatar_filename'] = $thedata[$i]['team_profile_avatar_filename'];
            }

            //add clients details
            if ($thedata[$i]['bug_comments_user_type'] == 'client') {
                if ($thedata[$i]['client_users_full_name'] != '') {
                    $thedata[$i]['comment_by'] = '<label class="label label-purple">' . $thedata[$i]['client_users_full_name'] . '</label>';
                } else {
                    $thedata[$i]['comment_by'] = '<label class="label label-default">' . $this->data['lang']['lang_unavailable'] . '</label>';
                }
                $thedata[$i]['avatar_filename'] = $thedata[$i]['client_users_avatar_filename'];
            }

        }

        //return the processed data
        return $thedata;

    }


    // -- __reopenBug- -------------------------------------------------------------------------------------------------------
    /**
     * open bug and mark as recurring
     */

    function __reopenBug()
    {

        //flow control
        $next = true;

        /** CLIENT CHECK PERMISSION **/
        if (!$this->permissions->bugsView($this->bug_id)) {
            $this->notifications('wi_notification', $this->data['lang']['lang_permission_denied_info']);
            //halt
            $next = false;
        }

        //update bug status
        if ($next) {
            $result = $this->bugs_model->updateBug($this->bug_id, 'recurring');
            
            if ($result) {
                //show error
                $this->session->set_flashdata('notice-success', $this->data['lang']['lang_request_has_been_completed']);
            } else {
                //show error
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
            }
            //redirect
            $this->__easyRedirect('reopen-bug', 'view');
        }

    }

    // -- __addComment- -------------------------------------------------------------------------------------------------------
    /**
     * add new comment
     * 
     */

    function __addComment()
    {

        //flow control
        $next = true;

        //check if any post data (avoid direct url access)
        if (!isset($_POST['submit'])) {
            //redirect to 'view' url instead
            $this_url = uri_string();
            $redirect = str_replace('add-comment', 'view', $this_url);
            redirect($redirect);
        }

        /** CLIENT CHECK PERMISSION **/
        if (!$this->permissions->bugsView($this->input->post('bug_comments_bug_id'))) {
            $this->notifications('wi_notification', $this->data['lang']['lang_permission_denied_info']);
            //halt
            $next = false;
        }
        
        //check fields
        if ($next) {
            if (!$this->__formValidation('add_comment')) {
                //show error
                $this->session->set_flashdata('notice-error', $this->form_processor->error_message);
                //redirect to view
                $this->__easyRedirect('add-comment', 'view');
                //halt
                $next = false;
            }
        }

        //check
        if ($next) {
            $sqldata['bug_comments_text'] = $this->input->post('bug_comments_text');
            $sqldata['bug_comments_user_id'] = $this->data['vars']['my_id'];
            $sqldata['bug_comments_user_type'] = 'client';
            $sqldata['bug_comments_bug_id'] = $this->input->post('bug_comments_bug_id');
            $sqldata['bug_comments_project_id'] = $this->input->post('bug_comments_project_id');

            $result = $this->bug_comments_model->addComment($sqldata);
            

            //results
            if ($result) {
                $this->session->set_flashdata('notice-success', $this->data['lang']['lang_request_has_been_completed']);
                
                //update client unread comments
                $this->bugs_model->updateUnreadCommentsTeam($this->input->post('bug_comments_bug_id'), 'yes');

            } else {
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
            }
            //redirect to view
            $this->__easyRedirect('add-comment', 'view');
        }
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
        if ($form == 'add_comment') {

            //check required fields
            $fields = array('bug_comments_text' => $this->data['lang']['lang_comment']);
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
        $this->data['controller_profiling'][] = __function__; //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']); //complete the view
        $this->__commonAll_View($view);
    }

}

/* End of file tasks.php */
/* Location: ./application/controllers/admin/tasks.php */
