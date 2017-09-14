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
class Ipnlog extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'settings.ipnlog.html';
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
        $this->data['vars']['main_title'] = $this->data['lang']['lang_paypal_ipn_log'];

        //re-route to correct method
        switch ($action) {

            case 'list':
                $this->__listLog();
                break;

            case 'purge':
                $this->__purgeLog();
                break;

            case 'view':
                $this->__viewLog();
                break;

            default:
                $this->__listLog();
        }

        //css - active tab
        $this->data['vars']['css_active_tab_ipnlog'] = 'tab-active';

        //load view
        $this->__flmView('admin/main');

    }

    // -- __listLog- -------------------------------------------------------------------------------------------------------
    /**
     * get all log item from database
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
        $this->data['reg_blocks'][] = 'ipnlog';
        $this->data['blocks']['ipnlog'] = $this->paypal_ipn_log_model->listLog();

        //count results rows - used by pagination class
        $rows_count = $this->paypal_ipn_log_model->listLog($offset, 'count');

        //pagination
        $config = pagination_default_config();
        $config['base_url'] = site_url("admin/maillog/list");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $pagelimit;
        $config['uri_segment'] = 4; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //visibility - show table or show nothing found
        if ($rows_count > 0 && !empty($this->data['blocks']['ipnlog'])) {
            $this->data['visible']['wi_log_table'] = 1;
        } else {
            $this->notifications('wi_tabs_notification', $this->data['lang']['lang_no_results_found']);
        }
    }


    // -- __viewLog- -------------------------------------------------------------------------------------------------------
    /**
     * get a logs raw data
     */

    function __viewLog()
    {

        //flow control
        $next = true;

        //template
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'ipnlog.modal.html';

        //get email
        if ($next) {
            $this->data['reg_fields'][] = 'ipn';
            $this->data['fields']['ipn'] = $this->paypal_ipn_log_model->getLog($this->uri->segment(5));
            

            if ($this->data['fields']['ipn']) {

                //create array from json data
                $this->data['fields']['ipn']['raw_data'] = json_decode($this->data['fields']['ipn']['ipn_data_dump'], true);

                //incase the data was not json encoded
                if ($this->data['fields']['ipn']['raw_data'] == '') {
                    $this->data['fields']['ipn']['raw_data'] = $this->data['fields']['ipn']['ipn_data_dump'];
                } else {
                    //format it for display
                    ob_start();
                    echo "<ul>";
                    foreach ($this->data['fields']['ipn']['raw_data'] as $key => $value) {
                        echo "<li>$key: $value</li>";
                    }
                    echo "</ul>";
                    $this->data['fields']['ipn']['raw_data'] = ob_get_contents();
                    ob_end_clean();
                }
                $this->data['visible']['show_log'] = 1;
            } else {
                $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_found']);
            }
        }

    }


    // -- __purgeLog- -------------------------------------------------------------------------------------------------------
    /**
     * delete all data from the log
     */

    function __purgeLog()
    {

        //flow control
        $next = true;

        //template
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'ipnlog.modal.html';

        //delete all email in log ad redirect
        if ($next) {
            $this->paypal_ipn_log_model->purgeLog();
            $this->session->set_flashdata('notice-success', $this->data['lang']['lang_request_has_been_completed']);
            redirect('/admin/settings/ipnlog');
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
