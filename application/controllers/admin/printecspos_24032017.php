<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'third_party/WebClientPrint/WebClientPrint.php';
use Webprint\WebClientPrint;
use Webprint\Utils;
use Webprint\DefaultPrinter;
use Webprint\InstalledPrinter;
use Webprint\PrintFile;
use Webprint\ClientPrintJob;
use Webprint\UserSelectedPrinter;
use Webprint\ParallelPortPrinter;
use Webprint\SerialPortPrinter;
use Webprint\NetworkPrinter;
use Webprint\ClientPrintJobGroup;



class Printecspos extends MY_Controller
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


        //PERMISSIONS CHECK - ACCESS
        //do this check before __commonAll_ProjectBasics()
        /*if ($this->data['vars']['my_group'] != 1) {
                redirect('/admin/error/permission-denied');
        }*/
		
		

        //get the action from url
        $action = $this->uri->segment(3);

        //route the rrequest
        switch ($action) {
			case 'save-image':
				 $this->__saveImage();
				 break;	
			case 'save-saldoimage':
				 $this->__savesaldoImage();
				 break;	
			case 'billprint':
				 $this->__print('bill');
				 break;	
			case 'tagprint':
				 $this->__print('tag');
				 break;		
			case 'heatsealprint':
				 $this->__print('heatseal');
				 break;	
			 case 'saldoprint':
				 $this->__print('saldo');
				 break;

			case 'print':
				 $this->__printBill();
				 break;	
				 
			case 'print-heatseal':
				 $this->__printHeatSeal();
				 break;	
				 
			case 'print-saldo':
				 $this->__printSaldo();
				 break;	


			 default:
                $this->__view();
                break;
				
				
        }

        //load view
		$this->__flmView('admin/main');
    }
	
	
	/*print saldo bill*/
	function __printSaldo()
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
			
			$partnerinfo = $this->general_model->getPartnerDetails($customer['partner'],$customer['partner']);
				
			$this->data['fields']['branch']['company'] = $partnerinfo['name'];
			$this->data['fields']['branch']['street'] = $partnerinfo['street'] ;
			$this->data['fields']['branch']['zip'] =  $partnerinfo['zip']. ' '.$partnerinfo['city'];
			$this->data['fields']['branch']['phone'] = $partnerinfo['phone']; 
			$this->data['fields']['branch']['org_nr'] = $partnerinfo['org_nr']; 
				
				
			//Create ESC/POS commands for sample receipt
			$esc = '0x1B'; //ESC byte in hex notation
			$newLine = '0x0A'; //LF byte in hex notation
			
			
			$cmds = '';
			$cmds = $esc . "@"; //Initializes the printer (ESC @)
			$cmds .= $esc . '!' . '0x38'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
			$cmds .= $esc . 'a' . '0x01';
			$cmds .= $this->data['fields']['branch']['company']; //text to print
			$cmds .= $newLine ;
			$cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
			$cmds .= $this->data['fields']['branch']['street'];
			$cmds .= $newLine;
			$cmds .= $this->data['fields']['branch']['zip'];
			$cmds .= $newLine;
			$cmds .= 'Telefon: 0x2B47 '.$this->data['fields']['branch']['phone'];
			$cmds .= $newLine;
			$cmds .= 'Org.nr: '. $this->data['fields']['branch']['org_nr'];
			$cmds .= $esc . 'a' . '0x00';
			$cmds .= $newLine.$newLine;
			
			$cmds .= $esc . 'a' . '0x01';
			$cmds .= '0x1D'.'h'.'0x3C';       // GS h 162  barcode height  
			$cmds .= '0x1D0x6B' . '0x05';  //GS k m d
			$cmds .= $saldo_id. '0x00';
			$cmds .= $newLine;
			$cmds .= $esc . '!' . '0x38'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
			$cmds .= $saldo_id. '0x00';
			$cmds .= $newLine.$newLine;
			
			$cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
			$cmds .= $esc . 'a' . '0x00';
			$cmds .= $this->data['fields']['customer']['number'];
			$cmds .= $newLine;
			$cmds .= $esc . '!' . '0x38'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
			$cmds .= ucwords($this->data['fields']['customer']['customer_name']);
			$cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
			$cmds .= $this->data['fields']['customer']['address'];
			$cmds .= $newLine;
			//$cmds .= $this->data['fields']['customer']['zip'].' '.$this->data['fields']['customer']['city'] ;
			//$cmds .= $newLine;
			$cmds .= 'Kasserer: '.$this->data['fields']['employee']['initial'] ;
			$cmds .= $newLine;
			$cmds .= $this->data['fields']['order']['order_time'];
			$cmds .= $newLine.$newLine;
					
				
			$in_type  = $this->general_model->getInType($customer['in_type']);
			
			$footerarray=array('B'=>'Bankkort (B)','K'=>'Kontant(K)','G'=>'Gift Card (G)','F'=>'Faktura (F)','kk'=>'kasse kredit (kk)');
				
			$html ='<tr>
			<td nowrap="nowrap" style="text-align: left;width:18%; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:bold;  vertical-align: top;">1</td>
			<td style="text-align: left; padding: 5px 0 0;width:55%; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">'.$footerarray[$in_type].'</td>
			<td nowrap="nowrap" style="text-align: right;width:25%; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top;">kr '.formatcurrency($customer['amount']).'</td>';
				
				$cmds .= str_pad('1', 4) . str_pad($footerarray[$in_type], 25);
				$cmds .= $esc . 'a' . '0x02';
				$cmds .= 'kr '.formatcurrency($customer['amount']);
				$cmds .= $newLine;
				$cmds .= $esc . 'a' . '0x00';
				
				
				$this->data['fields']['customer']['list']=$html;
				
				$summery='<tr>
				 <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Delsum'.$min_price_txt.'</td><td nowrap="nowrap" style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal; border-top:#000 dashed 1px;">kr '.formatcurrency($customer['amount']).'</td>
				  </tr>';
				  
				  
				$cmds .= $newLine.$newLine;
				$cmds .= $esc . 'a' . '0x02';
				$cmds .= str_pad('Delsum'.$min_price_txt, 0, "", STR_PAD_LEFT).'       ';
				$cmds .= 'kr '.formatcurrency($customer['amount']);
				$cmds .= $newLine;	
				  
				  
				  
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
			  
				$cmds .= str_pad('Herav 25% MVA', 0, "", STR_PAD_LEFT).'       ';
				$cmds .= 'kr '.formatcurrency($mva);
				$cmds .= $newLine;	
			  
				$cmds .= str_pad('Totalt', 0, "", STR_PAD_LEFT).'       ';
				$cmds .= 'kr '.formatcurrency($customer['amount']);
				$cmds .= $newLine;
			  
			  
			  
			    if($saldo_amount != '' && $saldo_amount != '0,00')
			  {
				  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Kassakredit saldo: kr '.$saldo_amount.'</td></tr>';
				  
				$cmds .= $newLine;					  
				$cmds .= 'Kassakredit saldo: kr '.formatcurrency($saldo_amount);
				$cmds .= $newLine;	
				  
			  }
		
		
		$this->data['fields']['customer']['summery']=$summery;
		
		//echo '<pre>';print_r($this->data['fields']['branch']);exit;
		
		//kndens kvittering  copi
		$kundens = $cmds .$newLine.$newLine;
		$kundens .= $esc . '!' . '0x24'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
		$kundens .='-Kundens kvittering-';
		$kundens .= $newLine;	
		$kundens .= $esc . 'a' . '0x00';
		
		
		//page break
		$pagebreak = $newLine;
		$pagebreak .= $newLine;
		$pagebreak .= $newLine;
		$pagebreak .= $newLine;
		$pagebreak .= $newLine;
		$pagebreak .= '0x1D0x560x00';
		$pagebreak .= $newLine;
		
		//intern copi
		$intern = $cmds .$newLine.$newLine;
		$intern .= $esc . '!' . '0x24'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
		$intern .='-Intern kopi-';
		$intern .= $newLine;	
		$intern .= $esc . 'a' . '0x00';
			
		$printer_commands = $kundens . $pagebreak.$intern;
		
		if($printerName == '')
		{
			$printerName='EPSON TM-T88V Receipt';
		}
		
		
		//profiling
        $this->data['controller_profiling'][] = __function__;
		echo '<input type="hidden" value="true" name="pid" id="pid"  value="0"/> ';
		echo '<input type="hidden" name="printerCommands" id="printerCommands" value="'.$printer_commands.'" /> ';
		
		echo '<a style="visibility:hidden;"  id="printbtn" class="btn btn-success btn-large" onclick="javascript:jsWebClientPrint.print(\'pid=\' + $(\'#pid\').attr(\'checked\') + \'&printerName='.$printerName.'&printerCommands=\' + $(\'#printerCommands\').val());">Print File...</a>';

		 echo '<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>';
	
	echo '<script>
		$(document).ready(function(){
			$( "#printbtn").trigger( "click" );
		});
	</script>';


		//Get Absolute URL of this page
		$currentAbsoluteURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
		$currentAbsoluteURL .= $_SERVER["SERVER_NAME"];
		if($_SERVER["SERVER_PORT"] != "80" && $_SERVER["SERVER_PORT"] != "443")
		{
			$currentAbsoluteURL .= ":".$_SERVER["SERVER_PORT"];
		} 
		$currentAbsoluteURL .= $_SERVER["REQUEST_URI"];
		
		//echo $currentAbsoluteURL."<br>";
		
		//WebClientPrinController.php is at the same page level as WebClientPrint.php
		$webClientPrintControllerAbsoluteURL = $this->data['vars']['site_url'].APPPATH . 'third_party/WebClientPrint/WebClientPrintController.php';
		
		//echo $webClientPrintControllerAbsoluteURL."<br>";
		$orderid = ltrim($orderid, '0');		
		
		
		//DemoPrintFileProcess.php is at the same page level as WebClientPrint.php
		$demoPrintFileProcessAbsoluteURL =  $this->data['vars']['site_url'].APPPATH . 'third_party/WebClientPrint/WebClientPrintCommandsProcess.php?order='.$orderid.'&printtype='.$printtype.'&tag_printer='.$this->session->userdata['tag_printer'].'&bill_printer='.$this->session->userdata['bil_printer'];
		

		//echo $demoPrintFileProcessAbsoluteURL."<br>";
		
		$session_id = $this->session->userdata('session_id');
		
		//echo $session_id;
		
		//Specify the ABSOLUTE URL to the WebClientPrintController.php and to the file that will create the ClientPrintJob object
		echo WebClientPrint::createScript($webClientPrintControllerAbsoluteURL, $demoPrintFileProcessAbsoluteURL, $session_id);
		
		 
		 exit;
		
	}
	
	
	/*prin heatseal*/
    function __printHeatSeal()
    {
		
		$order_id = $this->uri->segment(4);
		$orderinfo = $this->orders_model->getOrderinfo($order_id);
		
		//print_r($this->session);
		
		//get customer details
		$this->data['reg_fields'][] = 'customer';
		$this->data['fields']['customer'] = $customer = $this->orders_model->getCustomerDetails($order_id);
		
		//print_r($this->data['fields']['customer']);
		
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
		}
		
        //Create ESC/POS commands for sample receipt
        $esc = '0x1B'; //ESC byte in hex notation
        $newLine = '0x0A'; //LF byte in hex notation
		
		
        $cmds = '';
        $cmds = $esc . "@"; //Initializes the printer (ESC @)
        $cmds .= $esc . '!' . '0x38'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
		$cmds .= $esc . 'a' . '0x01';
        $cmds .= $this->data['fields']['branch']['company']; //text to print
        $cmds .= $newLine ;
        $cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
        $cmds .= $this->data['fields']['branch']['street'];
        $cmds .= $newLine;
        $cmds .= $this->data['fields']['branch']['zip'];
        $cmds .= $newLine;
        $cmds .= 'Telefon: 0x2B47 '.$this->data['fields']['branch']['phone'];
        $cmds .= $newLine;
        $cmds .= 'Org.nr: '. $this->data['fields']['branch']['org_nr'];
		$cmds .= $esc . 'a' . '0x00';
        $cmds .= $newLine.$newLine;
		
		$cmds .= $esc . 'a' . '0x01';
		$cmds .= '0x1D'.'h'.'0x3C';       // GS h 162  barcode height  
		$cmds .= '0x1D0x6B' . '0x05';  //GS k m d
		$cmds .= $order_id. '0x00';
        $cmds .= $newLine;
        $cmds .= $esc . '!' . '0x38'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
		$cmds .= $order_id. '0x00';
		$cmds .= $newLine.$newLine;
		
        $cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
		$cmds .= $esc . 'a' . '0x00';
        $cmds .= $this->data['fields']['customer']['number'];
        $cmds .= $newLine;
        $cmds .= $esc . '!' . '0x38'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
        $cmds .= ucwords($this->data['fields']['customer']['customer_name']);
        $cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
        $cmds .= $this->data['fields']['customer']['address'];
        $cmds .= $newLine;
        //$cmds .= $this->data['fields']['customer']['zip'].' '.$this->data['fields']['customer']['city'] ;
        //$cmds .= $newLine;
        $cmds .= 'Kasserer: '.$this->data['fields']['employee']['initial'] ;
        $cmds .= $newLine;
        $cmds .= $this->data['fields']['order']['order_time'];
        $cmds .= $newLine.$newLine;
		
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
				$cathtml = '';
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
					$orderlinedeliverydate[]= $orderdetails[$i]['name'].' '.strtolower($weekdayarray[$day]).' '.$p_b_delivery_time;
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
			  
			 //echo '<pre>';print_r($barcodes);exit;

			  $str.='<tr>
				<td nowrap="nowrap" style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:bold;  vertical-align: top;">'.$orderdetails[$i]['name'].' ('.$quantity.')</td>
				<td style="text-align: center; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; "> </td>
				<td nowrap="nowrap" style="text-align: right; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top;font-weight:bold;">kr '.formatcurrency($amount).$vary.'</td>
			  </tr>
			  <tr><td colspan="3" style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">';
			  
			  
			  $prod = $orderdetails[$i]['name'].' ('.$quantity.')';
			  
			$cmds .= str_pad($prod, 30);
			$cmds .= $esc . 'a' . '0x02';
			$cmds .= 'kr '.formatcurrency($amount).$vary;
			$cmds .= $newLine;
			$cmds .= $esc . 'a' . '0x00';
			  
			  $boolean = true ;

			  if(($orderdetails[$i]['in_meter'] == 1) && ($boolean))
			  {
				$meter_text =1;
				$boolean = false;
			  }
				  
			if($orderdetails[$i]['special_instruction']!=''){
				$str .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;line-height:32px"><b>Fritekst :</b><br>'.$orderdetails[$i]['special_instruction'].'</p>';
				
				$cmds .= 'Fritekst : ';
				$cmds .= $newLine;	
				$cmds .= $orderdetails[$i]['special_instruction'];
				$cmds .= $newLine;
				
			}
			
			if($orderdetails[$i]['complain']==1){
				$str .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;line-height:32px"><b>Reklamasjon</b></p>';
				$cmds .= 'Reklamasjon';
				$cmds .= $newLine;	
			
			}
			
			if($orderdetails[$i]['in_house']==1){
				$str .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;line-height:32px"><b>Renses på huset</b></p>';
				$cmds .= 'Renses på huset';
				$cmds .= $newLine;	
				
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
                <td style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">'.$name.' <br>HS 0x23'.$baritems['barcode'].'</td>';
				
				$cmds .= $name;
				$cmds .= $newLine;
				$cmds .= 'HS 0x23'.$baritems['barcode'] ;
				
				if($baritems['status'] == 'canceled')
				{
					$str.='<td style="text-align: center; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">0</td>';
					
					$cmds .= $esc . 'a' . '0x02';
					$cmds .= '0';
					$cmds .= $newLine;
					$cmds .= $esc . 'a' . '0x00';
					
				}
				else
				{
					$str.='<td style="text-align: center; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">1 </td>';
					
					$cmds .= $esc . 'a' . '0x02';
					$cmds .= '1';
					$cmds .= $newLine;
					$cmds .= $esc . 'a' . '0x00';
					
				}
					$cmds .= $newLine;
							   
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
					<td style="text-align: left; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">'.$orderdetails[$i]['name'].' <br>HS 0x23 
								   <td style="text-align: center; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">1 </td>
					<td nowrap="nowrap" style="text-align: right; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top;"></td>			
				  </tr>';
				  
						$cmds .= $orderdetails[$i]['name'] ;
						$cmds .= $newLine;
						$cmds .= 'HS 0x23';
						$cmds .= $esc . 'a' . '0x02';
						$cmds .= '1';
						$cmds .= $newLine;
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
				  
				$cmds .= $newLine;
				$cmds .= $esc . 'a' . '0x02';
				$cmds .= str_pad('Delsum'.$min_price_txt, 0, "", STR_PAD_LEFT).'       ';
				$cmds .= 'kr '.formatcurrency($subtotal);
				$cmds .= $newLine;	
				
		
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
			  
				$cmds .= str_pad('Discount('.$vouchercode.')', 0, "", STR_PAD_LEFT).'       ';
				$cmds .= 'kr '.formatcurrency($discount);
				$cmds .= $newLine;	
			  
			
		}
		
		  
				
			$fprice=$cus_sub_total-$discount;
		
			if($orderinfo['type'] != 'shop')
			{
				if($fprice < $min_price)
				{
					
					$summery.='<tr>
					 <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Minste beløp</td><td  nowrap="nowrap" style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal; border-top:#000 dashed 0px;">kr '.formatcurrency($min_price).'</td>
					  </tr>';
					  
					$cmds .= str_pad('Minste beløp', 0, "", STR_PAD_LEFT).'       ';
					$cmds .= 'kr '.formatcurrency($min_price);
					$cmds .= $newLine;	
					  
					  
				}
			
			}
		
		
		
		$price=$subtotal-$discount;
		$frakt=$delviery;
		
			if($orderinfo['type'] != 'shop')
			{
				$summery.='<tr>
				 <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Levering</td><td   nowrap="nowrap" style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal; border-top:#000 dashed 0px;">kr '.formatcurrency($delviery).'</td>
				</tr>';
				
				$cmds .= str_pad('Levering', 0, "", STR_PAD_LEFT).'       ';
				$cmds .= 'kr '.formatcurrency($delviery);
				$cmds .= $newLine;	
				
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
			  
				$cmds .= str_pad('Herav 25% MVA', 0, "", STR_PAD_LEFT).'       ';
				$cmds .= 'kr '.formatcurrency($mva);
				$cmds .= $newLine;	
			  
				$cmds .= str_pad('Totalt', 0, "", STR_PAD_LEFT).'       ';
				$cmds .= 'kr '.formatcurrency($this->data['fields']['order']['totalt']);
				$cmds .= $newLine;
					
				$cmds .= $esc . 'a' . '0x00';
			  
			  if($saldo_amount != '' && $saldo_amount != '0,00')
			  {
				  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Kassakredit saldo: kr '.$saldo_amount.'</td></tr>';
				  
				$cmds .= $newLine;					  
				$cmds .= 'Kassakredit saldo: kr '.formatcurrency($saldo_amount);
				$cmds .= $newLine;	

			  }
			   
			   if($meter_text == 1){
			   $summery.=' <tr>
              <td colspan="3" valign="top" align="left" style="text-align: left; padding: 10px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;"><i>* Prisen blir kalkulert når ferdig</i></td>
              </tr>';
			  
				$cmds .= '* Prisen blir kalkulert når ferdig';
				$cmds .= $newLine;	
			  
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
				$cmds .= $newLine;	
				$cmds .= 'Notater';
				$cmds .= $newLine;	
				$cmds .= $this->data['fields']['order']['delivery_note'];
				$cmds .= $newLine;	
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
				$cmds .= $newLine;	
				$cmds .= 'Spesialinstruksjoner';
				$cmds .= $newLine;	
				$cmds .= $this->data['fields']['order']['special_instruction'];
				$cmds .= $newLine;	
				
			}
			
			
			
			$this->data['lists']['orderlinedelivery']='';
			if(count($checkdeliverydate) == 1)
			{
				$delivery_dates=implode(',',$checkdeliverydate);
				
				$this->data['lists']['orderlinedelivery'] = '<tr>
				  <td colspan="3" style="margin:0px 0; padding:10px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight: bold;  color: #000" valign="top" align="left">Ferdig: '.$delivery_dates.'</td>
				</tr>';
				$cmds .= $esc . '!' . '0x20'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
				$cmds .= 'Ferdig: ';
				$cmds .= $newLine;	
				$cmds .= $delivery_dates;
				$cmds .= $newLine;	
				
				
			}
			else
			{
				if(count($orderlinedelivery) > 0)
				{
	   
				    $orderlinedelivery_time = implode($newLine,$orderlinedeliverydate);
	   
					
					$cmds .= $esc . '!' . '0x20'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
					$cmds .= 'Ferdig: ';
					$cmds .= $newLine;	
					$cmds .= $orderlinedelivery_time;
					$cmds .= $newLine;	
					
					
				}
			}
			
			$cmds .= $esc . 'a' . '0x01';
			
			$cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
			$cmds .= 'Etter kl. 13:00';	
			$cmds .= $newLine;	
			$cmds .= $newLine;	
			$cmds .= $esc . '!' . '0x24'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
			$cmds .= '-Kundens kvittering-';
			$cmds .= $newLine;	
			$cmds .= $esc . 'a' . '0x00';
			
		
		
		
		if($printerName == '')
		{
			$printerName='EPSON TM-T88V Receipt';
		}
		
		
		//profiling
        $this->data['controller_profiling'][] = __function__;
		echo '<input type="hidden" value="true" name="pid" id="pid"  value="0"/> ';
		echo '<input type="hidden" name="printerCommands" id="printerCommands" value="'.$cmds.'" /> ';
		
		echo '<a style="visibility:hidden;"  id="printbtn" class="btn btn-success btn-large" onclick="javascript:jsWebClientPrint.print(\'pid=\' + $(\'#pid\').attr(\'checked\') + \'&printerName='.$printerName.'&printerCommands=\' + $(\'#printerCommands\').val());">Print File...</a>';

		 echo '<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>';
	
	echo '<script>
		$(document).ready(function(){
			$( "#printbtn").trigger( "click" );
		});
	</script>';


		//Get Absolute URL of this page
		$currentAbsoluteURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
		$currentAbsoluteURL .= $_SERVER["SERVER_NAME"];
		if($_SERVER["SERVER_PORT"] != "80" && $_SERVER["SERVER_PORT"] != "443")
		{
			$currentAbsoluteURL .= ":".$_SERVER["SERVER_PORT"];
		} 
		$currentAbsoluteURL .= $_SERVER["REQUEST_URI"];
		
		//echo $currentAbsoluteURL."<br>";
		
		//WebClientPrinController.php is at the same page level as WebClientPrint.php
		$webClientPrintControllerAbsoluteURL = $this->data['vars']['site_url'].APPPATH . 'third_party/WebClientPrint/WebClientPrintController.php';
		
		//echo $webClientPrintControllerAbsoluteURL."<br>";
		$orderid = ltrim($orderid, '0');		
		
		
		//DemoPrintFileProcess.php is at the same page level as WebClientPrint.php
		$demoPrintFileProcessAbsoluteURL =  $this->data['vars']['site_url'].APPPATH . 'third_party/WebClientPrint/WebClientPrintCommandsProcess.php?order='.$orderid.'&printtype='.$printtype.'&tag_printer='.$this->session->userdata['tag_printer'].'&bill_printer='.$this->session->userdata['bil_printer'];
		

		//echo $demoPrintFileProcessAbsoluteURL."<br>";
		
		$session_id = $this->session->userdata('session_id');
		
		//echo $session_id;
		
		//Specify the ABSOLUTE URL to the WebClientPrintController.php and to the file that will create the ClientPrintJob object
		echo WebClientPrint::createScript($webClientPrintControllerAbsoluteURL, $demoPrintFileProcessAbsoluteURL, $session_id);
		
		 
		 exit;
	}
	
	
	
    function __printBill()
    {
		
		$order_id = $this->uri->segment(4);
		$orderinfo = $this->orders_model->getOrderinfo($order_id);
		
		//print_r($this->session);
		
		//get customer details
		$this->data['reg_fields'][] = 'customer';
		$this->data['fields']['customer'] = $customer = $this->orders_model->getCustomerDetails($order_id);
		
		//print_r($this->data['fields']['customer']);
		
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
		}
		
        //Create ESC/POS commands for sample receipt
        $esc = '0x1B'; //ESC byte in hex notation
        $newLine = '0x0A'; //LF byte in hex notation
		
		
        $cmds = '';
        $cmds = $esc . "@"; //Initializes the printer (ESC @)
        $cmds .= $esc . '!' . '0x38'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
		$cmds .= $esc . 'a' . '0x01';
        $cmds .= $this->data['fields']['branch']['company']; //text to print
        $cmds .= $newLine ;
        $cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
        $cmds .= $this->data['fields']['branch']['street'];
        $cmds .= $newLine;
        $cmds .= $this->data['fields']['branch']['zip'];
        $cmds .= $newLine;
        $cmds .= 'Telefon: 0x2B47 '.$this->data['fields']['branch']['phone'];
        $cmds .= $newLine;
        $cmds .= 'Org.nr: '. $this->data['fields']['branch']['org_nr'];
		$cmds .= $esc . 'a' . '0x00';
        $cmds .= $newLine.$newLine;
		
		$cmds .= $esc . 'a' . '0x01';
		$cmds .= '0x1D'.'h'.'0x3C';       // GS h 162  barcode height  
		$cmds .= '0x1D0x6B' . '0x05';  //GS k m d
		$cmds .= $order_id. '0x00';
        $cmds .= $newLine;
        $cmds .= $esc . '!' . '0x38'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
		$cmds .= $order_id. '0x00';
		$cmds .= $newLine.$newLine;
		
        $cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
		$cmds .= $esc . 'a' . '0x00';
        $cmds .= $this->data['fields']['customer']['number'];
        $cmds .= $newLine;
        $cmds .= $esc . '!' . '0x38'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
        $cmds .= ucwords($this->data['fields']['customer']['customer_name']);
        $cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
        $cmds .= $this->data['fields']['customer']['address'];
        $cmds .= $newLine;
        //$cmds .= $this->data['fields']['customer']['zip'].' '.$this->data['fields']['customer']['city'] ;
        //$cmds .= $newLine;
        $cmds .= 'Kasserer: '.$this->data['fields']['employee']['initial'] ;
        $cmds .= $newLine;
        $cmds .= $this->data['fields']['order']['order_time'];
        $cmds .= $newLine.$newLine;
		
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
				$cathtml = '';
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
					$orderlinedeliverydate[]= $orderdetails[$i]['name'].' '.strtolower($weekdayarray[$day]).' '.$p_b_delivery_time;
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
								 
				$cmds .= str_pad($quantity, 5) . str_pad($orderdetails[$i]['name'], 27);
				$cathtml .= str_pad($quantity, 5) . str_pad($orderdetails[$i]['name'], 27);
				
				
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
				
				
				$html .='</td>
                <td nowrap="nowrap" style="text-align: right;width:25%; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top;">kr '.formatcurrency($productPrice).$vary.'</td>
              </tr>';
			  
				$cmds .= $esc . 'a' . '0x02';
				$cmds .= 'kr '.formatcurrency($productPrice).$vary;
				$cmds .= $newLine;
				$cmds .= $esc . 'a' . '0x00';
				
				
				$cathtml .= $esc . 'a' . '0x02';
				$cathtml .= 'kr '.formatcurrency($productPrice).$vary;
				$cathtml .= $newLine;
				$cathtml .= $esc . 'a' . '0x00';
				
				
				if($orderdetails[$i]['special_instruction']!=''){
					$html .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;font-size: 30px; line-height:32px"><b>Fritekst :</b><br>'.$orderdetails[$i]['special_instruction'].'</p>';
					
					$cmds .= 'Fritekst : ';
					$cmds .= $newLine;	
					$cmds .= $orderdetails[$i]['special_instruction'];
					$cmds .= $newLine;
					
					$cathtml .= 'Fritekst : ';
					$cathtml .= $newLine;	
					$cathtml .= $orderdetails[$i]['special_instruction'];
					$cathtml .= $newLine;
						
				}
				
				if($orderdetails[$i]['complain']==1){
					$html .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;font-size: 30px; line-height:32px"><b>Reklamasjon</b></p>';
					
					$cmds .= 'Reklamasjon';
					$cmds .= $newLine;	
					
					$cathtml .= 'Reklamasjon';
					$cathtml .= $newLine;	
					
				}
				
				if($orderdetails[$i]['in_house']==1){
					$html .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;font-size: 30px; line-height:32px"><b>Renses på huset</b></p>';
					$cmds .= 'Renses på huset';
					$cmds .= $newLine;	
					
					$cathtml .= 'Renses på huset';
					$cathtml .= $newLine;	
					
				}
					
			  
			  $str.=$html;
			  $categoryorderline[$orderdetails[$i]['category']][$i]['item']= $cathtml;
			  
			  
			  $ferdig = $orderlinedeliverydate[$i];
				
			 $categoryorderline[$orderdetails[$i]['category']][$i]['delivery']= $ferdig;
			  
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
				  
				$cmds .= $newLine;
				$cmds .= $esc . 'a' . '0x02';
				$cmds .= str_pad('Delsum'.$min_price_txt, 0, "", STR_PAD_LEFT).'       ';
				$cmds .= 'kr '.formatcurrency($subtotal);
				$cmds .= $newLine;	
				
		
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
			  
				$cmds .= str_pad('Discount('.$vouchercode.')', 0, "", STR_PAD_LEFT).'       ';
				$cmds .= 'kr '.formatcurrency($discount);
				$cmds .= $newLine;	
			  
			
		}
		
		  
				
			$fprice=$cus_sub_total-$discount;
		
			if($orderinfo['type'] != 'shop')
			{
				if($fprice < $min_price)
				{
					
					$summery.='<tr>
					 <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Minste beløp</td><td  nowrap="nowrap" style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal; border-top:#000 dashed 0px;">kr '.formatcurrency($min_price).'</td>
					  </tr>';
					  
					$cmds .= str_pad('Minste beløp', 0, "", STR_PAD_LEFT).'       ';
					$cmds .= 'kr '.formatcurrency($min_price);
					$cmds .= $newLine;	
					  
					  
				}
			
			}
		
		
		
		$price=$subtotal-$discount;
		$frakt=$delviery;
		
			if($orderinfo['type'] != 'shop')
			{
				$summery.='<tr>
				 <td colspan="2" style="text-align: right; padding:5px; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Levering</td><td   nowrap="nowrap" style="text-align: right; padding:5px 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal; border-top:#000 dashed 0px;">kr '.formatcurrency($delviery).'</td>
				</tr>';
				
				$cmds .= str_pad('Levering', 0, "", STR_PAD_LEFT).'       ';
				$cmds .= 'kr '.formatcurrency($delviery);
				$cmds .= $newLine;	
				
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
			  
				$cmds .= str_pad('Herav 25% MVA', 0, "", STR_PAD_LEFT).'       ';
				$cmds .= 'kr '.formatcurrency($mva);
				$cmds .= $newLine;	
			  
				$cmds .= str_pad('Totalt', 0, "", STR_PAD_LEFT).'       ';
				$cmds .= 'kr '.formatcurrency($this->data['fields']['order']['totalt']);
				$cmds .= $newLine;
					
				$cmds .= $esc . 'a' . '0x00';
			  
			  if($saldo_amount != '' && $saldo_amount != '0,00')
			  {
				  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Kassakredit saldo: kr '.$saldo_amount.'</td></tr>';
				  
				$cmds .= $newLine;					  
				$cmds .= 'Kassakredit saldo: kr '.formatcurrency($saldo_amount);
				$cmds .= $newLine;	

			  }
			   
			   if($meter_text == 1){
			   $summery.=' <tr>
              <td colspan="3" valign="top" align="left" style="text-align: left; padding: 10px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;"><i>* Prisen blir kalkulert når ferdig</i></td>
              </tr>';
			  
				$cmds .= '* Prisen blir kalkulert når ferdig';
				$cmds .= $newLine;	
			  
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
				$cmds .= $newLine;	
				$cmds .= 'Notater';
				$cmds .= $newLine;	
				$cmds .= $this->data['fields']['order']['delivery_note'];
				$cmds .= $newLine;	
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
				$cmds .= $newLine;	
				$cmds .= 'Spesialinstruksjoner';
				$cmds .= $newLine;	
				$cmds .= $this->data['fields']['order']['special_instruction'];
				$cmds .= $newLine;	
				
			}
			
			
			
			$this->data['lists']['orderlinedelivery']='';
			if(count($checkdeliverydate) == 1)
			{
				$delivery_dates=implode(',',$checkdeliverydate);
				
				$this->data['lists']['orderlinedelivery'] = '<tr>
				  <td colspan="3" style="margin:0px 0; padding:10px 0; font-family: \'arial\', monospace; font-size: 40px; font-weight: bold;  color: #000" valign="top" align="left">Ferdig: '.$delivery_dates.'</td>
				</tr>';
				$cmds .= $esc . '!' . '0x20'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
				$cmds .= 'Ferdig: ';
				$cmds .= $newLine;	
				$cmds .= $delivery_dates;
				$cmds .= $newLine;	
				
				
			}
			else
			{
				if(count($orderlinedelivery) > 0)
				{
	   
				    $orderlinedelivery_time = implode($newLine,$orderlinedeliverydate);
	   
					
					$cmds .= $esc . '!' . '0x20'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
					$cmds .= 'Ferdig: ';
					$cmds .= $newLine;	
					$cmds .= $orderlinedelivery_time;
					$cmds .= $newLine;	
					
					
				}
			}
			
			$cmds .= $esc . 'a' . '0x01';
			
			$cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
			$cmds .= 'Etter kl. 13:00';	
			$cmds .= $newLine;	
			$cmds .= $newLine;	
			$cmds .= $esc . '!' . '0x24'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
			$cmds .= '-Kundens kvittering-';
			$cmds .= $newLine;	
			$cmds .= $esc . 'a' . '0x00';
			
		
			$commonheader = '';
			$commonheader .= $esc . '!' . '0x38'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
			$commonheader .= $esc . 'a' . '0x01';
			$commonheader .= $this->data['fields']['branch']['company']; //text to print
			$commonheader .= $newLine ;
			$commonheader .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
			$commonheader .= $this->data['fields']['branch']['street'];
			$commonheader .= $newLine;
			$commonheader .= $this->data['fields']['branch']['zip'];
			$commonheader .= $newLine;
			$commonheader .= 'Telefon: 0x2B47 '.$this->data['fields']['branch']['phone'];
			$commonheader .= $newLine;
			$commonheader .= 'Org.nr: '. $this->data['fields']['branch']['org_nr'];
			$commonheader .= $esc . 'a' . '0x00';
			$commonheader .= $newLine.$newLine;
			
			$commonheader .= $esc . 'a' . '0x01';
			$commonheader .= '0x1D'.'h'.'0x3C';       // GS h 162  barcode height  
			$commonheader .= '0x1D0x6B' . '0x05';  //GS k m d
			$commonheader .= $order_id. '0x00';
			$commonheader .= $newLine;
			$commonheader .= $esc . '!' . '0x38'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
			$commonheader .= $order_id. '0x00';
			$commonheader .= $newLine.$newLine;
			
			$commonheader .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
			$commonheader .= $esc . 'a' . '0x00';
			$commonheader .= $this->data['fields']['customer']['number'];
			$commonheader .= $newLine;
			$commonheader .= $esc . '!' . '0x38'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
			$commonheader .= ucwords($this->data['fields']['customer']['customer_name']);
			$commonheader .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
			$commonheader .= $this->data['fields']['customer']['address'];
			$commonheader .= $newLine;
			//$commonheader .= $this->data['fields']['customer']['zip'].' '.$this->data['fields']['customer']['city'] ;
			//$commonheader .= $newLine;
			$commonheader .= 'Kasserer: '.$this->data['fields']['employee']['initial'] ;
			$commonheader .= $newLine;
			$commonheader .= $this->data['fields']['order']['order_time'];
			$commonheader .= $newLine.$newLine;
			
			
			$commonfooter = $esc . '!' . '0x24'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
			$commonfooter .='-Intern kopi-';
			$commonfooter .= $newLine;	
			$commonfooter .= $esc . 'a' . '0x00';
			
			$pagebreak = $newLine;
			$pagebreak .= $newLine;
			$pagebreak .= $newLine;
			$pagebreak .= $newLine;
			$pagebreak .= $newLine;
			$pagebreak .= '0x1D0x560x00';
			$pagebreak .= $newLine;
			
			
			
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
					
						$categoryprint.=$pagebreak;
						$categoryprint.= $commonheader;
						$catout='';
						$catdelivery = '';
						foreach($catitems as $key=>$cat_html)
						{
							$catout.= $catitems[$key]['item'];
							$catdelivery .= $catitems[$key]['delivery'];
							$antall += $catitems[$key]['antall'];
							
							//echo $catout."<br>";
						}
					
						$categoryprint.=$catout;
						$categoryprint .= $newLine;	

						$categoryprint .= $esc . '!' . '0x20'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
						$categoryprint .= 'Ferdig: ';
						$categoryprint .= $newLine;	
						$categoryprint .= $catdelivery;
						$categoryprint .= $newLine;	
						$commonheader .= $esc . 'a' . '0x01';
						$categoryprint .=' Antall '.$antall;
						$categoryprint .= $newLine;
						
			
						$categoryprint.= $commonfooter;
						
						
					}
				
				}
				
			}
			
		}
		
		
		$cmds .= $newLine;	
		$cmds .= $categoryprint;
		
		
		if($printerName == '')
		{
			$printerName='EPSON TM-T88V Receipt';
		}
		
		
		//profiling
        $this->data['controller_profiling'][] = __function__;
		echo '<input type="hidden" value="true" name="pid" id="pid"  value="0"/> ';
		echo '<input type="hidden" name="printerCommands" id="printerCommands" value="'.$cmds.'" /> ';
		
		echo '<a style="visibility:hidden;"  id="printbtn" class="btn btn-success btn-large" onclick="javascript:jsWebClientPrint.print(\'pid=\' + $(\'#pid\').attr(\'checked\') + \'&printerName='.$printerName.'&printerCommands=\' + $(\'#printerCommands\').val());">Print File...</a>';

		 echo '<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>';
	
	echo '<script>
		$(document).ready(function(){
			$( "#printbtn").trigger( "click" );
		});
	</script>';


		//Get Absolute URL of this page
		$currentAbsoluteURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
		$currentAbsoluteURL .= $_SERVER["SERVER_NAME"];
		if($_SERVER["SERVER_PORT"] != "80" && $_SERVER["SERVER_PORT"] != "443")
		{
			$currentAbsoluteURL .= ":".$_SERVER["SERVER_PORT"];
		} 
		$currentAbsoluteURL .= $_SERVER["REQUEST_URI"];
		
		//echo $currentAbsoluteURL."<br>";
		
		//WebClientPrinController.php is at the same page level as WebClientPrint.php
		$webClientPrintControllerAbsoluteURL = $this->data['vars']['site_url'].APPPATH . 'third_party/WebClientPrint/WebClientPrintController.php';
		
		//echo $webClientPrintControllerAbsoluteURL."<br>";
		$orderid = ltrim($orderid, '0');		
		
		
		//DemoPrintFileProcess.php is at the same page level as WebClientPrint.php
		$demoPrintFileProcessAbsoluteURL =  $this->data['vars']['site_url'].APPPATH . 'third_party/WebClientPrint/WebClientPrintCommandsProcess.php?order='.$orderid.'&printtype='.$printtype.'&tag_printer='.$this->session->userdata['tag_printer'].'&bill_printer='.$this->session->userdata['bil_printer'];
		

		//echo $demoPrintFileProcessAbsoluteURL."<br>";
		
		$session_id = $this->session->userdata('session_id');
		
		//echo $session_id;
		
		//Specify the ABSOLUTE URL to the WebClientPrintController.php and to the file that will create the ClientPrintJob object
		echo WebClientPrint::createScript($webClientPrintControllerAbsoluteURL, $demoPrintFileProcessAbsoluteURL, $session_id);
		
		 
		 exit;
	}
	
	
	/*save image from html*/
    function __saveImage()
    {
		
		 //profiling
		 if(count($_POST) > 0)
		 {
			$orderid=$_POST['order'];
		//	$name=sprintf("%08d", $_POST['id']);
			$name=$_POST['id'];
			$subfolder=substr($name,0,3);
			$this->data['controller_profiling'][] = __function__;
			//just a random name for the image file
			$random = rand(100, 1000);
			$imagedata=str_replace('[removed]','data:image/png;base64,',$_POST['data']);
			
			//$_POST[data][1] has the base64 encrypted binary codes. 
			//convert the binary to image using file_put_contents
			
			if($subfolder == 'tag')
			{
				if (!is_dir(PATHS_PRINT_IMAGES.$orderid.'/tag')) {
					mkdir(PATHS_PRINT_IMAGES.$orderid.'/tag', 0777, TRUE);
				}
				$savefile = @file_put_contents(PATHS_PRINT_IMAGES.$orderid.'/tag/'."$name.png", base64_decode(explode(",", $imagedata)[1]));
			}
			else if($subfolder == 'hea')
			{
				if (!is_dir(PATHS_PRINT_IMAGES.$orderid.'/heatseal')) {
					mkdir(PATHS_PRINT_IMAGES.$orderid.'/heatseal', 0777, TRUE);
				}
				$savefile = @file_put_contents(PATHS_PRINT_IMAGES.$orderid.'/heatseal/'."$name.png", base64_decode(explode(",", $imagedata)[1]));
			}
			else
			{
				if (!is_dir(PATHS_PRINT_IMAGES.$orderid.'/bill')) {
					mkdir(PATHS_PRINT_IMAGES.$orderid.'/bill', 0777, TRUE);
				}
				$savefile = @file_put_contents(PATHS_PRINT_IMAGES.$orderid.'/bill/'."$name.png", base64_decode(explode(",", $imagedata)[1]));
			}
			//if the file saved properly, print the file name
			if($savefile){
				echo $name;exit;
			}
		}
		
	}
	
	function __savesaldoImage()
	{
		 //profiling
		 if(count($_POST) > 0)
		 {
			$orderid=$_POST['order'];
			$name=$_POST['id'];
			$subfolder=substr($name,0,3);
			$this->data['controller_profiling'][] = __function__;
			//just a random name for the image file
			$random = rand(100, 1000);
			$imagedata=str_replace('[removed]','data:image/png;base64,',$_POST['data']);
			
			//$_POST[data][1] has the base64 encrypted binary codes. 
			//convert the binary to image using file_put_contents
			
			if (!is_dir(PATHS_PRINT_IMAGES.'saldo/'.$orderid)) {
					mkdir(PATHS_PRINT_IMAGES.'saldo/'.$orderid, 0777, TRUE);
			}
			$savefile = @file_put_contents(PATHS_PRINT_IMAGES.'saldo/'.$orderid.'/'."$name.png", base64_decode(explode(",", $imagedata)[1]));
			//if the file saved properly, print the file name
			if($savefile){
				echo $name;exit;
			}
		}
		
	}
		
    function __view()
    {


        //profiling
        $this->data['controller_profiling'][] = __function__;

		
		
	exit;
		
	}

    function __page()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;
		
		$urlParts = parse_url($_SERVER['REQUEST_URI']);
		
		print_r($urlParts); 
		
		if (isset($urlParts['query'])) {
			$rawQuery = $urlParts['query'];
			parse_str($rawQuery, $qs);
			
			print_r($qs); 
			
			if (isset($qs[WebClientPrint::CLIENT_PRINT_JOB])) {
		
				$useDefaultPrinter = ($qs['useDefaultPrinter'] === 'checked');
				$printerName = urldecode($qs['printerName']);
		
				$printerName = 'EPSON TM-T88IV Receipt';

				$fileName ='intern.png';
				$filePath = '';
				$filePath='http://secureserver.no/pos/images/print/10154674/intern.png';
				
				//Create array of PrintFile objects you want to print

				if (!Utils::isNullOrEmptyString($fileGroup)) {
					//Create a ClientPrintJob obj that will be processed at the client side by the WCPP
					
					$fileGroup = array(
					new PrintFile('http://secureserver.no/pos/images/print/10154674/intern.png', 'intern.png', NULL)
					
					);		
					
					$cpj = new ClientPrintJob();
					//$cpj->printFile = new PrintFile($filePath, $fileName, null);
					$cpj->printFileGroup = $fileGroup;
		 
					if ($useDefaultPrinter || $printerName === 'null') {
						$cpj->clientPrinter = new DefaultPrinter();
					} else {
						$cpj->clientPrinter = new InstalledPrinter($printerName);
					}

					//Send ClientPrintJob back to the client
					ob_start();
					ob_clean();
					header('Content-type: application/octet-stream');
					echo $cpj->sendToClient();
					ob_end_flush();
					exit();
				}
			}
  		 }
		 
		 exit;
	}
	

    function __print($printtype)
    {
		
		$orderid=$this->uri->segment(4);
		$orderinfo = $this->orders_model->getOrderinfo($orderid);
		/*
		if($printtype == 'tag')
		{
			$printerName='EPSON TM-U220 Receipt';
		}
		else
		{
			$printerName='EPSON TM-T88IV Receipt';
		}*/
		
		if($printtype == 'tag')
		{
			$printerName=$this->session->userdata['tag_printer'];
		}
		else
		{
			$printerName=$this->session->userdata['bil_printer'];
		}
		
		if($printerName == '')
		{
			$printerName='EPSON TM-T88IV Receipt';
		}
		
		
		//profiling
        $this->data['controller_profiling'][] = __function__;
		echo '<input type="hidden" value="true" name="useDefaultPrinter" id="useDefaultPrinter" /> ';
		echo '<input type="hidden" value="PNG" name="ddlFileType" id="ddlFileType" /> ';
		
		echo '<a style="visibility:hidden;"  id="printbtn" class="btn btn-success btn-large" onclick="javascript:jsWebClientPrint.print(\'useDefaultPrinter=\' + $(\'#useDefaultPrinter\').attr(\'checked\') + \'&printerName='.$printerName.'&filetype=\' + $(\'#ddlFileType\').val());">Print File...</a>';

		 echo '<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>';
	
	echo '<script>
		$(document).ready(function(){
			$( "#printbtn").trigger( "click" );
		});
	</script>';


		//Get Absolute URL of this page
		$currentAbsoluteURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
		$currentAbsoluteURL .= $_SERVER["SERVER_NAME"];
		if($_SERVER["SERVER_PORT"] != "80" && $_SERVER["SERVER_PORT"] != "443")
		{
			$currentAbsoluteURL .= ":".$_SERVER["SERVER_PORT"];
		} 
		$currentAbsoluteURL .= $_SERVER["REQUEST_URI"];
		
		//echo $currentAbsoluteURL."<br>";
		
		//WebClientPrinController.php is at the same page level as WebClientPrint.php
		$webClientPrintControllerAbsoluteURL = $this->data['vars']['site_url'].APPPATH . 'third_party/WebClientPrint/WebClientPrintController.php';
		
		//echo $webClientPrintControllerAbsoluteURL."<br>";
		$orderid = ltrim($orderid, '0');		
		
		
		//DemoPrintFileProcess.php is at the same page level as WebClientPrint.php
		$demoPrintFileProcessAbsoluteURL =  $this->data['vars']['site_url'].APPPATH . 'third_party/WebClientPrint/WebClientPrintFileProcess.php?order='.$orderid.'&printtype='.$printtype.'&tag_printer='.$this->session->userdata['tag_printer'].'&bill_printer='.$this->session->userdata['bil_printer'];
		

		//echo $demoPrintFileProcessAbsoluteURL."<br>";
		
		$session_id = $this->session->userdata('session_id');
		
		//echo $session_id;
		
		//Specify the ABSOLUTE URL to the WebClientPrintController.php and to the file that will create the ClientPrintJob object
		echo WebClientPrint::createScript($webClientPrintControllerAbsoluteURL, $demoPrintFileProcessAbsoluteURL, $session_id);
		
		 
		 exit;
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

/* End of file invoice.php */
/* Location: ./application/controllers/admin/invoice.php */
