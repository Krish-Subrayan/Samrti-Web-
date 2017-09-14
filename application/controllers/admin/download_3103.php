<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming download form data as pdf related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Download extends MY_Controller
{

    /**
     * constructor method
     */
    public function __construct()
    {

        parent::__construct();

        //profiling::
        $this->data['controller_profiling'][] = __function__;

    }

    /**
     * This is our re-routing function and is the inital function called
     */
    function index()
    {

        /* --------------URI SEGMENTS----------------
        *
        * /admin/tasks/2/view/*.*
        * (2)->controller
        * (3)->project_id
        * (4)->router
        *
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //get patient id
        $this->order_id = $this->uri->segment(3);

		
		$this->data['vars']['path']= PATHS_APPLICATION_FOLDER.'themes/default/common/img/logo.png';
		
		$this->data['vars']['logo']= '<img src="'.$this->data['vars']['path'].'" alt=""/>';
		
		//print_r($this->data['vars']);
		

        //PERMISSIONS CHECK - ACCESS
        //do this check before __commonAll_ProjectBasics()
        /*if ($this->data['vars']['my_group'] != 1) {
                redirect('/admin/error/permission-denied');
        }*/

        //get the action from url
        $action = $this->uri->segment(3);

        //route the rrequest
        switch ($action) {
			case 'print':
				 //template file
				 $this->data['template_file'] = PATHS_ADMIN_THEME . 'print.html';
				 $this->__printReceipt();
				 //$this->__printTag();
				 break;	
			case 'infile':
				 //template file
				 $this->__createInfile();
				 //$this->__printTag();
				 break;		 
            default:
                $this->__report();
                break;
				
				
				
        }


        //load view
        if ($action != 'pdf') {
            $this->__flmView('admin/main');
        }
    }
	
	

		
		/*print order receipt once ordered*/
		function __printReceipt(){
			
			//profiling
			$this->data['controller_profiling'][] = __function__;
			
			//$order_id = 20154586;
			$order_id = $this->uri->segment(4);
			
			//load helper
			$this->load->helper('download');
			
			
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
				
            	$employee = '<li style="margin:0; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 36px; font-weight: normal; color: #000; list-style: none;">Kasserer: [employee.initial]</li>';
			
			}
			$this->data['lists']['employee'] =$employee;	
			
			
			//get orderline
			$orderdetails = $this->orders_model->getOrderLine($order_id);
			
			//print_r($orderdetails);
			
			$str ='';
			
			$delsum = 0;
			$orderlinedelivery=array();
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
                <td nowrap="nowrap" style="text-align: left;width:18%; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 40px; font-weight:bold;  vertical-align: top;">'.$quantity.'</td>
                <td style="text-align: left; padding: 5px 0 0;width:50%; font-family: \'arial\', monospace; font-size: 40px; font-weight:normal;vertical-align: top; ">'.$orderdetails[$i]['name'];
				
				  $boolean = true ;
				
				  if(($orderdetails[$i]['in_meter'] == 1) && ($boolean))
				  {
					$meter_text =1;
					$boolean = false;
				  }
					
				
				if($orderdetails[$i]['special_instruction']!=''){
					$html .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;font-size: 40px; line-height:28px"><b>Fritekst :</b><br>'.$orderdetails[$i]['special_instruction'].'</p>';
				}
				
				if($orderdetails[$i]['complain']==1){
					$html .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;font-size: 40px; line-height:28px"><b>Reklamasjon</b></p>';
				}
				
				if($orderdetails[$i]['in_house']==1){
					$html .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;font-size: 40px; line-height:28px"><b>Renses på huset</b></p>';
				}
				

			    $vary = ($orderdetails[$i]['in_meter'] == 1) ? "*" : '' ;
				
				
				$html .='</td>
                <td nowrap="nowrap" style="text-align: right;width:20%; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 40px; font-weight:normal;vertical-align: top;">kr '.formatcurrency($productPrice).$vary.'</td>
              </tr>';
			  $str.=$html;
			  $categoryorderline[$orderdetails[$i]['category']][$i]['item']=$html;
			  
			  
			  $ferdig = $orderlinedelivery[$i].'<div class="separator" style="border-top: #000 dashed 1px;margin: 5px 0;height:1px"></div>';
				
			 $categoryorderline[$orderdetails[$i]['category']][$i]['delivery']=$ferdig;
			  
			  
			}
			
			//print_r($categoryorderline);
			
			
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
				 <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 40px; font-weight:normal ">Delsum'.$min_price_txt.'</td><td nowrap="nowrap" style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight:normal; border-top:#000 dashed 1px;">kr '.formatcurrency($subtotal).'</td>
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
             <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 40px; font-weight:normal ">Discount ('.$vouchercode.')</td><td nowrap="nowrap" style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight:normal; border-top:#000 dashed 0px;">kr '.formatcurrency($discount).'</td>
              </tr>';
			
		}
		
		  
				
			$fprice=$cus_sub_total-$discount;
		
			if($orderinfo['type'] != 'shop')
			{
				if($fprice < $min_price)
				{
					
					$summery.='<tr>
					 <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 40px; font-weight:normal ">Minste beløp</td><td  nowrap="nowrap" style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight:normal; border-top:#000 dashed 0px;">kr '.formatcurrency($min_price).'</td>
					  </tr>';
					  
				}
			
			}
		
		
		
		$price=$subtotal-$discount;
		$frakt=$delviery;
		
			if($orderinfo['type'] != 'shop')
			{
				$summery.='<tr>
				 <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 40px; font-weight:normal ">Levering</td><td   nowrap="nowrap" style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight:normal; border-top:#000 dashed 0px;">kr '.formatcurrency($delviery).'</td>
				</tr>';
			}  
			  //mva
				$mva=$this->data['fields']['order']['totalt']/1.25;	
				
				$mva=round($mva, 2);
				
				$mva=$this->data['fields']['order']['totalt']-$mva;
				$this->data['lists']['mva'] =$mva;	
			
			  
			  $summery.='<tr>
                <td colspan="2" style="text-align: right; padding:0px; font-family: \'arial\', monospace; font-size: 40px; font-weight:normal ">Herav 25% MVA</td>
                <td style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight:normal;">kr '.formatcurrency($mva).'</td>
              </tr>
              <tr>
                <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 40px; font-weight:normal ">Totalt</td>
                <td style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight:normal; border-top:#000 dashed 1px; border-bottom:#000 dashed 1px; ">kr '.formatcurrency($this->data['fields']['order']['totalt']).'</td>
              </tr>';
			  
			  if($saldo_amount != '' && $saldo_amount != '0,00')
			  {
				  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 40px; font-weight:normal ">Kassakredit saldo: kr '.$saldo_amount.'</td></tr>';
			  }
			
			   
			   if($meter_text == 1){
			   $summery.=' <tr>
              <td colspan="3" valign="top" align="left" style="text-align: left; padding: 10px 0 0; font-family: \'arial\', monospace; font-size: 40px; font-weight:normal;"><i>* Prisen blir kalkulert når ferdig</i></td>
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
				  <td valign="top" colspan="3" align="left" style="margin:0; padding:0; font-family: \'arial\', monospace; font-size: 40px; font-weight: bold;  color: #000">Notater</td>
				</tr>
				<tr>
				  <td colspan="3" valign="top" align="left" style="text-align: left; padding: 10; font-family: \'arial\', monospace; font-size: 40px; font-weight:normal;">'.$this->data['fields']['order']['delivery_note'].'</td>
				</tr>';
			}
			
			$this->data['lists']['special_instruction']='';
			
			
			if($this->data['fields']['order']['special_instruction'] != '')
			{
				 $this->data['lists']['special_instruction'] = '<tr>
				  <td valign="top" colspan="3" align="left" style="margin:0; padding:0; font-family: \'arial\', monospace; font-size: 40px; font-weight: bold;  color: #000">Spesialinstruksjoner</td>
				</tr>
				<tr>
				  <td colspan="3" valign="top" align="left" style="text-align: left; padding: 10; font-family: \'arial\', monospace; font-size: 40px; font-weight:normal;">'.$this->data['fields']['order']['special_instruction'].'</td>
				</tr>';
			}
			
			
			
			$this->data['lists']['orderlinedelivery']='';
			if(count($checkdeliverydate) == 1)
			{
				$delivery_dates=implode(',',$checkdeliverydate);
				
				$this->data['lists']['orderlinedelivery'] = '<tr>
				  <td colspan="4" style="margin:0px 0; padding:10px 0; font-family: \'arial\', monospace; font-size: 55px; font-weight: bold;  color: #000" valign="top" align="left">Ferdig: '.$delivery_dates.'</td>
				</tr>';
			}
			else
			{
				if(count($orderlinedelivery) > 0)
				{
					$orderlinedelivery_time=implode('<div class="separator" style="border-top: #000 dashed 1px;margin: 5px 0;height:1px"></div>',$orderlinedelivery);
					
					$this->data['lists']['orderlinedelivery'] = '<tr>
				  <td colspan="4" style="margin:0px 0; padding:10px 0; font-family: \'arial\', monospace; font-size: 55px; font-weight: bold;  color: #000" valign="top" align="left">Ferdig: </td>
				</tr><tr>
					<td valign="top" colspan="4" align="left" style="margin:0px 0; padding:10px 0; font-family: \'arial\', monospace; font-size: 20px; font-weight: bold;  color: #000">'.$orderlinedelivery_time.'</td>
					</tr>';
				}
			}
			
			
			
			$commonheader='     <table  width="294" align="center" border="0" cellpadding="0" cellspacing="0"   bgcolor="#0ff">
  <tr>
  <td valign="top" align="center"> 
