<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Kassekredit extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'settings.kassekredit.html';
		
		if($this->session->userdata['current_staff_employee_type'] != 'admin'){
			redirect('/admin/error/permission-denied');
			exit();
		}
		
		if(!isset($this->session->userdata['start_current_staff']))
		{
			$this->session->set_flashdata('notice-error', $this->data['lang']['lang_session_timed_out']);
			redirect('/admin/');
			exit();
		}
		
		if($this->session->userdata['start_current_staff'] != $this->session->userdata['current_staff'])
		{
			$this->session->set_flashdata('notice-error', $this->data['lang']['lang_please_login']);
			redirect('/admin/');
			exit();
		}
		

        //css settings
        $this->data['vars']['css_menu_topnav_settings'] = 'nav_alternative_controls_active'; //menu
        $this->data['vars']['css_menu_heading_settings'] = 'heading-menu-active'; //menu
        $this->data['vars']['css_menu_settings'] = 'open'; //menu
        
        //PERMISSIONS CHECK - GENERAL
        //Administrator only
        /*if ($this->data['vars']['my_group'] != 1) {
            redirect('/admin/error/permission-denied');
        }*/
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
        $this->data['vars']['main_title'] = $this->data['lang']['lang_settings_company'];
		
		//css - active tab
        $this->data['vars']['css_add_saldo'] = 'selected';


        //re-route to correct method
        switch ($action) {
			
			case 'add-saldo':
			     $this->__addSaldo(); 
				 break;
			
			case 'list':
				 $this->data['vars']['css_add_saldo'] = '';
			     $this->__listSaldo(); 
				 break;
				 
            case 'search-complaint-list-log':
                $this->__cachedComplaintListLog();
                break;
				 
			
            default:
                $this->__viewpage();
        }

        //css - active tab
        $this->data['vars']['css_menu_kassekredit'] = 'current';

        //load view
        $this->__flmView('admin/main');

    }
	
    /**
     * load general settings
     */
    function __viewpage()
    {
	}

	// -- add saldo ----------------------------------------------------------------------------------------------
    /**
     * add a saldo by admin for Reklamajon
     *
     *
     * @param array $thedata normally the $_post array
     * @return array
     */
	 
	function __addsaldo()
	{
	
		$type='in';
		$amount=$_POST['amount'];
		$customer=$_POST['customer'];
		$complaint_reason=$_POST['reason'];
		$in_type='complaint';
		$in_status='paid';
	
		$regtime=date('Y-m-d H:i:s');
		$paymentarray=array(
		'type'=>$type,
		'in_type'=>$in_type,
		'in_status'=>$in_status,
		'customer'=>$customer,
		'amount'=>$amount,
		'regtime'=>$regtime,
		'complaint_reason'=>$complaint_reason
		);
		
		
		
		$saldoid=$this->payments_model->addCustomerPayment($paymentarray);
		//$saldo_status = $this->payments_model->getSaldostatus($customer); //check is the customer having saldo 
		$result = $this->payments_model->updateCustomerBalance($customer,$amount,$in_status,'credit');
		
		$result = array("status"=>'success','saldoid'=>$saldoid,"amount"=>$amount,'message'=>
'Customer payment has been credited','saldocolor'=>$saldocolor);
		echo json_encode($result);exit;
	}
	
	

	  /**
      *  list complaints added in amonth
      */
     function __listSaldo()
	 {
        //profiling
        $this->data['controller_profiling'][] = __function__;

		//css - active tab
        $this->data['vars']['css_sald_list'] = 'selected';
		 
        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'settings.kassekredit.list.html';

		
        //uri segments
        $search_id = (is_numeric($this->uri->segment(5))) ? $this->uri->segment(5) : 0;
        //load the original posted search into $_get array
        $this->input->load_query($search_id);
		
		
		$month =  ($this->input->get('month')!='') ?  $this->input->get('month') : date('m');
		$year =  ($this->input->get('year')!='') ?  $this->input->get('year') :  date('Y');
		
		$this->data['vars']['month'] =	$month;
		$this->data['vars']['year'] =	$year;
		
		
		$this->data['reg_blocks'][] = 'complaint';
		$this->data['blocks']['complaint'] = $this->settings_faktura_model->getComplaintsList($month,$year);
		$this->data['debug'][] = $this->settings_faktura_model->debug_data;

		//print_r($this->data['blocks']['timesheet']);
		if(!empty($this->data['blocks']['complaint'])){
			$this->data['visible']['complaint_list'] =	1;
		}
		else{
			$this->data['visible']['complaint_list'] =	0;
		}
		
		
		$this->data['lists']['total'] = $this->data['blocks']['timesheet'][0]['month_total_hours'] ;
	
		
	 }
	

    /**
     * takes all posted (search form) data and saves it to an array
     * array is then saved in database
     * the unique id of the database record is now used in redirect for all page results
     */
    function __cachedComplaintListLog()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //create array containg all post data in format:: array('name'=>$this->input->post('name));
        $search_array = array();
        foreach ($_POST as $key => $value) {
            $search_array[$key] = $this->input->post($key);
        }

        //save serch query in database & get id of database record
        $search_id = $this->input->save_query($search_array);
		

        //change url to "list" and redirect with cached search id.
        redirect("admin/settings/kassekredit/list/$search_id");

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
        if ($form == 'edit_settings') {

            //check required fields
            $fields = array(
                'company_name' => $this->data['lang']['lang_company_name'],
                'company_email' => $this->data['lang']['lang_email_address'],
                'company_email_name' => $this->data['lang']['lang_email_from_name']);

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

/* End of file xyz.php */
/* Location: ./application/controllers/admin/xyz.php */
