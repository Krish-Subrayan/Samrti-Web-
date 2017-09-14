<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * NEXTLOOP
 *
 * This is the from loading controller. All other controlller extend this one
 * A huge amount of 'common' work and heavy lifting is done in here
 *          
 *
 */

class MY_Controller extends CI_Controller
{

    //__________STANDARD VARS__________
    var $next = true; //flow control
    public $data = array(); //mega array passed to TBS
    var $results_limit;
    var $client_details = array();
    var $project_details = array();
    var $clients_user_details = array();
    var $jsondata = array(); //mega array passed to json
    var $project_id; //current project
    var $client; //array with logged in clients profile data
    var $client_id; //logged in client
    var $user_id; //logged in client user
    var $member_id; //logged in team members id's
    var $project_leaders_id;
    var $project_leaders_details;

    //__________MAILING LISTS__________
    var $mailinglist_admins; //array of admins email addresses (used to send system emails etc0

    // -- __construct- -------------------------------------------------------------------------------------------------------
    /**
     * do some pre run tasks
     *
     * @usedby  All
     * 
     * @param	void
     * @return void
     */
    function __construct()
    {

        parent::__construct();

        /*
        |----------------------------------------------------------------------------
        | LOAD PROFILER (IF IN DEBUG MODE) - TO SEE MEMORY & EXECUTION TIME USAGE ETC
        |----------------------------------------------------------------------------
        |
        */
        if ($this->config->item('debug_mode') == 1) {
            $this->output->enable_profiler(true);
        }

        /*
        |----------------------------------------------------------------------------
        | LOAD MODELS
        |----------------------------------------------------------------------------
        |
        */
        $this->load->model('version_model');
        $this->load->model('bugs_model');
        $this->load->model('clients_model');
        $this->load->model('clients_model');
        $this->load->model('clientsoptionalfields_model');
        $this->load->model('file_messages_model');
        $this->load->model('file_messages_replies_model');
        $this->load->model('files_model');
        $this->load->model('invoice_items_model');
        $this->load->model('invoice_products_model');
        $this->load->model('invoices_model');
        $this->load->model('message_replies_model');
        $this->load->model('messages_model');
        $this->load->model('milestones_model');
        $this->load->model('payments_model');
        $this->load->model('permissions_model');
        $this->load->model('project_events_model');
        $this->load->model('project_members_model');
        $this->load->model('projects_model');
        $this->load->model('projectsoptionalfields_model');
        $this->load->model('quotationforms_model');
        $this->load->model('quotations_model');
        $this->load->model('settings_company_model');
        $this->load->model('settings_emailtemplates_model');
        $this->load->model('settings_general_model');
        $this->load->model('settings_invoices_model');
        $this->load->model('settings_paypal_model');
        $this->load->model('tasks_model');
        $this->load->model('team_message_replies_model');
        $this->load->model('team_messages_model');
        $this->load->model('teamprofile_model');
        $this->load->model('tickets_departments_model');
        $this->load->model('tickets_mailer_model');
        $this->load->model('tickets_model');
        $this->load->model('tickets_replies_model');
        $this->load->model('timer_model');
        $this->load->model('users_model');
        $this->load->model('users_model');
        $this->load->model('settings_payment_methods_model');
        $this->load->model('system_events_model');
        $this->load->model('updating_model');
        $this->load->model('settings_cash_model');
        $this->load->model('settings_bank_model');
        $this->load->model('mynotes_model');
        $this->load->model('email_queue_model');
        $this->load->model('settings_stripe_model');
        $this->load->model('email_log_model');
        $this->load->model('paypal_ipn_log_model');
        $this->load->model('task_files_model');
        $this->load->model('bug_comments_model');
        $this->load->model('clone_model');
        $this->load->model('ci_log_model');
        $this->load->model('settings_clientform_model');
        $this->load->model('settings_order_model');
        $this->load->model('settings_faktura_model');
		
		
        /*
        |----------------------------------------------------------------------------
        | custom models
        |----------------------------------------------------------------------------
        */
        $this->load->model('products_model');
		$this->load->model('orders_model');
		$this->load->model('process_order_model');
		$this->load->model('general_model');
		$this->load->model('customer_model');				
		$this->load->model('employee_model');
        $this->load->model('smart_laundry_model');

		$weekdayarray=array("Monday"=>"Mandag","Tuesday"=>"Tirsdag","Wednesday"=>"Onsdag","Thursday"=>"Torsdag","Friday"=>"Fredag","Saturday"=>"Lørdag","Sunday"=>"Søndag");
		$day=date('l',strtotime(date('Y-m-d H:i:s')));
		$montharray=array('1'=>'Januar','2'=>'Februar','3'=>'Mars','4'=>'April','5'=>'Mai','6'=>'Juni','7'=>'Juli','8'=>'August','9'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember');
		$month=date('n');
		
		$this->data['vars']['partner_branch_name'] =  substr( $this->session->userdata['partner_branch_name'],0, 3);

		/*Today date*/
		$this->data['vars']['today'] = date('d.m.Y');
	//	$this->data['vars']['today_date'] =$weekdayarray[$day].' '.date('d.F Y');
		$this->data['vars']['today_date'] =$weekdayarray[$day].' '.date('d').' '.$montharray[$month].' '.date('Y');
		$this->data['vars']['week'] = date('W');
		$this->data['vars']['today_time'] = date('H:i:s');
		$this->data['vars']['server_time'] =date("F d, Y H:i:s", time());
		$this->data['vars']['cart_total'] = $this->cart->total_items();
		

		//parkerte count
		if(count($_COOKIE) > 0)
		{
			$i=0;
			foreach($_COOKIE as $ckey=>$citems)
			{
				if(intval($ckey) > 0)
				{
					$i++;
				}
				
			}
			if($i > 0)
			{
				$this->data['vars']['parkert_count'] = '<div  id="parkerte_tcount" class="count pcount">'.$i.'</div>' ;
			}
		}
		else
		{
			$this->data['vars']['parkert_count'] =  '';
		}
		


        /*
        |----------------------------------------------------------------------------
        | EXECUTE ANY MYSQL UPDATE FILES
        |----------------------------------------------------------------------------
        |
        | this checks if any mysql files exist in the /updates folder and executes
        | If error is ecountered it halts and alerts ADMIN ONLY
        | DEVELOPER MODE - Ignore this when in developer mode
        |
        */
        if ($this->config->item('dev_mode') != 1) {
            if ($this->uri->segment(1) == 'admin') {
               // $this->__preRun_MysqlUpdates();
            }
        }


        /*
        |----------------------------------------------------------------------------
        | USER DATA - IP - BROWSER - OPERATING SYSTEM ETC
        |----------------------------------------------------------------------------
        |
        | sets database stored data that is used commonly in the system
        |
        */
        $this->__preRun_User_Agent_Data();

        /*
        |----------------------------------------------------------------------------
        | SETS ALL COMMON DYNAMIC (DATABASE) DATA
        |----------------------------------------------------------------------------
        |
        | sets database stored data that is used commonly in the system
        |
        */
        $this->__preRun_Dynamic_Data();

        /*
        |----------------------------------------------------------------------------
        | SET LANGUAGE
        |----------------------------------------------------------------------------
        |
        | - verify and set language file (set to data array)
        | - create language pulldown lists
        | - create simple array of all available language (found in the language folder)
        |
        */
        $this->__preRun_Language();

        /*
        |----------------------------------------------------------------------------
        | SETS ALL COMMON STATIC DATA
        |----------------------------------------------------------------------------
        |
        | sets static data that is used commonly in the system
        |
        */
        $this->__preRun_Static_Data();

        /*
        |----------------------------------------------------------------------------
        | SET COMMON ARRAYS
        |----------------------------------------------------------------------------
        |
        | set various/common arrays that are used by various controllers
        |
        */
        $this->__preRun_Arrays();

        /*
        |----------------------------------------------------------------------------
        | REFRESH VARIOUS DATABASE RECORDS
        |----------------------------------------------------------------------------
        |
        | - refresh essential database tables on every page loaded
        | - these are tables that must be kept extra fresh on each page load
        |
        */
        $this->__preRun_RefreshDatabase();

        /*
        |----------------------------------------------------------------------------
        | SET SITE THEME
        |----------------------------------------------------------------------------
        |
        | - verify and set site theme (set to data array)
        | - create themes pulldown lists
        | - create simple array of all available themes (found in the language folder)
        |
        */
        $this->__preRun_Theme();

        /*
        |----------------------------------------------------------------------------
        | LOAD / INITIATE ANY CUSTOM LIBRARIES
        |----------------------------------------------------------------------------
        |
        | - load any libraries that are commonly used but need to be loaded in
        |   some special way
        |
        */
        $this->__preRun_Libraries();

        /*
        |----------------------------------------------------------------------------
        | BEFORE ANYTHING ELSE - CHECK SYSTEM INTEGRITY (SANITY CHECK)
        |----------------------------------------------------------------------------
        |
        | this checks that all parts of the system
        | are setup as expected
        | DEVELOPER MODE - Ignore this when in developer mode
        |
        */
        if ($this->config->item('dev_mode') != 1) {
            $this->__preRun_System_Sanity_Checks();
        }


        /*
        |----------------------------------------------------------------------------
        | SET TEAM MEMBERS CORE PERMISSION LEVELS BASED ON THEIR GROUP
        |----------------------------------------------------------------------------
        |
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {

            //get my permission levels
            set_my_permissions($this->groups_model->groupDetails($this->session->userdata('team_profile_groups_id')));

            //set my D.A.V.E (delete/add/view/edit) permissions
            $this->__commonAdmin_SetMyPermissions();
        }

        /*
        |----------------------------------------------------------------------------
        | REGISTER TEAM MEMBER LAST ACTIVE IN DATABASE
        |----------------------------------------------------------------------------
        |
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $this->__commonAdmin_RegisterLastActive();
        }

        /*
        |----------------------------------------------------------------------------
        | REGISTER CLIENT USER LAST ACTIVE IN DATABASE
        |----------------------------------------------------------------------------
        |
        */
        if (is_numeric($this->session->userdata('client_users_id'))) {
            $this->__commonClient_RegisterLastActive();
        }

        /*
        |----------------------------------------------------------------------------
        | DISPLAY ANY SESSION FLASH NOTICES
        |----------------------------------------------------------------------------
        | This can be messages that were set before a page redirected. They are set
        | as follows, on the previous page (before redirect or post etc)
        | $this->session->set_flashdata(notice-success', 'Request has been completed');
        | $this->session->set_flashdata(notice-error', 'Request could not be completed');
        |
        */
        if ($this->session->flashdata('notice-success') != '') {
            $this->notices('success', $this->session->flashdata('notice-success'), 'noty');
        }
        if ($this->session->flashdata('notice-error') != '') {
            $this->notices('error', $this->session->flashdata('notice-error'), 'noty');
        }
        if ($this->session->flashdata('notice-success-html') != '') {
            $this->notices('success', $this->session->flashdata('notice-success-html'), 'html');
        }
        if ($this->session->flashdata('notice-error-html') != '') {
            $this->notices('error', $this->session->flashdata('notice-error-html'), 'html');
        }
        if ($this->session->flashdata('notification') != '') {
            $this->notifications('wi_notification', $this->session->flashdata('notification'));
        }
    }
	

    /** 
	 *get category menu 
     */
    function __categoryMenu() {
   
		
        //profiling
        $this->data['controller_profiling'][] = __function__;
		
		
        $cat_id = is_numeric($this->uri->segment(4)) ?  $this->uri->segment(4): '';
		
        //get results
		$this->data['reg_blocks'][] = 'category';
		$this->data['blocks']['category'] = $this->products_model->getCategories();
		
		//print_r($this->data['blocks']['category'] );
		
		if (count($this->data['blocks']['category']) > 0) {
				foreach($this->data['blocks']['category']  as $ckey=>$catitems)
				{
					if($catitems['id'] == 12)
					{
						$tilbudProducts=$this->products_model->getTilbudProducts();
						if(count($tilbudProducts) > 0)
						{
							$ii=0;
							foreach($tilbudProducts as $tilbuditems)
							{
								if($tilbuditems['id'] != '66')
								{
									$ii++;
								}
								
							}
							if($ii > 0)
							{
								$this->data['blocks']['category'][$ckey]['count']=$ii;
							}
							else
							{
								unset($this->data['blocks']['category'][$ckey]);
							}
							
							
						}
					}
				}
			}
			
			if(count($this->data['blocks']['category']) > 0)
			{
				$newcat=$this->data['blocks']['category'];
				$i=0;
				foreach($newcat as $citems)
				{
					$this->data['blocks']['category'][$i]=$citems;
					$i++;
				}
			}
			
			
		$this->data['debug'][] = $this->products_model->debug_data;
		$selected = 'Kategorier';	
		$str = '';
		
		//get poupular count 
		$cus_id = $this->session->userdata['pos_customer_id'];
		$subscription = $this->payments_model->getSaldostatus($cus_id);
		$this->data['reg_blocks'][] = 'popular';
		$this->data['blocks']['popular'] = $this->products_model->getPopularProduct($cus_id);
		$this->data['debug'][] = $this->products_model->debug_data;
		
		$popular_count = count($this->data['blocks']['popular']);
		$boolean = false;

		if (count($this->data['blocks']['category']) > 0) {
			for($i=0;$i<count($this->data['blocks']['category']);$i++){
				
			 $count =	($this->data['blocks']['category'][$i]['id']=='13') ?  $popular_count : $this->data['blocks']['category'][$i]['count'];
			  
			  if( $count != ''){
				$boolean = true;
				  
				$path_parts = pathinfo($this->data['blocks']['category'][$i]['path']);
				$this->data['blocks']['category'][$i]['thumb'] = $path_parts['filename'] . "." .$path_parts['extension'];
				if($cat_id == $this->data['blocks']['category'][$i]['id']){
					$selected = $this->data['blocks']['category'][$i]['name'];
				}
				
				
				$str.='<a href="'.$this->data['vars']['site_url'].'admin/products/category/'.$this->data['blocks']['category'][$i]['id'].'">
						<div class="row">
							<div class="col-xs-2">
								<img width="25" height="25" src="'.$this->data['vars']['site_url'].'images/'.$this->data['blocks']['category'][$i]['thumb'].'"  title="'.$this->data['blocks']['category'][$i]['name'].'" alt="'.$this->data['blocks']['category'][$i]['name'].'"/>
							</div>
							<div class="col-xs-5">
								<p>'.$this->data['blocks']['category'][$i]['name'].'</p>
							</div>
							<div class="col-xs-5">
								<p class="grey">'. $count.' produkter</p>
							</div>
						</div>
					</a>';            
				
			  }
			}
		}
		if(!$boolean){
			$str.='<a href="#"><div class="row">
							<div class="col-xs-12 text-center">
								<p style="text-align: center;">No Categories Found</p>
							</div>
						</div></a>';
		}


		$this->data['lists']['menu_selected'] = $selected;
		$this->data['lists']['menu'] = $str;
		
    }
	
	

    //=================================================================ADMIN METHODS============================================================\\

    // -- __commonAdmin_LoggedInCheck- -------------------------------------------------------------------------------------------------------
    /**
     * checks if user is logged in, else redirects
     *
     * @usedby  Admin
     * 
     * @param	void
     * @return void
     */
    function __commonAdmin_LoggedInCheck()
    {

        //is user logged in..else redirect to login page
        logged_in_check('partner_branch');				
		if($this->session->userdata['session_date'])			
		{				
			if($this->session->userdata['session_date'] != date('Y-m-d'))				
			{					
				redirect('/admin/login');				
			}			
		}			
		else			
		{				
			redirect('/admin/login');			
		}

    }

    // -- __commonAdmin_RegisterLastActive- -------------------------------------------------------------------------------------------------------
    /**
     * records a team members last activity as NOW() whenver this controler is loaded
     *
     * @usedby  Admin
     * @usedby  Team
     * 
     * @param	void
     * @return void
     */
    function __commonAdmin_RegisterLastActive()
    {

        //update team member as last active now()
        $this->teamprofile_model->registerLastActive($this->session->userdata('team_profile_id'));

    }

    // -- __commonAdmin_PermissionsMenus- -------------------------------------------------------------------------------------------------------
    /**
     *  Set my human D.A.V.E (delete/add/view/edit) permissions for CATEGORY
     *
     *  [CATEGORIES] are set in the common array
     *                      - $this->data['common_arrays']['permission_categories']
     *
     *  Now set permission for each category, based on my permission level fro each category
     *                      - $this->data['permission'][delete_item_my_project_files] = 1
     *                      - $this->data['permission'][add_item_my_project_files] = 1
     *                      - $this->data['permission'][view_item_my_project_files] = 1
     *                      - $this->data['permission'][edit_item_my_project_files] = 1
     *
     *  [PERMISSION LEVELS] These are set when I log in and are taken from my [GROUPS] permission
     *                      - They are all set in $this->data['my_permissions']
     *
     * @usedby  Admin
     * 
     * @param	void
     * @return void
     */
    function __commonAdmin_SetMyPermissions()
    {

        //loop through each category and set my D.A.V.E (delete/add/view/edit) permissions
        foreach ($this->data['common_arrays']['permission_categories'] as $value) {

            //the individual D.A.V.E permissions for each category (e.g.delete_item_clients)
            $delete = 'delete_item_' . $value;
            $add = 'add_item_' . $value;
            $view = 'view_item_' . $value;
            $edit = 'edit_item_' . $value;

            //what is my numeric permission level for this category
            $my_permission = $this->data['my_permissions'][$value];

            //set my D.A.V.E into a new array $this->data['permission']
            $this->data['permission'][$delete] = ($my_permission >= 4) ? 1 : 0;
            $this->data['permission'][$add] = ($my_permission >= 2) ? 1 : 0;
            $this->data['permission'][$view] = ($my_permission >= 1) ? 1 : 0;
            $this->data['permission'][$edit] = ($my_permission >= 3) ? 1 : 0;
        }
    }

    // -- __commonPermissionVisibility- -------------------------------------------------------------------------------------------------------
    /**
     *  Set visibility of common items such as menus etc, based on team members permissions
     *
     *
     * @usedby  Admin
     * 
     * @param	void
     * @return void
     */
    function __commonPermissionVisibility()
    {

    }

    // -- __commonAll_ProjectBasics- -------------------------------------------------------------------------------------------------------
    /**
     * 1) checks if a project exists. 
     * 2) loads the main project detials into $data['rows4']
     * 3) sets some vars that will be used universally in this object [$this->client_id]
     * 4) If project does not exist, it redirects to error page
     *
     * @usedby  Admin & Client
     * 
     * @param numeric $project_id]
     * @return void
     */
    function __commonAll_ProjectBasics($project_id = '', $redirect = 'yes')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //register this project counts array (used later - below)
        $this->data['reg_fields'][] = 'this_project_counts';

        //check if project exists
        if ($next) {
            if (!$this->project_details = $this->projects_model->projectDetails($project_id)) {
                if ($redirect == 'yes') {
                    //redirect to error handler
                    if (is_numeric($this->session->userdata('team_profile_id'))) {
                        redirect('admin/error/not-found');
                    }
                    if (is_numeric($this->session->userdata('client_users_id'))) {
                        redirect('client/error/not-found');
                    }
                } else {
                    return false;
                }
            }
        }

        //load of of the projects data
        if ($next) {

            //main project data
            $this->data['rows4'] = $this->project_details;
            $this->data['reg_fields'][] = 'project_details';
            $this->data['fields']['project_details'] = $this->project_details; //for tbs merging
            $this->data['project_details'] = $this->project_details; //for general use

            //set client_id
            $this->client_id = $this->project_details['projects_clients_id'];
            $this->data['vars']['client_id'] = $this->client_id;

            //do not disturb the session client_id for clients
            if (is_numeric($this->session->userdata('client_users_id'))) {
                $this->client_id = $this->session->userdata('client_users_clients_id');
            }

            //get clients primary user
            $this->clients_user_details = $this->users_model->clientPrimaryUser($this->client_id);


            //get team project leader details
            $this->project_leaders_details = $this->project_members_model->getProjectLead($project_id);


            //set the project leader id into a var
            if (is_numeric($this->session->userdata('team_profile_id'))) {
                $this->data['vars']['project_leaders_id'] = $this->project_leaders_details['project_members_team_id'];
            }

            //get the project percentage complete figure
            $project_percentage = $this->__commonAdmin_ProjectPecentageComplete($this->project_id);
            $this->data['vars']['project_percentage_completed'] = $project_percentage;

            //refresh all timers for this project and make the time up2date
            $this->timer_model->refeshProjectTimers($this->project_id);


            //get ALL time spent on project
            $this->data['vars']['project_timer_hours_spent'] = $this->timer_model->projectTime($this->project_id, 'all');


            /*MY TIMER SPENT ON PROJECT
            * --only do this if a team members is logged in---
            */
            if (is_numeric($this->session->userdata('team_profile_id'))) {
                $this->data['vars']['my_project_timer_hours_spent'] = $this->timer_model->projectTime($this->project_id, $this->data['vars']['my_id']);

            }

            /*MY TIMER STATUS & BUTTON VISIBILITY
            * --only do this if a team members is logged in---
            */
            if (is_numeric($this->session->userdata('team_profile_id'))) {
                $this->data['vars']['my_project_timer_status'] = $this->timer_model->timerStatus($this->project_id, $this->data['vars']['my_id']);

                if ($this->data['vars']['my_project_timer_status'] == 'running') {
                    //show start button
                    $this->data['vars']['css_start_timer_btn'] = 'invisible';
                    $this->data['vars']['css_stop_timer_btn'] = 'visible';
                } else {
                    //shw stop button
                    $this->data['vars']['css_stop_timer_btn'] = 'invisible';
                    $this->data['vars']['css_start_timer_btn'] = 'visible';
                }
            }

            /*CREATE A TIMER FOR ME IF I DONT HAVE ONE
            * --only do this if a team members is logged in---
            */
            if (is_numeric($this->session->userdata('team_profile_id'))) {
                if ($this->data['vars']['my_project_timer_status'] == 'none') {
                    $this->timer_model->addNewTimer($this->project_id, $this->data['vars']['my_id']);

                }
            }

            /* MY TIMER ID FOR THIS PROJECT
            * --only do this if a team members is logged in---
            */
            if (is_numeric($this->session->userdata('team_profile_id'))) {
                $result = $this->timer_model->timerDetails($this->project_id, $this->data['vars']['my_id']);
                $this->data['vars']['my_project_timer_id'] = $result['timer_id'];

            }

            /* MY TASKS COUNT FOR THIS PROJECT
            * [my_project_tasks_count.pending]
            * [my_project_tasks_count.completed]
            * [my_project_tasks_count.behing_schedule]
            * [my_project_tasks_count.all_open]
            * [my_project_tasks_count.all_tasks]
            * --only do this if a team members is logged in---
            */
            if (is_numeric($this->session->userdata('team_profile_id'))) {
                $this->data['reg_fields'][] = 'my_project_tasks_count';
                $this->data['fields']['my_project_tasks_count'] = $this->tasks_model->allMyTasksCounts($this->session->userdata('team_profile_id'), $this->project_id);

            }

            /* PROJECT MILESTONE COUNTS
            * [this_project_counts.milestone_all]
            * [this_project_counts.milestone_all_open]
            * [this_project_counts.milestone_inprogress]
            * [this_project_counts.milestone_behind]
            * [this_project_counts.milestone_completed]
            */
            $this->data['fields']['this_project_counts']['milestone_all'] = $this->milestones_model->countMilestones($this->project_id, 'all');

            $this->data['fields']['this_project_counts']['milestone_all_open'] = $this->milestones_model->countMilestones($this->project_id, 'uncompleted');

            $this->data['fields']['this_project_counts']['milestone_completed'] = $this->milestones_model->countMilestones($this->project_id, 'completed');

            $this->data['fields']['this_project_counts']['milestone_behind'] = $this->milestones_model->countMilestones($this->project_id, 'behind schedule');

            $this->data['fields']['this_project_counts']['milestone_inprogress'] = $this->milestones_model->countMilestones($this->project_id, 'in progress');


            /* PROJECT INVOICES COUNTS
            * [this_project_counts.invoices_all]           
            * [this_project_counts.invoices_paid]
            * [this_project_counts.invoices_due]
            * [this_project_counts.invoices_overdue]
            * [this_project_counts.invoices_partpaid]
            * [this_project_counts.invoices_all_unpaid]
            */
            $this->data['fields']['this_project_counts']['invoices_all'] = $this->invoices_model->countInvoices($this->project_id, 'project', 'all');

            $this->data['fields']['this_project_counts']['invoices_paid'] = $this->invoices_model->countInvoices($this->project_id, 'project', 'paid');

            $this->data['fields']['this_project_counts']['invoices_due'] = $this->invoices_model->countInvoices($this->project_id, 'project', 'due');

            $this->data['fields']['this_project_counts']['invoices_overdue'] = $this->invoices_model->countInvoices($this->project_id, 'project', 'overdue');

            $this->data['fields']['this_project_counts']['invoices_partpaid'] = $this->invoices_model->countInvoices($this->project_id, 'project', 'partpaid');

            $this->data['fields']['this_project_counts']['invoices_all_unpaid'] = $this->invoices_model->countInvoices($this->project_id, 'project', 'all-unpaid');


            /* PROJECT FILES COUNT
            */
            $this->data['fields']['this_project_counts']['all_files'] = $this->files_model->countFiles($this->project_id, 'all');


            /* PROJECT MAILING LIST
            * creates a list of all users to receive email notifications
            * [team members] and [client users] for this project
            * only users who have anabled email notifications will receive them
            */
            $this->data['vars']['project_members_team'] = $this->project_members_model->listProjectmembers($this->project_id);

            $this->data['vars']['project_members_client'] = $this->users_model->clientUsers($this->project_details['clients_id']);

            //add team members to mailing list for this project
            for ($i = 0; $i < count($this->data['vars']['project_members_team']); $i++) {
                if ($this->data['vars']['project_members_team'][$i]['team_profile_notifications_system'] == 'yes') {
                    $name = $this->data['vars']['project_members_team'][$i]['team_profile_full_name'];
                    $email = $this->data['vars']['project_members_team'][$i]['team_profile_email'];
                    $user_type = 'team';
                    $this->data['vars']['project_mailing_list'][] = array(
                        'name' => $name,
                        'email' => $email,
                        'user_type' => $user_type);
                    $this->data['vars']['project_team_mailing_list'][] = array(
                        'name' => $name,
                        'email' => $email,
                        'user_type' => $user_type);
                }
            }
            //add client users to mailing list for this project
            for ($i = 0; $i < count($this->data['vars']['project_members_client']); $i++) {
                if ($this->data['vars']['project_members_client'][$i]['client_notifications_system'] == 'yes') {
                    $name = $this->data['vars']['project_members_client'][$i]['client_users_full_name'];
                    $email = $this->data['vars']['project_members_client'][$i]['client_users_email'];
                    $user_type = 'client';
                    $this->data['vars']['project_mailing_list'][] = array(
                        'name' => $name,
                        'email' => $email,
                        'user_type' => $user_type);
                }
            }
        }

        /** PERMISSIONS - VISIBILITY **/
        if ($next) {
            if (is_numeric($this->session->userdata('team_profile_id'))) {
                $this->__common_ProjectPermissions($this->project_id, $this->session->userdata('team_profile_id'));
            }
        }
    }

