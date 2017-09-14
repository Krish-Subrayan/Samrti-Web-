<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class Customer extends MY_Controller
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
		
    }
    /**
     * This is our re-routing function and is the inital function called
     *
     * 
     */
    function index()
    {
		//echo '<pre>';print_r($this->session->userdata);exit;
        //profiling
        $this->data['controller_profiling'][] = __function__;
        //login check
        $this->__commonAdmin_LoggedInCheck();
        //get the action from url
        $action = $this->uri->segment(3);
		//echo '<pre>';print_r();exit;
        //get data
        $this->__pulldownLists();
			
        //route the rrequest
        switch ($action) {
			case 'register':
                $this->__addcustomer();
				break;
			case 'updateprofile':
				//$this->data['template_file'] = PATHS_ADMIN_THEME . 'profile.html';
                $this->__updateprofile();
				break;
			case 'profile':
				if(intval($this->session->userdata['customer']['company']) > 0)
				{
					$this->data['template_file'] = PATHS_ADMIN_THEME . 'companyprofile.html';
				}
				else
				{
					$this->data['template_file'] = PATHS_ADMIN_THEME . 'profile.html';
				}
				
                $this->__profile();
				$this->__getOrders();
                break;
			case 'companyprofile':
				$this->data['template_file'] = PATHS_ADMIN_THEME . 'companyprofile.html';
                $this->__profile();
				$this->__getOrders();
                break;
			case 'logout':
				$this->flmLogOut();
                break;
			case 'addsaldo':
			 $this->__addsaldo();
			break;

			case 'validateGiftcard':
			 $this->__validateGiftcard();
			break;	
					
			case 'account':
				 $this->data['template_file'] = PATHS_ADMIN_THEME . 'customer.account.log.html';
				 $this->__getCustomerDetail();
				 $this->__getAccountLog();
                break;
			case 'ajaxaccount':
				$this->__getajaxAccountLog();
                break;	
            case 'search-account-log':
                $this->__cachedCustomerAccountLog();
                break;
			case 'company':
				$this->data['template_file'] = PATHS_ADMIN_THEME . 'companycustomer.html';
				 $this->__getCustomersList();
                $this->__company();
                break;
			case 'mobileDetails':
				$this->__mobileDetails();
                break;
			case 'saldoprint':
				$this->data['template_file'] = PATHS_ADMIN_THEME . 'print.saldo.html';
				$this->__saldoprint();
				break;
			default:
			
			if(isset($_POST['company_type']) && isset($_POST['cart_type']) && isset($_POST['cmobile']))
			{
				
				$newdata = array('company_status'=> 1);
				$this->session->set_userdata($newdata);
				
				$this->data['template_file'] = PATHS_ADMIN_THEME . 'companycustomer.html';
				$this->__getCustomersList();
                $this->__company();
			}
			else
			{
				 $this->data['template_file'] = PATHS_ADMIN_THEME . 'customer.html';
				 $this->__mobileVerification();
				 $this->__getCustomersList();
			}
			 break;
        }
        //load view
        $this->__flmView('admin/main');
    }
	
	
    /**
     * takes all posted (search form) data and saves it to an array
     * array is then saved in database
     * the unique id of the database record is now used in redirect for all page results
     */
    function __cachedCustomerAccountLog()
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
        redirect("admin/customer/account/$search_id");

    }
	
     /**
      * get account log of a customer
      */
     function __getAccountLog(){
 		
        //profiling
        $this->data['controller_profiling'][] = __function__;
		
		$customer_id = $this->session->userdata['pos_customer_id']; //get  customer id from session
		

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //uri segments
        $search_id = (is_numeric($this->uri->segment(4))) ? $this->uri->segment(4) : 0;
        $sort_by = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(6) == '') ? 'sortby_id' : $this->uri->segment(6);
        $offset = (is_numeric($this->uri->segment(7))) ? $this->uri->segment(7) : 0;

        //load the original posted search into $_get array
        $this->input->load_query($search_id);
		
		
		
		$this->data['vars']['from'] = ($this->input->get('from')=='') ? 'Fra': date('d.m.Y',strtotime($this->input->get('from')));
		
		$this->data['vars']['to'] = ($this->input->get('to')=='') ? 'Til': date('d.m.Y',strtotime($this->input->get('to')));

		  
		//get results for orders in process (orders which are placed , in process)
		$this->data['reg_blocks'][] = 'account';
		$this->data['blocks']['account'] = $this->customer_model->getCustomerAccountLog($customer_id,'','','','',0);
		$this->data['debug'][] = $this->customer_model->debug_data;
		
		
		$str ='';
		if(count($this->data['blocks']['account']) > 0){
		  for($i=0;$i<count($this->data['blocks']['account']);$i++){
			  
			  $opstatus = $this->data['blocks']['account'][$i]['in_status'];
			  
			  $color = ($opstatus=='canceled') ? 'red' : (($opstatus == 'paid') ?  'green' : 'orange');	
			  $status = ($opstatus=='canceled') ? 'C' : (($opstatus == 'paid') ?  'BE' : 'UB');	
			  $orderid = ($this->data['blocks']['account'][$i]['order']!='')  ? '#'.sprintf("%08d",$this->data['blocks']['account'][$i]['order']) :  '';	
			  
			  $type  = ($this->data['blocks']['account'][$i]['type'] =='in') ? "Inn" :  "Ut";
			  	  
			  
			  $in_type  = ($this->data['blocks']['account'][$i]['in_type'] =='gift_card') ? "Gift Card" :  $this->data['blocks']['account'][$i]['in_type'];

				$str .='<div class="orderlisting row" >
				  <div class="col-md-2 black-text bold"> '.$this->data['blocks']['account'][$i]['rdate'].' </div>
				  <div class="col-md-1 black-text bold text-center"> '.ucfirst($type).'</div>
				  <div class="col-md-1 no-padd black-text bold"> '.ucfirst($in_type).'</div>
				  <div class="col-md-2 black-text no-padd  bold"> '. $orderid.' </div>
				  <div class="col-md-3 black-text no-padd bold"> '.$this->data['blocks']['account'][$i]['name'].' </div>
				  <div class="col-md-2 black-text no-padd text-right bold"> kr '.formatcurrency($this->data['blocks']['account'][$i]['amount']).' </div>';
				 $str .='<div class="col-md-1 text-center black-text bold"> <div class="'.$color.' paymentstatus"> '.$status.'</div></div> ';
				  
				$str .='</div>';				
		  }//for
		  
		}
		else{
			$str.='<div class="col-md-12 text-center mt-lg mb-lg"><h2>Ingen resultat.</h2></div>';
		}
		  
		  
		  $this->data['lists']['account_log'] =  $str;
      }

	
		/**
      * ajax get account log of a customer
      */
     function __getajaxAccountLog(){
 		
        //profiling
        $this->data['controller_profiling'][] = __function__;
		
		$customer_id = $this->session->userdata['pos_customer_id']; //get  customer id from session
		

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //uri segments
        $search_id = (is_numeric($this->uri->segment(4))) ? $this->uri->segment(4) : 0;
        $sort_by = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(6) == '') ? 'sortby_id' : $this->uri->segment(6);
        $offset = (is_numeric($this->uri->segment(7))) ? $this->uri->segment(7) : 0;

        //load the original posted search into $_get array
        $this->input->load_query($search_id);
		
		
		
		$this->data['vars']['from'] = ($this->input->get('from')=='') ? 'Fra': date('d.m.Y',strtotime($this->input->get('from')));
		
		$this->data['vars']['to'] = ($this->input->get('to')=='') ? 'Til': date('d.m.Y',strtotime($this->input->get('to')));

		
		  
		//get results for orders in process (orders which are placed , in process)
		$this->data['reg_blocks'][] = 'account';
		$this->data['blocks']['account'] = $this->customer_model->getCustomerAccountLog($customer_id,'','','','',0);
		$this->data['debug'][] = $this->customer_model->debug_data;
		
		
		$str ='';
		if(count($this->data['blocks']['account']) > 0){
		  for($i=0;$i<count($this->data['blocks']['account']);$i++){
			  
			  $opstatus = $this->data['blocks']['account'][$i]['in_status'];
			  
			  $color = ($opstatus=='canceled') ? 'red' : (($opstatus == 'paid') ?  'green' : 'orange');	
			  $status = ($opstatus=='canceled') ? 'C' : (($opstatus == 'paid') ?  'BE' : 'UB');	
			  $orderid = ($this->data['blocks']['account'][$i]['order']!='')  ? '#'.$this->data['blocks']['account'][$i]['order'] :  '';		  
			  
			  $in_type  = ($this->data['blocks']['account'][$i]['in_type'] =='gift_card') ? "Gift Card" :  $this->data['blocks']['account'][$i]['in_type'];
			  
			  $type  = ($this->data['blocks']['account'][$i]['type'] =='in') ? "Inn" :  "Ut";

				$str .='<div class="orderlisting row" >
				  <div class="col-md-2 black-text bold"> '.$this->data['blocks']['account'][$i]['rdate'].' </div>
				  <div class="col-md-1 black-text bold text-center"> '.ucfirst($type).'</div>
				  <div class="col-md-1 no-padd black-text bold"> '.ucfirst($in_type).'</div>
				  <div class="col-md-2 black-text no-padd  bold"> '.$orderid.' </div>
				  <div class="col-md-3 black-text no-padd bold"> '.$this->data['blocks']['account'][$i]['name'].' </div>
				  <div class="col-md-2 black-text no-padd text-right bold"> kr '.formatcurrency($this->data['blocks']['account'][$i]['amount']).' </div>';
				 $str .='<div class="col-md-1 text-center black-text bold"> <div class="'.$color.' paymentstatus"> '.$status.'</div></div> ';
				  
				$str .='</div>';				
		  }//for
		  
		}
		else{
			$str.='<div class="col-md-12 text-center mt-lg mb-lg"><h2>Ingen resultat.</h2></div>';
		}
		$result=array('status'=>'success','account_log'=>$str);
		echo json_encode($result);exit;
	}
	
	
     /**
      * get details of a customer
      */
     function __getCustomerDetail(){
 		
        //profiling
        $this->data['controller_profiling'][] = __function__;
		
		$customer_id = $this->session->userdata['pos_customer_id']; //get  customer id from session
		
		$saldo_status = $this->payments_model->getSaldostatus($customer_id);
		$this->data['visible']['saldo_status'] = $saldo_status;
		
		
		//get results
		$this->data['reg_fields'][] = 'customer';
		$this->data['fields']['customer'] = $this->customer_model->getCustomerinfo($customer_id);
		$this->data['debug'][] = $this->customer_model->debug_data;
		
		$this->data['lists']['customer_name'] = $this->data['fields']['customer']['firstname'];
		if(count($this->data['fields']['customer'])> 0){
			$this->data['visible']['wi_customer_profile'] = 1;
		}
		else{
			$this->data['visible']['wi_profile_none'] = 0;
		}
		
		$customer=$this->session->userdata['customer']['id'];
		$data = $this->payments_model->getAccountBalance($customer);
		$pendingsaldo = $data['pending'];
		$paidsaldo = $data['paid'];
			
		if(intval($pendingsaldo) > 0)
		{	
			//$amount= formatcurrency($paidsaldo).' ('.formatcurrency($pendingsaldo).')';
			$amount= formatcurrency($paidsaldo);
		}
		else
		{
			$amount= formatcurrency($paidsaldo);
		}
		$this->data['lists']['saldo']=$amount;

		if($paidsaldo < 0)
		{
			$this->data['lists']['saldocolor']='style="color:red;"';
		}
		
		
		//get utlevering count for a customer
		$this->data['reg_blocks'][] = 'orders';
		$this->data['blocks']['orders'] = $this->orders_model->getCustomerOrderhistory($customer_id,'utlevering');
		$this->data['debug'][] = $this->orders_model->debug_data;
		
		$this->data['vars']['order_count'] =  count($this->data['blocks']['orders']);
		
		
	 }
	
	
     /**
      * get customers list
      */
     function __getCustomersList(){
 		
        //profiling
        $this->data['controller_profiling'][] = __function__;
		
		if($this->session->userdata['current_staff_employee_type']=='admin'){
	
		//get results
		$this->data['reg_blocks'][] = 'customers';
		//$this->data['blocks']['customers'] = $this->customer_model->allCustomer('name','ASC');
		$this->data['debug'][] = $this->customer_model->debug_data;
		
		
		$temp = '';
		
		for($i=0;$i<count($this->data['blocks']['customers']);$i++){
			
			$name = $this->data['blocks']['customers'][$i]['name'];
			$alphabet = $name[0];
				if($temp != $alphabet){
					if($i!=0){
					 $str .='</div>';
					}
					$str .=' <div class="row">
						 <div class="col-md-12 alphabet">'.$alphabet .'</div>';
				}
				
				$str .='<div class="customer-list row">
                  <div class="pull-left col-md-2"> <img src="img/avatar.png" alt="" title="" width="42"/> </div>
                  <div class="pull-left col-md-10 customer-info">
                    <p><a href="'.$this->data['vars']['site_url'].'admin/customer/profile/'.$this->data['blocks']['customers'][$i]['id'].'"><span>'.$this->data['blocks']['customers'][$i]['name'].'</span></a> (+47) '.$this->data['blocks']['customers'][$i]['number'].' <br>
                      <a href="mailto:'.$this->data['blocks']['customers'][$i]['email'].'">'.$this->data['blocks']['customers'][$i]['email'].'</a></p>
                  </div>
                 </div>';				
			
			 
				if($i == (count($this->data['blocks']['customers'])-1)){
					 $str .='</div>';
				}
				
				$temp = $alphabet ;
				
		  }//for
		  
		  $this->data['lists']['customers'] =  $str;
		  $this->data['visible']['wi_customers'] = 0;
		}
		else{
			$this->data['visible']['wi_customers'] = 0;
		}
		
		$this->data['visible']['wi_customers'] = 0;
	 }
	 	
     /**
      * get orders of a customer (orders history)
      */
     function __getOrders(){
 		
        //profiling
        $this->data['controller_profiling'][] = __function__;
		
		$customer_id = ($this->uri->segment(4)) ? $this->uri->segment(4) : $this->session->userdata['pos_customer_id'];
		
		
		//get results
		$this->data['reg_blocks'][] = 'orders';
		$this->data['blocks']['orders'] = $this->orders_model->getCustomerOrderhistory($customer_id);
		$this->data['debug'][] = $this->orders_model->debug_data;
		
		
		$orderhistory = array();
		if(count($this->data['blocks']['orders']) > 0)
		{
			foreach($this->data['blocks']['orders'] as $orders)
			{
				$odate=date('Y-m-d',strtotime($orders['order_time']));
				$orderhistory[$odate][]=$orders;
			}
		}
		
		
		$customerorderdetails='';
		if(count($orderhistory) > 0)
		{
			foreach($orderhistory as $odate=>$orderitems)
			{
				$datetitle=date('l, jS F Y',strtotime($odate));
				$customerorderdetails.='<div class="row">
				<div class="col-md-12 alphabet">'.$datetitle.'</div>';
				if(count($orderitems) > 0)
				{
					foreach($orderitems as $oitems)
					{
						$o_time=$datetitle=date('H:i',strtotime($oitems['order_time']));
						
							$company = ($oitems['type'] == 'shop') ?  $oitems['partner_branch'] :  $this->data['settings_company']['company_name'];
							
							$branch =  ($company == $this->data['settings_company']['company_name']) ?  '('.$company .')':  (($company != $this->session->userdata['partner_branch_name']) ? '('.$company .')' :  ''); 
						
						
						$amount = ($oitems['changed_amount']!='') ? $oitems['changed_amount'] :  $oitems['total_amount'];
						
						
						$customerorderdetails.='<div class="order-list row">
						<div class="col-md-9">
						<p><a  href="'.$this->data['vars']['site_url'].'admin/orders/'.$oitems['id'].'"><span class="green-text"> #'.$oitems['id'].' '.$branch.'</span></a> kr '.$amount.'
						</p>
					   </div>                
					   <div class="col-md-3 text-right">
						<p>'.$o_time.'</p>
					   </div>
						</div>';
					}
				}
				$customerorderdetails.='</div>';
			}
		}
			
		$this->data['lists']['customerorderdetails']=$customerorderdetails;
		
		 $subscription = $this->payments_model->getSaldostatus($customer_id);
		
		
		$data=$this->customer_model->getCustomerinfo($customer_id);

		
	
		if(intval($subscription) > 0)
		{
		
			$this->data['lists']['subscription']=' <input onclick="checkAbonnement();" type="radio"  value="Ja" class="form-control" checked name="subscribe"  > Ja&nbsp;&nbsp;
			  <input type="radio"  value="Nei" onclick="checkAbonnement();"   class="form-control" name="subscribe" >Nei';
			  
			  if($data['invoicestatus'] == '1')
			  {
				$this->data['lists']['subscription1']=' <input  onclick="checkfaktura();" type="radio"  value="Ja"   class="form-control" checked name="subscribe1"  > Ja&nbsp;&nbsp;
			  <input type="radio"  value="Nei"   onclick="checkfaktura();"  class="form-control" name="subscribe1" >Nei';
			  }
			  else
			  {
				$this->data['lists']['subscription1']=' <input  onclick="checkfaktura();" type="radio"  value="Ja" class="form-control"   name="subscribe1" > Ja&nbsp;&nbsp;<input type="radio"  value="Nei" checked onclick="checkfaktura();" class="form-control" name="subscribe1" >Nei';
			  }
			  
			  
			  
		}
		else
		{
			$this->data['lists']['subscription']=' <input type="radio" onclick="checkAbonnement();" value="Ja"   class="form-control"  name="subscribe"  > Ja&nbsp;&nbsp;
			  <input type="radio"  value="Nei" checked onclick="checkAbonnement();"  class="form-control" name="subscribe" >Nei';
			  
			  if($data['invoicestatus'] == '1')
			  {
				$this->data['lists']['subscription1']=' <input  onclick="checkfaktura();" type="radio"  value="Ja"   class="form-control" checked name="subscribe1"  > Ja&nbsp;&nbsp;
			  <input type="radio"  value="Nei"   onclick="checkfaktura();"  class="form-control" name="subscribe1" >Nei';
			  }
			  else
			  {
				$this->data['lists']['subscription1']=' <input  onclick="checkfaktura();" type="radio"  value="Ja" class="form-control"   name="subscribe1" > Ja&nbsp;&nbsp;<input type="radio"  value="Nei" checked onclick="checkfaktura();" class="form-control" name="subscribe1" >Nei';
			  }
		}
		
			if($data['autofil_invoice'] == '')
			{
				$this->data['lists']['fakturaoption']='<select name="autofil_faktura_sel" onchange="checkFakturaValue(this.value);" id="autofil_faktura_sel"><option  value="Ja">Ja</option><option selected value="Nei" >Nei</option></select>';
			}
			else
			{
				$this->data['lists']['fakturaoption']='<select  name="autofil_faktura_sel" onchange="checkFakturaValue(this.value);" id="autofil_faktura_sel"><option selected value="Ja">Ja</option><option  value="Nei" >Nei</option></select>';
			}
			

		
		if(count($this->data['blocks']['orders'])> 0){
			$this->data['visible']['wi_customer_orders'] = 1;
		}
		else{
			$this->data['visible']['wi_orders_none'] = 1;
		}
	 }
	
	function __updateprofile()
	{
		  $customer = $this->session->userdata['pos_customer_id'];
	
			//	echo '<pre>';print_r($this->session->userdata['customer']['email']);exit;
		   //$customer = $this->session->userdata['pos_customer_id'];
		  
		 
		  
		   if(count($_POST) > 0)
			{
			
				$cid=$customer;
				$firstname=$this->input->post('firstname');
				$lastname=$this->input->post('lastname');
				$street_line_1=$this->input->post('street_line_1');
				$street_line_2=$this->input->post('street_line_2');
				$floor=$this->input->post('floor');
				$calling_bell=$this->input->post('calling_bell');
				$zip=$this->input->post('zip');
				$email=$this->input->post('email');
				$confirm_email=$this->input->post('confirm_email');
				$subscribe=$this->input->post('subscribe');
				
				if($email != '')
				{
						if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
								$error = array('response'=>"error",'message'=>$this->data['lang']['lang_invalid_email']);
								echo json_encode($error);exit;
							}
							$sql="SELECT * FROM a_email WHERE email='".trim($email)."' AND customer!='".$cid."'";
							$query=$this->db->query($sql);
							$status=$query->num_rows();
							if(intval($status) > 0)
							{
								$error = array('response'=>"error",'message'=>"E-post id finnes allerede.");
								echo json_encode($error);exit;
							}
				}
			
				//if(trim($zip) != '')
				//{
					//$result=$this->general_model->isValidZip($zip); //check its a valid zip
					//if($result)
					//{
						
						$fields=array('firstname'=>$firstname,'lastname'=>$lastname);
						$this->customer_model->addCustomer($fields,$cid);
						
						if($email != '')
						{
							$this->customer_model->updateEmail($email,$cid);
						}
						$this->customer_model->updateAddress($street_line_1,$street_line_2,$floor,$calling_bell,$zip,$cid);
						
						if($subscribe == 'Ja')
						{
							$saldostatus = $this->payments_model->getSaldostatus($cid);
							if(intval($saldostatus) == 0)
							{
								$paymentarray=array(
								'type'=>'in',
								'in_type'=>'visa',
								'in_status'=>'paid',
								'customer'=>$cid,
								'amount'=>0,
								'regtime'=>date('Y-m-d H:i:s'),
								);
								$this->payments_model->addCustomerPayment($paymentarray);
							}
							$this->payments_model->updateCustomerBalance($cid,0,'paid');
							
						}
						
						
						
						
						
						
	
						
					//}
					
				//}
			
		
			}
		
		$data=$this->customer_model->getCustomerinfo($customer);
		 //$data['partner_branch_name_short'] = substr($data['partner_branch'] ,0,3);
		 $newdata = array('customer'  => $data);
		 $this->session->set_userdata($newdata);
		 
		 $success = array('response'=>"success",'message'=>$this->data['lang']['lang_profile_update']);
		 
		 echo json_encode($success);exit;
		 
	}
	
	
	/*edit customer profile*/
	function __profile()
	{
	
	      $customer = ($this->uri->segment(4)) ? $this->uri->segment(4) : $this->session->userdata['pos_customer_id'];

			//	echo '<pre>';print_r($this->session->userdata['customer']['email']);exit;
		   //$customer = $this->session->userdata['pos_customer_id'];
		  
		 
		   if(count($_POST) > 0)
			{
				
				
				$cid=$customer;
				$firstname=$this->input->post('firstname');
				$lastname=$this->input->post('lastname');
				$street_line_1=$this->input->post('street_line_1');
				$street_line_2=$this->input->post('street_line_2');
				$floor=$this->input->post('floor');
				$calling_bell=$this->input->post('calling_bell');
				$zip=$this->input->post('zip');
				
				$email=$this->input->post('email');
				$confirm_email=$this->input->post('confirm_email');
				$subscribe=$this->input->post('subscribe');
				$subscribe1=$this->input->post('subscribe1');
				$subscribe2=$this->input->post('autofil_faktura_sel');
				$faktura_value=$this->input->post('autofil_faktura_value');
				
				
			
				
				
				if($email != '')
				{
					if($email != $confirm_email )
					{
							//$this->session->set_flashdata('notice-error', 'Samsvarer ikke over e-postadresse');
							//redirect('/admin/customer/profile/');
							//exit;
					}
					else
					{
						if($email != '')
						{
							if (filter_var($email, FILTER_VALIDATE_EMAIL) === false)
							{
							$this->session->set_flashdata('notice-error', $this->data['lang']['lang_invalid_email']);
							redirect('/admin/customer/profile/');
							exit;
							
							}
							$sql="SELECT * FROM a_email WHERE email='".trim($email)."' AND customer!='".$cid."'";
							$query=$this->db->query($sql);
							$status=$query->num_rows();
							if(intval($status) > 0)
							{
								$this->session->set_flashdata('notice-error', "E-post id finnes allerede.");
								redirect('/admin/customer/profile/');
								exit;
							}
						}
					}
					
					
				}
				
				//if(trim($zip) != '')
				//{
				//	$result=$this->general_model->isValidZip($zip); //check its a valid zip
					//if($result)
					//{
					
							$fields=array('firstname'=>$firstname,'lastname'=>$lastname);
							
						
						
						$this->customer_model->addCustomer($fields,$cid);
						if($email != '')
						{
							$this->customer_model->updateEmail($email,$cid);
						}
						$this->customer_model->updateAddress($street_line_1,$street_line_2,$floor,$calling_bell,$zip,$cid);
						
						if(isset($_POST['company']))
						{
							if($cid > 0)
							{
								$this->customer_model->addcompanyCustomer($cid,$_POST['company']);
							}
							
						}	
						
						
						if($subscribe == 'Ja' && $subscribe1 == 'Ja' && $subscribe2 == 'Nei')
						{
							$saldostatus = $this->payments_model->getSaldostatus($cid);
							if(intval($saldostatus) == 0)
							{
								$paymentarray=array(
								'type'=>'in',
								'in_type'=>'visa',
								'in_status'=>'paid',
								'customer'=>$cid,
								'amount'=>0,
								'regtime'=>date('Y-m-d H:i:s'),
								);
								$this->payments_model->addCustomerPayment($paymentarray);
							}
							$this->payments_model->updateCustomerBalance($cid,0,'paid');
							
						}
						
						
						$data = $this->payments_model->getAccountBalance($cid);
						
					
						
						//if($subscribe == 'Ja')
						//{
							if($subscribe1 == 'Ja')
							{
							
								if($subscribe2 == 'Ja')
								{
									if(intval($data['paid']) <= 0 && intval($data['pending']) <= 0)
									{
										if(intval($faktura_value) > 0)
										{
											$paymentarray=array(
											'type'=>'in',
											'in_type'=>'invoice',
											'in_status'=>'pending',
											'customer'=>$cid,
											'amount'=>$faktura_value,
											'regtime'=>date('Y-m-d H:i:s'),
											);
											$this->payments_model->addCustomerPayment($paymentarray);
											$this->payments_model->updateCustomerBalance($cid,$faktura_value,'pending');
										}
										
									}
									
									$this->customer_model->addAutofilInvoice($cid,$faktura_value);
								}
								else
								{
									$this->customer_model->addAutofilInvoice($cid,0);
								}
							}
							else
							{
									
									$this->customer_model->addAutofilInvoice($cid,0);
									
							}
							
							if($subscribe == 'Ja' && $subscribe1 == 'Ja')
							{
								$this->customer_model->updateInvoiceStatus($cid,1);
							}
							else
							{
								$this->customer_model->updateInvoiceStatus($cid,0);
							}
							
							
							if($subscribe == 'Ja')
							{
								$saldostatus = $this->payments_model->getSaldostatus($cid,false,false);
								if($saldostatus)
								{	
									$this->customer_model->updateSaldostatus($cid,1);
								}
								else
								{
									$this->payments_model->updateCustomerBalance($cid,0,'paid','credit','1');
								}
							}
							else
							{
								//Nei
								$saldostatus = $this->payments_model->getSaldostatus($cid,false,false);
								if($saldostatus)
								{	
									$this->customer_model->updateSaldostatus($cid,0);
								}
								
								$this->customer_model->addAutofilInvoice($cid,0);								
							}
						
						//}
				
						
						
						$this->session->set_flashdata('notice-success', $this->data['lang']['lang_profile_update']);
						
						//$data=$this->general_model->getAvailableArea($zip);
	
						/*if(!empty($data)){
							$newdata = array('zipdata'  => $data);
							$this->session->set_userdata($newdata);
					
							$newdata = array('service_available'  => '1');
							$this->session->unset_userdata('service_available');
							$this->session->set_userdata($newdata);
							
							
						}
						else
						{
							$this->session->set_flashdata('notice-error', $this->data['lang']['lang_service_not_available']);
							$newdata = array('service_available'  => '0');
							$this->session->unset_userdata('service_available');
							$this->session->set_userdata($newdata);
						}*/
					//}
					
				//}
						if(isset($_POST['company']))
						{
							redirect('/admin/customer/companyprofile');
						}
						else
						{
							redirect('/admin/customer/profile/');
						}
						
			
				exit;
		
			}
			//profiling::
			$this->data['controller_profiling'][] = __function__;
			
			
			
		 $data=$this->customer_model->getCustomerinfo($customer);
		 
		 //echo '<pre>';print_r($data);exit;
		 
		//$data['partner_branch_name_short'] = substr($data['partner_branch'] ,0,3);
		
		if($this->session->userdata['partner_branch'] == 14 || $this->session->userdata['partner_branch'] == 28)
		{
		$company= $this->customer_model->getCompanylist();
		$companyid=0;
		
		if(intval($data['company']) > 0)
		{
			$companyid=$data['company'];
		}
		else
		{
			if(isset($_COOKIE['navn_id']))
			{
				$companyid =$_COOKIE['navn_id'];
			}
		}
		
		
		
		
			$companylist='<option value="">-- Select Company --</option>';
			if(count($company) > 0)
			{
				foreach($company as $com)
				{
					if($companyid == $com['id'])
					{
						$companylist.='<option selected="selected" value="'.$com['id'].'">'.$com['name'].'</option>';
					}
					else
					{
						$companylist.='<option value="'.$com['id'].'">'.$com['name'].'</option>';
					}
				}
			}
		$this->data['lists']['companylist']=$companylist;
		 
				 
		}
		
		
		 $newdata = array('customer'  => $data);
		 $this->session->set_userdata($newdata);
		
	}
	
	function __updateCustomerprofile($postrequest)
	{
	
	     // $customer = ($this->uri->segment(4)) ? $this->uri->segment(4) : $this->session->userdata['pos_customer_id'];
	
			//	echo '<pre>';print_r($this->session->userdata['customer']['email']);exit;
		   //$customer = $this->session->userdata['pos_customer_id'];
		  
		  
		   if(count($_POST) > 0)
			{
				$cid=$postrequest['customer'];
				$firstname=$postrequest['firstname'];
				$lastname=$postrequest['lastname'];
				$street_line_1=$postrequest['street_line_1'];
				$street_line_2=$postrequest['street_line_2'];
				$floor=$postrequest['floor'];
				$calling_bell=$postrequest['calling_bell'];
				$zip=$postrequest['zip'];
				$mobile=$postrequest['mobile'];
				
				 $email=$postrequest['email'];
				$confirm_email=$postrequest['confirm_email'];
				$subscribe=$postrequest['subscribe'];
			
				if($email != '')
						{
							if (filter_var($email, FILTER_VALIDATE_EMAIL) === false)
							{
							$this->session->set_flashdata('notice-error', $this->data['lang']['lang_invalid_email']);
							redirect('/admin/customer/profile/');
							exit;
							
							}
							$sql="SELECT * FROM a_email WHERE email='".trim($email)."' AND customer!='".$cid."'";
							$query=$this->db->query($sql);
							$status=$query->num_rows();
							if(intval($status) > 0)
							{
								$this->session->set_flashdata('notice-error', "E-post id finnes allerede.");
								redirect('/admin/customer/profile/');
								exit;
							}
						}
				
				if($cid)
				{
					//$result=$this->general_model->isValidZip($zip); //check its a valid zip
					//if($result)
					//{
						
						$fields=array('firstname'=>$firstname,'lastname'=>$lastname,'employee'=>$this->session->userdata['current_staff']);
						
						$this->customer_model->addCustomer($fields,$cid);
						
						if($email != '')
						{
							$this->customer_model->updateEmail($email,$cid);
						}
						
						if($mobile != '')
						{
							$this->customer_model->updateMobile($mobile,$cid);
						}
						
						
						$this->customer_model->updateAddress($street_line_1,$street_line_2,$floor,$calling_bell,$zip,$cid);
						
						if($subscribe == 'Ja')
						{
							$saldostatus = $this->payments_model->getSaldostatus($cid);
							if(intval($saldostatus) == 0)
							{
								$paymentarray=array(
								'type'=>'in',
								'in_type'=>'visa',
								'in_status'=>'paid',
								'customer'=>$cid,
								'amount'=>0,
								'regtime'=>date('Y-m-d H:i:s'),
								);
								$this->payments_model->addCustomerPayment($paymentarray);
							}
							$this->payments_model->updateCustomerBalance($cid,0,'paid');
							
						}
						
						
						
						$this->session->set_flashdata('notice-success', $this->data['lang']['lang_profile_update']);
						/*
						$data=$this->general_model->getAvailableArea($zip);
	
						if(!empty($data)){
							$newdata = array('zipdata'  => $data);
							$this->session->set_userdata($newdata);
					
							$newdata = array('service_available'  => '1');
							$this->session->unset_userdata('service_available');
							$this->session->set_userdata($newdata);
							
							
						}
						else
						{
							$this->session->set_flashdata('notice-error', $this->data['lang']['lang_service_not_available']);
							$newdata = array('service_available'  => '0');
							$this->session->unset_userdata('service_available');
							$this->session->set_userdata($newdata);
						}*/
					//}
					
				}
			//redirect('/admin/customer/profile/');
				//exit;
		
			}
			//profiling::
			$this->data['controller_profiling'][] = __function__;
			
			
			
		 $data=$this->customer_model->getCustomerinfo($cid);
		// print_r($data);exit;
		//$data['partner_branch_name_short'] = substr($data['partner_branch'] ,0,3);
		//print_r($data);exit;
		
		 $newdata = array('customer'  => $data);
		 $this->session->set_userdata($newdata);
		 
		 $response = array('response'=>"success","customer"=>$cid,"message" => 'Account has been created successfully...!');
				echo json_encode($response);exit;
		
	}
	
	
	function __addcustomer()
	{
	
	
	
		if(count($_POST) > 0)
		{
			if(intval($_POST['customer']) > 0)
			{
				self::__profile($_POST);
				exit;
				
			}
		
			$firstname=trim($_POST['firstname']);
			$lastname=trim($_POST['lastname']);
			$email=trim($_POST['email']);
			$mobile=trim($_POST['mobile']);
			$street_line_1=trim($_POST['street_line_1']);
			$street_line_2=trim($_POST['street_line_2']);
			$floor=trim($_POST['floornr']);
			$calling_bell=trim($_POST['calling_bell']);
			$zip=trim($_POST['zip']);
			$subscribe=trim($_POST['subscribe']);
			
			$subscribe1=trim($_POST['subscribe1']);
			$subscribe2=trim($_POST['subscribe2']);
			$faktura_value=trim($_POST['fakturavalue']);
			
		
			
			
			$password=self::get_random_password();
			
			$company=intval($_POST['company']);
			
			
			if($email != '')
			{
				if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
				$error = array('response'=>$this->data['lang']['lang_invalid_email']);
				echo json_encode($error);exit;
				}
				$sql="SELECT * FROM a_email WHERE email='".trim($email)."'";
				$query=$this->db->query($sql);
				$status=$query->num_rows();
				if(intval($status) > 0)
				{
					$error = array('response'=>"E-post id finnes allerede.");
					echo json_encode($error);exit;
				}
			}
			
			
			
			$sql="SELECT * FROM a_phone WHERE number='".trim($mobile)."'";
			$query=$this->db->query($sql);
			$status=$query->num_rows();
			if(intval($status) > 0)
			{
				$error = array('response'=>"Det finnes mobilnummer allerede");
				echo json_encode($error);exit;
			}
			$firstdigit=substr($mobile, 0, 1);
			$mobile_array=array('4','9');
			$landline_array=array('2','3','5','6','7');
			$phone_type='mobile';
			if(in_array($firstdigit,$mobile_array))
			{
				$phone_type='mobile';
			}
			if(in_array($firstdigit,$landline_array))
			{
				$phone_type='home';
			}
			
			/*$sql="SELECT * FROM  a_zip WHERE id='".trim($zip)."'";
			$query=$this->db->query($sql);
			$status=$query->num_rows();
			if(intval($status) == 0)
			{
				$error = array('response'=>"Ikke en gyldig zip");
				echo json_encode($error);exit;
			}*/
			
			$gender='male';
			if(isset($this->session->userdata['customer']['gender']))
			{
				if(trim($this->session->userdata['customer']['gender']) != '')
				{
					$gender=strtolower(trim($this->session->userdata['customer']['gender']));
				}
			}
			$cus_id=0;
			$fields = array(
				'firstname' => $firstname,
				'lastname' => $lastname,
				'sex'=>$gender,
				'password' => md5($password),
				'employee'=>$this->session->userdata['current_staff'],
				'p_b_account_created'=>$this->session->userdata['partner_branch'],
				'partner_active_customer' => '1'
			);
			
			$cus_id = $this->customer_model->addCustomer($fields,$cus_id);
				
				if($email != '')
				{
					$fields = array(
					'customer' => $cus_id,
					'email' => $email
					);
					$email_status=$this->customer_model->addEmail($fields);
				}
			
				$fields = array(
						'customer' => $cus_id,
						'number' => $mobile,
						'type'=>$phone_type
				);
				$this->customer_model->addPhone($fields);
			
				$this->customer_model->updateAddress($street_line_1,$street_line_2,$floor,$calling_bell,$zip,$cus_id);
				$this->customer_model->updateCustomer($cus_id);
				
						if($subscribe == 'Ja' && $subscribe1 == 'Ja' && $subscribe2 == 'Nei')
						{
							$saldostatus = $this->payments_model->getSaldostatus($cus_id);
							if(intval($saldostatus) == 0)
							{
								$paymentarray=array(
								'type'=>'in',
								'in_type'=>'visa',
								'in_status'=>'paid',
								'customer'=>$cus_id,
								'amount'=>0,
								'regtime'=>date('Y-m-d H:i:s'),
								);
								$this->payments_model->addCustomerPayment($paymentarray);
							}
							$this->payments_model->updateCustomerBalance($cus_id,0,'paid');
							
						}
				
				
				$cid=$cus_id;
							//if($subscribe == 'Ja')
						//{
							if($subscribe1 == 'Ja')
							{
							
								if($subscribe2 == 'Ja')
								{
									if(intval($data['paid']) <= 0 && intval($data['pending']) <= 0)
									{
										if(intval($faktura_value) > 0)
										{
											$paymentarray=array(
											'type'=>'in',
											'in_type'=>'invoice',
											'in_status'=>'pending',
											'customer'=>$cid,
											'amount'=>$faktura_value,
											'regtime'=>date('Y-m-d H:i:s'),
											);
											$this->payments_model->addCustomerPayment($paymentarray);
											$this->payments_model->updateCustomerBalance($cid,$faktura_value,'pending');
										}
										
									}
									
									$this->customer_model->addAutofilInvoice($cid,$faktura_value);
								}
								else
								{
									$this->customer_model->addAutofilInvoice($cid,0);
								}
							}
							else
							{
									
									$this->customer_model->addAutofilInvoice($cid,0);
									
							}
							
							if($subscribe == 'Ja' && $subscribe1 == 'Ja')
							{
								$this->customer_model->updateInvoiceStatus($cid,1);
							}
							else
							{
								$this->customer_model->updateInvoiceStatus($cid,0);
							}
							
							
							if($subscribe == 'Ja')
							{
								$saldostatus = $this->payments_model->getSaldostatus($cid,false,false);
								if($saldostatus)
								{	
									$this->customer_model->updateSaldostatus($cid,1);
								}
								else
								{
									$this->payments_model->updateCustomerBalance($cid,0,'paid','credit','1');
									
									
								}
							}
							else
							{
								//Nei
								$saldostatus = $this->payments_model->getSaldostatus($cid,false,false);
								if($saldostatus)
								{	
									$this->customer_model->updateSaldostatus($cid,0);
								}
								
								$this->customer_model->addAutofilInvoice($cid,0);								
							}
						
						//}
				
				//$this->customer_model->addcompanyCustomer($cus_id,$company);
				
						if(isset($_POST['company']))
						{
							if($cus_id > 0)
							{
								$this->customer_model->addcompanyCustomer($cus_id,$_POST['company']);
							}
							
						}
				
				$newdata = array('pos_customer_id'  => $cus_id);
				$this->session->unset_userdata('pos_customer_id');
				$this->session->set_userdata($newdata);
				
				if($company > 0)
				{
					$mobileinfo=$this->customer_model->companycustomermobileinfo($mobile);	
				}
				else
				{
					$mobileinfo=$this->customer_model->customermobileinfo($mobile);	
				}
				$newdata = array('customer'  => $mobileinfo);
				$this->session->set_userdata($newdata);
				
				
				
				
				$response = array('response'=>"success","customer"=>$cus_id,"message" => 'Account has been created successfully...!');
				echo json_encode($response);exit;
				
			
		}
		//echo '<pre>';print_r($_POST);exit;
	}
	
	function get_random_password($chars_min=6, $chars_max=8, $use_upper_case=false, $include_numbers=false, $include_special_chars=false)
    {
        $length = rand($chars_min, $chars_max);
        $selection = 'aeuoyibcdfghjklmnpqrstvwxz';
        if($include_numbers) {
            $selection .= "1234567890";
        }
        if($include_special_chars) {
            $selection .= '!@\"#$%&[]{}';
        }
        $password = "";
        for($i=0; $i<$length; $i++) {
            $current_letter = $use_upper_case ? (rand(0,1) ? strtoupper($selection[(rand() % strlen($selection))]) : $selection[(rand() % strlen($selection))]) : $selection[(rand() % strlen($selection))];            
            $password .=  $current_letter;
        }                
      return $password;
    }
	
	
	function __mobileVerification()
	{

	//echo '<pre>';print_r($_POST);exit;
		
		if(count($_POST) > 0)
		{
			if(isset($_POST['mobile']) && intval($_POST['mobile']) > 0)
			{
				$mobile=trim($_POST['mobile']);
				if(!is_digits($mobile))
				{
					$this->session->set_flashdata('notice-error', 'Invalid mobile number.');
					redirect('/admin/customer');
					exit;
				
				}
				if(!is_numeric($mobile)) {
				
					$this->session->set_flashdata('notice-error', 'Invalid mobile number.');
					redirect('/admin/customer');
					exit;
				}
				
				if(strlen($mobile) !== 8) {
					$this->session->set_flashdata('notice-error', 'Dette tallet er ikke et Norge nummer.');
					//redirect('/admin/customer');
					//exit;
				}
				
				$firstdigit=substr($mobile, 0, 1);
				$firstdigitarray=array('2','3','4','5','6','7','9');
				
				if(!in_array($firstdigit,$firstdigitarray)) {
					$this->session->set_flashdata('notice-error', 'Dette tallet er ikke et Norge nummer.');
					//redirect('/admin/customer');
					//exit;
				}
				
				$mobileinfo=$this->customer_model->customermobileinfo($mobile);	
				$newdata = array('customer'  => $mobileinfo);
				$this->session->set_userdata($newdata);
				if(intval($mobileinfo['id']) > 0)
				{
				
					$cus_id=trim($mobileinfo['id']);
					$ccstatus=$this->customer_model->validateCompanycustomer($cus_id);	
					if($ccstatus)
					{
						$mobileinfo=$this->customer_model->companycustomermobileinfo($mobile);	
						$newdata = array('customer'  => $mobileinfo);
						$this->session->set_userdata($newdata);
					}
					
					
					$newdata = array('pos_customer_id'  => $cus_id);
					$this->session->unset_userdata('pos_customer_id');
					$this->session->set_userdata($newdata);
					
					$this->cart->destroy();
				
				    if($this->session->userdata['partner_branch'] != 1000){
						redirect('/admin/products');
					}
					else{
						redirect('/admin/smartlaundry');
					}
					exit();
				}
				else
				{
					if(!$mobileinfo)
					{
						$newdata = array('customer'  => array('mobile'=>$mobile));
						$this->session->set_userdata($newdata);
					}
					 
					
					if(strlen($mobile) !== 8) {
						$this->session->set_flashdata('notice-error', 'Dette tallet er ikke et Norge nummer.');
						redirect('/admin/customer');
						exit;
					}
					
					$firstdigit=substr($mobile, 0, 1);
					$firstdigitarray=array('2','3','4','5','6','7','9');
					
					if(!in_array($firstdigit,$firstdigitarray)) {
						$this->session->set_flashdata('notice-error', 'Dette tallet er ikke et Norge nummer.');
						redirect('/admin/customer');
						exit;
					}
				}
				
			}
			else
			{
				//
				if(isset($_POST['navn_id']) && intval($_POST['navn_id']) > 0)
				{
					$mobileinfo=$this->customer_model->customerAccountinfo($_POST['navn_id']);

					$newdata = array('customer'  => $mobileinfo);
					$this->session->set_userdata($newdata);
				}
				else
				{
					if(isset($_POST['navn']) && trim($_POST['navn']) != '')
					{
						$newdata = array('customer'  => array('firstname'=>$_POST['navn']));
						$this->session->set_userdata($newdata);
						
						redirect('/admin/customer');
						exit;
					}
				}
			}
		}
		else
		{
			if($this->session->userdata['pos_customer_id'])
			{
				//redirect('/admin/customer/profile');
				//	exit();
			}
		}
		
		
		 
		$emailreq='';
		if(isset($this->session->userdata['customer']['email']))
		{
			if($this->session->userdata['customer']['email'] != '')
			{
				$emailreq='required="required"';
			}
		}
		
		
		$this->data['lists']['email_field'] = '<tr>
                               <td>E-post</td>
                               <td><div class="col-sm-12  no-padding-both">
								<input type="email"  value="'.$this->session->userdata['customer']['email'].'"  maxlength="100" class="form-control" name="email" id="email" >
								<input type="hidden"  value="'.$mobileinfo['id'].'"  maxlength="100" class="form-control" name="customer_id" id="customer_id">
								
								</div></td>
                                </tr>';
								/*'<tr>
                                  <td>Bekreft E-post</td>
                                  <td><div class="col-sm-12  no-padding-both">
								  <input type="email"  value=""  maxlength="100" class="form-control" name="confirm_email" id="confirm_email" '.$emailreq.'>
									</div></td>
                                </tr>';*/
								
$this->data['lists']['subscription']=' <input type="radio" onclick="checkAbonnement();"  value="Ja"   class="form-control"  name="subscribe"  > Ja&nbsp;&nbsp;
<input type="radio"  onclick="checkAbonnement();"  value="Nei" checked   class="form-control" name="subscribe" >Nei';
	
$this->data['lists']['subscription_faktura']=' <input type="radio" checked onclick="checkfaktura();"  value="Ja"   class="form-control"  name="subscribe1"  > Ja&nbsp;&nbsp;
<input type="radio"  value="Nei"    onclick="checkfaktura();"  class="form-control" name="subscribe1" >Nei';
	
		  
		if(isset($_POST['navn_id']) && intval($_POST['navn_id']) > 0)
		{
			$customer_id=intval($_POST['navn_id']);
			$subscription = $this->payments_model->getSaldostatus($customer_id);
			
			if(intval($subscription) > 0)
			{
				$this->data['lists']['subscription']=' <input disabled type="radio"  value="Ja"   class="form-control" checked name="subscribe"  > Ja&nbsp;&nbsp;
				<input type="radio"  value="Nei" disabled   class="form-control" name="subscribe" >Nei';
				
				$this->data['lists']['subscription_faktura']=' <input type="radio"  onclick="checkfaktura();" checked value="Ja" disabled  class="form-control"  name="subscribe1"  > Ja&nbsp;&nbsp;
<input type="radio"  value="Nei" onclick="checkfaktura();" disabled class="form-control" name="subscribe1" >Nei';
				
			}
		}				
		
	
		
		
	}

	function __company()
   	{
	
	
		if(count($_POST) > 0)
		{
	
		//print_r($_POST);


			if(isset($_POST['company']))
			{
				$newdata = array('company'=> $_POST['company']);
				$this->session->set_userdata($newdata);
			}

			if(isset($_POST['cart_type']))
			{
				$newdata = array('carttype'=> $_POST['cart_type']);
				$this->session->set_userdata($newdata);
			}
			
			if(isset($_POST['customer_type']))
			{
				$newdata = array('companytype'=> $_POST['customer_type']);
				$this->session->unset_userdata('companytype');
				$this->session->set_userdata($newdata);
			}
			
			if(isset($_POST['company_id']))
			{
				$newdata = array('company'=> $_POST['company_id']);
				$this->session->unset_userdata('company');
				$this->session->set_userdata($newdata);
			}
			
				if(isset($_POST['cmobile']))
				{
					$mobile=trim($_POST['cmobile']);
				}
				else if(isset($_POST['mobile']))
				{
					$mobile=trim($_POST['mobile']);
				}
				else if(isset($_POST['cmobile_heatseal']))
				{
					$mobile=trim($_POST['cmobile_heatseal']);
				}
				else if(isset($_POST['new_company_mobile']))
				{
					$mobile=trim($_POST['new_company_mobile']);
				}
				else
				{
					$mobile=trim($_POST['bedrift']);
				}
				
				if(isset($_POST['company']))
				{
				
					$mobileinfo=$this->customer_model->customermobileinfo($mobile);	
					$cus_id=trim($mobileinfo['id']);
					if($cus_id > 0)
					{
					$this->customer_model->addcompanyCustomer($cus_id,$_POST['company']);
					}
					
					
				}	
					
				
		
			
			if(trim($mobile) != '')
			{
				
				if(!is_digits($mobile))
				{
					$this->session->set_flashdata('notice-error', 'Invalid mobile number.');
					redirect('/admin/customer/company');
					exit;
				
				}
				if(!is_numeric($mobile)) {
				
					$this->session->set_flashdata('notice-error', 'Invalid mobile number.');
					redirect('/admin/customer/company');
					exit;
				}
				
				if(strlen($mobile) !== 8) {
					$this->session->set_flashdata('notice-error', 'Dette tallet er ikke et Norge nummer.');
					//redirect('/admin/customer');
					//exit;
				}
				
				$firstdigit=substr($mobile, 0, 1);
				$firstdigitarray=array('2','3','4','5','6','7','9');
				
				if(!in_array($firstdigit,$firstdigitarray)) {
					$this->session->set_flashdata('notice-error', 'Dette tallet er ikke et Norge nummer.');
					//redirect('/admin/customer');
					//exit;
				}
				
				$mobileinfo=$this->customer_model->companycustomermobileinfo($mobile);	

				//print_r($mobileinfo);
				
				$newdata = array('customer'  => $mobileinfo);
				$this->session->set_userdata($newdata);
				
				
				if(intval($mobileinfo['id']) > 0 && intval($mobileinfo['company']) > 0)
				{
				
					$cus_id=trim($mobileinfo['id']);
					
					$ccstatus=$this->customer_model->validateCompanycustomer($cus_id);	
					
					
					if($ccstatus)
					{
						$mobileinfo=$this->customer_model->companycustomermobileinfo($mobile);	
						$newdata = array('customer'  => $mobileinfo);
						
						if(!isset($_POST['customer_type']))
						{
							if($mobileinfo['type'] == 'guest')
							{
								$newdata = array('companytype'=> $mobileinfo['type']);
								$this->session->unset_userdata('companytype');
								$this->session->set_userdata($newdata);
							}
							else
							{
								$newdata = array('companytype'=> 'staff');
								$this->session->unset_userdata('companytype');
								$this->session->set_userdata($newdata);
							}
							
							
							
						}
						
						if(!isset($_POST['company_id']))
						{
							$newdata = array('company'=> $mobileinfo['company']);
							$this->session->unset_userdata('company');
							$this->session->set_userdata($newdata);
						}
			
			
						$this->session->set_userdata($newdata);
					}
					
					$newdata = array('pos_customer_id'  => $cus_id);
					$this->session->unset_userdata('pos_customer_id');
					$this->session->set_userdata($newdata);
					
					$this->cart->destroy();
					
					//session destroy when product added using heatseal
					$this->session->unset_userdata('heatsealcart');
					$this->session->unset_userdata('producttypecart');
					$this->session->unset_userdata('producttype_cart');
					$this->session->unset_userdata('producttypecart1');


				    if($this->session->userdata['partner_branch'] != 1000){
						redirect('/admin/products');
					}
					else{
						redirect('/admin/smartlaundry');
					}

					exit();
				}
				else
				{
				
					if(intval($mobileinfo['id']) > 0)
					{
						$newdata = array('pos_customer_id'  => $mobileinfo['id']);
						$this->session->set_userdata($newdata);
						redirect('/admin/customer/companyprofile');
						exit;
					}
				
					if(!$mobileinfo)
					{
						$mobileinfo1=$this->customer_model->customermobileinfo($mobile);	
						if(intval($mobileinfo1['id']) > 0)
						{
							$newdata = array('customer'  => $mobileinfo1);
							$this->session->set_userdata($newdata);
						}
						else
						{
							$newdata = array('customer'  => array('mobile'=>$mobile));
							$this->session->set_userdata($newdata);
						}
					}
					 
					
					if(strlen($mobile) !== 8) {
						$this->session->set_flashdata('notice-error', 'Dette tallet er ikke et Norge nummer.');
						redirect('/admin/customer/company');
						exit;
					}
					
					$firstdigit=substr($mobile, 0, 1);
					$firstdigitarray=array('2','3','4','5','6','7','9');
					
					if(!in_array($firstdigit,$firstdigitarray)) {
						$this->session->set_flashdata('notice-error', 'Dette tallet er ikke et Norge nummer.');
						redirect('/admin/customer/company');
						exit;
					}
				}
				
			}
		}
		else
		{
			if($this->session->userdata['pos_customer_id'])
			{
				//redirect('/admin/customer/profile');
				//exit();
			}
		}
		
		$emailreq='';
		if(isset($this->session->userdata['customer']['email']))
		{
			if($this->session->userdata['customer']['email'] != '')
			{
				$emailreq='required="required"';
			}
		}
		
		
		$this->data['lists']['email_field'] = '<tr>
                               <td>E-post</td>
                               <td><div class="col-sm-12  no-padding-both">
								<input type="email"  value="'.$this->session->userdata['customer']['email'].'"  maxlength="100" class="form-control" name="email" id="email" >
								<input type="hidden"  value="'.$this->session->userdata['customer']['id'].'"  maxlength="100" class="form-control" name="customer_id" id="customer_id">
								
								</div></td>
                                </tr>';
								/*'<tr>
                                  <td>Bekreft E-post</td>
                                  <td><div class="col-sm-12  no-padding-both">
								  <input type="email"  value=""  maxlength="100" class="form-control" name="confirm_email" id="confirm_email" '.$emailreq.'>
									</div></td>
                                </tr>';*/
								
$this->data['lists']['subscription']=' <input type="radio" onclick="checkAbonnement();"  value="Ja"   class="form-control"  name="subscribe"  > Ja&nbsp;&nbsp;
<input type="radio"  onclick="checkAbonnement();"  value="Nei" checked   class="form-control" name="subscribe" >Nei';
	
$this->data['lists']['subscription_faktura']=' <input type="radio" checked onclick="checkfaktura();"  value="Ja"   class="form-control"  name="subscribe1"  > Ja&nbsp;&nbsp;
<input type="radio"  value="Nei"    onclick="checkfaktura();"  class="form-control" name="subscribe1" >Nei';
	
		  
		if(isset($_POST['navn_id']) && intval($_POST['navn_id']) > 0)
		{
			$customer_id=intval($_POST['navn_id']);
			$subscription = $this->payments_model->getSaldostatus($customer_id);
			
			if(intval($subscription) > 0)
			{
				$this->data['lists']['subscription']=' <input disabled type="radio"  value="Ja"   class="form-control" checked name="subscribe"  > Ja&nbsp;&nbsp;
				<input type="radio"  value="Nei" disabled   class="form-control" name="subscribe" >Nei';
				
				$this->data['lists']['subscription_faktura']=' <input type="radio"  onclick="checkfaktura();" checked value="Ja" disabled  class="form-control"  name="subscribe1"  > Ja&nbsp;&nbsp;
<input type="radio"  value="Nei" onclick="checkfaktura();" disabled class="form-control" name="subscribe1" >Nei';
				
			}
		}	
		
		
	
								
	
		$company= $this->customer_model->getCompanylist();
		if(isset($_COOKIE['navn_id']))
		{
			$companyid =$_COOKIE['navn_id'];
		
			$companylist='';
			if(count($company) > 0)
			{
				foreach($company as $com)
				{
					if($companyid == $com['id'])
					{
						$companylist.='<option selected="selected" value="'.$com['id'].'">'.$com['name'].'</option>';
					}
					else
					{
						$companylist.='<option value="'.$com['id'].'">'.$com['name'].'</option>';
					}
					
				}
			}
		}
		else
		{
			$companylist='<option value="">-- Select Company --</option>';
			if(count($company) > 0)
			{
				foreach($company as $com)
				{
					$companylist.='<option value="'.$com['id'].'">'.$com['name'].'</option>';
				}
			}
		}
		$this->data['lists']['companylist']=$companylist;						
		
		
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
	
	function flmLogOut()
	{
        //profiling
        $this->data['controller_profiling'][] = __function__;
		$customer=$this->session->userdata['customer']['id'];
		$newdata = array('skipsaldo'  =>array($customer=>''));
		$this->session->set_userdata($newdata);  
		
		$this->cart->destroy();
				$array_items = array(
				'cart_contents' => '',
				'total_price' => '',
				'subtotal' => '',
				'cartdata' => '',
				'heatsealcart' => '',
				'producttypecart' => '',
				'producttypecart1' => '',
				'producttype_cart' => '',
				'producttypeskip' => ''
				);
				$this->session->set_userdata($array_items);
				
				
		//delete all session data
        $array_items = array(
		'customer' => '', 
		'cartdata' => '',
		'zipdata' => '',
		'service_available' => '',
		'pos_customer_id' => '',
		'cart_contents' => '',
		'total_price' => '',
		'subtotal' => '',
		);
		$this->session->unset_userdata($array_items);
        //redirect to login page
        redirect('/');
    }
	
		// -- add saldo ----------------------------------------------------------------------------------------------
    /**
     * add a new customer payment
     *
     *
     * @param array $thedata normally the $_post array
     * @return array
     */
	 
	function __addsaldo()
	{
	
		$type='in';
		$paytype=trim($_POST['paytype']);
		if(strtolower($paytype) == 'faktura')
		{
			/*if(trim($this->session->userdata['customer']['email']) == '')
			{
				$result = array("status"=>'error','message'=>
'Your email is empty. Please enter your email address');
				echo json_encode($result);exit;
			}*/
		}
		
		$amount=$_POST['amount'];
		$customer=$this->session->userdata['customer']['id'];
		if(strtolower($paytype) == 'kort')
		{
			$in_type='visa';
			$in_status='paid';
			
			
		
		}
		else if(strtolower($paytype) == 'kontant')
		{
			$in_type='cash';
			$in_status='paid';
		}
		else if(strtolower($paytype) == 'gavekort')
		{
			$in_type='gift_card';
			$in_status='paid';
			$giftcard=$_POST['amount'];
			
			$giftstatus=$this->payments_model->validateGiftcard($giftcard,$customer);	
			if($giftstatus)
			{
				if($giftstatus['status'] == 'proceed')
				{
					$amount=$giftstatus['amount'];
				}
				else
				{
					$amount=0;
				}
			}
			else
			{
				$amount=0;
			}
				
			
			
		}
		else
		{
			$in_type='invoice';
			$in_status='pending';
		}
		
	
		$regtime=date('Y-m-d H:i:s');
		$paymentarray=array(
		'type'=>$type,
		'in_type'=>$in_type,
		'in_status'=>$in_status,
		'customer'=>$customer,
		'amount'=>$amount,
		'regtime'=>$regtime,
		
		);
		if(strtolower($paytype) == 'gavekort')
		{
			$paymentarray['gift_card']=intval($giftstatus['id']);
		}
		
		$saldoid=$this->payments_model->addCustomerPayment($paymentarray,1);
		$result = $this->payments_model->updateCustomerBalance($customer,$amount,$in_status,'credit','1','1');
		
		require(APPPATH .'third_party/Mobile_Detect.php');
        $detect = new Mobile_Detect;
        $device=($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');
		if($device == 'tablet' || $device == 'phone')
		{
			if(strtolower($paytype) == 'kort')
			{
				$payload=array(
					"data"=>array("title"=>"Saldo Payment",
								"message"=>"Saldo Payment Notification.",
								"priority"=>"high",
									"content_available"=>"true",    
									"sound"=>"default", 
									"icon"=>"default"),
					"to"=>$this->session->userdata['gcmid'],   
					"type"=>'saldo', 
					"id"=>$saldoid,
					"amount"=>$amount,
					"customer"=>$customer);
					
					$server_key=$this->session->userdata['server_key'];
					//Send Puhs notification for saldo payment
					$pushstatus=send_fcm($server_key,$payload);
					
					
			}
		
		}
		
		
		
		
		
		$data = $this->payments_model->getAccountBalance($customer);
		$pendingsaldo = $data['pending'];
		$paidsaldo = $data['paid'];
			
		if(intval($pendingsaldo) > 0)
		{	
			//$amount= formatcurrency($paidsaldo).' ('.formatcurrency($pendingsaldo).')';
			$amount= formatcurrency($paidsaldo);
		}
		else
		{
			$amount= formatcurrency($paidsaldo);
		}
		
		$saldocolor='';
		if($paidsaldo < 0)
		{
			$saldocolor='style="color:red;"';
		}
		
		
		
		
		$result = array("status"=>'success','saldoid'=>$saldoid,"amount"=>$amount,'message'=>
'Customer payment has been credited','saldocolor'=>$saldocolor);
		echo json_encode($result);exit;
	}
	
	function __validateGiftcard()
	{
		$giftcard=trim($_POST['gift']);
		$customer=$this->session->userdata['customer']['id'];
		$giftstatus=$this->payments_model->validateGiftcard($giftcard,$customer);	
		if($giftstatus)
		{
			if($giftstatus['status'] == 'proceed')
			{
				$amount=formatcurrency($giftstatus['amount']);
				$result = array("status"=>'success',"amount"=>$amount,'message'=>
'Success. The Gift card amount is Kr.'.$amount.'');
			}
			else if($giftstatus['status'] == 'cancelled')
			{
				$result = array("status"=>'error','message'=>'Gift card status is cancelled.');
			}
			else
			{
				$result = array("status"=>'error','message'=>'Gift card has been already used.');
			}
			
			
		
		}
		else
		{
			$result = array("status"=>'error','message'=>'Invalid Gift card');
		}
		echo json_encode($result);exit;
	}
	
	
    /**
     * Generates various pulldown (<option>...</option>) lists for ready use in HTML
     * Output is set to e.g. $this->data['lists']['milestones']
     *
     */
    function __pulldownLists()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;
		
		
		//get utlevering count for a customer
		$customer_id = $this->session->userdata['pos_customer_id']; //get  customer id from session
		
		$this->data['reg_blocks'][] = 'orders';
		$this->data['blocks']['orders'] = $this->orders_model->getCustomerOrderhistory($customer_id,'utlevering');
		$this->data['debug'][] = $this->orders_model->debug_data;
		
		$this->data['vars']['order_count'] =  count($this->data['blocks']['orders']);
		
		
        //get results
		$this->data['reg_blocks'][] = 'category';
		$this->data['blocks']['category'] = $this->products_model->getCategories();
		$this->data['debug'][] = $this->products_model->debug_data;	
		if (count($this->data['blocks']['category']) > 0) {
			for($i=0;$i<count($this->data['blocks']['category']);$i++){
				$path_parts = pathinfo($this->data['blocks']['category'][$i]['path']);
				$this->data['blocks']['category'][$i]['thumb'] = $path_parts['filename'] . "." .$path_parts['extension'];
			}
		}
    }
	
	function __mobileDetails()
	{
	
	
	
		if(count($_POST) > 0)
		{
			$mobile=$_POST['mobile'];
			$mobinfo=$this->customer_model->customermobileinfo($mobile);
			
			if($mobinfo)
			{
		
				$customer=$mobinfo['id'];
				$newdata = array('customer'=> $mobinfo);
				$this->session->set_userdata($newdata);
			   $companyinfo=$this->customer_model->validateCompanycustomer($customer,true);
				

				if(count($companyinfo) > 0)//1
					{
						$companyarray=array();
						foreach($companyinfo as $companyitems)
						{
							$companyarray[$companyitems['company']]=$companyitems['company'];
								
						}
							$heatinfo='';
						
						
								$heatinfo.='<select onchange="setCompany(this.value);" style="height:39px !important; width:100%" name="company" id="company" class="select-list"><option value="">-- Select Company --</option>';
								
								$companylist=$this->customer_model->getCompanylist(false);	
								
							
								
								if(count($companylist) > 0)
								{
									foreach($companylist as $list)
									{
										if(in_array($list['id'],$companyarray))
										{
											$heatinfo.='<option value="'.$list['id'].'">'.$list['name'].'</option>';
										}
									}
								}
								$heatinfo.='';
								$heatinfo.='</select>';
						
						//$heatinfo.='';
						$result = array("status"=>'success',"data"=>$heatinfo);
						
					}
					else
					{
						$result = array("status"=>'success',"data"=>'');
					}
				
					
				}
				else
				{
					
					$result = array("status"=>'success',"data"=>'');
				}
		}
		else
		{
			
			$result = array("status"=>'error',"message"=>"Invalid.");
		}
		
		echo json_encode($result);exit;
	}
	
	
	function __saldoprint()
	{
		$saldo_id = $this->uri->segment(4);
		$this->data['reg_fields'][] = 'customer';
		$this->data['reg_fields'][] = 'branch';
		$this->data['fields']['customer'] = $customer = $this->customer_model->getSaldoDetails($saldo_id);
		$saldo_amount='';
		$data = $this->payments_model->getAccountBalance($customer['customer']);
		$pendingsaldo = $data['pending'];
		$paidsaldo = $data['paid'];

			if(intval($pendingsaldo) > 0)
			{	
				//$saldo_amount= formatcurrency($paidsaldo).' ('.formatcurrency($pendingsaldo).')';
				$saldo_amount= formatcurrency($paidsaldo);
			}
			else
			{
				$saldo_amount= formatcurrency($paidsaldo);
			}
			
			
			
			$address=$customer['street_line_1'];
			if($customer['street_line_2'] != '')
			{
				$address.=', '.$customer['street_line_2'];
			}
			$this->data['fields']['customer']['address'] = $address;
			
			$partnerinfo = $this->general_model->getPartnerDetails($customer['partner'],$customer['partner']);
				
				$this->data['fields']['branch']['company'] = $partnerinfo['name'];
				$this->data['fields']['branch']['street'] = $partnerinfo['street'] ;
				$this->data['fields']['branch']['zip'] =  $partnerinfo['zip']. ' '.$partnerinfo['city'];
				$this->data['fields']['branch']['phone'] = $partnerinfo['phone']; 
				$this->data['fields']['branch']['org_nr'] = $partnerinfo['org_nr']; 
				
				$in_type  = $this->__getInType($customer['in_type']);
				
				$footerarray=array('B'=>'Bankkort (B)','K'=>'Kontant(K)','G'=>'Gift Card (G)','F'=>'Faktura (F)','kk'=>'kasse kredit (kk)');
				
				
				$html ='<tr>
                <td nowrap="nowrap" style="text-align: left;width:18%; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:bold;  vertical-align: top;">1</td>
                <td style="text-align: left; padding: 5px 0 0;width:55%; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">'.$footerarray[$in_type].'</td>
                <td nowrap="nowrap" style="text-align: right;width:25%; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top;">kr '.formatcurrency($customer['amount']).'</td>';
				
				$this->data['fields']['customer']['list']=$html;
				
				$summery='<tr>
				 <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Delsum'.$min_price_txt.'</td><td nowrap="nowrap" style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal; border-top:#000 dashed 1px;">kr '.formatcurrency($customer['amount']).'</td>
				  </tr>';
				  
				  $mva=$customer['amount']/1.25;	
				
				$mva=round($mva, 2);
				
				$mva=$customer['amount']-$mva;
				
				
				 $summery.='<tr>
                <td colspan="2" style="text-align: right; padding:0px; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Herav 25% MVA</td>
                <td style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;">kr '.formatcurrency($mva).'</td>
              </tr>
              <tr>
                <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Totalt</td>
                <td style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal; border-top:#000 dashed 1px; border-bottom:#000 dashed 1px; ">kr '.formatcurrency($customer['amount']).'</td>
              </tr>';
			  
			    if($saldo_amount != '' && $saldo_amount != '0,00')
			  {
				  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Kassakredit saldo: kr '.$saldo_amount.'</td></tr>';
			  }
		
		
		$this->data['fields']['customer']['summery']=$summery;
		
		//echo '<pre>';print_r($this->data['fields']['branch']);exit;
	}
	
	//get in types in norwegiean
    function __getInType($type = '')
    {
		
        switch ($type) {
			
			case 'visa':
			    $str = 'B';
                break;
			case 'invoice':
			    $str = 'F';
                break;
			case 'gift_card':
			    $str = 'G';
                break;
			case 'cash':
			    $str = 'K';
                break;
			case 'account':
			    $str = 'kk';
                break;
			case '':
			    $str = 'kk';
                break;
            default:
			    $str = 'B';
                break;
				
		}
		
		return $str;
				
	}
	
		
	
	
	
	

	
}
/* End of file customer.php */
/* Location: ./application/controllers/admin/customer.php */