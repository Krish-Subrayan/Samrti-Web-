<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class Orders extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'order.scan.html';
		$cus_id = $this->session->userdata['pos_customer_id'];
		$saldo_status = $this->payments_model->getSaldostatus($cus_id);
		$this->data['visible']['saldo_status'] = $saldo_status;
		
    }
    /**
     * This is our re-routing function and is the inital function called
     *
     * 
     */
    function index()
    {
	
	//self::makeorderpaymentself('01067598');
	

	
	//exit;
        //profiling
        $this->data['controller_profiling'][] = __function__;
        //login check
        $this->__commonAdmin_LoggedInCheck();	
	
	//echo '<pre>';print_r($this->session->userdata);exit;
		
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
		
		
        //get the action from url
        $action = $this->uri->segment(3);
		
		$this->__categoryMenu();
		
        //route the rrequest
        switch ($action) {
				
			case 'orderlinedetails':
                 $this->__getorderlinedetails();
           		 break;
				 
			case 'utlevering':
				//if(!$this->session->userdata['pos_customer_id'])
				//{
					//redirect('/');
			//	}
				 //template file
				 if($this->session->userdata['logged_in'] == 'shop'){ 
					$this->data['template_file'] = PATHS_ADMIN_THEME . 'utlevering.orders.html';
				 }
				 else
				 {
					 $this->data['template_file'] = PATHS_ADMIN_THEME . 'smart.laundry.utlevering.orders.html';
				 }
				 $this->_getcustomerinfo();
				 $this->__getCustomerDetail();
				 $this->__getOrders();
				 //css settings
				 $this->data['vars']['css_menu_utlevering'] = 'current'; //menu
           		 break;
				 
				
			case 'utlever-pending':
			$this->data['template_file'] = PATHS_ADMIN_THEME . 'utlever.pending.orders.html';
				 $this->_getcustomerinfo();
				 $this->__getCustomerDetail();
				 $this->__getOrders('pending');
				 //css settings
				 $this->data['vars']['css_menu_pendingutlever'] = 'current'; //menu
           		break;		 
			case 'get-order-detail':
				 $this->__getOrderDetail();
           		 break;
			case 'saveeditorder':
				 $this->__saveeditorder();
           		 break;	 
				 
			case 'get-order-receipt':
				 $this->__getOrderReceipt();
           		 break;
				 
            case 'edit-utlevering':
				if(!$this->session->userdata['pos_customer_id'])
				{
					redirect('/');
				}
			    $this->__editUtlevering();
                break;
				 
			case 'updateorderlog':
				 $this->__updateorderlog();
           		 break;	 
			case 'editorder':
				 $this->__editorder();
           	 break;	
			 case 'printtagOrder':
				 $this->__printtagOrder();
           	 break;	
			 case 'editorderpayment':
				 $this->__editorderpayment();
           	 break;	
			  case 'deleteorderpayment':
				 $this->__deleteorderpayment();
           	 break;
			case 'addOrderline':
				 $this->__addOrderline();
			case 'removeOrderline':
				 $this->__removeOrderline();	 
			case 'saveOrder':
				 $this->__saveOrder();
				 
			case 'makeorderpayment':
				 $this->__makeorderpayment();
				 
			case 'updatedeliverystatus':
				 $this->__updatedeliverystatus();
				 
            case 'update-utlevering-date':
			    $this->__updateUtleveringDate();
                break;

			case 'discount':
				$this->__caluclateDiscount();
          		 break;	
				
			case 'voucher-discount':
				$this->__voucherDiscount();
          		 break;	
				 	 
			case 'print':
				 //template file
				 $this->data['template_file'] = PATHS_ADMIN_THEME . 'print.html';
				 $this->__printReceipt();
				 $this->__printTag();
          		 break;	
			case 'tagprint':
			case 'print-tag':
				 //template file
				 $this->data['template_file'] = PATHS_ADMIN_THEME . 'print.tag.html';
				 //$this->__printReceipt();
				 $this->__printTag();
          		 break;		 
			case 'view':
				 //template file
				 $this->data['template_file'] = PATHS_ADMIN_THEME . 'print.view.html';
				 $this->__printReceipt();
          		 break;	
				 
			case 'print-heatseal':
				 //template file
				 $this->data['template_file'] = PATHS_ADMIN_THEME . 'print.heatseal.html';
				 $this->__printReceiptWithHeatSeal();
          		 break;	
			 case 'getHeatseallog':
				 $this->data['template_file'] = PATHS_ADMIN_THEME . 'iframe.heatseal.html';
				 $this->__logReceiptWithHeatSeal();
           		 break;	 
				 
			case 'getproducttypes':
				 //template file
				 $this->__getproducttypes();
          		 break;			
			case 'damageheatseal':
				 $this->__damageheatseal();
           	 break;	
			case 'savedamageheatseal':
				 $this->__savedamageheatseal();	
			break;					 
			case 'removedamageline':
				 $this->__removedamageline();	
			break;	 
			case 'ajaxutleveringinfo':
				 $this->__utleveringinfo();	
			break;
			case 'ajaxutpendingleveringinfo':
				 $this->__utleveringinfo('pending');	
			break;
			
			case 'cancelorderlines':
				 $this->__cancelorderlines();	
			break;
			case 'get-pay-order-details':
				 $this->__getpayorderdetails();	
			break;
			case 'paymentMultiOrder':
				 $this->__paymentMultiOrder();	
			break;
			case 'updatemultideliverystatus':
				 $this->__updatemultideliverystatus();	
			break;
			
			default:
			$this->__getBagdetails();
			break;
				
        }
        //load view
        if ($action != 'pdf') {
            $this->__flmView('admin/main');
        }
    }
	
	
	
	
	/*update product quantity in cart */
	function __editUtlevering(){
		
        //profiling
        $this->data['controller_profiling'][] = __function__;
		
		$oid = $this->input->post('oid');
		$olid = $this->input->post('olid');
		$qty = $this->input->post('qty');
		$price = $this->input->post('price');
		$total = $this->input->post('total');
		$complain = $this->input->post('complain');
		$in_house = $this->input->post('in_house');
		$desp = $this->input->post('desp');
		
		$price = ($complain!='1')  ?   $price : 0;

		
	

		/*$oid = 10154688;
		$olid = 508;
		$qty = 2;
		$price = 0;
		$total = 804;
		$complain = 1;
		$in_house = 0;
		$desp = "ggg";*/
		if($qty == 0)
		{
			/*$data = $this->orders_model->updateOrderline($olid,$oid,$qty,$price,$total,$complain,$in_house,$desp,'canceled','canceled');
			$heatdata = $this->orders_model->orderlineHeatseal($olid);
		
			if(count($heatdata) > 0)
			{
				$this->orders_model->updateHeatsealstatus($heatdata,'19');//canceled
			}*/
			//cancel orderline when the qty is 0
			//$this->process_order_model->cancelorderline($oid,$olid,'pending','10','');
			
			$data = $this->orders_model->updateOrderline($olid,$oid,$qty,$price,$total,$complain,$in_house,$desp);

			
			
		}
		else
		{
			$data = $this->orders_model->updateOrderline($olid,$oid,$qty,$price,$total,$complain,$in_house,$desp);
		}
		
		//$data = $this->orders_model->updateOrderline(332,10154635,2,398,4299);
		$this->data['debug'][] = $this->orders_model->debug_data;
		
		$result = array("error"=>$data);
		echo json_encode($result);exit;
		
		
	}
	
	/*update utlevering date for an orderline  */
	function __updateUtleveringDate(){
		
        //profiling
        $this->data['controller_profiling'][] = __function__;
			$olid = explode('@',$this->input->post('olid'));
			$utlevering = date('Y-m-d',strtotime($this->input->post('utlevering')));
			$oid = $this->input->post('oid');

			/*$olid = '195@196';
			$olid = explode('@',$olid );
			$utlevering = date('Y-m-d',strtotime('4.1.2017'));
			$oid = '10154594';*/
            
			$data = $this->orders_model->updateOrderlineDate($oid,$olid,$utlevering);
			$this->data['debug'][] = $this->orders_model->debug_data;
			
			$result = array("error"=>$data);
			echo json_encode($result);exit;
				
		
	}
	
     /**
      * get details of a customer
      */
     function __getCustomerDetail(){
 		
        //profiling
        $this->data['controller_profiling'][] = __function__;
		
		$customer_id = $this->session->userdata['pos_customer_id']; //get  customer id from session
		
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
			$amount= formatcurrency($paidsaldo).' ('.formatcurrency($pendingsaldo).')';
		}
		else
		{
			$amount= formatcurrency($paidsaldo);
		}
		$this->data['lists']['saldo']=$amount;
		
		$this->data['lists']['saldocolor']='';
		if($paidsaldo < 0)
		{
			$this->data['lists']['saldocolor']='style="color:red;"';
		}
		
	 }
	
	
     /**
      * get orders of a customer (orders ready fro delivery)
      */
     function __getOrders($flag=''){
 		
        //profiling
        $this->data['controller_profiling'][] = __function__;
		
		$customer_id = $this->session->userdata['pos_customer_id']; //get  customer id from session
		
		  
		//get results for orders ready to deliver today
		$this->data['reg_blocks'][] = 'ready';
		$this->data['blocks']['ready'] = $this->orders_model->getCustomerOrderhistory($customer_id,'utlevering');
		

		$this->data['debug'][] = $this->orders_model->debug_data;
		$this->data['vars']['order_count'] =  count($this->data['blocks']['ready']);
		$ordersid=$this->uri->segment(4);
		$this->data['visible']['utorder_status'] = 0;
		
		if(intval($ordersid) > 0)
		{
			$this->data['visible']['utorder_status'] = 1;
			$this->data['vars']['utorder_count']= sprintf('%08d', intval($ordersid));
		}
		$str ='';
		if(count($this->data['blocks']['ready']) > 0){
		for($i=0;$i<count($this->data['blocks']['ready']);$i++){
			
			 $result = $this->orders_model->validateTodaydelivery($this->data['blocks']['ready'][$i]['id']);
			 

			 if($result){		  
			    $order_id = $this->data['blocks']['ready'][$i]['id'];
				
			
				$this->data['reg_blocks'][] = 'orderline';
				$this->data['blocks']['orderline'] = $this->orders_model->getOrderLine($order_id);
				
				$this->data['debug'][] = $this->orders_model->debug_data;
				$arr = array();
				for($j=0;$j<count($this->data['blocks']['orderline']);$j++){
					$ptype = $this->data['blocks']['orderline'][$j]['ptype'];
					if ((!in_array($ptype, $arr)) || ($j==0)){
						$arr[] = $ptype;
					}
					$gtype = ucwords(implode(', ',$arr));
				}

			
			  	$amount = ($this->data['blocks']['ready'][$i]['changed_amount']!='') ? $this->data['blocks']['ready'][$i]['changed_amount'] :  $this->data['blocks']['ready'][$i]['total_amount'];
			  
			  	$opstatus = $this->data['blocks']['ready'][$i]['payment_status'];
			  	
			  	$color = ($opstatus=='canceled') ? 'red' : (($opstatus == 'paid') ?  'green' : 'orange');	
			  
				
				$str .='<div class="orderlisting row" id="order_'.$this->data['blocks']['ready'][$i]['id'].'" >
				<a href="#" rel="'.intval($this->data['blocks']['ready'][$i]['id']).'">
				  <div class="col-md-2"> #'.$this->data['blocks']['ready'][$i]['id'].' </div>
				  <div class="col-md-2"> '.$this->data['blocks']['ready'][$i]['odate'].' </div>
				  <div class="col-md-2"> '.date('d.m.Y').' </div>
				  <div class="col-md-2">'.$gtype.' </div>
				  <div class="col-md-2">kr '.formatcurrency($amount).' </div>
				  <div class="col-md-2 text-center"> <div class="'.$color.' paymentstatus"> '.ucfirst($this->data['blocks']['ready'][$i]['payment_status']).'</div> </a></div>
				</div>';
				
					
					
				
			 }
			 
				
		  }//for
		}
		else{
			$str.='<div class="col-md-12 text-center mt-lg mb-lg"><h2>Ingen resultat.</h2></div>';
		}
		$this->data['lists']['orders_ready'] =  $str;
		
		 
		$currentyear=1;//NUll
		//get results for orders in process (orders which are placed , in process)
		$this->data['reg_blocks'][] = 'iporders';
		
		$currentyearorders1=$this->orders_model->getCustomerOrderhistory($customer_id,'utlevering','',$currentyear,$flag);
		
		$currentyear=2;//current year
		$currentyearorders2=$this->orders_model->getCustomerOrderhistory($customer_id,'utlevering','',$currentyear,$flag);
		
		$currentyear=3;//Previous year
		$currentyearorders3=$this->orders_model->getCustomerOrderhistory($customer_id,'utlevering','',$currentyear,$flag);
		
		$currentyearorders=array_merge($currentyearorders1,$currentyearorders2,$currentyearorders3);
	
		
		$this->data['blocks']['iporders'] = $currentyearorders;
		
	//echo '<pre>';print_r($this->data['blocks']['iporders']);exit;
		
		$this->data['debug'][] = $this->orders_model->debug_data;
		$str ='';
		if(count($this->data['blocks']['iporders']) > 0){
		  for($i=0;$i<count($this->data['blocks']['iporders']);$i++){
			
			$old[] = $this->data['blocks']['iporders'][$i]['id'];
			
		    $company = ($this->data['blocks']['iporders'][$i]['type'] == 'shop') ?  $this->data['blocks']['iporders'][$i]['partner_branch'] :  $this->data['settings_company']['company_name'];
			
			$special_chars = 'ø';// all the special characters you want to check for 
			if (preg_match('/'.$special_chars.'/', $company))
			{
				$companyinitial=substr($company,0,4);
			}
			else
			{
				$companyinitial=substr($company,0,3);
			}
			$branch =  ($company == $this->data['settings_company']['company_name']) ?  '('.$companyinitial.')':  (($company != $this->session->userdata['partner_branch_name']) ? '('.$companyinitial.')' :  ''); 
			
		
			$orderline_today_delivery = $this->orders_model->validateTodaydelivery($this->data['blocks']['iporders'][$i]['id']);
			
			//if($orderline_today_delivery)
			//{
				//continue;
			//}
			
			if($i==(count($this->data['blocks']['iporders'])-1)){
			
				$firstdelivery = $this->orders_model->getShopdeliverydate($this->data['blocks']['iporders'][$i]['id'],$old,'ASC','process');
			
			}
			
			$result = $this->orders_model->validateOrderlinedelivery('order',$this->data['blocks']['iporders'][$i]['id']);
			
			if($result)
			{
			
				//product types 
				$order_id = $this->data['blocks']['iporders'][$i]['id'];
				$this->data['reg_blocks'][] = 'orderline';
				$this->data['blocks']['orderline'] = $this->orders_model->getOrderLine($order_id);
				$this->data['debug'][] = $this->orders_model->debug_data;
				
				
				$order_from=$this->data['blocks']['iporders'][$i]['type'];
				if($order_from == 'shop')
				{
					$this->data['reg_fields'][] = 'employee';
					$this->data['fields']['employee'] =  $this->employee_model->getEmployeeDetail($this->data['blocks']['iporders'][$i]['employee']);
				}
			
			
				
				for($j=0;$j<count($this->data['blocks']['orderline']);$j++){
					
					$olineid=$this->data['blocks']['orderline'][$j]['id'];
					
					$orderline_result = $this->orders_model->validateOrderlinedelivery('orderline',$olineid);
					if($orderline_result)
					{
					
					
						$nextdeliverydate=$this->data['blocks']['orderline'][$j]['p_b_delivery_time'];
						
						if($nextdeliverydate == '')
						{
							$nextdeliverydate= $this->data['blocks']['iporders'][$i]['odate'];
						}
						$orlinestatus=1;
						break;
					}
					
				}
				
				
				$arr = array();
				$myarr = array();
				$carr = array();
				$artikler = '';
				$prod_name = array();
				$temp = '';
				for($j=0;$j<count($this->data['blocks']['orderline']);$j++){
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
				
				  $amount = ($this->data['blocks']['iporders'][$i]['changed_amount']!='') ? $this->data['blocks']['iporders'][$i]['changed_amount'] :  $this->data['blocks']['iporders'][$i]['total_amount'];
				  
				  $opstatus = $this->data['blocks']['iporders'][$i]['payment_status'];
				  
				  $color = ($opstatus=='canceled') ? 'red' : (($opstatus == 'paid') ?  'green' : 'orange');	
				  
				  $status = ($opstatus=='canceled') ? 'C' : (($opstatus == 'paid') ?  'BE' : 'UB');	
				  		  
					
					$str .='<div class="orderlisting row" id="order_'.$this->data['blocks']['iporders'][$i]['id'].'" >';
					
					$str .='<div class="col-md-1"><input type="checkbox" onclick="orderchkbox();" name="orderchkbox[]" id="box_'.$this->data['blocks']['iporders'][$i]['id'].'" value="'.$this->data['blocks']['iporders'][$i]['id'].'" /></div>';
					
					 
					$str .='<a href="#" rel="'.intval($this->data['blocks']['iporders'][$i]['id']).'">
					
					  <div class="col-md-2" id="orderinfo_'.intval($this->data['blocks']['iporders'][$i]['id']).'"> #'.$this->data['blocks']['iporders'][$i]['id'].' <span class="green-text">'.$branch.'</span></div>
					  <div class="col-md-2 no-padd"> '.$this->data['blocks']['iporders'][$i]['odate'].' </div>
					  <div class="col-md-3 "> '.$kategori.' </div>';
					  
					  /*<div class="col-md-1 no-padd"> '.date('d.m.Y',strtotime($nextdeliverydate)).' </div>*/
					  
					 $str .='  <div class="col-md-3">kr '.formatcurrency($amount).' </div></a>';
					 /*$str .='<div class="col-md-1 text-center"> <div class="'.$color.' paymentstatus"> '.$status.'</div> </div>';*/
					 
					 $str .=' <div class="col-md-1 text-center"><div title="utsikt" class="bg-purple paymentstatus utsikt" id="'.$this->data['blocks']['iporders'][$i]['id'].'">UT</div></div>
					 
					 <div style="display:none;" id="kasserer_'.intval($this->data['blocks']['iporders'][$i]['id']).'">Kasserer: '.$this->data['fields']['employee']['initial'].'</div>';
					$str .='</div>';				
			}	
		  }//for
		  
		}
		else{
			$str.='<div class="col-md-12 text-center mt-lg mb-lg"><h2>Ingen resultat.</h2></div>';
		}
		  
		$this->data['lists']['orders_in_process'] =  $str;
		  
		  
		 
		  
		//get results for orders delivered
		$this->data['reg_blocks'][] = 'delivered';
		if($flag == '')
		{	
			$this->data['blocks']['delivered'] = $this->orders_model->getCustomerOrderhistory($customer_id,'delivered');
		}
		else
		{
			$this->data['blocks']['delivered'] =array();
		}
		
		

		$this->data['debug'][] = $this->orders_model->debug_data;
		$str ='';
		if(count($this->data['blocks']['delivered']) > 0){
		
		

						
						
						
		for($i=0;$i<count($this->data['blocks']['delivered']);$i++){
			
			$order_from=$this->data['blocks']['delivered'][$i]['type'];
				if($order_from == 'shop')
				{
					$this->data['reg_fields'][] = 'employee';
					$this->data['fields']['employee'] =  $this->employee_model->getEmployeeDetail($this->data['blocks']['delivered'][$i]['employee']);
				}
				
			 $orderline_today_delivery = $this->orders_model->validateTodaydelivery($this->data['blocks']['delivered'][$i]['id']);
		
			$old[] = $this->data['blocks']['delivered'][$i]['id'];
			
		    $company = ($this->data['blocks']['delivered'][$i]['type'] == 'shop') ?  $this->data['blocks']['delivered'][$i]['partner_branch'] :  $this->data['settings_company']['company_name'];
					
		  // $branch =  ($company == $this->data['settings_company']['company_name']) ?  '('.substr($company,0,3) .')':  (($company != $this->session->userdata['partner_branch_name']) ? '('.substr($company,0,3).')' :  ''); 
			
			$special_chars = 'ø';// all the special characters you want to check for 
			if (preg_match('/'.$special_chars.'/', $company))
			{
				$companyinitial=substr($company,0,4);
			}
			else
			{
				$companyinitial=substr($company,0,3);
			}
			$branch =  ($company == $this->data['settings_company']['company_name']) ?  '('.$companyinitial.')':  (($company != $this->session->userdata['partner_branch_name']) ? '('.$companyinitial.')' :  '');
		
			if($i==(count($this->data['blocks']['delivered'])-1)){
				$lastdelivery = $this->orders_model->getShopdeliverydate($this->data['blocks']['delivered'][$i]['id'],$old,'DESC');
			}
		
			//product types 
			$order_id = $this->data['blocks']['delivered'][$i]['id'];
			$this->data['reg_blocks'][] = 'orderline';
			$this->data['blocks']['orderline'] = $this->orders_model->getOrderLine($order_id);
			$this->data['debug'][] = $this->orders_model->debug_data;
			$nextdeliverydate=$this->data['blocks']['delivered'][$i]['odate'];
			
			for($j=0;$j<count($this->data['blocks']['orderline']);$j++){
					
					$olineid=$this->data['blocks']['orderline'][$j]['id'];
					if($this->data['blocks']['orderline'][$j]['p_b_delivery_time'] != '')
					{
						$nextdeliverydate=$this->data['blocks']['orderline'][$j]['p_b_delivery_time'];
					}
			}
			
			
			$arr = array();
			$myarr = array();
			$artikler = '';
			$carr = array();
			$prod_name = array();
			for($j=0;$j<count($this->data['blocks']['orderline']);$j++){
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
					$prod_name[$j] = substr($myarr['name'],0,2);
				}
				
			}
			
			$kategori = ucwords(implode(', ',$carr));
			$kategori = $this->general_model->trim_text($kategori);

			$artikler = implode(', ',$prod_name);
			$artikler = $this->general_model->trim_text($artikler);

			
			  $amount = ($this->data['blocks']['delivered'][$i]['changed_amount']!='') ? $this->data['blocks']['delivered'][$i]['changed_amount'] :  $this->data['blocks']['delivered'][$i]['total_amount'];
			  
			  $opstatus = $this->data['blocks']['delivered'][$i]['payment_status'];
			  
			  $color = ($opstatus=='canceled') ? 'red' : (($opstatus == 'paid') ?  'green' : 'orange');	
			  
			  $status = ($opstatus=='canceled') ? 'C' : (($opstatus == 'paid') ?  'BE' : 'UB');	
			  		
				$neworderid=sprintf('%08d', intval($this->data['blocks']['delivered'][$i]['id']));					
				
				$str .='<div class="orderlisting row" id="order_'.$neworderid.'" >
				<a href="#" rel="'.intval($this->data['blocks']['delivered'][$i]['id']).'">
				  <div class="col-md-2" id="orderinfo_'.intval($this->data['blocks']['delivered'][$i]['id']).'"> #'.$this->data['blocks']['delivered'][$i]['id']. ' <span class="green-text">'.$branch.'</span></div>
				  <div class="col-md-2 no-padd"> '.$this->data['blocks']['delivered'][$i]['odate'].' </div>
				  <div class="col-md-2"> '.$kategori.'</div>
				  <div class="col-md-2 no-padd"> '.date('d.m.Y',strtotime($nextdeliverydate)).' </div>
				  <div class="col-md-2 ">kr '.formatcurrency($amount).' </div>
				  <div class="col-md-1 text-center "> <div class="'.$color.' paymentstatus"> '.$status.'</div></div></a><div class="col-md-1 text-center"><div title="utsikt" class="bg-purple paymentstatus utsikt" id="'.$this->data['blocks']['delivered'][$i]['id'].'">UT</div></div><div style="display:none;" id="kasserer_'.intval($this->data['blocks']['delivered'][$i]['id']).'">Kasserer: '.$this->data['fields']['employee']['initial'].'</div>
				</div>';				
				
		  }//for
		  
		}
		else{
			$str.='<div class="col-md-12 text-center mt-lg mb-lg"><h2>Ingen resultat.</h2></div>';
		}
		  
		  
		  $this->data['lists']['orders_delivered'] =  $str;

		
		if(count($this->data['fields']['orders'])> 0){
			$this->data['visible']['wi_customer_orders'] = 1;
		}
		else{
			$this->data['visible']['wi_orders_none'] = 0;
		}
	 }
	
	
     /**
      * list utlevering orders of a customer
      */
     function __getOrderDetail(){
 		
        //profiling
        $this->data['controller_profiling'][] = __function__;

		$order_id = $this->input->post('oid');
		
			
		
		$this->data['reg_fields'][] = 'order';
		$orderinfo = $this->orders_model->getOrderinfo($order_id);
		
		
		$cus_id=$orderinfo['customer'];

		
		$this->data['fields']['order']=$orderinfo;
		
		
		$this->data['debug'][] = $this->orders_model->debug_data;
		
		$customer = $this->orders_model->getCustomerDetails($order_id);
		
		
		
		$delivery_time = $this->data['fields']['order']['delivery_time'];
		$total_amount = $this->data['fields']['order']['total_amount'];
		$instruction = $this->data['fields']['order']['special_instruction'];
		$discount = 0;
		
		
		
		//get results
		$this->data['reg_blocks'][] = 'orderline';
		$this->data['blocks']['orderline'] = $this->orders_model->getOrderLine($order_id);
		$this->data['debug'][] = $this->orders_model->debug_data;
		
		
		
		
			$str ='';
			$total = 0;
			
			 //echo '<pre>';print_r($this->data['blocks']['orderline']); exit;
		     $rens='<div id="ajax-loader-rens"><img src="img/ajax-loader.gif"/></div>';
		     $vask='<div id="ajax-loader-vask"><img src="img/ajax-loader.gif"/></div>';
			 $rensstatus=0;
			 $vaskstatus=0;
			 $paidamountarray=array();
			 $waitamountarray=array();
			 $paidstatus=1;
			 $discountstatus=1;
			 $pendingstatus=0;
			 $this->data['reg_blocks'][] = 'rens';
			 $this->data['reg_blocks'][] = 'vask';
			 
			
			 
			if (count($this->data['blocks']['orderline']) > 0) {
				$k = 0;
				$str ='';
				$row = array();
				for($i=0;$i<count($this->data['blocks']['orderline']);$i++){ 
					$subtotal = 0;
					$quantity = 0;
					
					// echo '<pre>';print_r();exit;
					 
					 
					$gtype = $this->data['blocks']['orderline'][$i]['ptype'];
					$product_id = $this->data['blocks']['orderline'][$i]['id'];
					$this->data['blocks']['orderline'][$i]['i'] = $i;
					
					$quantity = ($this->data['blocks']['orderline'][$i]['changed_quantity']!='') ? $this->data['blocks']['orderline'][$i]['changed_quantity'] : $this->data['blocks']['orderline'][$i]['quantity'];
					
					
					
					 
					 
										
					if (round($quantity, 0) == $quantity)
					{
						// is whole number
						$quantity = round($quantity, 0);
					}					
					
					
					 $discount = $this->products_model->getProDiscount($this->data['blocks']['orderline'][$i]['product']);
					 $discount=$discount[0];
					 $ddesc='&nbsp;';
					 if(isset($discount['description']))
					 {
						$ddesc=' ('.$discount['description'].')';
					 }
					 
					 $subtotal = ($this->data['blocks']['orderline'][$i]['changed_amount']!='') ? $this->data['blocks']['orderline'][$i]['changed_amount'] : $this->data['blocks']['orderline'][$i]['amount'];
					 
					 $productPrice=$subtotal;
					
						
					 $productPrice=round($productPrice);
						 
					  if($this->data['blocks']['orderline'][$i]['payment_status'] == 'paid')
					  {
							$paidamountarray[]=$productPrice;
							$discountstatus=0;
					  }
					  
					   if($this->data['blocks']['orderline'][$i]['payment_status'] == 'waiting')
					  {
							$waitamountarray[]=$productPrice;
							$paidstatus=0;
							$discountstatus=0;
					  }
					  
					  if($this->data['blocks']['orderline'][$i]['payment_status'] == 'pending')
					  {
							$deliverystatus = $this->payments_model->getDeliverystatus($order_id,$this->data['blocks']['orderline'][$i]['id']);
							if($deliverystatus)
							{
								$waitamountarray[]=$productPrice;
								$paidstatus=0;
								$discountstatus=0;
							}
							else
							{
								$paidstatus=0;
								$pendingstatus=1;
							}
					  
							
					  }
					
					
					$subtotalarray[]= $productPrice;
					
					$total += $subtotal;
					
					$cart_type = ($gtype=='rens') ? 'rens' : 'vask';
					$day = $this->data['blocks']['orderline'][$i]['utlevering'];
					
					$row[$day][$cart_type][$k] = $this->data['blocks']['orderline'][$i]['id'];
					
					$this->data['blocks'][$cart_type][$k]['orderline_emp']='';
					if($this->data['blocks']['orderline'][$i]['payment_status'] == 'paid' || $this->data['blocks']['orderline'][$i]['payment_status'] == 'waiting')
					{
			//	echo '<pre>';print_r();exit;
						$orderlineemp = $this->employee_model->getEmployeebranchDetail($this->data['blocks']['orderline'][$i]['id'],$this->data['blocks']['orderline'][$i]['order']);
						if($orderlineemp)
						{
						
							//$this->data['blocks'][$cart_type][$k]['orderline_emp']='<p>Kasserer: '.$orderlineemp['initial'].'</p>';
							$this->data['blocks'][$cart_type][$k]['orderline_emp']='('.$orderlineemp['initial'].')';
						}
						
					}
					
					$this->data['blocks'][$cart_type][$k]['utlevering'] = $this->data['blocks']['orderline'][$i]['utlevering'];					
					$this->data['blocks'][$cart_type][$k]['id'] = $this->data['blocks']['orderline'][$i]['id'];
					
					$this->data['blocks'][$cart_type][$k]['oprice'] = round($this->data['blocks']['orderline'][$i]['oprice']);
					$this->data['blocks'][$cart_type][$k]['product'] = round($this->data['blocks']['orderline'][$i]['product']);
					
					$arr = $this->orders_model->getProductDisplayName($this->data['blocks']['orderline'][$i]['product']);
					
					$this->data['blocks'][$cart_type][$k]['name'] = $arr['name'];
					$this->data['blocks'][$cart_type][$k]['price'] = $this->data['blocks']['orderline'][$i]['price'];
					$this->data['blocks'][$cart_type][$k]['description'] = $this->data['blocks']['orderline'][$i]['special_instruction'];
					$this->data['blocks'][$cart_type][$k]['complain'] = $this->data['blocks']['orderline'][$i]['complain'];
					$this->data['blocks'][$cart_type][$k]['in_house'] = $this->data['blocks']['orderline'][$i]['in_house'];
					
					$this->data['blocks'][$cart_type][$k]['qty'] = $quantity;
					$this->data['blocks'][$cart_type][$k]['gtype'] = $gtype;
					$this->data['blocks'][$cart_type][$k]['subtotal'] = $productPrice;
					
					$path_parts = pathinfo($this->data['blocks']['orderline'][$i]['path']);
					$this->data['blocks'][$cart_type][$k]['thumb'] = $path_parts['filename'] . "." .$path_parts['extension'];
					$this->data['blocks'][$cart_type][$k]['status'] =  $this->data['blocks']['orderline'][$i]['payment_status'];
					$k++;
				
			  }//for
			  
			  $vask_arr = array_values($this->data['blocks']['vask']);
			  $rens_arr = array_values($this->data['blocks']['rens']);
			 
			 
			//echo '<pre>';print_r($rens_arr);exit;
			  
			  //for vask	
			 $prev_date ='';
			 $date = '';
			 $str_vask ='';
			 $status = '';
			 if(count($vask_arr) >0 ){
			  $vaskstatus=1;
			  for($z=0;$z<count($vask_arr);$z++){ 
					$date = $vask_arr[$z]['utlevering'];
					
					if(($z==0) || ($date!=$prev_date)){
						$rowid ='';
						foreach($row as $key2 => $value2) {
							$day = $key2;
							if($day==$date){
								$rowid	= implode('@',$row[$key2]['vask']);
							}
						}
						
						$str_vask.='<div class="row">
						<div class="pull-left col-md-3 no-padd text-center">
						</div> 
						<div class="pull-right col-md-6 no-padd  customer-info">
						   <div class="input-group">
						   <div class="pull-left saldo">Utlevering: </div> 
						   <input type="text" id="'.$rowid.'" class="form-control datepicker" value="'.$date.'">
							<div class="input-group-addon">
								<span class="glyphicon glyphicon-th"></span>
							</div>
						</div>
					   </div>
					   </div>';
					   $rowid = '';
					}
					
					$reklama = ($vask_arr[$z]['complain'] =='1' ) ? '<span class="orange-text">Reklamasjon</span>' : '';
					$renses =  ($vask_arr[$z]['in_house'] =='1' ) ? '<span class="purple-text">Renses på huset</span>' : '';


			  		$str_vask.='
						  <div class="orderline mt-sm">
						   <div class="pull-left col-md-2 no-padd text-center">';
						   
						/*$str_vask.='	 <div class="count">'.$vask_arr[$z]['qty'].'</div>
							<div class="round">
								<div style="background-image: url(\''.$this->data['vars']['site_url'].'\images/'.$vask_arr[$z]['thumb'].'\');" class="img"></div>
							</div>'; */
							
						//	$str_vask.='<div class="round"><div class="img">'.$vask_arr[$z]['qty'].'</div></div>';
							
							$str_vask.='<div class="round1"><div class="img1"><p><span>'.$vask_arr[$z]['qty'].'</span></p></div></div>';
							              
						 $str_vask.='  </div>
						 
						 <div class="pull-left col-md-5 no-padd">
						   <p><span>'.$vask_arr[$z]['name'].' '.$vask_arr[$z]['orderline_emp'].'</span>
							'.$vask_arr[$z]['description'].$reklama.$renses.'</p>
						   </div>
						   
						   <div class="pull-left col-md-3 no-padd text-right"><p><span>kr '.formatcurrency($vask_arr[$z]['subtotal']).'</span></p>
						   
						</div>
						
						 
						  
						   <div class="pull-left col-md-2 no-padd text-center">';
						   
						  if($vask_arr[$z]['status'] == 'pending')
						  {
						  
								$resstatus=$this->settings_order_model->getCancelstatus($order_id,$vask_arr[$z]['id']);
								if($resstatus)
								{
									$color = ($vask_arr[$z]['status']=='canceled') ? 'red' :  'orange';
									
									$str_vask.=' <p class="paymentstatus"><span  class="'.$color.'">C</span></p>';
								}
								else
								{
									$str_vask.='<a onclick="editprod();" class="editprod" data-toggle="modal" data-title="'.$vask_arr[$z]['name'].'"  href="#editProductModal" data-stuff="'.$vask_arr[$z]['id'].'@'.$vask_arr[$z]['name'].'@'.$vask_arr[$z]['price'].'@'.$vask_arr[$z]['path'].'@'.$vask_arr[$z]['description'].'@'.$vask_arr[$z]['qty'].'@'.$vask_arr[$z]['gtype'].'@'.$vask_arr[$z]['complain'].'@'.$vask_arr[$z]['in_house'].'@'.$vask_arr[$z]['oprice'].'@'.$vask_arr[$z]['product'].'"><img src="img/plus-minus.png" alt="Edit" title=""/></a>';
								}
								
						  
							
						  }
						  else{
							  
							  $color = ($vask_arr[$z]['status']=='canceled') ? 'red' : (($vask_arr[$z]['status']=='paid') ?  'green' : 'orange');
							  
							  $status = ($vask_arr[$z]['status']=='canceled') ? 'C' : (($opstatus == 'paid') ?  'BE' : 'UB');	
							  
							  $resstatus=$this->settings_order_model->getCancelstatus($order_id,$vask_arr[$z]['id']);
								if($resstatus)
								{
									$color = ($vask_arr[$z]['status']=='canceled') ? 'red' :  'orange';
									
									//$str_vask.=' <p class="paymentstatus"><span  class="'.$color.'">'.ucfirst($status).'(C)</span></p>';
									
									$str_vask.=' <p class="paymentstatus"><span  class="'.$color.'"> C </span></p>';
									
								}
								else
								{
								 $str_vask.='<p class="paymentstatus"><span  class="'.$color.'">'.ucfirst($status).'</span></p>';
								}
							 
						  }
						
						$str_vask.='    </div>
						   <div class="clearfix"></div>
						   <hr>
						 </div> 
					  ';
					$prev_date =  $date;  
				  }
			  }
			 
			 //for  rens 
			 $prev_date ='';
			 $date = '';
			 $str_rens ='';
			 $status ='';
			 if(count($rens_arr) >0 ){
			  $rensstatus=1;
			  for($z=0;$z<count($rens_arr);$z++){
					$date = $rens_arr[$z]['utlevering'];
					
					if(($z==0) || ($date!=$prev_date)){
						$rowid ='';
						foreach($row as $key2 => $value2) {
							$day = $key2;
							if($day==$date){
								$rowid	= implode('@',$row[$key2]['rens']);
							}
						}
						$str_rens.='<div class="row">
						<div class="pull-left col-md-3 no-padd text-center">
						</div> 
						<div class="pull-right col-md-6 no-padd  customer-info">
						   <div class="input-group" >
						   <div class="pull-left saldo">Utlevering: </div> 
						   <input type="text" id="'.$rowid.'" class="form-control datepicker" value="'.$date.'">
							<div class="input-group-addon">
								<span class="glyphicon glyphicon-th"></span>
							</div>
						</div>
					   </div>
					   </div>';
					   $rowid = '';
					}
					
					
					$reklama = ($rens_arr[$z]['complain'] =='1' ) ? '<span class="orange-text">Reklamasjon</span>' : '';
					$renses =  ($rens_arr[$z]['in_house'] =='1' ) ? '<span class="purple-text">Renses på huset</span>' : '';
					
					
			  		$str_rens.='
						  <div class="orderline mt-sm">
						   <div class="pull-left col-md-2 no-padd text-center">';
						   
							/*$str_rens.=' <div class="count">'.$rens_arr[$z]['qty'].'</div>
							<div class="round">
								<div style="background-image: url(\''.$this->data['vars']['site_url'].'\images/'.$rens_arr[$z]['thumb'].'\');" class="img"></div>
							</div> ';*/
							
					//	$str_rens.='<div class="round"><div class="img">'.$rens_arr[$z]['qty'].'</div></div>';
					
						$str_rens.='<div class="round1"><div class="img1"><p><span>'.$rens_arr[$z]['qty'].'</span></p></div></div>';
							               
							              
						$str_rens.='   </div>
						
						   <div class="pull-left col-md-5 no-padd">
						   <p><span>'.$rens_arr[$z]['name'].' '.$rens_arr[$z]['orderline_emp'].'</span>
							'.$rens_arr[$z]['description'].$reklama.$renses.'</p>
						   </div>
						   
						   <div class="pull-left col-md-3 no-padd text-right">
						   <p><span>kr '.formatcurrency($rens_arr[$z]['subtotal']).'</span></p></div>
						
						
						  
						   <div class="pull-left col-md-2 no-padd text-center">';
						   
						  if($rens_arr[$z]['status'] == 'pending')
						  {
						  
						  $resstatus=$this->settings_order_model->getCancelstatus($order_id,$rens_arr[$z]['id']);
								if($resstatus)
								{
									
									$color = ($rens_arr[$z]['status']=='canceled') ? 'red' :  'orange';
									
									$str_rens.=' <p class="paymentstatus"><span  class="'.$color.'">C</span></p>';
								}
								else
								{
								 
								$str_rens.=' <a class="editprod" data-toggle="modal" data-title="'.$rens_arr[$z]['name'].'"  href="#editProductModal" data-stuff="'.$rens_arr[$z]['id'].'@'.$rens_arr[$z]['name'].'@'.$rens_arr[$z]['price'].'@'.$rens_arr[$z]['path'].'@'.$rens_arr[$z]['description'].'@'.$rens_arr[$z]['qty'].'@'.$rens_arr[$z]['gtype'].'@'.$rens_arr[$z]['complain'].'@'.$rens_arr[$z]['in_house'].'@'.$rens_arr[$z]['oprice'].'@'.$rens_arr[$z]['product'].'"><img src="img/plus-minus.png" alt="Edit" title=""/></a>';
								
								}
						  }
						  else{
							  
							  $color = ($rens_arr[$z]['status']=='canceled') ? 'red' : (($rens_arr[$z]['status']=='paid') ?  'green' : 'orange');

							  $status = ($rens_arr[$z]['status']=='canceled') ? 'C' : (($opstatus == 'paid') ?  'BE' : 'UB');	
							  
							//  $str_rens.='<p class="paymentstatus"><span  class="'.$color.'">'.ucfirst($status).'</span></p>';
								$resstatus=$this->settings_order_model->getCancelstatus($order_id,$rens_arr[$z]['id']);
								if($resstatus)
								{
									$color = ($rens_arr[$z]['status']=='canceled') ? 'red' :  'orange';
									
									$str_rens.=' <p class="paymentstatus"><span  class="'.$color.'">C</span></p>';
								}
								else
								{
								 $str_rens.='<p class="paymentstatus"><span  class="'.$color.'">'.ucfirst($status).'</span></p>';
								}
						  }
						
							
						 $str_rens.='</div>
						   <div class="clearfix"></div>
						   <hr>
						 </div> 
					  ';
					$prev_date =  $date;  
				  }
			 }
			  
			}//if
		
			if($rensstatus == '1')
			{
				$str_rens = '<style>#rens_div{display:block;}</style>' . $str_rens;
			}
			else
			{
				$str_rens ='<style>#rens_div{display:none!important;}</style>' . $str_rens;
			}
			
			if($vaskstatus == '1')
			{
				$str_vask='<style>#vask_div{display:block;}</style>' . $str_vask;
			}
			else
			{
				$str_vask='<style>#vask_div{display:none!important;}</style>' . $str_vask;
			}
			
			$total=array_sum($subtotalarray);
			$subtotal=$total;
			$cus_sub_total=$subtotal;
			$customerid = $customer['customerid'];
			$zone = $customer['zone'];
			$delivery_type = 'normal'; //default
			$this->data['reg_fields'][] = 'delivery';
			$this->data['fields']['delivery'] =$this->general_model->checkMinimumAmount($delivery_type,$customerid,$total,$zone);
			
			$min_price=$this->data['fields']['delivery']['min_price'];
			$min_price_txt = '';
			$min_price_status=0;
			if($cus_sub_total < $min_price)
			{
				$min_price_txt =  ' (Minste beløp kr '.formatcurrency($min_price).')';
				$min_price_status=1;
			}
			
			$subtotal = ($subtotal < $min_price) ?  $min_price : $subtotal;
			$delsum=$subtotal;
			
			$delviery = ($subtotal >= $this->data['fields']['delivery']['free_delivery_after']) ?  '0' : $this->data['fields']['delivery']['delivery_charge'];
			
			$old_delivery_charge = $this->data['fields']['delivery']['delivery_charge'];


			$discountstatus=0;
			$vouchercode='';
			$discount=0;
			if(intval($orderinfo['voucher']) > 0)
			{
				$discountstatus=1;
				$qry="SELECT *,ROUND(price, 0) as price FROM a_voucher WHERE id='".$orderinfo['voucher']."'";
				$query = $this->db->query($qry);
				$vdata = $query->row_array();//multi row array
				
				$vouchercode=$vdata['voucher'];
				
				if($vdata['percentage'] != '')
				{
					$percentage=$vdata['percentage']/100;
					$discount=$subtotal*$percentage;
					
					
				}
				else
				{
					$discount=$vdata['price'];
				}
				if(($vdata['free_delivery_charge'] == 1)  && 	($orderinfo['type'] != 'shop'))
				{
					$delviery=0;
				}
				else
				{
					$delviery=$old_delivery_charge;
				}
				
				$fprice=$cus_sub_total-$discount;
			
					if($fprice < $min_price)
					{
						$min_price_status=2;
					}
			}
			else{
				//if the discount in % or kr
				if($orderinfo['order_discount'] !=''){
					$vouchercode = $orderinfo['order_discount'];
					if(stripos($vouchercode, '%')){
						$percentage = str_replace("%","",$vouchercode);
						$discount =  $delsum * ($percentage/100);
					}
					else{
						
						$discount = str_replace("kr ","",$vouchercode);
						$vouchercode = "kr ".formatcurrency($discount);
					}
					
				}

			}
			
			$newdiscount=$discount;
			
	
			
			
			if($orderinfo['type'] == 'shop')
			{
				$delviery=0;
				$min_price_status=0;
			}
			
		
			$totalamount=$orderinfo['total_amount'];
			if(trim($orderinfo['changed_amount']) != '')
			{
				$totalamount=$orderinfo['changed_amount'];
			}
			
			$paidamountstatus=0;
			$pendingamountstatus=0;
			$betalstatus=1;
			$waitamountstatus=0;
			$totawaitamount=0;
			if(count($waitamountarray) > 0)
			{
				$waitamountstatus=1;
				$totawaitamount=array_sum($waitamountarray);
			}
		
			if(count($paidamountarray) > 0)
			{
				$paidamountstatus=1;
				$totapaidamount=array_sum($paidamountarray);
				
			}

			
							//echo '<pre>';print_r($paidamountarray);exit;
			if($pendingstatus == 1 && $discount > 0)
			{
					 if(intval($totapaidamount) > 0)
					 {
						if($discountstatus == 0)
						{
							if($totapaidamount >= $discount)
							{
								$discountapplystatus=1;
								$totapaidamount=$totapaidamount-$discount;
							}
							else
							{
								if($discount >= $totapaidamount)
								{
									$discountapplystatus=1;
									$discount=$discount-$totapaidamount;
									$totapaidamount=0;
								}
							}
						}
						
					  }
					  
					 if(intval($totawaitamount) > 0)
					 {
					 
				
						
						if($discountstatus == 0)
						{
							if($totawaitamount >= $discount)
							{
								$discountapplystatus=1;
								$totawaitamount=$totawaitamount-$discount;
							}
							else
							{
								if($discount >= $totawaitamount)
								{
									$discountapplystatus=1;
									$discount=$discount-$totawaitamount;
									$totawaitamount=0;
								}
							}
								
						}
							 	
					}  
					
					if(intval($totapaidamount) == 0 && intval($totawaitamount) == 0)
					{
					
						$balanceamount = $totalamount;
						
					}
					else
					{
						$balanceamount= $totalamount-$totapaidamount;
						$balanceamount= $balanceamount-$totawaitamount;
					}
					
	
			}
			else
			{
			
			
				if($discountstatus == 0 && $discount > 0)
				{
						$totalpayamt=($totapaidamount+$totawaitamount) - $orderinfo['total_amount'];
						$discountapplystatus=1;
						$balanceamount=$totalpayamt-$discount;
						
							if($totapaidamount >= $discount)
							{
								$totapaidamount=$totapaidamount-$discount;
							}
							else
							{
								if($discount >= $totapaidamount)
								{
									$discount=$discount-$totapaidamount;
								}
							}
							
							if($discount > 0)
							{
								if($totawaitamount >= $discount)
								{
									$discountapplystatus=1;
									$totawaitamount=$totawaitamount-$discount;
								}
								else
								{
									if($discount >= $totawaitamount)
									{
										$discountapplystatus=1;
										$discount=$discount-$totawaitamount;
										$totawaitamount=0;
									}
								}
							}
							
							
								
						
						
						
						
						
					
				}
				else
				{
					//$balanceamount= $orderinfo['total_amount']-$totapaidamount;
					//$balanceamount= $balanceamount-$totawaitamount;
					if(count($paidamountarray) > 0)
					{
						//$paidamountstatus=1;
						//$totapaidamount=array_sum($paidamountarray);
						$balanceamount= $totalamount-$totapaidamount;
						$balanceamount=$balanceamount-$totawaitamount;
					
						if(intval($balanceamount) > 0)
						{
							$pendingamountstatus=1;
						}
						else
						{
							$betalstatus=0;
						}
					}
					else if((count($waitamountarray) > 0) || (count($paidamountarray) > 0)){
						
						$pendingamountstatus=1;
						$balanceamount=$totalamount-$totawaitamount;
						
					}
				}
			}
			
			
			
			
			
			
			
			if(count($paidamountarray) > 0 || count($waitamountarray) > 0)
			{
					if(intval($balanceamount) > 0)
					{
						$pendingamountstatus=1;
					}
					else
					{
						$betalstatus=0;
					}
			
			}
			
			if(intval($pendingstatus) > 0)
			{	
				$betalstatus=1;
				
			}
			
			
			
			/*
			if(count($paidamountarray) > 0)
			{
				//$paidamountstatus=1;
				//$totapaidamount=array_sum($paidamountarray);
				$balanceamount= $totalamount-$totapaidamount;
				
					$balanceamount=$balanceamount-$totawaitamount;
				
					if(intval($balanceamount) > 0)
					{
						$pendingamountstatus=1;
					}
					else
					{
						$betalstatus=0;
					}
			}
			else if((count($waitamountarray) > 0) || (count($paidamountarray) > 0)){
				
				$pendingamountstatus=1;
				$balanceamount=$totalamount-$totawaitamount;
				
			}
			*/
			$deliveryreadystatus=0;
			if($betalstatus == 0)
			{
				if($orderinfo['order_status'] != 9)
				{
					$deliveryreadystatus=1;
				}
			}
			
				//echo $deliveryreadystatus;exit;
				
			
			$count = $this->cart->total_items();
			$delsumamt=$totalamount/1.25;	
			$delsumamt=round($delsumamt, 2);
			$delsumvat=$totalamount-$delsumamt;	
			
			
			
				$data=$this->customer_model->getCustomerinfo($cus_id);
				$mobileinfo=$this->customer_model->customermobileinfo($data['mobile']);	
				$newdata = array('customer'  => $data);
				$this->session->set_userdata($newdata);
				$newdata = array('pos_customer_id'  => $cus_id);
				$this->session->unset_userdata('pos_customer_id');
				$this->session->set_userdata($newdata);
				
				$data = $this->payments_model->getAccountBalance($cus_id);
				$pendingsaldo = $data['pending'];
				$paidsaldo = $data['paid'];
				$saldocolor='0';
				if($paidsaldo < 0)
				{
					$saldocolor='1';
				}
		
				if(intval($pendingsaldo) > 0)
				{	
					$amount=formatcurrency($paidsaldo).' ('.formatcurrency($pendingsaldo).')';
				}
				else
				{
					$amount=formatcurrency($paidsaldo);
				}
				
				$profile='<p><span>'.$mobileinfo['firstname'].' '.$mobileinfo['lastname'].'</span> (+47) '.$mobileinfo['mobile'].' <br>
                        <a href="mailto:'.$mobileinfo['email'].'">'.$mobileinfo['email'].'</a> '.$mobileinfo['partner_branch_name'].' </p>';
			
			
			$deliverytime='';
			$empinitial='';
			if($orderinfo['order_status'] == 9)
			{
				$deliverytime=$orderinfo['deliverytime'];
				if(intval($orderinfo['employee_p_branch']) > 0)
				{
					$orderlineemp = $this->employee_model->getEmployeebranchDetail(0,0,$orderinfo['employee_p_branch']);
					$empinitial=$orderlineemp['initial'];
				
				}
				
			}
			
			$cancelinfo = $this->settings_order_model->getCancelOrderhistory($order_id);
			$cancelstatus=0;
			$reason='';
			$comment='';
			if(count($cancelinfo) > 0)
			{
				$cancelstatus=1;
				$reason=$cancelinfo[0]['reason'];
				$comment=$cancelinfo[0]['comment'];
				//echo '<pre>';print_r($cancelstatus);exit;
			}
			
			
			
			if(intval($totalamount) == 0)
			{
				$betalstatus=0;
			}
			
			$this->data['visible']['canceled_btn'] = 1 ;
			
			if($orderinfo['order_status'] == 11)
			{
				$betalstatus=0;
				$deliveryreadystatus = 0;
				$this->data['visible']['canceled_btn'] = 0;
			}
			
			
			$result = array("order_list_rens"=>$str_rens,"order_list_vask"=>$str_vask,"delsum"=>$total,"mva"=>$delsumvat,"count"=>$count,"instruction"=>$instruction,"delsum_currency"=>formatcurrency($total),"mva_currency"=>formatcurrency($delsumvat),
			"delviery"=>$delviery,"vouchercode"=>$vouchercode,"discount"=>$newdiscount,"min_price_status"=>$min_price_status,"min_price"=>$min_price,'min_price_txt'=>$min_price_txt,'total_amount_currency'=>formatcurrency($totalamount),'total_amount'=>$totalamount,'discount_currency'=>formatcurrency($newdiscount),'paidamount'=>formatcurrency($totapaidamount),'totawaitamount'=>formatcurrency($totawaitamount),'balanceamount'=>formatcurrency($balanceamount),'paidamountstatus'=>$paidamountstatus,'pendingamountstatus'=>$pendingamountstatus,'betalstatus'=>$betalstatus,'deliveryreadystatus'=>$deliveryreadystatus,'waitamountstatus'=>$waitamountstatus,'order_id'=>'#'.$order_id,'profile'=>$profile,'amount'=>$amount,'saldocolor'=>$saldocolor,'deliverytime'=>$deliverytime,'empinitial'=>$empinitial,'cancelstatus'=>$cancelstatus,'reason'=>$reason,'comment'=>$comment);
		
			echo json_encode($result);exit;
	
	 }
	 
	 
	 
	 
	
	/*get bag details*/
	function __getBagdetails()
	{
		//profiling
		$this->data['controller_profiling'][] = __function__;
		
		
		$order_id = ($this->uri->segment(3)) ? trim(urldecode($this->uri->segment(3))) : '';
		$action = ($this->uri->segment(4)) ? trim($this->uri->segment(4)) : '';
		$this->data['vars']['iframe_order']=intval($order_id);

		if($action == 'edit')
		{
			$this->data['visible']['edit_order'] = 1;
			$this->data['vars']['edit_order']=intval($order_id);
		}
		
		
		$this->data['visible']['bekreft_status']=$this->process_order_model->getOrderlogstatus($order_id,5);
		
		
	
		

		
			if(isset($_POST['barcode']))
			{
				$newdata = array('barcode'  => $_POST['barcode']);
				$this->session->unset_userdata('barcode');
				$this->session->set_userdata($newdata);
				$barcode = $_POST['barcode']; // barcode of a bag
			}
			else
			{
				if(intval($order_id) > 0)
				{
					$barcode = $order_id;
				}
				else
				{
					$barcode = $this->session->userdata['barcode'];
				}
			}
			
			if(intval($barcode) == 0)
			{
				$newarray=array('status'=>'error','message'=>'Invalid Barcode');
				
			}
			if(strlen($barcode) != 8)
			{
				$newarray=array('status'=>'error','message'=>'Invalid Barcode');
				
			}
			
			
			
			$type='laundry';
			$baginfo=$this->orders_model->getBagBarcodeinfo($barcode,$type);
			
			
			
			$this->data['reg_blocks'][] = 'blk1';
			$str='';
			if($baginfo)
			{
				
				
				$bag=$baginfo['id'];
			
				$binfo=$this->orders_model->getBagOrder($bag);
				
				$orderid=$binfo['order'];
				$blogid=$binfo['id'];
				$this->data['blocks']['blk1'] = $this->process_order_model->getOrders($bag,$orderid,$blogid);
				
					
				
			}
			else
			{

				$binfo=$this->process_order_model->getHeatBarcodeinfo($barcode);
			
				if($binfo)
				{
					$bag=$binfo['bag'];
					$orderid=$binfo['order'];
					$blogid=$binfo['id'];
					$this->data['blocks']['blk1'] = $this->process_order_model->getOrders($bag,$orderid,$blogid);
					if(count($this->data['blocks']['blk1']) == 0)
					{
						$this->data['blocks']['blk1'][0] = $this->orders_model->getOrderinfo($barcode);
					}
						
				}
				else
				{
					$this->data['blocks']['blk1'][0] = $this->orders_model->getOrderinfo($barcode);
				}
				
				
			}
		
		///echo '<pre>';print_r($this->data['blocks']['blk1']);exit;
        //get results and save for tbs block merging
		if(count($this->data['blocks']['blk1'] > 0)) {
			
			for($i=0;$i<count($this->data['blocks']['blk1']);$i++){
			
			$order_id=$this->data['blocks']['blk1'][$i]['id'];	
			
			
			
			$customer = $this->orders_model->getCustomerDetails($order_id);
			
			$data=$this->customer_model->getCustomerinfo($customer['customer']);
			$this->session->unset_userdata('customer');
			$newdata = array('customer'  => $data);
			$this->session->set_userdata($newdata);
			$newdata = array('pos_customer_id'  => $customer['customer']);
			$this->session->unset_userdata('pos_customer_id');
			$this->session->set_userdata($newdata);
				
			
			
			$orderinfo = $this->orders_model->getOrderinfo($order_id,'scan');
		
			$order_status = $orderinfo['order_status'];
			
			$this->data['visible']['wi_show_order'] = 0;
			  $this->data['visible']['bekreft_status']=0;
			
			if(!empty($orderinfo)){
				$this->data['visible']['wi_show_order'] = 1;
				  $this->data['visible']['bekreft_status']=1;
				
				
			if($order_status == 3){
				$this->data['visible']['wi_show_process_button'] = 1;
			}
			
			//get shop detail where order placed ( from SL or branch)
			$order_from = $orderinfo['type'];
			
			$ordered_branch = '';
			
			if($order_from == 'shop'){
				
				$partnerinfo = $this->general_model->getPartnerDetails($orderinfo['partner'],$orderinfo['partner_branch']);
				$company = $partnerinfo['name'];
				$address = $partnerinfo['street'] .", ". $partnerinfo['zip']. ' '.$partnerinfo['city'];
				$phone = $partnerinfo['phone']; 
				

			}
			else{
				$company = $this->data['settings_company']['company_name'];
				$address = $this->data['settings_company']['company_address_street'] .", ". $this->data['settings_company']['company_address_zip']. ' '.$this->data['settings_company']['company_address_city'];
				$phone = $this->data['settings_company']['company_telephone']; 
			}
			
			
			$ordered_branch =  ($company != $this->session->userdata['partner_branch_name']) ? $company : '';
			
             $company_info ='<div class="company-info">
			     <div class="list-button">';
				
				
				  if($this->session->userdata['partner_branch'] !='1')
					$company_info .='<a href="#tagproduct" onclick="printTagOrder(\''.$order_id.'\');" id="printTagbtn" data-toggle="modal" class="btn-icon"><i class="fa fa-tags" aria-hidden="true"></i></a>';
				 
				 if($this->session->userdata['partner_branch'] != 1000)
				{
					$company_info .='<a href="#editproduct" onclick="editOrder(\''.$order_id.'\');" id="editproductbtn" data-toggle="modal" class="btn-icon"><i class="fa fa-edit" aria-hidden="true"></i></a>';
				}
				 
				

				   			$company_info .='<a href="#orderHeatSeal" onclick="damageheatseal(\''.$order_id.'\');"  data-toggle="modal" class="btn-icon"><i class="fa fa-camera" aria-hidden="true"></i></a>
							
				   			<a href="javascript:void(0);" onclick="heatsealprint(\''.$order_id.'\');"  data-toggle="modal" class="btn-icon hsbutton"><i class="fa fa-print" aria-hidden="true"></i></a>

                   </div>

                <p class="text-center"><span>'.$company.'</span><br>
                  '.$address.'<br>
                  '.$phone.'</p>
              </div>
              <hr>';
			  
			}
			
			$this->data['lists']['company_info']=$company_info;
			
			$orderdetails = $this->orders_model->getOrderLine($order_id);
			$collectiontinfo=$this->orders_model->getCollectionDeliverytime('collection',$orderinfo['collection_time']);
			$deliveryinfo=$this->orders_model->getCollectionDeliverytime('delivery',$orderinfo['delivery_time']);
		
			$address=$customer['street_line_1'];
			if($customer['street_line_2'] != '')
			{
				$address.=', '.$customer['street_line_2'];
			}
			
			$data=$this->orders_model->getCustomerOrderhistory($customer['customer'],'','scan');
			$orderhistory = array();
			if(count($data) > 0)
			{
				foreach($data as $orders)
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
					<div class="col-md-12 day">'.$datetitle.'</div>';
					if(count($orderitems) > 0)
					{
						foreach($orderitems as $oitems)
						{
							
							$company = ($oitems['type'] == 'shop') ?  $oitems['partner_branch'] :  $this->data['settings_company']['company_name'];
							
							
							//$branch =  ($company == $this->data['settings_company']['company_name']) ?  '':  (($company != $this->session->userdata['partner_branch_name']) ? '('.$company .')' :  ''); 
							$special_chars = 'ø';// all the special characters you want to check for 
			if (preg_match('/'.$special_chars.'/', $company))
			{
				$companyinitial=substr($company,0,4);
			}
			else
			{
				$companyinitial=substr($company,0,3);
			}
			$branch =  ($company == $this->data['settings_company']['company_name']) ?  '('.$companyinitial.')':  (($company != $this->session->userdata['partner_branch_name']) ? '('.$companyinitial.')' :  '');
							
							
						    $amount = ($oitems['changed_amount']!='') ? $oitems['changed_amount'] :  $oitems['total_amount'];
							

							$o_time=$datetitle=date('H:i',strtotime($oitems['order_time']));;
							$customerorderdetails.='<div class=" order-list row">
							<div class="col-md-9">
							<p><a href="#" rel="'.intval($oitems['id']).'" class="green-text"> #'.$oitems['id'].' '.$branch.'</a><span>kr '.formatcurrency($amount).'</span>
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
			
		//	<span class="green-text"> (5) </span>
		
			if(!empty($orderinfo)){
			   $customer_detail='<div class="customer-detail mt-sm">
                   <div class="pull-left col-md-6 no-padd">
                   <p><span>Ordrenr: </span>#'.$order_id.'</p>
				   <input type="hidden" id="ordrenr" name="ordrenr" value="'.$order_id.'" />
                   <p><span>Telefon: </span>'.$customer['number'].'<br>
                    <span>Navn: </span>'.$customer['customer_name'].'</br>';
					
					if($collectiontinfo['sdate'] != '')
					{
						$customer_detail.='<span>Henting: </span>'.$collectiontinfo['sdate'].' &nbsp; '.$collectiontinfo['stime'].'-'.$collectiontinfo['etime'].'<br>';
					}
					else{
						$customer_detail.='<span>Innlevering: </span>'.date('d.m.Y H:i',strtotime($orderinfo['order_time'])).'<br>';
					}
				
				 $customer_detail .='</p>
                   </div>';      	
					
                 $customer_detail .='
				 <div class="pull-left col-md-6 no-padd">
				 <p><span>Adresse: </span>'.$address.', '.$customer['zip'].' '.$customer['city'].' <br>';
					
					if($customer['floor'] != '')
					{
				   $customer_detail.='<span>Etg: </span>'.$customer['floor'].' <br>';
				   }
				   if($customer['calling_bell'] != '')
					{
				   $customer_detail.='<span>RK: </span>'.$customer['calling_bell'].' <br>';
				   }
			
				
				if(($order_from != 'shop')){
					$customer_detail.='<span>Levering: </span>'.$deliveryinfo['sdate'].'  &nbsp; '.$deliveryinfo['stime'].'-'.$deliveryinfo['etime'];
				}


				 $customer_detail .='</p>
                   </div>';      	
				
                   $customer_detail .='          
                   <div class="clearfix"></div>
                   <hr>
                 </div> '; 
				 
			}
				              
			 $this->data['lists']['customer_detail']=$customer_detail;
				 
			
		//echo '<pre>';print_r($customer);exit;
		$this->data['blocks']['blk1'][$i]['cdate']  = date("d.m.Y",strtotime($this->data['blocks']['blk1'][$i]['cstime']));	 
		$this->data['blocks']['blk1'][$i]['ctime']  = date("H:i",strtotime($this->data['blocks']['blk1'][$i]['cstime'])) ."->" . date("H:i",strtotime($this->data['blocks']['blk1'][$i]['cetime'])); 
		$this->data['blocks']['blk1'][$i]['ddate']  = date("d.m.Y",strtotime($this->data['blocks']['blk1'][$i]['dstime']));	 
		$this->data['blocks']['blk1'][$i]['dtime']  = date("H:i",strtotime($this->data['blocks']['blk1'][$i]['dstime'])) ."->" . date("H:i",strtotime($this->data['blocks']['blk1'][$i]['detime']));
		
				 
		
			//get results and save for tbs block merging
			$data = $this->orders_model->getOrderLine($this->data['blocks']['blk1'][$i]['id']);
					if($order_status >= 5)
					{
						$this->data['visible']['bekreft_status']=0;
					}
	
			if(!empty($data)){
			
	
				$delsum = 0;
				$lstatus=1;
				$orderlinedelivery=array();
				$hstatus=0;
				for($j=0;$j<count($data);$j++){
					
       				$arr = $this->orders_model->getProductDisplayName($data[$j]['product']);
					
					$data[$j]['name'] = $arr['name'];
					
					
				
					$heatsealstatus=$this->process_order_model->getHeatsealStatus($data[$j]['product'],$this->session->userdata['partner_branch']);
					$data[$j]['heatsealstatus']=1;
					if($heatsealstatus)
					{
						//echo '<pre>';print_r();exit;
						$data[$j]['heatsealstatus']=$heatsealstatus['heatseal'];
					}
					
					if($order_status >= 5)
					{
						$data[$j]['heatsealstatus']=0;
						
					}
					
					if($data[$j]['heatsealstatus'] > 0)
					{	
						$hstatus=1;
					}
					
					
					
					
					$sqty = $this->process_order_model->getHeatOrderLine($data[$j]['id']);
					
					
					
					$proqty = $this->process_order_model->validateProducttype($data[$j]['product']);
				
					//$proqty=$data[$j]['qty'];
				
					if(trim($data[$j]['changed_quantity']) != '')
					{
							$data[$j]['quantity']=$data[$j]['changed_quantity'];
							$subtotal =  $data[$j]['changed_amount'];
						
					}
					else{
						
						$subtotal = $data[$j]['amount'];

					}
					
					if (round($data[$j]['quantity'], 0) == $data[$j]['quantity'])
					{
						// is whole number
						$data[$j]['quantity'] = round($data[$j]['quantity'], 0);
					}					
					
					
					
					if($data[$j]['p_b_delivery_time'] != '' && $data[$j]['p_b_delivery_time'] != '0000-00-00')
					{
						$p_b_delivery_time = date('d.m.Y',strtotime($data[$j]['p_b_delivery_time']));
						$orderlinedelivery[]= '<span class="pname"> '.$data[$j]['name'].'</span>&nbsp;&nbsp; '.$p_b_delivery_time;
					}
					else{
						$p_b_delivery_time = '';
					}
					
					 $discount = $this->products_model->getProDiscount($data[$j]['product']);
					
					 $discount=$discount[0];
					 $ddesc='&nbsp;';
					 if(isset($discount['description']))
					 {
						$ddesc=' ('.$discount['description'].')';
					 }
					
				
					
					$productPrice=$subtotal;
					$productPrice=round($productPrice);
						
					
					
					$subtotalarray[]= $productPrice;
					
					
					//$delsum += $data[$j]['price'] * $data[$j]['quantity'];
					$delsum=$productPrice;
					$path_parts = pathinfo($data[$j]['path']);
					$img = $path_parts['filename'] .".".$path_parts['extension']; 
					//$total = ($delsum + $delivery_charge) - $discount;
					if($data[$j]['quantity'] >= $sqty)
					{
						$newquantity=$data[$j]['quantity']-$sqty;
					}
					else
					{
						$newquantity=0;
					}
					
					$newquantity=$proqty*$newquantity;
					
					$onclick="";
					
					if(count($data) == $lstatus)
					{
						if(intval($newquantity) > 0)
						{
							if(intval($data[$j]['heatsealstatus']) > 0)
							{
								$onclick=' onclick="setNeworderline(\''.$data[$j]['id'].'\',\'1\',\''.$proqty.'\');"';
								
								$str.=' <div style="cursor:pointer;"  id="tr_'.$data[$j]['id'].'" class="orderlist cart_table_item">';
							}
							else
							{
								$str.=' <div id="tr_'.$data[$j]['id'].'" class="orderlist noheatseal">';
							}
							
						}
						else
						{
							$str.=' <div id="tr_'.$data[$j]['id'].'" class="orderlist noheatseal">';
						}
						
						$newlstatus=1;
					}
					else
					{
						if(intval($newquantity) > 0)
						{
							if(intval($data[$j]['heatsealstatus']) > 0)
							{
								$onclick=' onclick="setNeworderline(\''.$data[$j]['id'].'\',\'0\',\''.$proqty.'\');"';
								
								$str.='<div style="cursor:pointer;"  id="tr_'.$data[$j]['id'].'" class="orderlist cart_table_item">';
							}
							else
							{
								$str.='<div id="tr_'.$data[$j]['id'].'" class="orderlist noheatseal">';
							}
							
						}
						else
						{
							$str.='<div id="tr_'.$data[$j]['id'].'" class="orderlist noheatseal">';
						}
						$newlstatus=0;
						
					}
					$actualqty=$data[$j]['quantity']*$proqty;
					
					if(intval($current_partner_branch) == 1000) //hvittsnip branch  ( only this branch aceept orders from comapny)
					{	
									
						$str .= '
						<div class="pull-left col-md-1">';
						if(intval($data[$j]['heatsealstatus']) == 0)
						{
							$str.='<p><input disabled="disabled" checked="checked" type="checkbox" name="orderlinechk[]" id="orderlinechk_'.$data[$j]['id'].'" value="'.$data[$j]['id'].'" /></p>';
						}
						else if(intval($data[$j]['heatsealstatus']) > 0 && $newquantity > 0)
						{
							$str.='<p><input type="checkbox" name="orderlinechk[]" onclick="skipOrderlineScan(this,\''.$data[$j]['id'].'\');" id="orderlinechk_'.$data[$j]['id'].'" value="'.$data[$j]['id'].'" /></p>';
						}
						else
						{
							$str.='<p><input disabled="disabled"  checked="checked" type="checkbox" name="orderlinechk[]" id="orderlinechk_'.$data[$j]['id'].'" value="'.$data[$j]['id'].'" /></p>';
						}
						$str.='</div> ';
				   
				   		$str.='<div class="pull-left col-md-3 no-padd" '.$onclick.'>';
					}
					else{
						$str.='<div class="pull-left col-md-4" '.$onclick.'>';
					}
				   
				   
					$str.='
                   <p>'.$data[$j]['name'].'</p>
                   </div>  
					<div class="pull-left col-md-2 no-padd text-center" '.$onclick.'>
                  	 <p>'.$p_b_delivery_time.'</p>
                   </div> 
                   <div class="pull-left col-md-2 no-padd text-center" '.$onclick.'>';
					$str.='<p>kr '.formatcurrency($data[$j]['price']).'</p>';
                    $str.='</div>
				   
                   <div class="pull-left col-md-2 no-padd text-center" '.$onclick.'>
                    <input type="hidden" value="'.$proqty.'" id="sqty_'.$data[$j]['id'].'" />
					<input type="hidden" value="'.$newquantity.'" id="newquantity_'.$data[$j]['id'].'" />
					<input type="hidden" value="'.$data[$j]['product'].'" id="product_'.$data[$j]['id'].'" />
					<span class="antall">';
					if(intval($data[$j]['heatsealstatus']) == 0)
					{
						 $str.='<font style="display:none;" data="0" id="qty_'.$data[$j]['id'].'">0</font>'; 
						if($order_status >= 5)
						{
						
							if($heatsealstatus['heatseal'] == 1)
							{
					$str.='<font data="0" id="qty1_'.$data[$j]['id'].'">'.$data[$j]['quantity'].'</font>'; 
							}
							else
							{
									$str.='<font data="0" id="qty1_'.$data[$j]['id'].'">0</font>'; 
							}
						
					
						 $str.=' / '.$data[$j]['quantity'].'</span>';
						}
						else
						{
							 $str.='<font data="0" id="qty1_'.$data[$j]['id'].'">0</font>'; 
						 $str.=' / '.$data[$j]['quantity'].'</span>';
						}
						 
					}
					else
					{
						 $str.='<font style="display:none;" data="'.$newlstatus.'" id="qty_'.$data[$j]['id'].'">'.$newquantity.'</font>';
						 
						  $str.='<font  data="'.$newlstatus.'" id="qty1_'.$data[$j]['id'].'">'.$sqty.'</font>';
						  
						$str.=' / '.$data[$j]['quantity'].'</span>';
						 
						 
					}
					
					 
                   $str.='</div>
                   <div class="pull-left col-md-2   text-right" '.$onclick.'>
                    <p class="tprice"> kr '.formatcurrency($productPrice).'</p>
                   </div>
                   <div class="clearfix"></div>
                   <hr>
                 </div>';
				 
				 
					 
					
$lstatus++;
				}
				
				if($hstatus == 0)
					{
						$this->data['visible']['bekreft_status']=0;
					}
			if(count($orderlinedelivery) > 0)
			{
				/*$orderlinedelivery_time =implode('<br>',$orderlinedelivery);
				
				$orderlinedelivery_time = '<span>Levering: </span><br>' . $orderlinedelivery_time;
				
				$orderlevering= $orderlinedelivery_time;*/
			}
			
			if(!empty($orderinfo)){
	
				$this->data['lists']['customer_detail'].= $orderlevering;
			}
	
		$subtotal=array_sum($subtotalarray);
		
		
		$cus_sub_total=$subtotal;
		$customerid = $customer['customerid'];
		$zone = $customer['zone'];
		$delivery_type = 'normal'; //default
		$this->data['reg_fields'][] = 'delivery';
		$this->data['fields']['delivery'] =$this->general_model->checkMinimumAmount($delivery_type,$customerid,$subtotal,$zone);
		$min_price= $this->data['fields']['delivery']['min_price'];
		$min_price_txt = '';
		if($orderinfo['type'] != 'shop')
		{
			if($cus_sub_total < $min_price)
			{
				$min_price_txt =  ' (Minste beløp kr '.formatcurrency($min_price).')';
			}
			
			$subtotal = ($subtotal < $min_price) ?  $min_price : $subtotal;
		}
		
		
		$delsum=$subtotal;
		$this->data['debug'][] = $this->general_model->debug_data;	
		$delviery = ($subtotal >= $this->data['fields']['delivery']['free_delivery_after']) ?  '0' : $this->data['fields']['delivery']['delivery_charge'];
		$old_delivery_charge=$this->data['fields']['delivery']['delivery_charge'];
		
		$summary='   <div class="handlekurv">   
               <div class="col-md-6">
               </div>
               <div class="col-md-6 no-padd">
                  <div class="row mt-sm">
                   <div class="pull-left col-md-6 no-padd">
                   <p class="grey-text">Delsum'.$min_price_txt.'</p>
                   </div>                
                   <div class="pull-left col-md-6  text-right">
                     <p>kr '.formatcurrency($subtotal).'</p>
                   </div>
                   <div class="clearfix"><input type="hidden" id="ordrenr" name="ordrenr" value="'.$order_id.'" /></div>
                  <hr>
                 </div>';
				 
				 
		//if the discount is voucher		 
		$discount=0;	 
		if(intval($orderinfo['voucher']) > 0)
		{
			$qry="SELECT *,ROUND(price, 0) as price FROM a_voucher WHERE id='".$orderinfo['voucher']."'";
			$query = $this->db->query($qry);
			$vdata = $query->row_array();//multi row array
			
			$vouchercode=$vdata['voucher'];
			
			if($vdata['percentage'] != '')
			{
				$percentage=$vdata['percentage']/100;
				$discount=$subtotal*$percentage;
				
				
			}
			else
			{
				$discount=$vdata['price'];
			}
			if($vdata['free_delivery_charge'] == 1)
			{
				$delviery=0;
			}
			else
			{
				$delviery=$old_delivery_charge;
			}
		}		 
		else{
			//if the discount in % or kr
			if($orderinfo['order_discount'] !=''){
				$vouchercode = $orderinfo['order_discount'];
				if(stripos($vouchercode, '%')){
					$percentage = str_replace("%","",$vouchercode);
					$discount =  $delsum * ($percentage/100);
				}
				else{
					
					$discount = str_replace("kr ","",$vouchercode);
					$vouchercode = "kr ".formatcurrency($discount);
				}
				
			}

		}
		if($discount > 0)

				$summary.='<div class="row">
                   <div class="pull-left col-md-6 no-padd">
                   <p class="grey-text"><span class="black=text">Discount ( '.$vouchercode.' )</span></p>
                   </div>                
                   <div class="pull-left col-md-6  text-right">
                     <p>kr '.formatcurrency($discount).'</p>
                   </div>
                   <div class="clearfix"></div>
                  <hr>
                 </div>';
		}
				 
				
		$fprice=$cus_sub_total-$discount;
		
		if($orderinfo['type'] != 'shop')
		{
			if($fprice < $min_price)
			{
				$summary.='<div class="row">
					   <div class="pull-left col-md-6 no-padd">
					   <p class="grey-text">Minste beløp</p>
					   </div>                
					   <div class="pull-left col-md-6  text-right">
						 <p>kr '.formatcurrency($min_price).'</p>
					   </div>
					   <div class="clearfix"></div>
					  <hr>
					 </div>';
				
			}
		}

		if($orderinfo['type'] != 'shop')
		{
			$summary.='<div class="row">
		<div class="pull-left col-md-6 no-padd">
			<p class="grey-text">Levering</p>
                   </div>                
                   <div class="pull-left col-md-6   text-right">';
				   
				    if($delviery == 0)
					{
						 $summary.='<p>kr 0,00</p>';
					}
					else
					{
						
						 $summary.='<p>kr '.formatcurrency($delviery).'</p>';
					}
					
				    
                  $summary.='</div>
                   <div class="clearfix"></div>
                  <hr>
                 </div>';
		}
		

		if(trim($orderinfo['changed_amount']) != '')
		{
			$orderinfo['total_amount']=$orderinfo['changed_amount'];
		}

			 
			  $summary.='<div class="row totalt">
			   <div class="pull-left col-md-6 no-padd">
			   <p>TOTALT</p>
			   </div>                
			   <div class="pull-left col-md-6   text-right">
				 <p>kr '.formatcurrency($orderinfo['total_amount']).'</p>
			   </div>
			   <div class="clearfix"></div>
			  <hr>
			 </div> 
			  
		  </div>
		</div>  
		 ';
			 
			 $this->data['lists']['summary']=$summary;
				
			  
	}
			
				   
				   $notes='';
				   if($orderinfo['delivery_note']!='')
				   {
						$notes.=' <p><span>Delivery Notes: </span>'.$orderinfo['delivery_note'].'</p>';
				   }
				   
				    if($orderinfo['special_instruction']!='')
				   {
						$notes.=' <p><span>Spesialinstruksjoner: </span>'.$orderinfo['special_instruction'].'</p>';
				   }
				
				if($notes != '')
				{
						 $str .= '<div class="notes mt-sm">
                   <div class="pull-left col-md-12 no-padd">';
				   $str.=$notes;
				    $str.='</div>
                 <div class="clearfix"></div>
                 <hr>
               </div>';
				}
				
				
		}
		else{
	
			$str.='<div class="orderlist mt-sm">
					<div class="pull-left col-md-7 no-padd">
				   '.$lang['lang_no_results_found'].'
				   </div>
				   </div>';
				   $this->data['visible']['bekreft_status']=0;
		}
		$this->data['lists']['orderlines']=$str;
	//	echo '<pre>';print_r($this->data['visible']);exit;
	 // }

	//	echo '<pre>';print_r($str);exit;
		
		
		//echo json_encode($data);exit;
		
        //log debug data
		//$this->__ajaxdebugging();
        //load the view for json echo
		
        //$this->__flmView('common/json');
		
		//$this->data['lists']['orderlist'] = $str;		
		
    }
	
	
	/*get orderline details*/	
	function __getorderlinedetails()
	{
	
	
		$barcode = $this->input->post('barcode'); // barcode of a bag
		
		
		$type='laundry';
		$baginfo=$this->orders_model->getBagBarcodeinfo($barcode,$type);
		if($baginfo)
		{
			$newarray=array('status'=>'bag','message'=>'Invalid Barcode');
			echo json_encode($newarray);exit;
		}
		$type='client';
		$baginfo=$this->orders_model->getBagBarcodeinfo($barcode,$type);
		if($baginfo)
		{
			$newarray=array('status'=>'bag','message'=>'Invalid Barcode');
			echo json_encode($newarray);exit;
		}
		
		
		
		
			/*$newdata = array('barcode'  => $_POST['barcode']);
			$this->session->unset_userdata('barcode');
			$this->session->set_userdata($newdata);*/
		$orderline = $this->input->post('orderline');//orderline
		
		
		$qty = $this->input->post('qty');//orderline
		
		$lstatus = $this->input->post('lstatus');//orderline last status
		$producttype = $this->input->post('producttype');//orderline last status
		$product = $this->input->post('product');
		$barstatus=$this->process_order_model->heatsealbarcodestatus($barcode,$orderline);
		
		$customerstatus=$this->process_order_model->heatsealCustomerstatus($barcode,$orderline);
		
		if($customerstatus)
		{
			$newarray=array('status'=>'error','message'=>'Heat seal belongs to '.$customerstatus['name'].' ('.$customerstatus['customer'].'). Please contact administrator.');
			echo json_encode($newarray);exit;
		}
		
		if($barstatus)
		{
			$newarray=array('status'=>'error','message'=>'This heatseal was already in process with order #'.$barstatus['order'].'.');
			echo json_encode($newarray);exit;
		}
		if(intval($barcode) == 0)
		{
			$newarray=array('status'=>'error','message'=>'Invalid heatseal');
			echo json_encode($newarray);exit;
		}
		
		if(strlen($barcode) != 8)
		{
			$newarray=array('status'=>'error','message'=>'Invalid heatseal');
			echo json_encode($newarray);exit;
		}
		
		$barcode2char = substr($barcode, 0, 2);
		
		if($barcode2char != '10') 
		{
			$newarray=array('status'=>'error','message'=>'Invalid heatseal');
			echo json_encode($newarray);exit;
		}
		
		if(intval($orderline) == 0)
		{
			$newarray=array('status'=>'error','message'=>'Invalid Product');
			echo json_encode($newarray);exit;
		}
		$heat_seal=$this->process_order_model->heatsealinfo($barcode,$orderline,$producttype,$product);
		$this->process_order_model->updateheatseal_log($heat_seal,$orderline,'2');//started
		if($qty == 1 && intval($lstatus) > 0)
		{
			//$orderid=$this->orders_model->getOrderid($orderline);
			//if($orderid)
			//{	
				//$this->orders_model->updateorderlog($orderid,5);
				//	$this->process_order_model->craeteInfile($orderid);
				//	$this->process_order_model->updatebagorderlogstatus($orderid);
			//}
		}
		$newarray=array('status'=>'success','orderline'=>$orderline,'qty'=>$qty);
		echo json_encode($newarray);exit;
	}
	
	function __updateorderlog()
	{
		$orderid=$_POST['order'];
		if($orderid)
		{	
			$status=$this->orders_model->updateorderlog($orderid,5);
			if($status)
			{
				//$this->process_order_model->craeteInfile($orderid);
				$this->process_order_model->updatebagorderlogstatus($orderid);
				$newarray=array('status'=>'success','message'=>$this->data['lang']['lang_order_scanned']);
				echo json_encode($newarray);exit;
			}
			else
			{
				$newarray=array('status'=>'error','message'=>$this->data['lang']['lang_already_scanned']);
				echo json_encode($newarray);exit;
			}
			
			}
		else
		{
			$newarray=array('status'=>'error','message'=>$this->data['lang']['lang_invalid_order']);
			echo json_encode($newarray);exit;
		}
		
	}
	
	function __printtagOrder()
	{	
		//profiling
		$this->data['controller_profiling'][] = __function__;
		
		$order_id = $this->input->post('orderid');
		

        $customer = $this->orders_model->getCustomerDetails($order_id);
        
        $orderinfo = $this->orders_model->getOrderinfo($order_id);
		
		$order_status = $orderinfo['order_status'];
		
		
		if($order_status == 3){
			$this->data['visible']['wi_show_process_button'] = 1;
		}
        
        //get shop detail where order placed ( from SL or branch)
        $order_from = $orderinfo['type'];
        
        if($order_from == 'shop'){
            
            $partnerinfo = $this->general_model->getPartnerDetails($orderinfo['partner'],$orderinfo['partner_branch']);
            
            $company = $partnerinfo['name'];
            $address = $partnerinfo['street'] .", ". $partnerinfo['zip']. ' '.$partnerinfo['city'];
            $phone = $partnerinfo['phone']; 
            

        }
        else{
            $company = $this->data['settings_company']['company_name'];
            $address = $this->data['settings_company']['company_address_street'] .", ". $this->data['settings_company']['company_address_zip']. ' '.$this->data['settings_company']['company_address_city'];
            $phone = $this->data['settings_company']['company_telephone']; 
        }
		
		
		 $company_info ='';
		
		
		$orderdetails = $this->orders_model->getOrderLine($order_id);
		$collectiontinfo=$this->orders_model->getCollectionDeliverytime('collection',$orderinfo['collection_time']);
		$deliveryinfo=$this->orders_model->getCollectionDeliverytime('delivery',$orderinfo['delivery_time']);
		
		
			$address=$customer['street_line_1'];
			if($customer['street_line_2'] != '')
			{
				$address.=', '.$customer['street_line_2'];
			}
			
			
		   $customer_detail='<div class="customer-detail row">
			   <div class="pull-left col-md-3 no-padd">
			   <p><span>Ordrenr: </span>#'.sprintf('%08d', $order_id).'</p>
			   <p><span>Telefon: </span>'.$customer['number'].'<br>
			   </div>
			    <div class="pull-left col-md-5 no-padd">
				<span>Navn: </span>'.$customer['customer_name'].'</br>
				<span>Adresse: </span>'.$address.', '.$customer['zip'].' '.$customer['city'].'';
				
				
			/*	if($customer['floor'] != '')
				{
			   $customer_detail.='<span>Etg: </span>'.$customer['floor'].' <br>';
			   }
			   if($customer['calling_bell'] != '')
				{
			   $customer_detail.='<span>RK: </span>'.$customer['calling_bell'].' <br>';
			   }*/
				
					$customer_detail.='</div><div class="pull-left col-md-3 no-padd">';
				if($collectiontinfo['sdate'] != '')
				{
					$customer_detail.='<span>Henting: </span>'.$collectiontinfo['sdate'].' &nbsp; '.$collectiontinfo['stime'].'-'.$collectiontinfo['etime'].'<br>';
				}
				else{
					
					$customer_detail.='<span>Innlevering: </span>'.date('d.m.Y H:i',strtotime($orderinfo['order_time'])).'<br>';

				}
				if(($order_from != 'shop')){
					
					$customer_detail.='<span>Levering: </span>'.$deliveryinfo['sdate'].'  &nbsp; '.$deliveryinfo['stime'].'-'.$deliveryinfo['etime'].'</p>';
				}
				   
				   $str='</div>     

                  
                   <div class="clearfix"></div>
                 </div>
                   <hr>

<div  class="orderlist row">


<div class="col-md-1"><input type="checkbox" onclick="cancelcheckall(this)" id="checkall">
</div>';



if(($order_from == 'shop')){

$str .='
<div class="pull-left col-md-1">
<p><b>Print</b></p>
</div>

<div class="pull-left col-md-3">
<p><b>Artikler</b></p>
</div>

<div class="pull-left col-md-1">
<p><b>Utlevering</b></p>
</div>';

}
else{
	
$str .='
<div class="pull-left col-md-1">
<p><b>Print</b></p>
</div>
<div class="pull-left col-md-3">
<p><b>Artikler</b></p>
</div>';
}


 $str .='<div class="pull-left col-md-2 no-padd text-center">
<p><b>Pris</b></p>
</div>
<div class="pull-left col-md-1 no-padd text-center">
<p><b>Antall</b></p>
</div>
<div class="pull-left col-md-1 no-padd text-center">
<p><b>Anullering</b></p>
</div>
<div class="pull-left col-md-2 text-right">
<p><b>Totalt</b></p>
</div>
</div>
 <hr><div class="popupscroll">
				 ';   
        
			//get results and save for tbs block merging
			$data = $this->orders_model->getOrderLine($order_id);
			
			for($w=0;$w<count($data);$w++){
				$odlines[] = $data[$w]['id'];
			}
			//print_r($odlines);
			//get cancel orderline of an array
			$canceled_arr = $this->settings_order_model->getCancelOrderLine($order_id,$odlines);
			//print_r($canceled_arr);
			
			$canceled_arr_approved = $this->settings_order_model->getCancelOrderLine($order_id,$odlines,'approved');
			//print_r($canceled_arr_approved);


			//$str='';
			if(!empty($data)){
				$delsum = 0;
				$lstatus=1;
				$orderlineproduct=array();
				$orderlinedelivery=array();
				for($j=0;$j<count($data);$j++){
					
				$arr = $this->orders_model->getProductDisplayName($data[$j]['product']);
				
				$data[$j]['name'] = $arr['name'];
				
				
				$orderlineproduct[$data[$j]['product']]=$data[$j]['product'];
				
				if($data[$j]['p_b_delivery_time'] != '' || $data[$j]['p_b_delivery_time'] != '0000-00-00')
				{
					$p_b_delivery_time = date('d.m.Y',strtotime($data[$j]['p_b_delivery_time']));
					$orderlinedelivery[]= '<span class="pname"> '. $data[$j]['name'].'</span>&nbsp;&nbsp; '.$p_b_delivery_time;
				}
			
				
				//echo '<pre>';print_r($data[$j]);exit;
			//	echo $data[$j]['id'];exit;
					
					
					
					$sqty = $this->process_order_model->getHeatOrderLine($data[$j]['id']);
						$proqty=$data[$j]['qty'];
		
					if(trim($data[$j]['changed_quantity']) != '')
					{
							$data[$j]['quantity']=$data[$j]['changed_quantity'];
							$subtotal = $data[$j]['changed_amount'];
					}
					else{
						 $subtotal = $data[$j]['amount'];
					}
					
					if (round($data[$j]['quantity'], 0) == $data[$j]['quantity'])
					{
						// is whole number
						$data[$j]['quantity'] = round($data[$j]['quantity'], 0);
					}					
					
					
					 $discount = $this->products_model->getProDiscount($data[$j]['product']);
					 $discount=$discount[0];
					 $ddesc='&nbsp;';
					 if(isset($discount['description']))
					 {
						//$ddesc=' ('.$discount['description'].')';
					 }
					 
					 $quantity  = $data[$j]['quantity'];
					 $productPrice=$subtotal;
					 
					 $productPrice=round($productPrice);
					 
					 
					$subtotalarray[]= $productPrice;
				
					//$subtotal = $data[$j]['price'] * $data[$j]['quantity'];
					//$subtotalarray[]= $subtotal;
					$delsum += $data[$j]['amount'] * $data[$j]['quantity'];
					$path_parts = pathinfo($data[$j]['amount']);
					$img = $path_parts['filename'] .".".$path_parts['extension']; 
					//$total = ($delsum + $delivery_charge) - $discount ;
					if($data[$j]['quantity'] >= $sqty)
					{
						$newquantity=$data[$j]['quantity']-$sqty;
					}
					else
					{
						$newquantity=0;
					}
					
					$newquantity=$proqty*$newquantity;
					
					$class = ($data[$j]['product']=='1') ? "orderdisabled" : ''; 
					

					$pstatus = ($data[$j]['payment_status'] == 'paid') ? '' : 'order'.$data[$j]['payment_status'] ;
					$cstatus = $pending = '';
					$disable = ($data[$j]['product']=='1') ? "disabled" : ' onclick="printitem(this);" name="printorderlines[]"';
					if(in_array($data[$j]['id'], $canceled_arr)){
						$cstatus = 'orderpaid';
                        $disable = 'disabled checked name="cprintorderlines[]"';
						if(in_array($data[$j]['id'], $canceled_arr_approved)){
							$pending = 'Canceled';
						}
						else {
							$pending = 'Pending';
						}
						//$pending = ($orderinfo['order_status']=='11') ? 'Canceled':'Pending';
						
					}
					
					
					if(count($data) == $lstatus)
					{
						if(intval($newquantity) > 0)
						{
							$str.=' <div id="cdiv_'.$data[$j]['id'].'" class="orderlist '.$class. ' '.$pstatus.' '. $cstatus.'">';
						}
						else
						{
							$str.=' <div id="cdiv_'.$data[$j]['id'].'" class="orderlist '.$class. ' '.$pstatus.' '. $cstatus.'">';
						}
						
						$newlstatus=1;
					}
					else
					{
						if(intval($newquantity) > 0)
						{
							$str.='<div id="cdiv_'.$data[$j]['id'].'" class="orderlist '.$class. ' '.$pstatus.' '. $cstatus.'">';
						}
						else
						{
							$str.='<div id="cdiv_'.$data[$j]['id'].'" class="orderlist '.$class. ' '.$pstatus.' '. $cstatus.'">';
						}
						$newlstatus=0;
						
					}
					
				if($data[$j]['p_b_delivery_time'] != '' && $data[$j]['p_b_delivery_time'] != '0000-00-00')
				{
					$p_b_delivery_time = date('d.m.Y',strtotime($data[$j]['p_b_delivery_time']));
				}
				else{
					$p_b_delivery_time = '';
				}
					
					
					
					
					$str .= '
					<div class="pull-left col-md-1">';
					if($disable == 'disabled')
					{	
						$str .= '<input checked type="checkbox" '.$disable.' id="print_'.$data[$j]['id'].'" value="'.$data[$j]['id'].'" />';
					}
					else
					{
						$str .= '<input  type="checkbox" '.$disable.'  id="print_'.$data[$j]['id'].'" value="'.$data[$j]['id'].'" />';
					}
					
					
					
					$str .= '</div>
					<div class="pull-left col-md-1">
					   <p><input style="border:1px solid #e7e7e7;" '.$disable.' class="form-control" type="text" size="2" name="tagprint[]" value="1" id="tagprint_'.$data[$j]['id'].'" /></p>
					   </div>  
					';
					
					if(($order_from == 'shop')){
					
					   $str .= '<div class="pull-left col-md-3">
					   <p>'.$data[$j]['name'].'</p>
					   </div>  
					   <div class="pull-left col-md-1">
					   <p>'.$p_b_delivery_time.'</p>
					   </div> ';
					
					}
					else{
						
					   $str .= '<div class="pull-left col-md-4">
					   <p>'.$data[$j]['name'].'</p>
					   </div>';  
					}

				   
                  $str .= ' <div class="pull-left col-md-2 no-padd text-center">
					 <p>kr '.formatcurrency($data[$j]['price']).'</p>
                   </div>
					<div class="pull-left col-md-1 no-padd text-center"><p>
					
						 '.$data[$j]['quantity'].'</p></div><div class="pull-left col-md-1 no-padd text-center"><p>'.ucfirst($pending).'</p></div>'	;	
					
				$str .= '			   
                   <div class="pull-left col-md-2 text-right">
				   <p class="tprice" id="subtotal_'.$data[$j]['product'].'"> kr '.formatcurrency($subtotal).'</p>
                   </div>
                   <div class="clearfix"></div>
                   <hr>
                 </div>';
				
				 
				$forderlineid=$data[$j]['id'];
				 
                    $lstatus++;
				}
				
		
		$subtotal=array_sum($subtotalarray);
	
		$cus_sub_total=$subtotal;
		$customerid = $customer['customerid'];
		$zone = $customer['zone'];
		$delivery_type = 'normal'; //default
		$this->data['reg_fields'][] = 'delivery';
		$this->data['fields']['delivery'] =$this->general_model->checkMinimumAmount($delivery_type,$customerid,$subtotal,$zone);
		$min_price=$this->data['fields']['delivery']['min_price'];
		$min_price_txt = '';
		
		if($orderinfo['type'] != 'shop')
		{
			if($cus_sub_total < $min_price)
			{
				$min_price_txt =  ' (Minste beløp kr '.formatcurrency($min_price).')';
			}
			$subtotal = ($subtotal < $min_price) ?  $min_price : $subtotal;
		}
		 $delsum=$subtotal;
		 
		
		 
		$this->data['debug'][] = $this->general_model->debug_data;	
		$delviery = ($subtotal >= $this->data['fields']['delivery']['free_delivery_after']) ?  '0' : $this->data['fields']['delivery']['delivery_charge'];
		$old_delivery_charge=$this->data['fields']['delivery']['delivery_charge'];
		
		
		$newproducts= $this->products_model->getPartnerProductList('','main','',$orderinfo['type']);
		
		//echo '<pre>';print_r($newproducts);exit;
		
		$summary=' </div>';
  				
		
		
		$fprice=$cus_sub_total-$discount;
		
		

					
					if(trim($orderinfo['changed_amount']) != '')
					{
						$orderinfo['total_amount']=$orderinfo['changed_amount'];
					}
					
                 
	    }
				 
				  
				
				
				
				
		$delivery_type = 'normal';
		$deliveryinfo =$this->general_model->checkMinimumAmount($delivery_type,'','',$customer['zone']);
		$deliveryinfo= json_encode($deliveryinfo);
		$deliveryinfo=htmlentities($deliveryinfo);
		
	//	echo '<pre>';print_r($orderlinedelivery);exit;
			if(count($orderlinedelivery) > 0)
			{
				//$orderlinedelivery_time=implode('<br>',$orderlinedelivery);
				//$orderlinedelivery_time = '<span>Levering: </span><br>' . $orderlinedelivery_time;
				
			//$customer_detail.=$orderlinedelivery_time.'</p>';
			}
			else
			{	
			
				$customer_detail.= $orderlevering;
			}
			
		
		
		
		
		
				
		$order_details = $company_info . $customer_detail .$str . $summary ;
		
		$order_details.=' 
		
		<input type="hidden" id="zoneinfo" name="zoneinfo"  value="'.$deliveryinfo.'" />	
		                  <div class="clearfix" style="margin-bottom:30px"><input type="hidden" id="eorder_id" name="eorder_id" value="'.$order_id.'" /></div> 
';
		
		
		$order_details.='<div class="row" style="margin-bottom:30px">
                     <div class="col-md-3 pull-left">
                      
                     </div>
                     <div class="col-md-2 pull-right no-padd">
                      
                       <button type="button"  onclick="tagprint(\''.$order_id.'\');"  class="btn-lg green npayment_type">Print</button>
                      </div> <div class="clearfix"></div>              
                </div>';
				
				
		
		$result = array("order_details"=>$order_details);
		echo json_encode($result);exit;
	

  
	}
	
	function __editorder()
	{
		

		//profiling
		$this->data['controller_profiling'][] = __function__;
		
		$order_id = $this->input->post('orderid');
		
		//$order_id = '01060388';

        $customer = $this->orders_model->getCustomerDetails($order_id);
        
        $orderinfo = $this->orders_model->getOrderinfo($order_id);
		
		$order_status = $orderinfo['order_status'];
		
		
		if($order_status == 3){
			$this->data['visible']['wi_show_process_button'] = 1;
		}
        
        //get shop detail where order placed ( from SL or branch)
        $order_from = $orderinfo['type'];
        
        if($order_from == 'shop'){
            
            $partnerinfo = $this->general_model->getPartnerDetails($orderinfo['partner'],$orderinfo['partner_branch']);
            
            $company = $partnerinfo['name'];
            $address = $partnerinfo['street'] .", ". $partnerinfo['zip']. ' '.$partnerinfo['city'];
            $phone = $partnerinfo['phone']; 
            

        }
        else{
            $company = $this->data['settings_company']['company_name'];
            $address = $this->data['settings_company']['company_address_street'] .", ". $this->data['settings_company']['company_address_zip']. ' '.$this->data['settings_company']['company_address_city'];
            $phone = $this->data['settings_company']['company_telephone']; 
        }
		
		
		 $company_info ='';
		
		
		$orderdetails = $this->orders_model->getOrderLine($order_id);
		$collectiontinfo=$this->orders_model->getCollectionDeliverytime('collection',$orderinfo['collection_time']);
		$deliveryinfo=$this->orders_model->getCollectionDeliverytime('delivery',$orderinfo['delivery_time']);
		
		
			$address=$customer['street_line_1'];
			if($customer['street_line_2'] != '')
			{
				$address.=', '.$customer['street_line_2'];
			}
			
			
		   $customer_detail='<div class="customer-detail row">
			   <div class="pull-left col-md-3 no-padd">
			   <p><span>Ordrenr: </span>#'.sprintf('%08d', $order_id).'</p>
			   <p><span>Telefon: </span>'.$customer['number'].'<br>
			   </div>
			    <div class="pull-left col-md-5 no-padd">
				<span>Navn: </span>'.$customer['customer_name'].'</br>
				<span>Adresse: </span>'.$address.', '.$customer['zip'].' '.$customer['city'].'';
				
				
			/*	if($customer['floor'] != '')
				{
			   $customer_detail.='<span>Etg: </span>'.$customer['floor'].' <br>';
			   }
			   if($customer['calling_bell'] != '')
				{
			   $customer_detail.='<span>RK: </span>'.$customer['calling_bell'].' <br>';
			   }*/
				
					$customer_detail.='</div><div class="pull-left col-md-3 no-padd">';
				if($collectiontinfo['sdate'] != '')
				{
					$customer_detail.='<span>Henting: </span>'.$collectiontinfo['sdate'].' &nbsp; '.$collectiontinfo['stime'].'-'.$collectiontinfo['etime'].'<br>';
				}
				else{
					
					$customer_detail.='<span>Innlevering: </span>'.date('d.m.Y H:i',strtotime($orderinfo['order_time'])).'<br>';

				}
				if(($order_from != 'shop')){
					
					$customer_detail.='<span>Levering: </span>'.$deliveryinfo['sdate'].'  &nbsp; '.$deliveryinfo['stime'].'-'.$deliveryinfo['etime'].'</p>';
				}
				   
				   $str='</div>     
			   
                  
                   <div class="clearfix"></div>
                 </div>
                   <hr>

<div  class="orderlist row">

<div class="col-md-1"><input type="checkbox" onClick="paycheckall(this)" id="checkall" style="margin-left:27px" /></div>';



if(($order_from == 'shop')){

$str .='<div class="pull-left col-md-3">
<p><b>Artikler</b></p>
</div>

<div class="pull-left col-md-1 no-padd">
<p><b>Utlevering</b></p>
</div>';

}
else{
	
$str .='<div class="pull-left col-md-4">
<p><b>Artikler</b></p>
</div>';
}


 $str .='<div class="pull-left col-md-2 no-padd text-center">
<p><b>Pris</b></p>
</div>
<div class="pull-left col-md-1 no-padd text-center">
<p><b>Antall</b></p>
</div>
<div class="pull-left col-md-1 no-padd text-center">
<p><b>Status</b></p>
</div>
<div class="pull-left col-md-1 no-padd text-center">
<p><b>Anullering</b></p>
</div>
<div class="pull-left col-md-2 text-right">
<p><b>Totalt</b></p>
</div>
</div>
 <hr><div class="popupscroll">
				 ';   
        
			//get results and save for tbs block merging
			$data = $this->orders_model->getOrderLine($order_id);
			
			for($w=0;$w<count($data);$w++){
				$odlines[] = $data[$w]['id'];
			}
			//print_r($odlines);
			
			//get cancel orderline of an array
			$canceled_arr = $this->settings_order_model->getCancelOrderLine($order_id,$odlines);
			//print_r($canceled_arr);
			$canceled_arr_approved = $this->settings_order_model->getCancelOrderLine($order_id,$odlines,'approved');
			//print_r($canceled_arr_approved);
			
			
			//$str='';
			if(!empty($data)){
				$delsum = 0;
				$lstatus=1;
				$orderlineproduct=array();
				$orderlinedelivery=array();
				
				$paidamountarray=array();
				$paidstatus=1;
				$discountstatus=1;
				$pendingstatus=0;
				for($j=0;$j<count($data);$j++){
					
				$arr = $this->orders_model->getProductDisplayName($data[$j]['product']);
				
				$data[$j]['name'] = $arr['name'];
				
				
				$orderlineproduct[$data[$j]['product']]=$data[$j]['product'];
				
				if($data[$j]['p_b_delivery_time'] != '' || $data[$j]['p_b_delivery_time'] != '0000-00-00')
				{
					$p_b_delivery_time = date('d.m.Y',strtotime($data[$j]['p_b_delivery_time']));
					$orderlinedelivery[]= '<span class="pname"> '. $data[$j]['name'].'</span>&nbsp;&nbsp; '.$p_b_delivery_time;
				}
			
				$resstatus=$this->settings_order_model->getCancelstatus($order_id,$data[$j]['id'],true);
				
				//echo '<pre>';print_r($data[$j]);exit;
			//	echo $data[$j]['id'];exit;
					
					
					
					$sqty = $this->process_order_model->getHeatOrderLine($data[$j]['id']);
						$proqty=$data[$j]['qty'];
		
					if(trim($data[$j]['changed_quantity']) != '')
					{
							$data[$j]['quantity']=$data[$j]['changed_quantity'];
							$subtotal = $data[$j]['changed_amount'];
					}
					else{
						 $subtotal = $data[$j]['amount'];
					}
					
					if (round($data[$j]['quantity'], 0) == $data[$j]['quantity'])
					{
						// is whole number
						$data[$j]['quantity'] = round($data[$j]['quantity'], 0);
					}					
					
					
					 $discount = $this->products_model->getProDiscount($data[$j]['product']);
					 $discount=$discount[0];
					 $ddesc='&nbsp;';
					 if(isset($discount['description']))
					 {
						//$ddesc=' ('.$discount['description'].')';
					 }
					 
					 $quantity  = $data[$j]['quantity'];
					 $productPrice=$subtotal;
					 
					 $productPrice=round($productPrice);
					 
					 
					$subtotalarray[]= $productPrice;
				
					//$subtotal = $data[$j]['price'] * $data[$j]['quantity'];
					//$subtotalarray[]= $subtotal;
					$delsum += $data[$j]['amount'] * $data[$j]['quantity'];
					$path_parts = pathinfo($data[$j]['amount']);
					$img = $path_parts['filename'] .".".$path_parts['extension']; 
					//$total = ($delsum + $delivery_charge) - $discount ;
					if($data[$j]['quantity'] >= $sqty)
					{
						$newquantity=$data[$j]['quantity']-$sqty;
					}
					else
					{
						$newquantity=0;
					}
					
					$newquantity=$proqty*$newquantity;
					
					
					$cstatus = $pending = '';
					$disable = ' onclick="payitem(this);" name="corderlines[]"';
					if(in_array($data[$j]['id'], $canceled_arr)){
						$cstatus = 'orderdisabled';
						$disable = 'disabled checked name="corderlines"';
						if(in_array($data[$j]['id'], $canceled_arr_approved)){
							$pending = 'Canceled';
						}
						else {
							$pending = 'Pending';
						}
						//$pending = ($orderinfo['order_status']=='11') ? 'Canceled':'Pending';
					}
					
					
					if(count($data) == $lstatus)
					{
						if(intval($newquantity) > 0)
						{
							$str.=' <div id="trr_'.$data[$j]['id'].'" class="orderlist order'.$data[$j]['payment_status'].' '. $cstatus.'">';
						}
						else
						{
							$str.=' <div id="trr_'.$data[$j]['id'].'" class="orderlist order'.$data[$j]['payment_status'].' '. $cstatus.'">';
						}
						
						$newlstatus=1;
					}
					else
					{
						if(intval($newquantity) > 0)
						{
							$str.='<div id="trr_'.$data[$j]['id'].'" class="orderlist order'.$data[$j]['payment_status'].' '. $cstatus.'">';
						}
						else
						{
							$str.='<div id="trr_'.$data[$j]['id'].'" class="orderlist order'.$data[$j]['payment_status'].' '. $cstatus.'">';
						}
						$newlstatus=0;
						
					}
					
				if($data[$j]['p_b_delivery_time'] != '' && $data[$j]['p_b_delivery_time'] != '0000-00-00')
				{
					$p_b_delivery_time = date('d.m.Y',strtotime($data[$j]['p_b_delivery_time']));
				}
				else{
					$p_b_delivery_time = '';
				}
					
					
					if($pending!=''){
						
						$str .= '<div class="pull-left col-md-1" style="padding-left:42px">';
					}
					else{
						
					$str .= '<div class="pull-left col-md-1">
					
					
					<a class="removeOrderline" onclick="updateorderlines(\''.$data[$j]['id'].'\',\''.$order_id.'\');"  href="#removeOrderlineModal" data-toggle="modal" data-title="Remove" data-stuff="'.$data[$j]['id'].'@'.$order_id.'"><span class="x-icon"><i class="icon-remove red-text"></i></span></span></a> &nbsp;
					
					';
					}
					
					//$str.='<input type="checkbox" name="printorderlines[]" id="print_'.$data[$j]['id'].'" value="'.$data[$j]['id'].'" />';
					
					if($data[$j]['payment_status'] == 'pending')
					{
						$deliverystatus = $this->payments_model->getDeliverystatus($order_id,$data[$j]['id']);
						
						if($deliverystatus)
						{
							$str.= '<input type="checkbox"  checked="checked"  disabled="disabled"  name="corderliness" id="corderlines_'.$data[$j]['id'].'"  value="" />';
					
							if(strtolower($data[$j]['payment_status']) == 'pending')
							{
								$waitamountarray[]=$productPrice;
								$paidstatus=0;
								$discountstatus=0;
							}
						}
						else
						{
							if($resstatus)
							{
								$str.= '<input type="checkbox"  checked="checked"  disabled="disabled"  name="corderliness" id="corderlines_'.$data[$j]['id'].'"  value="" />';
							}
							else
							{
								$paidstatus=0;
						$pendingstatus=1;
						$str.= '<input type="checkbox"   '.$disable.' id="corderlines_'.$data[$j]['id'].'"  value="'.$data[$j]['id'].'" />';
							}
						
						
						}
					}
					else
					{	
						$str.= '<input type="checkbox"  checked="checked"  disabled="disabled"  name="corderliness" id="corderlines_'.$data[$j]['id'].'"  value="" />';
					
						if(strtolower($data[$j]['payment_status']) == 'waiting')
						{
							$waitamountarray[]=$productPrice;
							$paidstatus=0;
							$discountstatus=0;
						}
						if(strtolower($data[$j]['payment_status']) == 'paid' || strtolower($data[$j]['payment_status']) == 'canceled')
						{
							$paidamountarray[]=$productPrice;
							$discountstatus=0;
						}
							
							
					}
					
					
					$str.='</div>';
					
					if(($order_from == 'shop')){
					
					   $str .= '<div class="pull-left col-md-3">
					   <p>'.$data[$j]['name'].'</p>
					   </div>  
					   <div class="pull-left col-md-1 no-padd">
					   <p>'.$p_b_delivery_time.'</p>
					   </div> ';
					
					}
					else{
						
					   $str .= '<div class="pull-left col-md-4">
					   <p>'.$data[$j]['name'].'</p>
					   </div>';  
					}

				   
                  $str .= ' <div class="pull-left col-md-2 no-padd text-center">
					 <p>kr '.formatcurrency($data[$j]['price']).'</p>
                   </div>
					<div class="pull-left col-md-1 no-padd text-center orderline">';
					
					
			
								if($resstatus)
								{
									$str .= '<a  id="edit_pad_'.$data[$j]['id'].'"  data-title="'.$data[$j]['name'].'"   data-stuff="'.$data[$j]['id'].'@'.$data[$j]['name'].'@'.$data[$j]['price'].'@'.$data[$j]['path'].'@'.$data[$j]['description'].'@'.$data[$j]['quantity'].'@'.$data[$j]['ptype'].'@'.$data[$j]['complain'].'@'.$data[$j]['in_house'].'@'.round($data[$j]['oprice']).'@'.$data[$j]['product'].'" style="vertical-align: middle; line-height:inherit"><p class="paymentstatus">';
									if($resstatus == 'approved')
									{
										$str .= '<span  class="red">C</span>';
									}
									else
									{
										$str .= '<span  class="orange">C</span>';
									}
									
									
									$str .= '</p></a>';	
									
									
								}
								else
								{	
								
									if(strtolower($data[$j]['payment_status']) == 'paid' || strtolower($data[$j]['payment_status']) == 'canceled')
									{
										
										$str .= $data[$j]['quantity'];
									}
									else{								
									 $str .= ' <a class="editprod" id="edit_pad_'.$data[$j]['id'].'" data-toggle="modal" data-title="'.$data[$j]['name'].'"  href="#editProductModal" data-stuff="'.$data[$j]['id'].'@'.$data[$j]['name'].'@'.$data[$j]['price'].'@'.$data[$j]['path'].'@'.$data[$j]['description'].'@'.$data[$j]['quantity'].'@'.$data[$j]['ptype'].'@'.$data[$j]['complain'].'@'.$data[$j]['in_house'].'@'.round($data[$j]['oprice']).'@'.$data[$j]['product'].'" style="vertical-align: middle; line-height:inherit">'.$data[$j]['quantity']. ' &nbsp;<img src="img/plus-minus.png" alt="Edit" title="" style="vertical-align: inherit"/></a>';	
									}
								}
								
					
						
					
					
					/*$str .= '<div class="quantity">';
					$arr1=array("min_quantity" => "1" ,"price" => $data[$j]['price']);
				 $ddata = $this->products_model->getProDiscount($data[$j]['product']);
				
				 $discount_status='0';
				 $discount='';

					if(count($ddata) > 0 ){

						$discount_status= '1';
						$discount= json_encode($ddata[0]);
						$discount=htmlentities($discount);
					}

					
					$str.='<script> min_qty_price['.$data[$j]['product'].'] =['.json_encode($arr1).'];</script>
					<input type="hidden" name="product['.$data[$j]['product'].'][id]" value="'.$data[$j]['product'].'" class="prodId"/>
					
					<input type="hidden" name="product['.$data[$j]['product'].'][orderline]" value="'.$data[$j]['id'].'" />
					
					
					
					
                   <input type="hidden" name="product['.$data[$j]['product'].'][name]" value="'.$data[$j]['name'].'"/>
                                    <input type="hidden" name="product['.$data[$j]['product'].'][price]" class="product_price" id="price_'.$data[$j]['product'].'" value="'.$data[$j]['price'].'"/>
                                    <input type="hidden" name="product['.$data[$j]['product'].'][new_price]" class="new_price_'.$data[$j]['product'].'" value="'.$data[$j]['price'].'"/>
                                    <input type="hidden" name="product['.$data[$j]['product'].'][subtotal]" value="'.$subtotal.'" class="subtotal_'.$data[$j]['product'].'"/>
                                    <input type="hidden" name="product['.$data[$j]['product'].'][percent]" value="" class="product_percent"/>
                                    <input type="hidden" name="product['.$data[$j]['product'].'][offer_price]" class="product_offer_price" value=""/>
									<input type="hidden" id="discount_status_'.$data[$j]['product'].'" name="product['.$data[$j]['product'].'][discount_status]"  value="'.$discount_status.'"/>
									<input type="hidden" id="discount_'.$data[$j]['product'].'" name="product['.$data[$j]['product'].'][discount]"  value="'.$discount.'" /><input type="hidden" id="oqty_'.$data[$j]['product'].'" name="product['.$data[$j]['product'].'][oqty]"  value="'.$data[$j]['quantity'].'" />';
					 $str.= '<input type="button" class="minus" value="-">
                     <input type="text"  id="qty_'.$data[$j]['id'].'" class="input-text qty text" readonly title="Qty" value="'.$data[$j]['quantity'].'" name="product['.$data[$j]['product'].'][qty]" min="1" step="1">
                     <input type="button" class="plus" value="+">
					</div>';*/
					
					
				$str .= '</div>	
                   <div class="pull-left col-md-1 text-center">
				   <p >'.ucfirst($data[$j]['payment_status']).'</p>
				   </div>
					<div class="pull-left col-md-1 no-padd text-center">
						   <p>'.ucfirst($pending).'</p>
                   </div>
				   
                   <div class="pull-left col-md-2 text-right">
				   <p class="tprice" id="subtotal_'.$data[$j]['product'].'"> kr '.formatcurrency($subtotal).'</p>
				   
				    <input type="hidden" value="'.$productPrice.'" name="ordertotal['.$data[$j]['id'].']" id="oltotal_'.$data[$j]['id'].'" />
	   
	     <input type="hidden" value="'.$data[$j]['payment_status'].'" name="orderpayment['.$data[$j]['id'].']" />
				   
                   </div>
                   <div class="clearfix"></div>
                   <hr>
                 </div>';
				
				 
				$forderlineid=$data[$j]['id'];
				 
                    $lstatus++;
				}
				
		
		$subtotal=array_sum($subtotalarray);
	
		$cus_sub_total=$subtotal;
		$customerid = $customer['customerid'];
		
		$data = $this->payments_model->getAccountBalance($customerid);
		$pendingsaldo = $data['pending'];
		$paidsaldo = $data['paid'];
		if(intval($pendingsaldo) > 0)
		{	
			$amount= formatcurrency($paidsaldo).' ('.formatcurrency($pendingsaldo).')';
			$saldo = $paidsaldo + $pendingsaldo;
		}
		else
		{
			$amount= formatcurrency($paidsaldo);
			$saldo = $paidsaldo;
		}
		
		$saldostatus=$this->payments_model->getSaldostatus($customerid);
		
		
		
		$zone = $customer['zone'];
		$delivery_type = 'normal'; //default
		$this->data['reg_fields'][] = 'delivery';
		$this->data['fields']['delivery'] =$this->general_model->checkMinimumAmount($delivery_type,$customerid,$subtotal,$zone);
		$min_price=$this->data['fields']['delivery']['min_price'];
		$min_price_txt = '';
		
		if($orderinfo['type'] != 'shop')
		{
			if($cus_sub_total < $min_price)
			{
				$min_price_txt =  ' (Minste beløp kr '.formatcurrency($min_price).')';
			}
			$subtotal = ($subtotal < $min_price) ?  $min_price : $subtotal;
		}
		 $delsum=$subtotal;
		 
		
		 
		$this->data['debug'][] = $this->general_model->debug_data;	
		$delviery = ($subtotal >= $this->data['fields']['delivery']['free_delivery_after']) ?  '0' : $this->data['fields']['delivery']['delivery_charge'];
		$old_delivery_charge=$this->data['fields']['delivery']['delivery_charge'];
		
		
		$newproducts= $this->products_model->getPartnerProductList('','main','',$orderinfo['type']);
		
		//echo '<pre>';print_r($newproducts);exit;
		
		$summary=' </div>';
		
		
			
		$summary .='   <div class="handlekurv">   
               <div class="col-md-6 no-padd">';
			   
$summary.='<div class="row mt-sm">';

		if($orderinfo['order_status'] !='11' && $orderinfo['order_status'] !='9'){

$summary.='<div class="pull-left col-md-5 no-padd">';                   
if(count($newproducts) > 0)
{
	$option='<option value="0">-- Select Product --</option>';
	$temp = '';
	$prd = array();
	foreach($newproducts as $proitems)
	{
		if(!isset($orderlineproduct[$proitems['id']]))
		{
			$temp = $proitems['name'];
			if (!in_array($temp, $prd)){
				$prd[] = $proitems['name'];
				$option.='<option value="'.$proitems['id'].'">'.$proitems['name'].'</option>';
			}
			
		}
		
	}
}

				   
$summary.='<select  class="" name="nproduct" id="nproduct">';
$summary.=$option;
$summary.='</select>';


                  $summary.='  </div>
				    <div class="pull-left col-md-5">
                  <button type="button" id="add_productbtn" onclick="addProduct();" style="font-size:15px !important;width:70%;padding:8px 0" class="btn-lg btn-primary">'.$this->data['lang']['lang_add_product'].'</button>
				  
				  
				  <button type="button" class="minusplus" data="'.$forderlineid.'" style="display:none;"  class="btn-lg btn-primary">Refersh</button>
				  
                   </div> 
       <div class="clearfix"></div>';
		}
                 
         $summary.=' </div>';
			   
		
			   
			$summary.='<input type="hidden" id="pay_amt" name="pay_amt" value="0" />
			<input type="hidden" id="paying_amt" name="paying_amt" value="0" />
			<input type="hidden" id="saldo_amt" name="saldo_amt" value="'.$saldo.'" />
			<input type="hidden" value="'.$discountstatus.'" id="discountstatus" name="discountstatus" />
			
			 <div class="row col-md-10 mt-sm paid-summary">';
			 
			  
				   if($saldostatus)
				   {
						 $summary.='
						   <div class="pull-left col-md-6 no-padd">
							<p class="grey-text">Saldo</p>
						  </div>
						  <div class="pull-left col-md-6  no-padd text-right">
							<p>kr '.'<b id="csaldo_amt">'.$amount.'</b></p>
						  </div>
						  <div class="clearfix"></div>
						  <hr>

						 <div class="pull-left col-md-12 no-padd" style="display:none">
						 <p>Saldo kr <b id="csaldo_amt">'.$amount.'</b></p></div>';    
						 
						 $summary.='<div style="display:none; class="pull-left col-md-12 no-padd">
						 <p>Your payable amount kr <b id="payable_amt">0,00</b></p>
						 </div>';
						 
						$summary.='<div style="display:none; class="pull-left col-md-12 mt-sm no-padd">
						<div class="col-md-3">
						<input type="hidden" id="saldostatus" name="saldostatus" value="1"  />
						</div><div class="col-md-7"></div></div>'; 
				   }
				   else
				   {
						$summary.='<div style="display:none; class="pull-left col-md-12 mt-sm no-padd">
						<div class="col-md-3">
						<input type="hidden" id="saldostatus" name="saldostatus" value="0"  />
						</div><div class="col-md-7"></div></div>'; 
				   }

	
	 $totapaidamount=array_sum($paidamountarray);
			  
			  $totalwaitamount=array_sum($waitamountarray);
			  
			  
					if(trim($orderinfo['changed_amount']) != '')
					{
						// if(intval($orderinfo['changed_amount']) > 0)
						 // {
							$orderinfo['total_amount']=$orderinfo['changed_amount'];
						  //}
					}
					
	//if discount is a voucher
			$discount=0;
			$free_delivery_charge=0;
			if(intval($orderinfo['voucher']) > 0){
		
				$qry="SELECT *,ROUND(price, 0) as price FROM a_voucher WHERE id='".$orderinfo['voucher']."'";
				$query = $this->db->query($qry);
				$vdata = $query->row_array();//multi row array
				
				$vouchercode=$vdata['voucher'];
				
				if($vdata['percentage'] != '')
				{
					$percentage=$vdata['percentage']/100;
					$discount=$subtotal*$percentage;
					
					
				}
				else
				{
					$discount=$vdata['price'];
				}
				
				if(($vdata['free_delivery_charge'] == 1)  && 	($orderinfo['type'] != 'shop'))
				{
					$delviery=0;
					 $free_delivery_charge=1;
				}
				else
				{
					$delviery=$old_delivery_charge;
					
				}
			}
			else{
				//if the discount in % or kr
				if($orderinfo['order_discount'] !=''){
					$vouchercode = $orderinfo['order_discount'];
					if(stripos($vouchercode, '%')){
						$percentage = str_replace("%","",$vouchercode);
						$discount =  $delsum * ($percentage/100);
					}
					else{
						
						$discount = str_replace("kr ","",$vouchercode);
						$vouchercode = "kr ".formatcurrency($discount);
					}
					
				}

			}
			
	
				  
			$discountapplystatus=0;
		
			if($pendingstatus == 1)
			{
				  if(intval($totapaidamount) > 0)
				  {
					if($discountstatus == 0)
					{
						if($totapaidamount >= $discount)
						{
							$discountapplystatus=1;
							$totapaidamount=$totapaidamount-$discount;
						}
						else
						{
							if($discount >= $totapaidamount)
							{
								$discountapplystatus=1;
								$discount=$discount-$totapaidamount;
								$totapaidamount=0;
							}
						}
						
						
						
					}
					
				  }
				  
				  if(intval($totalwaitamount) > 0)
				  {
					if($discountstatus == 0 && $discount > 0)
					{
						if($totalwaitamount >= $discount)
						{
							$discountapplystatus=1;
							$totalwaitamount=$totalwaitamount-$discount;
						}
						else
						{
							if($discount >= $totalwaitamount)
							{
								$discountapplystatus=1;
								$discount=$discount-$totalwaitamount;
								$totalwaitamount=0;
							}
						}
					}
					
				  }
					
					$balanceamount= $orderinfo['total_amount']-$totapaidamount;
					$balanceamount= $balanceamount-$totalwaitamount;
			  
			
			}
			else
			{
				
				if($discountstatus == 0 && $discount > 0)
				{
				
					  if(intval($totapaidamount) > 0)
					  {
						if($discountstatus == 0)
						{
							if($totapaidamount >= $discount)
							{
								$discountapplystatus=1;
								$totapaidamount=$totapaidamount-$discount;
							}
							else
							{
								if($discount >= $totapaidamount)
								{
									$discountapplystatus=1;
									$discount=$discount-$totapaidamount;
									$totapaidamount=0;
								}
							}
							
							
							
						}
						
					  }
					  
					    if(intval($totalwaitamount) > 0)
				  {
					if($discountstatus == 0 && $discount > 0)
					{
						if($totalwaitamount >= $discount)
						{
							$discountapplystatus=1;
							$totalwaitamount=$totalwaitamount-$discount;
						}
						else
						{
							if($discount >= $totalwaitamount)
							{
								$discountapplystatus=1;
								$discount=$discount-$totalwaitamount;
								$totalwaitamount=0;
							}
						}
					}
					
				  }
					
				  
				
					$balanceamount= $orderinfo['total_amount']-$totapaidamount;
						$balanceamount= $balanceamount-$totalwaitamount;
				
				
						//$totalpayamt=($totapaidamount+$totalwaitamount) - $orderinfo['total_amount'];
						//$discountapplystatus=1;
						//$balanceamount=$totalpayamt-$discount;
					
				}
				else
				{
					$balanceamount= $orderinfo['total_amount']-$totapaidamount;
					$balanceamount= $balanceamount-$totalwaitamount;
				}

			}
			
			
				
  $summary.='<div class="row col-md-12 no-padd mt-sm">
                   <div class="pull-left col-md-6 no-padd">
                   <p class="grey-text">Betalt Beløp</p>
                   </div>                
                   <div class="pull-left col-md-6  no-padd text-right">
                     <p>kr '.formatcurrency($totapaidamount).'</p>
                   </div>
                   <div class="clearfix"></div>
                  <hr>
				</div>';			

$summary.='<div class="row col-md-12 no-padd mt-sm">
                   <div class="pull-left col-md-6 no-padd">
                   <p class="grey-text">Faktura Beløp</p>
                   </div>                
                   <div class="pull-left col-md-6  no-padd text-right">
                     <p>kr '.formatcurrency($totalwaitamount).'</p>
                   </div>
                   <div class="clearfix"></div>
                  <hr>
				</div>';

$summary.='<div class="row col-md-12 no-padd mt-sm">
                   <div class="pull-left col-md-6 no-padd">
                   <p class="grey-text"><span class="black=text">Utestående Beløp</p>
                   </div>                
                   <div class="pull-left col-md-6  no-padd text-right">
                     <p>kr '.formatcurrency($balanceamount).'</p>
                   </div>
                   <div class="clearfix"></div>
                  <hr>
                 </div>';				
				   
				   
				   
				   
			
			 
			 $summary.='<div class="clearfix"></div></div>';
			 
			
			   
			    
			 
			   
			   
			   
               $summary.='</div>
               <div class="col-md-6 no-padd">
                  <div class="row mt-sm">
                   <div class="pull-left col-md-6 no-padd">
                   <p class="grey-text">Delsum'.$min_price_txt.'</p>
                   </div>                
                   <div class="pull-left col-md-6 text-right min-cart">
                     <p>kr <span>'.formatcurrency($subtotal).'</span></p>
                   </div>
                   <div class="clearfix"></div>
                  <hr>
                 </div>';
				 
			
		 
		if($discount > 0){
			
				$summary.='<div class="row">
                   <div class="pull-left col-md-6 no-padd">
                   <p class="grey-text"><span class="black=text">Discount ( '.$vouchercode.' )</span></p>
                   </div>                
                   <div class="pull-left col-md-6   text-right">
                     <p>kr '.formatcurrency($discount).'</p>
                   </div>
                   <div class="clearfix"></div>
                  <hr>
                 </div>';
				 
		}
		
		
		$fprice=$cus_sub_total-$discount;
		
		if($orderinfo['type'] != 'shop')
		{
			if($fprice < $min_price)
			{
				$summary.='<div class="row" style="display:none;" id="minstebel">
                   <div class="pull-left col-md-6 no-padd">
                   <p class="grey-text">Minste beløp</p>
                   </div>                
                   <div class="pull-left col-md-6  text-right">
                     <p id="min_price">kr '.formatcurrency($min_price).'</p>
                   </div>
                   <div class="clearfix"></div>
                  <hr>
                 </div>';
			}
		
		$summary.='<div class="row">
		<div class="pull-left col-md-6 no-padd">
			<p class="grey-text">Levering</p>
                   </div>                
                   <div class="pull-left col-md-6   text-right">';
				   
				    if($delviery == 0)
					{
						 $summary.='<p id="delviery_amt">kr 0,00</p>';
					}
					else
					{
						
						 $summary.='<p id="delviery_amt">kr '.formatcurrency($delviery).'</p>';
					}
					
					
                     
                  $summary.='</div>
                   <div class="clearfix"></div>
                  <hr>
                 </div>';
       }   

					//if(intval($orderinfo['changed_amount']) > 0)
					if(trim($orderinfo['changed_amount']) != '')
					{
						$orderinfo['total_amount']=$orderinfo['changed_amount'];
					}
					
                  $summary.='<div class="row totalt mb-s">
                   <div class="pull-left col-md-6 no-padd">
                   <p>TOTALT</p>
                   </div>                
                   <div class="pull-left col-md-6  text-right">
                     <p id="total_amount">kr '.formatcurrency($orderinfo['total_amount']).'</p>
					 
					 <input type="hidden" id="order_total_amount" name="order_total_amount" value="'.$orderinfo['total_amount'].'"  />
					 
					  <input type="hidden" id="order_delviery_amt" name="order_delviery_amount" value="'.$delviery.'"  />
					  
					   <input type="hidden" id="order_discount_amount" name="order_discount_amount" value="'.$discount.'"  />
					   
					   
					     <input type="hidden" id="free_delivery_charge" name="free_delivery_charge" value="'.$free_delivery_charge.'"  />
						 
						   <input type="hidden" id="order_type" name="order_type" value="'.$orderinfo['type'].'"  />
					  
					 
					  
                   </div>
                   <div class="clearfix"></div>
                  <hr>';
                
				
				  if(intval($saldostatus) == 0)
				  {
				  
					$totpayment=$totalwaitamount + $totapaidamount;
				  
				   if($totpayment == $orderinfo['total_amount'])
					{
						
					}
					else
					{
						$summary.='<div class="row">
					<div class="pull-left col-md-12 no-padd text-right">
				 	 <div class="saldo-button1 group-pos">
                            
                                <div class="btn-group pull-right">
								<button type="button" id="pay_cash" onclick="paymenttype(\'cash\')" data-switch-set="#discount_type12" data-switch-value="Kontant" class="btn btn-default payment_type">Kontant</button>								
                                  <button type="button" id="pay_invoice" onclick="paymenttype(\'invoice\')" data-switch-set="#discount_type12" data-switch-value="Faktura" class="btn btn-default payment_type">Faktura</button>
                                  <button type="button"  id="pay_visa"   onclick="paymenttype(\'visa\')"  data-switch-set="#discount_type12" data-switch-value="Kort" class="btn btn-default payment_type">Kort</button>
                                </div>
                                <input type="hidden" name="opay_type" id="opay_type">
                             
                         </div>
						 <div class="clearfix"></div>
						 </div>
				 
				 </div>';
					}
					
				  }
				  else
				  {
					$summary.='<input type="hidden" value="saldo" name="opay_type" id="opay_type">';
				  }
				  
				  
				 
                  
			   $summary.='</div></div>
            </div>  
             ';
		
	    }
				   $notes='';
				   if($orderinfo['delivery_note']!='')
				   {
						$notes.=' <p><span>Delivery Notes: </span>'.$orderinfo['delivery_note'].'</p>';
				   }
				   
				    if($orderinfo['special_instruction']!='')
				   {
						$notes.=' <p><span>Spesialinstruksjoner: </span>'.$orderinfo['special_instruction'].'</p>';
				   }
				
				
				if($notes != '')
				{
						 $str .= '<div class="notes mt-sm">
                   <div class="pull-left col-md-12 no-padd">';
				   $str.=$notes;
				    $str.='</div>
                 <div class="clearfix"></div>
                 <hr>
               </div>';
				}
				
		$delivery_type = 'normal';
		$deliveryinfo =$this->general_model->checkMinimumAmount($delivery_type,'','',$customer['zone']);
		$deliveryinfo= json_encode($deliveryinfo);
		$deliveryinfo=htmlentities($deliveryinfo);
		
	//	echo '<pre>';print_r($orderlinedelivery);exit;
			if(count($orderlinedelivery) > 0)
			{
				//$orderlinedelivery_time=implode('<br>',$orderlinedelivery);
				//$orderlinedelivery_time = '<span>Levering: </span><br>' . $orderlinedelivery_time;
				
			//$customer_detail.=$orderlinedelivery_time.'</p>';
			}
			else
			{	
			
				$customer_detail.= $orderlevering;
			}
			
		
		
		
		
		
				
		$order_details = $company_info . $customer_detail .$str . $summary ;
		
		$order_details.=' 
		
		<input type="hidden" id="zoneinfo" name="zoneinfo"  value="'.$deliveryinfo.'" />  
		
		                <input type="hidden" id="eorder_id" name="eorder_id" value="'.$order_id.'" /> 
';
		
		
		$order_details.='<div class="row mt-sm" style="margin-bottom:30px">
                     <div class="col-md-3 pull-left">
                      
                     </div>
                     <div class="col-md-3 pull-right no-padd">';
					 $totpayment=$totalwaitamount + $totapaidamount;
					 
					if($totpayment == $orderinfo['total_amount'])
					{
						
					}
					else
					{
						if($orderinfo['order_status'] != '9' &&  $orderinfo['order_status'] != '11')
						{
							$order_details.='<button onclick="newpayment();" type="button"  class="btn-lg green npayment_type">Betal &nbsp;&nbsp;<span></span></button>';
						}
						
					}
					
					
					  // <button type="button" onclick=""  class="btn-lg green" >Betal</button>
                      $order_details.='</div> 
                     <div class="col-md-3 pull-right">';
					if($orderinfo['order_status'] != '9' &&  $orderinfo['order_status'] != '11')
					 {
                       $order_details.=' <button type="button" onclick=""  class="btn-lg orange"  id="order_close"  data-dismiss="modal" aria-hidden="true">'.$this->data['lang']['lang_update'].'</button>';
					 }
					 $order_details.='</div>
					  
					  <div class="clearfix"></div>              
                </div>';
				
				
				$order_details.='<script type="text/javascript" src="'.base_url().'application/themes/default/common/js/products.js"></script>';
		$result = array("order_details"=>$order_details);
		echo json_encode($result);exit;
	

  
	}
	
	function __getOrderReceipt()
	{
    
		//profiling
		$this->data['controller_profiling'][] = __function__;
		
		$order_id = $this->input->post('oid');

        $customer = $this->orders_model->getCustomerDetails($order_id);
        
        $orderinfo = $this->orders_model->getOrderinfo($order_id);
		
		$order_status = $orderinfo['order_status'];
		
		
		$this->data['visible']['wi_show_order'] = 1;
		
		$orderlogstatus=$this->process_order_model->getOrderlogstatus($order_id,5);
		
		if($orderlogstatus == 1){
			$this->data['visible']['wi_show_process_button'] = 1;
		}
        
        //get shop detail where order placed ( from SL or branch)
        $order_from = $orderinfo['type'];
        
        if($order_from == 'shop'){
            
            $partnerinfo = $this->general_model->getPartnerDetails($orderinfo['partner'],$orderinfo['partner_branch']);
            
            $company = $partnerinfo['name'];
            $address = $partnerinfo['street'] .", ". $partnerinfo['zip']. ' '.$partnerinfo['city'];
            $phone = $partnerinfo['phone']; 
            

        }
        else{
            $company = $this->data['settings_company']['company_name'];
            $address = $this->data['settings_company']['company_address_street'] .", ". $this->data['settings_company']['company_address_zip']. ' '.$this->data['settings_company']['company_address_city'];
            $phone = $this->data['settings_company']['company_telephone']; 
        }
		
		
		 $company_info ='<div class="company-info">
			     <div class="list-button">';
				 
					if($this->session->userdata['partner_branch'] !='1')
				 	$company_info .='<a href="#tagproduct" onclick="printTagOrder(\''.$order_id.'\');" id="printTagbtn" data-toggle="modal" class="btn-icon"><i class="fa fa-tags" aria-hidden="true"></i></a>';
					
					 if($this->session->userdata['partner_branch'] != 1000)
					{
				
					 $company_info .='<a href="#editproduct" onclick="editOrder(\''.$order_id.'\');" id="editproductbtn" data-toggle="modal" class="btn-icon"><i class="fa fa-edit" aria-hidden="true"></i></a>';
					}			
				   	 $company_info .='<a href="#orderHeatSeal" onclick="damageheatseal(\''.$order_id.'\');"  data-toggle="modal" class="btn-icon"><i class="fa fa-camera" aria-hidden="true"></i></a>
							
				   			<a href="javascript:void(0);" onclick="heatsealprint(\''.$order_id.'\');"   data-toggle="modal" class="btn-icon hsbutton"><i class="fa fa-print" aria-hidden="true"></i></a>
							
							
                   </div>
			<p class="text-center"><span>'.$company.'</span><br>
			  '.$address.'<br>
			  '.$phone.'</p>
				</div>
		  <hr>';
		
		
		$orderdetails = $this->orders_model->getOrderLine($order_id);
		//echo '<pre>';print_r();exit;
		$collectiontinfo=$this->orders_model->getCollectionDeliverytime('collection',$orderinfo['collection_time']);
		$deliveryinfo=$this->orders_model->getCollectionDeliverytime('delivery',$orderinfo['delivery_time']);
		
		
			$address=$customer['street_line_1'];
			if($customer['street_line_2'] != '')
			{
				$address.=', '.$customer['street_line_2'];
			}
			
			
		   $customer_detail='<div class="customer-detail mt-sm">
			   <div class="pull-left col-md-6 no-padd">
			   <p><span>Ordrenr: </span>#'.$orderinfo['id'].'</p>
			   <p><span>Telefon: </span>'.$customer['number'].'<br>
				<span>Navn: </span>'.$customer['customer_name'].'</br>';
				
				
			   if($collectiontinfo['sdate'] != '')
				{
					$customer_detail.='<span>Henting: </span>'.$collectiontinfo['sdate'].' &nbsp; '.$collectiontinfo['stime'].'-'.$collectiontinfo['etime'].'<br>';
				}
				else{
					
					$customer_detail.='<span>Innlevering: </span>'.date('d.m.Y H:i',strtotime($orderinfo['order_time'])).'<br>';

				}
				
				 $customer_detail .='</p>
                   </div>';      	
					
                 $customer_detail .='
				 <div class="pull-left col-md-6 no-padd">
				 <span>Adresse: </span>'.$address.', '.$customer['zip'].' '.$customer['city'].' <br>';
				
				if($customer['floor'] != '')
				{
			   $customer_detail.='<span>Etg: </span>'.$customer['floor'].' <br>';
			   }
			   if($customer['calling_bell'] != '')
				{
			   $customer_detail.='<span>RK: </span>'.$customer['calling_bell'].' <br>';
			   }
			   
			   
				
				if(($order_from != 'shop')){
					$customer_detail .='<span>Levering: </span>'.$deliveryinfo['sdate'].'  &nbsp; '.$deliveryinfo['stime'].'-'.$deliveryinfo['etime'];
				}
				   
				   
                    $str='</p></div><div class="clearfix"></div>
                   <hr>
                 </div> ';   
        
			//get results and save for tbs block merging
			$data = $this->orders_model->getOrderLine($order_id);
			$heatsealarray=array();
			//$str='';
			if(!empty($data)){
				$delsum = 0;
				$lstatus=1;
				$orderlinedelivery=array();
				$hstatus=0;
				for($j=0;$j<count($data);$j++){
				
			//	echo $data[$j]['id'];exit;
			
       				$arr = $this->orders_model->getProductDisplayName($data[$j]['product']);
					
					$data[$j]['name'] = $arr['name'];
			
			
			
					$heatsealstatus=$this->process_order_model->getHeatsealStatus($data[$j]['product'],$this->session->userdata['partner_branch']);
					$data[$j]['heatsealstatus']=1;
					if($heatsealstatus)
					{
						//echo '<pre>';print_r();exit;
						$data[$j]['heatsealstatus']=$heatsealstatus['heatseal'];
					}
					
					if($order_status >= 5)
					{
						$data[$j]['heatsealstatus']=0;
						
					}
					
					if($data[$j]['heatsealstatus'] > 0)
					{	
						$hstatus=1;
					}
					
					
					$sqty = $this->process_order_model->getHeatOrderLine($data[$j]['id']);
					
					$proqty = $this->process_order_model->validateProducttype($data[$j]['product']);
					
					//$proqty=$data[$j]['qty'];
		
					if(trim($data[$j]['changed_quantity']) != '')
					{
						$data[$j]['quantity']=$data[$j]['changed_quantity'];
						
						$subtotal = $data[$j]['changed_amount'];

					}
					else{
						$subtotal = $data[$j]['amount'];

					}
					
					if (round($data[$j]['quantity'], 0) == $data[$j]['quantity'])
					{
						// is whole number
						$data[$j]['quantity'] = round($data[$j]['quantity'], 0);
					}					
					
					
					if($data[$j]['p_b_delivery_time'] != '' && $data[$j]['p_b_delivery_time'] != '0000-00-00')
					{
						$p_b_delivery_time = date('d.m.Y',strtotime($data[$j]['p_b_delivery_time']));
						$orderlinedelivery[]='<span class="pname"> '.$data[$j]['name'].'</span>&nbsp;&nbsp; '.$p_b_delivery_time;
					}
					else{
						$p_b_delivery_time =  '';
					}
					
					
					$discount = $this->products_model->getProDiscount($data[$j]['product']);
					 $discount=$discount[0];
					 $ddesc='&nbsp;';
					 if(isset($discount['description']))
					 {
						$ddesc=' ('.$discount['description'].')';
					 }
					
				
					
					$productPrice=$subtotal;
					
					 $productPrice=round($productPrice);
						 
					
					
					$subtotalarray[]= $productPrice;
					//$delsum += $data[$j]['price'] * $data[$j]['quantity'];
						$delsum=$productPrice;
					$path_parts = pathinfo($data[$j]['path']);
					$img = $path_parts['filename'] .".".$path_parts['extension']; 
					//$total = ($delsum + $delivery_charge) - $discount ;
					if($data[$j]['quantity'] >= $sqty)
					{
						$newquantity=$data[$j]['quantity']-$sqty;
					}
					else
					{
						$newquantity=0;
					}
					
					$newquantity=$proqty*$newquantity;
					
					$onclick='';
					if(count($data) == $lstatus)
					{
						if(intval($newquantity) > 0)
						{
							if(intval($data[$j]['heatsealstatus']) > 0)
							{
								$heatsealarray[$data[$j]['id']]=$data[$j]['id'];
								$onclick=' onclick="setNeworderline(\''.$data[$j]['id'].'\',\'1\',\''.$proqty.'\');"';
								$str.=' <div style="cursor:pointer;"  id="tr_'.$data[$j]['id'].'" class="orderlist cart_table_item">';
							}
							else
							{
								$str.=' <div id="tr_'.$data[$j]['id'].'" class="orderlist noheatseal">';
							}
						}
						else
						{
							$str.=' <div id="tr_'.$data[$j]['id'].'" class="orderlist noheatseal">';
						}
						
						$newlstatus=1;
					}
					else
					{
						if(intval($newquantity) > 0)
						{
							if(intval($data[$j]['heatsealstatus']) > 0)
							{
								
								$onclick=' onclick="setNeworderline(\''.$data[$j]['id'].'\',\'0\',\''.$proqty.'\');"';
								$str.='<div style="cursor:pointer;"  id="tr_'.$data[$j]['id'].'" class="orderlist cart_table_item">';
							}
							else
							{
								$str.='<div id="tr_'.$data[$j]['id'].'" class="orderlist noheatseal">';
							}
						}
						else
						{
							$str.='<div id="tr_'.$data[$j]['id'].'" class="orderlist noheatseal">';
						}
						$newlstatus=0;
						
					}
					$actualqty=$data[$j]['quantity']*$proqty;
					
					if(intval($current_partner_branch) == 1000) //hvittsnip branch  ( only this branch aceept orders from comapny)
					{	
					
					$str .= '<div class="pull-left col-md-1">';
					
					if(intval($data[$j]['heatsealstatus']) == 0)
					{
						$str.='<p><input disabled="disabled" checked="checked" type="checkbox" name="orderlinechk[]" id="orderlinechk_'.$data[$j]['id'].'" value="'.$data[$j]['id'].'" /></p>';
					}
					else if(intval($data[$j]['heatsealstatus']) > 0 && $newquantity > 0)
					{
						$str.='<p><input type="checkbox" name="orderlinechk[]" onclick="skipOrderlineScan(this,\''.$data[$j]['id'].'\');" id="orderlinechk_'.$data[$j]['id'].'" value="'.$data[$j]['id'].'" /></p>';
					}
					else
					{
						$str.='<p><input disabled="disabled" checked="checked" type="checkbox" name="orderlinechk[]" id="orderlinechk_'.$data[$j]['id'].'" value="'.$data[$j]['id'].'" /></p>';
					}
					
					
				   		$str.='<div class="pull-left col-md-3 no-padd" '.$onclick.'>';
					}
					else{
						$str.='<div class="pull-left col-md-4" '.$onclick.'>';
					}
					
					
					$str .= '
                   <p>'.$data[$j]['name'].'</p>
                   </div>
					<div class="pull-left col-md-2 no-padd text-center" '.$onclick.'>
                   <p>'.$p_b_delivery_time.'</p>
                   </div>
				   
					<div class="pull-left col-md-2 no-padd text-center" '.$onclick.'>';
					$str.='<p>kr '.formatcurrency($data[$j]['price']).'</p>';
                    $str.='</div>			   
				   
                   <div class="pull-left col-md-2 no-padd text-center" '.$onclick.'>
                    <input type="hidden" value="'.$proqty.'" id="sqty_'.$data[$j]['id'].'" />
					<input type="hidden" value="'.$newquantity.'" id="newquantity_'.$data[$j]['id'].'" />
					<input type="hidden" value="'.$data[$j]['product'].'" id="product_'.$data[$j]['id'].'" />
					<span class="antall">';
					
					
					if(intval($data[$j]['heatsealstatus']) == 0)
					{
						 $str.='<font style="display:none;" data="0" id="qty_'.$data[$j]['id'].'">0</font>'; 
						 if($order_status >= 5)
						{
							if($heatsealstatus['heatseal'] == 1)
							{
						$str.='<font data="0" id="qty1_'.$data[$j]['id'].'">'.$data[$j]['quantity'].'</font>'; 
							}
							else
							{
									$str.='<font data="0" id="qty1_'.$data[$j]['id'].'">0</font>'; 
							}
							
							
						}
						else
						{
							$str.='<font data="0" id="qty1_'.$data[$j]['id'].'">0</font>'; 
						}
						  
						 $str.=' / '.$data[$j]['quantity'].'</span>';
					}
					else
					{
						
						$str.='<font style="display:none;" data="'.$newlstatus.'" id="qty_'.$data[$j]['id'].'">'.$newquantity.'</font>';
						 
						  $str.='<font  data="'.$newlstatus.'" id="qty1_'.$data[$j]['id'].'">'.$sqty.'</font>';
						  
						$str.=' / '.$data[$j]['quantity'].'</span>';
					}
					
					 
                   $str.='</div>';
				   				                   
				   
                   $str.='<div class="pull-left col-md-2 text-right" '.$onclick.'>
                    <p class="tprice"> kr '.formatcurrency($productPrice).'</p>
                   </div>
                   <div class="clearfix"></div>
                   <hr>
                 </div>';
				 
                    $lstatus++;
				}
				
				
				
		$subtotal=array_sum($subtotalarray);
		$cus_sub_total=$subtotal;
		$customerid = $customer['customerid'];
		$zone = $customer['zone'];
		$delivery_type = 'normal'; //default
		
		$this->data['reg_fields'][] = 'delivery';
		$this->data['fields']['delivery'] =$this->general_model->checkMinimumAmount($delivery_type,$customerid,$subtotal,$zone);
		$min_price=$this->data['fields']['delivery']['min_price'];
		$min_price_txt = '';
		if($orderinfo['type'] != 'shop')
		{
			if($cus_sub_total < $min_price)
			{
				$min_price_txt =  ' (Minste beløp kr '.formatcurrency($min_price).')';
			}
			$subtotal = ($subtotal < $min_price) ?  $min_price : $subtotal;
		}
		$delsum=$subtotal;
		
		
		$this->data['debug'][] = $this->general_model->debug_data;	
		$delviery = ($subtotal >= $this->data['fields']['delivery']['free_delivery_after']) ?  '0' : $this->data['fields']['delivery']['delivery_charge'];
		$old_delivery_charge= $this->data['fields']['delivery']['delivery_charge'];
		
		$summary='   <div class="handlekurv">   
               <div class="col-md-6">
               </div>
               <div class="col-md-6 no-padd">
                  <div class="row mt-sm">
                   <div class="pull-left col-md-6 no-padd">
                   <p class="grey-text">Delsum'.$min_price_txt.'</p>
                   </div>                
                   <div class="pull-left col-md-6 text-right">
                     <p>kr '.formatcurrency($subtotal).'</p>
                   </div>
                   <div class="clearfix"></div>
                  <hr>
                 </div>';
				 
			//if discount is a voucher	 
			$discount=0;	 
			if(intval($orderinfo['voucher']) > 0){
		
				$qry="SELECT *,ROUND(price, 0) as price FROM a_voucher WHERE id='".$orderinfo['voucher']."'";
				$query = $this->db->query($qry);
				$vdata = $query->row_array();//multi row array
				
				$vouchercode=$vdata['voucher'];
				
				if($vdata['percentage'] != '')
				{
					$percentage=$vdata['percentage']/100;
					$discount=$subtotal*$percentage;
					
					
				}
				else
				{
					$discount=$vdata['price'];
				}
				
				if(($vdata['free_delivery_charge'] == 1)  && ($orderinfo['type'] != 'shop'))
				{
					$delviery=0;
				}
				else
				{
					$delviery=$old_delivery_charge;
				}
				
			}
			else{
				//if the discount in % or kr
				if($orderinfo['order_discount'] !=''){
					$vouchercode = $orderinfo['order_discount'];
					if(stripos($vouchercode, '%')){
						$percentage = str_replace("%","",$vouchercode);
						$discount =  $delsum * ($percentage/100);
					}
					else{
						
						$discount = str_replace("kr ","",$vouchercode);
						$vouchercode = "kr ".formatcurrency($discount);
					}
					
				}

			}
				 
				 
			if($discount > 0){
				
				$summary.='<div class="row">
                   <div class="pull-left col-md-6 no-padd">
                   <p class="grey-text"><span class="black=text">Discount ( '.$vouchercode.' )</span></p>
                   </div>                
                   <div class="pull-left col-md-6  text-right">
                     <p>kr '.formatcurrency($discount).'</p>
                   </div>
                   <div class="clearfix"></div>
                  <hr>
                 </div>';
				
			}
				 
				
			$fprice=$cus_sub_total-$discount;
		
			if($orderinfo['type'] != 'shop')
			{
			
				if($fprice < $min_price)
				{
					$summary.='<div class="row">
						   <div class="pull-left col-md-6 no-padd">
						   <p class="grey-text">Minste beløp</p>
						   </div>                
						   <div class="pull-left col-md-6 text-right">
							 <p>kr '.formatcurrency($min_price).'</p>
						   </div>
						   <div class="clearfix"></div>
						  <hr>
						 </div>';
				}
			
				$summary.='<div class="row">
				<div class="pull-left col-md-6 no-padd">
				<p class="grey-text">Levering</p>
                   </div>                
                   <div class="pull-left col-md-6 text-right">';
				   
				    if($delviery == 0)
					{
						 $summary.='<p>kr 0,00</p>';
					}
					else
					{
						
						 $summary.='<p>kr '.formatcurrency($delviery).'</p>';
					}
					
					
                     
                  $summary.='</div>
                   <div class="clearfix"></div>
                  <hr>
                 </div>';
                } 
				
				
				
				//if(intval($orderinfo['changed_amount']) > 0)
				if(trim($orderinfo['changed_amount']) != '')
					{
						$orderinfo['total_amount']=$orderinfo['changed_amount'];
					}
					
					
                  $summary.='<div class="row totalt">
                   <div class="pull-left col-md-6 no-padd">
                   <p>TOTALT</p>
                   </div>                
                   <div class="pull-left col-md-6 text-right">
                     <p>kr '.formatcurrency($orderinfo['total_amount']).'</p>
                   </div>
                   <div class="clearfix"></div>
                  <hr>
                 </div> 
                  
			  </div>
            </div>  
             ';
		
	    }
				   $notes='';
				   if($orderinfo['delivery_note']!='')
				   {
						$notes.=' <p><span>Delivery Notes: </span>'.$orderinfo['delivery_note'].'</p>';
				   }
				   
				    if($orderinfo['special_instruction']!='')
				   {
						$notes.=' <p><span>Spesialinstruksjoner: </span>'.$orderinfo['special_instruction'].'</p>';
				   }
				

				if($notes != '')
				{
						 $str .= '<div class="notes mt-sm">
                   <div class="pull-left col-md-12 no-padd">';
				   $str.=$notes;
				    $str.='</div>
                 <div class="clearfix"></div>
                 <hr>
               </div>';
				}
				
			if(count($orderlinedelivery) > 0)
			{
				/*$orderlinedelivery_time=implode('<br>',$orderlinedelivery);
				$orderlinedelivery_time = '<span>Levering: </span><br>' . $orderlinedelivery_time;
				
				$customer_detail.=$orderlinedelivery_time;*/
			}
			else
			{	
				$customer_detail.=$orderlevering;
			}
			
			
				
		$order_details = $company_info . $customer_detail .$str . $summary ;
		
		if($order_status < 5 ){
		
		$order_details.=' <div class="row">
                     <div class="col-md-3 pull-left">';
                       /*<button type="button" id="tilbake" onclick="window.location.href=\''.base_url().'admin/\'" class="btn-lg orange">Tilbake</button>*/
                  $order_details.='   </div>
                     <div class="col-md-3 pull-right no-padd">';
                        if($hstatus > 0)
						{
							$order_details.=' <button type="button" onclick="updateorderlog();" id="begynne" class="btn-lg green">Bekreft og skriv utt</button>';
						}
						
                       
                       $order_details.='</div>               
                    <div class="clearfix"><input type="hidden" id="ordrenr" name="ordrenr" value="'.$order_id.'" /></div> 
                </div> ';
				
		}
		
		$result = array("order_details"=>$order_details);
		echo json_encode($result);exit;
  
	}
	
	function __saveeditorder()
	{
		if(count($_POST) > 0)
		{
			$orderid=$_POST['eorder_id'];
			$order_total_amount=$_POST['order_total_amount'];
			$order_delviery_amount=$_POST['order_delviery_amount'];
			$pricearray=array();
			foreach($_POST['product'] as $pro=>$proitems)
			{
				$pricearray[$proitems['orderline']]=$this->orders_model->updateorderlineinfo($proitems,$orderid);
			}
			$orderamount=array_sum($pricearray);
			$orderinfo = $this->orders_model->getOrderinfo($orderid);
			
			
			
		if(intval($orderinfo['voucher']) > 0)
		{
			$qry="SELECT * FROM a_voucher WHERE id='".$orderinfo['voucher']."'";
			$query = $this->db->query($qry);
			$vdata = $query->row_array();//multi row array
			
			$vouchercode=$vdata['voucher'];
			
			if($vdata['percentage'] != '')
			{
				$percentage=$vdata['percentage']/100;
				$discount=$subtotal*$percentage;
				
				
			}
			else
			{
				$discount=$vdata['price'];
			}
			
			$orderamount=$orderamount-$discount;	
		}		
		else{
			
			
			if($orderinfo['order_discount'] !='')
			{
					$vouchercode = $orderinfo['order_discount'];
					if(stripos($vouchercode, '%')){
						$percentage = str_replace("%","",$vouchercode);
						 $discount =  $orderamount * ($percentage/100);
					}
					else{
						
						$discount = str_replace("kr ","",$vouchercode);
					
					}
					
					$orderamount=$orderamount-$discount;
			}
			
		}	
			
			
			$this->orders_model->updateorderinfo($orderid,$orderamount,$order_delviery_amount);
			$result = array("status"=>'success',"message"=>"Order has been updated successfully.");
		}
		else
		{
			$result = array("status"=>'error',"message"=>"Invalid");
		
		}
	
		echo json_encode($result);exit;

	}
	
	function __addOrderline()
	{	
		if(count($_POST) > 0)
		{
			$productid=$_POST['product'];
			$order=intval($_POST['order']);

			$orderinfo = $this->orders_model->getOrderinfo($order);
			
		
			$cus_id = $this->session->userdata['pos_customer_id'];
			$subscription = $this->payments_model->getSaldostatus($cus_id);
			
			$productinfo= $this->products_model->getProduct($productid,'main','',$orderinfo['type'],$subscription);
			$p_b_delivery_time ='';
			if($orderinfo['type'] == 'shop'){
				$duration=$productinfo['duration'];
				$days = floor (($duration*60) / 1440);	
				//convert hours 
				$c_date=date('Y-m-d',strtotime($orderinfo['order_time']));
				$date = new DateTime($c_date);
				
				if($duration == 96){
					$p_b_delivery_time=addDays($c_date,$days,true);
				}
				else{
					$date->modify("+$days day");
					$p_b_delivery_time = $date->format('Y-m-d');
				}
				
			}
			
			$price=$productinfo['price'];
			$order_delviery_amount =  $orderinfo['delivery_charge'];
			$amount = ($orderinfo['changed_amount']!='') ?  $orderinfo['changed_amount'] : $orderinfo['total_amount']; 
			$order_total_amount = $amount + $price;
			
				
			
			
			/*echo $order_delviery_amount."<br>";
			echo $amount."<br>";
			echo $order_total_amount."<br>";
			echo $price."<br>";*/

			$orderline=$this->orders_model->addorderlineinfo($order,$productid,$price,$p_b_delivery_time);
			
			$this->orders_model->updateOrderline($orderline,$order,1,$price,'','','','');
			
			
			//$result =$this->orders_model->updateorderinfo($order,$order_total_amount,$order_delviery_amount);
			
			if($orderline)
			{
				$result = array("status"=>'success',"message"=>"Product has been added successfully.");
			}
			else
			{
				$result = array("status"=>'error',"message"=>"Invalid");
			}
			
			echo json_encode($result);exit;
		}
		
		
		///[product] => 7echo '<pre>';print_r($_POST);exit;
    //[/order] => 1049
	}
	
	/*Make payment for individual orderline amount */
	function __makeorderpayment()
	{
		$request = $_POST;
		$saldostatus=$_POST['saldostatus'];
		$orderid=$_POST['eorder_id'];
		$in_type=$_POST['opay_type'];
		$totalorderline=intval($_POST['totalorderline']);
		$checkedoderline=intval($_POST['checkedoderline']);

		$orderinfo = $this->orders_model->getOrderinfo($orderid);
		
				$amttarray=array();
				foreach($_POST['ordertotal'] as $orderline)
				{
					$amttarray[]=$orderline;
				}
				$order_amount=array_sum($amttarray);
		
		
		
		//$total_amt=($orderinfo['changed_amount'] != '') ? $orderinfo['changed_amount']:$orderinfo['total_amount'];
	
	//echo '<pre>';print_r($orderamounts);exit;
	
		$discount=0;
		
				if(intval($orderinfo['voucher']) > 0)
				{
					$qry="SELECT * FROM a_voucher WHERE id='".$orderinfo['voucher']."'";
					$query = $this->db->query($qry);
					$vdata = $query->row_array();//multi row array
					$vouchercode=$vdata['voucher'];
					if($vdata['percentage'] != '')
					{
						$percentage=$vdata['percentage']/100;
						$discount=$order_amount*$percentage;
					}
					else
					{
						$discount=$vdata['price'];
					}
					if($vdata['free_delivery_charge'] == 1)
					{
						$delviery=0;
					}
					else
					{
						$delviery=$old_delivery_charge;
					}
					$discount=$discount;
				}		
				else
				{
					
					
						if($orderinfo['order_discount'] !='')
						{
							$vouchercode = $orderinfo['order_discount'];
							if(stripos($vouchercode, '%'))
							{
								$percentage = str_replace("%","",$vouchercode);
								
								$discount =  $order_amount * ($percentage/100);
							}
							else
							{
								$discount = str_replace("kr ","",$vouchercode);
							}
						
						}
					
				}
			
			
		
		
		if(strtolower($in_type) == 'visa')
		{
			$payment_type='visa';
		}
		if(strtolower($in_type) == 'cash')
		{
			$payment_type='cash';
		}
		
		
		if(strtolower($in_type) == 'invoice')
		{
			$payment_type='invoice';
			/*if(trim($this->session->userdata['customer']['email']) == '')
			{
				$result = array("status"=>'emailerror','message'=>
'Your email is empty. Please enter your email address');
				echo json_encode($result);exit;
			}*/
		}
		
	
		
		$customer=$this->session->userdata['customer']['id'];
		if(count($_POST['corderlines']) > 0)
		{	
			
			if($totalorderline == count($_POST['corderlines']))
			{
				$amtarray=array();
				foreach($_POST['corderlines'] as $orderline)
				{
					$amtarray[]=$_POST['ordertotal'][$orderline];
				}
				$orderamount=array_sum($amtarray);
				
				$orderamount=$orderamount-$discount;
				
				
				if($in_type == 'visa')
				{
					$in_status='paid';
				}
				else
				{
					//$in_status='waiting';
					$in_status='paid';
				}
				
				if($saldostatus)
				{
				
						$customer=$this->session->userdata['customer']['id'];
						$data = $this->payments_model->getAccountBalance($customer);
						$autofillamt=$this->customer_model->getAutofileamt($customer);

						if(intval($data['paid']) < 0)
						{
								//$newamt=$orderamount;
								
								if(intval($autofillamt) > 0)
								{
									$newamt=$autofillamt;
									$newpaid=abs($data['paid'])+$orderamount;
									if(intval($data['pending']) > $orderamount && $newpaid < intval($data['pending']))
									{
										$newamt=0;
									}
																		
								}
								else
								{
									$newamt=$orderamount;
								}
								
								$paymentarray=array(
								'type'=>$type,
								'in_type'=>'invoice',
								'in_status'=>'pending',
								'customer'=>$customer,
								'amount'=>$newamt,
								'regtime'=>date('Y-m-d H:i:s'));
								$saldoid=$this->payments_model->addCustomerPayment($paymentarray);
								$this->payments_model->updateCustomerBalance($customer,$newamt,'pending');
						}
						else
						{
							if($orderamount > intval($data['paid']))
							{
								$bal=$orderamount-intval($data['paid']);
								//$newamt=$bal;
								if(intval($autofillamt) > 0)
								{
									$newamt=$autofillamt;	
									$newpaid=abs($data['paid'])+$bal;
									if(intval($data['pending']) > $bal && $newpaid < intval($data['pending']))
									{
										$newamt=0;
									}									
								}
								else
								{
									$newamt=$bal;
								}

								$paymentarray=array(
								'type'=>$type,
								'in_type'=>'invoice',
								'in_status'=>'pending',
								'customer'=>$customer,
								'amount'=>$bal,
								'regtime'=>date('Y-m-d H:i:s'));
								$saldoid=$this->payments_model->addCustomerPayment($paymentarray);
								$this->payments_model->updateCustomerBalance($customer,$newamt,'pending');
							}
						}
						
						
				
				
					$in_status='paid';
					$payment_type='account';
					$type='out';
					$paytype=trim($_POST['paytype']);
					$customer=$this->session->userdata['customer']['id'];
					$regtime=date('Y-m-d H:i:s');
					$paymentarray=array(
					'type'=>$type,
					'in_status'=>$in_status,
					'customer'=>$customer,
					'order'=>$orderid,
					'regtime'=>$regtime,
					'amount'=>$orderamount);
					$cpaystatus=$this->payments_model->addCustomerPayment($paymentarray);
					if($cpaystatus)
					{
						$result = $this->payments_model->updateCustomerBalance($customer,$orderamount,'','debit');
					}
					
					
				}
				
				if($saldostatus)
				{
				
					$data = $this->payments_model->getAccountBalance($customer);
					$pendingsaldo = $data['pending'];
					$paidsaldo = $data['paid'];
					
					if($orderamount > $paidsaldo)
					{
						$in_status='paid';
					}
				}
				$this->orders_model->orderlinePayment($orderid,0,$payment_type,$in_status,$checkedoderline);
				$orderpaymentstatus=$this->payments_model->updateorderPaymentstatus($orderid);
					
				
			}
			else
			{
			
				$in_discount_status=1;
				if(count($_POST['orderpayment']) > 0)
				{
					foreach($_POST['orderpayment'] as $payingstatus)
					{
						if($payingstatus == 'paid' || $payingstatus == 'waiting')
						{
							$in_discount_status=0;
						}
					}
				}
				
				
				
				foreach($_POST['corderlines'] as $orderline)
				{
				
				
						$orderlineamount=$_POST['ordertotal'][$orderline];
						
							if(intval($in_discount_status) > 0)
							{
								if($orderlineamount >= $discount)
								{
									$orderlineamount=$orderlineamount-$discount;
								}
								else
								{
									if($discount >= $orderlineamount)
									{
										$discount=$discount-$orderlineamount;
										$orderlineamount=0;
									}
								}
							}
						
						$data = $this->payments_model->getAccountBalance($customer);
						$pendingsaldo = $data['pending'];
						$paidsaldo = $data['paid'];
						
						if($paidsaldo > $orderlineamount)
						{
							if($saldostatus)
							{
								$customer=$this->session->userdata['customer']['id'];
								$data = $this->payments_model->getAccountBalance($customer);
								$autofillamt=$this->customer_model->getAutofileamt($customer);
								if(intval($data['paid']) < 0)
								{
										if(intval($autofillamt) > 0)
										{
											$newamt=$autofillamt;	
											$newpaid=abs($data['paid'])+$orderlineamount;
												if(intval($data['pending']) > $orderlineamount && $newpaid < intval($data['pending']))
												{
													$newamt=0;
												}
										}
										else
										{
											$newamt=$orderlineamount;
										}
														
										$paymentarray=array(
										'type'=>$type,
										'in_type'=>'invoice',
										'in_status'=>'pending',
										'customer'=>$customer,
										'amount'=>$newamt,
										'regtime'=>date('Y-m-d H:i:s'));
										$saldoid=$this->payments_model->addCustomerPayment($paymentarray);
										$this->payments_model->updateCustomerBalance($customer,$newamt,'pending');
								}
								else
								{
									if($orderlineamount > intval($data['paid']))
									{
										$bal=$orderlineamount-intval($data['paid']);
									//	$newamt=$bal;
									
										if(intval($autofillamt) > 0)
										{
											$newamt=$autofillamt;	
											$newpaid=abs($data['paid'])+$bal;											
												if(intval($data['pending']) > $bal && $newpaid < intval($data['pending']))
												{
													$newamt=0;
												}
										}
										else
										{
											$newamt=$bal;
										}
						
										$paymentarray=array(
										'type'=>$type,
										'in_type'=>'invoice',
										'in_status'=>'pending',
										'customer'=>$customer,
										'amount'=>$bal,
										'regtime'=>date('Y-m-d H:i:s'));
										$saldoid=$this->payments_model->addCustomerPayment($paymentarray);
										$this->payments_model->updateCustomerBalance($customer,$newamt,'pending');
									}
								}
								$in_status='paid';
								$payment_type='account';
								$type='out';
								$paytype=trim($_POST['paytype']);
								$customer=$this->session->userdata['customer']['id'];
								$regtime=date('Y-m-d H:i:s');
								$paymentarray=array(
								'type'=>$type,
								'in_status'=>$in_status,
								'customer'=>$customer,
								'order'=>$orderid,
								'orderline'=>$orderline,
								'regtime'=>$regtime,
								'amount'=>$orderlineamount);
								//echo '<pre>';print_r($paymentarray);exit;
								$cpaystatus=$this->payments_model->addCustomerPayment($paymentarray);
								if($cpaystatus)
								{
									$result = $this->payments_model->updateCustomerBalance($customer,$orderlineamount,'','debit');
								}
							    
								
							}
							else
							{
								if($in_type == 'visa')
								{
									$in_status='paid';
								}
								else
								{
									$in_status='paid';
								}
								
								
							}
						}
						else
						{
							
								if($in_type == 'visa')
								{
									$in_status='paid';
								}
								else
								{
									$in_status='paid';
								}
								
							
								
								if($saldostatus)
								{
										$newamt=$orderlineamount;
										$customer=$this->session->userdata['customer']['id'];
										$data = $this->payments_model->getAccountBalance($customer);
										$autofillamt=$this->customer_model->getAutofileamt($customer);
										if(intval($data['paid']) < 0)
										{
												if(intval($autofillamt) > 0)
												{
													$newamt=$autofillamt;	
													$newpaid=abs($data['paid'])+$orderlineamount;
														if(intval($data['pending']) > $orderlineamount && $newpaid < intval($data['pending']))
														{
															$newamt=0;
														}
												}
												else
												{
													$newamt=$orderlineamount;
												}
						
												$paymentarray=array(
												'type'=>'in',
												'in_type'=>'invoice',
												'in_status'=>'pending',
												'customer'=>$customer,
												'amount'=>$newamt,
												'regtime'=>date('Y-m-d H:i:s'));
												$this->payments_model->addCustomerPayment($paymentarray);
												$this->payments_model->updateCustomerBalance($customer,$newamt,'pending');
										}
										else
										{
											if($orderlineamount > intval($data['paid']))
											{
												$bal=$orderlineamount-intval($data['paid']);
												//$newamt=$bal;
												
												if(intval($autofillamt) > 0)
												{
													//$newamt=$autofillamt;	
													$newpaid=abs($data['paid'])+$bal;
														if(intval($data['pending']) > $bal && $newpaid < intval($data['pending']))
														{
															$newamt=0;
														}
												}
												else
												{
													$newamt=$bal;
												}
												
						
												$paymentarray=array(
												'type'=>$type,
												'in_type'=>'invoice',
												'in_status'=>'pending',
												'customer'=>$customer,
												'amount'=>$bal,
												'regtime'=>date('Y-m-d H:i:s'));
												$this->payments_model->addCustomerPayment($paymentarray);
												$this->payments_model->updateCustomerBalance($customer,$newamt,'pending');
											}
										}
								
								
									$in_status='paid';
									$payment_type='account';
									$type='out';
									$paytype=trim($_POST['paytype']);
									$customer=$this->session->userdata['customer']['id'];
									$regtime=date('Y-m-d H:i:s');
									$paymentarray=array(
									'type'=>$type,
									'in_status'=>$in_status,
									'customer'=>$customer,
									'order'=>$orderid,
									'orderline'=>$orderline,
									'regtime'=>$regtime,
									'amount'=>$orderlineamount);
										
								$cpaystatus=$this->payments_model->addCustomerPayment($paymentarray);
								if($cpaystatus)
								{	
									$result = $this->payments_model->updateCustomerBalance($customer,$orderlineamount,'','debit');
								}	
									
								}
								
								
						}
					
				if($saldostatus)
				{
					
						//$data = $this->payments_model->getAccountBalance($customer);
						//$pendingsaldo = $data['pending'];
						//$paidsaldo = $data['paid'];
						
						if($orderlineamount > $paidsaldo)
						{
							$in_status='paid';
						}
				 }
						$this->orders_model->orderlinePayment($orderid,$orderline,$payment_type,$in_status,$checkedoderline);
						$orderpaymentstatus=$this->payments_model->updateorderPaymentstatus($orderid);
				}
			
			}
			
			
			
			$data = $this->payments_model->getAccountBalance($customer);
			$pendingsaldo = $data['pending'];
			$paidsaldo = $data['paid'];
			$saldocolor='0';
			if($paidsaldo < 0)
			{
				$saldocolor='1';
			}
				
				
			
				
			if(intval($pendingsaldo) > 0)
			{	
				$amount=formatcurrency($paidsaldo).' ('.formatcurrency($pendingsaldo).')';
			}
			else
			{
				$amount=formatcurrency($paidsaldo);
			}


			$newarray=array('status'=>'success','orderid'=>$orderid,'message'=>'Payment status has been updated','orderpaymentstatus'=>$orderpaymentstatus,'amount'=>$amount,'saldocolor'=>$saldocolor);
			echo json_encode($newarray);exit;
			
		}
		else
		{
			$newarray=array('status'=>'error','message'=>'Please select atleast one product to continue.');
			echo json_encode($newarray);exit;
			
		}
		
		
	}
	
	function __saveOrder()
    {	
	
		$request = $_POST;
		$request["device_type"]=$this->session->userdata['logged_in'];
		$emailaddress=$this->session->userdata['customer']['email'];
		$request["total_amount"]=$_POST['total'];
		$request["delivery_time"]='';
		$request["collection_time"]='';
		$request["delivery_notes"]='';
		$request["special_instruction"]=$_POST['instruction'];
		$request["payment_type"]='';
		$request["products"]=$this->session->userdata['cart_contents'];
		$request["order_status"]=3; //collected in shop directly
		$request["customer"]=$this->session->userdata['customer']['id'];  
		$subscription = $this->payments_model->getSaldostatus($request["customer"],false);
		//$result = array("status"=>'error',"message"=>"Handlevognen er tom");
		
			$sdata=array();
			if($this->session->userdata['salg'])
			{
				$sdata['salg']=$this->session->userdata['salg'];
			}
			else
			{
				$sdata['salg']=array();
			}
			
			
			$salg_status=0;
			if(count($sdata['salg']) > 0)
			{
				$salg_status=1;
			}
			
			if(intval($salg_status) > 0)
			{
				if($_POST['opay_type'] == '' && $subscription == 0)
				{
					$result = array("status"=>'error',"message"=>"Please select any one payment type'");
					echo json_encode($result);exit;
				}
			}
			
			
			
			
			
			
		//$data = $this->payments_model->getAccountBalance($request["customer"]);
		
		//echo '<pre>';print_r($data['paid']);exit;
		
		
		$request['voucher']=$request['voucher'];
		$payment_status='pending';
		
		$discount_type = $request["discount_type"];
		$discount = $request["discount_value"];
		
		if($discount_type=='%'){
			$request["discount"] = $discount.'%';
		}
		else if($discount_type=='Kr'){
			$request["discount"] = 'kr '.$discount;
		}
		else if($discount_type=='Kupongkode'){
			$request["discount"] = NULL;
		}
		
		//echo '<pre>';print_r($request);exit;
		

		if($this->session->userdata['cart_contents'])
		{
			$orderid = $this->orders_model->saveOrder($request,$payment_status);
			
			
			if($orderid)
			{
				
				$orderid = sprintf("%08d", $orderid);
				
				//get orderline
				$categorycount = $this->orders_model->getOrderLinecategory($orderid);
				/*$this->orders_model->sendorderemail($orderid);*/
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
				
				if($subscription)
				{
						/*if(intval($salg_status) > 0)
						{
							self::__paymentMultiOrder(true,$orderid);
						}
						else
						{
							self::makeorderpaymentself($orderid);
						}*/
						
						$customer = $this->session->userdata['customer']['id']; 
						$balance = $this->payments_model->getAccountBalance($customer);
						
						$pendingsaldo = $balance['pending'];
						$paidsaldo = $balance['paid'];
						$this->payments_model->updateOrderBalance($orderid,$pendingsaldo,$paidsaldo);
						
						
						$saldocolor='0';
						if(intval($paidsaldo) < 0)
						{
							$saldocolor='1';
						}
						
						if(intval($pendingsaldo) > 0)
						{	
							$amount=formatcurrency($paidsaldo).' ('.formatcurrency($pendingsaldo).')';
						}
						else
						{
							$amount=formatcurrency($paidsaldo);
						}


						$result = array("status"=>'success',"message"=>"Bestillingen Har blitt plassert. Ordrenummer er #$orderid",'orderid'=>$orderid,'customer'=>$this->session->userdata['customer']['id'],'orderpaymentstatus'=>$orderpaymentstatus,'amount'=>$amount,'saldocolor'=>$saldocolor,'categorycount'=>$categorycount);
						
						
				}
				else
				{
				
					/*if(intval($salg_status) > 0)
					{
						self::__paymentMultiOrder(true,$orderid);
					}*/
					
					
					$result = array("status"=>'success',"message"=>"Bestillingen Har blitt plassert. Ordrenummer er #$orderid",'orderid'=>$orderid,'customer'=>$this->session->userdata['customer']['id'],'orderpaymentstatus'=>'','amount'=>'','saldocolor'=>'','categorycount'=>$categorycount);
						
				}
				
				
				$this->cart->destroy();
				
				$sdata=array();
				$sdata['salg_status']=0;
				$this->session->set_userdata($sdata);
				$s_data['salg']=array();				
				$this->session->set_userdata($s_data);
				
				
			}
			else
			{
				$result = array("status"=>'error',"message"=>"Order not placed. Please check Administrator.");
			}
			
		}
		else
		{	
			$result = array("status"=>'error',"message"=>"Handlevognen er tom");
		}
			
			
			echo json_encode($result);exit;
			
		
		}
		
	
	/*Salso customer place order make payment amount */
	function makeorderpaymentself($orderid)
	{
		//$request = $_POST;
		$saldostatus=true;
		//$in_type=$_POST['opay_type'];
		//$totalorderline=intval($_POST['totalorderline']);
		//$checkedoderline=intval($_POST['checkedoderline']);

		$orderinfo = $this->orders_model->getOrderinfo($orderid);
		//echo '<pre>';print_r();exit;
		$orderlines=$this->orders_model->getOrderLine($orderid);
		
	
				$amttarray=array();
				$in_meter=0;
				$paidarray=array();
				$pendingarray=array();
				foreach($orderlines as $orderline)
				{
					if(intval($orderline['in_meter']) > 0)
					{
						$in_meter=1;
						$pendingarray[$orderline['id']]=$orderline['id'];
					}
					else
					{
						
						if($orderline['changed_amount'] != '')
						{
							$amttarray[]=$orderline['changed_amount'];
							$paidarray[$orderline['id']]=$orderline['changed_amount'];
						}
						else
						{
							$amttarray[]=$orderline['amount'];
							$paidarray[$orderline['id']]=$orderline['amount'];
						}
						
					}
					
					
				}
				//echo '<pre>';print_r($pendingarray);exit;
				$order_amount=array_sum($amttarray);
		
				if(intval($order_amount) == 0)
				{
					return false;
				}
				
				$skiporderline='';
				if(count($pendingarray) > 0)
				{
					$skiporderline=implode(',',$pendingarray);
				}
				
			
		
		
		//$total_amt=($orderinfo['changed_amount'] != '') ? $orderinfo['changed_amount']:$orderinfo['total_amount'];
	
	//echo '<pre>';print_r($orderamounts);exit;
	
				$discount=0;
		
				if(intval($orderinfo['voucher']) > 0)
				{
					$qry="SELECT * FROM a_voucher WHERE id='".$orderinfo['voucher']."'";
					$query = $this->db->query($qry);
					$vdata = $query->row_array();//multi row array
					$vouchercode=$vdata['voucher'];
					if($vdata['percentage'] != '')
					{
						$percentage=$vdata['percentage']/100;
						$discount=$order_amount*$percentage;
					}
					else
					{
						$discount=$vdata['price'];
					}
					if($vdata['free_delivery_charge'] == 1)
					{
						$delviery=0;
					}
					else
					{
						$delviery=$old_delivery_charge;
					}
					$discount=$discount;
				}		
				else
				{
					
					
						if($orderinfo['order_discount'] !='')
						{
							$vouchercode = $orderinfo['order_discount'];
							if(stripos($vouchercode, '%'))
							{
								$percentage = str_replace("%","",$vouchercode);
								
								$discount =  $order_amount * ($percentage/100);
							}
							else
							{
								$discount = str_replace("kr ","",$vouchercode);
							}
						
						}
					
				}
			
				$customer=$this->session->userdata['customer']['id'];
				$orderamount=$order_amount;
				$orderamount=$orderamount-$discount;
				$data = $this->payments_model->getAccountBalance($orderinfo['customer']);
				$autofillamt=$this->customer_model->getAutofileamt($orderinfo['customer']);
				if(intval($data['paid']) < 0)
				{
						if(intval($autofillamt) > 0)
						{
							$newamt=$autofillamt;	
							$newpaid=abs($data['paid'])+$orderamount;
							if(intval($data['pending']) > $orderamount && $newpaid < intval($data['pending']))
							{
								$newamt=0;
							}							
						}
						else
						{
							$newamt=$orderamount;
						}
						if($newamt!=0){
							$paymentarray=array(
							'type'=>$type,
							'in_type'=>'invoice',
							'in_status'=>'pending',
							'customer'=>$orderinfo['customer'],
							'amount'=>$newamt,
							'regtime'=>date('Y-m-d H:i:s'));
							$saldoid=$this->payments_model->addCustomerPayment($paymentarray);
							$this->payments_model->updateCustomerBalance($orderinfo['customer'],$newamt,'pending');
						}
				}
				else
				{
					if($orderamount > intval($data['paid']))
					{
						$bal=$orderamount-intval($data['paid']);
						
						if(intval($autofillamt) > 0)
						{
							//$newamt=$autofillamt;	
							$newpaid=abs($data['paid'])+$bal;
							if(intval($data['pending']) > $bal && $newpaid < intval($data['pending']))
							{
								$newamt=0;
							}
						}
						else
						{
							$newamt=$bal;
						}
						
						if($newamt!=0){
							$paymentarray=array(
							'type'=>$type,
							'in_type'=>'invoice',
							'in_status'=>'pending',
							'customer'=>$orderinfo['customer'],
							'amount'=>$bal,
							'regtime'=>date('Y-m-d H:i:s'));
							$saldoid=$this->payments_model->addCustomerPayment($paymentarray);
							$this->payments_model->updateCustomerBalance($orderinfo['customer'],$newamt,'pending');
						}
					}
				}
				
			
				if(intval($in_meter) > 0)
				{
					if(count($paidarray) > 0)
					{
						foreach($paidarray as $olineid=>$olineamt)
						{
							$in_status='paid';
							$payment_type='account';
							$type='out';
							$customer=$this->session->userdata['customer']['id'];
							$regtime=date('Y-m-d H:i:s');
							$paymentarray=array(
							'type'=>$type,
							'in_status'=>$in_status,
							'customer'=>$customer,
							'orderline'=>$olineid,
							'regtime'=>$regtime,
							'amount'=>$olineamt);
							$cpaystatus=$this->payments_model->addCustomerPayment($paymentarray);
							if($cpaystatus)
							{
								$result = $this->payments_model->updateCustomerBalance($customer,$olineamt,'','debit');
							}
						}
					}
					
				}
				else
				{
					$in_status='paid';
					$payment_type='account';
					$type='out';
					$customer=$this->session->userdata['customer']['id'];
					$regtime=date('Y-m-d H:i:s');
					$paymentarray=array(
					'type'=>$type,
					'in_status'=>$in_status,
					'customer'=>$customer,
					'order'=>$orderid,
					'regtime'=>$regtime,
					'amount'=>$orderamount);
					$cpaystatus=$this->payments_model->addCustomerPayment($paymentarray);
					if($cpaystatus)
					{
						$result = $this->payments_model->updateCustomerBalance($customer,$orderamount,'','debit');
					}
				}
			
					
					$data = $this->payments_model->getAccountBalance($customer);
					$pendingsaldo = $data['pending'];
					$paidsaldo = $data['paid'];
					
					if($orderamount > $paidsaldo)
					{
						$in_status='paid';
					}
				
		$this->payments_model->saldoorderlinePayment($orderid,$payment_type,$in_status,$skiporderline);
		$orderpaymentstatus=$this->payments_model->updateorderPaymentstatus($orderid,$skiporderline);
					
			return $orderpaymentstatus;
			
		
	 }
		
		/*print order receipt once ordered*/
		function __printReceipt(){
			
			//profiling
			$this->data['controller_profiling'][] = __function__;
			
			//$order_id = 20154586;
			$order_id = $this->uri->segment(4);
			
			//print_r($this->session);
			
			//get customer details
			$this->data['reg_fields'][] = 'customer';
			$this->data['fields']['customer'] = $customer = $this->orders_model->getCustomerDetails($order_id);
			
			
			$saldo_amount='';
			$data = $this->payments_model->getAccountBalance($customer['customer']);
			$pendingsaldo = $data['pending'];
			$paidsaldo = $data['paid'];

			if(intval($pendingsaldo) > 0)
			{	
				$saldo_amount= formatcurrency($paidsaldo).' ('.formatcurrency($pendingsaldo).')';
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
			
			
			//get order details
			$this->data['reg_fields'][] = 'order';
			$this->data['fields']['order'] = $orderinfo = $this->orders_model->getOrderinfo($order_id);
			
			
			$order_status = $orderinfo['order_status'];
			
			$this->data['fields']['order']['totalt'] = ($orderinfo['changed_amount']!='') ?  $orderinfo['changed_amount'] : $orderinfo['total_amount'];

			
			//get shop detail where order placed ( from SL or branch)
			$order_from = $orderinfo['type'];
			$this->data['reg_fields'][] = 'branch';
			if($order_from == 'shop'){
				
				$partnerinfo = $this->general_model->getPartnerDetails($orderinfo['partner'],$orderinfo['partner_branch']);
				
				$this->data['fields']['branch']['company'] = $partnerinfo['name'];
				$this->data['fields']['branch']['street'] = $partnerinfo['street'] ;
				$this->data['fields']['branch']['zip'] =  $partnerinfo['zip']. ' '.$partnerinfo['city'];
				$this->data['fields']['branch']['phone'] = $partnerinfo['phone']; 
				$this->data['fields']['branch']['org_nr'] = $partnerinfo['org_nr']; 
	
			}
			else{
				$this->data['fields']['branch']['company'] = $this->data['settings_company']['company_name'];
				$this->data['fields']['branch']['street'] = $this->data['settings_company']['company_address_street'] .", ". $this->data['settings_company']['company_address_zip']. ' '.$this->data['settings_company']['company_address_city'];
				$this->data['fields']['branch']['phone'] = $this->data['settings_company']['company_telephone'];
				$this->data['fields']['branch']['org_nr'] = $this->data['settings_company']['company_org_nr']; 
				 
			}
			
			//get employe details who taken that order
			$employee = '';
			if($order_from == 'shop'){
				
				$this->data['reg_fields'][] = 'employee';
				$this->data['fields']['employee'] =  $this->employee_model->getEmployeeDetail($orderinfo['employee']);
				
            	$employee = '<li style="margin:0; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 28px; font-weight: normal; color: #000; list-style: none;">Kasserer: [employee.initial]</li>';
			
			}
			$this->data['lists']['employee'] =$employee;	
			
			
			//get orderline
			$orderdetails = $this->orders_model->getOrderLine($order_id);
			
			//print_r($orderdetails);
			
			
			
			$str ='';
			
			$delsum = 0;
			$orderlinedelivery=array();
			$orderlinedelivery1=array();
			$orderlinedelivery2=array();
			$orderlinedelivery3=array();
			$checkdeliverydate=array();
			$categoryorderline=array();
			$weekdayarray=array("Mon"=>"Man","Tue"=>"Tirs","Wed"=>"Ons","Thu"=>"Tors","Fri"=>"Fre","Sat"=>"lør","Sun"=>"Søn");	
			
			for($i=0;$i< count($orderdetails);$i++)
			{
				$html='';
				$arr = $this->orders_model->getProductDisplayName($orderdetails[$i]['product']);
				
				$orderdetails[$i]['name'] = $arr['name'];
				
				
				//echo '<pre>';print_r($orderdetails[$i]['p_b_delivery_time']);exit;
				if($orderdetails[$i]['p_b_delivery_time'] != '' && $orderdetails[$i]['p_b_delivery_time'] != '0000-00-00')
				{
					$b_delivery_time=date('Y-m-d',strtotime($orderdetails[$i]['p_b_delivery_time']));
					$p_b_delivery_time = date('d/m/Y',strtotime($orderdetails[$i]['p_b_delivery_time']));
					$day=date('D',strtotime($orderdetails[$i]['p_b_delivery_time']));
					$checkdeliverydate[$b_delivery_time]= strtolower($weekdayarray[$day]).' '.$p_b_delivery_time;
					$orderlinedelivery[]= '<span class="pname"> '.$orderdetails[$i]['name'].'</span> '.strtolower($weekdayarray[$day]).' '.$p_b_delivery_time;
					$newtime=strtotime($orderdetails[$i]['p_b_delivery_time']);
					$orderlinedelivery1[$newtime][]= '<span class="pname"> '.$orderdetails[$i]['name'].'</span> ';
					$orderlinedelivery2[$newtime][]= strtolower($weekdayarray[$day]);
					$orderlinedelivery3[$newtime][]= $p_b_delivery_time;
				}
	
				$quantity = $orderdetails[$i]['quantity'] = ($orderdetails[$i]['changed_quantity']!='') ?  $orderdetails[$i]['changed_quantity'] : $orderdetails[$i]['quantity'];
				
				
				if (round($quantity, 0) == $quantity)
				{
					// is whole number
					$quantity = round($quantity, 0);
				}					
				
				$amount = ($orderdetails[$i]['changed_amount']!='') ?  $orderdetails[$i]['changed_amount'] : $orderdetails[$i]['amount'];

				$total_price = $amount ;
				
				
				$delsum =  $delsum + $total_price;
				
				$productPrice=$total_price;
				
				$discount = $this->products_model->getProDiscount($orderdetails[$i]['product']);
				 $discount=$discount[0];
				 $ddesc='&nbsp;';
				 if(isset($discount['description']))
				 {
					 $ddesc='('.$discount['description'].')';
				 }
				 $products=$orderdetails[$i];
				 
				
				// $productPrice=round($productPrice);
				 $subtotalarray[]=$productPrice;
								 
				
				$html .='<tr>
                <td nowrap="nowrap" style="text-align: left;width:18%; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:bold;  vertical-align: top;">'.$quantity.'</td>
                <td style="text-align: left; padding: 5px 0 0;width:55%; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">'.$orderdetails[$i]['name'];
				
				  $boolean = true ;
				
				  if(($orderdetails[$i]['in_meter'] == 1) && ($boolean))
				  {
					$meter_text =1;
					$boolean = false;
				  }
					
				
				if($orderdetails[$i]['special_instruction']!=''){
					$html .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;font-size: 30px; line-height:32px"><b>Fritekst :</b><br>'.$orderdetails[$i]['special_instruction'].'</p>';
				}
				
				if($orderdetails[$i]['complain']==1){
					$html .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;font-size: 30px; line-height:32px"><b>Reklamasjon</b></p>';
				}
				
				if($orderdetails[$i]['in_house']==1){
					$html .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;font-size: 30px; line-height:32px"><b>Renses på huset</b></p>';
				}
				

			    $vary = ($orderdetails[$i]['in_meter'] == 1) ? "*" : '' ;
				
				
				$html .='</td>
                <td nowrap="nowrap" style="text-align: right;width:25%; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top;">kr '.formatcurrency($productPrice).$vary.'</td>
              </tr>';
			  $str.=$html;
			  $categoryorderline[$orderdetails[$i]['category']][$i]['item']=$html;
			  
			  
			  $ferdig = $orderlinedelivery[$i].'<div class="separator" style="border-top: #000 dashed 1px;margin: 5px 0;height:1px"></div>';
				
			 $categoryorderline[$orderdetails[$i]['category']][$i]['delivery']=$ferdig;
			  
			 $additionalproductcount = $this->products_model->additionalProductCount($orderdetails[$i]['product']);
			 
			 $antall = ($additionalproductcount > 0) ? ($additionalproductcount *  $quantity) : $quantity;
			 
			 $categoryorderline[$orderdetails[$i]['category']][$i]['antall']= $antall;
			 
			}
			
			
			
			
			$customerid = $customer['customerid'];
			$zone = $customer['zone'];
			$delivery_type = 'normal'; //default
		
			
			$subtotal=array_sum($subtotalarray);
			$cus_sub_total=$subtotal;
			
			$this->data['reg_fields'][] = 'delivery';
			$this->data['fields']['delivery'] =$this->general_model->checkMinimumAmount($delivery_type,$customerid,$subtotal,$zone);
			$min_price=$this->data['fields']['delivery']['min_price'];
			$min_price_txt = '';
			
			if($orderinfo['type'] != 'shop')
			{
				if($cus_sub_total < $min_price)
				{
					$min_price_txt =  ' (Minste beløp kr '.formatcurrency($min_price).')';
				}
				
				$subtotal = ($subtotal < $min_price) ?  $min_price : $subtotal;
				$delsum=$subtotal;
				$this->data['debug'][] = $this->general_model->debug_data;	
				$delviery = ($subtotal >= $this->data['fields']['delivery']['free_delivery_after']) ?  '0' : $this->data['fields']['delivery']['delivery_charge'];
				$old_delivery_charge=$this->data['fields']['delivery']['delivery_charge'];
				
			}
			

				$summery='<tr>
				 <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Delsum'.$min_price_txt.'</td><td nowrap="nowrap" style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal; border-top:#000 dashed 1px;">kr '.formatcurrency($subtotal).'</td>
				  </tr>';
			
		
		$discount=0;

		if(intval($orderinfo['voucher']) > 0)
		{
			$qry="SELECT * FROM a_voucher WHERE id='".$orderinfo['voucher']."'";
			$query = $this->db->query($qry);
			$vdata = $query->row_array();//multi row array
			
			$vouchercode=$vdata['voucher'];
			
			if($vdata['percentage'] != '')
			{
				$percentage=$vdata['percentage']/100;
				$discount=$subtotal*$percentage;
				
				
			}
			else
			{
				$discount=$vdata['price'];
			}
			if($vdata['free_delivery_charge'] == 1)
			{
				$delviery=0;
			}
			else
			{
				$delviery=$old_delivery_charge;
			}
			
			
			$discount=$discount+0;
				
		}		
		else{
			//if the discount in % or kr
			if($orderinfo['order_discount'] !=''){
				$vouchercode = $orderinfo['order_discount'];
				if(stripos($vouchercode, '%')){
					$percentage = str_replace("%","",$vouchercode);
					$discount =  $delsum * ($percentage/100);
				}
				else{
					
					$discount = str_replace("kr ","",$vouchercode);
					$vouchercode = "kr ".formatcurrency($discount);
				}
				
			}

		}
		if($discount > 0){
			
			$summery.='<tr>
             <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Discount ('.$vouchercode.')</td><td nowrap="nowrap" style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal; border-top:#000 dashed 0px;">kr '.formatcurrency($discount).'</td>
              </tr>';
			
		}
		
		  
				
			$fprice=$cus_sub_total-$discount;
		
			if($orderinfo['type'] != 'shop')
			{
				if($fprice < $min_price)
				{
					
					$summery.='<tr>
					 <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Minste beløp</td><td  nowrap="nowrap" style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal; border-top:#000 dashed 0px;">kr '.formatcurrency($min_price).'</td>
					  </tr>';
					  
				}
			
			}
		
		
		
		$price=$subtotal-$discount;
		$frakt=$delviery;
		
			if($orderinfo['type'] != 'shop')
			{
				$summery.='<tr>
				 <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Levering</td><td   nowrap="nowrap" style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal; border-top:#000 dashed 0px;">kr '.formatcurrency($delviery).'</td>
				</tr>';
			}  
			  //mva
				$mva=$this->data['fields']['order']['totalt']/1.25;	
				
				$mva=round($mva, 2);
				
				$mva=$this->data['fields']['order']['totalt']-$mva;
				$this->data['lists']['mva'] =$mva;	
			
			  
			  $summery.='<tr>
                <td colspan="2" style="text-align: right; padding:0px; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Herav 25% MVA</td>
                <td style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;">kr '.formatcurrency($mva).'</td>
              </tr>
              <tr>
                <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Totalt</td>
                <td style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal; border-top:#000 dashed 1px; border-bottom:#000 dashed 1px; ">kr '.formatcurrency($this->data['fields']['order']['totalt']).'</td>
              </tr>';
			  
			  if($saldo_amount != '' && $saldo_amount != '0,00')
			  {
				  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Kassakredit saldo: kr '.$saldo_amount.'</td></tr>';
			  }
			
			   
			   if($meter_text == 1){
			   $summery.=' <tr>
              <td colspan="3" valign="top" align="left" style="text-align: left; padding: 10px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;"><i>* Prisen blir kalkulert når ferdig</i></td>
              </tr>';
			   }
			  
			  
			 
			$this->data['lists']['summery'] = $summery;
			$this->data['lists']['orderline'] = $str;
			$this->data['lists']['delsum'] = $delsum;
			//$this->data['lists']['delivery_note'] = $this->data['fields']['order']['delivery_note'];
			
			
			
			$this->data['lists']['delivery_note']='';
			
			
			if($this->data['fields']['order']['delivery_note'] != '')
			{
				 $this->data['lists']['delivery_note'] = '<tr>
				  <td valign="top" colspan="3" align="left" style="margin:0; padding:0; font-family: \'arial\', monospace; font-size: 30px; font-weight: bold;  color: #000">Notater</td>
				</tr>
				<tr>
				  <td colspan="3" valign="top" align="left" style="text-align: left; padding: 10; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;">'.$this->data['fields']['order']['delivery_note'].'</td>
				</tr>';
			}
			
			$this->data['lists']['special_instruction']='';
			
			
			if($this->data['fields']['order']['special_instruction'] != '')
			{
				 $this->data['lists']['special_instruction'] = '<tr>
				  <td valign="top" colspan="3" align="left" style="margin:0; padding:0; font-family: \'arial\', monospace; font-size: 30px; font-weight: bold;  color: #000">Spesialinstruksjoner</td>
				</tr>
				<tr>
				  <td colspan="3" valign="top" align="left" style="text-align: left; padding: 10; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;">'.$this->data['fields']['order']['special_instruction'].'</td>
				</tr>';
			}
			
			
			
			$this->data['lists']['orderlinedelivery']='';
			if(count($checkdeliverydate) == 1)
			{
				$delivery_dates=implode(',',$checkdeliverydate);
				
				$this->data['lists']['orderlinedelivery'] = '<tr>
				  <td colspan="3" style="margin:0px 0; padding:10px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight: bold;  color: #000" valign="top" align="left">Ferdig: '.$delivery_dates.'</td>
				</tr>';
			}
			else
			{
			
			//echo '<pre>';print_r($orderlinedelivery2);exit;
			$orderlinedelivery_time='';
				if(count($orderlinedelivery) > 0)
				{
				
					if(count($orderlinedelivery1) > 0)
					{
						foreach($orderlinedelivery1 as $dkey=>$delivery1)
						{
									//echo '<pre>';print_r($delivery1);exit;
							
							//$orderlinedelivery[]= '<span class="pname"> '.$orderdetails[$i]['name'].'</span> '.strtolower($weekdayarray[$day]).' '.$p_b_delivery_time;
							$deliveryitems=implode('<br />',$delivery1);
							$deliveryitems.=$orderlinedelivery2[$dkey][0];
							$deliveryitems.=' '.$orderlinedelivery3[$dkey][0];
							
							$orderlinedelivery_time.=$deliveryitems;
							$orderlinedelivery_time.='<br>';
							
							//$orderlinedelivery_time.=implode('<div class="separator" style="border-top: #000 dashed 1px;margin: 5px 0;height:1px"></div>',$delivery1);
						//	$orderlinedelivery_time.='<br>';
						}
					
					}
					//$orderlinedelivery_time=implode('<div class="separator" style="border-top: #000 dashed 1px;margin: 5px 0;height:1px"></div>',$orderlinedelivery);
					
					$this->data['lists']['orderlinedelivery'] = '<tr>
				  <td colspan="3" style="margin:0px 0; padding:10px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight: bold;  color: #000" valign="top" align="left">Ferdig: </td>
				</tr><tr>
					<td valign="top" colspan="3" align="left" style="margin:0px 0; padding:10px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight: bold;  color: #000">'.$orderlinedelivery_time.'</td>
					</tr>';
				}
			}
			
			
			
			$commonheader='<table  width="100%" align="center" border="0" cellpadding="0" cellspacing="0" > 
    <tbody>
      <tr>
        <th valign="top" align="center" style="padding:0 0px 30px 0;"><ul style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 15px; font-weight: normal;text-align:center;">
            <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 32px; font-weight: normal; color: #000; list-style: none;"><strong>'.$this->data['fields']['branch']['company'].'</strong></li>
            <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal; color: #000; list-style: none;">'.$this->data['fields']['branch']['street'].'</li>
            <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal; color: #000; list-style: none;">'.$this->data['fields']['branch']['zip'].'</li>
            <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal; color: #000; list-style: none;">Telefon:+47 '.$this->data['fields']['branch']['phone'].'</li>
            <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal; color: #000; list-style: none;">Org.nr: '.$this->data['fields']['branch']['org_nr'].'</li>

          </ul></th>
      </tr>
      <tr>
        <td width="50%" valign="top" align="center" style="padding:0 0px 30px 0;"><ul style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal">
        <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size:32px; font-weight: normal; color: #000; list-style: none;"><img src="'.base_url().'admin/barcode/[order.id]" alt="Barcode" style="width:300px;height:60px;"></li>
        <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size:50px; font-weight: normal; color: #000; list-style: none;">[order.id]</li>
		<li style="margin:0; padding: 0; font-family: \'arial\', monospace;font-weight: normal; color: #000; list-style: none;">&nbsp;</li>

         </ul></td>
      </tr>
      <tr>
			<td valign="top" align="left" style="padding:0 0 30px 0;"><ul style="margin:0; padding:0 0 0 2px; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal">
            <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal; color: #000; list-style: none;">+47[customer.number]</li>
            <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 34px; font-weight: bold; color: #000; list-style: none; text-transform:uppercase">[customer.customer_name]</li>
            <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 28px; font-weight: normal; color: #000; list-style: none;">[customer.address]</li>
            <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 28px; font-weight: normal; color: #000; list-style: none;">[customer.zip] [customer.city]</li>
               '.$this->data['lists']['employee'].'
            <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 28px; font-weight: normal; color: #000; list-style: none;">[order.order_time]</li>
           </ul>
		   </td>
      </tr>
      <tr>
        <td  valign="top" align="center"><table width="100%" align="center" border="0" cellpadding="0" cellspacing="0">
            <thead>
              <tr>
                <th style="text-align: left; width:18%; border-top:#000 dashed 1px; border-bottom:#000 dashed 1px; padding: 10px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Ant.</th>
                <th style="text-align: left; width:70%; border-top:#000 dashed 1px; border-bottom:#000 dashed 1px; padding: 10px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Artikler</th>
                <th style="text-align: right; width:20%; border-top:#000 dashed 1px; border-bottom:#000 dashed 1px; padding: 10px 0; font-family: \'arial\', monospace; font-size: 30px; width:20%; font-weight:normal ">Beløp</th>
				<th style="text-align: right; width:5%;">&nbsp;</th>
              </tr>
            </thead>
            <tbody>';
			
			
	$commonfooter='
        <tr>
              <td colspan="4" valign="top" align="center" style="text-align: center; padding: 0 0 20px; font-family: \'arial\', monospace; font-size: 50px; font-weight:normal;">Intern kopi</td>
            </tr>
    </tbody>
  </table>
';

		
		$categoryprint='';
		//echo '<pre>hi';print_r($orderdetails);exit;
		
		//print_r($categoryorderline);
		
		if(count($orderdetails) > 0){
            if(count($categoryorderline) > 0)
			{
				foreach($categoryorderline as $catkey=>$catitems)
				{
					$antall = 0;
					if(count($catitems) > 0)
					{
					
						$categoryprint.='<div class="page-break"></div>';
						$categoryprint.='<div class="print" id="cat'.strtolower($catkey).'">'.$commonheader;
						$catout='';
						$catdelivery = '';
						foreach($catitems as $key=>$cathtml)
						{
							$catout.= $catitems[$key]['item'];
							$catdelivery .= $catitems[$key]['delivery'];
							$antall += $catitems[$key]['antall'];
						}
					
						$categoryprint.=$catout;
						
					$delivery_date = '<tr>
              <td nowrap="nowrap" style="text-align: center; padding: 5px 0; font-family: \'arial\', monospace; font-size: 15px; font-weight:normal;  vertical-align: top;">&nbsp;</td>
            </tr>
			<tr>
				  <td colspan="3" style="margin:0px 0; padding:10px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight: bold;  color: #000" valign="top" align="left">Ferdig: </td>
				</tr><tr>
					<td valign="top" colspan="3" align="left" style="margin:0px 0; padding:0px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight: bold;  color: #000">'.$catdelivery.'</td>
					</tr>
					<tr>
              <td colspan="3" nowrap="nowrap" align="center" style="padding: 5px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:bold;  vertical-align: top;">Antall '.$antall.'</td>
            </tr>';
			
						$categoryprint.= $delivery_date.$commonfooter;
						$categoryprint.='</div>';
					}
				
				}
				
			}
			
		}
               
             

			$this->data['lists']['categoryprint']=$categoryprint;
			
			
			/* <tr>
              <td colspan="3" valign="top" align="center" style="text-align: center; padding: 30px; font-family: 'arial', monospace; font-size: 28px; font-weight:normal;">[ Intern kopi ] <br> [ Kundens kvittering ]<br> [ Ekstra kopi (3) ]</td>
            </tr>*/
			
			
			$this->data['fields']['order']['otime'] = date('d.m.Y H:i:s',strtotime($this->data['fields']['order']['order_time']));
			
			

   }


		/*print order receipt with heat seal*/
		function __printReceiptWithHeatSeal()
		{
			
			//profiling
			$this->data['controller_profiling'][] = __function__;
			
			//$order_id = 20154586;
			$order_id = $this->uri->segment(4);
			
			//print_r($this->session);
			
			//get customer details
			$this->data['reg_fields'][] = 'customer';
			$this->data['fields']['customer'] = $customer = $this->orders_model->getCustomerDetails($order_id);
			$saldo_amount='';
			$data = $this->payments_model->getAccountBalance($customer['customer']);
			$pendingsaldo = $data['pending'];
			$paidsaldo = $data['paid'];
				
			if(intval($pendingsaldo) > 0)
			{	
				$saldo_amount= formatcurrency($paidsaldo).' ('.formatcurrency($pendingsaldo).')';
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
			
			
			//get order details
			$this->data['reg_fields'][] = 'order';
			$this->data['fields']['order'] = $orderinfo = $this->orders_model->getOrderinfo($order_id);
			
			
			
			$this->data['fields']['order']['order_time'] = date('d.m.Y H:i:s',strtotime($this->data['fields']['order']['order_time']));
			
			$order_status = $orderinfo['order_status'];
			
			$this->data['fields']['order']['totalt'] = ($orderinfo['changed_amount']!='') ?  $orderinfo['changed_amount'] : $orderinfo['total_amount'];

				
			//get shop detail where order placed ( from SL or branch)
			$order_from = $orderinfo['type'];
			$this->data['reg_fields'][] = 'branch';
			if($order_from == 'shop'){
				
				$partnerinfo = $this->general_model->getPartnerDetails($orderinfo['partner'],$orderinfo['partner_branch']);
				
				$this->data['fields']['branch']['company'] = $partnerinfo['name'];
				$this->data['fields']['branch']['street'] = $partnerinfo['street'] ;
				$this->data['fields']['branch']['zip'] =  $partnerinfo['zip']. ' '.$partnerinfo['city'];
				$this->data['fields']['branch']['phone'] = $partnerinfo['phone']; 
				$this->data['fields']['branch']['org_nr'] = $partnerinfo['org_nr']; 
	
			}
			else{
				$this->data['fields']['branch']['company'] = $this->data['settings_company']['company_name'];
				$this->data['fields']['branch']['street'] = $this->data['settings_company']['company_address_street'] .", ". $this->data['settings_company']['company_address_zip']. ' '.$this->data['settings_company']['company_address_city'];
				$this->data['fields']['branch']['phone'] = $this->data['settings_company']['company_telephone'];
				$this->data['fields']['branch']['org_nr'] = $this->data['settings_company']['company_org_nr']; 
				 
			}
			
			//get employe details who taken that order
			$employee = '';
			if($order_from == 'shop'){
				
				$this->data['reg_fields'][] = 'employee';
				$this->data['fields']['employee'] =  $this->employee_model->getEmployeeDetail($orderinfo['employee']);
				
            	$employee = '<li style="margin:0; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 28px; font-weight: normal; color: #000; list-style: none;">Kasserer: [employee.initial]</li>';
			
			}
			$this->data['lists']['employee'] =$employee;	
			
			
			//get orderline
			$orderdetails = $this->orders_model->getOrderLine($order_id);
			
		//	echo '<pre>';print_r($orderdetails);exit;
			
			$str ='';
			$delsum = 0;
			$orderlinedelivery=array();
			$checkdeliverydate=array();
			$weekdayarray=array("Mon"=>"Man","Tue"=>"Tirs","Wed"=>"Ons","Thu"=>"Tors","Fri"=>"Fre","Sat"=>"lør","Sun"=>"Søn");	
			for($i=0;$i< count($orderdetails);$i++)
			{
				
				$arr = $this->orders_model->getProductDisplayName($orderdetails[$i]['product']);
				
				$orderdetails[$i]['name'] = $arr['name'];

				//echo '<pre>';print_r($orderdetails[$i]['product']);exit;
				if($orderdetails[$i]['p_b_delivery_time'] != '' && $orderdetails[$i]['p_b_delivery_time'] != '0000-00-00')
				{
					$b_delivery_time=date('Y-m-d',strtotime($orderdetails[$i]['p_b_delivery_time']));
					$p_b_delivery_time = date('d/m/Y',strtotime($orderdetails[$i]['p_b_delivery_time']));
					$day=date('D',strtotime($orderdetails[$i]['p_b_delivery_time']));
					$checkdeliverydate[$b_delivery_time]= strtolower($weekdayarray[$day]).' '.$p_b_delivery_time;
					$orderlinedelivery[]= '<span class="pname"> '.$orderdetails[$i]['name'].'</span> '.strtolower($weekdayarray[$day]).' '.$p_b_delivery_time;
				}
	
				$quantity = $orderdetails[$i]['quantity'] = ($orderdetails[$i]['changed_quantity']!='') ?  $orderdetails[$i]['changed_quantity'] : $orderdetails[$i]['quantity'];
				
				if (round($quantity, 0) == $quantity)
				{
					// is whole number
					$quantity = round($quantity, 0);
				}					
				
				
				$amount = ($orderdetails[$i]['changed_amount']!='') ?  $orderdetails[$i]['changed_amount'] : $orderdetails[$i]['amount'];

				$total_price = $amount ;

				$delsum =  $delsum + $total_price;
				
				$productPrice=$total_price;
				
				$discount = $this->products_model->getProDiscount($orderdetails[$i]['product']);
				 $discount=$discount[0];
				 $ddesc='&nbsp;';
				 if(isset($discount['description']))
				 {
					 $ddesc='('.$discount['description'].')';
				 }
				 $products=$orderdetails[$i];
				
				 $productPrice=round($productPrice);
				 $subtotalarray[]=$productPrice;
								 
				
				
			  $vary = ($orderdetails[$i]['in_meter'] == 1) ? "*" : '' ;
			
			  $barcodes = $this->orders_model->orderlineHeatseal($orderdetails[$i]['id']);
			  
			 // echo '<pre>';print_r($barcodes);exit;

				  $str.='<tr>
					<td nowrap="nowrap" style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:bold;  vertical-align: top;">'.$orderdetails[$i]['name'].' ('.$quantity.')</td>
					<td style="text-align: center; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; "> </td>
					<td nowrap="nowrap" style="text-align: right; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top;font-weight:bold;">kr '.formatcurrency($amount).$vary.'</td>
				  </tr>
				  <tr><td colspan="3" style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">';
				  
				  $boolean = true ;
	
				  if(($orderdetails[$i]['in_meter'] == 1) && ($boolean))
				  {
					$meter_text =1;
					$boolean = false;
				  }
				  
				  
					if($orderdetails[$i]['special_instruction']!=''){
						$str .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;line-height:32px"><b>Fritekst :</b><br>'.$orderdetails[$i]['special_instruction'].'</p>';
					}
					
					if($orderdetails[$i]['complain']==1){
						$str .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;line-height:32px"><b>Reklamasjon</b></p>';
					}
					
					if($orderdetails[$i]['in_house']==1){
						$str .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;line-height:32px"><b>Renses på huset</b></p>';
					}
				  
				  
					$str.='</td></tr>';
					
			  
			  // echo '<pre>';print_r($orderdetails[$i]);
			// echo '<pre>';print_r($barcodes);exit;
			$prodtype=$this->process_order_model->validateProducttype($orderdetails[$i]['product']);
				  
			  if($orderdetails[$i]['in_meter'] == 1)
			  {
				$actualqty=1;
			  }
			  else
			  {
				$actualqty=$prodtype*$orderdetails[$i]['quantity'];
			  }
			 
			 
			 
			 $rqty=0;
			 if($actualqty > count($barcodes))
			 {
				$rqty=$actualqty-count($barcodes);
			 }
			 
			 
			$additionalproductcount=$this->products_model->additionalProductCount($orderdetails[$i]['product']);
	
			 
			// $orderdetails[$i]['product']
			 
			 
		// echo '<pre>';print_r($barcodes);exit;
			
			 
			 $amtstatus=0;
			 $count=0;
			  foreach($barcodes as $baritems)
			  {
			  
			
					if($additionalproductcount == $count)
					{
						$count=0;
					}
			   
			   
					$name=$orderdetails[$i]['name'];
					if(intval($baritems['additional_product']) > 0)
					{	
						$query=$this->db->query("SELECT a_additional_product.name FROM a_product_additional_product
						LEFT JOIN a_additional_product ON a_additional_product.id=a_product_additional_product.additional_product
						WHERE a_product_additional_product.id='".$baritems['additional_product']."'");		
						if($query->num_rows() > 0)		
						{				
							$result=$query->row_array();
							
							$name=$result['name'];
							
							  $count++;
						}
					}
				
				$oramount=($baritems['changed_amount'] != '') ? $baritems['changed_amount']:$baritems['amount'];
				
				
				//$oramount=$orderdetails[$i]['price'];
				
				$amtstar='';
				if(intval($oramount) == 0)
				{
					$amtstar='*';
				}
				
				if($baritems['barcode']!=''){
				$str.='<tr>
                <td style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">'.$name.' <br>HS #'.$baritems['barcode'].'</td>';
				if($baritems['status'] == 'canceled')
				{
					$str.='<td style="text-align: center; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">0</td>';
				}
				else
				{
					$str.='<td style="text-align: center; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">1 </td>';
				}
                 
							   
					//if($amtstatus == 0)
					if($count == 1 || $count == 0)
					{
						 //$str.='<td nowrap="nowrap" style="text-align: right; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 28px; font-weight:normal;vertical-align: top;">kr '.formatcurrency($oramount).'</td>';
						 
						 $str.='<td nowrap="nowrap" style="text-align: right; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top;"></td>';
						 
						 
					}
					else
					{
						 $str.='<td nowrap="nowrap" style="text-align: right; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top;"></td>';
					}					
              
			   
			   
				  $str.='</tr>';
			  
					if(intval($baritems['additional_product']) > 0)
					{
						$amtstatus=1;
					}
			  
				}
			  
			  }
			  
			 
			  if($rqty > 0)
			  {
			 
					$rrqty=count($barcodes)+1;
			  
					foreach(range($rrqty,$actualqty) as $qtyitems)
					{
					
						if($baritems['barcode']!=''){
							$str.='<tr>
					<td style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">'.$orderdetails[$i]['name'].' <br>HS # 
								   <td style="text-align: center; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">1 </td>
					<td nowrap="nowrap" style="text-align: right; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top;"></td>
				  </tr>';
						}
					
					}
			  }
			  
			 
			  
			}
			
			
			
			$customerid = $customer['customerid'];
			$zone = $customer['zone'];
			$delivery_type = 'normal'; //default
		
			
			$subtotal=array_sum($subtotalarray);
			$cus_sub_total=$subtotal;
			
			$this->data['reg_fields'][] = 'delivery';
			$this->data['fields']['delivery'] =$this->general_model->checkMinimumAmount($delivery_type,$customerid,$subtotal,$zone);
			$min_price=$this->data['fields']['delivery']['min_price'];
			$min_price_txt = '';
			
			if($orderinfo['type'] != 'shop')
			{
				if($cus_sub_total < $min_price)
				{
					$min_price_txt =  ' (Minste beløp kr '.formatcurrency($min_price).')';
				}
				
				$subtotal = ($subtotal < $min_price) ?  $min_price : $subtotal;
				$delsum=$subtotal;
				$this->data['debug'][] = $this->general_model->debug_data;	
				$delviery = ($subtotal >= $this->data['fields']['delivery']['free_delivery_after']) ?  '0' : $this->data['fields']['delivery']['delivery_charge'];
				$old_delivery_charge=$this->data['fields']['delivery']['delivery_charge'];
				
			}
			

				$summery='<tr>
				 <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Delsum'.$min_price_txt.'</td><td nowrap="nowrap" style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal; border-top:#000 dashed 1px;">kr '.formatcurrency($subtotal).'</td>
				  </tr>';
			
		
		//if the discount is a voucher
		$discount=0;
		if(intval($orderinfo['voucher']) > 0)
		{
			$qry="SELECT * FROM a_voucher WHERE id='".$orderinfo['voucher']."'";
			$query = $this->db->query($qry);
			$vdata = $query->row_array();//multi row array
			
			$vouchercode=$vdata['voucher'];
			
			if($vdata['percentage'] != '')
			{
				$percentage=$vdata['percentage']/100;
				$discount=$subtotal*$percentage;
				
			}
			else
			{
				$discount=$vdata['price'];
			}
			if($vdata['free_delivery_charge'] == 1)
			{
				$delviery=0;
			}
			else
			{
				$delviery=$old_delivery_charge;
			}
			
			
			$discount=$discount+0;
		}		
		else{
			//if the discount in % or kr
			if($orderinfo['order_discount'] !=''){
				$vouchercode = $orderinfo['order_discount'];
				if(stripos($vouchercode, '%')){
					$percentage = str_replace("%","",$vouchercode);
					$discount =  $delsum * ($percentage/100);
				}
				else{
					
					$discount = str_replace("kr ","",$vouchercode);
					$vouchercode = "kr ".formatcurrency($discount);
				}
				
			}

		}
			
		 if($discount > 0){	
			$summery.='<tr>
             <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Discount ('.$vouchercode.')</td><td nowrap="nowrap" style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal; border-top:#000 dashed 0px;">kr '.formatcurrency($discount).'</td>
              </tr>';
		 }
			
			
			$fprice=$cus_sub_total-$discount;
		
			if($orderinfo['type'] != 'shop')
			{
				if($fprice < $min_price)
				{
					
					$summery.='<tr>
					 <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Minste beløp</td><td  nowrap="nowrap" style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal; border-top:#000 dashed 0px;">kr '.formatcurrency($min_price).'</td>
					  </tr>';
					  
				}
			
			}
		
		
		
		$price=$subtotal-$discount;
		$frakt=$delviery;
		
			if($orderinfo['type'] != 'shop')
			{
				$summery.='<tr>
				 <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Levering</td><td   nowrap="nowrap" style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal; border-top:#000 dashed 0px;">kr '.formatcurrency($delviery).'</td>
				</tr>';
			}  
			  //mva
				$mva=$this->data['fields']['order']['totalt']/1.25;	
				
				$mva=round($mva, 2);
				
				$mva=$this->data['fields']['order']['totalt']-$mva;
				$this->data['lists']['mva'] =$mva;	
			
			  
			  $summery.='<tr>
                <td colspan="2" style="text-align: right; padding:0px; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Herav 25% MVA</td>
                <td style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;">kr '.formatcurrency($mva).'</td>
              </tr>
              <tr>
                <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Totalt</td>
                <td style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal; border-top:#000 dashed 1px; border-bottom:#000 dashed 1px; ">kr '.formatcurrency($this->data['fields']['order']['totalt']).'</td>
              </tr>';
			  if($saldo_amount != '' && $saldo_amount != '0,00')
			  {
				  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Kassakredit saldo: kr '.$saldo_amount.'</td></tr>';
			  }
			  
			
			   if($meter_text == 1){
			   $summery.=' <tr>
              <td colspan="3" valign="top" align="left" style="text-align: left; padding: 10px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;"><i>* Prisen blir kalkulert når ferdig</i></td>
              </tr>';
			   }
			 
			$this->data['lists']['summery'] = $summery;
			$this->data['lists']['orderline'] = $str;
			$this->data['lists']['delsum'] = $delsum;
			
			$this->data['lists']['delivery_note']='';
			if($this->data['fields']['order']['delivery_note'] != '')
			{
				 $this->data['lists']['delivery_note'] = '<tr>
				  <td valign="top" colspan="3" align="left" style="margin:0; padding:0; font-family: \'arial\', monospace; font-size: 30px; font-weight: bold;  color: #000">Notater</td>
				</tr>
				<tr>
				  <td colspan="3" valign="top" align="left" style="text-align: left; padding: 10; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;">'.$this->data['fields']['order']['delivery_note'].'</td>
				</tr>';
			}
			
			
			
			$this->data['lists']['special_instruction']='';
			
			
			if($this->data['fields']['order']['special_instruction'] != '')
			{
				 $this->data['lists']['special_instruction'] = '<tr>
				  <td valign="top" colspan="3" align="left" style="margin:0; padding:0; font-family: \'arial\', monospace; font-size: 30px; font-weight: bold;  color: #000">Spesialinstruksjoner</td>
				</tr>
				<tr>
				  <td colspan="3" valign="top" align="left" style="text-align: left; padding: 10; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;">'.$this->data['fields']['order']['special_instruction'].'</td>
				</tr>';
			}
			
			
			$this->data['lists']['orderlinedelivery']='';
			if(count($checkdeliverydate) == 1)
			{
				$delivery_dates=implode(',',$checkdeliverydate);
				
				$this->data['lists']['orderlinedelivery'] = '<tr>
				  <td colspan="3" style="margin:0px 0; padding:10px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight: bold;  color: #000" valign="top" align="left">Ferdig: '.$delivery_dates.'</td>
				</tr>';
			}
			else
			{
				if(count($orderlinedelivery) > 0)
				{
					$orderlinedelivery_time=implode('<div class="separator" style="border-top: #000 dashed 1px;margin: 5px 0;"></div>',$orderlinedelivery);
					
					$this->data['lists']['orderlinedelivery'] = '<tr>
				  <td colspan="3" style="margin:0px 0; padding:10px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight: bold;  color: #000" valign="top" align="left">Ferdig: </td>
				</tr><tr>
					<td valign="top" colspan="3" align="left" style="margin:0px 0; padding:10px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight: bold;  color: #000">'.$orderlinedelivery_time.'</td>
					</tr>';
				}
			}
			

	    }


		
	
	function __removeOrderline()
	{
		if(count($_POST) > 0)
		{
			$orderlineid=$_POST['orderline'];
			$orderline=$this->orders_model->removeOrderline($orderlineid);
			if($orderline)
			{
				$result = array("status"=>'success',"message"=>"Product has been removed successfully.");
			}
			else
			{
				$result = array("status"=>'error',"message"=>"Invalid");
			}
			
			echo json_encode($result);exit;
		}
	}
	
	/* Edit order line indvidual payment information*/
	function __editorderpayment()
	{
		

		//profiling
		$this->data['controller_profiling'][] = __function__;
		
		$order_id = $this->input->post('orderid');
		

        $customer = $this->orders_model->getCustomerDetails($order_id);
        
        $orderinfo = $this->orders_model->getOrderinfo($order_id);
		
		//echo '<pre>';print_r($orderinfo['type']);exit;
		
			$order_from=$orderinfo['type'];
			$employeeinfo=array();
			if($order_from == 'shop')
			{
				$employeeinfo = $this->employee_model->getEmployeeDetail($orderinfo['employee']);
				
			}
			
			
		$order_status = $orderinfo['order_status'];
		
		
		if($order_status == 3){
			$this->data['visible']['wi_show_process_button'] = 1;
		}
        
        //get shop detail where order placed ( from SL or branch)
        $order_from = $orderinfo['type'];
        
        if($order_from == 'shop'){
            
            $partnerinfo = $this->general_model->getPartnerDetails($orderinfo['partner'],$orderinfo['partner_branch']);
            
            $company = $partnerinfo['name'];
            $address = $partnerinfo['street'] .", ". $partnerinfo['zip']. ' '.$partnerinfo['city'];
            $phone = $partnerinfo['phone']; 
            

        }
        else{
            $company = $this->data['settings_company']['company_name'];
            $address = $this->data['settings_company']['company_address_street'] .", ". $this->data['settings_company']['company_address_zip']. ' '.$this->data['settings_company']['company_address_city'];
            $phone = $this->data['settings_company']['company_telephone']; 
        }
		
		
			$company_info ='';
			$orderdetails = $this->orders_model->getOrderLine($order_id);
		
		
			//Kasserer: '.$this->data['fields']['employee']['initial'].'
		
		
		
		$collectiontinfo=$this->orders_model->getCollectionDeliverytime('collection',$orderinfo['collection_time']);
		$deliveryinfo=$this->orders_model->getCollectionDeliverytime('delivery',$orderinfo['delivery_time']);
		
		
			$address=$customer['street_line_1'];
			if($customer['street_line_2'] != '')
			{
				$address.=', '.$customer['street_line_2'];
			}
			
			
		   $customer_detail='<div class="customer-detail row">
			   <div class="pull-left col-md-3 no-padd">
			   <p><span>Ordrenr: </span>#'.$orderinfo['id'].'</p>
			   <p><span>Telefon: </span>'.$customer['number'].'<br>
			   </div>
			    <div class="pull-left col-md-5 no-padd">
				<span>Navn: </span>'.$customer['customer_name'].'</br>
				<span>Adresse: </span>'.$address.', '.$customer['zip'].' '.$customer['city'].'';
				
				
			/*	if($customer['floor'] != '')
				{
			   $customer_detail.='<span>Etg: </span>'.$customer['floor'].' <br>';
			   }
			   if($customer['calling_bell'] != '')
				{
			   $customer_detail.='<span>RK: </span>'.$customer['calling_bell'].' <br>';
			   }*/
					$customer_detail.='</div><div class="pull-left col-md-4 no-padd">';
				if($collectiontinfo['sdate'] != '')
				{
					$customer_detail.='<span>Henting: </span>'.$collectiontinfo['sdate'].' &nbsp; '.$collectiontinfo['stime'].'-'.$collectiontinfo['etime'].'<br>';
				}
				else{
					
					$customer_detail.='<span>Innlevering: </span>'.date('d.m.Y H:i',strtotime($orderinfo['order_time'])).'<br>';

				}
				
				if(($order_from != 'shop')){
					$customer_detail .='<span>Levering: </span>'.$deliveryinfo['sdate'].'  &nbsp; '.$deliveryinfo['stime'].'-'.$deliveryinfo['etime'].'</p>';
				}
				
				if(count($employeeinfo) > 0){
					$customer_detail .='<span>Kasserer: </span>'.$employeeinfo['initial'].'</p>';
				}
				
				
				   
				   $str='</div>                
                  
                   <div class="clearfix"></div>
                 </div>
                   <hr>

<div  class="orderlist row">

<div class="pull-left col-md-1 no-padd text-center">

<input type="checkbox" onClick="paycheckall(this)" id="checkall"  />
</div>';

if(($order_from == 'shop')){

$str .='<div class="pull-left col-md-3">
<p><b>Artikler</b></p>
</div>

<div class="pull-left col-md-1 no-padd">
<p><b>Utlevering</b></p>
</div>';

}
else{
	
$str .='<div class="pull-left col-md-4">
<p><b>Artikler</b></p>
</div>';
}

$str .='<div class="pull-left col-md-2 no-padd text-center">
<p><b>Pris</b></p>
</div>
<div class="pull-left col-md-1 no-padd text-center">
<p><b>Antall</b></p>
</div>
<div class="pull-left col-md-1 no-padd text-center">
<p><b>Status</b></p>
</div>
<div class="pull-left col-md-1 no-padd text-center">
<p><b>Anullering</b></p>
</div>
<div class="pull-left col-md-2 text-right">
<p><b>Totalt</b></p>
</div>
</div>
 <hr>
	<div class="popupscroll"> ';   
        
			//get results and save for tbs block merging
			$data = $this->orders_model->getOrderLine($order_id);
			
			for($w=0;$w<count($data);$w++){
				$odlines[] = $data[$w]['id'];
			}
			//print_r($odlines);
			//get cancel orderline of an array
			$canceled_arr = $this->settings_order_model->getCancelOrderLine($order_id,$odlines);
			//print_r($canceled_arr);
		
			$canceled_arr_approved = $this->settings_order_model->getCancelOrderLine($order_id,$odlines,'approved');
			//print_r($canceled_arr_approved);
			
	$str.='<input type="hidden" name="totalorderline" id="totalorderline" value="'.count($data).'" />';
	
	$str.='<input type="hidden" name="checkedoderline" id="checkedoderline" value="" />';
	
	
			if(!empty($data)){
				$delsum = 0;
				$lstatus=1;
				$orderlineproduct=array();
				$orderlinedelivery=array();
				$paidamountarray=array();
				$paidstatus=1;
				$discountstatus=1;
				$pendingstatus=0;
				for($j=0;$j<count($data);$j++){
				
				$arr = $this->orders_model->getProductDisplayName($data[$j]['product']);
				
				$data[$j]['name'] = $arr['name'];


				$orderlineproduct[$data[$j]['product']]=$data[$j]['product'];
				
				if($data[$j]['p_b_delivery_time'] != '' && $data[$j]['p_b_delivery_time'] != '0000-00-00')
				{
					$p_b_delivery_time = date('d.m.Y',strtotime($data[$j]['p_b_delivery_time']));
					//$orderlinedelivery[]=$data[$j]['name'].' '.$p_b_delivery_time;
				}
			     else{
					 $p_b_delivery_time = '';
				 }
				
				//echo '<pre>';print_r($data[$j]);exit;
			//	echo $data[$j]['id'];exit;
					
					
					
					$sqty = $this->process_order_model->getHeatOrderLine($data[$j]['id']);
						$proqty=$data[$j]['qty'];
		
					if(trim($data[$j]['changed_quantity']) != '')
					{

						$data[$j]['quantity']=$data[$j]['changed_quantity'];
						
						/*if(intval($data[$j]['changed_quantity']) > 0)
						{
							$data[$j]['quantity']=$data[$j]['changed_quantity'];
						}
						else
						{
							continue;
						}*/
						
					}
					
					$discount = $this->products_model->getProDiscount($data[$j]['product']);
					 $discount=$discount[0];
					 $ddesc='&nbsp;';
					 if(isset($discount['description']))
					 {
						$ddesc=' ('.$discount['description'].')';
					 }
					
					$price =  '';
					if(trim($data[$j]['changed_amount']) != '')
					{
						//if(intval($data[$j]['changed_amount']) > 0)
						//{
							$price = $data[$j]['changed_amount'];
							$data[$j]['quantity'] = $data[$j]['changed_quantity'];
						//}
					}
					else
					{
						$price = $data[$j]['amount'];
					}
					
				
					$subtotal = $price;
					$productPrice=$subtotal;
					
					$productPrice=round($productPrice);
						
					
					
					$subtotalarray[]= $productPrice;
					
					//$subtotalarray[]= $subtotal;
					//$delsum += $data[$j]['price'] * $data[$j]['quantity'];
					$delsum=$productPrice;
					$path_parts = pathinfo($data[$j]['path']);
					$img = $path_parts['filename'] .".".$path_parts['extension']; 
				//	$total = ($delsum + $delivery_charge) - $discount ;
					if($data[$j]['quantity'] >= $sqty)
					{
						$newquantity=$data[$j]['quantity']-$sqty;
					}
					else
					{
						$newquantity=0;
					}
					
					$newquantity=$proqty*$newquantity;
					
					$cstatus = $pending = '';
					$disable = ' onclick="payitem(this);" name="corderlines[]"';
					if(in_array($data[$j]['id'], $canceled_arr)){
						$cstatus = 'orderdisabled';
						$disable = 'disabled checked name="corderlines"';
						if(in_array($data[$j]['id'], $canceled_arr_approved)){
							$pending = 'Canceled';
						}
						else {
							$pending = 'Pending';
						}
						//$pending = ($orderinfo['order_status']=='11') ? 'Canceled':'Pending';
						
					}
					
					$str.=' <div id="tr_'.$data[$j]['id'].'" class="orderlist order'.$data[$j]['payment_status'].' '. $cstatus.'">';
					$str .= '
					<div class="pull-left col-md-1 no-padd text-center">';
					
					if($data[$j]['payment_status'] == 'pending')
					{
					
						$deliverystatus = $this->payments_model->getDeliverystatus($order_id,$data[$j]['id']);
						if($deliverystatus)
						{
							$str.= '<input type="checkbox"  checked="checked"  disabled="disabled"  name="corderliness" id="corderlines_'.$data[$j]['id'].'"  value="" />';
					
							if(strtolower($data[$j]['payment_status']) == 'pending')
							{
								$waitamountarray[]=$productPrice;
								$paidstatus=0;
								$discountstatus=0;
							}
						}
						else
						{
							$paidstatus=0;
							$pendingstatus=1;
							$str.= '<input type="checkbox"   '.$disable.'  id="corderlines_'.$data[$j]['id'].'"  value="'.$data[$j]['id'].'" />';
						}
						
						
					}
					else
					{	
						$str.= '<input type="checkbox"  checked="checked"  disabled="disabled"  name="corderliness" id="corderlines_'.$data[$j]['id'].'"  value="" />';
					
						if(strtolower($data[$j]['payment_status']) == 'waiting')
						{
							$waitamountarray[]=$productPrice;
							$paidstatus=0;
							$discountstatus=0;
						}
						if(strtolower($data[$j]['payment_status']) == 'paid' || strtolower($data[$j]['payment_status']) == 'canceled')
						{
							$paidamountarray[]=$productPrice;
							$discountstatus=0;
						}
							
							
					}
					
					if(($order_from == 'shop')){
					
					   $str .= '</div>  <div class="pull-left col-md-3">
					   <p>'.$data[$j]['name'].'</p>
					   </div>  
					   
					   <div class="pull-left col-md-1 no-padd">
					   <p>'.$p_b_delivery_time.'</p>
					   </div> ';
					
					}
					else{
						
					   $str .= '</div>  <div class="pull-left col-md-2">
					   <p>'.$data[$j]['name'].'</p>
					   </div>';  
					}


				   
                   $str.= '<div class="pull-left col-md-2 no-padd text-center">
					 <p>kr '.formatcurrency($data[$j]['price']).'</p>
                   </div>
					<div class="pull-left col-md-1 no-padd text-center">
					<div class="quantity">';
					
					
					$arr1=array("min_quantity" => "1" ,"price" => $data[$j]['price']);
					$ddata = $this->products_model->getProDiscount($data[$j]['product']);
				
					if (round($data[$j]['quantity'], 0) == $data[$j]['quantity'])
					{
						// is whole number
						$data[$j]['quantity'] = round($data[$j]['quantity'], 0);
					}					

					
					$str.='<p>'.$data[$j]['quantity'].'</p>
					 
					</div>
					</div>	
					
					<div class="pull-left col-md-1  no-padd text-center">
				   <p>'.ucfirst($data[$j]['payment_status']).'</p>
                   </div>
					<div class="pull-left col-md-1 no-padd text-center">
						   <p>'.ucfirst($pending).'</p>
                   </div>

					
                   <div class="pull-left col-md-2  text-right">
       <p class="tprice" id="subtotal_'.$data[$j]['product'].'"> kr '.formatcurrency($productPrice).'</p>
	   
	   <input type="hidden" value="'.$productPrice.'" name="ordertotal['.$data[$j]['id'].']" id="oltotal_'.$data[$j]['id'].'" />
	   
	     <input type="hidden" value="'.$data[$j]['payment_status'].'" name="orderpayment['.$data[$j]['id'].']" />
                   </div>
                   <div class="clearfix"></div>
                   <hr>
                 </div>';
				
				 
				$forderlineid=$data[$j]['id'];
				 
                    $lstatus++;
				}
				
				
		$subtotal=array_sum($subtotalarray);
		$cus_sub_total=$subtotal;
		$customerid = $customer['customerid'];
		$zone = $customer['zone'];
		$delivery_type = 'normal'; //default
		$this->data['reg_fields'][] = 'delivery';
		$this->data['fields']['delivery'] =$this->general_model->checkMinimumAmount($delivery_type,$customerid,$subtotal,$zone);
		$min_price=$this->data['fields']['delivery']['min_price'];
		$min_price_txt = '';
		if($cus_sub_total < $min_price)
		{
			if($orderinfo['type'] != 'shop')
			{
				$min_price_txt =  ' (Minste beløp kr '.formatcurrency($min_price).')';
			}
			
		}
		
		if($orderinfo['type'] != 'shop')
		{
			$subtotal = ($subtotal < $min_price) ?  $min_price : $subtotal;
		}
		$delsum=$subtotal;
		$this->data['debug'][] = $this->general_model->debug_data;	
		$delviery = ($subtotal >= $this->data['fields']['delivery']['free_delivery_after']) ?  '0' : $this->data['fields']['delivery']['delivery_charge'];
		$old_delivery_charge = $this->data['fields']['delivery']['delivery_charge'];
		
		
		$newproducts= $this->products_model->getProduct();
		
		//echo '<pre>';print_r($newproducts);exit;
		$customer=$this->session->userdata['customer']['id'];
		$data = $this->payments_model->getAccountBalance($customer);
		$pendingsaldo = $data['pending'];
		$paidsaldo = $data['paid'];
		

			
		if(intval($pendingsaldo) > 0)
		{	
			$amount= formatcurrency($paidsaldo).' ('.formatcurrency($pendingsaldo).')';
			$saldo = $paidsaldo + $pendingsaldo;
		}
		else
		{
			$amount= formatcurrency($paidsaldo);
			$saldo = $paidsaldo;
		}
		
		$saldostatus=$this->payments_model->getSaldostatus($customer);	
		
		
			$summary='</div><div class="handlekurv">   
               <div class="col-md-8 no-padd">
		    <input type="hidden" id="pay_amt" name="pay_amt" value="0" />
			<input type="hidden" id="paying_amt" name="paying_amt" value="0" />
			<input type="hidden" id="saldo_amt" name="saldo_amt" value="'.$saldo.'" />
			<input type="hidden" value="'.$discountstatus.'" id="discountstatus" name="discountstatus" />
			   <div class="row col-md-8 mt-sm paid-summary">';
				
				   
				   if($saldostatus)
				   {
						 $summary.='
						   <div class="pull-left col-md-6 no-padd">
							<p class="grey-text">Saldo</p>
						  </div>
						  <div class="pull-left col-md-6  no-padd text-right">
							<p>kr '.'<b id="csaldo_amt">'.$amount.'</b></p>
						  </div>
						  <div class="clearfix"></div>
						  <hr>

						 <div class="pull-left col-md-12 no-padd" style="display:none">
						 <p>Saldo kr <b id="csaldo_amt">'.$amount.'</b></p></div>';    
						 
						 $summary.='<div style="display:none; class="pull-left col-md-12 no-padd">
						 <p>Your payable amount kr <b id="payable_amt">0,00</b></p>
						 </div>';
						 
						$summary.='<div style="display:none; class="pull-left col-md-12 mt-sm no-padd">
						<div class="col-md-3">
						<input type="hidden" id="saldostatus" name="saldostatus" value="1"  />
						</div><div class="col-md-7"></div></div>'; 
				   }
				   else
				   {
						$summary.='<div style="display:none; class="pull-left col-md-12 mt-sm no-padd">
						<div class="col-md-3">
						<input type="hidden" id="saldostatus" name="saldostatus" value="0"  />
						</div><div class="col-md-7"></div></div>'; 
				   }

			
			  $totapaidamount=array_sum($paidamountarray);
			  
			  $totalwaitamount=array_sum($waitamountarray);
			  
			  
					if(trim($orderinfo['changed_amount']) != '')
					{
						// if(intval($orderinfo['changed_amount']) > 0)
						 // {
							$orderinfo['total_amount']=$orderinfo['changed_amount'];
						  //}
					}
			 
 
			 
			  
			  
			  	 //if the dicount is voucher			
			 $discount=0;
			 $free_delivery_charge=0;
			if(intval($orderinfo['voucher']) > 0)
			{
		
				$qry="SELECT *,ROUND(price, 0) as price FROM a_voucher WHERE id='".$orderinfo['voucher']."'";
				$query = $this->db->query($qry);
				$vdata = $query->row_array();//multi row array
				
				$vouchercode=$vdata['voucher'];
				
				if($vdata['percentage'] != '')
				{
					$percentage=$vdata['percentage']/100;
					$discount=$subtotal*$percentage;
				}
				else
				{
					$discount=$vdata['price'];
				}
	
				if(($vdata['free_delivery_charge'] == 1)  && 	($orderinfo['type'] != 'shop'))
				{
					 $delviery=0;
					 $free_delivery_charge=1;
				}
				else
				{
					$delviery=$old_delivery_charge;
					
				}
		    }
			else{
				//if the discount in % or kr
				if($orderinfo['order_discount'] !=''){
					$vouchercode = $orderinfo['order_discount'];
					if(stripos($vouchercode, '%')){
						$percentage = str_replace("%","",$vouchercode);
						$discount =  $delsum * ($percentage/100);
					}
					else{
						
						$discount = str_replace("kr ","",$vouchercode);
						$vouchercode = "kr ".formatcurrency($discount);
					}
					
					
				}

			}
			
		
		
			  
			$discountapplystatus=0;
		
			if($pendingstatus == 1)
			{
				  if(intval($totapaidamount) > 0)
				  {
					if($discountstatus == 0)
					{
						if($totapaidamount >= $discount)
						{
							$discountapplystatus=1;
							$totapaidamount=$totapaidamount-$discount;
						}
						else
						{
							if($discount >= $totapaidamount)
							{
								$discountapplystatus=1;
								$discount=$discount-$totapaidamount;
								$totapaidamount=0;
							}
						}
						
						
						
					}
					
				  }
				  
				  if(intval($totalwaitamount) > 0)
				  {
					if($discountstatus == 0 && $discount > 0)
					{
						if($totalwaitamount >= $discount)
						{
							$discountapplystatus=1;
							$totalwaitamount=$totalwaitamount-$discount;
						}
						else
						{
							if($discount >= $totalwaitamount)
							{
								$discountapplystatus=1;
								$discount=$discount-$totalwaitamount;
								$totalwaitamount=0;
							}
						}
					}
					
				  }
					
					$balanceamount= $orderinfo['total_amount']-$totapaidamount;
					$balanceamount= $balanceamount-$totalwaitamount;
			  
			
			}
			else
			{
				
				if($discountstatus == 0 && $discount > 0)
				{
				
					  if(intval($totapaidamount) > 0)
					  {
						if($discountstatus == 0)
						{
							if($totapaidamount >= $discount)
							{
								$discountapplystatus=1;
								$totapaidamount=$totapaidamount-$discount;
							}
							else
							{
								if($discount >= $totapaidamount)
								{
									$discountapplystatus=1;
									$discount=$discount-$totapaidamount;
									$totapaidamount=0;
								}
							}
							
							
							
						}
						
					  }
					  
					    if(intval($totalwaitamount) > 0)
				  {
					if($discountstatus == 0 && $discount > 0)
					{
						if($totalwaitamount >= $discount)
						{
							$discountapplystatus=1;
							$totalwaitamount=$totalwaitamount-$discount;
						}
						else
						{
							if($discount >= $totalwaitamount)
							{
								$discountapplystatus=1;
								$discount=$discount-$totalwaitamount;
								$totalwaitamount=0;
							}
						}
					}
					
				  }
					
				  
				
					$balanceamount= $orderinfo['total_amount']-$totapaidamount;
						$balanceamount= $balanceamount-$totalwaitamount;
				
				
						//$totalpayamt=($totapaidamount+$totalwaitamount) - $orderinfo['total_amount'];
						//$discountapplystatus=1;
						//$balanceamount=$totalpayamt-$discount;
					
				}
				else
				{
					$balanceamount= $orderinfo['total_amount']-$totapaidamount;
					$balanceamount= $balanceamount-$totalwaitamount;
				}

			}
			
				
				 // echo $balanceamount;exit;

			
				  $summary.='<div class="row col-md-12 no-padd mt-sm">
                   <div class="pull-left col-md-6 no-padd">
                   <p class="grey-text">Betalt Beløp</p>
                   </div>                
                   <div class="pull-left col-md-6  no-padd text-right">
                     <p>kr '.formatcurrency($totapaidamount).'</p>
                   </div>
                   <div class="clearfix"></div>
                  <hr>
				</div>';
				
				$summary.='<div class="row col-md-12 no-padd mt-sm">
                   <div class="pull-left col-md-6 no-padd">
                   <p class="grey-text">Faktura Beløp</p>
                   </div>                
                   <div class="pull-left col-md-6  no-padd text-right">
                     <p>kr '.formatcurrency($totalwaitamount).'</p>
                   </div>
                   <div class="clearfix"></div>
                  <hr>
				</div>';

			
				
				   $summary.='<div class="row col-md-12 no-padd mt-sm">
                   <div class="pull-left col-md-6 no-padd">
                   <p class="grey-text"><span class="black=text">Utestående Beløp</p>
                   </div>                
                   <div class="pull-left col-md-6  no-padd text-right">
                     <p>kr '.formatcurrency($balanceamount).'</p>
                   </div>
                   <div class="clearfix"></div>
                  <hr>
                 </div>
				
                  <div class="clearfix"></div>
                </div>
			  </div>
               <div class="col-md-4 no-padd">
                  <div class="row mt-sm">
                   <div class="pull-left col-md-6 no-padd">
                   <p class="grey-text">Delsum'.$min_price_txt.'</p>
                   </div>                
                   <div class="pull-left col-md-6   text-right min-cart">
                     <p>kr <span>'.formatcurrency($subtotal).'</span></p>
                   </div>
                   <div class="clearfix"></div>
                  <hr>
                 </div>
				 
				 
				 ';
			// echo '<pre>';print_r($orderinfo);exit;
			
			
		
			if($discount!=0){  // show discount of an order
				
				$summary.='<div class="row mt-sm">
				   <div class="pull-left col-md-6 no-padd">
				   <p class="grey-text"><span class="black=text">Discount ( '.$vouchercode.' )</span></p>
				   </div>                
				   <div class="pull-left col-md-6  text-right">
					 <p>kr '.formatcurrency($discount).'</p>
				   </div>
				   <div class="clearfix"></div>
				  <hr>
				 </div>';
				
			}
				 
				
				$fprice=$cus_sub_total-$discount;
		
		if($orderinfo['type'] != 'shop')
		{
			if($fprice < $min_price)
			{
			$summary.='<div class="row" style="display:none;" id="minstebel">
                   <div class="pull-left col-md-6 no-padd">
                   <p class="grey-text">Minste beløp</p>
                   </div>                
                   <div class="pull-left col-md-6  text-right">
                     <p id="min_price">kr '.formatcurrency($min_price).'</p>
                   </div>
                   <div class="clearfix"></div>
                  <hr>
                 </div>';
			}
		}
		if(($orderinfo['type'] != 'shop') && ($delviery > 0))
		{
		$summary.='<div class="row">
		<div class="pull-left col-md-6 no-padd">
			<p class="grey-text">Levering</p>
                   </div>                
                   <div class="pull-left col-md-6  text-right">';
				   
				    if($delviery == 0)
					{
						 $summary.='<p id="delviery_amt">kr 0,00</p>';
					}
					else
					{
						
						 $summary.='<p id="delviery_amt">kr '.formatcurrency($delviery).'</p>';
					}
					
					
                     
					
					 
                  $summary.='</div>
                   <div class="clearfix"></div>
                  <hr>
                 </div>';
			}	 
				
		if(intval($orderinfo['changed_amount']) > 0)
					{
						$orderinfo['total_amount']=$orderinfo['changed_amount'];
					}		  
				
				 
				 
                 
                    $summary.='<div class="row totalt  mt-sm">
                   <div class="pull-left col-md-6 no-padd">
                   <p>TOTALT</p>
                   </div>                
                   <div class="pull-left col-md-6   text-right">
                     <p id="total_amount">kr '.formatcurrency($orderinfo['total_amount']).'</p>
					 
					 <input type="hidden" id="order_total_amount" name="order_total_amount" value="'.$orderinfo['total_amount'].'"  />
					 
					  <input type="hidden" id="order_delviery_amt" name="order_delviery_amount" value="'.$delviery.'"  />
					  
					   <input type="hidden" id="order_discount_amount" name="order_discount_amount" value="'.$discount.'"  />
					   
					   
					     <input type="hidden" id="free_delivery_charge" name="free_delivery_charge" value="'.$free_delivery_charge.'"  />
					  
					 
					  
                   </div>
                   <div class="clearfix"></div>
                  <hr>';
				  
				  
				 
				  
				  if(intval($saldostatus) == 0)
				  {
				  
					$totpayment=$totalwaitamount + $totapaidamount;
				  
				   if($totpayment == $orderinfo['total_amount'])
					{
						
					}
					else
					{
						$summary.='<div class="row">
					<div class="pull-left col-md-12 no-padd text-right">
				 	 <div class="saldo-button1 group-pos">
                            
                                <div class="btn-group">
								<button type="button" id="pay_cash" onclick="paymenttype(\'cash\')" data-switch-set="#discount_type12" data-switch-value="Kontant" class="btn btn-default payment_type">Kontant</button>								
                                  <button type="button" id="pay_invoice" onclick="paymenttype(\'invoice\')" data-switch-set="#discount_type12" data-switch-value="Faktura" class="btn btn-default payment_type">Faktura</button>
                                  <button type="button"  id="pay_visa"   onclick="paymenttype(\'visa\')"  data-switch-set="#discount_type12" data-switch-value="Kort" class="btn btn-default payment_type">Kort</button>
                                </div>
                                <input type="hidden" name="opay_type" id="opay_type">
                             
                         </div>
						 <div class="clearfix"></div>
						 </div>
				 
				 </div>';
					}
					
				  }
				  else
				  {
					$summary.='<input type="hidden" value="saldo" name="opay_type" id="opay_type">';
				  }
				  
				  
					
 
				  
                 $summary.='</div> 
                  
			  </div>
            </div>  
             ';
		
	    }
               $notes='';
               if($this->data['blocks']['blk1'][$i]['delivery_note']!='')
               {
                    $notes.=' <p>Delivery Notes: <span>'.$this->data['blocks']['blk1'][$i]['delivery_note'].'</span></p>';
               }
               
                if($this->data['blocks']['blk1'][$i]['special_instruction']!='')
               {
                    $notes.=' <p>Spesialinstruksjoner: <span>'.$this->data['blocks']['blk1'][$i]['special_instruction'].'</span></p>';
               }
				
				if($notes != '')
				{
						 $str .= '<div class="orderlist mt-sm">
                   <div class="pull-left col-md-12 no-padd">';
				   $str.=$notes;
				    $str.='</div>
                 <div class="clearfix"></div>
                 <hr>
               </div>';
				}
				
		$delivery_type = 'normal';
		$deliveryinfo =$this->general_model->checkMinimumAmount($delivery_type,'','',$customer['zone']);
		$deliveryinfo= json_encode($deliveryinfo);
		$deliveryinfo=htmlentities($deliveryinfo);
		
	//	echo '<pre>';print_r($orderlinedelivery);exit;
			if(count($orderlinedelivery) > 0)
			{
				/*$orderlinedelivery_time=implode('<br>',$orderlinedelivery);
				$orderlinedelivery_time = '<span>Levering: </span><br>' . $orderlinedelivery_time;
				$customer_detail.=$orderlinedelivery_time.'</p>';*/
			}
			else
			{	
				$customer_detail.=$orderlevering;
			}
			
		
				
		$order_details = $company_info . $customer_detail .$str . $summary ;
		
		$order_details.=' 
		
		<input type="hidden" id="zoneinfo" name="zoneinfo"  value="'.$deliveryinfo.'" />
		
		<div class="clearfix"></div>

		<div class="row mt-sm" style="margin-bottom:30px">
                     <div class="col-md-3 pull-left">
                      
                     </div>
                     <div class="col-md-3 pull-right no-padd">';
                      
					  $totpayment=$totalwaitamount + $totapaidamount;
					  
					if($totpayment == $orderinfo['total_amount'])
					{
						
					}
					else
					{
						$order_details.='<button onclick="newpayment();" type="button"  class="btn-lg green npayment_type">Betal &nbsp;&nbsp;<span></span></button>';
					}
					
                       
                     
					 $order_details.=' </div>               
                    <div class="clearfix"><input type="hidden" id="eorder_id" name="eorder_id" value="'.$order_id.'" /></div> 
                </div><script type=\'text/javascript\'>


$(\'.pay_type\').on(\'click\', function(e){
     e.preventDefault();
    $(\'#confirm\').modal({ backdrop: \'static\', keyboard: false });
	
});

</script>';
		$result = array("order_details"=>$order_details);
		echo json_encode($result);exit;
	

  
	}
	
	
	function __deleteorderpayment()
		{
		

		//profiling
		$this->data['controller_profiling'][] = __function__;
		
		$order_id = $this->input->post('orderid');
		
		//$order_id =  '16036000';
		

        $customer = $this->orders_model->getCustomerDetails($order_id);
        $orderinfo = $this->orders_model->getOrderinfo($order_id);
		
		//echo '<pre>';print_r($orderinfo['type']);exit;
		
			$order_from=$orderinfo['type'];
			$employeeinfo=array();
			if($order_from == 'shop')
			{
				$employeeinfo = $this->employee_model->getEmployeeDetail($orderinfo['employee']);
			}
			
			
		$order_status = $orderinfo['order_status'];
		
		
		if($order_status == 3){
			$this->data['visible']['wi_show_process_button'] = 1;
		}
        
        //get shop detail where order placed ( from SL or branch)
        $order_from = $orderinfo['type'];
        
        if($order_from == 'shop')
		{
            
            $partnerinfo = $this->general_model->getPartnerDetails($orderinfo['partner'],$orderinfo['partner_branch']);
            
            $company = $partnerinfo['name'];
            $address = $partnerinfo['street'] .", ". $partnerinfo['zip']. ' '.$partnerinfo['city'];
            $phone = $partnerinfo['phone']; 
            

        }
        else{
            $company = $this->data['settings_company']['company_name'];
            $address = $this->data['settings_company']['company_address_street'] .", ". $this->data['settings_company']['company_address_zip']. ' '.$this->data['settings_company']['company_address_city'];
            $phone = $this->data['settings_company']['company_telephone']; 
        }
		
		
			$company_info ='';
			$orderdetails = $this->orders_model->getOrderLine($order_id);
		
		
			//Kasserer: '.$this->data['fields']['employee']['initial'].'
		
		
		
		$collectiontinfo=$this->orders_model->getCollectionDeliverytime('collection',$orderinfo['collection_time']);
		$deliveryinfo=$this->orders_model->getCollectionDeliverytime('delivery',$orderinfo['delivery_time']);
		
		
			$address=$customer['street_line_1'];
			if($customer['street_line_2'] != '')
			{
				$address.=', '.$customer['street_line_2'];
			}
			
			
		   $customer_detail='<div class="customer-detail row">
			   <div class="pull-left col-md-3 no-padd">
			   <p><span>Ordrenr: </span>#'.sprintf("%08d", $order_id).'</p>
			   <p><span>Telefon: </span>'.$customer['number'].'<br>
			   </div>
			    <div class="pull-left col-md-5 no-padd">
				<span>Navn: </span>'.$customer['customer_name'].'</br>
				<span>Adresse: </span>'.$address.', '.$customer['zip'].' '.$customer['city'].'';
				
				
			/*	if($customer['floor'] != '')
				{
			   $customer_detail.='<span>Etg: </span>'.$customer['floor'].' <br>';
			   }
			   if($customer['calling_bell'] != '')
				{
			   $customer_detail.='<span>RK: </span>'.$customer['calling_bell'].' <br>';
			   }*/
					$customer_detail.='</div><div class="pull-left col-md-4 no-padd">';
				if($collectiontinfo['sdate'] != '')
				{
					$customer_detail.='<span>Henting: </span>'.$collectiontinfo['sdate'].' &nbsp; '.$collectiontinfo['stime'].'-'.$collectiontinfo['etime'].'<br>';
				}
				else{
					
					$customer_detail.='<span>Innlevering: </span>'.date('d.m.Y H:i',strtotime($orderinfo['order_time'])).'<br>';

				}
				
				if(($order_from != 'shop')){
					$customer_detail .='<span>Levering: </span>'.$deliveryinfo['sdate'].'  &nbsp; '.$deliveryinfo['stime'].'-'.$deliveryinfo['etime'].'</p>';
				}
				
				if(count($employeeinfo) > 0){
					$customer_detail .='<span>Kasserer: </span>'.$employeeinfo['initial'].'</p>';
				}
				
				
				   
				   $str='</div>                
                  
                   <div class="clearfix"></div>
                 </div>
                   <hr>

<div  class="orderlist row">

<div class="pull-left col-md-1 no-padd text-center">

<input type="checkbox" onClick="paycheckall(this)" id="checkall"  />
</div>';

if(($order_from == 'shop')){

$str .='<div class="pull-left col-md-3">
<p><b>Artikler</b></p>
</div>

<div class="pull-left col-md-1 no-padd">
<p><b>Utlevering</b></p>
</div>';

}
else{
	
$str .='<div class="pull-left col-md-4">
<p><b>Artikler</b></p>
</div>';
}

$str .='<div class="pull-left col-md-2 no-padd text-center">
<p><b>Pris</b></p>
</div>
<div class="pull-left col-md-1 no-padd text-center">
<p><b>Antall</b></p>
</div>
<div class="pull-left col-md-1 no-padd text-center">
<p><b>Status</b></p>
</div>
<div class="pull-left col-md-1 no-padd text-center">
<p><b>Anullering</b></p>
</div>
<div class="pull-left col-md-2 text-right">
<p><b>Totalt</b></p>
</div>
</div>
 <hr>
	<div class="popupscroll"> ';   
        
			//get results and save for tbs block merging
			$data = $this->orders_model->getOrderLine($order_id);
			//print_r($data);
			for($w=0;$w<count($data);$w++){
				$odlines[] = $data[$w]['id'];
			}
			//print_r($odlines);
			//get cancel orderline of an array
			$canceled_arr = $this->settings_order_model->getCancelOrderLine($order_id,$odlines);
			//print_r($canceled_arr);
			
			$canceled_arr_approved = $this->settings_order_model->getCancelOrderLine($order_id,$odlines,'approved');
			//print_r($canceled_arr_approved);
			
			
			
			$str.='<input type="hidden" name="totalorderline" id="totalorderline" value="'.count($data).'" />';
			$str.='<input type="hidden" name="checkedoderline" id="checkedoderline" value="" />';
	
	
			if(!empty($data)){
				$delsum = 0;
				$lstatus=1;
				$orderlineproduct=array();
				$orderlinedelivery=array();
				$paidamountarray=array();
				$paidstatus=1;
				$discountstatus=1;
				$pendingstatus=0;
				for($j=0;$j<count($data);$j++){
					
			   // echo $j."=".$data[$j]['name']."<br>";		
				
				$arr = $this->orders_model->getProductDisplayName($data[$j]['product']);
				
				$data[$j]['name'] = $arr['name'];


				$orderlineproduct[$data[$j]['product']]=$data[$j]['product'];
				
				if($data[$j]['p_b_delivery_time'] != '' && $data[$j]['p_b_delivery_time'] != '0000-00-00')
				{
					$p_b_delivery_time = date('d.m.Y',strtotime($data[$j]['p_b_delivery_time']));
					//$orderlinedelivery[]=$data[$j]['name'].' '.$p_b_delivery_time;
				}
			     else{
					 $p_b_delivery_time = '';
				 }
				
				//echo '<pre>';print_r($data[$j]);exit;
			//	echo $data[$j]['id'];exit;
					
					
					
					$sqty = $this->process_order_model->getHeatOrderLine($data[$j]['id']);
					$proqty=$data[$j]['qty'];
		
					if(trim($data[$j]['changed_quantity']) != '')
					{
						$data[$j]['quantity']=$data[$j]['changed_quantity'];
						
						/*if(intval($data[$j]['changed_quantity']) > 0)
						{
							$data[$j]['quantity']=$data[$j]['changed_quantity'];
						}
						else
						{
							continue;
						}*/
						
					}
					
					$discount = $this->products_model->getProDiscount($data[$j]['product']);
					 $discount=$discount[0];
					 $ddesc='&nbsp;';
					 if(isset($discount['description']))
					 {
						$ddesc=' ('.$discount['description'].')';
					 }
					
					$price =  '';
					if(trim($data[$j]['changed_amount']) != '')
					{
						//if(intval($data[$j]['changed_amount']) > 0)
						//{
							$price = $data[$j]['changed_amount'];
							$data[$j]['quantity'] = $data[$j]['changed_quantity'];
						//}
					}
					else
					{
						$price = $data[$j]['amount'];
					}
					
				
					$subtotal = $price;
					$productPrice=$subtotal;
					
					$productPrice=round($productPrice);
						
					
					
					$subtotalarray[]= $productPrice;
					
					//$subtotalarray[]= $subtotal;
					//$delsum += $data[$j]['price'] * $data[$j]['quantity'];
					$delsum=$productPrice;
					$path_parts = pathinfo($data[$j]['path']);
					$img = $path_parts['filename'] .".".$path_parts['extension']; 
				//	$total = ($delsum + $delivery_charge) - $discount ;
				
				
					if($data[$j]['quantity'] >= $sqty)
					{
						$newquantity=$data[$j]['quantity']-$sqty;
					}
					else
					{
						$newquantity=0;
					}
					
					$newquantity=$proqty*$newquantity;
					
					$pstatus = ($data[$j]['payment_status'] == 'paid') ? '' : 'order'.$data[$j]['payment_status'] ;
					$cstatus = $pending = '';
					$disable = ' onclick="payitem(this);" name="corderlines[]"';
					if(in_array($data[$j]['id'], $canceled_arr)){
						$cstatus = 'orderdisabled';
						$disable = 'disabled checked name="corderlines"';
						if(in_array($data[$j]['id'], $canceled_arr_approved)){
							$pending = 'Canceled';
						}
						else {
							$pending = 'Pending';
						}
						//$pending = ($orderinfo['order_status']=='11') ? 'Canceled':'Pending';
						
					}
					
					
					$str.=' <div id="tr_'.$data[$j]['id'].'" class="orderlist '.$pstatus.' '. $cstatus.'">';
					$str .= '
					<div class="pull-left col-md-1 no-padd text-center">';
					
					//echo $data[$j]['payment_status'];
					
					if($data[$j]['payment_status'] == 'pending')
					{
						$paidstatus=0;
						$pendingstatus=1;
						$str.= '<input type="checkbox" '.$disable.'   id="corderlines_'.$data[$j]['id'].'"  value="'.$data[$j]['id'].'" />';
					}
					else
					{	
					
					   //disabled="disabled"  
						$str.= '<input type="checkbox" '.$disable.'    id="corderlines_'.$data[$j]['id'].'"  value="'.$data[$j]['id'].'" />';
					
						if(strtolower($data[$j]['payment_status']) == 'waiting')
						{
							$waitamountarray[]=$productPrice;
							$paidstatus=0;
							$discountstatus=0;
						}
						if(strtolower($data[$j]['payment_status']) == 'paid' || strtolower($data[$j]['payment_status']) == 'canceled')
						{
							$paidamountarray[]=$productPrice;
							$discountstatus=0;
						}
							
					}
					
					if(($order_from == 'shop')){
					
					   $str .= '</div>  <div class="pull-left col-md-3">
					   <p>'.$data[$j]['name'].'</p>
					   </div>  
					   
					   <div class="pull-left col-md-1 no-padd">
					   <p>'.$p_b_delivery_time.'</p>
					   </div> ';
					
					}
					else{
						
					   $str .= '</div>  <div class="pull-left col-md-4">
					   <p>'.$data[$j]['name'].'</p>
					   </div>';  
					}


				   
                   $str.= '<div class="pull-left col-md-2 no-padd text-center">
					 <p>kr '.formatcurrency($data[$j]['price']).'</p>
                   </div>
					<div class="pull-left col-md-1 no-padd text-center">
					<div class="quantity">';
					
					
					$arr1=array("min_quantity" => "1" ,"price" => $data[$j]['price']);
					$ddata = $this->products_model->getProDiscount($data[$j]['product']);
				
					if (round($data[$j]['quantity'], 0) == $data[$j]['quantity'])
					{
						// is whole number
						$data[$j]['quantity'] = round($data[$j]['quantity'], 0);
					}					

					
					$str.='<p>'.$data[$j]['quantity'].'</p>
					 
					</div>
					</div>	
					
					<div class="pull-left col-md-1  no-padd text-center">
						   <p>'.ucfirst($data[$j]['payment_status']).'</p>
                   </div>
					<div class="pull-left col-md-1 no-padd text-center">
						   <p>'.ucfirst($pending).'</p>
                   </div>

					
                   <div class="pull-left col-md-2  text-right">
       <p class="tprice" id="subtotal_'.$data[$j]['product'].'"> kr '.formatcurrency($productPrice).'</p>
	   
	   <input type="hidden" value="'.$productPrice.'" name="ordertotal['.$data[$j]['id'].']" id="oltotal_'.$data[$j]['id'].'" />
	   
	     <input type="hidden" value="'.$data[$j]['payment_status'].'" name="orderpayment['.$data[$j]['id'].']" />
                   </div>
                   <div class="clearfix"></div>
                   <hr>
                 </div>';
				
				 
				$forderlineid=$data[$j]['id'];
				 
                    $lstatus++;
				}
				
				
		$subtotal=array_sum($subtotalarray);
		$cus_sub_total=$subtotal;
		$customerid = $customer['customerid'];
		$zone = $customer['zone'];
		$delivery_type = 'normal'; //default
		$this->data['reg_fields'][] = 'delivery';
		$this->data['fields']['delivery'] =$this->general_model->checkMinimumAmount($delivery_type,$customerid,$subtotal,$zone);
		$min_price=$this->data['fields']['delivery']['min_price'];
		$min_price_txt = '';
		if($cus_sub_total < $min_price)
		{
			if($orderinfo['type'] != 'shop')
			{
				$min_price_txt =  ' (Minste beløp kr '.formatcurrency($min_price).')';
			}
			
		}
		
		if($orderinfo['type'] != 'shop')
		{
			$subtotal = ($subtotal < $min_price) ?  $min_price : $subtotal;
		}
		$delsum=$subtotal;
		$this->data['debug'][] = $this->general_model->debug_data;	
		$delviery = ($subtotal >= $this->data['fields']['delivery']['free_delivery_after']) ?  '0' : $this->data['fields']['delivery']['delivery_charge'];
		$old_delivery_charge = $this->data['fields']['delivery']['delivery_charge'];
		
		
		$newproducts= $this->products_model->getProduct();
		
		//echo '<pre>';print_r($newproducts);exit;
		$customer=$this->session->userdata['customer']['id'];
		$data = $this->payments_model->getAccountBalance($customer);
		$pendingsaldo = $data['pending'];
		$paidsaldo = $data['paid'];
		

			
		if(intval($pendingsaldo) > 0)
		{	
			$amount= formatcurrency($paidsaldo).' ('.formatcurrency($pendingsaldo).')';
			$saldo = $paidsaldo + $pendingsaldo;
		}
		else
		{
			$amount= formatcurrency($paidsaldo);
			$saldo = $paidsaldo;
		}
		
		$saldostatus=$this->payments_model->getSaldostatus($customer);	
		
		
			$summary='</div><div class="handlekurv">   
               <div class="col-md-8 no-padd">
		    <input type="hidden" id="pay_amt" name="pay_amt" value="0" />
			<input type="hidden" id="paying_amt" name="paying_amt" value="0" />
			<input type="hidden" id="saldo_amt" name="saldo_amt" value="'.$saldo.'" />
			<input type="hidden" value="'.$discountstatus.'" id="discountstatus" name="discountstatus" />
			 <div class="row col-md-8 mt-sm paid-summary">
			 
			 <div class="row mt-sm">
					<div class="pull-left col-md-3 no-padd">
				   <p>Reason</span></p>
				   </div>                
				   <div class="pull-left col-md-9 no-padd">';
				   
				   $reasons=$this->payments_model->getCancelReasons();	
				   if(count($reasons) > 0)
				   {
						$summary.='<select class="form-control" name="cancelreason" id="cancelreason">';
						foreach($reasons as $reason)
						{
							$summary.='<option value="'.$reason['id'].'">'.$reason['name'].'</option>';
						}
						$summary.='</select>';
				   }
				   
				   
					$summary.='</div>
				   </div>
				   
				    <div class="clearfix"></div>
					
					<div class="row mt-sm">
				    <div class="pull-left col-md-3 no-padd">
				   <p>Comments</p>
				   </div>                
				   <div class="pull-left col-md-9">
					<textarea name="comments" id="comments"></textarea>
				   </div> </div>
				   
			 ';
				
				   
				  

			
			  $totapaidamount=array_sum($paidamountarray);
			  
			  $totalwaitamount=array_sum($waitamountarray);
			  
			  
			  if(intval($orderinfo['changed_amount']) > 0)
			  {
				$orderinfo['total_amount']=$orderinfo['changed_amount'];
			  }
			  
			  
			  	 //if the dicount is voucher			
			 $discount=0;
			 $free_delivery_charge=0;
			if(intval($orderinfo['voucher']) > 0)
			{
		
				$qry="SELECT *,ROUND(price, 0) as price FROM a_voucher WHERE id='".$orderinfo['voucher']."'";
				$query = $this->db->query($qry);
				$vdata = $query->row_array();//multi row array
				
				$vouchercode=$vdata['voucher'];
				
				if($vdata['percentage'] != '')
				{
					$percentage=$vdata['percentage']/100;
					$discount=$subtotal*$percentage;
				}
				else
				{
					$discount=$vdata['price'];
				}
	
				if(($vdata['free_delivery_charge'] == 1)  && 	($orderinfo['type'] != 'shop'))
				{
					 $delviery=0;
					 $free_delivery_charge=1;
				}
				else
				{
					$delviery=$old_delivery_charge;
					
				}
		    }
			else{
				//if the discount in % or kr
				if($orderinfo['order_discount'] !=''){
					$vouchercode = $orderinfo['order_discount'];
					if(stripos($vouchercode, '%')){
						$percentage = str_replace("%","",$vouchercode);
						$discount =  $delsum * ($percentage/100);
					}
					else{
						
						$discount = str_replace("kr ","",$vouchercode);
						$vouchercode = "kr ".formatcurrency($discount);
					}
					
					
				}

			}
			
		
		
			  
			$discountapplystatus=0;
		
			if($pendingstatus == 1)
			{
				  if(intval($totapaidamount) > 0)
				  {
					if($discountstatus == 0)
					{
						if($totapaidamount >= $discount)
						{
							$discountapplystatus=1;
							$totapaidamount=$totapaidamount-$discount;
						}
						else
						{
							if($discount >= $totapaidamount)
							{
								$discountapplystatus=1;
								$discount=$discount-$totapaidamount;
								$totapaidamount=0;
							}
						}
						
						
						
					}
					
				  }
				  
				  if(intval($totalwaitamount) > 0)
				  {
					if($discountstatus == 0 && $discount > 0)
					{
						if($totalwaitamount >= $discount)
						{
							$discountapplystatus=1;
							$totalwaitamount=$totalwaitamount-$discount;
						}
						else
						{
							if($discount >= $totalwaitamount)
							{
								$discountapplystatus=1;
								$discount=$discount-$totalwaitamount;
								$totalwaitamount=0;
							}
						}
					}
					
				  }
					
					$balanceamount= $orderinfo['total_amount']-$totapaidamount;
					$balanceamount= $balanceamount-$totalwaitamount;
			  
			
			}
			else
			{
				
				if($discountstatus == 0 && $discount > 0)
				{
				
					  if(intval($totapaidamount) > 0)
					  {
						if($discountstatus == 0)
						{
							if($totapaidamount >= $discount)
							{
								$discountapplystatus=1;
								$totapaidamount=$totapaidamount-$discount;
							}
							else
							{
								if($discount >= $totapaidamount)
								{
									$discountapplystatus=1;
									$discount=$discount-$totapaidamount;
									$totapaidamount=0;
								}
							}
							
							
							
						}
						
					  }
					  
					    if(intval($totalwaitamount) > 0)
				  {
					if($discountstatus == 0 && $discount > 0)
					{
						if($totalwaitamount >= $discount)
						{
							$discountapplystatus=1;
							$totalwaitamount=$totalwaitamount-$discount;
						}
						else
						{
							if($discount >= $totalwaitamount)
							{
								$discountapplystatus=1;
								$discount=$discount-$totalwaitamount;
								$totalwaitamount=0;
							}
						}
					}
					
				  }
					
				  
				
					$balanceamount= $orderinfo['total_amount']-$totapaidamount;
						$balanceamount= $balanceamount-$totalwaitamount;
				
				
						//$totalpayamt=($totapaidamount+$totalwaitamount) - $orderinfo['total_amount'];
						//$discountapplystatus=1;
						//$balanceamount=$totalpayamt-$discount;
					
				}
				else
				{
					$balanceamount= $orderinfo['total_amount']-$totapaidamount;
					$balanceamount= $balanceamount-$totalwaitamount;
				}

			}
			
				
				 // echo $balanceamount;exit;

			
				
				
				

			
				
				   $summary.='
                  <div class="clearfix"></div>
                </div>
			  </div>
               <div class="col-md-4 no-padd">
                  <div class="row mt-sm">
                   <div class="pull-left col-md-6 no-padd">
                   <p class="grey-text">Delsum'.$min_price_txt.'</p>
                   </div>                
                   <div class="pull-left col-md-6   text-right min-cart">
                     <p>kr <span>'.formatcurrency($subtotal).'</span></p>
                   </div>
                   <div class="clearfix"></div>
                  <hr>
                 </div>
				 
				 
				 ';
			// echo '<pre>';print_r($orderinfo);exit;
			
			
		
			if($discount!=0){  // show discount of an order
				
				$summary.='<div class="row mt-sm">
				   <div class="pull-left col-md-6 no-padd">
				   <p class="grey-text"><span class="black=text">Discount ( '.$vouchercode.' )</span></p>
				   </div>                
				   <div class="pull-left col-md-6  text-right">
					 <p>kr '.formatcurrency($discount).'</p>
				   </div>
				   <div class="clearfix"></div>
				  <hr>
				 </div>';
				
			}
				 
				
				$fprice=$cus_sub_total-$discount;
		
		if($orderinfo['type'] != 'shop')
		{
			if($fprice < $min_price)
			{
			$summary.='<div class="row" style="display:none;" id="minstebel">
                   <div class="pull-left col-md-6 no-padd">
                   <p class="grey-text">Minste beløp</p>
                   </div>                
                   <div class="pull-left col-md-6  text-right">
                     <p id="min_price">kr '.formatcurrency($min_price).'</p>
                   </div>
                   <div class="clearfix"></div>
                  <hr>
                 </div>';
			}
		}
		if(($orderinfo['type'] != 'shop') && ($delviery > 0))
		{
		$summary.='<div class="row">
		<div class="pull-left col-md-6 no-padd">
			<p class="grey-text">Levering</p>
                   </div>                
                   <div class="pull-left col-md-6  text-right">';
				   
				    if($delviery == 0)
					{
						 $summary.='<p id="delviery_amt">kr 0,00</p>';
					}
					else
					{
						
						 $summary.='<p id="delviery_amt">kr '.formatcurrency($delviery).'</p>';
					}
					
					
                     
					
					 
                  $summary.='</div>
                   <div class="clearfix"></div>
                  <hr>
                 </div>';
			}	 
				
		//if(intval($orderinfo['changed_amount']) > 0)
					if(trim($orderinfo['changed_amount']) != '')
					{
						$orderinfo['total_amount']=$orderinfo['changed_amount'];
					}		  
				
				 
				 
                 
                    $summary.='<div class="row totalt  mt-sm">
                   <div class="pull-left col-md-6 no-padd">
                   <p>TOTALT</p>
                   </div>                
                   <div class="pull-left col-md-6   text-right">
                     <p id="total_amount">kr '.formatcurrency($orderinfo['total_amount']).'</p>
					 
					 <input type="hidden" id="order_total_amount" name="order_total_amount" value="'.$orderinfo['total_amount'].'"  />
					 
					  <input type="hidden" id="order_delviery_amt" name="order_delviery_amount" value="'.$delviery.'"  />
					  
					   <input type="hidden" id="order_discount_amount" name="order_discount_amount" value="'.$discount.'"  />
					   
					   
					     <input type="hidden" id="free_delivery_charge" name="free_delivery_charge" value="'.$free_delivery_charge.'"  />
					  
					 
					  
                   </div>
                   <div class="clearfix"></div>
				   
                  <hr>';
				  
				   $summary.='<div class="pull-right col-md-6 no-padd mt-sm text-right"><button onclick="deleteorderlines();" type="button"  class="btn-lg green npayment_type">Cancel</button></div><div class="clearfix"></div>';
				  
				  
				 
				  
				  if(intval($saldostatus) == 0)
				  {
				  
					$totpayment=$totalwaitamount + $totapaidamount;
				  
					
				  }
				 
				  $summary.='</div> 
                  
			  </div>
            </div>';
		
	    }
               $notes='';
               if($this->data['blocks']['blk1'][$i]['delivery_note']!='')
               {
                    $notes.=' <p>Delivery Notes: <span>'.$this->data['blocks']['blk1'][$i]['delivery_note'].'</span></p>';
               }
               
                if($this->data['blocks']['blk1'][$i]['special_instruction']!='')
               {
                    $notes.=' <p>Spesialinstruksjoner: <span>'.$this->data['blocks']['blk1'][$i]['special_instruction'].'</span></p>';
               }
				
				if($notes != '')
				{
						 $str .= '<div class="orderlist mt-sm">
                   <div class="pull-left col-md-12 no-padd">';
				   $str.=$notes;
				    $str.='</div>
                 <div class="clearfix"></div>
                 <hr>
               </div>';
				}
				
		$delivery_type = 'normal';
		$deliveryinfo =$this->general_model->checkMinimumAmount($delivery_type,'','',$customer['zone']);
		$deliveryinfo= json_encode($deliveryinfo);
		$deliveryinfo=htmlentities($deliveryinfo);
		
	//	echo '<pre>';print_r($orderlinedelivery);exit;
			if(count($orderlinedelivery) > 0)
			{
				/*$orderlinedelivery_time=implode('<br>',$orderlinedelivery);
				$orderlinedelivery_time = '<span>Levering: </span><br>' . $orderlinedelivery_time;
				$customer_detail.=$orderlinedelivery_time.'</p>';*/
			}
			else
			{	
				$customer_detail.=$orderlevering;
			}
			
		
				
		$order_details = $company_info . $customer_detail .$str . $summary ;
		
		$order_details.=' 
		
		<input type="hidden" id="zoneinfo" name="zoneinfo"  value="'.$deliveryinfo.'" />
		
		<div class="clearfix"></div>

		<div class="row mt-sm" style="margin-bottom:30px">
                     <div class="col-md-3 pull-left">
                      
                     </div>
                     <div class="col-md-2 pull-right no-padd">';
                      
					  $totpayment=$totalwaitamount + $totapaidamount;
					  
					/*if($totpayment == $orderinfo['total_amount'])
					{
						
					}
					else
					{
						$order_details.='<button onclick="deleteorderlines();" type="button"  class="btn-lg green npayment_type">Cancel</button>';
					}*/
					
					//$order_details.='<button onclick="deleteorderlines();" type="button"  class="btn-lg green npayment_type">Cancel</button>';
                     
					 $order_details.=' </div>               
                    <div class="clearfix"><input type="hidden" id="eorder_id" name="eorder_id" value="'.$order_id.'" /></div> 
                </div><script type=\'text/javascript\'>


$(\'.pay_type\').on(\'click\', function(e){
     e.preventDefault();
    $(\'#confirm\').modal({ backdrop: \'static\', keyboard: false });
	
});

</script>';
		$result = array("order_details"=>$order_details);
		echo json_encode($result);exit;
	

  
	}
	
	
	function __updatedeliverystatus()
	{
		if(count($_POST) > 0)
		{
			$orderid=intval($_POST['orderid']);
			$status=$this->orders_model->updateorderlog($orderid,9);
			if($status)
			{
				$result = array("status"=>'success',"message"=>"Deliery status has been updated successfully.");
			}
			else
			{
				$result = array("status"=>'error',"message"=>"Already Delivered");
			}
			
		}
		else
			{
				$result = array("status"=>'error',"message"=>"Invalid");
			}
		
		
		echo json_encode($result);exit;
	}
	
	
	
	
	/*get voucher details*/
	function __voucherDiscount(){
		
		$voucher = $this->input->post('voucher');
		$customer = $this->session->userdata['customer']['id'];
		$order_id = $this->input->post('oid');
		$amount = $this->input->post('delsum');
		
		$this->data['reg_fields'][] = 'order';
		$orderinfo = $this->orders_model->getOrderinfo($order_id);
		
		$delivery_type = 'normal'; //default
		$zone = $this->session->userdata['zipdata']['zone'];
		$customerinfo=$this->customer_model->getCustomerinfo($customer);
		
		//$amount = ($orderinfo['changed_amount']!='') ? $orderinfo['changed_amount'] : $orderinfo['total_amount'];
		
		$total=$amount;
		$subtotal=$total;
		$cus_sub_total=$subtotal;
		
		$this->data['reg_fields'][] = 'delivery';
		$this->data['fields']['delivery'] =$this->general_model->checkMinimumAmount($delivery_type,$customer,$total,$zone);
		
		$min_price=$this->data['fields']['delivery']['min_price'];
		$min_price_txt = '';
		$min_price_status=0;
		if($cus_sub_total < $min_price)
		{
			$min_price_txt =  ' (Minste beløp kr '.formatcurrency($min_price).')';
			$min_price_status=1;
		}
		
		$subtotal = ($subtotal < $min_price) ?  $min_price : $subtotal;
		$delsum=$subtotal;
		
		$delviery = ($subtotal >= $this->data['fields']['delivery']['free_delivery_after']) ?  '0' : $this->data['fields']['delivery']['delivery_charge'];
		$old_delivery_charge = $this->data['fields']['delivery']['delivery_charge'];

		
		//get voucher type
		$voucher_data = $this->general_model->getVoucherType($voucher,$customer,$amount);
		$this->data['debug'][] = $this->general_model->debug_data;
		
		
		if (empty($voucher_data)) {
			
			$delsumamt=$total/1.25;	
			$delsumamt=round($delsumamt, 2);
			$delsumvat= $total-$delsumamt;
			$data['mva']= formatcurrency($delsumvat);	
			$data['total']= $total;	
			$data['total_currenccy']= formatcurrency($total);	
			$data['error'] = 'true' ;
			$data['error1'] = 'Kupongen er ikke gyldig';
			$data['error2'] = 'Din totale beløpet var mindre enn minimumsbeløpet for å bruke kupongkode.';

			echo json_encode($data);exit;
		}
		
		//print_r($voucher_data);			
		
		$voucher_type = $voucher_data['type'];
		
		if($voucher_type == 'invitation'){
			$usedstatus = $this->general_model->checkVoucherused($voucher,$customer,$total);
			if($usedstatus)
			{
				
				$delsumamt=$total/1.25;	
				$delsumamt=round($delsumamt, 2);
				$delsumvat= $total-$delsumamt;
				$data['mva']= formatcurrency($delsumvat);	
				$data['total']= $total;	
				$data['total_currenccy']= formatcurrency($total);	
				$data['error'] = true ;
				$data['error1'] = 'Beklage, du har allerede brukt den kupongkode. Kann ikke bruke mer enn én.';
				
				echo json_encode($data);
				
			}
		}
		

		$status = $this->general_model->validateVoucher($voucher_data['id'],$customer);
		if($status)
		{
			
			$delsumamt=$total/1.25;	
			$delsumamt=round($delsumamt, 2);
			$delsumvat= $total-$delsumamt;
			$data['mva']= formatcurrency($delsumvat);	
			$data['total']= $total;	
			$data['total_currenccy']= formatcurrency($total);	
			$data['error'] = 'true' ;
			$data['error1'] = 'Beklager dette kupongkode allerede brukt.';
			$data['error2'] = 'Vennligst prøv en annen kupongkode';
			
			echo json_encode($data);exit;
		}
		
		
		$data = $this->general_model->getVoucherInfo($voucher,$customer,$total,$voucher_type);
		$this->data['debug'][] = $this->general_model->debug_data;
		
		
		if(!empty($data)){
			
			
			$delivery_charge = $delivery['delivery_charge'];
			$min_price = $delivery['min_price'];
			$new_delsum = $total;
				
			if($data['percentage'] != '')
			{
				$percentage=$data['percentage']/100;
				$discount=$new_delsum*$percentage;
				
				$data['price']=$discount;
				$data['discount_price']=$discount;
				
			}
			else
			{
				$discount=$data['price'];
				$data['discount_price']=$data['price'];
			}
			
			$new_delsum=$new_delsum-$discount;
			$data['min_price_txt']='';
			if($new_delsum < $min_price)
			{
				$data['min_price_txt']=' (Minste beløp kr '.$min_price.')';
				$min_price_status=1;
	
			}
			
			
			$new_delsum = ($new_delsum < $min_price) ?  $min_price : $new_delsum;
			$total =  $new_delsum;
			
			
			if($orderinfo['type'] == 'shop')
			{
				$data['delivery_price']=0;	
				$min_price_status=0;
				$data['min_price']= 0;
			}
			
	
			$delsumamt=$total/1.25;	
			$delsumamt=round($delsumamt, 2);
			$delsumvat= $total-$delsumamt;
			$data['name']=$voucher;
			$data['discount_price_currency']= formatcurrency($discount);
			$data['mva']= formatcurrency($delsumvat);	
			$data['total']= $total;	
			$data['total_currenccy']= formatcurrency($total);	
			
			
			$this->session->unset_userdata('subtotal');
			$this->session->unset_userdata('total_price');
			$newdata = array('total_price'  => $total,'subtotal'=>$new_delsum);
			$this->session->set_userdata($newdata);
			
			
			$newdata = array('total_price'  => $total);
			$this->session->set_userdata($newdata);
			
			$result = $this->orders_model->updateOrderDiscount($order_id,$voucher_data['id'],'Kupongkode',$total,$customer);
			
			if($result){
				echo json_encode($data);
			}
			else{
				
				$delsumamt=$total/1.25;	
				$delsumamt=round($delsumamt, 2);
				$delsumvat= $total-$delsumamt;
				$data['mva']= formatcurrency($delsumvat);	
				$data['total']= $total;	
				$data['total_currenccy']= formatcurrency($total);	
				$data['error'] = 'true' ;
				$data['error1'] = 'Kupongen er ikke gyldig';
				$data['error2'] = 'Din totale beløpet var mindre enn minimumsbeløpet for å bruke kupongkode.';
			
				echo json_encode($data);
			}
		}
		else{
			$delsumamt=$total/1.25;	
			$delsumamt=round($delsumamt, 2);
			$delsumvat= $total-$delsumamt;
			$data['mva']= formatcurrency($delsumvat);	
			$data['total']= $total;	
			$data['total_currenccy']= formatcurrency($total);	
			$data['error'] = 'true' ;
			$data['error1'] = 'Kupongen er ikke gyldig';
			$data['error2'] = 'Din totale beløpet var mindre enn minimumsbeløpet for å bruke kupongkode.';
		
			echo json_encode($data);exit;
		}
		
		exit;
		
    }
	
	
	/*get voucher details*/
	function __caluclateDiscount(){
		
		$discount_type = $this->input->post('discount_type');
		$discount_val = $this->input->post('discount_val');
		$customer = $this->session->userdata['customer']['id'];
		$order_id = $this->input->post('oid');
		$total = $this->input->post('delsum');
		
		$this->data['reg_fields'][] = 'order';
		$orderinfo = $this->orders_model->getOrderinfo($order_id);
		
		if($discount_type == '%'){
			$discount =  $total * ($discount_val /100);
			$data['name'] =  $discount .'%';
		}
		else{
			
			$discount = $discount_val;
			$data['name'] = "kr ". $discount;
			
		}
		
		$total = $total-$discount;
		$result = $this->orders_model->updateOrderDiscount($order_id,$discount_val,$discount_type,$total,$customer);
		
		if($result){
			$delsumamt=$total/1.25;	
			$delsumamt=round($delsumamt, 2);
			$delsumvat= $total-$delsumamt;
			$data['mva']= formatcurrency($delsumvat);	
			$data['total']= $total;	
			$data['total_currenccy']= formatcurrency($total);
			$data['discount_price']=$discount;
			$data['discount_price_currency']= formatcurrency($discount);
			echo json_encode($data);
			exit;
		}
		else{
			
			$delsumamt=$total/1.25;	
			$delsumamt=round($delsumamt, 2);
			$delsumvat= $total-$delsumamt;
			$data['mva']= formatcurrency($delsumvat);	
			$data['total']= $total;	
			$data['total_currenccy']= formatcurrency($total);	
			$data['error'] = 'true' ;
			$data['error1'] = 'Discount not applied.Please try again.';
			echo json_encode($data);
			exit;
		}
		
		
		
		
	}
	
	function __getproducttypes()
	{
		if(count($_POST) > 0)
		{
			$orderline=$_POST['orderline'];
			$producttypes = $this->process_order_model->getproducttypes($orderline);
			$outhtml='';
			if($producttypes)
			{
				$i=0;
				foreach($producttypes as $proitems)
				{
					
				 if(($i%3)==0 && ($i !=0 ))	
				 	$outhtml.='<div class="clearfix mt-lg"></div>';
					
					
				$outhtml.='<div class="col-md-4">
                 <input type="radio" class="producttype" value="'.$proitems['id'].'" name="producttype" onclick="closepopup();" /> '.$proitems['name'].'</div>';
				 $i++;
				}
			}
				$data=array();
				$data['protype'] = $outhtml;
				echo json_encode($data);
				exit;
			
		}
		
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
	function __damageheatseal()
	{
		
		//profiling
		$this->data['controller_profiling'][] = __function__;
		
		$order_id = $this->input->post('orderid');
        $customer = $this->orders_model->getCustomerDetails($order_id);
        $orderinfo = $this->orders_model->getOrderinfo($order_id);
		
		$order_status = $orderinfo['order_status'];
		//get shop detail where order placed ( from SL or branch)
        $order_from = $orderinfo['type'];
        
        if($order_from == 'shop'){
            
            $partnerinfo = $this->general_model->getPartnerDetails($orderinfo['partner'],$orderinfo['partner_branch']);
            $company = $partnerinfo['name'];
            $address = $partnerinfo['street'] .", ". $partnerinfo['zip']. ' '.$partnerinfo['city'];
            $phone = $partnerinfo['phone']; 
        }
        else{
            $company = $this->data['settings_company']['company_name'];
            $address = $this->data['settings_company']['company_address_street'] .", ". $this->data['settings_company']['company_address_zip']. ' '.$this->data['settings_company']['company_address_city'];
            $phone = $this->data['settings_company']['company_telephone']; 
        }
		
		
		$company_info ='';
		
		
		$orderdetails = $this->orders_model->getOrderLine($order_id);
		$collectiontinfo=$this->orders_model->getCollectionDeliverytime('collection',$orderinfo['collection_time']);
		$deliveryinfo=$this->orders_model->getCollectionDeliverytime('delivery',$orderinfo['delivery_time']);
		
		
			$address=$customer['street_line_1'];
			if($customer['street_line_2'] != '')
			{
				$address.=', '.$customer['street_line_2'];
			}
			
			
			   $customer_detail='<div class="customer-detail row">
			   <div class="pull-left col-md-3 no-padd">
			   <p><span>Ordrenr: </span>#'.$order_id.'
			   <input type="hidden" id="eorder_id" name="eorder_id" value="'.$order_id.'" />
			   </p>
			   <p><span>Telefon: </span>'.$customer['number'].'<br>
			   </div>
			    <div class="pull-left col-md-5 no-padd">
				<span>Navn: </span>'.$customer['customer_name'].'</br>
				<span>Adresse: </span>'.$address.', '.$customer['zip'].' '.$customer['city'].'';
				
				
			/*	if($customer['floor'] != '')
				{
			   $customer_detail.='<span>Etg: </span>'.$customer['floor'].' <br>';
			   }
			   if($customer['calling_bell'] != '')
				{
			   $customer_detail.='<span>RK: </span>'.$customer['calling_bell'].' <br>';
			   }*/
				
				$customer_detail.='</div><div class="pull-left col-md-4 no-padd">';
				if($collectiontinfo['sdate'] != '')
				{
					$customer_detail.='<span>Henting: </span>'.$collectiontinfo['sdate'].' &nbsp; '.$collectiontinfo['stime'].'-'.$collectiontinfo['etime'].'<br>';
				}
				else{
					
					$customer_detail.='<span>Innlevering: </span>'.date('d.m.Y H:i',strtotime($orderinfo['order_time'])).'<br>';

				}
				if(($order_from != 'shop')){
					
					$customer_detail.='<span>Levering: </span>'.$deliveryinfo['sdate'].'  &nbsp; '.$deliveryinfo['stime'].'-'.$deliveryinfo['etime'].'</p>';
					
				}
				   
				   $str='</div>                
                  
                   <div class="clearfix"></div>
                 </div>
                   <hr>

				 ';   
        
			//get results and save for tbs block merging
			$data = $this->orders_model->getOrderLine($order_id);
		//	echo '<pre>';print_r($data);exit;
			//$str='';
			if(!empty($data)){
				
				$productdropdown='';
				$heatsealdropdown='';
				$orderlinearray=array();
				$heatarray=array();
				$heatsealnamearray=array();
				$orderlinenamearray=array();
				foreach($data as $order_line)
				{	
					$order_line_id=$order_line['id'];
					$orderlinearray[]=$order_line_id;
					
       				$arr = $this->orders_model->getProductDisplayName($order_line['product']);
					$order_line['name'] = $arr['name'];
					
					$orderlinenamearray[$order_line_id]=$order_line['name'];
					$productdropdown.='<option value="'.$order_line_id.'">'.$order_line['name'].'</option>';
				
					$heatseal = $this->orders_model->orderlineHeatseal($order_line['id']);
					
					
					if($heatseal)
					{
						foreach($heatseal as $heatinfo)
						{
							$subname='';
							if(intval($heatinfo['heatid']) > 0)
							{ 
							
							
							
							
								if(intval($heatinfo['additional_product']) > 0)
								{
									$protype = $this->process_order_model->getproducttypename($heatinfo['heatid']);
									$subname=' - '.$protype['name'].' ';
								}
								$heatsealnamearray[$heatinfo['heatid']]=$order_line['name'].$subname.'(#'.$heatinfo['barcode'].')';
								
								$heatsealdropdown.='<option value="'.$heatinfo['heatid'].'">HS #'.$heatinfo['barcode'].' ('.$order_line['name'].''.$subname.')</option>';
								$heatarray[]=$heatinfo['heatid'];
							}
							
						}
						
					}
				
										
				}
				
			}
			
		$protype = $this->process_order_model->getdamageheatseal($orderlinearray,$heatarray);
			
		
			$str.=' <div class="popupscroll">';
			
			$dinfo='';
			if(count($protype) > 0)
			{
				$i=1;
				foreach($protype as $items)
				{
				
				
				$imgarray=unserialize($items['image_path']);
				
	
					$names='';
					
					if(intval($items['heat_seal']) > 0)
					{
						$names=$heatsealnamearray[$items['heat_seal']];
					}
					
					if(intval($items['orderline']) > 0)
					{
						$names=$orderlinenamearray[$items['orderline']];
					}
					
				if($i == 1)
				{
					$dinfo.='<div class="row">';	
				}
				else
				{
					$dinfo.='<div class="row  mt-sm">';
				}
					
                    $dinfo.='<div class="pull-left col-md-12">
                    <div class="col-md-11 no-padd text-left">
					 <p><b>'.$names.'</b></p>
					</div>					
					<div class="col-md-1 no-padd text-right">
					<p><a class="removeDimg" data-toggle="modal" data-title="Remove" href="#removeDamageModal" onclick="removeimg(\''.$items['id'].'\',\''.$order_id.'\');"  data-title="Remove" ><span class="x-icon"><i class="icon-remove red-text"></i></span></a></p>
					</div>
                    </div>
                    <div class="pull-left col-md-12">
                      <p>'.$items['comment'].'</p>
                    </div>
                    <div class="pull-left col-md-12 no-padd">
                      <div class="product-block">';
					  
					  if(count($imgarray) > 0)
					  {
						foreach($imgarray as $imgpath)
						{
							$imgdata=base_url().'images/uploads/'.$imgpath;
							 $dinfo.='<div class="col-md-3 no-padd text-center">
                          <div class="square"> <a style="background-image: url('.$imgdata.');" class="img" ></a></div>
                        </div>';
						}
					  }
                        
						
                        
                       $dinfo.=' <div class="clearfix"></div>
                      </div>
                    </div>
                    <div class="clearfix"></div>
                  </div>';	
$i++;				  
				}
			}
                  
				  
				  
				
				  $str.=$dinfo;
                $str.=' </div>                                    
                 ';
			
			$str.='  <hr>
				  
                  <div class="row heatseal mt-sm" style="margin-bottom:30px">
                    <div class="col-md-12 no-padd">
                      <div class="row mt-sm">
                        <div class="pull-left col-md-2 no-padd">
                          <select class="" onchange="getHeatdropdown(this.value)" name="bytype" id="bytype"  title="">';
						  if($heatsealdropdown != '')
						  {
							 $str.='<option value="heatseal">By Heat Seal</option>';
						  }
						  
						  if($productdropdown != '')
						  {
							 $str.='<option value="product">By Product</option>';
						  }
                           
                           
                          $str.='</select>
                        </div>
                        <div class="pull-left col-md-5 no-padd">';
						  if($heatsealdropdown != '')
						  {
                           $str.='<select class="" style="display:none;" name="sheatseal" id="sheatseal"  title="">'.$heatsealdropdown.'</select>';
						  }
						  if($productdropdown != '')
						  {
						  $str.='<select class="" style="display:none;" name="sproduct" id="sproduct"  title="">'.$productdropdown.'</select>';
						  }
						   
						   
						$str.='</div>
                        <div class="pull-left col-md-2 no-padd">
                          <input type="file" id="heatsealimage" name="heatsealimage[]" multiple="multiple" value="Upload File" style="margin-left:10px"/>
                        </div>
                        <div class="pull-left col-md-3 no-padd">
                          <input type="text" placeholder="kommentarer" name="comments" value="" class="form-control"/>
                        </div>
                        <div class="clearfix"></div>
                        <div class="pull-left col-md-4 no-padd mt-sm">
                          <button type="submit" id="add_productbtn"  style="font-size:32px !important;width:70%;padding:8px 0" class="btn-lg btn-primary">Add</button>
                          <button type="button" class="minusplus" data="398" style="display:none;">Refersh</button>
                        </div>
                        <div class="clearfix"></div>
                      </div>
                    </div>
                  </div>
				  ';
            $order_details = $company_info . $customer_detail .$str;
			$result = array("order_details"=>$order_details);
			echo json_encode($result);exit;
	}
	
	function __savedamageheatseal()
	{
		$uploads=array();
		if(count($_POST) > 0)
		{
			$savetype=$_POST['bytype'];
			$heatseal=$_POST['sheatseal'];
			$ordreline=$_POST['sproduct'];
			$comments=$_POST['comments'];
			$config = array(
			'upload_path'   => 'images/uploads/',
			'allowed_types' => 'jpg|gif|png',
			'overwrite'     => 1,                       
			);
			$this->load->library('upload', $config);
			$files=$_FILES['heatsealimage'];
			foreach ($files['name'] as $key => $image) 
			{
				$_FILES['heatsealimage']['name']= $files['name'][$key];
				$_FILES['heatsealimage']['type']= $files['type'][$key];
				$_FILES['heatsealimage']['tmp_name']=$files['tmp_name'][$key];
				$_FILES['heatsealimage']['error']= $files['error'][$key];
				$_FILES['heatsealimage']['size']= $files['size'][$key];
				$config['file_name'] = $image;
				$this->upload->initialize($config);
				if ($this->upload->do_upload('heatsealimage')) 
				{
					$uploadsdata=$this->upload->data();
					$uploads[]=$uploadsdata['file_name'];
				}
			}
			
			$images=$uploads;
			$damagearray=array(
			'heat_seal'=>$heatseal,
			'savetype'=>$savetype,
			'ordreline'=>$ordreline,
			'image_path'=>$images,
			'comment'=>$comments);
			
			$protype = $this->process_order_model->updatedamageheatseal($damagearray);
			
			$result=array('status'=>'success');
		 }
		else
		{
			$result=array('status'=>'error');
		}
		echo json_encode($result);exit;
	}
	
	/* Remove the damage product details*/
	function __removedamageline()
	{
		if(count($_POST) > 0)
		{
		$id=$_POST['id'];
			$status=$this->orders_model->removedamageline($id);
			if($status)
			{
				$result = array("status"=>'success',"message"=>"Damage items has been removed successfully.");
			}
			else
			{
				$result = array("status"=>'error',"message"=>"Invalid");
			}
			
			echo json_encode($result);exit;
		}
	}
	
	/*get customer id from order number or barcode*/
	function _getcustomerinfo()
	{
		if($this->uri->segment(4))
		{
			$urlorderid=$this->uri->segment(4);
			if(intval($urlorderid) > 0)
			{
				$type='laundry';
				$baginfo=$this->orders_model->getBagBarcodeinfo($urlorderid,$type);
				$str='';
				if($baginfo)
				{
					$bag=$baginfo['id'];
					$binfo=$this->orders_model->getBagOrder($bag);
					$orderid=$binfo['order'];
					$blogid=$binfo['id'];
					$orderinfo= $this->process_order_model->getOrders($bag,$orderid,$blogid);
					
				}
				else
				{
					$binfo=$this->process_order_model->getHeatBarcodeinfo($urlorderid);
					if($binfo)
					{
						$bag=$binfo['bag'];
						$orderid=$binfo['order'];
						$blogid=$binfo['id'];
						$orderinfo = $this->process_order_model->getOrders($bag,$orderid,$blogid);
					}
					else
					{
				
						$orderinfo = $this->orders_model->getOrderinfo($urlorderid);
					}
				}
				
			
				$cus_id=$orderinfo['customer'];
				$data=$this->customer_model->getCustomerinfo($cus_id);
				$mobileinfo=$this->customer_model->customermobileinfo($data['mobile']);	
				$newdata = array('customer'  => $data);
				$this->session->set_userdata($newdata);
				$newdata = array('pos_customer_id'  => $cus_id);
				$this->session->unset_userdata('pos_customer_id');
				$this->session->set_userdata($newdata);
			}
				
				
				
		}
	}
	
	 /* utlevering ajax refresh once done payment*/
	 function __utleveringinfo($flag='')
	 {
		  //profiling
        $this->data['controller_profiling'][] = __function__;
		
		$customer_id = $this->session->userdata['pos_customer_id']; //get  customer id from session
		
		
		  
		//get results for orders ready to deliver today
		$this->data['reg_blocks'][] = 'ready';
		$this->data['blocks']['ready'] = $this->orders_model->getCustomerOrderhistory($customer_id,'utlevering');
		

		$this->data['debug'][] = $this->orders_model->debug_data;
		$this->data['vars']['order_count'] =  count($this->data['blocks']['ready']);
		$ordersid=$this->uri->segment(4);
		$this->data['visible']['utorder_status'] = 0;
		
		if(intval($ordersid) > 0)
		{
			$this->data['visible']['utorder_status'] = 1;
			//$this->data['vars']['utorder_count']=intval($ordersid);
			$this->data['vars']['utorder_count']= sprintf('%08d', intval($ordersid));
		}
		$str ='';
		if(count($this->data['blocks']['ready']) > 0){
		for($i=0;$i<count($this->data['blocks']['ready']);$i++){
			
			
			 $result = $this->orders_model->validateTodaydelivery($this->data['blocks']['ready'][$i]['id']);
			 

			 if($result){		  
			
			    $order_id = $this->data['blocks']['ready'][$i]['id'];
				$this->data['reg_blocks'][] = 'orderline';
				$this->data['blocks']['orderline'] = $this->orders_model->getOrderLine($order_id);
				$this->data['debug'][] = $this->orders_model->debug_data;
				$arr = array();
				for($j=0;$j<count($this->data['blocks']['orderline']);$j++){
					$ptype = $this->data['blocks']['orderline'][$j]['ptype'];
					if ((!in_array($ptype, $arr)) || ($j==0)){
						$arr[] = $ptype;
					}
					$gtype = ucwords(implode(', ',$arr));
				}

			
			  	$amount = ($this->data['blocks']['ready'][$i]['changed_amount']!='') ? $this->data['blocks']['ready'][$i]['changed_amount'] :  $this->data['blocks']['ready'][$i]['total_amount'];
			  
			  	$opstatus = $this->data['blocks']['ready'][$i]['payment_status'];
			  	
			  	$color = ($opstatus=='canceled') ? 'red' : (($opstatus == 'paid') ?  'green' : 'orange');	
			  
				
				$str .='<div class="orderlisting row" id="order_'.$this->data['blocks']['ready'][$i]['id'].'">';
				$str .='<div class="col-md-1" style="width:1%;"><input type="checkbox" onclick="orderchkbox();" name="orderchkbox[]" id="box_'.$this->data['blocks']['iporders'][$i]['id'].'" value="'.$this->data['blocks']['iporders'][$i]['id'].'" /></div>';
				
				$str .='<a href="javascript:void(0);" onclick="ajaxutleveringedit('.intval($this->data['blocks']['ready'][$i]['id']).')" rel="'.intval($this->data['blocks']['ready'][$i]['id']).'">
				  <div class="col-md-2" id="orderinfo_'.intval($this->data['blocks']['iporders'][$i]['id']).'"> #'.$this->data['blocks']['ready'][$i]['id'].' </div>
				  <div class="col-md-1"> '.$this->data['blocks']['ready'][$i]['odate'].' </div>
				  <div class="col-md-2"> '.date('d.m.Y').' </div>
				  <div class="col-md-2">'.$gtype.' </div>
				  <div class="col-md-2">kr '.formatcurrency($amount).' </div>
				  <div class="col-md-2 text-center"> <div class="'.$color.' paymentstatus"> '.ucfirst($this->data['blocks']['ready'][$i]['payment_status']).'</div> </a></div>
				</div>';
				
			 }
				
		  }//for
		}
		else{
			$str.='<div class="col-md-12 text-center mt-lg mb-lg"><h2>Ingen resultat.</h2></div>';
		}
		  
		  
		  $this->data['lists']['orders_ready'] =  $str;
		 
		 
		//get results for orders in process (orders which are placed , in process)
		$this->data['reg_blocks'][] = 'iporders';
		//$this->data['blocks']['iporders'] = $this->orders_model->getCustomerOrderhistory($customer_id,'utlevering');
		$currentyear=1;//current year
		$currentyearorders1=$this->orders_model->getCustomerOrderhistory($customer_id,'utlevering','',$currentyear,$flag);
		
		$currentyear=2;//current year
		$currentyearorders2=$this->orders_model->getCustomerOrderhistory($customer_id,'utlevering','',$currentyear,$flag);
		
		$currentyear=3;//Previous year
		$currentyearorders3=$this->orders_model->getCustomerOrderhistory($customer_id,'utlevering','',$currentyear,$flag);
		
		$currentyearorders=array_merge($currentyearorders1,$currentyearorders2,$currentyearorders3);
	
		
		$this->data['blocks']['iporders'] = $currentyearorders;
		
		
		
		
		
		$this->data['debug'][] = $this->orders_model->debug_data;
		$str ='';
		if(count($this->data['blocks']['iporders']) > 0){
		  for($i=0;$i<count($this->data['blocks']['iporders']);$i++){
			
			$old[] = $this->data['blocks']['iporders'][$i]['id'];
			
		    $company = ($this->data['blocks']['iporders'][$i]['type'] == 'shop') ?  $this->data['blocks']['iporders'][$i]['partner_branch'] :  $this->data['settings_company']['company_name'];
					
		 //  $branch =  ($company == $this->data['settings_company']['company_name']) ?  '('.substr($company,0,3) .')':  (($company != $this->session->userdata['partner_branch_name']) ? '('.substr($company,0,3).')' :  ''); 
			
			$special_chars = 'ø';// all the special characters you want to check for 
			if (preg_match('/'.$special_chars.'/', $company))
			{
				$companyinitial=substr($company,0,4);
			}
			else
			{
				$companyinitial=substr($company,0,3);
			}
			$branch =  ($company == $this->data['settings_company']['company_name']) ?  '('.$companyinitial.')':  (($company != $this->session->userdata['partner_branch_name']) ? '('.$companyinitial.')' :  '');
			
			$orderline_today_delivery = $this->orders_model->validateTodaydelivery($this->data['blocks']['iporders'][$i]['id']);
			
			if($orderline_today_delivery)
			{
				//continue;
			}
			
			if($i==(count($this->data['blocks']['iporders'])-1)){
			
				$firstdelivery = $this->orders_model->getShopdeliverydate($this->data['blocks']['iporders'][$i]['id'],$old,'ASC','process');
			
			}
			
			$result = $this->orders_model->validateOrderlinedelivery('order',$this->data['blocks']['iporders'][$i]['id']);
			
			if($result)
			{
			
				//product types 
				$order_id = $this->data['blocks']['iporders'][$i]['id'];
				$this->data['reg_blocks'][] = 'orderline';
				$this->data['blocks']['orderline'] = $this->orders_model->getOrderLine($order_id);
				$this->data['debug'][] = $this->orders_model->debug_data;
				
				//print_r($this->data['blocks']['orderline']);
				
				$order_from=$this->data['blocks']['iporders'][$i]['type'];
				if($order_from == 'shop')
				{
					$this->data['reg_fields'][] = 'employee';
					$this->data['fields']['employee'] =  $this->employee_model->getEmployeeDetail($this->data['blocks']['iporders'][$i]['employee']);
				}
				
				for($j=0;$j<count($this->data['blocks']['orderline']);$j++){
					
					$olineid=$this->data['blocks']['orderline'][$j]['id'];
					
					$orderline_result = $this->orders_model->validateOrderlinedelivery('orderline',$olineid);
					if($orderline_result)
					{
					
					
						$nextdeliverydate=$this->data['blocks']['orderline'][$j]['p_b_delivery_time'];
						
						if($nextdeliverydate == '')
						{
							$nextdeliverydate= $this->data['blocks']['iporders'][$i]['odate'];
						}
						$orlinestatus=1;
						break;
					}
					
				}
				
				
				$arr = array();
				$myarr = array();
				$carr = array();
				$artikler = '';
				$prod_name = array();
				$temp = '';
				for($j=0;$j<count($this->data['blocks']['orderline']);$j++){
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
				
				  $amount = ($this->data['blocks']['iporders'][$i]['changed_amount']!='') ? $this->data['blocks']['iporders'][$i]['changed_amount'] :  $this->data['blocks']['iporders'][$i]['total_amount'];
				  
				  $opstatus = $this->data['blocks']['iporders'][$i]['payment_status'];
				  
				  $color = ($opstatus=='canceled') ? 'red' : (($opstatus == 'paid') ?  'green' : 'orange');	
				  
				  $status = ($opstatus=='canceled') ? 'C' : (($opstatus == 'paid') ?  'BE' : 'UB');	
				  		  
					
					$str .='<div class="orderlisting row" id="order_'.$this->data['blocks']['iporders'][$i]['id'].'" >';
					
					$str .='<div class="col-md-1"><input type="checkbox" onclick="orderchkbox();" name="orderchkbox[]" id="box_'.$this->data['blocks']['iporders'][$i]['id'].'" value="'.$this->data['blocks']['iporders'][$i]['id'].'" /></div>';
					
					
					$str .='<a  href="javascript:void(0);" onclick="ajaxutleveringedit('.intval($this->data['blocks']['iporders'][$i]['id']).')" rel="'.intval($this->data['blocks']['iporders'][$i]['id']).'">
					  <div class="col-md-2" id="orderinfo_'.intval($this->data['blocks']['iporders'][$i]['id']).'"> #'.$this->data['blocks']['iporders'][$i]['id'].' <span class="green-text">'.$branch.'</span></div>
					  <div class="col-md-2 no-padd"> '.$this->data['blocks']['iporders'][$i]['odate'].' </div>
					  <div class="col-md-3"> '.$kategori.' </div>';				  
					  
					  /*$str .='<div class="col-md-1 no-padd"> '.date('d.m.Y',strtotime($nextdeliverydate)).' </div>';*/
					 $str .=' <div class="col-md-3">kr '.formatcurrency($amount).' </div></a>';
					 /*$str .='<div class="col-md-1 text-center"> <div class="'.$color.' paymentstatus"> '.$status.'</div> </div>*/
					 $str .='<div class="col-md-1 text-center"><div title="utsikt" class="bg-purple paymentstatus utsikt" id="'.$this->data['blocks']['iporders'][$i]['id'].'">UT</div></div>';
					$str .='	<div style="display:none;" id="kasserer_'.intval($this->data['blocks']['iporders'][$i]['id']).'">Kasserer: '.$this->data['fields']['employee']['initial'].'</div></div>';				
			}	
		  }//for
		  
		}
		else{
			$str.='<div class="col-md-12 text-center mt-lg mb-lg"><h2>Ingen resultat.</h2></div>';
		}
		  
		  
		  
		  $orders_in_process =  $str;
		  
		  if($flag == '')
		  {
					//get results for orders delivered
				$this->data['reg_blocks'][] = 'delivered';
				$this->data['blocks']['delivered'] = $this->orders_model->getCustomerOrderhistory($customer_id,'delivered');
				$this->data['debug'][] = $this->orders_model->debug_data;
				$str ='';
				if(count($this->data['blocks']['delivered']) > 0){

				for($i=0;$i<count($this->data['blocks']['delivered']);$i++){
					
					 $orderline_today_delivery = $this->orders_model->validateTodaydelivery($this->data['blocks']['delivered'][$i]['id']);
				
					$old[] = $this->data['blocks']['delivered'][$i]['id'];
					
					$company = ($this->data['blocks']['delivered'][$i]['type'] == 'shop') ?  $this->data['blocks']['delivered'][$i]['partner_branch'] :  $this->data['settings_company']['company_name'];
							
				 //  $branch =  ($company == $this->data['settings_company']['company_name']) ?  '('.substr($company,0,3) .')':  (($company != $this->session->userdata['partner_branch_name']) ? '('.substr($company,0,3).')' :  ''); 
				 $special_chars = 'ø';// all the special characters you want to check for 
					if (preg_match('/'.$special_chars.'/', $company))
					{
						$companyinitial=substr($company,0,4);
					}
					else
					{
						$companyinitial=substr($company,0,3);
					}
					$branch =  ($company == $this->data['settings_company']['company_name']) ?  '('.$companyinitial.')':  (($company != $this->session->userdata['partner_branch_name']) ? '('.$companyinitial.')' :  '');
					
				
					if($i==(count($this->data['blocks']['delivered'])-1)){
						$lastdelivery = $this->orders_model->getShopdeliverydate($this->data['blocks']['delivered'][$i]['id'],$old,'DESC');
					}
				
					//product types 
					$order_id = $this->data['blocks']['delivered'][$i]['id'];
					$this->data['reg_blocks'][] = 'orderline';
					$this->data['blocks']['orderline'] = $this->orders_model->getOrderLine($order_id);
					$this->data['debug'][] = $this->orders_model->debug_data;
					$nextdeliverydate=$this->data['blocks']['delivered'][$i]['odate'];
					
					for($j=0;$j<count($this->data['blocks']['orderline']);$j++){
							
							$olineid=$this->data['blocks']['orderline'][$j]['id'];
							if($this->data['blocks']['orderline'][$j]['p_b_delivery_time'] != '')
							{
								$nextdeliverydate=$this->data['blocks']['orderline'][$j]['p_b_delivery_time'];
							}
					}
					
					
					$arr = array();
					$myarr = array();
					$artikler = '';
					$carr = array();
					$prod_name = array();
					for($j=0;$j<count($this->data['blocks']['orderline']);$j++){
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
							$prod_name[$j] = substr($myarr['name'],0,2);
						}
						
					}
					
					$kategori = ucwords(implode(', ',$carr));
					$kategori = $this->general_model->trim_text($kategori);

					$artikler = implode(', ',$prod_name);
					$artikler = $this->general_model->trim_text($artikler);
					
						$order_from=$this->data['blocks']['delivered'][$i]['type'];
						if($order_from == 'shop')
						{
							$this->data['reg_fields'][] = 'employee';
							$this->data['fields']['employee'] =  $this->employee_model->getEmployeeDetail($this->data['blocks']['delivered'][$i]['employee']);
						}

					
					  $amount = ($this->data['blocks']['delivered'][$i]['changed_amount']!='') ? $this->data['blocks']['delivered'][$i]['changed_amount'] :  $this->data['blocks']['delivered'][$i]['total_amount'];
					  
					  $opstatus = $this->data['blocks']['delivered'][$i]['payment_status'];
					  
					  $color = ($opstatus=='canceled') ? 'red' : (($opstatus == 'paid') ?  'green' : 'orange');	
					  
					  $status = ($opstatus=='canceled') ? 'C' : (($opstatus == 'paid') ?  'BE' : 'UB');	
							  
						
						$str .='<div class="orderlisting row" id="order_'.$this->data['blocks']['delivered'][$i]['id'].'" >';
						//$str .='<div class="col-md-1" style="width:1%;"><input type="checkbox" onclick="orderchkbox();" name="orderchkbox[]" id="box_'.$this->data['blocks']['iporders'][$i]['id'].'" value="'.$this->data['blocks']['iporders'][$i]['id'].'" /></div>';
						
						$str .='<a  href="javascript:void(0);" onclick="ajaxutleveringedit('.intval($this->data['blocks']['delivered'][$i]['id']).')" rel="'.intval($this->data['blocks']['delivered'][$i]['id']).'">
						
						  <div class="col-md-2" id="orderinfo_'.intval($this->data['blocks']['delivered'][$i]['id']).'"> #'.$this->data['blocks']['delivered'][$i]['id']. ' <span class="green-text">'.$branch.'</span></div>
						  <div class="col-md-2 no-padd"> '.$this->data['blocks']['delivered'][$i]['odate'].' </div>
						  <div class="col-md-2 "> '.$kategori.'</div>
						  <div class="col-md-2 no-padd"> '.date('d.m.Y',strtotime($nextdeliverydate)).' </div>
						  <div class="col-md-2">kr '.formatcurrency($amount).' </div>
						  <div class="col-md-1 text-center"> <div class="'.$color.' paymentstatus"> '.$status.'</div></div></a><div class="col-md-1 text-center"><div title="utsikt" class="bg-purple paymentstatus utsikt" id="'.$this->data['blocks']['delivered'][$i]['id'].'">UT</div></div><div style="display:none;" id="kasserer_'.intval($this->data['blocks']['delivered'][$i]['id']).'">Kasserer: '.$this->data['fields']['employee']['initial'].'</div>
						</div>';				
						
				  }//for
				  
				}
				else{
					$str.='<div class="col-md-12 text-center mt-lg mb-lg"><h2>Ingen resultat.</h2></div>';
				}
				  
				  
				  $orders_delivered =  $str;
		  }
		 
		  
		

		
		if(count($this->data['fields']['orders'])> 0){
			$this->data['visible']['wi_customer_orders'] = 1;
		}
		else{
			$this->data['visible']['wi_orders_none'] = 0;
		}
		
		$html='<h2 class="title">Aktuelle</h2>
           <div class="finished_orders order-black aktuelle">
                <div class="orderlisting row" style="background:#d8d8d8">';
					 $html .='<div class="col-md-1"></div>';
				$html .='<div class="col-md-2 black-text bold">Ordre nr. </div>
					  <div class="col-md-2 black-text no-padd bold">Inn</div>
					  <div class="col-md-3 black-text  bold"> Kategorier </div>
					  <div class="col-md-3 black-text bold">Totalt</div>
					  <div class="col-md-1 text-center black-text bold">Print</div>
					</div>           
           '.$orders_in_process.'
          </div>';
		  
		  if($flag == '')
		  {

           $html .='<h2 class="title">Utlevert</h2>
           <div class="finished_orders order-black">
             <div class="orderlisting row">
				 <div class="col-md-2 black-text bold">Ordre nr. </div>
				 <div class="col-md-2 black-text no-padd bold">Inn</div>
				 <div class="col-md-2 black-text  bold"> Kategorier </div>
				 <div class="col-md-2 black-text no-padd  bold"> Ferdig </div>
				 <div class="col-md-2 black-text  bold">Totalt</div>
				 <div class="col-md-1 text-center black-text bold">Status</div>
				 <div class="col-md-1 text-center black-text bold">Print</div>
                 
             </div>           
			'.$orders_delivered.'
          </div>';
		 } 
          $html .='<div class="clearfix"></div>';
		 
		$result = array("order_details"=>$html);
		echo json_encode($result);exit;
	 }
	 
	 function __getpayorderdetails()
	 {
	
		
		$htmlout='<div class="orderlisting row">
				 <div class="col-md-4 black-text bold no-padd">Ordre nr. </div>
				 <div class="col-md-4 black-text text-left bold">Totalt</div>
				 <div class="col-md-4 black-text text-right bold no-padd">Utestående</div>
				</div>';
		$orderamtarray=array();
		if(count($_POST['orders']) > 0)
		{	
			foreach($_POST['orders'] as $orderid)
			{
			
				$orderinfo=$this->process_order_model->getOrderInfo($orderid);
				
				$htmlout.='<div class="orderlisting row">
				 <div class="col-md-4 black-text bold no-padd">#'.$orderid.'</div>';
				 $htmlout.='<div class="col-md-4 black-text  bold">kr '.$orderinfo['total_amount_currency'].'</div>';
				 if($orderinfo['betalstatus'] == 1)
				 {
					if(intval($orderinfo['balanceamount']) > 0)
					{
						$orderamtarray[]=$orderinfo['balance_amount'];
						$htmlout.='<div class="col-md-4 black-text text-right no-padd bold">kr '.$orderinfo['balanceamount'].'</div>';
					}
					else
					{
						$orderamtarray[]=$orderinfo['total_amount'];
						$htmlout.='<div class="col-md-4 black-text text-right no-padd bold">kr '.$orderinfo['total_amount_currency'].'</div>';
					}
					
					
				 }
				 else
				 {
					$htmlout.='<div class="col-md-4 black-text text-right no-padd bold">kr 0,00</div>';
				 }
				 
				$htmlout.='</div>';
			}
			
			
		}
		$orderamt=array_sum($orderamtarray);
                
                
if($orderamt > 0 ){
        $htmlout.='<div class="row">
                <div class="pull-left col-md-12 no-padd text-right">
                    <div class="saldo-button1 group-pos">
                        <div class="btn-group pull-right">
                            <button type="button" id="pay_cash" onclick="paymenttype(\'cash\')" data-switch-set="#discount_type12" data-switch-value="Kontant" class="btn btn-default payment_type">Kontant</button>
                            <button type="button" id="pay_invoice" onclick="paymenttype(\'invoice\')" data-switch-set="#discount_type12" data-switch-value="Faktura" class="btn btn-default payment_type">Faktura</button>
                            <button type="button"  id="pay_visa"   onclick="paymenttype(\'visa\')"  data-switch-set="#discount_type12" data-switch-value="Kort" class="btn btn-default payment_type">Kort</button>
                        </div>
                        <input type="hidden" name="opay_type" id="opay_type">
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>';
    }
 
                
                
		$result = array("order_details"=>$htmlout,'order_amount'=>'Kr.'.formatcurrency($orderamt));
		echo json_encode($result);exit;
	 }
	 
	 function __printTag()
	 {
		  if($this->session->userdata['partner_branch'] =='1')
		  {
			 $this->data['lists']['printtag']='';
			 return true;
		  }
		$orderlines= array();
		$orderid = $this->uri->segment(4);
		//echo $_GET['orderlines'];
		
		if(isset($_GET['orderlines']) && $_GET['orderlines']!='')
		$orderlines= explode(',',$_GET['orderlines']);
		$lines=$this->orders_model->getOrderLine($orderid);
		$orderinfo = $this->orders_model->getOrderinfo($orderid);
		$this->data['reg_fields'][] = 'order';
		$this->data['fields']['order'] = $orderinfo;
		
		
		$customer = $this->orders_model->getCustomerDetails($orderid);
		$weekdayarray=array("Mon"=>"Man","Tue"=>"Tirs","Wed"=>"Ons","Thu"=>"Tors","Fri"=>"Fre","Sat"=>"lør","Sun"=>"Søn");
		
		
		/*$partnerinfo = $this->general_model->getPartnerDetails($orderinfo['partner'],$orderinfo['partner_branch']);
		$companynamearray = explode(',',$partnerinfo['partner_name']);
		
		$companyname='';
		
		if(count($companynamearray) > 0)
		{
			$i=1;
			foreach($companynamearray as $cname)
			{
				$companyname.=substr($cname, 0, 1);
				
				if($i == 2)
				{
					break;
				}
				
				$i++;
			}
		}*/
		
        $order_from = $orderinfo['type'];
		
        if($order_from == 'shop'){
            
            $partnerinfo = $this->general_model->getPartnerDetails($orderinfo['partner'],$orderinfo['partner_branch']);
            
            $company = $partnerinfo['name'];
            $address = $partnerinfo['street'] .", ". $partnerinfo['zip']. ' '.$partnerinfo['city'];
            $phone = $partnerinfo['phone']; 
            

        }
        else{
            $company = $this->data['settings_company']['company_name'];
            $address = $this->data['settings_company']['company_address_street'] .", ". $this->data['settings_company']['company_address_zip']. ' '.$this->data['settings_company']['company_address_city'];
            $phone = $this->data['settings_company']['company_telephone']; 
        }
		
		$companyname = mb_substr($company,0,2);
		
		//($orderinfo['partner_branch'];
		//$orderinfo['order_time']
		
		$htmlout='';
		if(count($lines) > 0)
		{
			$total_qty = 0;
			
			foreach($lines as $olineitmes)
			{
				
				if(!empty($orderlines)){
					
					
					if(in_array($olineitmes['id'],$orderlines))
					{
						 
						 if($olineitmes['product'] !=1){  //exclde Skjorte bec'z Skjorte has only heatseal not tag
							 $q = ($olineitmes['changed_quantity'] == '') ? $olineitmes['quantity']:$olineitmes['changed_quantity'];
							
							 $prodtype=$this->process_order_model->validateProducttype($olineitmes['product']);
							 
							  if($orderdetails[$i]['in_meter'] == 1)
							  {
								$actualqty=1;
							  }
							  else
							  {
								$actualqty=$prodtype*$q;
							  }
								
								$q =intval($actualqty);
							
								$total_qty += intval($q);
						 }
					}
				}
				else{
					
						 if($olineitmes['product'] !=1){  //exclde Skjorte bec'z Skjorte has only heatseal not tag
					
							 $q = ($olineitmes['changed_quantity'] == '') ? $olineitmes['quantity']:$olineitmes['changed_quantity'];
							
							 $prodtype=$this->process_order_model->validateProducttype($olineitmes['product']);
							 
							  if($orderdetails[$i]['in_meter'] == 1)
							  {
								$actualqty=1;
							  }
							  else
							  {
								$actualqty=$prodtype*$q;
							  }
								
							  $q =intval($actualqty);
							
							  $total_qty += intval($q);
						  
						 }
						
				}

			}
			
	
			
			$item = 1;
			foreach($lines as $olineitmes)
			{
			if(!empty($orderlines)){
				
				if(in_array($olineitmes['id'],$orderlines))
				{
			
					$arr = $this->orders_model->getProductDisplayName($olineitmes['product']);
					$olineitmes['name'] = $arr['name'];
			
			
					if($olineitmes['product'] !=1){  //exclde Skjorte bec'z Skjorte has only heatseal not tag
						$ready='';
						if($olineitmes['p_b_delivery_time'] == '')
						{
							if($orderinfo['delivery_time'] != '')
							{
								$deliveryinfo=$this->orders_model->getCollectionDeliverytime('delivery',$orderinfo['delivery_time']);
								$rdate=$deliveryinfo['sdate'];
								$ready=date('d/m',strtotime($deliveryinfo['sdate']));
							}
						}
						else
						{
							$rdate=$olineitmes['p_b_delivery_time'];
							$ready=date('d/m',strtotime($olineitmes['p_b_delivery_time']));
						}
						$weekday='';
						if($ready != '')
						{
							$day=date('D',strtotime($rdate));
							$weekday=strtolower($weekdayarray[$day]);
						}
						
						$qty =($olineitmes['changed_quantity'] == '') ? $olineitmes['quantity']:$olineitmes['changed_quantity'];
					
					 //$barcodes = $this->orders_model->orderlineHeatseal($olineitmes['id']);
					 $prodtype=$this->process_order_model->validateProducttype($olineitmes['product']);
					 
					  if($orderdetails[$i]['in_meter'] == 1)
					  {
						$actualqty=1;
					  }
					  else
					  {
						$actualqty=$prodtype*$qty;
					  }
						
						$qty =intval($actualqty);
						
						
						if($qty > 0)
						{
							foreach(range(1,$qty) as $qtys)
							{
								$htmlout.='<div class="print"  id="tag'.$olineitmes['id'].$qtys.'">
						  <table width="100%"  align="center" border="0" cellpadding="0" cellspacing="0">
							<tbody>
							  <tr>
								<td width="50%" valign="top" align="left" style="padding:0 0 0px 0;">
								<ul style="margin:0; padding: 0; font-family: \'arial\', monospace;font-weight: normal">
									<li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 50px; font-weight: normal; color: #000; list-style: none;">'.$orderid.'</li></ul>  
								</td>    
								<td width="50%" valign="top" align="left" style="padding:0 0 4px 0;"><ul style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal">
				
									<li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal; color: #000; list-style: none; line-height:30px">'.$orderid.' '.$companyname.' '.$partnerinfo['id'].'</li>
									<li style="margin:0; padding: 0 0 0px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal; color: #000; list-style: none; line-height:30px">'.$customer['customer_name'].'</li>
								</ul>     
							   </td>
							   </tr>
								<tr>
								<td width="50%" valign="top" align="left" style="padding:0 0 0px 0;">
								<ul style="margin:0; padding: 0; font-family: \'arial\', monospace;font-weight: normal">
									<li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 50px; font-weight: normal; color: #000; list-style: none;">'.$partnerinfo['id'].':'.$ready.'</li></ul>
								</td>    
								<td width="50%" valign="top" align="left" style="padding:0 0 0px 0;" ><ul style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal">
									<li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal; color: #000; list-style: none;line-height:30px">I '.date('d/m',strtotime($orderinfo['order_time'])).'</li>
									<li style="margin:0; padding: 0 0 5px 0;  font-family: \'arial\', monospace; font-size: 30px; font-weight: normal; color: #000; list-style: none;line-height:30px">Rdy '.$ready.'</li>
								</ul>     
							   </td>
							   </tr>
							<tr>
								<td width="50%" valign="top" align="left" style="padding:0 0 0px 0;">
								<ul style="margin:0; padding: 0; font-family: \'arial\', monospace;font-weight: normal">
									<li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 50px; font-weight: normal; color: #000; list-style: none;">'.$weekday.':'.$item.'/'.$total_qty.'</li></ul>
								</td>    
								<td width="50%" valign="top" align="left" style="padding:0 0 0px 0;" ><ul style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal">
									<li style="margin:0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal; color: #000; list-style: none;line-height:30px">'.$olineitmes['name'].'</li>
									<li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal; color: #000; list-style: none;line-height:30px">'.$customer['number'].'</li>
								</ul>     
							   </td>
							   </tr>
									  
							</tbody>
						  </table>
						</div><div class="page-break"></div>';
						
						$item ++;
					
						}
					}
				
					}
				}	
				
			 }
			 else{
				 
				if($olineitmes['product'] !=1){  //exclde Skjorte bec'z Skjorte has only heatseal not tag
					$ready='';
					if($olineitmes['p_b_delivery_time'] == '')
					{
						if($orderinfo['delivery_time'] != '')
						{
							$deliveryinfo=$this->orders_model->getCollectionDeliverytime('delivery',$orderinfo['delivery_time']);
							$rdate=$deliveryinfo['sdate'];
							$ready=date('d/m',strtotime($deliveryinfo['sdate']));
						}
					}
					else
					{
						$rdate=$olineitmes['p_b_delivery_time'];
						$ready=date('d/m',strtotime($olineitmes['p_b_delivery_time']));
					}
					$weekday='';
					if($ready != '')
					{
						$day=date('D',strtotime($rdate));
						$weekday=strtolower($weekdayarray[$day]);
					}
					
					$qty =($olineitmes['changed_quantity'] == '') ? $olineitmes['quantity']:$olineitmes['changed_quantity'];
				
				 //$barcodes = $this->orders_model->orderlineHeatseal($olineitmes['id']);
				 $prodtype=$this->process_order_model->validateProducttype($olineitmes['product']);
				 
				  if($orderdetails[$i]['in_meter'] == 1)
				  {
					$actualqty=1;
				  }
				  else
				  {
					$actualqty=$prodtype*$qty;
				  }
					
					$qty =intval($actualqty);
					
					
					if($qty > 0)
					{
						foreach(range(1,$qty) as $qtys)
						{
							$htmlout.='<div class="print" id="tag'.$olineitmes['id'].$qtys.'">
					  <table width="100%"  align="center" border="0" cellpadding="0" cellspacing="0">
						<tbody>
						  <tr>
							<td width="50%" valign="top" align="left" style="padding:0 0 0px 0;">
							<ul style="margin:0; padding: 0; font-family: \'arial\', monospace; font-weight: normal">
								<li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 50px; font-weight: normal; color: #000; list-style: none;">'.$orderid.'</li></ul>  
							</td>    
							<td width="50%" valign="top" align="left" style="padding:0 0 4px 0;"><ul style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal">
			
								<li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal; color: #000; list-style: none; line-height:30px">'.$orderid.' '.$companyname.' '.$partnerinfo['id'].'</li>
								<li style="margin:0; padding: 0 0 0px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal; color: #000; list-style: none; line-height:30px">'.$customer['customer_name'].'</li>
							</ul>     
						   </td>
						   </tr>
							<tr>
							<td width="50%" valign="top" align="left" style="padding:0 0 0px 0;">
							<ul style="margin:0; padding: 0; font-family: \'arial\', monospace;font-weight: normal">
								<li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 50px; font-weight: normal; color: #000; list-style: none;">'.$partnerinfo['id'].':'.$ready.'</li></ul>
							</td>    
							<td width="50%" valign="top" align="left" style="padding:0 0 0px 0;" ><ul style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal">
								<li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal; color: #000; list-style: none;line-height:30px">I '.date('d/m',strtotime($orderinfo['order_time'])).'</li>
								<li style="margin:0; padding: 0 0 5px 0;  font-family: \'arial\', monospace; font-size: 30px; font-weight: normal; color: #000; list-style: none;line-height:30px">Rdy '.$ready.'</li>
							</ul>     
						   </td>
						   </tr>
						<tr>
							<td width="50%" valign="top" align="left" style="padding:0 0 0px 0;">
							<ul style="margin:0; padding: 0; font-family: \'arial\', monospace; font-weight: normal">
								<li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 50px; font-weight: normal; color: #000; list-style: none;">'.$weekday.':'.$item.'/'.$total_qty.'</li></ul>
							</td>    
							<td width="50%" valign="top" align="left" style="padding:0 0 0px 0;" ><ul style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal">
								<li style="margin:0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal; color: #000; list-style: none;line-height:30px">'.$olineitmes['name'].'</li>
								<li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 30px; font-weight: normal; color: #000; list-style: none;line-height:30px">'.$customer['number'].'</li>
							</ul>     
						   </td>
						   </tr>
								  
						</tbody>
					  </table>
					</div><div class="page-break"></div>';
					
					$item ++;
					
					}
					
					}
				}
				 
			 }
				 $this->data['lists']['printtag'] = $htmlout;

			}
		}
		
		//echo '<pre>';print_r($lines);exit;
		
	 }
	 
	 function __cancelorderlines()
	 {
		$corderlines=$_POST['corderlines'];
		$orderid=$_POST['eorder_id'];
		$totalorderline=intval($_POST['totalorderline']);
		$checkedoderline=intval($_POST['checkedoderline']);
		$orderdetails = $this->orders_model->getOrderLine($orderid);
		$cancelreason=$_POST['cancelreason'];
		$comments=$_POST['comments'];
		if(count($corderlines) > 0)
		{	
			if(count($corderlines) == count($orderdetails))
			{
				$this->process_order_model->cancelorderline($orderid,0,'pending',$cancelreason,$comments);
				$result = array("status"=>'success','orderid'=>$orderid,"message"=>"Order cancel request has been sent successfully.");
			}
			else
			{
				foreach($corderlines as $oline)
				{	
					$this->process_order_model->cancelorderline($orderid,$oline,'pending',$cancelreason,$comments);
				}
				$result = array("status"=>'success','orderid'=>$orderid,"message"=>"Orderlines cancel request has been sent successfully.");
			}
			
			//foreach($corderlines as $oline)
			//{	
			
				/*
				//echo $oline
				$orderline=$this->orders_model->getOrderlineinfo($orderid,$oline);
				$oid = $orderid;
				$olid = $oline;
				$qty = 0;
				$price = $orderline['price'];
				$total = $orderline['order_total_amount'];
				$complain = $orderline['complain'];
				$in_house = $orderline['in_house'];
				$desp = $orderline['special_instruction'];
				$price = ($complain!='1')  ?   $price : 0;
				
				$ocancel = ($totalorderline == $checkedoderline) ? '' : 'canceled';
				
				$data = $this->orders_model->updateOrderline($olid,$oid,$qty,$price,$total,$complain,$in_house,$desp,'canceled',$ocancel);
				
				$heatdata = $this->orders_model->orderlineHeatseal($olid);
				if(count($heatdata) > 0)
				{
					$this->orders_model->updateHeatsealstatus($heatdata,'19');//canceled
				}
				//SELECT * FROM `a_heat_seal_log` WHERE orderline='25' AND status='started' group by heat_seal
				*/
			
			//}
		
			
			/*$orderdetails = $this->orders_model->getOrderLine($orderid);
			if(count($orderdetails) > 0)
			{
				$status=0;
				foreach($orderdetails as $oitems)
				{
					if($oitems['payment_status'] == 'canceled' && $oitems['changed_quantity'] != '')
					{
						$status++;
					}
					
				}
				
				if($status == count($orderdetails))
				{
					$this->orders_model->updateorderlog($orderid,11);
				}
			}*/
			
		}
		else
		{
			$result = array("status"=>'error',"message"=>"Invalid");
		}
		
		echo json_encode($result);exit;
		
	 }
	 
	 function __paymentMultiOrder($self=false,$orderid=0)
	 {
		$orderamtarray=array();
        $in_type = $_POST['opay_type'];
		
		if(strtolower($in_type) == 'visa')
		{
			$payment_type='visa';
		}
		if(strtolower($in_type) == 'cash')
		{
			$payment_type='cash';
		}
		if(strtolower($in_type) == 'invoice')
		{
			$payment_type='invoice';
		}
		
		if($self)
		{
			$_POST['orders']=array();
			$_POST['orders'][$orderid]=$orderid;
		}
	
		
		$customer=$this->session->userdata['customer']['id'];
		if(count($_POST['orders']) > 0)
		{	
			foreach($_POST['orders'] as $order_id)
			{
			
				$orderid=$order_id;
				$orderinfo=$this->process_order_model->getOrderInfo($order_id);
				$data = $this->orders_model->getOrderLine($order_id);
				$totalorderline=count($data);
				$neworderlines=array();
				$orderlineamtarray=array();
				$orderpaymentarray=array();
				$corderlines=array();
				for($j=0;$j<count($data);$j++)
				{
					$price =  '';
					if(trim($data[$j]['changed_amount']) != '')
					{
						$price = $data[$j]['changed_amount'];
						$data[$j]['quantity'] = $data[$j]['changed_quantity'];
					}
					else
					{
						$price = $data[$j]['amount'];
					}
					$subtotal = $price;
					$productPrice=$subtotal;
					$productPrice=round($productPrice);
						
					
					
					$subtotalarray[]= $productPrice;
					
					if($data[$j]['payment_status'] == 'pending')
					{
						$corderlines[]=$data[$j]['id'];
					}
					$orderlineamtarray[$data[$j]['id']]=$productPrice;
					$orderpaymentarray[$data[$j]['id']]=$data[$j]['payment_status'];
				
				}
				
				
				$amttarray=array();
				foreach($orderlineamtarray as $orderlineamt)
				{
					$amttarray[]=$orderlineamt;
				}
				$order_amount=array_sum($amttarray);
				$discount=0;
		
				if(intval($orderinfo['voucher']) > 0)
				{
					$qry="SELECT * FROM a_voucher WHERE id='".$orderinfo['voucher']."'";
					$query = $this->db->query($qry);
					$vdata = $query->row_array();//multi row array
					$vouchercode=$vdata['voucher'];
					if($vdata['percentage'] != '')
					{
						$percentage=$vdata['percentage']/100;
						$discount=$order_amount*$percentage;
					}
					else
					{
						$discount=$vdata['price'];
					}
					if($vdata['free_delivery_charge'] == 1)
					{
						$delviery=0;
					}
					else
					{
						$delviery=$old_delivery_charge;
					}
					$discount=$discount;
				}		
				else
				{
					
					
						if($orderinfo['order_discount'] !='')
						{
							$vouchercode = $orderinfo['order_discount'];
							if(stripos($vouchercode, '%'))
							{
								$percentage = str_replace("%","",$vouchercode);
								
								$discount =  $order_amount * ($percentage/100);
							}
							else
							{
								$discount = str_replace("kr ","",$vouchercode);
							}
						
						}
					
				}
			
				$customer=$this->session->userdata['customer']['id'];
				
				
				if(count($corderlines) > 0)
				{	
				
			
					if($totalorderline == count($corderlines))
					{
						$amtarray=array();
						foreach($corderlines as $orderline)
						{
							$amtarray[]=$orderlineamtarray[$orderline];
						}
						$orderamount=array_sum($amtarray);
						
						$orderamount=$orderamount-$discount;
						
						$in_status='paid';
						//$saldostatus=1;
                        $cus_id =$this->session->userdata['customer']['id'];
						$saldostatus = $this->payments_model->getSaldostatus($cus_id);
						if($saldostatus)
						{
						
							
							$data = $this->payments_model->getAccountBalance($cus_id);
							$autofillamt=$this->customer_model->getAutofileamt($cus_id);
							if(intval($data['paid']) < 0)
							{
								if(intval($autofillamt) > 0)
								{
									$newamt=$autofillamt;
									$newpaid=abs($data['paid'])+$orderamount;
									if(intval($data['pending']) > $orderamount && $newpaid < intval($data['pending']))
									{
										$newamt=0;
									}
																		
								}
								else
								{
									$newamt=$orderamount;
								}
								$paymentarray=array(
								'type'=>'in',
								'in_type'=>'invoice',
								'in_status'=>'pending',
								'customer'=>$cus_id,
								'amount'=>$newamt,
								'regtime'=>date('Y-m-d H:i:s'));
								$saldoid=$this->payments_model->addCustomerPayment($paymentarray);
								$this->payments_model->updateCustomerBalance($cus_id,$newamt,'pending');
							
							}
							else
							{
								if($orderamount > intval($data['paid']))
								{
									$bal=$orderamount-intval($data['paid']);
									//$newamt=$bal;
									if(intval($autofillamt) > 0)
									{
										$newamt=$autofillamt;	
										$newpaid=abs($data['paid'])+$bal;
										if(intval($data['pending']) > $bal && $newpaid < intval($data['pending']))
										{
											$newamt=0;
										}									
									}
									else
									{
										$newamt=$bal;
									}

									$paymentarray=array(
									'type'=>'in',
									'in_type'=>'invoice',
									'in_status'=>'pending',
									'customer'=>$cus_id,
									'amount'=>$bal,
									'regtime'=>date('Y-m-d H:i:s'));
									$saldoid=$this->payments_model->addCustomerPayment($paymentarray);
									$this->payments_model->updateCustomerBalance($cus_id,$newamt,'pending');
								}
						
							}

						
							
							$in_status='paid';
							$payment_type='account';
							$type='out';
							$paytype=trim($_POST['paytype']);
							$customer=$this->session->userdata['customer']['id'];
							$regtime=date('Y-m-d H:i:s');
							$paymentarray=array(
							'type'=>$type,
							'in_status'=>$in_status,
							'customer'=>$customer,
							'order'=>$orderid,
							'regtime'=>$regtime,
							'amount'=>$orderamount);
							$cpaystatus=$this->payments_model->addCustomerPayment($paymentarray);
							if($cpaystatus)
							{
								$result = $this->payments_model->updateCustomerBalance($customer,$orderamount,'','debit');
							}
							
							
						}
				
						if($saldostatus)
						{
				
							$data = $this->payments_model->getAccountBalance($customer);
							$pendingsaldo = $data['pending'];
							$paidsaldo = $data['paid'];
							
							if($orderamount > $paidsaldo)
							{
								$in_status='paid';
							}
						}
						$this->orders_model->orderlinePayment($orderid,0,$payment_type,$in_status,count($corderlines));
						$orderpaymentstatus=$this->payments_model->updateorderPaymentstatus($orderid);
					
				
					}
					else
					{
			
						$in_discount_status=1;
						if(count($orderpaymentarray) > 0)
						{
							foreach($orderpaymentarray as $payingstatus)
							{
								if($payingstatus == 'paid' || $payingstatus == 'waiting')
								{
									$in_discount_status=0;
								}
							}
						}
				
				
						foreach($corderlines as $orderline)
						{
								$orderlineamount=$orderlineamtarray[$orderline];
								
									if(intval($in_discount_status) > 0)
									{
										if($orderlineamount >= $discount)
										{
											$orderlineamount=$orderlineamount-$discount;
										}
										else
										{
											if($discount >= $orderlineamount)
											{
												$discount=$discount-$orderlineamount;
												$orderlineamount=0;
											}
										}
									}
								
								$data = $this->payments_model->getAccountBalance($customer);
								$pendingsaldo = $data['pending'];
								$paidsaldo = $data['paid'];
								
								if($paidsaldo > $orderlineamount)
								{
									if($saldostatus)
									{
										$customer=$this->session->userdata['customer']['id'];
										$data = $this->payments_model->getAccountBalance($customer);
										$autofillamt=$this->customer_model->getAutofileamt($customer);
										if(intval($data['paid']) < 0)
										{
												if(intval($autofillamt) > 0)
												{
													$newamt=$autofillamt;	
													$newpaid=abs($data['paid'])+$orderlineamount;
														if(intval($data['pending']) > $orderlineamount && $newpaid < intval($data['pending']))
														{
															$newamt=0;
														}
												}
												else
												{
													$newamt=$orderlineamount;
												}
																
												$paymentarray=array(
												'type'=>$type,
												'in_type'=>'invoice',
												'in_status'=>'pending',
												'customer'=>$customer,
												'amount'=>$newamt,
												'regtime'=>date('Y-m-d H:i:s'));
												$saldoid=$this->payments_model->addCustomerPayment($paymentarray);
												$this->payments_model->updateCustomerBalance($customer,$newamt,'pending');
										}
										else
										{
											if($orderlineamount > intval($data['paid']))
											{
												$bal=$orderlineamount-intval($data['paid']);
											//	$newamt=$bal;
											
												if(intval($autofillamt) > 0)
												{
													$newamt=$autofillamt;	
													$newpaid=abs($data['paid'])+$bal;											
														if(intval($data['pending']) > $bal && $newpaid < intval($data['pending']))
														{
															$newamt=0;
														}
												}
												else
												{
													$newamt=$bal;
												}
								
												$paymentarray=array(
												'type'=>$type,
												'in_type'=>'invoice',
												'in_status'=>'pending',
												'customer'=>$customer,
												'amount'=>$bal,
												'regtime'=>date('Y-m-d H:i:s'));
												$saldoid=$this->payments_model->addCustomerPayment($paymentarray);
												$this->payments_model->updateCustomerBalance($customer,$newamt,'pending');
											}
										}
								
								
								
										$in_status='paid';
										$payment_type='account';
										$type='out';
										//$paytype=trim($_POST['paytype']);
										$customer=$this->session->userdata['customer']['id'];
										$regtime=date('Y-m-d H:i:s');
										$paymentarray=array(
										'type'=>$type,
										'in_status'=>$in_status,
										'customer'=>$customer,
										'order'=>$orderid,
										'orderline'=>$orderline,
										'regtime'=>$regtime,
										'amount'=>$orderlineamount);
										//echo '<pre>';print_r($paymentarray);exit;
										$cpaystatus=$this->payments_model->addCustomerPayment($paymentarray);
										if($cpaystatus)
										{
											$result = $this->payments_model->updateCustomerBalance($customer,$orderlineamount,'','debit');
										}
										
										
									}
									else
									{
										if($in_type == 'visa')
										{
											$in_status='paid';
										}
										else
										{
											$in_status='paid';
										}
										
										
									}
								}
								else
								{
									
										if($in_type == 'visa')
										{
											$in_status='paid';
										}
										else
										{
											$in_status='paid';
										}
										
									
										
										if($saldostatus)
										{
											
											$newamt=$orderlineamount;
										$customer=$this->session->userdata['customer']['id'];
										$data = $this->payments_model->getAccountBalance($customer);
										$autofillamt=$this->customer_model->getAutofileamt($customer);
										if(intval($data['paid']) < 0)
										{
												if(intval($autofillamt) > 0)
												{
													$newamt=$autofillamt;	
													$newpaid=abs($data['paid'])+$orderlineamount;
														if(intval($data['pending']) > $orderlineamount && $newpaid < intval($data['pending']))
														{
															$newamt=0;
														}
												}
												else
												{
													$newamt=$orderlineamount;
												}
						
												$paymentarray=array(
												'type'=>'in',
												'in_type'=>'invoice',
												'in_status'=>'pending',
												'customer'=>$customer,
												'amount'=>$newamt,
												'regtime'=>date('Y-m-d H:i:s'));
												$this->payments_model->addCustomerPayment($paymentarray);
												$this->payments_model->updateCustomerBalance($customer,$newamt,'pending');
										}
										else
										{
											if($orderlineamount > intval($data['paid']))
											{
												$bal=$orderlineamount-intval($data['paid']);
												//$newamt=$bal;
												
												if(intval($autofillamt) > 0)
												{
													//$newamt=$autofillamt;	
													$newpaid=abs($data['paid'])+$bal;
														if(intval($data['pending']) > $bal && $newpaid < intval($data['pending']))
														{
															$newamt=0;
														}
												}
												else
												{
													$newamt=$bal;
												}
												
						
												$paymentarray=array(
												'type'=>$type,
												'in_type'=>'invoice',
												'in_status'=>'pending',
												'customer'=>$customer,
												'amount'=>$bal,
												'regtime'=>date('Y-m-d H:i:s'));
												$this->payments_model->addCustomerPayment($paymentarray);
												$this->payments_model->updateCustomerBalance($customer,$newamt,'pending');
											}
										}
								
								
								
											$in_status='paid';
											$payment_type='account';
											$type='out';
										//	$paytype=trim($_POST['paytype']);
											$customer=$this->session->userdata['customer']['id'];
											$regtime=date('Y-m-d H:i:s');
											$paymentarray=array(
											'type'=>$type,
											'in_status'=>$in_status,
											'customer'=>$customer,
											'order'=>$orderid,
											'orderline'=>$orderline,
											'regtime'=>$regtime,
											'amount'=>$orderlineamount);
												
										$cpaystatus=$this->payments_model->addCustomerPayment($paymentarray);
										if($cpaystatus)
										{	
											$result = $this->payments_model->updateCustomerBalance($customer,$orderlineamount,'','debit');
										}	
											
										}
										
										
								}
							
								if($saldostatus)
								{
									if($orderlineamount > $paidsaldo)
									{
											$in_status='paid';
									}
									$payment_type='account';
								 }
								$this->orders_model->orderlinePayment($orderid,$orderline,$payment_type,$in_status,$checkedoderline);
								$orderpaymentstatus=$this->payments_model->updateorderPaymentstatus($orderid);
						}
					
					}
			
				}			
					
			}
			
		}
		
			$data = $this->payments_model->getAccountBalance($customer);
			$pendingsaldo = $data['pending'];
			$paidsaldo = $data['paid'];
				
			if(intval($pendingsaldo) > 0)
			{	
				$amount=formatcurrency($paidsaldo).' ('.formatcurrency($pendingsaldo).')';
			}
			else
			{
				$amount=formatcurrency($paidsaldo);
			}
		
			if($self)
			{
				return true;
			}
			else
			{
				$newarray=array('status'=>'success','message'=>'Payment status has been updated','amount'=>$amount);
				echo json_encode($newarray);exit;
			}
			
			
	 }

	 
	 
	 function __updatemultideliverystatus()
	{
	
		if(count($_POST['orders']) > 0)
		{	
			foreach($_POST['orders'] as $orderid)
			{
				$status=$this->orders_model->updateorderlog($orderid,9);
			}
		}
		
	$result = array("status"=>'success',"message"=>"Deliery status has been updated successfully.");
		
		echo json_encode($result);exit;
	}
	
		/*print order receipt with heat seal*/
		function __logReceiptWithHeatSeal()
		{
			
			//profiling
			$this->data['controller_profiling'][] = __function__;
			//$order_id = 20154586;
			$order_id = $this->uri->segment(4);
			
			$orderinfo = $this->orders_model->getOrderinfo($order_id);
			
			if(count($orderinfo) > 0){
			
				//get orderline
				$orderdetails = $this->orders_model->getOrderLine($order_id);
				
			//	echo '<pre>';print_r($orderdetails);exit;
				 $boolean = true;
				$str ='';
				$delsum = 0;
				$orderlinedelivery=array();
				$checkdeliverydate=array();
				$weekdayarray=array("Mon"=>"Man","Tue"=>"Tirs","Wed"=>"Ons","Thu"=>"Tors","Fri"=>"Fre","Sat"=>"lør","Sun"=>"Søn");	
				for($i=0;$i< count($orderdetails);$i++)
				{
					
					$arr = $this->orders_model->getProductDisplayName($orderdetails[$i]['product']);
					
					$orderdetails[$i]['name'] = $arr['name'];
	
					//echo '<pre>';print_r($orderdetails[$i]['product']);exit;
					if($orderdetails[$i]['p_b_delivery_time'] != '' && $orderdetails[$i]['p_b_delivery_time'] != '0000-00-00')
					{
						$b_delivery_time=date('Y-m-d',strtotime($orderdetails[$i]['p_b_delivery_time']));
						$p_b_delivery_time = date('d/m/Y',strtotime($orderdetails[$i]['p_b_delivery_time']));
						$day=date('D',strtotime($orderdetails[$i]['p_b_delivery_time']));
						$checkdeliverydate[$b_delivery_time]= strtolower($weekdayarray[$day]).' '.$p_b_delivery_time;
						$orderlinedelivery[]= '<span class="pname"> '.$orderdetails[$i]['name'].'</span> '.strtolower($weekdayarray[$day]).' '.$p_b_delivery_time;
					}
		
					$quantity = $orderdetails[$i]['quantity'] = ($orderdetails[$i]['changed_quantity']!='') ?  $orderdetails[$i]['changed_quantity'] : $orderdetails[$i]['quantity'];
					
					if (round($quantity, 0) == $quantity)
					{
						// is whole number
						$quantity = round($quantity, 0);
					}					
					
					
					$amount = ($orderdetails[$i]['changed_amount']!='') ?  $orderdetails[$i]['changed_amount'] : $orderdetails[$i]['amount'];
	
					$total_price = $amount ;
	
					$delsum =  $delsum + $total_price;
					
					$productPrice=$total_price;
					
					$discount = $this->products_model->getProDiscount($orderdetails[$i]['product']);
					 $discount=$discount[0];
					 $ddesc='&nbsp;';
					 if(isset($discount['description']))
					 {
						 $ddesc='('.$discount['description'].')';
					 }
					 $products=$orderdetails[$i];
					
					 $productPrice=round($productPrice);
					 $subtotalarray[]=$productPrice;
									 
					
					
				  $vary = ($orderdetails[$i]['in_meter'] == 1) ? "*" : '' ;
				
				  $barcodes = $this->orders_model->orderlineHeatseal($orderdetails[$i]['id']);
				  
				
	
					 /* $str.='<tr>
						<td nowrap="nowrap" style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:bold;  vertical-align: top;">'.$orderdetails[$i]['name'].' ('.$quantity.')</td>
					  </tr>';*/
					 
				$prodtype=$this->process_order_model->validateProducttype($orderdetails[$i]['product']);
					  
				  if($orderdetails[$i]['in_meter'] == 1)
				  {
					$actualqty=1;
				  }
				  else
				  {
					$actualqty=$prodtype*$orderdetails[$i]['quantity'];
				  }
				 
				 
				 
				 $rqty=0;
				 if($actualqty > count($barcodes))
				 {
					$rqty=$actualqty-count($barcodes);
				 }
				 
				 
				$additionalproductcount=$this->products_model->additionalProductCount($orderdetails[$i]['product']);
		
				 
				// $orderdetails[$i]['product']
				 
				 
			// echo '<pre>';print_r($barcodes);exit;
				
				 
				 $amtstatus=0;
				 $count=0;
				
				  foreach($barcodes as $baritems)
				  {
				  
				
						if($additionalproductcount == $count)
						{
							$count=0;
						}
				   
				   
						$name=$orderdetails[$i]['name'];
						if(intval($baritems['additional_product']) > 0)
						{	
							$query=$this->db->query("SELECT a_additional_product.name FROM a_product_additional_product
							LEFT JOIN a_additional_product ON a_additional_product.id=a_product_additional_product.additional_product
							WHERE a_product_additional_product.id='".$baritems['additional_product']."'");		
							if($query->num_rows() > 0)		
							{				
								$result=$query->row_array();
								
								$name=$result['name'];
								
								  $count++;
							}
						}
					
					$oramount=($baritems['changed_amount'] != '') ? $baritems['changed_amount']:$baritems['amount'];
					
					
					//$oramount=$orderdetails[$i]['price'];
					
					$amtstar='';
					if(intval($oramount) == 0)
					{
						$amtstar='*';
					}
					
					if($baritems['barcode']!=''){
						
					$boolean = false;
						
					$str.='<tr>
					<td width="40%" style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;">'.$name.'</td>
					 <td width="5%" style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;"> : </td>
					<td width="30%" style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;">HS #'.$baritems['barcode'].'</td>';
					$str.='<td width="25%" style="text-align: left; padding: 10px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top; "><input type="button" value="'.$this->data['lang']['lang_delete'].'" onclick="deleteHeatseal(\''.$baritems['logid'].'\');" /></td>';
					
					$str.='</tr>';
						//if($amtstatus == 0)
				  
						if(intval($baritems['additional_product']) > 0)
						{
							$amtstatus=1;
						}
				  
					}
				  
				  }
				  
				}
				
			$str.='<input type="hidden" id="iframe_order" name="iframe_order" value="'.$order_id.'" />';
			
			if($boolean){
				$str.='<tr>
					<td colspan="4" style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;">Ingen resultat.</td></tr>';
			}
			
		}
		else{
			
			 $str='<tr>
					<td colspan="4" style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;">Ingen resultat.</td></tr>';
			
		}
		
		$this->data['lists']['orderline'] = $str;
		
	}

	
}
/* End of file orders.php */
/* Location: ./application/controllers/admin/orders.php */