    // -- __common_ProjectPermissions- -------------------------------------------------------------------------------------------------------
    /**
     * Create my final permission for [THIS PROJECT[
     * Store the permissions in $this->data['perm'] array.
     * My final permissions will depened of if I am a project leader or not for [THIS PROJECT]
     * We will use $this->data['perm'] for hiding certain menus, buttons, and general permission testing 
     *
     * @usedby  Admin
     * 
     * @param numeric $project_id]
     * @return void
     */
    function __common_ProjectPermissions($project_id, $members_id)
    {

        $my_group = $this->data['vars']['my_group'];
        $my_projects_array = $this->data['my_projects_array'];
        $my_active_projects_array = $this->data['my_active_projects_array'];
        $my_leaders_projects_array = $this->data['my_leaders_projects_array'];
        $permission_categories = $this->data['common_arrays']['permission_categories'];

        /**-------------------------------------------------------------
        * SPECIAL CATEGORIES
        *--------------------------------------------------------------
        * these categories should not be altered from their database 
        * permission settings
        *
        *--------------------------------------------------------------*/
        $special_categories = array(
            'clients',
            'bugs',
            'tickets',
            'quotations');

        //loop through all categories and set my [project permissions] for this project
        foreach ($permission_categories as $key) {

            $view = "view_item_$key";
            $edit = "edit_item_$key";
            $add = "add_item_$key";
            $delete = "delete_item_$key";

            //exclude special categories
            if (!in_array($key, $special_categories)) {

                /**-------------------------------------------------------------
                * I AM A PROJECT LEADER
                *--------------------------------------------------------------
                * Grant new FULL permissions on projects that I am leader of
                * overide whatever my general permissions for each category
                * otherwise 
                *--------------------------------------------------------------*/
                if (in_array($project_id, $my_leaders_projects_array) || $this->data['vars']['my_group'] == 1) {
                    //overide my permission & give full acess
                    $this->data['project_permissions'][$delete] = 1;
                    $this->data['project_permissions'][$add] = 1;
                    $this->data['project_permissions'][$view] = 1;
                    $this->data['project_permissions'][$edit] = 1;

                } else {

                    /**-------------------------------------------------------------
                    * I AM NOT ASSIGNED TO THIS PROJECT
                    *--------------------------------------------------------------*/
                    if (!in_array($project_id, $my_projects_array)) {
                        $this->data['project_permissions'][$delete] = 0;
                        $this->data['project_permissions'][$add] = 0;
                        $this->data['project_permissions'][$view] = 0;
                        $this->data['project_permissions'][$edit] = 0;

                    } else {

                        /**-------------------------------------------------------------
                        * I AM NOT A PROJECT LEADER
                        *--------------------------------------------------------------*/
                        $this->data['project_permissions'][$delete] = $this->data['permission'][$delete];
                        $this->data['project_permissions'][$add] = $this->data['permission'][$add];
                        $this->data['project_permissions'][$view] = $this->data['permission'][$view];
                        $this->data['project_permissions'][$edit] = $this->data['permission'][$edit];
                    }
                }
            }
        }

        /**-------------------------------------------------------------
        * I AM A SUPER USER -OR- A REGULAR USER
        *--------------------------------------------------------------
        * Grant the [admin] and [project leader] SUPER USER STATUS
        * this will allow easier identification of these two users
        * in TBS etc 
        *--------------------------------------------------------------*/
        if (in_array($project_id, $my_leaders_projects_array) || $my_group == 1) {
            $this->data['project_permissions']['super_user'] = 1;
        } else {
            $this->data['project_permissions']['regular_user'] = 1;
        }

    }

