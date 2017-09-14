<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Orderhistory extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'settings.orders.html';
		
		/*if($this->session->userdata['current_staff_employee_type'] != 'admin'){
			redirect('/admin/error/permission-denied');
			exit();
		}*/
		
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

        //re-route to correct method
        switch ($action) {
            default:
                $this->__orderListing();
        }

        //css - active tab
        $this->data['vars']['css_menu_settings'] = 'current';

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * load general settings
     */
    function __orderListing()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;
		
        //load settings
        if ($next) {

		//get today in and ut in shop
		$this->data['reg_blocks'][] = 'iporders';
		$iporders=$this->settings_order_model->getOrderhistory();
		$orderline_orderids = $iporders['orderids'];
		$this->data['blocks']['iporders'] = $iporders['orderinfo'];
		$this->data['debug'][] = $this->settings_order_model->debug_data;
		$str ='';
		

				
	//	echo '<pre>';print_r($iporders);exit;
		
			
		if(count($this->data['blocks']['iporders']) > 0){
			
		  for($i=0;$i<count($this->data['blocks']['iporders']);$i++){
			
			$old[] = $this->data['blocks']['iporders'][$i]['id'];
			
		    $company = ($this->data['blocks']['iporders'][$i]['type'] == 'shop') ?  $this->data['blocks']['iporders'][$i]['partner_branch'] :  $this->data['settings_company']['company_name'];
					
		   $branch =  ($company == $this->data['settings_company']['company_name']) ?  '('.substr($company,0,3) .')':  (($company != $this->session->userdata['partner_branch_name']) ? '('.substr($company,0,3).')' :  ''); 
			
			
			$orderline_today_delivery = $this->orders_model->validateTodaydelivery($this->data['blocks']['iporders'][$i]['id']);
			
			
			
			if($i==(count($this->data['blocks']['iporders'])-1)){
			
				$firstdelivery = $this->orders_model->getShopdeliverydate($this->data['blocks']['iporders'][$i]['id'],$old,'ASC','process');
			
			}
			
		//	$result = $this->orders_model->validateOrderlinedelivery('order',$this->data['blocks']['iporders'][$i]['id']);
			
		
			
			//if($result)
			//{
			
				//product types 
				$order_id = $this->data['blocks']['iporders'][$i]['id'];
				$this->data['reg_blocks'][] = 'orderline';
					if(isset($orderline_orderids[$order_id]))
					{
					
				
						$this->data['blocks']['orderline'] = $this->settings_order_model->getOrderLine($order_id);
						
							
						
					}
					else
					{
						$this->data['blocks']['orderline'] = $this->orders_model->getOrderLine($order_id);
					}
					
				
					//if($order_id == '10154914')
					//{
						//echo '<pre>';print_r($this->data['blocks']['orderline']);exit;
					//}
					
					//10154877 
					
				
				$this->data['debug'][] = $this->orders_model->debug_data;
				$arr = array();
				$myarr = array();
				$carr = array();
				$artikler = '';
				$prod_name = array();
				$temp = '';
				$ostatusarray=array();
				$amtarray=array();
				for($j=0;$j<count($this->data['blocks']['orderline']);$j++)
				{
				
				
						if(isset($orderline_orderids[$order_id]))
						{
						
						 $ostatusarray[] = $this->settings_order_model->getOrderstatus($this->data['blocks']['orderline'][$j]['id'],'orderline');
						 
						 
					
					
			
						$amtarray[]=($this->data['blocks']['orderline'][$j]['changed_amount'] != '') ? $this->data['blocks']['orderline'][$j]['changed_amount'] : $this->data['blocks']['orderline'][$j]['amount'];
			
			
							
						}
						
						
					
					
					
						$ptype = $this->data['blocks']['orderline'][$j]['ptype'];
						if ((!in_array($ptype, $arr)) || ($j==0)){
							$arr[] = $ptype;
						}
						$gtype = ucwords(implode(', ',$arr));
						
						$cdescription = $this->data['blocks']['orderline'][$j]['cdescription'];
						if ((!in_array($cdescription, $carr)) || ($j==0)){
							$carr[] = $cdescription;
						}
						
						
						$myarr = $this->orders_model->getProductDisplayName($this->data['blocks']['orderline'][$j]['product']);					
						if($myarr['name']!=''){
							if (!in_array($temp, $prod_name)){
								$prod_name[$j] = substr($myarr['name'],0,2);
							}
							$temp = $myarr['name'];
						}
						
						
					
					
					
					
				}
				
				$kategori = ucwords(implode(', ',$carr));
				$kategori = $this->general_model->trim_text($kategori);
				
				$artikler = implode(', ',$prod_name);
				$artikler = $this->general_model->trim_text($artikler);
				
				
						if(isset($orderline_orderids[$order_id]))
						{
						
						  $amount = array_sum($amtarray);
							  if(count($ostatusarray) > 0)
							  {
								$ostatus=9;
							  }
						}
						else
						{
							  $amount = ($this->data['blocks']['iporders'][$i]['changed_amount']!='') ? $this->data['blocks']['iporders'][$i]['changed_amount'] :  $this->data['blocks']['iporders'][$i]['total_amount'];
							  
							  
							  
							  $ostatus = $this->settings_order_model->getOrderstatus($order_id);
							  
							  
						}
						
				//echo $order_id.'=>'.$ostatus;echo '<br>';
				  
				  $opstatus = $this->data['blocks']['iporders'][$i]['payment_status'];
				  $color = ($opstatus=='canceled') ? 'red' : (($opstatus == 'paid') ?  'green' : 'orange');	
				  $status = ($opstatus=='canceled') ? 'C' : (($opstatus == 'paid') ?  'BE' : 'UB');	
				  
				  //$ostatus = $this->data['blocks']['iporders'][$i]['ostatus'];
				  $ostatus = ($ostatus=='9' || $ostatus=='11') ? 'Ut' : 'Inn';	
				  		  
					
					$str .='<div class="orderlisting row" id="order_'.$this->data['blocks']['iporders'][$i]['id'].'" >
					<a href="#" rel="'.$this->data['blocks']['iporders'][$i]['id'].'">
					  <div class="col-md-1 no-padd text-center"> '.$ostatus.' </div>';
					  
					   /*<div class="col-md-2 no-padd text-center"> '.$this->data['blocks']['iporders'][$i]['customername'].' </div>*/
					  $str .='<div class="col-md-2"> #'.$this->data['blocks']['iporders'][$i]['id'].' <span class="green-text">'.$branch.'</span></div>
					  <div class="col-md-3 text-left"> '.$kategori.' </div>
					  <div class="col-md-1 no-padd text-left"> '.$this->data['blocks']['iporders'][$i]['einitial'].' </div>
					  <div class="col-md-3 text-right">kr '.formatcurrency($amount).' </div>';
					 $str .='<div class="col-md-1 text-center"> <div class="'.$color.' paymentstatus"> '.$status.'</div> </div></a><div class="col-md-1 text-center"><div title="utsikt" class="bg-purple paymentstatus utsikt" id="'.$this->data['blocks']['iporders'][$i]['id'].'">PT</div></div><div style="display:none;" id="kasserer_'.$this->data['blocks']['iporders'][$i]['id'].'">Kasserer: '.$this->data['blocks']['iporders'][$i]['einitial'].'</div>';
					$str .='</div>';		
//<div class="col-md-1 text-center"><div title="utsikt" class="bg-purple paymentstatus utsikt" id="'.$this->data['blocks']['iporders'][$i]['id'].'">UT</div></div>					
			//}	
		  }//for
		  
		}
		else{
			$str.='<div class="col-md-12 text-center mt-lg mb-lg"><h2>Ingen resultat.</h2></div>';
		}
		  

		  $this->data['lists']['orders'] =  $str;

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