<table  width="100%" align="center" border="0" cellpadding="0" cellspacing="0" bgcolor="#00f"> 
    <tbody>
      <tr>
        <th valign="top" align="center" style="padding:0 0px 40px 0;"><ul style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 15px; font-weight: normal;text-align:center;">
            <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 14px; font-weight: normal; color: #000; list-style: none;"><strong>'.$this->data['fields']['branch']['company'].'</strong></li>
            <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 40px; font-weight: normal; color: #000; list-style: none;">'.$this->data['fields']['branch']['street'].'</li>
            <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 40px; font-weight: normal; color: #000; list-style: none;">'.$this->data['fields']['branch']['zip'].'</li>
            <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 40px; font-weight: normal; color: #000; list-style: none;">Telefon:+47 '.$this->data['fields']['branch']['phone'].'</li>
            <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 40px; font-weight: normal; color: #000; list-style: none;">Org.nr: '.$this->data['fields']['branch']['org_nr'].'</li>

          </ul></th>
      </tr>
      <tr>
        <td valign="top" align="center" style="padding:0 0px 40px 0;"><ul style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 40px; font-weight: normal">
        <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size:16px; font-weight: normal; color: #000; list-style: none;"><img src="'.base_url().'admin/barcode/[order.id]" alt="Barcode" style="width:200px;height:30px;"></li>
        <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size:25px; font-weight: normal; color: #000; list-style: none;">[order.id]</li>
		<li style="margin:0; padding: 0; font-family: \'arial\', monospace;font-weight: normal; color: #000; list-style: none;">&nbsp;</li>

         </ul></td>
      </tr>
      <tr>
			<td valign="top" align="left" style="padding:0 0 40px 0;"><ul style="margin:0; padding:0 0 0 2px; font-family: \'arial\', monospace; font-size: 40px; font-weight: normal">
            <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 12px; font-weight: normal; color: #000; list-style: none;">+47[customer.number]</li>
            <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 16px; font-weight: normal; color: #000; list-style: none; text-transform:uppercase">[customer.customer_name]</li>
            <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 12px; font-weight: normal; color: #000; list-style: none;">[customer.address]</li>
            <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 12px; font-weight: normal; color: #000; list-style: none;">[customer.zip] [customer.city]</li>
               <!--[lists.employee;noerr;htmlconv=no;protect=no;comm]-->
            <li style="margin:0; padding: 0; font-family: \'arial\', monospace; font-size: 12px; font-weight: normal; color: #000; list-style: none;">[order.order_time]</li>
           </ul>
		   </td>
      </tr>
      <tr>
        <td  valign="top" align="center"><table width="100%" align="center" border="0" cellpadding="0" cellspacing="0">
            <thead>
              <tr>
                <th style="text-align: left; width:18%; border-top:#000 dashed 1px; border-bottom:#000 dashed 1px; padding: 10px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight:normal ">Ant.</th>
                <th style="text-align: left; width:60%; border-top:#000 dashed 1px; border-bottom:#000 dashed 1px; padding: 10px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight:normal ">Artikler</th>
                <th style="text-align: right; width:15%; border-top:#000 dashed 1px; border-bottom:#000 dashed 1px; padding: 10px 0; font-family: \'arial\', monospace; font-size: 40px; width:20%; font-weight:normal ">Beløp</th>
				<th style="text-align: right; width:5%;">&nbsp;</th>
              </tr>
            </thead>
            <tbody>';
			
			
	$commonfooter='</tbody>
			 <tr>
              <td colspan="4" nowrap="nowrap" style="text-align: center; padding: 5px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight:normal;  vertical-align: top;">&nbsp;</td>
            </tr>
			</tbody>
          </table></td>
      </tr>
        <tr>
              <td colspan="4" valign="top" align="center" style="text-align: center; padding: 40px; font-family: \'arial\', monospace; font-size: 25px; font-weight:normal;">Intern kopi</td>
            </tr>
    </tbody>
  </table>  </td>
  </tr>
  </table>  