    // -- __commonAdmin_ProjectPecentageComplete- -------------------------------------------------------------------------------------------------------
    /**
     * calculate the percentage progress of a particular project
     * calculation is based on the cumulative percentages for each milestone
     * a milestones progress is measured as a percentage/fraction of the completed tasks for that milesone
     * 
     * [sum of all current milstone percentages]/[total possible milestone percentages] *100
     * (i.e. 5 milestones = [5* 100% = 500%] total possible milestone percentages)
     *
     * @usedby  Admin
     * @usedby  Team
     * 
     * @param	void
     * @return void
     */
    function __commonAdmin_ProjectPecentageComplete($project_id)
    {

        if (!is_numeric($project_id)) {
            return 0;
        }

        //---------------calculate percentage-------------------
        //calculate the possible [total milestone] percentages (i.e. 5 milestones = [5* 100% = 500%])
        $total_possible_percentage = ($this->milestones_model->countMilestones($project_id, 'all')) * 100; //sum up all the current milestone percentage from all the milestone for this project
        $total_possible_percentage = ($total_possible_percentage <= 0) ? 100 : $total_possible_percentage; //make sure we have something
        $milestones = $this->milestones_model->listMilestones(0, 'results', $project_id);
        $current_percentages_total = 0;
        for ($i = 0; $i < count($milestones); $i++) {
            $current_percentages_total += $milestones[$i]['percentage'];
        }

        //work out the PROJECT progress based on [$current_percentages_total/$total_possible_percentage*100]
        $project_percentage = round(($current_percentages_total / $total_possible_percentage) * 100);
        $project_percentage = (is_numeric($project_percentage)) ? $project_percentage : 0; //return percentage
        return $project_percentage;
    }

