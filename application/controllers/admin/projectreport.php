<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all files related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Projectreport extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.report.html';

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_project_file'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';

        $this->load->model('report_model');
    }

    /**
     * This is our re-routing function and is the inital function called
     *
     * 
     */
    function index()
    {

        /* --------------URI SEGMENTS---------------
        * [segment example]
        * /admin/files/2/view/*.*
        * (2)->controller
        * (3)->project_id
        * (4)->router
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //get project id
        $this->project_id = $this->uri->segment(3);

        //set project_id for global use in template
        $this->data['vars']['project_id'] = $this->project_id;

        //check if project exists & set some basic data
        $this->__commonAll_ProjectBasics($this->project_id);

        //PERMISSIONS CHECK - PROJECT
        //do this check after __commonAll_ProjectBasics()
        if ($this->data['project_permissions']['super_user'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //get the action from url
        $action = $this->uri->segment(4);

        //route the request
        switch ($action) {

            default:
                $this->__viewReport();
                break;
        }

        //css - active tab
        $this->data['vars']['css_active_tab_projectreport'] = 'side-menu-main-active';

        //load view
        $this->__flmView('admin/main');

    }


    // -- __viewReport- -------------------------------------------------------------------------------------------------------
    /**
     * view notes
     */

    function __viewReport()
    {

        //flow control
        $next = true;

        /** ------------------------------------------------------------
         * BASIC PROJECT STATS
         * ------------------------------------------------------------*/
        if ($next) {

            $this->data['reg_fields'][] = 'report_basic';
            $this->data['fields']['report_basic'] = $this->report_model->projectStats($this->project_id);

        }



        /** ------------------------------------------------------------
         * TIMESHEETS
         * ------------------------------------------------------------*/
        if ($next) {

            $this->data['reg_blocks'][] = 'report_time';
            $this->data['blocks']['report_time'] = $this->report_model->projectTimeSheet($this->project_id);

        }


        /** ------------------------------------------------------------
         * TASKS
         * ------------------------------------------------------------*/
        if ($next) {

            $this->data['reg_blocks'][] = 'report_tasks';
            $this->data['blocks']['report_tasks'] = $this->report_model->projectTasks($this->project_id);

        }

        /** ------------------------------------------------------------
         * PAYMENTS
         * ------------------------------------------------------------*/
        if ($next) {

            $this->data['reg_blocks'][] = 'report_payments';
            $this->data['blocks']['report_payments'] = $this->report_model->projectPayments($this->project_id);

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

/* End of file files.php */
/* Location: ./application/controllers/admin/files.php */
