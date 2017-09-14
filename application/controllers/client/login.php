<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Login related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Login extends MY_Controller
{

    //__________STANDARD VARS__________
    var $next = true; //flow control
    var $data = array(); //mega array passed to TBS

    /**
     * Initiates any of the following:
     *          - sets the default template for this controller
     *
     * 
     */
    function __construct()
    {

        parent::__construct();

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //set default template file
        $this->data['template_file'] = PATHS_CLIENT_THEME . 'login.html';

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

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {

            case 'signin':
                $this->__signIn();
                break;

            case 'logout':
                $this->__logOut();
                break;

            case 'reminder':
                $this->__loginReminder();
                break;

            case 'passwordreset':
                $this->__resetPassword();
                break;

            default:
                $this->__loginForm();
                break;

        }

        //load view
        $this->__flmView('client/main');

    }

    /**
     * show login form
     *
     */
    function __loginForm()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //show form
        $this->data['visible']['wi_login_form'] = 1;

    }

    /**
     * validate signin & set sessions
     *
     */
    function __signIn()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //prevent direct access
        if (!isset($_POST['submit'])) {
            redirect('/client/login');
        }

        //flow control
        $next = true;

        //validate form
        if ($next) {
            if (!$this->__flmFormValidation('signin')) {
                //show error
                $this->notices('error', $this->form_processor->error_message, 'noty');
                //halt
                $next = false;
            }
        }

        //validate login details & get
        if ($next) {
            $result = $this->users_model->checkLogins();
            

            //did login pass
            if ($result) {
                //set the sessions data
                $session_data = array('client_users_id' => $result['client_users_id'], 'client_users_clients_id' => $result['client_users_clients_id']);
                $this->session->set_userdata($session_data);

                //redirect to home
                redirect('/client/home');

            } else {
                //login failed
                $next = false;
            }

        }

        //results
        if (!$next) {
            //show error
            $this->notices('error', $this->data['lang']['lang_incorrect_login_details'], 'noty');

            //show form
            $this->__loginForm();
        }

    }

    /**
     * email login reminder
     *
     */
    function __loginReminder()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //check if email exists
        if ($next) {
            $result = $this->users_model->checkRecordExists($this->input->post('my_emailaddress'));
            

            if ($result) {

                //add code to dbase
                $random_code = random_string('unique');
                $password_reset_link = site_url() . 'client/login/passwordreset/' . $random_code;
                $this->users_model->resetPasswordSetup($this->input->post('my_emailaddress'), $random_code);
                

                /** --------------------------emailer - v2----------------------------------------------------------*/
                //vars
                $vars['to_name'] = $result['client_users_full_name'];
                $vars['url_reset_link'] = $password_reset_link;
                $vars['to_email'] = $result['client_users_email'];

                //email - client
                $this->__emailer('password_reset_client', $vars);
                /** --------------------------emailer - v2----------------------------------------------------------*/

                //show success
                $this->notices('success', $this->data['lang']['lang_we_have_sent_you_an_email_with_instructions'], 'noty'); //noty or html

            } else {

                //show error
                $this->notices('error', $this->data['lang']['lang_no_user_with_that_email_address_was_found'], 'noty'); //noty or html

            }
        }

        //show form
        $this->__loginForm();
    }

    /**
     * email login reminder
     *
     */
    function __resetPassword()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //show form
        $this->data['visible']['wi_login_form'] = 1;

        //verify reset code
        if ($next) {
            $user = $this->users_model->resetPasswordCheckCode($this->uri->segment(4));
            
            if (!$user) {
                //reset code error
                $this->notices('error', $this->data['lang']['lang_invalid_reset_code_or_code_has_expired'], 'noty');

                //halt
                $next = false;
            }
        }

        //reset the password
        if ($next) {

            //new random password
            $new_password = random_string('alnum', 8);

            //update database
            $result = $this->users_model->resetPassword($this->uri->segment(4), $new_password);
            
            if (!$result) {
                //reset code error
                $this->notices('error', $this->data['lang']['lang_error_occurred_info'], 'noty');

                //halt
                $next = false;
            }
        }

        //send email with new password
        if ($next) {

            /** --------------------------emailer - v2----------------------------------------------------------*/
            //vars
            $vars['to_name'] = $user['client_users_full_name'];
            $vars['new_password'] = $new_password;
            $vars['to_email'] = $user['client_users_email'];

            //email - client
            $this->__emailer('new_password_client', $vars);
            /** --------------------------emailer - v2----------------------------------------------------------*/

            //show success
            $this->notices('success', $this->data['lang']['lang_your_password_has_been_updated'], 'noty');

            //notification
            $this->notifications('wi_notification', $this->data['lang']['lang_we_have_sent_a_new_password']);

            //hide form
            $this->data['visible']['wi_login_form'] = 0;
        }

    }

    /**
     * Logout the user
     *
     * @param	string
     */
    function __logOut()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //delete all session data
        $this->session->sess_destroy();

        //redirect to login page
        redirect('client/login');

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
        if ($form == 'signin') {

            //check required fields
            $fields = array('email' => $this->data['lang']['lang_email'], 'password' => $this->data['lang']['lang_password']);
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
     * send out an email
     *
     * @param string $email email address
     */
    function __emailer($email = '', $vars = array())
    {

        //password reminder - client----------------------------------------------
        if ($email == 'password_reset_client') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('password_reset_client');
            

            //exit if email is not enabled
            if ($template['status'] != 'enabled') {
                return;
            }

            //dynamic vars
            $vars['url_dashboard'] = $this->data['vars']['site_url_client'];
            $vars['email_signature'] = $this->data['settings_company']['company_email_signature'];

            //parse email
            $email_message = parse_email_template($template['message'], $vars);

            //send email
            email_default_settings(); //defaults (from emailer helper)
            $this->email->to($vars['to_email']);
            $this->email->subject($template['subject']);
            $this->email->message($email_message);
            $this->email->send();
            //log this
            $this->__emailLog($vars['to_email'], $template['subject'], $email_message);

        }

        //new password - client----------------------------------------------------
        if ($email == 'new_password_client') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('new_password_client');
            

            //exit if email is not enabled
            if ($template['status'] != 'enabled') {
                return;
            }

            //dynamic vars
            $vars['url_dashboard'] = $this->data['vars']['site_url_client'];
            $vars['email_signature'] = $this->data['settings_company']['company_email_signature'];

            //parse email
            $email_message = parse_email_template($template['message'], $vars);

            //send email
            email_default_settings(); //defaults (from emailer helper)
            $this->email->to($vars['to_email']);
            $this->email->subject($template['subject']);
            $this->email->message($email_message);
            $this->email->send();
            //log this
            $this->__emailLog($vars['to_email'], $template['subject'], $email_message);

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

/* End of file login.php */
/* Location: ./application/controllers/client/login.php */