    // -- __commonAdmin_Milestones- -------------------------------------------------------------------------------------------------------
    /**
     *
     *
     * @usedby  Admin
     * 
     * @param numeric $project_id]
     * @return void
     */
    function __commonAdmin_Milestones($project_id)
    {

    }

    // -- __preRun_Arrays- -------------------------------------------------------------------------------------------------------
    /**
     * makes common arrays globally available
     *
     * @usedby  Admin
     * 
     * @param	void
     * @return void
     */
    function __preRun_Arrays()
    {

        /** used in setting permissions etc.
         *(same as the column names in [groups] table)
         */
        $this->data['common_arrays']['permission_categories'] = array(
            'my_project_files',
            'my_project_details',
            'my_project_milestones',
            'my_project_my_tasks',
            'my_project_others_tasks',
            'my_project_messages',
            'my_project_team_messages',
            'my_project_invoices',
            'bugs',
            'clients',
            'tickets',
            'quotations');
        /** timer updates
         */
        $this->data['common_arrays']['timer_status'] = array(
            'running',
            'stopped',
            'reset');
    }

    //=================================================================CLIENT METHODS============================================================\\

    // -- __commonClient_LoggedInCheck- -------------------------------------------------------------------------------------------------------
    /**
     * checks if client user is logged in, else redirects
     * 
     * @usedby  Client
     * 
     * @param	void
     * @return void
     */
    function __commonClient_LoggedInCheck()
    {

        //is user logged in..else redirect to login page
        if (!is_numeric($this->session->userdata('client_users_id')) || !is_numeric($this->session->userdata('client_users_clients_id'))) {
            redirect('client/login');
        }
    }

    // -- __commonClient_registerLastActive- -------------------------------------------------------------------------------------------------------
    /**
     * records a clientusers last activity as NOW() whenver this controler is loaded
     * 
     * @usedby  Client
     * 
     * @param	void
     * @return void
     */
    function __commonClient_registerLastActive()
    {
        $this->users_model->registerLastActive($this->session->userdata('client_users_clients_id'));

    }

    //=================================================================ADMIN & CLIENT METHODS============================================================\\

    // -- notices- -------------------------------------------------------------------------------------------------------
    /**
     * set the visibility of error messages (notices)
     * 
     * @usedby  ALL
     * 
     * @param	string [type: error/success]   [message: the error message]
     * @return void [sets to big data]
     */
    function notices($type = '', $message = '', $display = 'noty')
    {

        //valid $type
        $valid_type = array('success', 'error'); //some sanity checks for valid params
        if (!in_array($type, $valid_type)) {
            return;
        }

        if ($message == '') {
            return;
        }

        //show ordinary notices on top of page
        if ($display == 'html') {
            if ($type == 'error') {
                $widget = 'wi_notice_error'; //used in tbs conditional [onshow; when wi_notice_error ==1] statement
            }

            //set the widget var
            if ($type == 'success') {
                $widget = 'wi_notice_success'; //used in tbs conditional [onshow; when wi_notice_success ==1] statement
            }
        }

        //show noty.js popup on bottom of page
        if ($display == 'noty') {
            if ($type == 'error') {
                $widget = 'wi_notice_error_noty'; //noty
            }

            //set the widget var
            if ($type == 'success') {
                $widget = 'wi_notice_success_noty'; //noty
            }
        }

        //save in big data array, for tbs usage
        $this->data['visible'][$widget] = 1;
        $this->data['notices'][$type] = $message;
    }

    // -- notifications- -------------------------------------------------------------------------------------------------------
    /**
     * set the visibility of notification style divs (e.g. [nothing found]
     * 
     * @usedby  ALL
     * 
     * @param	[message: the error message]
     * @param	[type: tabs|general]
     * @return void [sets to big data]
     */
    function notifications($block = '', $message = '')
    {

        //set message and visibility of notification
        $this->data['visible'][$block] = 1;
        $this->data['vars']['notification'] = $message;
    }

    // -- __commonAll_View- -------------------------------------------------------------------------------------------------------
    /**
     * sets routine view settings and loads the view
     * 
     * @usedby  ALL
     * 
     * @param	string [type: error/success]   [message: the error message]
     * @return void [sets to big data]
     */
    function __commonAll_View($view = '')
    {
        //refresh dynamic data (again) is there was a post
        if (isset($_POST['submit'])) {
            $this->__preRun_Dynamic_Data();
        }

        //post data
        $this->data['post'] = $_POST; //get data
        $this->data['get'] = $_GET; //url segmets array
        $this->data['url_segments'] = $this->uri->segment_array(); //sent to TBS engine
        $this->load->view($view, array('data' => $this->data));
    }

    // -- __preRun_System_Sanity_Checks- -------------------------------------------------------------------------------------------------------
    /**
     * checks the systems integrity, such
     *        - has installation completed
     *        - has the install folder been deleted
     *        - can we connect to the database
     *        - are writeable directories set correctly
     *
     * @usedby  ALL
     * 
     * @param	void
     * @return void [sets to big data]
     */
    function __preRun_System_Sanity_Checks()
    {

        //flow control
        $next = true;

        //declare
        $message = '';

        /*INSTALLATION FOLDER*/
        if ($next) {
            if (is_dir(FCPATH . 'install')) {
                $message = 'If you have completed installation, you must delete the <strong>INSTALL</strong> folder';
                $sanity_failed = true;
                $next = false;
            }
        }

        /*INSTALLATION COMPLETED -GET VERSION*/
        if ($next) {
            $result = $this->version_model->currentVersion();

            if ($result) {
                $this->data['version']['number'] = $result['version'];
                $this->data['version']['date'] = $result['date_installed'];
                $this->data['version']['install_type'] = $result['install_type'];

                //results
                $version_results = '<li> Version Number: ' . $result['version'] . ' <span style="color:#00a625;">PASSED</span></li>';
            } else {
                //set the message
                $version_results = '<li> Version Number: Unkown <span style="color:#fb2020;">FAILED</span></li>';
            }

            $message .= "<p><strong>Checking Application Version Number</strong></p> 
                            <ul>" . $version_results . "</ul>";

        }

        /*MAKE SURE TEMPIS THERE*/
        if ($next) {
            if (!is_dir(FILES_TEMP_FOLDER)) {
                @mkdir(FILES_TEMP_FOLDER);
            }
        }


        /*WRITEABLE DIRECTORIES*/
        if ($next) {

            $writeable_directories = array(
                FILES_BASE_FOLDER,
                PATHS_CACHE_FOLDER,
                PATHS_LOGS_FOLDER,
                UPDATES_FOLDER,
                FILES_AVATARS_FOLDER,
                FILES_TEMP_FOLDER,
                FILES_PROJECT_FOLDER,
                FILES_DATABASE_BACKUP_FOLDER,
                FILES_TICKETS_FOLDER,
                FILES_TASKS_FOLDER,
                PATHS_CAPTCHA_FOLDER,
                DATABASE_CONFIG_FILE); //loop and check each folder

            $writeable_results = ''; //declare
            foreach ($writeable_directories as $value) {

                if (is_writeable($value)) {
                    $writeable_results .= '<li>' . $value . ' - <span style="color:#00a625;">PASSED</span></li>';
                } else {
                    $writeable_results .= '<li>' . $value . ' - <span style="color:#fb2020;">FAILED</span></li>';
                    $sanity_failed = true;
                }
            }

            //set the message
            $message .= "<p><strong>Checking Directories CHMOD Settings</strong></p> 
                            <ul>" . $writeable_results . "</ul>";
        }

        /*CHECK CURL IS INSTALLED*/
        if ($next) {
            if (!function_exists('curl_version')) {
                $curl_results = '<li>Curl Installed- <span style="color:#fb2020;">FAILED</span></li>';
                $sanity_failed = true;
            } else {
                $curl_results = '<li>Curl Installed- <span  style="color:#00a625;">PASSED</span></li>';
            }

            //message
            $message .= "<p><strong>Checking Curl </strong></p> 
                            <ul>" . $curl_results . "</ul>";
        }

        /*CHECK PHP VERSION*/
        if ($next) {
            if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
                $php_results = '<li>PHP Version at least 5.3.0 - <span  style="color:#00a625;">PASSED</span></li>';
            } else {
                $php_results = '<li>PHP Version at least 5.3.0 - <span style="color:#fb2020;">FAILED</span></li>';
                $sanity_failed = true;
            }

            //message
            $message .= "<p><strong>Checking PHP Version </strong></p> 
                            <ul>" . $php_results . "</ul>";
        }