';

		
		$categoryprint='';
		
		if(count($orderdetails) > 1){
			
            if(count($categoryorderline) > 0)
			{
				foreach($categoryorderline as $catitems)
				{

					if(count($catitems) > 0)
					{
					
						$categoryprint.='<div class="page-break"></div>';
						$categoryprint.=$commonheader;
						$catout='';
						$catdelivery = '';
						foreach($catitems as $key=>$cathtml)
						{
							$catout.= $catitems[$key]['item'];
							$catdelivery .= $catitems[$key]['delivery'];
							
							//echo $catitems[$key]['item']."<br>";
							
						}
					
						$categoryprint.=$catout;
						
						
					$delivery_date = '<tr>
              <td colspan="4" nowrap="nowrap" style="text-align: center; padding: 5px 0; font-family: \'arial\', monospace; font-size: 15px; font-weight:normal;  vertical-align: top;">&nbsp;</td>
            </tr>
			<tr>
				  <td  style="margin:0px 0; padding:10px 0; font-family: \'arial\', monospace; font-size: 20px; font-weight: bold;  color: #000" valign="top" align="left">Ferdig: </td>
				</tr><tr>
					<td valign="top" colspan="4" align="left" style="margin:0px 0; padding:10px 0; font-family: \'arial\', monospace; font-size: 20px; font-weight: bold;  color: #000">'.$catdelivery.'</td>
					</tr>';
			
						
						
						$categoryprint.= $delivery_date.$commonfooter;
					}
				
				}
				
			}
	
		}
               
             

			$this->data['lists']['categoryprint']=$categoryprint;
			
			
			
			$next = true;


			$filename  =  "print.pdf";
			
        //start to generate the invoice1
        if ($next) {
            //reduce error reporting to only critical
            @error_reporting(E_ERROR);
            //turn off codeigniter profiler (which may be on in MY_Controller) during debug mode
            $this->output->enable_profiler(false);
            //generate the invoice view as normal, but buffer it to variable ($html)
            ob_start();
            $this->__flmView('client/main');
            $html = ob_get_contents();
            
            ob_end_clean();
            /*------------------------------- GENERATE PDF------- (mpdf class)------------------------/
            * Take generated html and passes it to mpdf class pdf output is saved in variable $pdf
            *
            *----------------------------------------------------------------------------------------*/
            $this->load->library('dompdf_lib');
            $dompdf = new DOMPDF();
            // Convert to PDF
            //$this->dompdf->set_paper(DEFAULT_PDF_PAPER_SIZE, 'portrait');
            $this->dompdf->set_paper("A4", "portrait");
            $this->dompdf->set_base_path(realpath(PATHS_COMMON_THEME . 'style/invoice.print.css'));
            $this->dompdf->load_html(htmlspecialchars_decode($html));
            $this->dompdf->render();
            $pdf = $this->dompdf->output();
            /*-------------------------------------- GENERATE PDF END -------------------------------*/
            //force download
            //force_download($filename, $pdf);
            //if we want user to view in browser (comment out the force_download)
            $this->dompdf->stream($filename, array("Attachment" => false));
            exit(0);
        }
			
			

   }

	
	
	
    /**
     * generate a bil for report ( daily as well between time dates)
     * uses the mpdf class
     * @attribution
     * http://www.mpdf1.com/mpdf/index.php?page=Download
     * http://davidsimpson.me/2013/05/19/using-mpdf-with-codeigniter/     *
     * @param   string $output display on screen or save as file
     */
    function __report($output = 'view')
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;
        //template file
        $this->data['template_file'] = PATHS_COMMON_THEME . 'download/print.report.html';
        //flow control
        $next = true;
        //load helper
        $this->load->helper('download');
		
		$date = $this->input->post('from');

	
		//$date ='14.03.2017';
		
		
		$this->data['vars']['from'] = ($this->input->post('from')=='') ? date('d.m.Y'): date('d.m.Y',strtotime($this->input->post('from')));
		
		
		
		$this->data['vars']['to'] = ($this->input->post('to')=='') ? date('d.m.Y'): date('d.m.Y',strtotime($this->input->post('to')));
