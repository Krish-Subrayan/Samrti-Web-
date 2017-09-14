<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Users related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Users extends MY_Controller
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
        $this->data['template_file'] = PATHS_CLIENT_THEME . '/users.html';

        //css settings
        $this->data['vars']['css_menu_projects'] = 'open'; //menu

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

        //uri - action segment
        $action = $this->uri->segment(3);

        //default page titles
        $this->data['vars']['main_title'] = '';
        $this->data['vars']['main_title_icon'] = '';

        $this->data['vars']['sub_title'] = $this->data['lang']['lang_users'];
        $this->data['vars']['sub_title_icon'] = '<i class="icon-group"></i>';

        //re-route to correct method
        switch ($action) {

            case 'view':
                $this->__clientUsers();
                break;

            case 'add-user':
                $this->__addUser();
                break;

            case 'edit-modal':
                $this->__editUserModal();
                break;

            default:
                $this->__clientUsers();

        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * load all of a clients users
     */
    function __clientUsers()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * client/users/view/asc/sortby_id/2
        * (2)->controller
        * (3)->router
        * (4)->sort_order
        * (5)->sort_by_column
        * (6)->offset       
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //uri segments
        $sort_by = ($this->uri->segment(4) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(5) == '') ? 'sortby_due_date' : $this->uri->segment(5);
        $offset = (is_numeric($this->uri->segment(6))) ? $this->uri->segment(6) : 0;

        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'client_users';
        $this->data['blocks']['client_users'] = $this->users_model->searchUsers($offset, 'search', $this->client_id);
        

        //count results rows - used by pagination class
        $rows_count = $this->users_model->searchUsers($offset, 'count', $this->client_id);
        

        //pagination
        $config = pagination_default_config();
        $config['base_url'] = site_url("client/users/view/$sort_by/$sort_by_column");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['results_limit'];
        $config['uri_segment'] = 6; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //sorting links for menus on the top of the table
        $link_sort_by = ($sort_by == 'asc') ? 'desc' : 'asc';

        //flip the sort_by
        $link_sort_by_column = array('sortby_fullname');
        foreach ($link_sort_by_column as $column) {
            $this->data['vars'][$column] = site_url("client/users/view/$link_sort_by/$column/$offset");
        }

        //visibility
        if ($rows_count > 0) {
            //show side menu
            $this->data['visible']['wi_client_users'] = 1;
        } else {
            //show mothing found
            $this->notifications('wi_tabs_notification', $this->data['lang']['lang_no_users_found']);
        }

    }

    /**
     * add a new user
     *
     */
    function __addUser()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        if (!isset($_POST['submit'])) {
            //redirect to 'view' url instead
            $this_url = uri_string();
            $redirect = str_replace('add-user', 'view', $this_url);
            redirect($redirect);
        }

        //validate form & display any errors
        $validation = $this->__flmFormValidation('add_user');
        if (!$validation) {
            //show error
            $this->notices('error', $this->form_processor->error_message, 'html');
            //halt
            $next = false;
        }

        //add to database
        if ($next) {
            $new_users_id = $this->users_model->addUser($this->client_id);
            

            //was adding successful
            if (!$new_users_id) {
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'html');
                //halt
                $next = false;
            }
        }

        //update primary contact if selected
        if ($next) {
            if ($this->input->post('client_users_main_contact') == 'on') {
                $this->users_model->updatePrimaryContact($this->client_id, $new_users_id);
                
            }
        }

        //all is ok
        if ($next) {

            //----------------------------------------SEND EMAIL V2----------------------------------------------
            //send email to user
            $this->__emailer('new_user_client');

            //send email to admin
            $this->__emailer('new_user_admin');
            //----------------------------------------SEND EMAIL V2----------------------------------------------

            //redirect to view
            $this->session->set_flashdata('notice-success', $this->data['lang']['lang_request_has_been_completed']);
            $this->__easyRedirect('add-user', 'view');

        } else {

            //redirect to view
            $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
            $this->__easyRedirect('add-user', 'view');

        }

        //load user page
        $this->__clientUsers();
    }

    /**
     * edit client details via modal popup
     *
     */
    function __editUserModal()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //template file
        $this->data['template_file'] = PATHS_COMMON_THEME . 'users.modal.html';

        //get client id
        $user_id = $this->uri->segment(4);

        //flow control
        $next = true;

        /** CLIENT CHECK PERMISSION **/
        if (!$this->permissions->usersEdit($user_id)) {

            //permission denied
            $this->notifications('wi_notification', $this->permissions->reason);

            //halt
            $next = false;
        }

        //load from database
        if ($next) {
            $this->data['reg_fields'][] = 'profile';
            $this->data['fields']['profile'] = $this->users_model->userDetails($user_id);
            

            //visibility - show table or show nothing found
            if (!empty($this->data['fields']['profile'])) {
                $this->data['visible']['wi_edit_user_details_table'] = 1;
            } else {
                $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
            }
        }
    }

    /**
     * send out an email
     *
     * @param string $email email address
     */
    function __emailer($email = '', $vars = array())
    {

        profiling(__function__, __line__, "emailer started - vars: " . json_encode($vars), '');

        //new client welcom email-------------------------------
        if ($email == 'new_user_client') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('new_user_client');
            

            //exit if email is not enabled
            if ($template['status'] != 'enabled') {
                return;
            }

            //additional vars
            $vars['to_name'] = $this->input->post('client_users_full_name');
            $vars['to_email'] = $this->input->post('client_users_email');
            $vars['username'] = $this->input->post('client_users_email');
            $vars['password'] = $this->input->post('client_users_password');
            $vars['clients_company_name'] = $this->data['vars']['my_company_name'];
            $vars['url_dashboard'] = $this->data['vars']['site_url_client'];
            $vars['email_signature'] = $this->data['settings_company']['company_email_signature'];
            $vars['todays_date'] = $this->data['vars']['todays_date'];

            //parse email
            $email_message = parse_email_template($template['message'], $vars);

            //debug
            $this->data['email_vars'] = $vars;
            profiling(__function__, __line__, "emailer - client email: " . json_encode($vars), '');

            //send email now
            email_default_settings(); //defaults (from emailer helper)
            $this->email->to($vars['to_email']);
            $this->email->subject($template['subject']);
            $this->email->message($email_message);
            $this->email->send();
            //log this
            $this->__emailLog($vars['to_email'], $template['subject'], $email_message);

        }

        //new client welcom email-------------------------------
        if ($email == 'new_user_admin') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('new_user_admin');
            

            //exit if email is not enabled
            if ($template['status'] != 'enabled') {
                return;
            }

            //send email to multiple admins
            for ($i = 0; $i < count($this->data['vars']['mailinglist_admins_full']); $i++) {

                //additional vars
                $vars['to_name'] = $this->data['vars']['mailinglist_admins_full'][$i]['name'];
                $vars['to_email'] = $this->data['vars']['mailinglist_admins_full'][$i]['email'];
                $vars['username'] = $this->input->post('client_users_email');
                $vars['password'] = $this->input->post('client_users_password');
                $vars['url_dashboard'] = $this->data['vars']['site_url_admin'];
                $vars['users_full_name'] = $this->input->post('client_users_full_name');
                $vars['clients_company_name'] = $this->data['vars']['my_company_name'];

                //parse email
                $email_message = parse_email_template($template['message'], $vars);
                profiling(__function__, __line__, "emailer - client email: " . json_encode($vars), '');

                //debug
                $this->data['email_vars'] = $vars;

                //send email now
                email_default_settings(); //defaults (from emailer helper)
                $this->email->to($vars['to_email']);
                $this->email->subject($template['subject']);
                $this->email->message($email_message);
                $this->email->send();
                //log this
                $this->__emailLog($vars['to_email'], $template['subject'], $email_message);
            }
        }

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
        if ($form == 'add_user') {

            //check required fields
            $fields = array(
                'client_users_email' => $this->data['lang']['lang_email'],
                'client_users_full_name' => $this->data['lang']['lang_full_name'],
                'client_users_password' => $this->data['lang']['lang_password']);

            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //check email fields
            $fields = array('client_users_email' => $this->data['lang']['lang_email']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //check password (lenght only - 8 characters min)
            $fields = array('client_users_password' => $this->data['lang']['lang_password']);
            if (!$this->form_processor->validateFields($fields, 'length')) {
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

/* End of file users.php */
/* Location: ./application/controllers/client/users.php */
