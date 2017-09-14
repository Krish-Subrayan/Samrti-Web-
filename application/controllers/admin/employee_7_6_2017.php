<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class employee extends MY_Controller
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
		if($this->session->userdata['partner_branch'] == 1000)
		{		
			 $this->data['template_file'] = PATHS_ADMIN_THEME . 'employee.login.laundry.html';
		}
		else
		{
			 $this->data['template_file'] = PATHS_ADMIN_THEME . 'employee.login.html';
		}
       
	   

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
        $this->__commonAdmin_LoggedInCheck();

        //get the action from url
        $action = $this->uri->segment(3);
		
		$current_partner_branch = $this->session->userdata['partner_branch'];
		
		
		//$this->data['vars']['access'] = (($current_partner_branch == 0) || ($current_partner_branch == 14) ) ? '0' : 1;
		$this->data['vars']['access'] = (($current_partner_branch == 0)) ? '0' : 1;
		$this->data['vars']['hvitsnip'] = (($current_partner_branch == 14)) ? 'company' : '';
		$this->data['vars']['search_mobile'] = (($current_partner_branch == 14)) ? '0' : '1';
		
				$this->session->unset_userdata('carttype');
				
				$this->session->unset_userdata('companytype');
				
				$this->session->unset_userdata('company');
				
				$newdata = array('company_status'=> 0);
				$this->session->set_userdata($newdata);
		
        //route the rrequest
        switch ($action) {

				
            case 'list' :
				//template file
				$this->data['template_file'] = PATHS_ADMIN_THEME . 'employee.list.html';
				break;
				
            case 'calendar' :
				//template file
				$this->data['template_file'] = PATHS_ADMIN_THEME . 'employee.calendar.html';
				$this->__getCalendar();
				break;
				
				
			 case 'startLogin' :
				$this->__startLogin();
				break;
				
			 case 'stopLogin' :
				$this->__stopLogin();
				break;
				
			 case 'currentTime' :
				$this->__currentTime();
				break;

			case 'search-customer':
				if($_GET['company'])
				{
					$this->__searchCompany();
				}
				else
				{
					$this->__searchCustomer();
				}
				break;

			case 'search-company-mobile':
				$this->__searchCompanyCustomer('number');
				 break;
				 
			 case 'search-company-customer':
				$this->__searchCompanyCustomer('name');
				 break;
			case 'searchcompany':
				$this->__searchCompany();
				 break;
			 case 'search-mobile':
			
				 if($this->session->userdata['partner_branch'] == 14 || $this->session->userdata['partner_branch'] == 28)
				 {
				
					$this->__searchCompanyCustomer('number');
				 }
				 else
				 {
					$this->__searchMobile();
				 }
          		
          		 break;	
				 
			 case 'getemplist':
				$this->__getemplist();
          		break;
				
			 case 'get-order-status':
				$this->__getOrderstatus();
          		break;
			 case 'testpush':
				$this->__pushMessage();
          		break;
				
            case 'search-calendar-log':
                $this->__cachedCalendarLog();
                break;
				
            default:				
				$this->__stafflogin();
                $this->__viewPage();
                break;
        }

        //load view
        $this->__flmView('admin/main');

    }
	
     /**
      * get order status
      */
     function __getOrderstatus()
	 {
 		
        //profiling
        $this->data['controller_profiling'][] = __function__;
		$oid =$this->input->post('nbarcode');
		$type='laundry';
		$baginfo=$this->orders_model->getBagBarcodeinfo($oid,$type);
	
		if($baginfo)
		{
			$bag=$baginfo['id'];
			$binfo=$this->orders_model->getBagOrder($bag);
			if(intval($binfo['order']) > 0)
			{
				$orderid=$binfo['order'];
			}
			else
			{
				$orderid=0;
			}
			
		}
		else
		{

			$binfo=$this->process_order_model->getHeatBarcodeinfo($oid);
			if($binfo)
			{
				$orderid=$binfo['order'];
				
			}
			else
			{
				$orderid=$oid;
			}
			
			
		}
		
	
		if(intval($orderid) > 0)
		{
			$result = $this->employee_model->getOrderstatus($orderid);
			$this->data['debug'][] = $this->employee_model->debug_data;
			$response = array('response'=>$result['status']);
		}
		else
		{
			$response = array('response'=>0);
		}
		
		echo json_encode($response);exit;									
		
     }
	
	
	
     /**
      * search customer by name
      */
     function __searchCustomer()
	 {
 		
		
        //profiling
        $this->data['controller_profiling'][] = __function__;
		
			
			$keyword =$this->input->get('query');
			
			
			$this->data['reg_blocks'][] = 'customer';
			$this->data['blocks']['customer'] = $this->customer_model->searchCustomer($keyword,'name','asc');
			$this->data['debug'][] = $this->customer_model->debug_data;
			$array = array();

			if (count($this->data['blocks']['customer']) > 0) {
				
				for($i=0;$i<count($this->data['blocks']['customer']);$i++){
					
					 $name = $this->data['blocks']['customer'][$i]['id'];
					 $val = $this->data['blocks']['customer'][$i]['name'];
					 $number = $this->data['blocks']['customer'][$i]['number'];
					$array[] = array (
						'label' =>$name,
						'value' => $val,
						'phone' => $number,
						'branch' => $this->session->userdata['partner_branch'],
					);
				}
				
				
				//RETURN JSON ARRAY
				
			
			}
			echo json_encode ($array);	exit;
	 }
	
	
     /**
      * search customer by name in a company
      */
     function __searchCompanyCustomer($type)
	 {
 		
	
        //profiling
			$this->data['controller_profiling'][] = __function__;
			$keyword =$this->input->get('query');
			//$company =intval($_COOKIE['navn_id']);
			$company =$this->input->get('companyval'); 
			
			if($company == '')
			{
				$company =$this->input->get('company'); 
			}
		
			
			if(intval($company) == 0)
			{
				$company =intval($_COOKIE['navn_id']);
			}
			
		
		
			$this->data['reg_blocks'][] = 'customer';
			$this->data['blocks']['customer'] = $this->customer_model->searchCompanyCustomer($keyword,$company,$type,'asc');
			$this->data['debug'][] = $this->customer_model->debug_data;
			
		$array = array();
			if (count($this->data['blocks']['customer']) > 0) {
				
				for($i=0;$i<count($this->data['blocks']['customer']);$i++){
					 $name = $this->data['blocks']['customer'][$i]['id'];
					 if($type == 'number')
					 {
						 $val = $this->data['blocks']['customer'][$i]['number'];
						 $number = $this->data['blocks']['customer'][$i]['name'];
					 }
					 else
					 {
						 $val = $this->data['blocks']['customer'][$i]['name'];
						 $number = $this->data['blocks']['customer'][$i]['number'];
					 }
					 
					
					$array[] = array (
						'label' =>$name,
						'value' => $val,
						'phone' => $number,
					);
				}
				
				//RETURN JSON ARRAY
				
			
			}
			echo json_encode ($array);exit;
	 }
	
	  /**
      * search customer by mobile
      */
     function __searchMobile()
	 {
 		
        //profiling
        $this->data['controller_profiling'][] = __function__;
		
			
			$keyword =$this->input->get('query');
			
			$this->data['reg_blocks'][] = 'customer';
			$this->data['blocks']['customer'] = $this->customer_model->searchCustomer($keyword,'id','asc','number');
		
			
			$this->data['debug'][] = $this->customer_model->debug_data;

	
			if (count($this->data['blocks']['customer']) > 0) {
				$array = array();
				for($i=0;$i<count($this->data['blocks']['customer']);$i++){
					
					 $name = $this->data['blocks']['customer'][$i]['id'];
					 $val = $this->data['blocks']['customer'][$i]['name'];
					 $number = $this->data['blocks']['customer'][$i]['number'];
					 $array[] = array (
						'label' =>$name,
						'value' => $number,
						'phone' => $number
					);
				}
				
				//RETURN JSON ARRAY
				echo json_encode ($array);	exit;
			
			}
	 }
	
	
	/*get current server time*/
	function __currentTime(){
		$msg = date('H:i:s');
		echo $msg;exit;									
	}


    // -- __viewPage- -------------------------------------------------------------------------------------------------------
    /**
     * some notes
     */

    function __viewPage()
    {

        //flow control
        $next = true;
		
		//echo '<pre>';print_r($this->session->userdata);exit;
		
        //profiling
        $this->data['controller_profiling'][] = __function__;
   
        //get results
		$data = $this->employee_model->getOpeningHours();
		
	
		
		
		$this->data['debug'][] = $this->employee_model->debug_data;	
		
		
		$str ='<div id="opening-hours">
               <p><span>Åpningstider:</span><br>';
	    if(count($data)> 0){		   
		foreach ($data as $key => $val){
				$today = date('l');		
				$day_eng =  ucfirst($data[$key]['weekday']);
				$day =  $data[$key]['weekday_nor'];
				$open = $data[$key]['opening_time'];
				$close =  $data[$key]['closing_time'];
				$time = ($open !='') ? $open ." - ". $close : 'Stengt';
				
				$class = ($day_eng == $today) ? ' bold' : '';
				$str .=  '<span class="weekday'.$class.'">'.$day .'</span> <span class="time'.$class.'">'. $time.'</span><br>'; 	
		}
				
		}
          $str .='</p></div>';
		  
		$this->data['lists']['opening_hours']=$str;
		
//echo '<pre>';print_r($this->session->userdata);exit;
		
		$current_partner=$this->session->userdata['partner'];
		$current_partner_branch=$this->session->userdata['partner_branch'];
		$result=$this->general_model->branchstafflist($current_partner_branch);
	//	echo '<pre>';print_r($result);exit;
	
	
	
		$staffarray=array(0);
		if(count($result) > 0)
		{
			foreach($result as $staffitem)
			{
				$staffarray[$staffitem['id']]=$staffitem['employee_p_branch'];
				$staff_array[$staffitem['employee_p_branch']]=$staffitem;
			}
		}
		
		$loginstafflist=$this->employee_model->loginstafflist($staffarray,date('Y-m-d'));	
	
		$staffstr='';
		$staffscript='';
	
		/*$staff_html='  
        	<div class="row" style="background: #d8d8d8;font-weight:bold">
             <div class="col-md-6 ">
               <p class="black-text">Navn</p>
             </div>
             <div class="col-md-2">
                 <p class="black-text">Start</p>
             </div>
             <div class="col-md-2">
                 <p class="black-text">Slutt</p>
             </div>
			 <div class="col-md-2 no-padd"></div>
             <div class="clearfix"></div>
             <hr>
          </div>';*/
		  
		 $staff_html = ''; 
		  
		$logarray=array();
		
			
		if($loginstafflist)
		{
	
			$cstaff=$this->session->userdata['current_staff'];
			$cstaffstatus=0;
			
			foreach($loginstafflist as $logitem)
			{
			
				$logid=$staff_array[$logitem['employee_p_branch']]['id'];
				
			
			
				$staffhtml.='<div class="row mt-sm">
				 <div class="col-md-6">
				   <p>'.$staff_array[$logitem['employee_p_branch']]['fname'].'&nbsp;'.$staff_array[$logitem['employee_p_branch']]['lname'].'</p>
				 </div>
				 <div class="col-md-3 no-padd"><p class="emptime">'.$logitem['in'].' - ';
				 
				 if(strcmp($logitem['out'] ,'00:00'))
				 $staffhtml.=$logitem['out'];
				 $staffhtml.='</p></div>';
				 
				 
				 if($logitem['end_time'] == '0000-00-00 00:00:00')
				 {
				 
					if($cstaff == $logid)
					{
						$newdata = array('start_current_staff'=>$logid);
						$this->session->set_userdata($newdata);	
					}
					
					
					
					$logarray[$logid]=$logid;
					$staffhtml.=' <div class="col-md-3 no-padd" id="staffbutton_'.$logid.'"><button type="button" name="stop" id="stop" onclick="stopLogin(\''.$logid.'\')" class="btn-xs red  btn-info">Stemple ut</button></div>'; 
				 }
				 else
				 {
					$staffhtml.='<div class="col-md-3 no-padd"><button type="button"  class="btn-xs black  btn-info">'.$logitem['totalhours'].'</button></div>'; 
				 }
				 
				 $staffhtml.='<div class="clearfix"></div><hr></div>';
				 
				
			}
		}
		

		
			if($this->session->userdata['staff'])
			{
				foreach($this->session->userdata['staff'] as $logstaff)
				{
				
					if(!isset($logarray[$logstaff['id']]))
					{
						  $staff_html.='<div class="row mt-sm">
						  <div class="col-md-6">
						  <p>'.$logstaff['fname'].'&nbsp;'.$logstaff['lname'].'</p>
						  </div>
						  <div class="col-md-3"></div>';
						  $staff_html.='<div class="col-md-3 no-padd" id="staffbutton_'.$staffitem['id'].'"><button type="button" name="start" id="start" onclick="startLogin(\''.$logstaff['id'].'\');" class="btn-xs btn-info">Stemple inn</button></div>'; 
						  $staff_html.='<div class="clearfix"></div><hr></div>';
					}
				
				}
			}
			
			
		
			//$this->session->userdata['staff']
			//echo '<pre>';print_r($logarray);exit;
			
		$this->data['lists']['employee_list']=$staff_html.$staffhtml;
		
		
		$this->data['lists']['employee_list_script']=$staffscript;
		
		
		$data = $this->employee_model->getTotalAnsatt();
		$this->data['debug'][] = $this->employee_model->debug_data;	
		$this->data['lists']['ansatt']=$data['count'];


		$data = $this->employee_model->getTotalOrder();
		$this->data['debug'][] = $this->employee_model->debug_data;	
		$this->data['lists']['order']=$data['count'];

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

        //nothing specified - return false & error message
        $this->form_processor->error_message = $this->data['lang']['lang_form_validation_error'];
        return false;
    }	
	
	function __stafflogin()	
	{		
	
		$cstaff=$this->session->userdata['current_staff'];
		
		//echo '<pre>';print_r($this->session->userdata);exit;
		
		if(count($_POST) > 0)		
		{	

			if(isset($_POST['staff']))			
			{				
				$staffid=$_POST['staff'];
				$result=$this->general_model->stafflogin($staffid);	
				
				
				if($result)				
				{					
					$newdata = array('current_staff'=>$staffid);
					$this->session->set_userdata($newdata);		
					
					//$s_data=$this->session->userdata;
					//$s_data['staff'][$staffid]=$result;
					$s_data['staff']=$this->session->userdata['staff'];
					$s_data['staff'][$staffid]=$result;
					$this->session->set_userdata($s_data);	
					
					$employee_type=$result['employee_type'];
					$name=$result['fname'];
					if($result['mname'] != '')
					{
						$name.=' '.$result['mname'];
					}
					if($result['lname'] != '')
					{
						$name.=' '.$result['lname'];
					}
					
					$newdata = array('current_staff_name'=>$name);
					$this->session->set_userdata($newdata);		
					
					$arr = array('current_staff_employee_type'=>$employee_type);
					$this->session->set_userdata($arr);		


					$profiledata='<div class="pull-left col-md-3 no-padd text-center">
						 <img src="'.EMPLOYEE_IMAGE_FOLDER.$result['avatar'].'" width="150"  alt="" title="">
					   </div>
					   <div class="pull-left col-md-9 customer-info">
						   <p><span>'.$name.'</span>
						   (+47) '.$result['phone'].'<br>
						   <a href="mailto: '.$result['email'].'"> '.$result['email'].'</a></p>
						<p class="mt-sm"> <a href="'.$this->data['vars']['site_url'].'admin/employee/calendar" class="btn-xs btn-info" style="width:auto;padding:5px!important; color:#fff; background:#ccc;text-decoration:none">Timerliste</a></p>
					   </div>
					   <div class="clearfix"></div>';
					    $cstaff=$this->session->userdata['current_staff'];
					    $start_status=0;
					    if($this->session->userdata['staff'][$cstaff]['start_time'])		
						{
							$start_status=1;
						}
					
						$employee_p_branch=$result['employee_p_branch'];
						
						$newdata = array('employee_p_branch'=>$employee_p_branch);
						$this->session->set_userdata($newdata);		
					
					
						$datetime=date('Y-m-d');
						$logintime=$this->employee_model->checkTodayLogin($employee_p_branch,$datetime);
					
						if($logintime)
						{
							$start_status=1;
						}
								
								
						$data = $this->employee_model->getTotalAnsatt();
						$this->data['debug'][] = $this->employee_model->debug_data;	
						$ansatt =$data['count'];
								
								
								
					$response = array('response'=>"success","staff"=>$staffid,"profile"=>$profiledata,"message" => 'Your account has been successfully loged in.','start_status'=>$start_status,'loginstatus'=>$loginstatus,'ansatt'=>$ansatt);

					//show login form with error
					$this->notices('error', $this->data['lang']['lang_employees_not_logout']);
					
					
					echo json_encode($response);exit;									
				}				
				else				
				{					
					$response = array('response'=>"error","message" =>  $this->data['lang']['lang_invalid_employee_id']);
					echo json_encode($response);exit;					
				}							
			}
			
					
		}
		
					
			$newtime='';		
			$button='<button type="button" name="start" id="start" onclick="startLogin();" class="btn-xlg btn-info">Stemple inn</button>';
			$staffprofile='<style>.employee-profile{display:none;}</style>';
			if(intval($cstaff) > 0)
			{
				$result=$this->session->userdata['staff'][$cstaff];	
				//echo '<pre>';print_r($result);exit;
				if($this->session->userdata['staff'][$cstaff]['start_time'])		
				{			
					$button='<button type="button" name="stop" id="stop" onclick="stopLogin();" class="btn-xlg red btn-info pull-left">Stemple ut</button>';	
					
					$employee_p_branch=$this->session->userdata['staff'][$cstaff]['employee_p_branch'];		
					$totallogintime=$this->employee_model->getTotalworkinghours($employee_p_branch,date('Y-m-d'));	
				
				//$newtime="starttime_".$cstaff."({precision: 'seconds', startValues: {".$totallogintime."}})";	
				
				

				
				}
					$name=$result['fname'];
					if($result['mname'] != '')
					{
						$name.=' '.$result['mname'];
					}
					if($result['lname'] != '')
					{
						$name.=' '.$result['lname'];
					}
					$staffprofile='<style>.employee-profile{display:block;}</style>
					   <div class="pull-left col-md-3 no-padd text-center">
						 <img src="'.EMPLOYEE_IMAGE_FOLDER.$result['avatar'].'" width="150"  alt="" title="">
					   </div>
					   <div class="pull-left col-md-9 customer-info">
						   <p><span>'.$name.'</span>
						   (+47) '.$result['phone'].'<br>
						   <a href="mailto: '.$result['email'].'"> '.$result['email'].'</a><br>
						<p class="mt-sm"> <a href="'.$this->data['vars']['site_url'].'admin/employee/calendar" class="btn-xs  btn-info" style="width:auto;padding:5px!important; color:#fff; background:#ccc;text-decoration:none">Timerliste</a></p>

					   </div>
					';
				
			}
			
			$this->data['lists']['staffprofile']=$staffprofile;
			$this->data['lists']['staffbutton']=$button;	
			//$this->data['lists']['stafftime']=$newtime;
						
		}
		
   function __startLogin()
   {
		$cstaff=$this->session->userdata['current_staff'];
	
		if(isset($_POST['staff']))			
		{				
			$staffid=$_POST['staff'];
			if($staffid == $cstaff)
			{
			
				$newdata = array('start_current_staff'=>$staffid);
				$this->session->set_userdata($newdata);	
			
				if($this->session->userdata['staff'][$cstaff]['start_time'] == '')
				{
					$datetime=date('Y-m-d H:i:s');	
					$employee_p_branch=$this->session->userdata['staff'][$cstaff]['employee_p_branch'];
					$logintime=$this->employee_model->startLogin($employee_p_branch,$datetime);	
					if($logintime)
					{
						//$result=$this->session->userdata['staff'][$cstaff];
						//$result['start_time']=$logintime;								
						//$newdata = array('staff'  =>array($cstaff=>$result));
						//$this->session->set_userdata($newdata);
						$s_data['staff']=$this->session->userdata['staff'];
						$s_data['staff'][$staffid]['start_time']=$logintime;
						$this->session->set_userdata($s_data);	
					
						
						
					}
				}
				
				$employee_p_branch=$this->session->userdata['staff'][$cstaff]['employee_p_branch'];		
				$totallogintime=$this->employee_model->getTotalworkinghours($employee_p_branch,date('Y-m-d'));	
				$newtime='';
				/*$start = $this->session->userdata['staff'][$cstaff]['start_time'];
				$now = strtotime("now");
				$then = strtotime("$start");
				$difference = $now - $then ;
				$num = $difference/86400;
				$days = intval($num);
				$num2 = ($num - $days)*24;
				$hours = intval($num2);
				$num3 = ($num2 - $hours)*60;
				$mins = intval($num3);
				$num4 = ($num3 - $mins)*60;
				$secs = intval($num4);*/
			//$newtime="{precision: 'seconds', startValues: {".$totallogintime."}}";	
					//$scripts='<script type="text/javascript">';
					//$scripts.='timer_'.$staffid.'.start('.$newtime.');';
				//	$scripts.='timer_'.$staffid.'.addEventListener(\'secondsUpdated\', function (e) {
					//	$(\'#time_'.$staffid.'\').html(timer_'.$staffid.'.getTimeValues().toString());
					//	});';
						
					//$scripts.='starttime_'.$staffid.'('.$newtime.')';
					/*$scripts.='</script>';*/
					
				$data = $this->employee_model->getTotalAnsatt();
				$this->data['debug'][] = $this->employee_model->debug_data;	
				$ansatt =$data['count'];

				$response = array('response'=>"success","time" => $newtime,"scripts" => '',"ansatt" => $ansatt);
				echo json_encode($response);exit;	
				
			}
			else
			{
				$response = array('response'=>"error","message" => 'Invalid Staff Id.');
				echo json_encode($response);exit;	
			}
		}
   }

   
    function __stopLogin()
   {
		$cstaff=$this->session->userdata['current_staff'];
		
		if(isset($_POST['staff']))			
		{				
			$staffid=$_POST['staff'];
			if($staffid == $cstaff)
			{
			
				//if($this->session->userdata['staff'][$cstaff]['start_time'] != '')
				//{
					$datetime=date('Y-m-d H:i:s');	
					$employee_p_branch=$this->session->userdata['staff'][$cstaff]['employee_p_branch'];
					$this->employee_model->stopLogin($employee_p_branch,$datetime);	
				//}
				
				
				//$newdata = array('staff'  =>array($cstaff=>''));
				//$this->session->set_userdata($newdata);
				
				$s_data['staff']=$this->session->userdata['staff'];
				unset($s_data['staff'][$cstaff]);
				$this->session->set_userdata($s_data);	
				
				$this->session->unset_userdata('start_current_staff');
				$this->session->unset_userdata('current_staff');
				$this->session->unset_userdata('employee_p_branch');
				
				
				$data = $this->employee_model->getTotalAnsatt();
				$this->data['debug'][] = $this->employee_model->debug_data;	
				$ansatt =$data['count'];
				
				
				$response = array('response'=>"success",'ansatt'=>$ansatt);
				echo json_encode($response);exit;	
				
			}
			else
			{
				$response = array('response'=>"error","message" => 'Invalid Staff Id.');
				echo json_encode($response);exit;	
			}
		}
		else
			{
				$response = array('response'=>"error","message" => 'Invalid Staff Id.');
				echo json_encode($response);exit;	
			}
   }

   
   function __getemplist()
    {

		$current_partner=$this->session->userdata['partner'];
		$current_partner_branch=$this->session->userdata['partner_branch'];
		$result=$this->general_model->branchstafflist($current_partner_branch);
		
		$staffarray=array();
		if(count($result) > 0)
		{
			foreach($result as $staffitem)
			{
				$staffarray[$staffitem['id']]=$staffitem['employee_p_branch'];
				$staff_array[$staffitem['employee_p_branch']]=$staffitem;
			}
		}
		
		$loginstafflist=$this->employee_model->loginstafflist($staffarray,date('Y-m-d'));	
	
	
		$staffstr='';
		$staffscript='';
		$staffhtml='';
		
		/*$staff_html='<div class="ansattlist" >          
        	<div class="row" style="background: #d8d8d8; font-weight:bold">
             <div class="col-md-6">
               <p class="black-text">Navn</p>
             </div>
             <div class="col-md-2">
                 <p class="black-text">Start</p>
             </div>
             <div class="col-md-2">
                 <p class="black-text">Slutt</p>
             </div>
			 <div class="col-md-2 no-padd"></div>
             <div class="clearfix"></div>
             <hr>
          </div>';*/
		  
		$staff_html='';  
		  
		$logarray=array();
		if($loginstafflist)
		{
	
			$cstaff=$this->session->userdata['current_staff'];
			$cstaffstatus=0;			
			foreach($loginstafflist as $logitem)
			{
				
				$logid=$staff_array[$logitem['employee_p_branch']]['id'];
				$staffhtml.='<div class="row  mt-sm">
				 <div class="col-md-6">
				   <p>'.$staff_array[$logitem['employee_p_branch']]['fname'].'&nbsp;'.$staff_array[$logitem['employee_p_branch']]['lname'].'</p>
				 </div>
				 <div class="col-md-3 no-padd"><p class="emptime">'.$logitem['in'].' - ';
				 if(strcmp($logitem['out'] ,'00:00'))
				 $staffhtml.=$logitem['out'];
				 $staffhtml.='</p></div>';
				 
				 
				 if($logitem['end_time'] == '0000-00-00 00:00:00')
				 {
					$logarray[$logid]=$logid;
					
					if($cstaff == $logid)
					{
						$newdata = array('start_current_staff'=>$logid);
						$this->session->set_userdata($newdata);	
					}
					
					
					
					
					$staffhtml.=' <div class="col-md-3 no-padd" id="staffbutton_'.$logid.'"><button type="button" name="stop" id="stop" onclick="stopLogin(\''.$logid.'\')" class="btn-xs red  btn-info">Stemple ut</button></div>'; 
				 }
				 else
				 {
					$staffhtml.='<div class="col-md-3 no-padd"><button type="button"  class="btn-xs black  btn-info">'.$logitem['totalhours'].'</button></div>'; 
				 }
				 
				 $staffhtml.='<div class="clearfix"></div><hr></div>';
				 
				
			}
		}
		
	
		
			if($this->session->userdata['staff'])
			{
				foreach($this->session->userdata['staff'] as $logstaff)
				{
				
					
				
					if(!isset($logarray[$logstaff['id']]))
					{
						  $staff_html.='<div class="row mt-sm">
						  <div class="col-md-6">
						  <p>'.$logstaff['fname'].'&nbsp;'.$logstaff['lname'].'</p>
						  </div>
						  <div class="col-md-3 no-padd"></div>';
						  $staff_html.='<div class="col-md-3 no-padd" id="staffbutton_'.$staffitem['id'].'"><button type="button" name="start" id="start" onclick="startLogin(\''.$logstaff['id'].'\');" class="btn-xs btn-info">Stemple inn</button></div>'; 
						  $staff_html.='<div class="clearfix"></div><hr></div>';
					}
				
				}
			}
			
			
			
		$employee_list=$staff_html.$staffhtml;
		
	
			
				$response = array('response'=>"success",'employees'=>$employee_list);
				echo json_encode($response);exit;	
		

    }

	  /**
      * search customer by name
      */
     function __searchCompany()
	 {
 		
        //profiling
        $this->data['controller_profiling'][] = __function__;
		
			
			$keyword =$this->input->get('query');
			
			
			$this->data['reg_blocks'][] = 'customer';
			$this->data['blocks']['customer'] = $this->customer_model->searchCompany($keyword,'name','asc');
			$this->data['debug'][] = $this->customer_model->debug_data;
			$array = array();

			if (count($this->data['blocks']['customer']) > 0) {
				
				for($i=0;$i<count($this->data['blocks']['customer']);$i++){
					
					 $name = $this->data['blocks']['customer'][$i]['id'];
					 $val = $this->data['blocks']['customer'][$i]['name'];
					 $number = $this->data['blocks']['customer'][$i]['number'];
					$array[] = array (
						'label' =>$name,
						'value' => $val,
						'phone' => $number,
						'branch' => $this->session->userdata['partner_branch'],
					);
				}
				
				
				//RETURN JSON ARRAY
				
			
			}
			echo json_encode ($array);	exit;
	 }
	 
	 

	  /**
      *  get timesheet for an employee
      */
     function __getCalendar()
	 {
 		
        //profiling
        $this->data['controller_profiling'][] = __function__;
		
		$employee =$this->session->userdata['employee_p_branch'];
		
		
        //uri segments
        $search_id = (is_numeric($this->uri->segment(4))) ? $this->uri->segment(4) : 0;
        //load the original posted search into $_get array
        $this->input->load_query($search_id);
		
		
		$month =  ($this->input->get('month')!='') ?  $this->input->get('month') : date('m');
		$year =  ($this->input->get('year')!='') ?  $this->input->get('year') :  date('Y');
		
		$this->data['vars']['month'] =	$month;
		$this->data['vars']['year'] =	$year;
		
		
		$this->data['reg_blocks'][] = 'timesheet';
		$this->data['blocks']['timesheet'] = $this->employee_model->getCalendar($employee,$month,$year);
		$this->data['debug'][] = $this->employee_model->debug_data;

		//print_r($this->data['blocks']['timesheet']);
		
		$this->data['lists']['total'] = $this->data['blocks']['timesheet'][0]['month_total_hours'] ;
	
		
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
	
	function __pushMessage()
	{
		require(APPPATH .'third_party/Mobile_Detect.php');
        $detect = new Mobile_Detect;
        $device=($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');
		if($device == 'tablet' || $device == 'phone')
		{
			self::send_fcm('fofePOKlM3M:APA91bEDAqC2SBo123ppu9vDXg4zZu3sG1ghwHX4Z1vLCcvPmbKNRoy_bqQgX_9oy6Ymu026qcJJWicqkCIr10LfJuLa-dBGDzZdlIDDHhW9NhpfXNJxRbhXqVZs7eY8_dRm6PT8GN3z','AAAAUtvA6CI:APA91bHsqbvBUq28UxpmeyXGRUadrte7aG6BEX_KpKfryvACpGp_FDs1J8Pp8KsZxmKfasWXNa6lw_emONV2Fv9ySaNSgN6cQMBtN6o-k0-Wh6TJIH7WoN3l65quggpQFItEswmZ2ooz');
		}
	}


    /**
     * takes all posted (search form) data and saves it to an array
     * array is then saved in database
     * the unique id of the database record is now used in redirect for all page results
     */
    function __cachedCalendarLog()
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
        redirect("admin/employee/calendar/$search_id");

    }
	
}

/* End of file employee.php */
/* Location: ./application/controllers/admin/employee.php */