//print_r();exit;
		
        //check if invoice exists
        if ($next) {
		
		
		  
		//get results for orders in process (orders which are placed , in process)
		$this->data['reg_blocks'][] = 'account';
		$this->data['reg_blocks'][] = 'account1';
		$this->data['reg_blocks'][] = 'account2';
	//	$this->data['blocks']['account1'] = $this->customer_model->getCustomerAccountLog('','','','',$date);
	//	$this->data['blocks']['account2'] = $this->customer_model->getCustomerpayment('','','','',$date);
	$this->data['blocks']['account1'] = $this->customer_model->getCustomerAccountLog();
		$this->data['blocks']['account2'] = $this->customer_model->getCustomerpayment();
	
		$newarray=array_merge($this->data['blocks']['account1'],$this->data['blocks']['account2']);
		
		function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {

			$sort_col = array();
			foreach ($arr as $key=> $row) {
				$sort_col[$key] = $row[$col];
			}
			
			array_multisort($sort_col, $dir, $arr);
			
			
			return $arr;
		}
		$subarray=array_sort_by_column($newarray, 'regtime');
		
	
		$this->data['blocks']['account']=$subarray;
		
		$this->data['debug'][] = $this->customer_model->debug_data;
			
			$bankarray=array();
            //set invoice name
             if (!empty($this->data['blocks']['account'])) {
				 $str = '';
				 $total = 0;
				for ($i=0; $i < count($this->data['blocks']['account']); $i++) {

				 $orderid = ($this->data['blocks']['account'][$i]['order']!='')  ? ''.$this->data['blocks']['account'][$i]['order'] :  '';		
				 
			  	$in_type  = ($this->data['blocks']['account'][$i]['in_type'] =='gift_card') ? "Gift Card" :  $this->data['blocks']['account'][$i]['in_type'];
				
				$in_type  = $this->__getInType($this->data['blocks']['account'][$i]['in_type']);
				
				//if($in_type != 'kk')
				//{
					$bankarray[$in_type][]=$this->data['blocks']['account'][$i]['amount'];
				//}
				
				
				$str .='<tr>
						<td nowrap="nowrap" style="text-align: left;width:30%; padding: 5px 0 5px; font-family: \'arial\', monospace; font-size: 30px; vertical-align: top;">'.$this->data['blocks']['account'][$i]['rdatewy'].'</td>
						<td style="text-align: left; padding: 5px 0 0;width:15%; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">'.$this->data['blocks']['account'][$i]['rtime'].'</td>
						<td style="text-align: center; padding: 5px 0 0;width:25%; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">'. $orderid.'</td>
						<td style="text-align: center; padding: 5px 0 0;width:3%; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top;vertical-align: top; ">'.strtoupper($in_type).'</td>
						<td nowrap="nowrap" style="text-align: right; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top;">kr '.formatcurrency($this->data['blocks']['account'][$i]['amount']).'</td>
					  </tr>';
						if($in_type != 'kk')
						{  
							$total +=	$this->data['blocks']['account'][$i]['amount']; 
						}
				}
				
				$footerarray=array('B'=>'Bankkort (B)','K'=>'Kontant(K)','G'=>'Gift Card (G)','F'=>'Faktura (F)','kk'=>'kasse kredit (kk)');
				
				$footerarray1=array('B'=>'bk','K'=>'kk','G'=>'gk','F'=>'fa','kk'=>'kk');
				
				$footer='';
				$totcount=0;
				$totamt=array();
				$casharray=array();
				foreach($footerarray as $fkey=>$foot)
				{
					$tot_amt=array_sum($bankarray[$fkey]);
					if($fkey != 'kk')
					{
						$totamt[]=$tot_amt;
					}
					
					//$casharray[strtolower($fkey)]=array(count($bankarray[$fkey]),$tot_amt);
					
					$newfkey=$footerarray1[$fkey];
					$totalkey=$newfkey.'_total';
					
					//$casharray[strtolower($fkey)]=$tot_amt;
					$casharray[$newfkey]=count($bankarray[$fkey]);
					$casharray[$totalkey]=$tot_amt;
					
					
					
					$amt=' kr '.formatcurrency($tot_amt);
					$footer.='<tr>
					<td valign="top" align="right" style="text-align: left;font-family: \'arial\', monospace; font-size:20px; font-weight:normal;padding: 5px 0;">'.$foot.'</td>
					<td style="text-align: center;  font-family: \'arial\', monospace; font-size: 20px; width:20%;"> '.count($bankarray[$fkey]).'
					</td>                
					<td style="text-align: right;font-family: \'arial\', monospace; font-size: 20px; width:20%;">  '.$amt.'</td></tr>';
					$totcount=$totcount+count($bankarray[$fkey]);
				}
				$tott_amount=array_sum($totamt);
				$total_amount=' kr '.formatcurrency($tott_amount);
				
				$delsumamt=$tott_amount/1.25;	
				$delsumamt=round($delsumamt, 2);
				$delsumvat=$tott_amount-$delsumamt;
				
				$vat_amount=' kr '.formatcurrency($delsumvat);
				
				
				
				$casharray['tax']=$delsumvat;
				$casharray['total_transaction']=$totcount;
				$casharray['total']=$tott_amount;
				
				
				
				$footer.='<tr>
                <td valign="top" align="right" style="text-align: left;font-family: \'arial\', monospace; font-size:20px; font-weight:normal;padding: 5px 0; font-weight:bold">Total</td>
                <td style="text-align: center;  font-family: \'arial\', monospace; font-size: 20px; width:20%;"> '.$totcount.'
                </td>                
                <td style="text-align: right;font-family: \'arial\', monospace; font-size: 20px; width:20%; font-weight:bold">  '.$total_amount.'  </td>
				</tr>';
				$footer.=' <tr>
                  <td valign="top" align="right" style="text-align: left;font-family: \'arial\', monospace; font-size:20px; font-weight:normal;padding: 5px 0; font-style:italic">MVA</td>
                <td style="text-align: center;  font-family: \'arial\', monospace; font-size: 20px; width:20%;"> 	
                </td>                
                <td style="text-align: right;font-family: \'arial\', monospace; font-size: 20px; width:20%;font-style:italic"> '.$vat_amount.' </td>
            </tr>';
				
				
				  $this->data['lists']['total'] =' kr '.formatcurrency($total);
				  $this->data['lists']['transaction'] = count($this->data['blocks']['account']);
				  
				  $this->data['lists']['report'] = $str;
				  $this->data['lists']['footer'] = $footer;
				  $filename = 'Report_'.date('d.m.Y').'.pdf';
				  $casharray['pdf_url']=$filename;
				  $casharray['start_amount']='0.00';
				  
				
					/*$cashid=$this->general_model->updateCashReport($casharray,$date);
					
					
					if($cashid)
					{
						$result = array("status"=>'success','cashid'=>$cashid);
					}
					else
					{
						$result = array("status"=>'error',"message"=>'Sorry daily report has been already printed');
					}
					echo json_encode($result);exit;
					*/
					
						echo $date;exit;
					 if(isset($_POST['status']))
				  {
					if(intval($_POST['status']) > 0)
					{
						$cashid=$this->general_model->updateCashReport($casharray,$date);
						if($cashid)
						{
							$result = array("status"=>'success','cashid'=>$cashid);
						}
						else
						{
			$result = array("status"=>'error',"message"=>'Sorry daily report has been already printed');
						}
						echo json_encode($result);exit;
					}
				  }
					
				  
				  /*$response = array('status'=>"success");
				  echo json_encode($response);exit;		*/							
				  
				  
            } else {
                //set flash notice
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                //redirect back to view the invoice
                redirect('/admin/settings/report');
            }
			
			
        }
        
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
	
    /**
     * generate a pdf for report ( daily as well between time dates)
     * uses the mpdf class
     * @attribution
     * http://www.mpdf1.com/mpdf/index.php?page=Download
     * http://davidsimpson.me/2013/05/19/using-mpdf-with-codeigniter/     *
     * @param   string $output display on screen or save as file
     */
    function __pdfReport($output = 'view')
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;
        //template file
        $this->data['template_file'] = PATHS_COMMON_THEME . 'download/print.report.html';
        //flow control
        $next = true;
        //load helper
        $this->load->helper('download');
		
		$this->data['vars']['from'] = ($this->input->get('from')=='') ? 'Fra': date('d.m.Y',strtotime($this->input->get('from')));
		
		$this->data['vars']['to'] = ($this->input->get('to')=='') ? 'Til': date('d.m.Y',strtotime($this->input->get('to')));

		
        //check if invoice exists
        if ($next) {
		
		  
		//get results for orders in process (orders which are placed , in process)
		$this->data['reg_blocks'][] = 'account';
		$this->data['blocks']['account'] = $this->customer_model->getCustomerAccountLog();
		$this->data['debug'][] = $this->customer_model->debug_data;
			
			
            //set invoice name
             if (!empty($this->data['blocks']['account'])) {
				for ($i=0; $i < count($this->data['blocks']['account']); $i++) {


				}
				
				
				  $this->data['lists']['report'] = $total;
				  $filename = $month.'_'.$year.'.pdf';
				  
            } else {
                //set flash notice
                $this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
                //redirect back to view the invoice
                redirect('/admin/not-found');
            }
			
			
        }
        
        //start to generate the invoice1
        if ($next) {
            //reduce error reporting to only critical
            @error_reporting(E_ERROR);
            //turn off codeigniter profiler (which may be on in MY_Controller) during debug mode
            $this->output->enable_profiler(false);
            //generate the invoice view as normal, but buffer it to variable ($html)
            ob_start();
            $this->__flmView('client/main');
            $html = ob_get_contents();
            
            ob_end_clean();
            /*------------------------------- GENERATE PDF------- (mpdf class)------------------------/
            * Take generated html and passes it to mpdf class pdf output is saved in variable $pdf
            *
            *----------------------------------------------------------------------------------------*/
            $this->load->library('dompdf_lib');
            $dompdf = new DOMPDF();
            // Convert to PDF
            //$this->dompdf->set_paper(DEFAULT_PDF_PAPER_SIZE, 'portrait');
            $this->dompdf->set_paper("A4", "portrait");
            $this->dompdf->set_base_path(realpath(PATHS_COMMON_THEME . 'style/invoice.print.css'));
            $this->dompdf->load_html(htmlspecialchars_decode($html));
            $this->dompdf->render();
            $pdf = $this->dompdf->output();
            /*-------------------------------------- GENERATE PDF END -------------------------------*/
            //force download
            //force_download($filename, $pdf);
            //if we want user to view in browser (comment out the force_download)
            $this->dompdf->stream($filename, array("Attachment" => false));
            exit(0);
        }
    }   

    /**
     * Generates various pulldown (<option>...</option>) lists for ready use in HTML
     * Output is set to e.g. $this->data['lists']['milestones']
     */
    function __pulldownLists()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;


    }


    /**
     * send out an email
     *
     * @param string $email email address
     */
    function __emailer($email = '', $vars = array())
    {

        //common variables
        $this->data['email_vars']['todays_date'] = $this->data['vars']['todays_date'];
        $this->data['email_vars']['company_email_signature'] = $this->data['settings_company']['company_email_signature'];
        $this->data['email_vars']['client_dashboard_url'] = $this->data['vars']['site_url_client'];

        //specific passed variables
        foreach ($vars as $key => $value) {
            $this->data['email_vars'][$key] = $value;
        }

        //-------------send out email-------------------------------
        if ($email == 'client_invoice') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate($this->data['email_vars']['email_template']);
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

            //parse email
            $email_message = parse_email_template($template['message'], $this->data['email_vars']);
            //send email
            email_default_settings(); //defaults (from emailer helper)
            //$this->email->to($this->data['email_vars']['client_users_email']);
            $this->email->to($this->data['email_vars']['client_users_email']);
            $this->email->subject($this->data['email_vars']['email_subject']);
            $this->email->message($email_message);
            $this->email->attach($this->data['email_vars']['pdfinvoice']);
            $this->email->send();

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
	
	function __printLog()
	{
		$status=0;
		$order=intval($_POST['order']);
		$sql="INSERT INTO a_print_log SET `order`='".$order."'";
		if($this->db->query($sql))
		{
			$status=1;
		}
		
		echo json_encode(array('status'=>$status));exit;
		
	}
	
	function __createInfile()
	{
		$this->process_order_model->craeteInfiledownload();
		exit;
	}

}

/* End of file invoice.php */
/* Location: ./application/controllers/admin/invoice.php */
