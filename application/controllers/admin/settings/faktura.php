<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Faktura extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'settings.faktura.html';
		
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
        $this->data['vars']['css_menu_faktura'] = 'open'; //menu
		//load helper
		$this->load->helper('download');
		
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
		
        //create pulldown lists
        $this->__pulldownLists();

        //uri - action segment
        $action = $this->uri->segment(4);

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_settings_company'];

        //re-route to correct method
        switch ($action) {
			
            case 'search-faktura-log':
                $this->__cachedFakturaLog();
                break;
			
			case 'update-invoice-status':
                $this->__updateInvoiceStatus();
				break;
				
			case 'update-order-invoice-status':
                $this->__updateOrderInvoiceStatus();
				break;
				
				
			case 'kunden-receipt':
				 //template file
				 $this->data['template_file'] = PATHS_ADMIN_THEME . 'print.kunden.html';
				 $this->__pdfKundenReceipt();
          		 break;	

			case 'saldo-receipt':
				 $this->__pdfSaldoReceipt();
          		 break;	
				 
			case 'customers':
				 //template file
				 $this->data['template_file'] = PATHS_ADMIN_THEME . 'settings.faktura.customers.html';
				 $this->__saldoCustomers();
          		 break;	
		
            case 'search-saldo-customers-log':
                $this->__cachedSaldoCustomersLog();
                break;
		
				
            default:
                $this->__viewFakturaList();
        }

        //css - active tab
        $this->data['vars']['css_menu_faktura'] = 'current';

        //load view
        $this->__flmView('admin/main');

    }


	/*download saldo as pdf for invoice*/
	function __pdfSaldoReceipt($printtype)
	{
		$saldo_id = $this->uri->segment(5);
		$this->data['reg_fields'][] = 'customer';
		$this->data['reg_fields'][] = 'branch';
		$this->data['fields']['customer'] = $customer = $this->customer_model->getSaldoDetails($saldo_id);
		
		
		if(count($this->data['fields']['customer']) == 0){
			return;
			exit;
		}
		 //template file
		 $this->data['template_file'] = PATHS_ADMIN_THEME . 'print.saldo.html';
		
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
			
			
		//get employe details who taken that order
		$employee = '';
		$this->data['reg_fields'][] = 'employee';
		$this->data['fields']['employee'] =  $this->employee_model->getEmployeeDetail($this->data['fields']['customer']['employee']);
		//print_r($this->data['fields']['employee']);
		$this->data['lists']['employee'] = '<li style="margin:0; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 24px; font-weight: normal; color: #000; list-style: none;">Kasserer: '.$this->data['fields']['employee']['initial'] .'</li>';		
		
				
			$in_type  = $this->general_model->getInType($customer['in_type']);
			
			$footerarray=array('BK'=>'Bankkort (BK)','KO'=>'Kontant(KO)','GC'=>'Gift Card (GC)','FA'=>'Faktura (FA)','KK'=>'Kasse kredit (KK)');
				
			$html ='<tr>
			<td nowrap="nowrap" style="text-align: left;width:18%; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:bold;  vertical-align: top;">1</td>
			<td style="text-align: left; padding: 5px 0 0;width:55%; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">'.$footerarray[$in_type].'</td>
			<td nowrap="nowrap" style="text-align: right;width:25%; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top;">kr '.formatcurrency($customer['amount']).'</td>';
				
				
				$this->data['lists']['orderline']=$html;
				
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
			  
			  if($this->data['fields']['customer']['autofil_invoice'] != NULL){
				  
				  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Fakturakunde</td></tr>';
				  
			  }
			  
			  else  if($saldo_amount != '' && $saldo_amount != '0,00')
			  {
				  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Kassakredit saldo: kr '.$saldo_amount.'</td></tr>';
				  
			  }
		
		
		$this->data['lists']['summery']=$summery;
		
		
		$filename = 'Faktura_'.$saldo_id;
		

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
		//Save PDF in server.
		//file_put_contents(PATH_ORDER_PDF_FOLDER.$filename.".pdf", $dompdf->output()); 			
		
		/*-------------------------------------- GENERATE PDF END -------------------------------*/
		//force download
		force_download($filename, $pdf);
		exit;
		//if we want user to view in browser (comment out the force_download)
	    // $this->dompdf->stream($filename, array("Attachment" => false));
		 //$result=array('status'=>'success');
		 //echo json_encode($result);
		
	}
	

	
	
	/*download kunden bill as pdf*/
    function __pdfKundenReceipt()
    {
		
		$order_id = $this->uri->segment(5);
		
		
		$orderinfo = $this->orders_model->getOrderinfo($order_id);
		
		if(count($orderinfo) == 0){
			return;
			exit;
		}
		
		
		//print_r($this->session);
		
		//get customer details
		$this->data['reg_fields'][] = 'customer';
		$this->data['fields']['customer'] = $customer = $this->orders_model->getCustomerDetails($order_id);
		
		//print_r($this->data['fields']['customer']);
		
		$saldo_amount='';
		/*$data = $this->payments_model->getAccountBalance($customer['customer']);
		$pendingsaldo = $data['pending'];
		$paidsaldo = $data['paid'];*/

		$pendingsaldo = $orderinfo['c_account_pending'];
		$paidsaldo = $orderinfo['c_account_paid'];
		
	
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
			$this->data['lists']['employee'] = '<li style="margin:0; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 24px; font-weight: normal; color: #000; list-style: none;">Kasserer: '.$this->data['fields']['employee']['initial'] .'</li>';		
		}
		
		
		//get orderline
		$orderdetails = $this->orders_model->getOrderLine($order_id,'print');
		//	echo '<pre>';print_r($orderdetails);exit;
		
			$str ='';
			
			$delsum = 0;
			$orderlinedelivery=array();
			$orderlinedelivery1=array();
			$orderlinedelivery2=array();
			$orderlinedelivery3=array();
			$checkdeliverydate=array();
			$categoryorderline=array();
			$weekdayarray=array("Mon"=>"Man","Tue"=>"Tirs","Wed"=>"Ons","Thu"=>"Tors","Fri"=>"Fre","Sat"=>"lør","Sun"=>"Søn");	
			$is_shirt_boolean = false;
			for($i=0;$i< count($orderdetails);$i++)
			{
				$html='';
				$chtml = '';
				$arr = $this->orders_model->getProductDisplayName($orderdetails[$i]['product']);
				
				$orderdetails[$i]['name'] = $arr['name'];

				if($orderdetails[$i]['category']=='Skjorte'){
				  $is_shirt_boolean = true;
				}
				
				//echo '<pre>';print_r($orderdetails[$i]['p_b_delivery_time']);exit;
				if($orderdetails[$i]['p_b_delivery_time'] != '' && $orderdetails[$i]['p_b_delivery_time'] != '0000-00-00')
				{
					$b_delivery_time=date('Y-m-d',strtotime($orderdetails[$i]['p_b_delivery_time']));
					$p_b_delivery_time = date('d/m/Y',strtotime($orderdetails[$i]['p_b_delivery_time']));
					$day=date('D',strtotime($orderdetails[$i]['p_b_delivery_time']));
					$orderlinedelivery[]= '<span class="pname"> '.$orderdetails[$i]['name'].'</span> '.strtolower($weekdayarray[$day]).' '.$p_b_delivery_time;
					
					
					 if($is_shirt_boolean){
						//$etter = $esc . 'a' . '0x00'; 
						//$etter .= $newLine.'Etter kl. 13:00';
						//$etter .= $esc . 'a' . '0x00'; 	
						$etter .= '<br>'.'Etter kl. 13:00';
						
					 }
					
					 $checkdeliverydate[$b_delivery_time]=  ucfirst(str_replace($search, $replace, $weekdayarray[$day])).' '.$p_b_delivery_time.$etter;
					
					  $dstr = str_replace($search, $replace, $orderdetails[$i]['name']);
					  
					  $orderlinedelivery1[strtotime($orderdetails[$i]['p_b_delivery_time'])][]=str_replace($search, $replace, $orderdetails[$i]['name']);
					  
					  $orderlinedelivery2[strtotime($orderdetails[$i]['p_b_delivery_time'])][]=strtolower(str_replace($search, $replace, $weekdayarray[$day]));
					  
					 $orderlinedelivery3[strtotime($orderdetails[$i]['p_b_delivery_time'])][]=' '.$p_b_delivery_time.$etter.'<br>';
					  
					 if(count($orderdetails)>1){
						 $dstr .= $newLine ;
					 }
					 $dstr .= strtolower(str_replace($search, $replace, $weekdayarray[$day])).' '.$p_b_delivery_time.$etter;
					 if(count($orderdetails)>1){
						 //$dstr .= $newLine ;
					 }
					 $is_shirt_boolean = false;
					 $etter = '';
					 
					$orderlinedeliverydate[]= $dstr;
					
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
				 
				$pname = str_replace($search, $replace, $orderdetails[$i]['name']);				 
								 
				
				
				$html .='<tr>
                <td nowrap="nowrap" style="text-align: left;width:18%; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:bold;  vertical-align: top;">'.$quantity.'</td>
                <td style="text-align: left; padding: 5px 0 0;width:55%; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">'.$orderdetails[$i]['name'];
				
				  $boolean = true ;
				
				  if(($orderdetails[$i]['in_meter'] == 1) && ($boolean))
				  {
					$meter_text =1;
					$boolean = false;
				  }
				  
			  	  $vary = ($orderdetails[$i]['in_meter'] == 1) ? "*" : '' ;
				
				
			  
				if($orderdetails[$i]['special_instruction']!=''){
					$html .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;font-size: 30px; line-height:32px"><b>Fritekst :</b><br>'.$orderdetails[$i]['special_instruction'].'</p>';
					
				}
				
				if($orderdetails[$i]['complain']==1){
					$html .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;font-size: 30px; line-height:32px"><b>Reklamasjon</b></p>';
					
				}
				
				if($orderdetails[$i]['in_house']==1){
					$html .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;font-size: 30px; line-height:32px"><b>Renses på huset</b></p>';
					
				}
				
				
				$html .='</td>
                <td nowrap="nowrap" style="text-align: right;width:25%; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top;">kr '.formatcurrency($productPrice).$vary.'</td>
              </tr>';
				
				
					
			  $str.=$html;
			  
			  
			  $categoryorderline[$orderdetails[$i]['category']][$i]['item']= $chtml;
			  
			  $categoryorderline[$orderdetails[$i]['category']][$i]['name']= str_replace($search, $replace, $orderdetails[$i]['name']);		
			  
			  $categoryorderline[$orderdetails[$i]['category']][$i]['day']= strtolower(str_replace($search, $replace, $weekdayarray[$day]));
			  
			  $categoryorderline[$orderdetails[$i]['category']][$i]['date']= $orderdetails[$i]['p_b_delivery_time'];			  
			  
			  if(count($orderdetails) == 1)
			  {
					$delivery_dates=implode(',',$checkdeliverydate);
					$ferdig = $delivery_dates;
			  }
			  else{
				  $ferdig = $orderlinedeliverydate[$i];
			  }
				
			  $categoryorderline[$orderdetails[$i]['category']][$i]['delivery']= $ferdig;
			  
			  $additionalproductcount = $this->products_model->additionalProductCount($orderdetails[$i]['product']);
			 
			  $antall = ($additionalproductcount > 0) ? ($additionalproductcount *  $quantity) : $quantity;
			 
			  $categoryorderline[$orderdetails[$i]['category']][$i]['antall']= $antall;

			  $categoryorderline[$orderdetails[$i]['category']][$i]['in_meter']= $meter_text;
			  
			  $categoryorderline[$orderdetails[$i]['category']][$i]['amount'] = $productPrice;
			  
			 
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
			  
			  
			  if($this->data['fields']['customer']['autofil_invoice'] != NULL){
				  
				  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Fakturakunde</td></tr>';
				  
			  }
			  else if($saldo_amount != '' && $saldo_amount != '0,00')
			  {
				  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Kassakredit saldo: kr '.$saldo_amount.'</td></tr>';
				  
			  }
			   
			   if($meter_text == 1){
			   $summery.=' <tr>
              <td colspan="3" valign="top" align="left" style="text-align: left; padding: 10px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;"><i>* Prisen blir kalkulert når ferdig</i></td>
              </tr>';
			  
			   }
				$cmds .= $newLine;	
			  
			 
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
				  <td colspan="3" style="margin:0px 0; padding:10px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight: bold;  color: #000" valign="top" align="left">Ferdig: <br>'.$delivery_dates.'</td>
				</tr>';
				
			}
			else
			{
				//$orderlinedelivery_time = implode($newLine,$orderlinedeliverydate);
				//print_r($orderlinedelivery1);
				//print_r($orderlinedelivery2);
				//print_r($orderlinedelivery3);
				$orderlinedelivery_time = '';
				
				if(count($orderlinedelivery) > 0)
				{
				
					if(count($orderlinedelivery1) > 0)
					{
						foreach($orderlinedelivery1 as $newkey=>$newitems)
						{
							$deliveryitems=implode('<br>',$newitems);
							$deliveryitems.='<br>'.ucfirst($orderlinedelivery2[$newkey][0]);
							$deliveryitems.=$orderlinedelivery3[$newkey][0];	
							$orderlinedelivery_time .=$deliveryitems;	
							$orderlinedelivery_time.='<br>';	
							//echo $orderlinedelivery_time;						
						}
					}
					
				$this->data['lists']['orderlinedelivery'] = '<tr>
				  <td colspan="3" style="margin:0px 0; padding:10px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight: bold;  color: #000" valign="top" align="left">Ferdig: <br>'.$orderlinedelivery_time.'</td>
				</tr>';
					
				}
			}
		
			
			$deliverytime=$orderinfo['deliverytime'];
            
			if($deliverytime != '' && $orderinfo['order_status'] == 9)
			{
				
				
				$orderlineemp = $this->employee_model->getEmployeebranchDetail(0,0,$orderinfo['employee_p_branch']);
				$empinitial=$orderlineemp['initial'];
				
				$this->data['lists']['utlevert'] = '<tr>
				  <td colspan="3" style="margin:0px 0; padding:10px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight: bold;  color: #000" valign="top" align="left">Utlevert <br>'.$empinitial.'<br>'.$deliverytime.'</td>
				</tr>';
			
			}
			
		$filename = 'Faktura_'.$order_id.'.pdf';	

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
		//Save PDF in server.
		//file_put_contents(PATH_ORDER_PDF_FOLDER.$filename.".pdf", $dompdf->output()); 			
		
		/*-------------------------------------- GENERATE PDF END -------------------------------*/
		//force download
		force_download($filename, $pdf);
		exit;
		//if we want user to view in browser (comment out the force_download)
	     $this->dompdf->stream($filename, array("Attachment" => false));
		//$result=array('status'=>'success');
		//echo json_encode($result);
		exit;
			
				
	}
	
	
	
	 /*invoice status*/
     function __updateInvoiceStatus(){
 		
        //profiling
        $this->data['controller_profiling'][] = __function__;

		$inid = $this->input->post('inid');
		$instatus = $this->input->post('instatus');
		
		/*$inid = 3629;
		$instatus = 'sent';*/
		
		
		$result = $this->settings_faktura_model->updateInvoiceStatus($inid,$instatus);
		
		if($result){
			$order_id = $saldo_id = '';
			if($instatus=='sent'){
				$data = $this->settings_faktura_model->getInvoiceDetail($inid);
				//print_r($data);
				if($data['type']==''){
					$order_id = $this->settings_faktura_model->getInvoiceOrderID($data['regtime'],$data['customer']);
				}
			}
			
			$resposnse=array('inid'=>$inid,'error'=>'success','message'=>'Invoice status has been updated.','order'=>$order_id,'saldo'=>$saldo_id);
			echo json_encode($resposnse);exit;
			
		}
		else{
			$resposnse=array('inid'=>$inid,'error'=>'error','message'=>'Please check with administrator.');
			echo json_encode($resposnse);exit;
			
		}
		
	 }
	 
	 
	 /*order invoice status*/
     function __updateOrderInvoiceStatus(){
 		
        //profiling
        $this->data['controller_profiling'][] = __function__;

		$orderid = $this->input->post('orderid');
		$instatus = $this->input->post('instatus');
		
		
		$result = $this->settings_faktura_model->updateOrderInvoiceStatus($orderid,$instatus);
		
		if($result){
			
			$resposnse=array('inid'=>$inid,'error'=>'success','message'=>'Invoice status has been updated.','order'=>$orderid);
			echo json_encode($resposnse);exit;
			
		}
		else{
			$resposnse=array('inid'=>$inid,'error'=>'error','message'=>'Please check with administrator.');
			echo json_encode($resposnse);exit;
			
		}
		
		
	 }	 
	

    /**
     * list faktura list
     */
    function __viewFakturaList()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //uri segments
        $search_id = (is_numeric($this->uri->segment(5))) ? $this->uri->segment(5) : 0;
        $sort_by = ($this->uri->segment(6) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(7) == '') ? 'sortby_id' : $this->uri->segment(7);
        $offset = (is_numeric($this->uri->segment(8))) ? $this->uri->segment(8) : 0;
		$status = ($this->uri->segment(4)) ? $this->uri->segment(4) : 'pending';
		
		

        //load the original posted search into $_get array
        $this->input->load_query($search_id);
		
		$this->data['vars']['from'] = ($this->input->get('from')=='Fra') ? 'Fra': (($this->input->get('from')=='') ? 'Fra' : date('d.m.Y',strtotime($this->input->get('from'))));
		
		$this->data['vars']['to'] = ($this->input->get('to')=='Til') ? 'Til': (($this->input->get('to')=='') ? 'Til' : date('d.m.Y',strtotime($this->input->get('to'))));
		
		
		$branch =  ($this->input->get('branch')!='') ?  $this->input->get('branch') : '';
		
		
		
		$this->data['vars']['status'] =	$status;
		$this->data['vars']['branch'] =	$branch;

		//css - active tab
        $this->data['vars']['css_faktura_'.$status] = 'selected';
		
		$this->data['visible']['btn_all'] =  0;
		
        //load settings
        if ($next) {

			//get today in and ut in shop
			$this->data['reg_blocks'][] = 'faktura';
			$this->data['blocks']['faktura']=$this->settings_faktura_model->getFakturaList($status);
			$this->data['debug'][] = $this->settings_faktura_model->debug_data;
			$str ='';
			if(count($this->data['blocks']['faktura']) > 0){
			  for($i=0;$i<count($this->data['blocks']['faktura']);$i++){
				 $str.='<div class="orderlisting row">
				  <div class="col-md-1 black-text bold" style="padding-right:0">#'.$this->data['blocks']['faktura'][$i]['partner_branch'].' </div>
				  <div class="col-md-6 black-text bold text-left"> '.$this->data['blocks']['faktura'][$i]['name'].' </div>
				  <div class="col-md-1 black-text no-padd  bold"> '.$this->data['blocks']['faktura'][$i]['number'].'  </div>
				  <div class="col-md-2 black-text no-padd bold text-right"> kr '.formatcurrency($this->data['blocks']['faktura'][$i]['amount']).'  </div>
				  ';
				  			  
				if($this->data['blocks']['faktura'][$i]['in_status'] =='pending'){
				$str.='  <div class="col-md-2 black-text  text-right bold"><a class="btn red pull-right" id="'.$this->data['blocks']['faktura'][$i]['id'].'" onclick="updateInvoiceID(\''.$this->data['blocks']['faktura'][$i]['id'].'\');" href="#sendInvoiceModal" data-toggle="modal" data-title="Send" style="padding:6px 12px; color:#fff" >Send</a>';
				}else{
					
					$str.= '<div class="col-md-2 black-text  text-center bold" id="'.$this->data['blocks']['faktura'][$i]['id'].'" >'.ucfirst($this->data['blocks']['faktura'][$i]['in_status']);
				}
				$str.=' </div> </div>';
				$this->data['visible']['btn_all'] =  1;
			  }
			}
			
			$this->data['reg_blocks'][] = 'faktura2';
			$this->data['blocks']['faktura2'] = $this->settings_faktura_model->getOrderlineFakturaList($status);
			$this->data['debug'][] = $this->settings_faktura_model->debug_data;
			
			///print_r($this->data['blocks']['faktura2']);
			
			//echo count($this->data['blocks']['faktura2']);
			
			if(count($this->data['blocks']['faktura2']) > 0){
				
			  $temp = 0;	
			  for($i=0;$i<count($this->data['blocks']['faktura2']);$i++){
				 
				 $total_count = $this->data['blocks']['faktura2'][$i]['total_count'];
				 $pending_count = $this->data['blocks']['faktura2'][$i]['pending_count'];
				 $orderid =  $this->data['blocks']['faktura2'][$i]['id'];
				 
				 $bool = false; 
				 
				 if($temp != $orderid){
				  
					 if($total_count == $pending_count){
						 
						 if(!$bool){
							 
							 $amount  = ($this->data['blocks']['faktura2'][$i]['changed_amount'] === NULL ) ?  $this->data['blocks']['faktura2'][$i]['total_amount'] : $this->data['blocks']['faktura2'][$i]['changed_amount'] ;
						  
							 $str.='<div class="orderlisting row">
							  <div class="col-md-1 black-text bold" style="padding-right:0">#'.$this->data['blocks']['faktura2'][$i]['partner_branch'].' </div>
							  <div class="col-md-6 black-text bold text-left"> '.$this->data['blocks']['faktura2'][$i]['name'].' </div>
							  <div class="col-md-1 black-text no-padd  bold"> '.$this->data['blocks']['faktura2'][$i]['number'].'  </div>
							  <div class="col-md-2 black-text no-padd bold text-right"> kr '.formatcurrency($amount).'  </div>';
									  
							if($this->data['blocks']['faktura2'][$i]['ol_payment_status'] == 'pending'){
								
							$str.='  <div class="col-md-2 black-text  text-right bold"><a class="btn red pull-right" id="'.$this->data['blocks']['faktura2'][$i]['id'].'" onclick="updateInvoiceStatus(\''.$this->data['blocks']['faktura2'][$i]['id'].'\');" href="#sendOrderInvoiceModal" data-toggle="modal" data-title="Send" style="padding:6px 12px; color:#fff" >Send</a>';
							}else{
								
								$status = ($this->data['blocks']['faktura2'][$i]['ol_payment_status'] == 'waiting') ? 'Sent' :  $this->data['blocks']['faktura2'][$i]['ol_payment_status'];
								
								$str.= '<div class="col-md-2 black-text   text-center bold" id="'.$this->data['blocks']['faktura2'][$i]['id'].'">'.ucfirst($status);
							}
							$str.=' </div> </div>';
							
							$bool = true;
						
						}//if
						
					 }//if
					else{
							
						 $amount  = ($this->data['blocks']['faktura2'][$i]['camount'] === NULL ) ?  $this->data['blocks']['faktura2'][$i]['tamount'] : $this->data['blocks']['faktura2'][$i]['camount'] ;
					  
						 $str.='<div class="orderlisting row">
						  <div class="col-md-1 black-text bold" style="padding-right:0">#'.$this->data['blocks']['faktura2'][$i]['partner_branch'].' </div>
						  <div class="col-md-6 black-text bold text-left"> '.$this->data['blocks']['faktura2'][$i]['name'].'  </div>
						  <div class="col-md-1 black-text no-padd  bold"> '.$this->data['blocks']['faktura2'][$i]['number'].'  </div>
						  <div class="col-md-2 black-text no-padd bold text-right"> kr '.formatcurrency($this->data['blocks']['faktura2'][$i]['total_pending']).'  </div>';
								  
						if($this->data['blocks']['faktura2'][$i]['ol_payment_status'] == 'pending'){
							
						$str.='  <div class="col-md-2 black-text  text-right bold"><a class="btn red pull-right" id="'.$this->data['blocks']['faktura2'][$i]['id'].'" onclick="updateInvoiceStatus(\''.$this->data['blocks']['faktura2'][$i]['id'].'\');" href="#sendOrderInvoiceModal" data-toggle="modal" data-title="Send" style="padding:6px 12px; color:#fff" >Send</a>';
						}else{
							
							$status = ($this->data['blocks']['faktura2'][$i]['ol_payment_status'] == 'waiting') ? 'Sent' :  $this->data['blocks']['faktura2'][$i]['ol_payment_status'];
							
							$str.= '<div class="col-md-2 black-text text-center bold" id="'.$this->data['blocks']['faktura2'][$i]['id'].'" >'.ucfirst($status);
						}
						$str.=' </div> </div>';
						
						$bool = true;
					}
					
					$temp = $this->data['blocks']['faktura2'][$i]['id'];
				  
				 }
				
				  
				  $this->data['visible']['btn_all'] =  1;
			  }
			}
			
	
			$this->data['lists']['faktura_log'] =  $str;
		}

    }
	
	

    /**
     * list customers
     */
    function __saldoCustomers()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //uri segments
        $search_id = (is_numeric($this->uri->segment(5))) ? $this->uri->segment(5) : 0;
        $sort_by = ($this->uri->segment(6) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(7) == '') ? 'sortby_id' : $this->uri->segment(7);
        $offset = (is_numeric($this->uri->segment(8))) ? $this->uri->segment(8) : 0;
		

        //load the original posted search into $_get array
        $this->input->load_query($search_id);
		
		
		$branch =  ($this->input->get('branch')!='') ?  $this->input->get('branch') : '';
		$saldo =  ($this->input->get('saldo')!='') ?  $this->input->get('saldo') : '';
		
		$this->data['vars']['saldo'] =	$saldo;
		$this->data['vars']['branch'] =	$branch;

		//css - active tab
        $this->data['vars']['css_faktura_customers'] = 'selected';
		
		$this->data['visible']['btn_all'] =  0;
		
        //load settings
        if ($next) {

			//get today in and ut in shop
			$this->data['reg_blocks'][] = 'faktura';
			$this->data['blocks']['faktura']=$this->settings_faktura_model->saldoCustomers();
			$this->data['debug'][] = $this->settings_faktura_model->debug_data;
			$str ='';
			if(count($this->data['blocks']['faktura']) > 0){
			  for($i=0;$i<count($this->data['blocks']['faktura']);$i++){
				 $str.='<div class="orderlisting row">
				  <div class="col-md-1 black-text bold" style="padding-right:0">#'.$this->data['blocks']['faktura'][$i]['partner_branch'].' </div>
				  <div class="col-md-7 black-text bold text-left"> '.$this->data['blocks']['faktura'][$i]['name'].' </div>
				  <div class="col-md-1 black-text no-padd  bold"> '.$this->data['blocks']['faktura'][$i]['number'].'  </div>
				  <div class="col-md-3 black-text  bold text-right"> kr '.formatcurrency($this->data['blocks']['faktura'][$i]['paid']).'  </div>
				  ';
					
				  /*$str.= '<div class="col-md-2 black-text  text-center bold no-padd">'.ucfirst($this->data['blocks']['faktura'][$i]['lastuse']);</div>*/
				
				$str.='  </div>';
				  $this->data['visible']['btn_all'] =  1;
			  }
			}
			else{
				$str.='<div class="col-md-12 text-center mt-lg mb-lg"><h2>Ingen resultat.</h2></div>';
			}
	
			  $this->data['lists']['faktura_log'] =  $str;
		}

    }
	


    /**
     * takes all posted (search form) data and saves it to an array
     * array is then saved in database
     * the unique id of the database record is now used in redirect for all page results
     */
    function __cachedFakturaLog()
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
		
		$status = ($this->input->post('status')) ? $this->input->post('status') : 'pending';
		

        //change url to "list" and redirect with cached search id.
        redirect("admin/settings/faktura/$status/$search_id");

    }
	
	
    /**
     * takes all posted (search form) data and saves it to an array
     * array is then saved in database
     * the unique id of the database record is now used in redirect for all page results
     */
    function __cachedSaldoCustomersLog()
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
        redirect("admin/settings/faktura/customers/$search_id");

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
	
	
    /**
     * Generates various pulldown (<option>...</option>) lists for ready use in HTML
     * Output is set to e.g. $this->data['lists']['milestones']
     *
     */
    function __pulldownLists()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;
		
        //[all_branch]
		$current_partner = $this->session->userdata['partner'];
        $data = $this->settings_faktura_model->getPartnerBranches($current_partner);
        $this->data['debug'][] = $this->settings_faktura_model->debug_data;
        $this->data['lists']['all_branch'] = create_pulldown_list($data, 'branch', 'name');
        $this->data['lists']['all_branch_id'] = create_pulldown_list($data, 'branch', 'id');
    }
	
	

}

/* End of file faktura.php */
/* Location: ./application/controllers/admin/faktura.php */
