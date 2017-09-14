<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * class for perfoming all Emailtemplates Settings related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Emaillog extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'settings.emaillog.html';
        $this->data['vars']['css_menu_heading_settings'] = 'heading-menu-active'; //menu
        $this->data['vars']['css_menu_settings'] = 'open'; //menu

        //css settings
        $this->data['vars']['css_menu_topnav_settings'] = 'nav_alternative_controls_active'; //menu

        //PERMISSIONS CHECK - GENERAL
        //Administrator only
        if ($this->data['vars']['my_group'] != 1) {
            redirect('/admin/error/permission-denied');
        }
    }

    /**
     * This is our re-routing function and is the inital function called
     *
     * 
     * 
     * 
     */
    function index()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //uri - action segment
        $action = $this->uri->segment(4);

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_settings_email_templates'];

        //re-route to correct method
        switch ($action) {

            case 'list':
                $this->__listLog();
                break;

            case 'purge':
                $this->__purgeLog();
                break;

            case 'view':
                $this->__viewMessage();
                break;

            default:
                $this->__listLog();
        }

        //css - active tab
        $this->data['vars']['css_active_tab_email'] = 'tab-active';

        //load view
        $this->__flmView('admin/main');

    }

    // -- __listClients- -------------------------------------------------------------------------------------------------------
    /**
     * list/search for clients
     */
    function __listLog()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * /admin/maillog/view/12
        * (2)->controller
        * (3)->router
        * (4)->offset
        ** -----------------------------------------*/

        //uri segments
        $offset = (is_numeric($this->uri->segment(4))) ? $this->uri->segment(4) : 0;

        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'maillog';
        $this->data['blocks']['maillog'] = $this->email_log_model->listLog();

        //count results rows - used by pagination class
        $rows_count = $this->email_log_model->listLog($offset, 'count');

        //pagination
        $config = pagination_default_config();
        $config['base_url'] = site_url("admin/maillog/list");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $pagelimit;
        $config['uri_segment'] = 4; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //visibility - show table or show nothing found
        if ($rows_count > 0 && !empty($this->data['blocks']['maillog'])) {
            $this->data['visible']['wi_log_table'] = 1;
        } else {
            $this->notifications('wi_tabs_notification', $this->data['lang']['lang_no_results_found']);
        }
    }


    // -- __viewMessage- -------------------------------------------------------------------------------------------------------
    /**
     * get an email from the log
     */

    function __viewMessage()
    {

        //flow control
        $next = true;

        //template
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'emaillog.modal.html';

        //get email
        if ($next) {
            $this->data['reg_fields'][] = 'email';
            $this->data['fields']['email'] = $this->email_log_model->getEmail($this->uri->segment(5));
            

            if ($this->data['fields']['email']) {
                $this->data['visible']['show_email'] = 1;
            } else {
                $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_found']);
            }
        }

    }


    // -- __purgeLog- -------------------------------------------------------------------------------------------------------
    /**
     * delete allemail from the log
     */

    function __purgeLog()
    {

        //flow control
        $next = true;

        //template
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'emaillog.modal.html';

        //delete all email in log ad redirect
        if ($next) {
            $this->email_log_model->purgeLog();
            $this->session->set_flashdata('notice-success', $this->data['lang']['lang_request_has_been_completed']);
            redirect('/admin/settings/emaillog');
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

/* End of file xyz.php */
/* Location: ./application/controllers/admin/xyz.php */
