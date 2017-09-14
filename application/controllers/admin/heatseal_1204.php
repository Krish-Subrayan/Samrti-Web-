<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class Heatseal extends MY_Controller
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
		
        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'heatseal.scan.html';
		
       
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
		
		
		
		
        //get data
        $this->__pulldownLists();
		
        //route the rrequest
        switch ($action) {
			case 'get-order-receipt':
				 $this->__getOrderReceipt();
           		 break;
			case 'barcodeinfo':
				 $this->__getBarcodeinfo();
           		 break; 
			case 'insert-cart':
				 $this->__insertbarcodecart();
           		 break;
			case 'addtocartbarcode':
				 $this->__addtocartbarcode();
           		 break; 
				case 'registerheatseal':
				 $this->__registerheatseal();
           		 break; 
			case 'producttypecart':
				 $this->__producttypecart();
           		 break;
			case 'getProducttype':
				 $this->__getProducttype();
           		 break;
			case 'validateBarcode':
				 $this->__validateBarcode();
           		 break;
			case 'plass-tildeling':
				$this->__getheatsealDetail();
				$this->data['template_file'] = PATHS_ADMIN_THEME . 'heatseal.plass.tildeling.html';
                break;	
            case 'getHeatseallog':
				 $this->data['template_file'] = PATHS_ADMIN_THEME . 'iframe.heatseal.tildeling.html';
				 $this->__logReceiptWithHeatSeal();
           		 break;	
		   case 'updateMissStatus':
				 $this->__updateMissStatus();
           		 break;	
			 case 'getorderreceipt':
				 $this->__getorderreceipt1();
           		 break;	
			 case 'deleteHeatseal':
				 $this->__deleteHeatseal();
           		 break;	
			case 'deliveryOrder':
				 $this->__deliveryOrder();
           		 break;	
			case 'upatedeliverystatus':
				 $this->__upatedeliverystatus();
           		 break;	
			default:
				$this->__getOrderDetail();
                break;
        }
        //load view
        $this->__flmView('admin/main');
    }
	
	
	
	
	/*get bag details*/
	function __getOrderDetail()
	{
		//profiling
		$this->data['controller_profiling'][] = __function__;
		
		
		$order_id = ($this->uri->segment(3)) ? $this->uri->segment(3) : '';
		$action = ($this->uri->segment(4)) ? $this->uri->segment(4) : '';
		

		if($action == 'edit')
		{
			$this->data['visible']['edit_order'] = 1;
			$this->data['vars']['edit_order']=intval($order_id);
		}

			
			
			if(isset($_POST['heatseal']))
			{
				$newdata = array('barcode'  => $_POST['heatseal']);
				$this->session->unset_userdata('barcode');
				$this->session->set_userdata($newdata);
				$barcode = $_POST['heatseal']; // barcode of a bag
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
			
			
		
			
			$type='laundry';
			$baginfo=$this->orders_model->getBagBarcodeinfo($barcode,$type);
			
			
			
			$logorders = $this->process_order_model->getHetseallog($barcode);

				
			$orderhistory = array();
			if(count($logorders) > 0)
			{
				foreach($logorders as $orders)
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
							
							
							$branch =  ($company == $this->data['settings_company']['company_name']) ?  '':  (($company != $this->session->userdata['partner_branch_name']) ? '('.substr($company, 0, 4) .')' :  ''); 
							
							
						    $amount = ($oitems['changed_amount']!='') ? $oitems['changed_amount'] :  $oitems['total_amount'];
							

							$o_time=$datetitle=date('H:i',strtotime($oitems['order_time']));;
							$customerorderdetails.='<div class=" order-list row">
							<div class="col-md-9">
							<p><a href="#" rel="'.$oitems['id'].'" class="green-text"> #'.$oitems['id'].' '.$branch.'</a><span>kr '.formatcurrency($amount).'</span>
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

				$orderid=$this->process_order_model->getHeatBarcodeinfo($barcode);
				
				$this->data['blocks']['blk1'][0] = $this->orders_model->getOrderinfo($orderid);

				
				
				
			}
			
			
		//echo '<pre>';print_r($this->data['blocks']['blk1']);exit;
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
			
			
		//	echo '<pre>';print_r($customer['customer']);exit;
			$orderinfo = $this->orders_model->getOrderinfo($order_id,'scan');
		
			$order_status = $orderinfo['order_status'];
			
			$this->data['visible']['wi_show_order'] = 0;

			
			if(!empty($orderinfo)){
				
			$this->data['visible']['wi_show_order'] = 1;
				
				
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
			     <div class="list-button">
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
			
			
			
			
		//	<span class="green-text"> (5) </span>
		
			if(!empty($orderinfo)){
			   $customer_detail='<div class="customer-detail mt-sm">
                   <div class="pull-left col-md-6 no-padd">
                   <p><span>Ordrenr: </span>#'.$order_id.'</p>
				   <input type="hidden" id="ordrenr" name="ordrenr" value="'.$order_id.'" />
                   <p><span>Telefon: </span>'.$customer['number'].'<br>
                    <span>Navn: </span>'.$customer['customer_name'].'</br>
					';
					
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
			$orderdetails = $this->orders_model->getOrderLine($this->data['blocks']['blk1'][$i]['id']);
			$data=$orderdetails;
			if(!empty($data)){
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
			  
			  			
			 /* $str.='<tr>
                <td nowrap="nowrap" style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:bold;  vertical-align: top;">'.$orderdetails[$i]['name'].' ('.$quantity.')</td>
                <td style="text-align: center; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top; "> </td>
                <td nowrap="nowrap" style="text-align: right; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top;font-weight:bold;">kr '.formatcurrency($amount).$vary.'</td>
              </tr>';*/
			  
			  
			  $str.='<div style="cursor:pointer;" class="orderlist cart_table_item">
					<div class="pull-left col-md-6 no-padd" >
                   <p><b>'.$orderdetails[$i]['name'].' ('.$quantity.')</b></p>
                   </div>  
					<div class="pull-left col-md-3 no-padd text-center" >
                  	 <p></p>
                   </div> 
                   <div class="pull-left col-md-3 no-padd text-center" ><p><b>kr '.formatcurrency($amount).$vary.'</b></p></div>
                   <div class="clearfix"></div>
                 </div>';
			
			  
			  
			  
			  // $str.='<tr><td colspan="3" style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top; ">';
			   $str.='<div style="cursor:pointer;" class="orderlist cart_table_item prodName">
			   <div class="pull-left col-md-12 no-padd" >';
			  $boolean = true ;

			  if(($orderdetails[$i]['in_meter'] == 1) && ($boolean))
			  {
				$meter_text =1;
				$boolean = false;
			  }
			  
			  
				if($orderdetails[$i]['special_instruction']!=''){
					$str .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;line-height:28px"><b>Fritekst :</b><br>'.$orderdetails[$i]['special_instruction'].'</p>';
				}
				
				if($orderdetails[$i]['complain']==1){
					$str .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;line-height:28px"><b>Reklamasjon</b></p>';
				}
				
				if($orderdetails[$i]['in_house']==1){
					$str .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;line-height:28px"><b>Renses på huset</b></p>';
				}
			  
				
				$str.='</div><div class="clearfix"></div>
                 </div>';
			  
			  $barcodes = $this->orders_model->orderlineHeatseal($orderdetails[$i]['id']);
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
				
				/*$str.='<tr>
                <td style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top; ">'.$name.' <br>HS #'.$baritems['barcode'].'</td>
                               <td style="text-align: center; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top; ">1 </td>';*/
				
				if($baritems['barcode']!=''){			   
							   
				$str.='<div style="cursor:pointer;" class="orderlist cart_table_item prodheatseal"><div class="pull-left col-md-6 no-padd" ><p>'.$name.'<br>HS #'.$baritems['barcode'].'</p></div><div class="pull-left col-md-3 no-padd text-center" >
                  	 <p>1</p>
                   </div> ';
				   
				}
							   
					//if($amtstatus == 0)
					if($count == 1 || $count == 0)
					{
						 //$str.='<td nowrap="nowrap" style="text-align: right; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 12px; font-weight:normal;vertical-align: top;">kr '.formatcurrency($oramount).'</td>';
						 
						// $str.='<td nowrap="nowrap" style="text-align: right; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top;"></td>';
						$str.='<div class="pull-left col-md-3 no-padd text-center" ><p></p></div>';
						 
						 
					}
					else
					{
						// $str.='<td nowrap="nowrap" style="text-align: right; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top;"></td>';
						$str.='<div class="pull-left col-md-3 no-padd text-center" ><p></p></div>';
					}					
              
			   
			   
              $str.='
				   
                   <div class="clearfix"></div>
                 </div>';
			  
					if(intval($baritems['additional_product']) > 0)
					{
						$amtstatus=1;
					}
			  
			  }
			  
			 
			  if($rqty > 0)
			  {
			 
					$rrqty=count($barcodes)+1;
			  
					foreach(range($rrqty,$actualqty) as $qtyitems)
					{
					
					
					$str.='<div style="cursor:pointer;" class="orderlist cart_table_item prodheatseal"><div class="pull-left col-md-6 no-padd" ><p>'.$orderdetails[$i]['name'].'<br>HS #</p></div><div class="pull-left col-md-3 no-padd text-center" >
                  	 <p>1</p>
                   </div> <div class="pull-left col-md-3 no-padd text-center" ><p></p></div>
				   
                   <div class="clearfix"></div>
                 </div>';
				 
					
						/*$str.='<tr>
                <td style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top; ">'.$orderdetails[$i]['name'].' <br>HS # 
                               <td style="text-align: center; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top; ">1 </td>
                <td nowrap="nowrap" style="text-align: right; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top;"></td>
              </tr>';*/
					}
					
			  }
			  
			  if($i!=count($orderdetails)){
				  $str.='<hr>';
			  }
			  
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
		}
		$this->data['lists']['orderlines']=$str;
		
		//echo '<pre>';print_r($this->data['lists']['orderlines']);exit;
			
			
	}
	
	
	function __getOrderReceipt()
	{
			//profiling
			$this->data['controller_profiling'][] = __function__;
			$order_id = $this->input->post('oid');
	
			if(intval($order_id) == 0)
			{
				$newarray=array('status'=>'error','message'=>'Invalid');
				
			}
			$this->data['blocks']['blk1'][0] = $this->orders_model->getOrderinfo($order_id);
			
			
		//echo '<pre>';print_r($this->data['blocks']['blk1']);exit;
        //get results and save for tbs block merging
		if(count($this->data['blocks']['blk1'] > 0)) {
			
			for($i=0;$i<count($this->data['blocks']['blk1']);$i++){
			
			$order_id=$this->data['blocks']['blk1'][$i]['id'];	
			
			
			
			$customer = $this->orders_model->getCustomerDetails($order_id);
			
		//	echo '<pre>';print_r($customer['customer']);exit;
			$orderinfo = $this->orders_model->getOrderinfo($order_id,'scan');
		
			$order_status = $orderinfo['order_status'];
			
			$this->data['visible']['wi_show_order'] = 0;

			
			if(!empty($orderinfo)){
				
			$this->data['visible']['wi_show_order'] = 1;
				
				
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
			     <div class="list-button">
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
			
			
			
			
		//	<span class="green-text"> (5) </span>
		
			if(!empty($orderinfo)){
			   $customer_detail='<div class="customer-detail mt-sm">
                   <div class="pull-left col-md-6 no-padd">
                   <p><span>Ordrenr: </span>#'.$order_id.'</p>
				   <input type="hidden" id="ordrenr" name="ordrenr" value="'.$order_id.'" />
                   <p><span>Telefon: </span>'.$customer['number'].'<br>
                    <span>Navn: </span>'.$customer['customer_name'].'</br>
					';
					
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
			$orderdetails = $this->orders_model->getOrderLine($this->data['blocks']['blk1'][$i]['id']);
		$data=$orderdetails;	
			
			if(!empty($data)){
			
	
			
			 $str='<div style="cursor:pointer;" class="orderlist cart_table_item">
					<div class="pull-left col-md-6 no-padd" >
                  <p><b>Artikler</b></p>
                   </div>  
					<div class="pull-left col-md-3 no-padd text-center" >
                  	 <p><b>Antall</b></p>
                   </div> 
                   <div class="pull-left col-md-3 no-padd text-center" ><p><b>Beløp</b></p></div>
				   <div class="clearfix"></div>
                 <hr />
                 </div>';
				 
				 
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
			  
			  			
			 /* $str.='<tr>
                <td nowrap="nowrap" style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:bold;  vertical-align: top;">'.$orderdetails[$i]['name'].' ('.$quantity.')</td>
                <td style="text-align: center; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top; "> </td>
                <td nowrap="nowrap" style="text-align: right; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top;font-weight:bold;">kr '.formatcurrency($amount).$vary.'</td>
              </tr>';*/
			  
			  
			  $str.='<div style="cursor:pointer;" class="orderlist cart_table_item">
					<div class="pull-left col-md-6 no-padd" >
                   <p><b>'.$orderdetails[$i]['name'].' ('.$quantity.')</b></p>
                   </div>  
					<div class="pull-left col-md-3 no-padd text-center" >
                  	 <p></p>
                   </div> 
                   <div class="pull-left col-md-3 no-padd text-center" ><p><b>kr '.formatcurrency($amount).$vary.'</b></p></div>
				   
                   <div class="clearfix"></div>
                 
                 </div>';
			  
			  
			  
			  // $str.='<tr><td colspan="3" style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top; ">';
			   $str.='<div style="cursor:pointer;" class="orderlist cart_table_item prodName">
			   <div class="pull-left col-md-12 no-padd" >';
			  $boolean = true ;

			  if(($orderdetails[$i]['in_meter'] == 1) && ($boolean))
			  {
				$meter_text =1;
				$boolean = false;
			  }
			  
			  
				if($orderdetails[$i]['special_instruction']!=''){
					$str .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;line-height:28px"><b>Fritekst :</b><br>'.$orderdetails[$i]['special_instruction'].'</p>';
				}
				
				if($orderdetails[$i]['complain']==1){
					$str .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;line-height:28px"><b>Reklamasjon</b></p>';
				}
				
				if($orderdetails[$i]['in_house']==1){
					$str .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;line-height:28px"><b>Renses på huset</b></p>';
				}
			  
			  
			   // $str.='</td></tr>';
				
				$str.='</div><div class="clearfix"></div>
                 </div>';
			  
			  $barcodes = $this->orders_model->orderlineHeatseal($orderdetails[$i]['id']);
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
				
				/*$str.='<tr>
                <td style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top; ">'.$name.' <br>HS #'.$baritems['barcode'].'</td>
                               <td style="text-align: center; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top; ">1 </td>';*/
							   
				$str.='<div style="cursor:pointer;" class="orderlist cart_table_item prodheatseal"><div class="pull-left col-md-6 no-padd" ><p>'.$name.'</p><p>HS #'.$baritems['barcode'].'</p></div><div class="pull-left col-md-3 no-padd text-center" >
                  	 <p>1</p>
                   </div> ';
							   
					//if($amtstatus == 0)
					if($count == 1 || $count == 0)
					{
						 //$str.='<td nowrap="nowrap" style="text-align: right; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 12px; font-weight:normal;vertical-align: top;">kr '.formatcurrency($oramount).'</td>';
						 
						// $str.='<td nowrap="nowrap" style="text-align: right; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top;"></td>';
						$str.='<div class="pull-left col-md-3 no-padd text-center" ><p></p></div>';
						 
						 
					}
					else
					{
						// $str.='<td nowrap="nowrap" style="text-align: right; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top;"></td>';
						$str.='<div class="pull-left col-md-3 no-padd text-center" ><p></p></div>';
					}					
              
			   
			   
              $str.='
				   
                   <div class="clearfix"></div>
                 </div>';
			  
					if(intval($baritems['additional_product']) > 0)
					{
						$amtstatus=1;
					}
			  
			
			  
			  }
			  
			 
			  if($rqty > 0)
			  {
			 
					$rrqty=count($barcodes)+1;
			  
					foreach(range($rrqty,$actualqty) as $qtyitems)
					{
					
					
					$str.='<div style="cursor:pointer;" class="orderlist cart_table_item prodheatseal"><div class="pull-left col-md-6 no-padd" ><p>'.$orderdetails[$i]['name'].'</p><p>HS #</p></div><div class="pull-left col-md-3 no-padd text-center" >
                  	 <p>1</p>
                   </div> <div class="pull-left col-md-3 no-padd text-center" ><p></p></div>
				   
                   <div class="clearfix"></div>
                 </div>';
				 
					
						/*$str.='<tr>
                <td style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top; ">'.$orderdetails[$i]['name'].' <br>HS # 
                               <td style="text-align: center; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top; ">1 </td>
                <td nowrap="nowrap" style="text-align: right; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top;"></td>
              </tr>';*/
					}
			  }
			  if($i!=count($orderdetails)){
				  $str.='<hr>';
			  }
			 
			  
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
		}
		$order_details=$company_info.$customer_detail.$str.$summary;
				
		$result = array('status'=>'success',"order_details"=>$order_details);
		echo json_encode($result);exit;
  
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
	/* Get heatseal barcode customer and company information*/
	
	function __getBarcodeinfo()
	{
		if(count($_POST) > 0)
		{
			$barcode=$_POST['barcode'];
			if($this->session->userdata['heatsealcart'][$barcode])
			{
				$result = array("status"=>'error',"message"=>"Heatseal already scanned code.");
				echo json_encode($result);exit;
			}
			
			$barinfo=$this->process_order_model->getHeatsealinfo($barcode);
			if($barinfo)
			{
				
				$newdata = array('current_heatseal'=>$barcode);
				$this->session->set_userdata($newdata);	
						
						
				$company=$barinfo['company'];
				
				$compnayinfo=$this->customer_model->getCompanyinfo($company);
				$product=$barinfo['product'];
				$productinfo=$this->products_model->getProductinfo($product);
		
				//$customer=1127;
				$customer=$barinfo['customer'];
				$customerstatus=0;
				if($customer)
				{
					$customerstatus=1;
					$mobileinfo=$this->customer_model->getCustomerinfo($customer);
					
					$newdata = array('customer'  => $mobileinfo);
					$this->session->set_userdata($newdata);
					if(intval($mobileinfo['id']) > 0)
					{
					
						$cus_id=trim($mobileinfo['id']);
					
						$ccstatus=$this->customer_model->validateCompanycustomer($cus_id);	
						if($ccstatus)
						{
							$mobile=$mobileinfo['phone'];
							$mobileinfo=$this->customer_model->companycustomermobileinfo($mobile);
							
							$newdata = array('customer'  => $mobileinfo);
							$this->session->set_userdata($newdata);
						}
						
						$newdata = array('pos_customer_id'  => $cus_id);
						$this->session->unset_userdata('pos_customer_id');
						$this->session->set_userdata($newdata);
						$this->cart->destroy();
					

					}
					
					
				}
				
					
					if(count($compnayinfo) > 0)
					{
						
						$heatinfo='<div class="col-md-9 col-sm-6 col-xs-6 no-padd" style="margin-bottom:20px">';
						$heatinfo.='<h4 class="mb-none"><strong>'.$compnayinfo['name'].'</strong></h4>';
						$heatinfo.='<p style="color:#5a5a5a;">'.$compnayinfo['street'].'</p>';
						$heatinfo.='<p style="color:#5a5a5a;">'.$compnayinfo['phone'].'</p>';
						$heatinfo.='<p style="color:#5a5a5a;">'.$compnayinfo['email'].'</p>';
						$heatinfo.='<input type="hidden" id="company" id="company" value="'.$company.'" />';
					
					}
					else
					{
						
							$heatinfo='<div class="col-md-12 col-sm-6 col-xs-6 no-padd">';
							$heatinfo.='<select name="company" onchange="saveCompany(this.value);" id="company" class="selectbox"><option value="">-- Select Company --</option>';
							
							$companylist=$this->customer_model->getCompanylist(false);	
							
							if(count($companylist) > 0)
							{
								foreach($companylist as $list)
								{
									$heatinfo.='<option value="'.$list['id'].'">'.$list['name'].'</option>';
								}
							}
							$heatinfo.='';
							$heatinfo.='</select><br />';
					}
					$heatinfo.='</div>';
					
				

					if(count($productinfo) > 0 && trim($productinfo['name']) != '')
					{
						
						$newdata = array('current_product'=>$barcode);
						$this->session->set_userdata($newdata);	
				
						$heatinfo.='<div class="col-md-3 col-sm-6 col-xs-6">
						<div class="col-md-12 col-sm-7 col-xs-7 no-apdd text-center">
											<div id="summary_1" class="summary entry-summary">
												<h4 class="mb-none"><strong>'.$productinfo['name'].'</strong></h4>
										</div>
							</div>
					 <div class="col-md-12 col-sm-5 col-xs-5 no-apdd">
										<div class="round">
										 <img class="img" src="'.$productinfo['path'].'" width="100%" height="80" />
										</div>                   
									   </div>
								   
					</div>';
					}
				

					$result = array("status"=>'success',"data"=>$heatinfo,'customerstatus'=>$customerstatus);
				}
			else
			{
				$barcode=$_POST['barcode'];
				$newdata = array('current_heatseal'=>$barcode);
				$this->session->set_userdata($newdata);	
				$heatinfo='<div class="col-md-12 col-sm-6 col-xs-6 no-padd">';
				$heatinfo.='<select  onchange="saveCompany(this.value);"  name="company" id="company" class="selectbox"><option value="">-- Select Company --</option>';
				$companylist=$this->customer_model->getCompanylist(false);	
					
					if(count($companylist) > 0)
					{
						foreach($companylist as $list)
						{
							$heatinfo.='<option value="'.$list['id'].'">'.$list['name'].'</option>';
						}
					}
				$heatinfo.='';
				$heatinfo.='</select><br />';
				$heatinfo.='</div>';
				
				$customerstatus=0;
				$result = array("status"=>'success',"data"=>$heatinfo,'customerstatus'=>$customerstatus);
			}
		
			
			
		}
		else
		{
			
			$result = array("status"=>'error',"message"=>"Invalid code.");
		}
		
		echo json_encode($result);exit;
	}	
    
	function __addtocartbarcode()
	{
		if(count($_POST) > 0)
		{
			$barcode=$_POST['barcode'];
			if($this->session->userdata['heatsealcart'][$barcode])
			{
				$result = array("status"=>'error',"message"=>"Heatseal already scanned code.");
				echo json_encode($result);exit;
			}
			
			$barinfo=$this->process_order_model->getHeatsealinfo($barcode);
			$product=$barinfo['product'];
			if(intval($product) > 0)
			{
					$proqty = $this->process_order_model->validateProducttype($product);
					if($proqty == 1)
					{
						$productinfo=$this->products_model->getProductinfo($product);
						self::__insertCart($productinfo,$barcode);
						$result = array("status"=>'success','message'=>$customerstatus);
					}
					else
					{
		/*			
		if($heatseal == 'skip')
		{
			$s_data['producttypeskip']=$this->session->userdata['producttypeskip'];
			$s_data['producttypeskip'][$product][$producttype]=$producttype;
			$this->session->set_userdata($s_data);
		}
		else
		{
			$s_data['producttypecart']=$this->session->userdata['producttypecart'];
			$s_data['producttypecart'][$product][$heatseal]=$producttype;
			$this->session->set_userdata($s_data);
		}*/
		
		
		
		
				
						$producttypes=$this->process_order_model->get_product_types($product);
							
						$outhtml='';
						if($producttypes)
						{
							$i=0;
							foreach($producttypes as $proitems)
							{
									
							 if(($i%3)==0 && ($i !=0 ))	
								$outhtml.='<div class="clearfix mt-lg"></div>';
								
								 $outhtml.='<div class="col-md-6">';
								 
								if($this->session->userdata['producttypeskip'][$product][$proitems['id']] ||
								$this->session->userdata['producttypecart'][$product][$proitems['id']])
								{
									$outhtml.=' <input disabled="disabled" type="radio" class="producttype" value="'.$proitems['id'].'" id="producttype_'.$proitems['id'].'" name="producttype" onclick="producttypepopup(\''.$proitems['id'].'\',\''.$product.'\');" />';
								}
								else
								{
									$outhtml.=' <input type="radio" class="producttype" value="'.$proitems['id'].'" id="producttype_'.$proitems['id'].'" name="producttype" onclick="producttypepopup(\''.$proitems['id'].'\',\''.$product.'\');" />';
								}
								 
								 
								
								
								$outhtml.=''.$proitems['name'].'</div>';
								 
								 if($this->session->userdata['producttypeskip'][$product][$proitems['id']] ||
								$this->session->userdata['producttypecart'][$product][$proitems['id']])
								{
								
								 }
								 else
								 {
									 $outhtml.='<div class="col-md-6">
								 <input type="button" class="btn" value="Skip '.$proitems['name'].'" name="producttype" onclick="skipscan(\''.$proitems['id'].'\',\''.$product.'\');" /></div>';
								  $outhtml.='<br />';
								 }
								
								 
								 
								 $i++;
							}
						}
						
						$outhtml.='<input type="hidden" data="'.count($producttypes).'" name="qty_'.$product.'" id="qty_'.$product.'" value="0" />';
						
		
					$result = array("status"=>'multiqty','protype'=>$outhtml);
						
					}
					
			}
			else
			{
				$result = array("status"=>'empty');
			}
			
			
			//echo '<pre>';print_r($productinfo);exit;
			
			//$result = array("status"=>'success',"data"=>$heatinfo,'customerstatus'=>$customerstatus);
		}
		else
		{
			$result = array("status"=>'error',"message"=>"Invalid code.");
		}
		
		echo json_encode($result);exit;
	}	
    
	function __insertbarcodecart()
	{
		if(count($_POST) > 0)
		{
			$barcode=$_POST['qty'];
			$prodcut=$_POST['pid'];
			if(intval($prodcut) > 0)
			{
			
				$proqty = $this->process_order_model->validateProducttype($prodcut);
				if($proqty == 1)
				{
					$productinfo=$this->products_model->getProductinfo($prodcut);
					self::__insertCart($productinfo,$barcode);
					$session_items = array('current_heatseal' => '');
					$this->session->unset_userdata($session_items);
				}
				
				
				
			}
			else
			{
				$result = array("status"=>'error',"message"=>"Invalid code.");
			}
		}
		else
		{
			$result = array("status"=>'error',"message"=>"Invalid code.");
		}
		
		echo json_encode($result);exit;
		
		
	}
	
	/*insert items to cart  */
	function __insertCart($productinfo,$barcode){
		
		  if(count($productinfo) > 0)
		  {
			$id = $productinfo['id'];
			$product = $productinfo['id'];
			$name = $productinfo['name'];
			$price = $productinfo['price'];
			$qty = 1;
			$img = $productinfo['path'];
			$desp = $productinfo['description'];
			$gtype = $productinfo['gtype'];
			$duration = $productinfo['duration'];
			$complain = '';
			$in_house = '';
			$spl_instruction = '';
			
			/*if($id == 143)
			{
				$price=$qty;
				$qty=1;
				if(intval($price) == 0)
				{	
					$qty=0;
				}
			}*/
			$price = ($complain == '1')  ?   0 : $price;
			if($id == 143)
			{
				$price=$qty;
				$qty=1;
				
				if($complain == '1')
				{
					$price=0;
					$qty=1;
				}
				else
				{
					if(intval($price) == 0)
					{	
						$qty=0;
					}
				}
				
				
				
			}
			
			
			
			
			$pdata = $this->products_model->getProductinfo($id,'main','',$this->session->userdata['logged_in']);
			//print_r($pdata);
			$in_meter = $pdata['in_meter'];
			
			
			$days = floor (($duration*60) / 1440);
			$c_date=date('Y-m-d');
			$date = new DateTime($c_date);
			
			if($duration == 96){
				$delivery_date=addDays($c_date,$days,true);
				$delivery_date=date('d.m.Y',strtotime($delivery_date));
			}
			else{
				$date->modify("+$days day");
				$delivery_date = $date->format('d.m.Y');
			}
			
			
			$price = ($complain!='1')  ?   $price : 0;
			$price = ($in_meter !='1')  ?   $price : 0;
			
				
			$dataa = $this->products_model->getProduct_Discount($id);
			$newdiscount=array();
			if(count($dataa) > 0)
			{
				$newdiscount=$dataa[0];
			}
			
			//insert into cart items
			$this->cart->product_name_rules ='\d\D';    //remove product name validation for special characters
			if($qty!=''){
				$data[] =  array(
						'id'      => $id,
						'name'     => $name,
						'price'   => $price,
						'qty'    => $qty,
						'utlevering' =>$delivery_date,
						'options' => array('heatseal'=>$barcode,'image'=>$img,'description'=>$spl_instruction,'gtype'=>$gtype,'discount'=>$newdiscount,'duration'=>$duration,'complain'=>$complain,'in_house'=>$in_house)
			     );
				
				
			
			$result = $this->cart->insert($data);       //insert cart data
			
			$s_data['heatsealcart']=$this->session->userdata['heatsealcart'];
			$s_data['heatsealcart'][$barcode][$id]=$id;
			$this->session->set_userdata($s_data);
			
			$session_items = array('current_heatseal' => '');
			$this->session->unset_userdata($session_items);
			
			
		if($heatseal == 'skip')
		{
			//$s_data['producttypeskip']=$this->session->userdata['producttypeskip'];
			//$s_data['producttypeskip'][$product][$producttype]=$producttype;
			//$this->session->set_userdata($s_data);
		}
		else
		{
			//$s_data['producttypecart']=$this->session->userdata['producttypecart'];
			//$s_data['producttypecart'][$product][$producttype]=$heatseal;
			//$s_data['producttypecart1']=$this->session->userdata['producttypecart1'];
			//$s_data['producttypecart1'][$product][$heatseal]=$producttype;
			//$this->session->set_userdata($s_data);
		}
		
		
		$s_data['producttypeskip']=$this->session->userdata['producttypeskip'];
		$s_data['producttypeskip'][$product]='';
		$this->session->set_userdata($s_data);
		
		$s_data['producttype_cart']=$this->session->userdata['producttype_cart'];
		$s_data['producttype_cart'][$product]='';
		$this->session->set_userdata($s_data);
		
		
		
			
			//if($this->session->userdata['producttypeskip'][$product][$proitems['id']] ||
			//	$this->session->userdata['producttypecart'][$product][$proitems['id']])
								
								
								
			
			
			
			/*$response = array("error"=>$result);
			echo json_encode($response);exit;*/
			
			}
		  }
			
			// reverse in descending order
			$data = array_reverse($this->cart->contents());
		 
			 $this->data['reg_blocks'][] = 'cart';
			 $this->data['reg_blocks'][] = 'rens';
			 $this->data['reg_blocks'][] = 'vask';
			 
			 $j=0;
			 $temp = 0;
			 foreach ($data as $items){
				 //print_r($items);
				 $date = $items['utlevering'];
					//echo  $temp ."=". $date;
				 if( (strtotime($temp) != strtotime($date)) || ($j==0)){
				 	$this->data['blocks']['cart'][$date][] = $items;
				 }
				 else{
					 $this->data['blocks']['cart'][$temp][] = $items;
				 }
				 $temp  =  $date;
				 $j++;
			 }
			 

			 $total =0;
			 //print_r($this->data['blocks']['cart']); 
		     $rens='<div id="ajax-loader-rens"><img src="img/ajax-loader.gif"/></div>';
		     $vask='<div id="ajax-loader-vask"><img src="img/ajax-loader.gif"/></div>';
			 $rensstatus=0;
			 $vaskstatus=0;
			if (count($this->data['blocks']['cart']) > 0) {
			  $k = 0;
			  $str ='';
			  $row = array();
			  foreach($this->data['blocks']['cart'] as $key => $value) {
				$day = $key;
				for($j=0;$j<count($this->data['blocks']['cart'][$day]);$j++){ 
					
						$gtype = $this->data['blocks']['cart'][$day][$j]['options']['gtype'];
						$cart_type = ($gtype=='rens') ? 'rens' : 'vask';
						
						$row[$day][$cart_type][$k] = $this->data['blocks']['cart'][$day][$j]['rowid'];

						
						$this->data['blocks'][$cart_type][$k]['utlevering'] = $this->data['blocks']['cart'][$day][$j]['utlevering'];
						$this->data['blocks'][$cart_type][$k]['id'] = $this->data['blocks']['cart'][$day][$j]['id'];
						$this->data['blocks'][$cart_type][$k]['name'] = $this->data['blocks']['cart'][$day][$j]['name'];
						$this->data['blocks'][$cart_type][$k]['price'] = $this->data['blocks']['cart'][$day][$j]['price'];
						$this->data['blocks'][$cart_type][$k]['description'] = $this->data['blocks']['cart'][$day][$j]['options']['description'];
						$this->data['blocks'][$cart_type][$k]['rowid'] = $this->data['blocks']['cart'][$day][$j]['rowid'];
						$this->data['blocks'][$cart_type][$k]['qty'] = $this->data['blocks']['cart'][$day][$j]['qty'];
						$this->data['blocks'][$cart_type][$k]['gtype'] = $this->data['blocks']['cart'][$day][$j]['options']['gtype'];
						$this->data['blocks'][$cart_type][$k]['subtotal'] = $this->data['blocks']['cart'][$day][$j]['subtotal'];
						
						$this->data['blocks'][$cart_type][$k]['in_house'] = $this->data['blocks']['cart'][$day][$j]['options']['in_house'];
						$this->data['blocks'][$cart_type][$k]['complain'] = $this->data['blocks']['cart'][$day][$j]['options']['complain'];						
						
						
						$this->data['blocks'][$cart_type][$k]['subtotal_currency'] = formatcurrency($this->data['blocks']['cart'][$day][$j]['subtotal']);
	
						$product_id = $this->data['blocks']['cart'][$day][$j]['id'];
						$this->data['blocks'][$cart_type][$k]['i'] = $k;
						$path_parts = pathinfo($this->data['blocks']['cart'][$day][$j]['options']['image']);
						$this->data['blocks'][$cart_type][$k]['thumb'] = $path_parts['filename'] . "." .$path_parts['extension'];
						$this->data['debug'][] = $this->products_model->debug_data;
						$total += $this->data['blocks']['cart'][$day][$j]['subtotal'];
						$k++;
				}//for 	 
				
			  }//for
			  
			  
			  
			  $vask_arr = array_values($this->data['blocks']['vask']);
			  $rens_arr = array_values($this->data['blocks']['rens']);
			  
			
			  //for vask	
			 $prev_date ='';
			 $date = '';
			 $str_vask ='';
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
					
					$reklama = ($vask_arr[$z]['complain'] =='1' ) ? '<span class="orange-text">Reklamasjon</span>' : '';
					$renses =  ($vask_arr[$z]['in_house'] =='1' ) ? '<span class="purple-text">Renses på huset</span>' : '';
					
					
			  		$str_vask.='
						  <div class="orderline mt-sm">
						   <div class="pull-left col-md-2 no-padd text-center">';
						   
							/*$str_vask.=' <div class="count">'.$vask_arr[$z]['qty'].'</div>
							<div class="round">
								<div style="background-image: url(\''.$this->data['vars']['site_url'].'\images/'.$vask_arr[$z]['thumb'].'\');" class="img"></div></div>';*/
						   
							$str_vask.='<div class="round1"><div class="img1"><p><span>'.$vask_arr[$z]['qty'].'</span></p></div></div>';
								
							$str_vask.='               
						   </div>
						   <div class="pull-left col-md-5 no-padd">
						   <p><span>'.$vask_arr[$z]['name'].'</span>
							'.$vask_arr[$z]['description'].$reklama.$renses.'</p>
						   </div>
						   <div class="pull-left col-md-3 no-padd text-right"><p><span>kr '.$vask_arr[$z]['subtotal_currency'].'</span></p>
						   
						</div>							   
						   <div class="pull-left col-md-2 no-padd text-center">
						   
						 <a class="editprod" onclick="editprod();" data-toggle="modal" data-title="'.$vask_arr[$z]['name'].'"  href="#editProductModal" data-stuff="'.$vask_arr[$z]['id'].'@'.$vask_arr[$z]['name'].'@'.$vask_arr[$z]['price'].'@'.$vask_arr[$z]['path'].'@'.$vask_arr[$z]['description'].'@'.$vask_arr[$z]['rowid'].'@'.$vask_arr[$z]['qty'].'@'.$vask_arr[$z]['gtype'].'@'.$vask_arr[$z]['complain'].'@'.$vask_arr[$z]['in_house'].'"><img src="img/plus-minus.png" alt="Edit" title=""/></a>	
						   </div>
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
						   
						/*$str_rens.='<div class="count">'.$rens_arr[$z]['qty'].'</div>
							<div class="round">
								<div style="background-image: url(\''.$this->data['vars']['site_url'].'\images/'.$rens_arr[$z]['thumb'].'\');" class="img"></div>
							</div>';*/
							
							$str_rens.='<div class="round1"><div class="img1"><p><span>'.$rens_arr[$z]['qty'].'</span></p></div></div>';
							               
						  $str_rens.=' </div>
						   <div class="pull-left col-md-5 no-padd">
						   <p><span>'.$rens_arr[$z]['name'].'</span>
							'.$rens_arr[$z]['description'].$reklama.$renses.'</p>
						   </div>
						   
						   <div class="pull-left col-md-3 no-padd text-right">
						   <p><span>kr '.$rens_arr[$z]['subtotal_currency'].'</span></div></p>
						   
						   <div class="pull-left col-md-2 no-padd text-center">
						 <a class="editprod" onclick="editprod();" data-toggle="modal" data-title="'.$rens_arr[$z]['name'].'"  href="#editProductModal" data-stuff="'.$rens_arr[$z]['id'].'@'.$rens_arr[$z]['name'].'@'.$rens_arr[$z]['price'].'@'.$rens_arr[$z]['path'].'@'.$rens_arr[$z]['description'].'@'.$rens_arr[$z]['rowid'].'@'.$rens_arr[$z]['qty'].'@'.$rens_arr[$z]['gtype'].'@'.$rens_arr[$z]['complain'].'@'.$rens_arr[$z]['in_house'].'"><img src="img/plus-minus.png" alt="Edit" title=""/></a>	
						   </div>
						   <div class="clearfix"></div>
						   <hr>
						 </div> 
					  ';
					$prev_date =  $date;  
				  }
			 }
			  
			}//if
			else{
				 $str.='<div class="col-md-12"> 
                  <p class="black-text  text-center">'.$this->data['lang']['lang_cart_empty'].'</p>
                 </div>';
			}
			
			if($rensstatus == '1')
			{
				$str_rens .='<style>#rens_div{display:block;}</style>';
			}
			else
			{
				$str_rens .='<style>#rens_div{display:none;}</style>';
			}
			
			if($vaskstatus == '1')
			{
				$str_vask .='<style>#vask_div{display:block;}</style>';
			}
			else
			{
				$str_vask .='<style>#vask_div{display:none;}</style>';
			}
			$count = $this->cart->total_items();
			$delsumamt=$total/1.25;	
			$delsumamt=round($delsumamt, 2);
			$delsumvat=$total-$delsumamt;	
			$result = array("status"=>'success','cartstatus'=>1,"order_list_rens"=>$str_rens,"order_list_vask"=>$str_vask,"delsum"=>$total,"mva"=>$delsumvat,"count"=>$count,"delsum_currency"=>formatcurrency($total),"mva_currency"=>formatcurrency($delsumvat));
			echo json_encode($result);exit;
			
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
		
    }
	
	function __registerheatseal()
	{
			if(isset($_POST['companytype']))
			{
				$newdata = array('companytype'=> $_POST['companytype']);
				$this->session->set_userdata($newdata);
			}
			
			if(isset($_POST['company']))
			{
				$newdata = array('company'=> $_POST['company']);
				$this->session->set_userdata($newdata);
			}
			$result = array('status'=>'success');
			echo json_encode($result);exit;
	}
	
	function __producttypecart()
	{
		$product=$_POST['product'];
		
		
		
		
		//echo ;exit;
		
		$heatseal=$_POST['heatseal'];
		$producttype=$_POST['producttype'];
		
		if(trim($heatseal) == 'skip')
		{
			//echo '<pre>';print_r($this->session->userdata['producttypeskip'][$product]);
			$s_data['producttypeskip']=$this->session->userdata['producttypeskip'];
			$s_data['producttypeskip'][$product][$producttype]=$producttype;
			$this->session->set_userdata($s_data);
			
			//echo '<pre>';print_r($this->session->userdata['producttypeskip'][$product]);exit;
			
		}
		else
		{
		
			if(intval($heatseal) == '')
			{
				$result=array('status'=>'error','message'=>'Invalid Barcode');
				echo json_encode($result);exit;	
			}
		
			$s_data['producttypecart']=$this->session->userdata['producttypecart'];
			$s_data['producttypecart'][$product][$producttype]=$heatseal;
			
			$s_data['producttype_cart']=$this->session->userdata['producttype_cart'];
			$s_data['producttype_cart'][$product][$producttype]=$heatseal;
			
			
			$s_data['producttypecart1']=$this->session->userdata['producttypecart1'];
			$s_data['producttypecart1'][$product][$heatseal]=$producttype;
			$this->session->set_userdata($s_data);
			
		}
		
		$sqty=0;
		if(is_array($this->session->userdata['producttypeskip'][$product]))
		{
			$sqty+=count($this->session->userdata['producttypeskip'][$product]);
		}
		if(is_array($this->session->userdata['producttype_cart'][$product]))
		{
			$sqty+=count($this->session->userdata['producttype_cart'][$product]);
		}
		
		

	//echo '<pre>';print_r($this->session->userdata);exit;
		
		$proqty = $this->process_order_model->validateProducttype($product);
		
		
		
		//	echo $sqty.'=>'.$proqty;exit;
		
		
					if($proqty <= $sqty)
					{
		
						$productinfo=$this->products_model->getProductinfo($product);
						if($productinfo)
						{
							self::__insertCart($productinfo,$heatseal);
						}
						else
						{
							$result = array("status"=>'success','cartstatus'=>0,'message'=>$customerstatus);
						}
						
					}
					else
					{
						$result = array("status"=>'success','cartstatus'=>0,'message'=>$customerstatus);
					}
				echo json_encode($result);exit;	
	}
	
	function __getProducttype()
	{
		$product=$_POST['product'];
		$producttypes=$this->process_order_model->get_product_types($product);
							
						$outhtml='<div class="prdtype">';
						$col = (count($producttypes) > 3) ?  '3':  '4'; 
						
						if($producttypes)
						{
							$i=0;
							foreach($producttypes as $proitems)
							{
									
								$outhtml.='<div class="col-md-'.$col.'">';
								
								
								if($this->session->userdata['producttypeskip'][$product][$proitems['id']] ||
								$this->session->userdata['producttype_cart'][$product][$proitems['id']])
								{
									$outhtml.=' <input disabled="disabled" type="radio" class="producttype" value="'.$proitems['id'].'" id="producttype_'.$proitems['id'].'" name="producttype" onclick="producttypepopup(\''.$proitems['id'].'\',\''.$product.'\');" />';
								}
								else
								{
									$outhtml.=' <input type="radio" class="producttype" value="'.$proitems['id'].'" id="producttype_'.$proitems['id'].'" name="producttype" onclick="producttypepopup(\''.$proitems['id'].'\',\''.$product.'\');" />';
								}
								 
								$outhtml.=$proitems['name'];
								
								
								$outhtml.='</div>';
								 
								 if($this->session->userdata['producttypeskip'][$product][$proitems['id']] ||
								$this->session->userdata['producttype_cart'][$product][$proitems['id']])
								{
									$outhtml.='<div style="display:none;" class="col-md-6">
								 <input type="button" disabled="disabled" id="skip_'.$proitems['id'].'" class="skipbtn btn" value="Skip '.$proitems['name'].'" name="producttype" /></div>';
								
								 }
								 else
								 {
									 $outhtml.='<div  style="display:none;" class="col-md-6">
								 <input type="button"  id="skip_'.$proitems['id'].'" class="skipbtn btn" value="Skip '.$proitems['name'].'" name="producttype" onclick="skipscan(\''.$proitems['id'].'\',\''.$product.'\');" /></div>';
								
								 }
								
								 
								 
								 $i++;
							}
						}
						
						$outhtml.='<input type="hidden" data="'.count($producttypes).'" name="qty_'.$product.'" id="qty_'.$product.'" value="0" />';
						
						$outhtml.='</div><div class="numeric-wrapper">
						
						<div class="clearfix"></div>
						
						<div class="modal-footer">
						<button type="button" data-dismiss="modal" onclick="addTocartDT();" class="btn green addProducbutton">Add to Cart</button>
						<button type="button" onclick="resetDT();" data-dismiss="modal" class="btn red addProductreset">Slett</button>
						
						<button type="button" onclick="removeBarcode();" data-dismiss="modal" class="btn red addProductreset">Add Heatseal</button>

					</div>

                <div class="clearfix"></div>
             </div>';
						
		
					$result = array('status'=>'success','protype'=>$outhtml);
					echo json_encode($result);exit;	
	}
	
	function __validateBarcode()
	{
		if(count($_POST) > 0)
		{
			if(isset($_POST['barcode']))
			{
				$barcode=$_POST['barcode'];
				$baginfo=$this->process_order_model->getBaginfo($barcode,'client');
				if($baginfo)
				{
					if($baginfo['type'] == 'client')
					{
						$result = array('status'=>'success','bagstatus'=>1,'message'=>'');
					}
					else
					{
						
						$message='Denne strekkode tilhører smart vaskeri';
						$result = array('status'=>'success','bagstatus'=>0,'message'=>$message);
					}
					
					
				}
				else
				{
					$message='Denne strekkoden ikke tildele noen kunde';
					$result = array('status'=>'success','bagstatus'=>0,'message'=>$message);
				}
			}
			else
			{
				$message='ugyldig';
				$result = array('status'=>'success','bagstatus'=>0,'message'=>$message);
			}
			
		}
		else
		{
			$message='ugyldig';
			$result = array('status'=>'success','bagstatus'=>0,'message'=>$message);
		}
		
		echo json_encode($result);exit;	
		
	}
	
	/*get bag details*/
	function __getheatsealDetail()
	{
	
	
		//profiling
		$this->data['controller_profiling'][] = __function__;
		
		
		$order_id = ($this->uri->segment(3)) ? $this->uri->segment(4) : '';
	
	if(intval($order_id) == 0)
	{
		$order_id=$_POST['orderid'];
	}
	
		$this->data['vars']['iframe_order']=intval($order_id);

		
		
		$this->data['visible']['bekreft_status']=$this->process_order_model->getOrderlogstatus($order_id,5);
		
		

		
			if(isset($_POST['orderid']))
			{
				$newdata = array('orderid'  => $_POST['orderid']);
				$this->session->unset_userdata('barcode');
				$this->session->set_userdata($newdata);
				$barcode = $_POST['orderid']; // barcode of a bag
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
		
		//echo '<pre>';print_r($this->data['blocks']['blk1']);exit;
        //get results and save for tbs block merging
		if(count($this->data['blocks']['blk1'] > 0)) {
			
			for($i=0;$i<count($this->data['blocks']['blk1']);$i++){
			
			$order_id=$this->data['blocks']['blk1'][$i]['id'];	
			
			
			
			$customer = $this->orders_model->getCustomerDetails($order_id);
			
		//	echo '<pre>';print_r($customer['customer']);exit;
			$orderinfo = $this->orders_model->getOrderinfo($order_id,'scan');
		
			$order_status = $orderinfo['order_status'];
			
			$this->data['visible']['wi_show_order'] = 0;

			
			if(!empty($orderinfo)){
				
			$this->data['visible']['wi_show_order'] = 1;
				
				
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
			   ';
				
				
				
				 
				 
				$company_info .='

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
							
							
							$branch =  ($company == $this->data['settings_company']['company_name']) ?  '':  (($company != $this->session->userdata['partner_branch_name']) ? '('.$company .')' :  ''); 
							
							
						    $amount = ($oitems['changed_amount']!='') ? $oitems['changed_amount'] :  $oitems['total_amount'];
							

							$o_time=$datetitle=date('H:i',strtotime($oitems['order_time']));;
							$customerorderdetails.='<div class=" order-list row">
							<div class="col-md-9">
							<p><a href="#" rel="'.$oitems['id'].'" class="green-text"> #'.$oitems['id'].' '.$branch.'</a><span>kr '.formatcurrency($amount).'</span>
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
		
	
			if(!empty($data)){
			
	
				$delsum = 0;
				$lstatus=1;
				$orderlinedelivery=array();
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
					//echo '<pre>';print_r($data);exit;
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
								$onclick='';
								// onclick="setNeworderline(\''.$data[$j]['id'].'\',\'1\',\''.$proqty.'\');"
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
							//	$onclick=' onclick="setNeworderline(\''.$data[$j]['id'].'\',\'0\',\''.$proqty.'\');"';
								
								$onclick='';
								
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
						  $str.='<font data="0" id="qty1_'.$data[$j]['id'].'">0</font>'; 
						 $str.=' / '.$data[$j]['quantity'].'</span>';
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
		}
		$this->data['lists']['orderlines']=$str;
		//echo '<pre>';print_r($this->data['visible']);exit;
	 // }

	//	echo '<pre>';print_r($str);exit;
		
		
		//echo json_encode($data);exit;
		
        //log debug data
		//$this->__ajaxdebugging();
        //load the view for json echo
		
        //$this->__flmView('common/json');
		
		//$this->data['lists']['orderlist'] = $str;		
		
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
			 
			 

		//	echo '<pre>';print_r($barcodes);exit;
			 
			 $amtstatus=0;
			 $count=0;
			 $boolean = true;
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
				
				if($baritems['barcode'] != '')
				{
					
					$boolean = false;
					$style = ($baritems['status'] == '17') ? 'style="color:red;"' : '';
						
					$str.='<tr '.$style.'>
					<td width="40%" style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;">'.$name.'</td>
					 <td width="5%" style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;"> : </td>';
					 if($baritems['status'] == '17')
					{
						$str.='<td id="td_'.$baritems['heatid'].'" width="30%" style="color:red;text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;">HS #'.$baritems['barcode'].'</td>';
					}
					else
					{
						$str.='<td  id="td_'.$baritems['heatid'].'" width="30%" style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;">HS #'.$baritems['barcode'].'</td>';
					}
					
					$str.='<td id="heat_status_'.$baritems['heatid'].'" width="25%" style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;">';
					if($baritems['status'] == '17')
					{
							$str.='<input type="button" value="'.$this->data['lang']['lang_found'].'" class="orange" onclick="updateMissStatus(\''.$baritems['heatid'].'\',\'22\',\''.$baritems['id'].'\');" /></td></tr>';
                 
					}
					else
					{
							$str.='<input type="button" value="'.$this->data['lang']['lang_missing'].'" onclick="updateMissStatus(\''.$baritems['heatid'].'\',\'17\',\''.$baritems['id'].'\');" /></td></tr>';
                 
					}
				
							   
					//if($amtstatus == 0)
			  
					if(intval($baritems['additional_product']) > 0)
					{
						$amtstatus=1;
					}
			  
				}
			  
			  }
			}
			
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
		
	function __updateMissStatus()
	{
		if(isset($_POST['heatseal']))
		{	
			$heatsealid=$_POST['heatseal'];
			$status=$_POST['status'];
			$orderline=$_POST['orderline'];
			
			$this->process_order_model->updateMissfoundStatus($heatsealid,$status,$orderline);
			
			$buttonstr='';
			if($status == 17)
			{
				$buttonstr='<input type="button" value="'.$this->data['lang']['lang_found'].'" class="orange" onclick="updateMissStatus(\''.$heatsealid.'\',\'22\',\''.$orderline.'\');" />';
				$newstatus='22';
			}
			if($status == 22)
			{
			//	$buttonstr='<input type="button" value="Miss" onclick="updateMissStatus(\''.$heatsealid.'\',\'17\',\''.$orderline.'\');" />';
				$buttonstr='';
				$newstatus='17';
			}
			
			$result = array('status'=>'success','message'=>'Status has been updated.','button'=>$buttonstr,'newstatus'=>$newstatus);
	
		}
		else
		{
			$result = array('status'=>'fail','message'=>'Invalid.');
		}
		echo json_encode($result);exit;
	}

	function __getorderreceipt1()
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
			     
			<p class="text-center"><span>'.$company.'</span><br>
			  '.$address.'<br>
			  '.$phone.'</p>
				</div>
		  <hr>';
		
		
		$orderdetails = $this->orders_model->getOrderLine($order_id);
		$collectiontinfo=$this->orders_model->getCollectionDeliverytime('collection',$orderinfo['collection_time']);
		$deliveryinfo=$this->orders_model->getCollectionDeliverytime('delivery',$orderinfo['delivery_time']);
		
		
			$address=$customer['street_line_1'];
			if($customer['street_line_2'] != '')
			{
				$address.=', '.$customer['street_line_2'];
			}
			
			
		   $customer_detail='<div class="customer-detail mt-sm">
			   <div class="pull-left col-md-6 no-padd">
			   <p><span>Ordrenr: </span>#'.$order_id.'</p>
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
			//$str='';
			if(!empty($data)){
				$delsum = 0;
				$lstatus=1;
				$orderlinedelivery=array();
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
						 $str.='<font data="0" id="qty_'.$data[$j]['id'].'">0</font> / '.$data[$j]['quantity'].'</span>';
					}
					else
					{
						 $str.='<font data="'.$newlstatus.'" id="qty_'.$data[$j]['id'].'">'.$newquantity.'</font> / '.$data[$j]['quantity'].'</span>';
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
		
		if($order_status < 5){
		
		$order_details.=' <div class="row">
                     <div class="col-md-3 pull-left">';
                       /*<button type="button" id="tilbake" onclick="window.location.href=\''.base_url().'admin/\'" class="btn-lg orange">Tilbake</button>*/
                  $order_details.='   </div>
                     <div class="col-md-3 pull-right no-padd">
                       
                      </div>               
                    <div class="clearfix"><input type="hidden" id="ordrenr" name="ordrenr" value="'.$order_id.'" /></div> 
                </div> ';
				
		}
		
		$result = array("order_details"=>$order_details);
		echo json_encode($result);exit;
  
	}
	
	function __deleteHeatseal()
	{
		$heatlogid=$_POST['heatseallog'];
		if(intval($heatlogid) > 0)
		{
			$res = $this->process_order_model->deleteHeatseal($heatlogid);
			if($res)
			{	
				$result = array('status'=>'success','message'=>'Heatseal has been successfully deleted.');
			}
			else
			{	
				$result = array('status'=>'error','message'=>'Invalid.');
			}
		}
		else
		{	
				$result = array('status'=>'error','message'=>'Invalid.');
		}
		echo json_encode($result);exit;
	}
	
	function __deliveryOrder()
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
			   <p><span>Ordrenr: </span>#'.$order_id.'</p>
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
	
$str .='<div class="pull-left col-md-5">
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
<div class="pull-left col-md-1 text-right">
<p><b>Delivery</b></p>
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
					
					
					$order_delivery_status=$this->orders_model->validateOrderlinedelivery('order',$order_id);
					
					$orderline_delivery_status=$this->orders_model->validateOrderlinedelivery('orderline',$data[$j]['id']);
					
					$chkboxstatus='1';
					
					if($order_delivery_status == true && $orderline_delivery_status == false)
					{
						$chkboxstatus='0';
					}
					
					if($order_delivery_status == false && $orderline_delivery_status == true)
					{
						$chkboxstatus='0';
					}
					
			
				
					
					    if($chkboxstatus)
						{
						$str.=' <div id="tr_'.$data[$j]['id'].'" class="orderlist ">';
						}
						else
						{
							$str.=' <div id="tr_'.$data[$j]['id'].'" class="orderlist order'.$data[$j]['payment_status'].'">';
						}
					
					$str .= '
					<div class="pull-left col-md-1 no-padd text-center">';
					
					//echo $data[$j]['payment_status'];
					
					if($data[$j]['payment_status'] == 'pending')
					{
						$paidstatus=0;
						$pendingstatus=1;
						if($chkboxstatus)
						{
							$str.= '<input type="checkbox" onclick="payitem(this);" name="corderlines[]" id="corderlines_'.$data[$j]['id'].'"  value="'.$data[$j]['id'].'" />';
						}
						else
						{
							$str.= '<input type="checkbox" disabled="disabled" name="corderlines" id="corderlines_'.$data[$j]['id'].'"  value="" />';
						}
						
					}
					else
					{	
					
						if($chkboxstatus)
						{
							$str.= '<input type="checkbox" onclick="payitem(this);" name="corderlines[]" id="corderlines_'.$data[$j]['id'].'"  value="'.$data[$j]['id'].'" />';
						}
						else
						{
							 $str.= '<input type="checkbox"  checked="checked"  disabled="disabled"  name="corderlines" id="corderlines_'.$data[$j]['id'].'"  value="" />';
						}
					  
					
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
						
					   $str .= '</div>  <div class="pull-left col-md-5">
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
		<div class="pull-left col-md-1  text-right">';
						if($chkboxstatus)
						{
							$str.='<p class="tprice" >Pending</p>';
						}
						else
						{
							$str.='<p class="tprice" >Delivery</p>';
						}
       
	  $str.='</div>

        <div class="pull-left col-md-2  text-right">
       <p class="tprice" id="subtotal_'.$data[$j]['product'].'"> kr '.formatcurrency($productPrice).'</p>
	   </div>
				     <input type="hidden" value="'.$productPrice.'" name="ordertotal['.$data[$j]['id'].']" id="oltotal_'.$data[$j]['id'].'" />
	   
	     <input type="hidden" value="'.$data[$j]['payment_status'].'" name="orderpayment['.$data[$j]['id'].']" />
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
					
					$order_details.='<button onclick="deliveryorderlines();" type="button"  class="btn-lg green npayment_type">Utlever</button>';
                     
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
	
	function __upatedeliverystatus()
	 {
		
		$corderlines=$_POST['corderlines'];
		$orderid=$_POST['eorder_id'];
		$totalorderline=intval($_POST['totalorderline']);
		$checkedoderline=intval($_POST['checkedoderline']);
		
		
		if(count($corderlines) > 0)
		{	
			foreach($corderlines as $oline)
			{	
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
				
				$heatdata = $this->orders_model->orderlineHeatseal($olid);
				
			
				if(count($heatdata) > 0)
				{
					$this->process_order_model->updateHeatsealstatus($heatdata,'18',$orderid);//delivered
				}
				
				
				$this->process_order_model->updateOrderlogstatus($orderid,$olid,'9');
				
				
				//SELECT * FROM `a_heat_seal_log` WHERE orderline='25' AND status='started' group by heat_seal
				/*
				//_____SQL QUERY_______
			$qry2="INSERT INTO a_order_log SET 
			`orderline`='".$orderline."',
			`status`='9',
			 partner='".$this->session->userdata['partner']."',
			 partner_branch='".$this->session->userdata['partner_branch']."',
			 employee_p_branch='".$this->session->userdata('employee_p_branch')."'";
			
			$query = $this->db->query($qry2);
			$results = $this->db->insert_id(); //last item insert id
			
			self::updateOrderlinelogstatus($orderid,$orderline);
				
				*/
			}
		
			
			$orderdetails = $this->orders_model->getOrderLine($orderid);
			$order_delivery_status=$this->orders_model->validateOrderlinedelivery('order',$orderid);
			
			if(count($orderdetails) > 0)
			{
				$status=0;
				foreach($orderdetails as $oitems)
				{
				
					$orderline_delivery_status=$this->orders_model->validateOrderlinedelivery('orderline',$oitems['id']);
					
					$chkboxstatus='0';
					
					if($order_delivery_status == true && $orderline_delivery_status == false)
					{
						$chkboxstatus='1';
					}
					
					if($order_delivery_status == false && $orderline_delivery_status == true)
					{
						$chkboxstatus='1';
					}
					
					
					if($chkboxstatus)
					{
						$status++;
					}
					
				}
				
				if($status == count($orderdetails))
				{
						$qry="INSERT INTO a_order_log 
						SET 
						`order`='".$orderid."',
						partner='".$this->session->userdata['partner']."',
						partner_branch='".$this->session->userdata['partner_branch']."',
						employee_p_branch='".$this->session->userdata('employee_p_branch')."',
						regtime='".date('Y-m-d H:i:s')."',
						status='9'";
						$query=$this->db->query($qry);
				}
			}
			
			$result = array("status"=>'success','orderid'=>$orderid,"message"=>"Selected orderlines has been successfully Delivered.");
			
			
		}
		else
			{
				$result = array("status"=>'error',"message"=>"Invalid");
			}
			
			echo json_encode($result);exit;
		
	 }
	 
	
	

	
}
/* End of file customer.php */
/* Location: ./application/controllers/admin/customer.php */