        //sanity check - failed
        if (isset($sanity_failed) && $sanity_failed) {
            //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\

            //log error
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: $message]"); //show error and die
            show_error($message, 500); //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\
        }

    }

    // -- __preRun_MysqlUpdates- -------------------------------------------------------------------------------------------------------
    /**
     * checks the /updates folder for any .sql files
     *        - exectutes each file
     *        - if error are encountered it displays error and halts
     *        - if all is ok, it set a session message to say update was successful
     *
     * @usedby  ADMIN
     * @return void [shows errr]
     */
    function __preRun_MysqlUpdates()
    {

        //only do this if on admin panel
        if ($this->uri->segment(1) != 'admin') {
            return;
        }

        //get current version of this application
        $versioning = $this->version_model->currentVersion();
        $version = $versioning['version'];

        //get list of all file in /updates folder
        $map = directory_map(UPDATES_FOLDER, 1);

        //loop through all the files and select only the .sql files
        foreach ($map as $key => $value) {

            //set the file path
            $file_path = UPDATES_FOLDER . $value;

            //get some information about this file/file path
            $file_path_info = pathinfo(UPDATES_FOLDER . $value);

            //use only .sql extension files
            if (is_array($file_path_info) && $file_path_info['extension'] == 'sql') {

                log_message('error', __file__ . ' --- ' . __function__ . ' --- ' . __line__ . ' --- DB UPDATE FILE FOUND (' . $file_path . ')');

                //get the file contents into a var
                $file_contents = file_get_contents($file_path);

                //flow control
                $next_sql = true;

                //does the file specify a 'required' version
                if (preg_match('%-- required-version-\[(.*?)\]--do-not-delete-----%', $file_contents, $regs)) {
                    $required_version = $regs[1];
                    if ($version != $required_version) {
                        $next_sql = false;
                    }
                    //for error
                    $required_type = '[required]';
                }

                //does the file specify a 'minimum' version
                if (preg_match('%-- minimum-version-\[(.*?)\]--do-not-delete-----%', $file_contents, $regs)) {
                    $minimum_version = $regs[1];
                    if ($version < $minimum_version) {
                        $next_sql = false;
                    }
                    //for error
                    $required_type = '[minimum]';
                }

                //was there a version (i.e minimum or required version) error
                if (!$next_sql) {
                    /** ------ CODEIGNITER STYLE - SYSTEM ERROR HANDLING----- */
                    $ci_message .= "<p><strong>UPDATING MYSQL DATABASE FAILED</strong></p>
                                    <p>
                                    Update File Name: $file_path
                                    <br>Your version of Freelance Dashboard is not compatible with this update.
                                    <br><strong>Specified Version:</strong> $minimum_version $required_version $required_type
                                    <br><strong>Your Current Version:</strong> $version
                                    </p>";
                    show_error($ci_message, 500);
                    /** ------ CODEIGNITER STYLE - SYSTEM ERROR HANDLING----- */
                    //exit whole update process
                    break;
                }

                //now get the file again and create line by line sql
                if ($next_sql) {

                    //get file again
                    $file_content = file($file_path);
                    //loop through each sql
                    foreach ($file_content as $sql_line) {
                        if (trim($sql_line) != "" && strpos($sql_line, "--") === false) {
                            $query .= $sql_line;
                            if (preg_match("/;\s*$/", $sql_line)) {

                                //execute each query
                                $result = $this->updating_model->updateDatabase($query);

                                //error for display
                                $update_debug .= $this->updating_model->debug_data;

                                //did we enounter an error
                                if (!$result) {
                                    log_message('error', __file__ . ' --- ' . __function__ . ' --- ' . __line__ . ' --- [DB UPDATED - sql error(' . $this->sql_last_query_and_error . ')]');
                                }

                                //reset query
                                $query = "";
                            }
                        }
                    }

                    //delete file
                    @unlink($file_path);
                }
            }

            //entire dump of update process
            log_message('error', __file__ . ' --- ' . __function__ . ' --- ' . __line__ . ' --- [DB UPDATED - FULL DEBUG DATA(' . $update_debug . ')]');
        }
    }

    // -- __preRun_RefreshDatabase- -------------------------------------------------------------------------------------------------------
    /**
     * various database refreshing that is run on each page load
     * 
     * @usedby  ALL
     * 
     * @param	void
     * @return void [sets to big data]
     */
    function __preRun_RefreshDatabase($view = '')
    {

        /* REFRESH INVOICES BASIC STATUS
        * This is a light weight refresh of [invoices status] only and is not resources demanidng
        * A more detailed invoice updating routine is run via cron job
        */
        $this->refresh->basicInvoiceStatus();
        $this->data['debug'][] = $this->refresh->debug_data; //library debug

    }

    // -- __preRun_Static_Data- -------------------------------------------------------------------------------------------------------
    /**
     * system wide information set into data arary
     * 
     * @usedby  ALL
     * 
     * @param	void
     * @return void [sets to big data]
     */
    function __preRun_Static_Data()
    {

        /*CONFIG FILE - SET TO DATA ARRAY*/
        $this->data['config'] = $this->config->config;
        /*BASE URL's*/
        $this->data['vars']['site_url'] = site_url(); //main
        $this->data['vars']['site_url_client'] = site_url('/client'); //clients
        $this->data['vars']['site_url_admin'] = site_url('/admin'); //admin
        $this->data['vars']['site_url_api'] = site_url('/api'); //api
        $this->data['vars']['site_url_current_page'] = current_url();
        /*PAYPAL IPN URL*/
        $this->data['vars']['paypal_ipn_url'] = site_url('/api/paypalipn'); //admin

        /*CR0N JOB LINK
        *--------------------------------------------------------------------
        *  url has special key to prevent anyone running this cron urls'
        * key must be changed to make it unique in the settings.php file
        *--------------------------------------------------------------------
        */
        $this->data['vars']['cronjobs_url_general'] = site_url('/admin/cronjobs/general/' . $this->data['config']['security_key']);
        /*ALLOWED FILE TYPES LIST - HUMAN READABLE
        * used mainly to display files types that are allowed in 'info/help' tips for users
        */
        if ($this->config->item('files_allowed_types') === 0) {
            $this->data['vars']['allowed_file_types_human_readable'] = $this->data['lang']['lang_all'];
        } else {
            $this->data['vars']['allowed_file_types_human_readable'] = str_replace('|', ', ', $this->config->item('files_allowed_types'));
        }

    }

    // -- __preRun_User_Agent_Data- -------------------------------------------------------------------------------------------------------
    /**
     * get the users current data - browser, ip address, operating system etc
     * 
     * @usedby  ALL
     * 
     * @param	void
     * @return void [sets to big data]
     */
    function __preRun_User_Agent_Data()
    {

        //get the browser
        if ($this->agent->is_browser()) {
            $browser = $this->agent->browser() . ' ' . $this->agent->version();
        } elseif ($this->agent->is_robot()) {
            $browser = $this->agent->robot();
        } elseif ($this->agent->is_mobile()) {
            $browser = $this->agent->mobile();
        } else {
            $browser = 'Unidentified Browser';
        }

        //set the data
        $this->data['user_agent']['browser'] = $browser;
        $this->data['user_agent']['operating_system'] = str_replace('Unknown ', '', $this->agent->platform());
        $this->data['user_agent']['ip_address'] = $this->input->ip_address();
        $this->data['user_agent']['referrer'] = $this->agent->referrer();
        $this->data['user_agent']['full_user_agent'] = $this->agent->agent_string();

    }

    // -- __preRun_Dynamic_Data- -------------------------------------------------------------------------------------------------------
    /**
     * system wide database stored information set into data arary
     * 
     * @usedby  ALL
     * 
     * @param	void
     * @return void [sets to big data]
     */
    function __preRun_Dynamic_Data()
    {

        /*CURRENT URL - PAGE URL*/
        $this->data['vars']['current_url'] = current_url();

        /*SYSTEM VERSION*/
        $this->data['vars']['application_version'] = $this->data['version']['number'];

        /* ADMIN - THIS TEAM MEMBERS GLOBALLY ACCESSIBLE DATA
        * --only do this if a team member user is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {

            //load users profile data
            $this->member_id = $this->session->userdata('team_profile_id');
            $this->team_member = $this->teamprofile_model->teamMemberDetails($this->member_id);
            //refresh my data
            $this->data['vars']['my_user_type'] = 'team';
            $this->data['vars']['my_id'] = $this->member_id;
            $this->data['vars']['my_unique_id'] = $this->team_member['team_profile_uniqueid'];
            $this->data['vars']['my_name'] = $this->team_member['team_profile_full_name'];
            $this->data['vars']['my_email'] = $this->team_member['team_profile_email'];
            $this->data['vars']['my_group'] = $this->team_member['team_profile_groups_id'];
            $this->data['vars']['my_group_name'] = $this->session->userdata('groups_name');
            $this->data['vars']['my_avatar'] = $this->team_member['team_profile_avatar_filename'];
            $this->data['vars']['my_pinned_project_1'] = $this->team_member['team_profile_pinned_1'];
            $this->data['vars']['my_pinned_project_2'] = $this->team_member['team_profile_pinned_2'];
            $this->data['vars']['my_pinned_project_3'] = $this->team_member['team_profile_pinned_3'];
            $this->data['vars']['my_pinned_project_4'] = $this->team_member['team_profile_pinned_4'];
        }

        /*EVENTS RANDOM CODE*/
        $this->data['vars']['new_events_id'] = random_string('alnum', 40);

        /*GENERAl SETTINGS*/
        $this->data['settings_general'] = $this->settings_general_model->getSettings();
        $this->data['debug'][] = $this->settings_general_model->debug_data;

        $this->data['reg_fields'][] = 'settings_general';
        $this->data['fields']['settings_general'] = $this->data['settings_general'];

        /*TODAYS FRIENDLY DATE*/
        $this->data['vars']['todays_date'] = $this->__format_date(date('Y-m-d')); //8 June 2014

        /*GENERAl COMPANY*/
        $this->data['settings_company'] = $this->settings_company_model->getSettings();

        $this->data['reg_fields'][] = 'settings_company';
        $this->data['fields']['settings_company'] = $this->data['settings_company'];
        /*INVOICE SETTINGS*/
        $this->data['settings_invoices'] = $this->settings_invoices_model->getSettings();

        $this->data['reg_fields'][] = 'settings_invoices';
        $this->data['fields']['settings_invoices'] = $this->data['settings_invoices']; //set to data->fields array

        /*COMPANY DETAILS*/
        $this->data['settings_company'] = $this->settings_company_model->getSettings();

        $this->data['reg_fields'][] = 'settings_company';
        $this->data['fields']['settings_company'] = $this->data['settings_company']; //set to data->fields array


        $this->data['reg_fields'][] = 'settings_clientform';
        $this->data['fields']['settings_clientform'] = $this->settings_clientform_model->getSettings(); //set to data->fields array


        /* MAILING LISTS
        * lists (arrays) of vairous email addresses used to send email
        * typically used to send out notifications and system emails
        */
        /*ADMIN - MAILING LIST OF ADMINS EMAILS
        * this is a list of all the emails for users in admin groupd
        * normally used to send out system notifcations
        */
        $result = $this->teamprofile_model->mailingListAdmin();

        for ($i = 0; $i < count($result); $i++) {
            $this->data['vars']['mailinglist_admins'][] = $result[$i]['team_profile_email'];
            $this->mailinglist_admins[] = $result[$i]['team_profile_email'];
        }

        /*ADMIN - MAILING LIST OF ADMINS EMAILS & NAME (FULL)
        * this is a list of all the emails for users in admin groupd
        * normally used to send out system notifcations
        */
        $result = $this->teamprofile_model->mailingListAdmin();

        for ($i = 0; $i < count($result); $i++) {
            $this->data['vars']['mailinglist_admins_full'][$i]['name'] = $result[$i]['team_profile_full_name'];
            $this->data['vars']['mailinglist_admins_full'][$i]['email'] = $result[$i]['team_profile_email'];
        }

        /* ADMIN - MY PROJECTS ARRAY & LIST
        * a comma separated list of all MY projects ID's (logged in team)
        * also a standard array of the project ID's
        * --only do this if a team members is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $projects = $this->project_members_model->allMembersProjects($this->session->userdata('team_profile_id'));
            //set arrays
            $this->data['my_leaders_projects_array'] = array();
            $this->data['my_active_projects_array'] = array();
            $this->data['my_projects_array'] = array();

            //declare
            $this->data['vars']['my_leaders_projects_list'] = '';
            $this->data['vars']['my_active_projects_list'] = '';
            $this->data['vars']['my_projects_list'] = '';
            $this->data['vars']['my_completed_projects_list'] = '';

            //loop through and create list of project id's & also normal array
            for ($i = 0; $i < count($projects); $i++) {

                /** all projects that I am leader */
                if ($projects[$i]['project_members_project_lead'] == 'yes') {
                    //comma list
                    $this->data['vars']['my_leaders_projects_list'] .= $projects[$i]['project_members_project_id'] . ','; //normal array
                    $this->data['my_leaders_projects_array'][] = $projects[$i]['project_members_project_id'];
                }

                /** all projects that are active */
                if ($projects[$i]['projects_status'] != 'completed') {
                    //comma list
                    $this->data['vars']['my_active_projects_list'] .= $projects[$i]['project_members_project_id'] . ','; //normal array
                    $this->data['my_active_projects_array'][] = $projects[$i]['project_members_project_id'];
                }

                /** all projects that are behind */
                if ($projects[$i]['projects_status'] == 'behind schedule') {
                    //comma list
                    $this->data['vars']['my_active_projects_list'] .= $projects[$i]['project_members_project_id'] . ','; //normal array
                    $this->data['my_behind_projects_array'][] = $projects[$i]['project_members_project_id'];
                }

                /** all projects that are currently on time */
                if ($projects[$i]['projects_status'] == 'in progress') {
                    //comma list
                    $this->data['vars']['my_active_projects_list'] .= $projects[$i]['project_members_project_id'] . ','; //normal array
                    $this->data['my_inprogress_projects_array'][] = $projects[$i]['project_members_project_id'];
                }

                /** all projects that are completed */
                if ($projects[$i]['projects_status'] == 'completed') {
                    //comma list
                    $this->data['vars']['my_completed_projects_list'] .= $projects[$i]['project_members_project_id'] . ','; //normal array
                    $this->data['my_completed_projects_list'][] = $projects[$i]['project_members_project_id'];
                }

                /** all projects */
                //comma list
                $this->data['vars']['my_projects_list'] .= $projects[$i]['project_members_project_id'] . ','; //normal array
                $this->data['my_projects_array'][] = $projects[$i]['project_members_project_id'];
                $this->data['vars']['my_projects_array'][] = $projects[$i]['project_members_project_id'];
            }

            //trim trailing comma ,
            $this->data['vars']['my_projects_list'] = rtrim($this->data['vars']['my_projects_list'], ',');
        }

        /* ADMIN - 'MY' PROJECTS COUNT
        * [my_projects_count.in_progress]
        * [my_projects_count.completed]
        * [my_projects_count.behind_schedule]
        * [my_projects_count.all_open]
        * [my_projects_count.all_projects]
        * 
        * --only do this if a team members is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $this->data['reg_fields'][] = 'my_projects_count';
            $this->data['fields']['my_projects_count']['in_progress'] = isset($this->data['my_inprogress_projects_array']) ? count($this->data['my_inprogress_projects_array']) : 0;
            $this->data['fields']['my_projects_count']['completed'] = isset($this->data['my_completed_projects_list']) ? count($this->data['my_completed_projects_list']) : 0;
            $this->data['fields']['my_projects_count']['behind_schedule'] = isset($this->data['my_behind_projects_array']) ? count($this->data['my_behind_projects_array']) : 0;
            $this->data['fields']['my_projects_count']['all_open'] = isset($this->data['my_active_projects_array']) ? count($this->data['my_active_projects_array']) : 0;
            $this->data['fields']['my_projects_count']['all_projects'] = isset($this->data['my_projects_array']) ? count($this->data['my_projects_array']) : 0;

        }

        /* ADMIN - 'ALL' PROJECTS COUNT
        * [projects_count.in_progress]
        * [projects_count.completed]
        * [projects_count.behind_schedule]
        * [projects_count.all_open]
        * [projects_count.all_projects]
        * 
        * --only do this if a team members is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $this->data['reg_fields'][] = 'projects_count';
            $this->data['fields']['projects_count'] = $this->projects_model->allProjectsCounts();

        }

        /* ADMIN - TICKETS COUNTS
        * [tickets_count.new]
        * [tickets_count.closed]
        * [tickets_count.client_replied]
        * [tickets_count.answered]
        * [tickets_count.all_open]
        * [tickets_count.all_tickets]
        * --only do this if a team members is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $this->data['reg_fields'][] = 'tickets_count';
            $this->data['fields']['tickets_count'] = $this->tickets_model->allTicketCounts();

        }

        /* ADMIN - BUGS COUNTS
        * [bugs_count.new]
        * [bugs_count.resolved]
        * [bugs_count.in_progress]
        * [bugs_count.not_a_bug]
        * [bugs_count.all_open]
        * [bugs_count.all_bugs]
        * --only do this if a team members is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $this->data['reg_fields'][] = 'bugs_count';
            $this->data['fields']['bugs_count'] = $this->bugs_model->allBugsCounts();

        }

        /* ADMIN -  QUOTATIONS COUNTS
        * [quotations_count.new]
        * [quotations_count.completed]
        * [quotations_count.pending]
        * [quotations_count.all_open]        
        * [quotations_count.all_quotations]
        * --only do this if a team members is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $this->data['reg_fields'][] = 'quotations_count';
            $this->data['fields']['quotations_count'] = $this->quotations_model->allQuotationsCounts();

        }

        /*ADMIN - QUOTATION FORMS COUNT
        * --only do this if a team members is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $this->data['vars']['quotation_forms_count'] = $this->quotationforms_model->countForms();

        }

        /*ADMIN - QUOTATION FORMS COUNT
        * --only do this if a team members is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $this->data['vars']['clients_count'] = $this->clients_model->countClients();

        }

        /* ADMIN -  MY TASKS COUNT
        * [my_tasks_count.pending]
        * [my_tasks_count.completed]
        * [my_tasks_count.behing_schedule]
        * [my_tasks_count.all_open]
        * [my_tasks_count.all_tasks]
        * --only do this if a team members is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $this->data['reg_fields'][] = 'my_tasks_count';
            $this->data['fields']['my_tasks_count'] = $this->tasks_model->allMyTasksCounts($this->session->userdata('team_profile_id'));

        }

        /* ADMIN - MY PROJECTS COUNT
        * --only do this if a team members is logged in---
        */
        if (is_numeric($this->session->userdata('team_profile_id'))) {
            $this->data['vars']['count_my_projects'] = $this->project_members_model->countMyProjects($this->session->userdata('team_profile_id'));

        }

        /* CLIENT - THIS CLIENTS GLOBALLY ACCESSIBLE DATA
        * --only do this if a client user is logged in---
        */
        if (is_numeric($this->session->userdata('client_users_id'))) {

            //set acclient id
            $this->client_id = $this->session->userdata('client_users_clients_id'); //load clients profile data
            $this->client = $this->clients_model->clientDetails($this->client_id);

            //load users profile data
            $this->user_id = $this->session->userdata('client_users_id');
            $this->client_user = $this->users_model->userDetails($this->user_id);


            //is my account still active
            if (!$this->client_user || !$this->client) {
                //delete all session data
                $this->session->sess_destroy();
            }

            //refresh my data
            $this->data['vars']['my_id'] = $this->user_id;
            $this->data['vars']['my_unique_id'] = $this->client_user['client_users_uniqueid'];
            $this->data['vars']['my_name'] = $this->client_user['client_users_full_name'];
            $this->data['vars']['my_user_type'] = 'client';
            $this->data['vars']['my_primary_contact'] = $this->client_user['client_users_main_contact'];
            $this->data['vars']['my_email'] = $this->client_user['client_users_email'];
            $this->data['vars']['my_avatar'] = $this->client_user['client_users_avatar_filename'];
            $this->data['vars']['my_telephone'] = $this->client_user['client_users_telephone'];
            $this->data['vars']['my_client_id'] = $this->client_id;
            $this->data['vars']['my_company_name'] = $this->client['clients_company_name'];
            $this->data['vars']['my_company_address'] = $this->client['clients_address'];
            $this->data['vars']['my_company_city'] = $this->client['clients_city'];
            $this->data['vars']['my_company_state'] = $this->client['clients_state'];
            $this->data['vars']['my_company_zipcode'] = $this->client['clients_zipcode'];
            $this->data['vars']['my_website'] = $this->client['clients_website'];
            $this->data['vars']['my_optionalfield1'] = $this->client['clients_optionalfield1'];
            $this->data['vars']['my_optionalfield2'] = $this->client['clients_optionalfield2'];
            $this->data['vars']['my_optionalfield3'] = $this->client['clients_optionalfield3'];
            $this->data['vars']['my_unique_code'] = $this->client['client_unique_code'];
        }

        /* CLIENT - PROJECTS COUNT
        * [client_projects_count.in_progress]
        * [client_projects_count.completed]
        * [client_projects_count.behind_schedule]
        * [client_projects_count.all_open]
        * [client_projects_count.all_projects]
        * 
        * --only do this if a client user is logged in---
        */
        if (is_numeric($this->session->userdata('client_users_id'))) {
            $this->data['reg_fields'][] = 'client_projects_count';
            $this->data['fields']['client_projects_count'] = $this->projects_model->allProjectsCounts($this->session->userdata('client_users_clients_id'));

        }

        /* CLIENT - TICKETS COUNTS
        * [client_tickets_count.new]
        * [client_tickets_count.closed]
        * [client_tickets_count.client_replied]
        * [client_tickets_count.answered]
        * [client_tickets_count.all_open]
        * [client_tickets_count.all_tickets]
        * --only do this if a client user is logged in---
        */
        if (is_numeric($this->session->userdata('client_users_id'))) {
            $this->data['reg_fields'][] = 'client_tickets_count';
            $this->data['fields']['client_tickets_count'] = $this->tickets_model->allTicketCounts($this->session->userdata('client_users_clients_id'));

        }

        /* CLIENT - BUGS COUNTS
        * [client_bugs_count.new]
        * [client_bugs_count.resolved]
        * [client_bugs_count.in_progress]
        * [client_bugs_count.not_a_bug]
        * [client_bugs_count.all_open]
        * [client_bugs_count.all_bugs]
        * --only do this if a client user is logged in---
        */
        if (is_numeric($this->session->userdata('client_users_id'))) {
            $this->data['reg_fields'][] = 'client_bugs_count';
            $this->data['fields']['client_bugs_count'] = $this->bugs_model->allBugsCounts($this->session->userdata('client_users_clients_id'));

        }

        /* CLIENT - QUOTATIONS COUNTS
        * [client_quotations_count.all_quotations]
        * [client_quotations_count.new]
        * [client_quotations_count.completed]
        * [client_quotations_count.pending]
        * [client_quotations_count.all_open]
        * --only do this if a client user is logged in---
        */
        if (is_numeric($this->session->userdata('client_users_id'))) {
            $this->data['reg_fields'][] = 'client_quotations_count';
            $this->data['fields']['client_quotations_count'] = $this->quotations_model->allQuotationsCounts($this->session->userdata('client_users_clients_id'));

        }

        /* CLIENT - USERS COUNTS
        * --only do this if a client user is logged in---
        */
        if (is_numeric($this->session->userdata('client_users_id'))) {
            $this->data['vars']['client_users_count'] = $this->users_model->allUsersCounts($this->session->userdata('client_users_clients_id'));

        }

        /* CLIENT - COMMA SEPERATED LIST OF CLIENTS PROJECTS
        *  create a list that can be used in sql query e.g. (WHERE project_events_project_id IN (2,4,5,9))
        * --only do this if a client user is logged in---
        */
        if (is_numeric($this->session->userdata('client_users_id'))) {
            $projects = $this->projects_model->allProjects('projects_id', 'DESC', $this->session->userdata('client_users_clients_id', 'all'));

            //do we have any projects
            if (@count($projects) <= 0 || !is_array($projects)) {
                //show no events message
                $this->data['visible']['no_timeline_events'] = 1; //halt
                $next = false;
            } else {

                //loop through and create list of project id's & also normal array
                $this->data['vars']['my_clients_project_list'] = '';
                for ($i = 0; $i < count($projects); $i++) {
                    //comma list
                    $this->data['vars']['my_clients_project_list'] .= $projects[$i]['projects_id'] . ',';
                    //normal array
                    $this->data['my_clients_project_array'][] = $projects[$i]['projects_id'];
                }

                //trim trailing comma ,
                $this->data['vars']['my_clients_project_list'] = rtrim($this->data['vars']['my_clients_project_list'], ',');
            }
        }

    }

    // -- __preRun_Language- -------------------------------------------------------------------------------------------------------
    /**
     * validate language file set in settings actually exists. 
     * If not, halt system with an error message
     * create an array of all languages that are available
     * create pulldown list of languages available
     * 
     * @usedby  ALL
     * 
     * @param	void
     * @return void [sets to big data] 
     *               - $this->data['languages_available']
     *               - $this->data['lists']['all_languages']
     */
    function __preRun_Language()
    {

        //currently specified language
        $current_language = $this->data['fields']['settings_general']['language'];
        //get content of language folder
        $language_folder = directory_map(PATHS_LANGUAGE_FOLDER, false, false);
        //check if the file 'defauls_lang.php' exists in each folder that is found
        $this->data['lists']['all_languages'] = ''; //declare
        foreach ($language_folder as $key => $value) {
            if (is_array($language_folder[$key])) {
                if (in_array('default_lang.php', $language_folder[$key])) {

                    //it exists, add it to languages array
                    $this->data['languages_available'][] = $key;
                    //add it to language pull down list
                    $this->data['lists']['all_languages'] .= '<option value="' . $key . '">' . ucfirst($key) . '</option>';
                }
            }
        }

        // check if language that is set in settings_general, physically exists
        if (!in_array($current_language, $this->data['languages_available'])) {

            //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\
            $message = '<b>File: </b>' . __file__ . '<br/>
                        <b>Function: </b>' . __function__ . '<br/>
                        <b>Line: </b>' . __line__ . '<br/>
                        <b>Notes: </b> Specified language file could not be found (' . $current_language . ')'; //display error
            show_error($message, 500); //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\

        }

        //everything is ok,load language into array
        $this->data['lang'] = $this->lang->load('default', $current_language, true);

        //--------------UTF-8 Encode the kanguage file ------------------
        function utf8encode(&$item, $key)
        {
            $item = htmlspecialchars(utf8_encode($item));
        }
        //only do this if language_mode is set to "2" in settings.php
        if ($this->config->item('language_mode') == 2) {
            array_walk_recursive($this->data['lang'], 'utf8encode');
        }
        //--------------UTF-8 Encode the language file end----------------

    }

    // -- __preRun_Theme- -------------------------------------------------------------------------------------------------------
    /**
     * validate theme that in settings actually exists. If not, halt system with an error message
     * create an array of all themes that are available
     * create pulldown list of themes available
     * 
     * @usedby  ALL
     * 
     * @param	void
     * @return void [sets to big data] 
     *               - $this->data['themes_available']
     *               - $this->data['lists']['all_themes']
     */
    function __preRun_Theme()
    {

        //currently specified theme
        $current_theme = $this->data['fields']['settings_general']['theme']; //get content of language folder
        $themes_folder = directory_map(PATHS_APPLICATION_FOLDER . 'themes', false, false);
        /* get each 'folder' name (only folders in first level)
        *  - at this first stage, we only check the admin themes
        *  - assume its a valid theme
        *  - add it to array and pulldown
        */
        $this->data['lists']['all_themes'] = '';
        foreach ($themes_folder as $key => $value) {

            //add folder name to theme array
            $this->data['themes_available'][] = $key; //add folder name to pull down list
            $this->data['lists']['all_themes'] .= '<option value="' . $key . '">' . ucfirst($key) . '</option>';
        }

        // check if theme that is currently set in settings_general, physically exists
        if (!in_array($current_theme, $this->data['themes_available'])) {

            //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\
            $message = '<b>File: </b>' . __file__ . '<br/>
                        <b>Function: </b>' . __function__ . '<br/>
                        <b>Line: </b>' . __line__ . '<br/>
                        <b>Notes: </b> Specified theme could not be found (' . $current_theme . ')'; //display error
            show_error($message, 500); //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\

        }

        /* now save the current theme in a constant for global use
        *  also set the client theme to same name
        */
        define('PATHS_ADMIN_THEME', FCPATH . "application/themes/$current_theme/admin/");
        define('PATHS_CLIENT_THEME', FCPATH . "application/themes/$current_theme/client/");
        define('PATHS_COMMON_THEME', FCPATH . "application/themes/$current_theme/common/"); //check if client theme/folder also exists
        if (!is_dir(PATHS_CLIENT_THEME)) {

            //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\
            $message = '<b>File: </b>' . __file__ . '<br/>
                        <b>Function: </b>' . __function__ . '<br/>
                        <b>Line: </b>' . __line__ . '<br/>
                        <b>Notes: </b> Specified [client] theme could not be found (' . $current_theme . ')'; //display error
            show_error($message, 500); //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\

        }
    }

    // -- __preRun_Libraries- -------------------------------------------------------------------------------------------------------
    /**
     * any libraries that need to be loaded in any special way
     * 
     * @usedby  ALL
     * 
     * @param	void
     * @return void
     */
    function __preRun_Libraries()
    {

        /*FORM PROCESSOR */
        //load form processor with default language
        $this->load->library("Form_processor", $this->data['lang']);
    }

    // -- __preRun_Dir_Cleanup- -------------------------------------------------------------------------------------------------------
    /**
     * clean up the 
     * 
     * @usedby  ALL
     * 
     * @param	void
     * @return void
     */
    function __preRun_Dir_Cleanup()
    {

        /*FORM PROCESSOR */
        //load form processor with default language
        $this->load->library("Form_processor", $this->data['lang']);
    }


    // -- __emailLog- -------------------------------------------------------------------------------------------------------
    /**
     * saves a copy of every email that is sent to a log database
     * can be used to review emails that were sent or for debugging email problems
     * @param string $to_type (client/admin)
     * @param string $email
     * @param string $subject
     * @param string $body
     * @return void
     */
    function __emailLog($email = '', $subject = '', $body = '')
    {
        //send email
        $this->email_log_model->addToLog($email, $subject, $body);

    }

    // -- __emailtagsProjectData- -------------------------------------------------------------------------------------------------------
    /**
     * [NEXTLOOP - freelance dashboard compatible]
     * get all basic information for a project and use keys that correspond to the tags used in mist emails
     * [var.clients_name], [var.project_title], [var.project_id], [var.project_start_date], [var.project_start_end]
     * @param numeric $id project id
     * @return void
     */
    function __emailtagsProjectData($id = '')
    {
        //flow
        $next = true;

        if (!is_numeric($id)) {
            profiling(__function__, __line__, "invalid project id");
            return false;
        }

        //get project details
        if ($next) {
            profiling(__function__, __line__, "getting project details");
            $project = $this->projects_model->projectDetails($id);

            if (!$project) {
                return false;
            }
        }

        //get client data
        if ($next) {
            profiling(__function__, __line__, "getting client data");
            if (!$client = $this->clients_model->clientDetails($project['projects_clients_id'])) {
                //halt
                $next = false;
            }
            $this->debug_data .= $this->ci->clients_model->debug_data;
        }

        //get main user details
        if ($next) {
            profiling(__function__, __line__, "getting main client users details");
            $user = $this->users_model->clientPrimaryUser($project['projects_clients_id']);

            if (!$user) {
                return false;
            }
        }

        //save in an array with same keys as used in email tags/vars
        if ($next) {
            profiling(__function__, __line__, "adding details to array");
            $vars['clients_id'] = $client['client_users_clients_id'];
            $vars['clients_company_name'] = $client['clients_company_name'];
            $vars['clients_name'] = $user['client_users_full_name'];
            $vars['clients_email'] = $user['client_users_email'];
            $vars['project_title'] = $project['projects_title'];
            $vars['project_id'] = $project['projects_id'];
            $vars['project_start_date'] = $this->__format_date($project['projects_date_created']);
            $vars['project_deadline'] = $this->__format_date($project['project_deadline']);
            $vars['project_status'] = $this->__dynamicLang("lang_" . $project['projects_status']);

            //add to data for debug
            $this->data['email_project_tags'] = $vars;

            //return
            return $vars;
        }

    }

    // -- __emailtagsInvoiceData- -------------------------------------------------------------------------------------------------------
    /**
     * [NEXTLOOP - freelance dashboard compatible]
     * get all basic information for the invoice and use keys that correspond to the tags used in emails
     * [var.clients_name], [var.invoice_total_amount], [var.invoice_amount_due], [var.invoice_date_created], [var.invoice_date_due]
     * @param numeric $id invoice id
     * @return void
     */
    function __emailtagsInvoiceData($id = '')
    {

        profiling(__function__, __line__, "starting: email invoice tags data");
        //flow
        $next = true;

        if (!is_numeric($id)) {
            profiling(__function__, __line__, "invalid invocie id");
            return false;
        }

        ///get invoice details
        if ($next) {
            profiling(__function__, __line__, "getting invoice details");
            if (!$invoice = $this->invoices_model->getInvoice($id)) {
                //halt
                $next = false;
            }
            $this->debug_data .= $this->ci->invoices_model->debug_data;
        }


        //get invoice payment details
        if ($next) {
            profiling(__function__, __line__, "getting invoice payment details");
            $payments_sum = $this->payments_model->sumInvoicePayments($id);
            $this->debug_data .= $this->ci->payments_model->debug_data;
            $balance_due = $invoice['invoices_amount'] - $payments_sum;
        }

        //get main user details
        if ($next) {
            profiling(__function__, __line__, "getting main client users details");
            $user = $this->users_model->clientPrimaryUser($invoice['invoices_clients_id']);

            if (!$user) {
                return false;
            }
        }

        //setting email invoice vars
        if ($next) {
            profiling(__function__, __line__, "setting email invoice vars");
            $vars['invoice_id'] = $invoice['invoices_id'];
            $vars['invoice_custom_id'] = $invoice['invoices_custom_id'];
            $vars['invoice_project_id'] = $invoice['invoices_project_id'];
            $vars['invoice_total_amount'] = $this->__format_number_decimal($invoice['invoices_amount']);
            $vars['invoice_previous_payments'] = $this->__format_number_decimal($payments_sum);
            $vars['invoice_amount_due'] = $this->__format_number_decimal($balance_due);
            $vars['invoice_date_created'] = $this->__format_date($invoice['invoices_date']);
            $vars['invoice_date_due'] = $this->__format_date($invoice['invoices_due_date']);
            $vars['invoice_status'] = $this->__dynamicLang("lang_" . $invoice['invoices_status']); //e.g lang_overdue
            $vars['invoice_terms'] = $this->data['settings_invoices']['settings_invoices_notes'];
            $vars['clients_name'] = $user['client_users_full_name'];
            $vars['clients_email'] = $user['client_users_email'];
            $vars['clients_id'] = $invoice['invoices_clients_id'];
        }

        //add to data for debug
        $this->data['email_invoice_tags'] = $vars;

        //return the data
        return $vars;
    }


    // -- __emailtagsFileData- -------------------------------------------------------------------------------------------------------
    /**
     * [NEXTLOOP - freelance dashboard compatible]
     * get all basic information about a file and use keys that correspond to the tags used in emails
     * [var.clients_name], [var.file_name], [var.file_id], [var.file_date_uploaded], [var.file_uploaded_by], [var.file_description], [var.file_size]
     * @param numeric $id file id
     * @return void
     */
    function __emailtagsFileData($id = '')
    {

        profiling(__function__, __line__, "starting: email file tags data");
        //flow
        $next = true;

        if (!is_numeric($id)) {
            profiling(__function__, __line__, "invalid file id");
            return false;
        }

        ///get file data
        if ($next) {
            profiling(__function__, __line__, "getting file data");
            if (!$file = $this->files_model->getFile($id)) {
                //halt
                $next = false;
            }
            $this->debug_data .= $this->ci->files_model->debug_data;
        }

        //get main user details
        if ($next) {
            profiling(__function__, __line__, "getting main client users details");
            $user = $this->users_model->clientPrimaryUser($file['files_client_id']);

            if (!$user) {
                return false;
            }
        }


        //get uploaded by data (team)
        if ($next && $file['files_uploaded_by'] == 'team') {
            profiling(__function__, __line__, "getting uploader details (team)");
            $uploader = $this->teamprofile_model->getMembersName($file['files_uploaded_by_id']);

        }


        //get uploaded by data (client)
        if ($next && $file['files_uploaded_by'] == 'client') {
            profiling(__function__, __line__, "getting uploader details (client)");
            $uploader = $this->users_model->getUsersName($file['files_uploaded_by_id']);

        }


        //setting file vars
        if ($next) {
            profiling(__function__, __line__, "setting email invoice vars");
            $vars['file_id'] = $file['files_id'];
            $vars['file_name'] = $file['files_name'];
            $vars['file_project_id'] = $file['files_project_id'];
            $vars['file_uploaded_by'] = $uploader;
            $vars['file_description'] = $file['files_description'];
            $vars['file_date_uploaded'] = $this->__format_date($file['files_date_uploaded']);
            $vars['file_size'] = $file['files_size_human'];
            $vars['clients_name'] = $user['client_users_full_name'];
            $vars['clients_email'] = $user['client_users_email'];
            $vars['clients_id'] = $user['invoices_clients_id'];

            //add to data for debug
            $this->data['email_file_tags'] = $vars;

            //return
            return $vars;
        }
    }


    // -- __emailtagsClientData- -------------------------------------------------------------------------------------------------------
    /**
     * get all basic information about a client and use keys that correspond to the tags used in emails
     * @param numeric $id file id
     * @return void
     */
    function __emailtagsClientData($id = '')
    {

        profiling(__function__, __line__, "starting: email client tags data");
        //flow
        $next = true;

        if (!is_numeric($id)) {
            profiling(__function__, __line__, "invalid client id");
            return false;
        }

        //get client data
        if ($next) {
            profiling(__function__, __line__, "getting client data");
            if (!$client = $this->clients_model->clientDetails($id)) {
                //halt
                $next = false;
            }
            $this->debug_data .= $this->ci->clients_model->debug_data;
        }

        //get main user details
        if ($next) {
            profiling(__function__, __line__, "getting main client users details");
            $user = $this->users_model->clientPrimaryUser($client['clients_id']);

            if (!$user) {
                return false;
            }
        }

        //setting file vars
        if ($next) {
            profiling(__function__, __line__, "setting email client vars");
            $vars['clients_company_name'] = $client['clients_company_name'];
            $vars['clients_id'] = $client['clients_id'];
            $vars['clients_name'] = $user['client_users_full_name'];
            $vars['clients_email'] = $user['client_users_email'];

            //add to data for debug
            $this->data['email_client_tags'] = $vars;

            //return
            return $vars;
        }
    }


    // -- __dynamicLang- -------------------------------------------------------------------------------------------------------
    /**
     * replace a string with lang with key that matches the string/text. Used mainly to relace database strings like 'in-progress'
     * @return date
     */
    function __dynamicLang($text)
    {
        //make lowercase
        $text = strtolower($text);

        //check blanks
        if ($text == '') {
            return;
        }

        //replace spaces with underscore
        $text = str_replace(' ', '_', $text);

        //find in language array
        if (array_key_exists($text, $this->data['lang'])) {
            $text = $this->data['lang'][$text];
            return $text;
        } else {
            //remove any dashes and return back string
            $text = str_replace('_', ' ', $text);
            //remove the word lang, incase it was there in original text
            $text = trim(str_replace('lang', '', $text));
            return $text;
        }
    }

    // -- format_date- -------------------------------------------------------------------------------------------------------
    /**
     * format a date
     * @return date
     */
    function __format_date($thedate)
    {

        $dateformat = $this->data['settings_general']['date_format'];

        //validate date format
        $dateformat = ($dateformat == '') ? 'm-d-Y' : $dateformat; //default

        //return formatted date
        $thedate = date($dateformat, strtotime($thedate));

        if ($thedate == '01-01-1970' || $thedate == '') {
            $thedate = '---';
        }

        return $thedate;
    }

    // -- __format_number_decimal- -------------------------------------------------------------------------------------------------------
    /**
     * Formats a number e.g. 1,000.00
     */
    function __format_number_decimal($number)
    {
        if (is_numeric($number)) {
            $number = number_format($number, 2, '.', ',');
        } else {
            $number = '0.00';
        }

        return $number;
    }


    // -- __easyRedirect- -------------------------------------------------------------------------------------------------------
    /**
     * simple to redirect when post action has completed
     * EXAMPLE 
     * http://domain.com/admin/file/12/add-message/16
     * http://domain.com/admin/file/12view/16
     * 
     * @param string $from the string to replace (e.g. 'add-message')
     * @param string $to the string to replace with (e.g. 'view')
     */
    function __easyRedirect($from = '', $to = '')
    {

        $this_url = uri_string();
        $redirect = str_replace($from, $to, $this_url);
        redirect($redirect);
    }


    // -- __permissionsCheckTask- -------------------------------------------------------------------------------------------------------
    /**
     * check if current team member/client (i.e. 'me'), has permission to [EDIT/DELETE] a task 
     * only the following users can carry out this action
     * [TEAM]
     *  (1) owner of the task
     *  (2) global admin
     *  (3) project leader
     *  (4) user must also have general permission for such an action
     * [CLIENT]
     *  (1) client who created a task
     *  (2) a task not created by client, but with client acccess enabled
     * 
     * @param numeric $task_id
     * @param string $action (edit/delete/view)
     */
    function __permissionsCheckTask($task_id = '', $action = '', $user_type = '')
    {
        //validation
        if (!is_numeric($task_id) || !in_array($action, array(
            'edit',
            'delete',
            'view')) || !in_array($user_type, array('client', 'team'))) {
            return false;
        }

        //get the task
        if (!$task = $this->tasks_model->getTask($task_id)) {
            return false;
        }


        /* ---------------------------------------------------------------------------------------------------
        * team member permissions
        * ---------------------------------------------------------------------------------------------------*/
        if ($user_type == 'team') {

            //check if team member has permission on this task
            $superusers = $this->tasks_model->superUsers($task_id);
            if (in_array($this->data['vars']['my_id'], $superusers) || $this->data['vars']['my_group'] == 1) {
                return true;
            } else {
                return false;
            }

            //check global permissions - delete
            if ($action == 'delete' && $this->data['permission']['delete_item_my_project_my_tasks'] != 1) {
                return false;
            }

            //check global permissions - edit
            if ($action == 'edit' && $this->data['permission']['edit_item_my_project_my_tasks'] != 1) {
                return false;
            }

            //check global permissions - edit
            if ($action == 'view' && $this->data['permission']['view_item_my_project_my_tasks'] != 1) {
                return false;
            }

        }

        /* ---------------------------------------------------------------------------------------------------
        * client permissions
        * ---------------------------------------------------------------------------------------------------*/
        if ($user_type == 'client') {

            //is this in clients projects
            if (in_array($task['tasks_project_id'], $this->data['my_clients_project_array'])) {
                return false;
            }


            //check permissions - delete
            if (in_array($action, array(
                'delete',
                'edit',
                'view')) && $task['tasks_created_by'] != 'client') {
                return false;
            }
        }
    }

}

/* End of file My_Controller.php */
/* Location: ./application/core/My_Controller.php */
