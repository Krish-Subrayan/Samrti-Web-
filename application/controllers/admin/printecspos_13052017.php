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
				 $this->__printBill('bill');
				 break;	
				 
			case 'print-intern-bill':
				 $this->__printInternBill('bill');
				 break;	
				 
			case 'print-kunden-bill':
				 $this->__printKundenBill('bill');
				 break;	
			case 'print-heatseal':
				 $this->__printHeatSeal('heatseal');
				 break;	
			case 'print-saldo':
				 $this->__printSaldo('saldo');
				 break;	

			case 'print-tag':
				 $this->__printTag('tag');
				 break;	
				 
			case 'print-report':
				 $this->__printReport('bill');
				 break;	
				 
			 case 'testprint':
				 $this->__testprintBill('tag');
			  break;	
			 default:
                $this->__view();
                break;
				
				
        }

        //load view
		$this->__flmView('admin/main');
    }
	

	
	/*Print sales report*/
    function __printReport($printtype)
    {
		
        //profiling
        $this->data['controller_profiling'][] = __function__;
        //flow control
        $next = true;
        //load helper
        $this->load->helper('download');
		
		$date  = $this->uri->segment(4);
		//$date  = '31.03.2017';

		$this->data['vars']['from'] = ($date=='') ? date('d.m.Y'): date('d.m.Y',strtotime($date));
		$this->data['vars']['to'] = ($date=='') ? date('d.m.Y'): date('d.m.Y',strtotime($date));


		$search = array('Å','å','Æ','æ','Ø','ø','(',')');
		$replace = array('0x5D','0x7D','0x5B','0x7B','0x5C','0x7C','0x28','0x29');
		
		
        //Create ESC/POS commands for sample receipt
        $esc = '0x1B'; //ESC byte in hex notation
        $newLine = '0x0A'; //LF byte in hex notation
		
		
        $cmds = '';
        $cmds = $esc . "@"; //Initializes the printer (ESC @)
        $cmds .= $esc . '!' . '0x18'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
		$cmds .= $esc . 'a' . '0x01';
		$cmds .= $esc . '0x52' . '0x09'; //internation charater set ESC R 9
        $cmds .= str_replace($search, $replace,'Omsetning på kasserull (small)'); //text to print
        $cmds .= $newLine ;
        $cmds .= 'Kasseskuffer 1';
        $cmds .= $newLine;
        $cmds .= 'Dato   ' .$date  .' - '.  $date;
        $cmds .= $newLine;
        $cmds .= $newLine;
		$cmds .= $esc . 'a' . '0x00';
		$cmds .= 'Dato      ' .'Tid    ' .'Ordre    ' .'Type ' .str_replace($search, $replace, 'Beløp');
        $cmds .= $newLine;
		$cmds .= $esc . '!' . '0x00'; 
		
		
        //check if invoice exists
        if ($next) {
		
		  
				//get results for orders in process (orders which are placed , in process)
				$this->data['reg_blocks'][] = 'account';
				$this->data['reg_blocks'][] = 'account1';
				$this->data['reg_blocks'][] = 'account2';
				
				$this->data['blocks']['account1'] = $this->customer_model->getCustomerAccountLog('','','','',$date);
				$this->data['blocks']['account2'] = $this->customer_model->getCustomerpayment('','','','',$date);
				
			
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
						
						$in_type  = $this->general_model->getInType($this->data['blocks']['account'][$i]['in_type']);
						
						$bankarray[$in_type][]=$this->data['blocks']['account'][$i]['amount'];
						
						$str .='<tr>
								<td nowrap="nowrap" style="text-align: left;width:30%; padding: 5px 0 5px; font-family: \'arial\', monospace; font-size: 30px; vertical-align: top;">'.$this->data['blocks']['account'][$i]['rdatewy'].'</td>
								<td style="text-align: left; padding: 5px 0 0;width:15%; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">'.$this->data['blocks']['account'][$i]['rtime'].'</td>
								<td style="text-align: center; padding: 5px 0 0;width:25%; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">'. $orderid.'</td>
								<td style="text-align: center; padding: 5px 0 0;width:3%; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top;vertical-align: top; ">'.strtoupper($in_type).'</td>
								<td nowrap="nowrap" style="text-align: right; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top;">kr '.formatcurrency($this->data['blocks']['account'][$i]['amount']).'</td>
							  </tr>';
						
						
							  
						$cmds .= $this->data['blocks']['account'][$i]['rdatewy'] .'  '.$this->data['blocks']['account'][$i]['rtime'].'  ';
																							
								if(is_numeric($orderid)) {
									$orderid = sprintf("%08d", $orderid);
									$cmds .= str_pad($orderid,10);
								}
								else{
									$cmds .= str_pad('',10);
								}
								
						$cmds .= strtoupper($in_type).'';		
						
						$cmds .= '  kr '.formatcurrency($this->data['blocks']['account'][$i]['amount']);$cmds .= $newLine;
							  
						 $total +=	$this->data['blocks']['account'][$i]['amount']; 
		
						}
						
						$footerarray=array('B'=>'Bankkort (B)','K'=>'Kontant(K)','G'=>'Gift Card (G)','F'=>'Faktura (F)','kk'=>'kasse kredit (kk)');
						
						$footer='';
						$totcount=0;
						$totamt=array();
						foreach($footerarray as $fkey=>$foot)
						{
							$tot_amt=array_sum($bankarray[$fkey]);
							$totamt[]=$tot_amt;
							$amt=' kr '.formatcurrency($tot_amt);
						
						$footer_total =  $foot. count($bankarray[$fkey]).$amt;
						
						$totcount=$totcount+count($bankarray[$fkey]);
						}
						$tott_amount=array_sum($totamt);
						$total_amount=' kr '.formatcurrency($tott_amount);
						
						$delsumamt=$tott_amount/1.25;	
						$delsumamt=round($delsumamt, 2);
						$delsumvat=$tott_amount-$delsumamt;
						
						$vat_amount=' kr '.formatcurrency($delsumvat);
						
						
						$this->data['lists']['total'] =' kr '.formatcurrency($total);
						$this->data['lists']['transaction'] = count($this->data['blocks']['account']);
						
						$this->data['lists']['report'] = $str;
						$this->data['lists']['footer'] = $footer;
						
						$filename = 'Report_'.$date.'.pdf';
						  
						$date = date('Y-m-d',strtotime($date))  ;
					    $this->data['fields']['betal'] =  $this->general_model->getSalesInfo($date);
						  
						  
						$cmds .= $newLine;  
						$cmds .= $esc . '!' . '0x01';   
						$cmds .= str_replace($search, $replace, 'Summer for åpne/lukkede perioder').'     kr '.formatcurrency($this->data['fields']['betal']['total']);
						$cmds .= $newLine;
						$cmds .= str_pad('Sum transaksjoner',31).'      '. $this->data['fields']['betal']['total_transaction'];
						$cmds .= $newLine;
						$cmds .= str_pad(str_replace($search, $replace, 'Startbeløp i kassen'),36).'    kr '.formatcurrency($this->data['fields']['betal']['start_amount']);
						$cmds .= $newLine;
						$cmds .= $newLine;
						
						$cmds .= 'Betalingsinformasjonen         ' .'Antall      ' .str_replace($search, $replace, 'Beløp');
						$cmds .= $newLine ; 
						
						
						$cmds .= 'Bankkort 0x28BK0x29                  ' .str_pad(intval($this->data['fields']['betal']['bk']),'4').'       ' .' kr '.formatcurrency($this->data['fields']['betal']['bk_total']);				
						$cmds .= $newLine;
						$cmds .= 'Kontant 0x28KO0x29                   ' .str_pad(intval($this->data['fields']['betal']['ko']),'4').'       ' .' kr '.formatcurrency($this->data['fields']['betal']['ko_total']);				
						$cmds .= $newLine;
						$cmds .= 'Faktura 0x28FA0x29                   ' .str_pad(intval($this->data['fields']['betal']['fa']),'4').'       ' .' kr '.formatcurrency($this->data['fields']['betal']['fa_total']);					
						$cmds .= $newLine;
						$cmds .= 'Gavekort 0x28GK0x29                  ' .str_pad(intval($this->data['fields']['betal']['gk']),'4').'       ' .' kr '.formatcurrency($this->data['fields']['betal']['gk_total']);					
						$cmds .= $newLine;
						$cmds .= 'Kasse kredit 0x28KK0x29              ' .str_pad(intval($this->data['fields']['betal']['kk']),'4').'       ' .' kr '.formatcurrency($this->data['fields']['betal']['kk_total']);					
						$cmds .= $newLine;
						$cmds .= 'Total                          '. str_pad('','4').'       ' .' kr '.formatcurrency($this->data['fields']['betal']['total']);					
						$cmds .= $newLine;
						$cmds .= 'MVA                            ' .str_pad('','4').'       ' .' kr '.formatcurrency($this->data['fields']['betal']['tax']);					
						$cmds .= $newLine;
						$cmds .= $newLine;
						
						$this->data['reg_fields'][] = 'employee';
						$this->data['fields']['employee'] =  $this->employee_model->getEmployeeDetail($this->data['fields']['betal']['employee_p_branch'],'employee_p_branch');
						
						
						$cmds .= 'Skrivet ut av: '.$this->data['fields']['employee']['name'];
						$cmds .= $newLine;
						$cmds .= 'Skrivet ut: '.date('d.m.Y').'   '.date('H:s:i');
						$cmds .= $newLine;
						$cmds .= $esc . 'a' . '0x02';
						$cmds .= 'Page 1 of 1';
						
						
						//$cmds .= $footer_total;						  
						$cmds .= $newLine ;  
						$cmds .= $newLine ;  
						$cmds .= $newLine ;  
						$cmds .= $newLine ;  
						$cmds .= $newLine ;  
						$cmds .= $newLine ;  
						
						  /*$response = array('status'=>"success");
						  echo json_encode($response);exit;		*/							
						  
						  
					} else {
						//set flash notice
						$this->session->set_flashdata('notice-error', $this->data['lang']['lang_request_could_not_be_completed']);
						//redirect back to view the invoice
						redirect('/admin/settings/report');
					}
			
			
        }
		
		$filename = 'report_'.date('Ymd',strtotime($date));
		$foldername = PRINT_FILES_FOLDER.$filename.'.SMART';
		
		
		if ( !write_file($foldername,$cmds,'w')){
			$this->session->set_flashdata('notice-error', 'Please try again.');
			redirect('/admin/orders/'.$order_id);
			exit;
		}
        
		
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
		echo '<input type="hidden" name="pid" id="pid"  value="0"/> ';
		echo '<input type="hidden" name="printerCommands" id="printerCommands" value="'.$filename.'" /> ';
		
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
		//$orderid = ltrim($orderid, '0');		
		
		
		//DemoPrintFileProcess.php is at the same page level as WebClientPrint.php
		$demoPrintFileProcessAbsoluteURL =  $this->data['vars']['site_url'].APPPATH . 'third_party/WebClientPrint/WebClientPrintCommandsProcess.php?printtype='.$printtype.'&tag_printer='.$this->session->userdata['tag_printer'].'&bill_printer='.$this->session->userdata['bil_printer'];
		

		//echo $demoPrintFileProcessAbsoluteURL."<br>";
		
		$session_id = $this->session->userdata('session_id');
		
		//echo $session_id;
		
		//Specify the ABSOLUTE URL to the WebClientPrintController.php and to the file that will create the ClientPrintJob object
		echo WebClientPrint::createScript($webClientPrintControllerAbsoluteURL, $demoPrintFileProcessAbsoluteURL, $session_id);
		
		 
		 exit;
	}

	
	/*print saldo bill*/
	function __printSaldo($printtype)
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
			
			//get employe details who taken that order
			$employee = '';
			$this->data['reg_fields'][] = 'employee';
			$this->data['fields']['employee'] =  $this->employee_model->getEmployeeDetail($this->data['fields']['customer']['employee']);
		
			
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

			$search = array('Å','å','Æ','æ','Ø','ø','(',')');
			$replace = array('0x5D','0x7D','0x5B','0x7B','0x5C','0x7C','0x28','0x29');
		
			//Create ESC/POS commands for sample receipt
			$esc = '0x1B'; //ESC byte in hex notation
			$newLine = '0x0A'; //LF byte in hex notation
			
			$cmds = '';
			$cmds = $esc . "@"; //Initializes the printer (ESC @)
			$cmds .= $esc . '0x52' . '0x09'; //internation charater set ESC R 9
				
			$cmds .= $esc . '!' . '0x38'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
			$cmds .= $esc . 'a' . '0x01';
			$cmds .= str_replace($search, $replace,$this->data['fields']['branch']['company']); //text to print
			$cmds .= $newLine ;
			$cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
       		$cmds .= str_replace($search, $replace, $this->data['fields']['branch']['street']);
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
       		$cmds .= ucwords(str_replace($search, $replace, $this->data['fields']['customer']['customer_name']));
			$cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
			//$cmds .= $this->data['fields']['customer']['address'];
			//$cmds .= $newLine;
			//$cmds .= $this->data['fields']['customer']['zip'].' '.$this->data['fields']['customer']['city'] ;
			$cmds .= $newLine;
			$cmds .= 'Kasserer: '.str_replace($search, $replace, $this->data['fields']['employee']['initial'] );
			$cmds .= $newLine;
			$cmds .= date('d.m.Y H:s:i');
			$cmds .= $newLine.$newLine;
			
				
			$in_type  = $this->general_model->getInType($customer['in_type']);
			
			$footerarray=array('BK'=>'Bankkort (BK)','KO'=>'Kontant(KO)','GC'=>'Gift Card (GC)','FA'=>'Faktura (FA)','KK'=>'Kasse kredit (KK)');
				
			$html ='<tr>
			<td nowrap="nowrap" style="text-align: left;width:18%; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:bold;  vertical-align: top;">1</td>
			<td style="text-align: left; padding: 5px 0 0;width:55%; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">'.$footerarray[$in_type].'</td>
			<td nowrap="nowrap" style="text-align: right;width:25%; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top;">kr '.formatcurrency($customer['amount']).'</td>';
				
				$cmds .= str_pad('1', 4) . str_pad(str_replace($search, $replace,$footerarray[$in_type]), 29);
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
				$cmds .= str_pad('Delsum'.str_replace($search, $replace, $min_price_txt), 0, "", STR_PAD_LEFT).'       ';
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
			  
			  if($this->data['fields']['customer']['autofil_invoice'] != NULL){
				  
				  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Fakturakunde</td></tr>';
				  
				$cmds .= $newLine;					  
				$cmds .= 'Fakturakunde';
				$cmds .= $newLine;	
			  }
			  
			  else  if($saldo_amount != '' && $saldo_amount != '0,00')
			  {
				  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Kassakredit saldo: kr '.$saldo_amount.'</td></tr>';
				  
				$cmds .= $newLine;	
				$cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
				$cmds .= $esc . 'a' . '0x00';				
				$cmds .= 'Kassakredit saldo: kr '.$saldo_amount;
				$cmds .= $newLine;	
				  
			  }
		
		
		$this->data['fields']['customer']['summery']=$summery;
		
		//echo '<pre>';print_r($this->data['fields']['branch']);exit;
		
		//kndens kvittering  copi
		$kundens = $cmds .$newLine.$newLine;
		$kundens .= $esc . '!' . '0x24'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
		$kundens .= $esc . 'a' . '0x01';
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
		$intern .= $esc . 'a' . '0x01';
		$intern .='-Intern kopi-';
		$intern .= $newLine;	
		$intern .= $esc . 'a' . '0x00';
		
		$intern .= $newLine;
		$intern .= $newLine;
		$intern .= $newLine;
		$intern .= $newLine;
		$intern .= $newLine;
			
		$printer_commands = $kundens . $pagebreak.$intern;
		
		$filename = 'saldo_'.$saldo_id;
		$foldername = PRINT_FILES_FOLDER.$filename.'.SMART';
		
		
		if ( !write_file($foldername,$printer_commands,'w')){
			$this->session->set_flashdata('notice-error', 'Please try again.');
			redirect('/admin/products/');
			exit;
		}
		
		
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
		echo '<input type="hidden" value="true" name="pid" id="pid"  value="0"/> ';
		echo '<input type="hidden" name="printerCommands" id="printerCommands" value="'.$filename.'" /> ';
		
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
		$saldo_id = ltrim($saldo_id, '0');		
		
		
		//DemoPrintFileProcess.php is at the same page level as WebClientPrint.php
		$demoPrintFileProcessAbsoluteURL =  $this->data['vars']['site_url'].APPPATH . 'third_party/WebClientPrint/WebClientPrintCommandsProcess.php?order='.$saldo_id.'&printtype='.$printtype.'&tag_printer='.$this->session->userdata['tag_printer'].'&bill_printer='.$this->session->userdata['bil_printer'];
		

		//echo $demoPrintFileProcessAbsoluteURL."<br>";
		
		$session_id = $this->session->userdata('session_id');
		
		//echo $session_id;
		
		//Specify the ABSOLUTE URL to the WebClientPrintController.php and to the file that will create the ClientPrintJob object
		echo WebClientPrint::createScript($webClientPrintControllerAbsoluteURL, $demoPrintFileProcessAbsoluteURL, $session_id);
		
		 exit;
		
	}
	

	
	
	/*prin heatseal*/
    function __printHeatSeal($printtype)
    {
		
		$order_id = $this->uri->segment(4);
		$orderinfo = $this->orders_model->getOrderinfo($order_id);
		
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
		}
		
		
		$search = array('Å','å','Æ','æ','Ø','ø','(',')');
		$replace = array('0x5D','0x7D','0x5B','0x7B','0x5C','0x7C','0x28','0x29');
		
		
		
        //Create ESC/POS commands for sample receipt
        $esc = '0x1B'; //ESC byte in hex notation
        $newLine = '0x0A'; //LF byte in hex notation
		
		
        $cmds = '';
        $cmds = $esc . "@"; //Initializes the printer (ESC @)
		$cmds .= $esc . '0x52' . '0x09'; //internation charater set ESC R 9
		
        $cmds .= $esc . '!' . '0x38'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
		$cmds .= $esc . 'a' . '0x01';
        $cmds .= str_replace($search, $replace,$this->data['fields']['branch']['company']);; //text to print
        $cmds .= $newLine ;
        $cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
        $cmds .= str_replace($search, $replace, $this->data['fields']['branch']['street']);
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
        $cmds .= ucwords(str_replace($search, $replace, $this->data['fields']['customer']['customer_name']));
        $cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
       // $cmds .= $this->data['fields']['customer']['address'];
        $cmds .= $newLine;
        //$cmds .= $this->data['fields']['customer']['zip'].' '.$this->data['fields']['customer']['city'] ;
        //$cmds .= $newLine;
        $cmds .= 'Kasserer: '.str_replace($search, $replace, $this->data['fields']['employee']['initial'] );
        $cmds .= $newLine;
        $cmds .= $this->data['fields']['order']['order_time'];
        $cmds .= $newLine.$newLine;
		
		//get orderline
		$orderdetails = $this->orders_model->getOrderLine($order_id,'print');
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
				$chtml = '';
				$meter_text =0;
				
				$heatsealstatus=$this->process_order_model->getHeatsealStatus($orderdetails[$i]['product'],$this->data['fields']['order']['partner_branch']);
				
				if($heatsealstatus['heatseal'] == 1)
				{
				
				$arr = $this->orders_model->getProductDisplayName($orderdetails[$i]['product']);
				
				$orderdetails[$i]['name'] = $arr['name'];
				
				
				//echo '<pre>';print_r($orderdetails[$i]['p_b_delivery_time']);exit;
				if($orderdetails[$i]['p_b_delivery_time'] != '' && $orderdetails[$i]['p_b_delivery_time'] != '0000-00-00')
				{
					$b_delivery_time=date('Y-m-d',strtotime($orderdetails[$i]['p_b_delivery_time']));
					$p_b_delivery_time = date('d/m/Y',strtotime($orderdetails[$i]['p_b_delivery_time']));
					$day=date('D',strtotime($orderdetails[$i]['p_b_delivery_time']));
					//$checkdeliverydate[$b_delivery_time]= $newLine.strtolower($weekdayarray[$day]).' '.$p_b_delivery_time;
					
					$orderlinedelivery[]= '<span class="pname"> '.$orderdetails[$i]['name'].'</span> '.strtolower($weekdayarray[$day]).' '.$p_b_delivery_time;
					//$orderlinedeliverydate[]= $orderdetails[$i]['name'].$newLine.strtolower($weekdayarray[$day]).' '.$p_b_delivery_time.$newLine;
					

					$checkdeliverydate[$b_delivery_time]=  $newLine.strtolower(str_replace($search, $replace, $weekdayarray[$day])).' '.$p_b_delivery_time;
					$orderlinedeliverydate[]= str_replace($search, $replace, $orderdetails[$i]['name']).$newLine.strtolower(str_replace($search, $replace, $weekdayarray[$day])).' '.$p_b_delivery_time.$newLine;
					

					  $orderlinedelivery1[strtotime($orderdetails[$i]['p_b_delivery_time'])][]=str_replace($search, $replace, $orderdetails[$i]['name']);
					  
					  $orderlinedelivery2[strtotime($orderdetails[$i]['p_b_delivery_time'])][]=strtolower(str_replace($search, $replace, $weekdayarray[$day]));
					  
					 $orderlinedelivery3[strtotime($orderdetails[$i]['p_b_delivery_time'])][]=' '.$p_b_delivery_time.$newLine;
					
					
					
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
			  
			  $pname = str_replace($search, $replace, $orderdetails[$i]['name']);
			 $cmds .= $esc . '!' . '0x08'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex			  				 
			$prod = $pname.' ('.$quantity.')';
			  
			$cmds .= str_pad($prod, 30);
			$cmds .= $esc . 'a' . '0x02';
			$cmds .= 'kr '.formatcurrency($amount).$vary;
			$cmds .= $newLine;
			$cmds .= $esc . 'a' . '0x00';
			$cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
			
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
				$cmds .= str_replace($search, $replace, $orderdetails[$i]['special_instruction']);				 
				$cmds .= $newLine;
				
			}
			
			if($orderdetails[$i]['complain']==1){
				$str .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;line-height:32px"><b>Reklamasjon</b></p>';
				$cmds .= 'Reklamasjon';
				$cmds .= $newLine;	
			
			}
			
			if($orderdetails[$i]['in_house']==1){
				$str .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;line-height:32px"><b>Renses på huset</b></p>';
				$cmds .= str_replace($search, $replace, 'Renses på huset');				 
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
				
				$cmds .= str_replace($search, $replace, $name);
				$cmds .= $newLine;
				$cmds .= 'HS 0x23'.$baritems['barcode'] ;
				
				if($baritems['status'] == 'canceled')
				{
					$str.='<td style="text-align: center; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">0</td>';
					
					$cmds .= $esc . 'a' . '0x02';
					//$cmds .= '0';
					$cmds .= $newLine;
					$cmds .= $esc . 'a' . '0x00';
					
				}
				else
				{
					$str.='<td style="text-align: center; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;vertical-align: top; ">1 </td>';
					
					$cmds .= $esc . 'a' . '0x02';
				//	$cmds .= '1';
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
				  
						$cmds .= str_replace($search, $replace, $orderdetails[$i]['name']);
						$cmds .= $newLine;
						$cmds .= 'HS 0x23';
						$cmds .= $esc . 'a' . '0x02';
						$cmds .= '1';
						$cmds .= $newLine;
						}
					
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
				$cmds .= str_pad('Delsum'.str_replace($search, $replace, $min_price_txt), 0, "", STR_PAD_LEFT).'       ';
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
			  
				$cmds .= str_pad('Discount('.str_replace($search, $replace, $vouchercode).')', 0, "", STR_PAD_LEFT).'       ';
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
					  
					$cmds .= str_pad(str_replace($search, $replace,'Minste beløp'), 0, "", STR_PAD_LEFT).'       ';
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
			  
			  
			  if($this->data['fields']['customer']['autofil_invoice'] != NULL){
				  
				  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Fakturakunde</td></tr>';
				  
				$cmds .= $newLine;					  
				$cmds .= 'Fakturakunde';
				$cmds .= $newLine;	
			  }
			  else if($saldo_amount != '' && $saldo_amount != '0,00')
			  {
				  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Kassakredit saldo: kr '.$saldo_amount.'</td></tr>';
				  
				$cmds .= $newLine;					  
				$cmds .= 'Kassakredit saldo: kr '.$saldo_amount;
				$cmds .= $newLine;	

			  }
			   
			   if($meter_text == 1){
			   $summery.=' <tr>
              <td colspan="3" valign="top" align="left" style="text-align: left; padding: 10px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;"><i>* Prisen blir kalkulert når ferdig</i></td>
              </tr>';
			  
				$cmds .= str_replace($search, $replace,'* Prisen blir kalkulert når ferdig');
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
				$cmds .= str_replace($search, $replace, $this->data['fields']['order']['delivery_note']);
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
				$cmds .= str_replace($search, $replace, $this->data['fields']['order']['special_instruction']);
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
	   
				    //$orderlinedelivery_time = implode($newLine,$orderlinedeliverydate);
					
					$orderlinedelivery_time = '';
	   
					if(count($orderlinedelivery1) > 0)
					{
						foreach($orderlinedelivery1 as $newkey=>$newitems)
						{
							$deliveryitems=implode($newLine,$newitems);
							$deliveryitems.=$newLine.ucfirst($orderlinedelivery2[$newkey][0]);
							$deliveryitems.=$orderlinedelivery3[$newkey][0];	
							$orderlinedelivery_time .=$deliveryitems;	
							$orderlinedelivery_time.=$newLine;							
						}
					}
					
					
					$cmds .= $esc . '!' . '0x20'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
					$cmds .= 'Ferdig: ';
					$cmds .= $newLine;	
					$cmds .= $orderlinedelivery_time;
					$cmds .= $newLine;	
					
					
				}
			}
			
			$cmds .= $esc . 'a' . '0x01';
				
			$cmds .= $newLine;	
			$cmds .= $esc . '!' . '0x24'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
			$cmds .= '-Intern kopi-';
			$cmds .= $newLine;	
			$cmds .= $newLine;
			$cmds .= $newLine;
			$cmds .= $newLine;
			$cmds .= $newLine;
			$cmds .= $newLine;
			$cmds .= $esc . 'a' . '0x00';
			
		//$order_id
		/* $sessionname=$printtype.'_1067575';
		 @session_start();
		 @$_SESSION[$sessionname]=$cmds;
		 echo $_SESSION['heatseal_1067575'];*/
		 
		$filename = $printtype.'_'.$order_id;
		$foldername = PRINT_FILES_FOLDER.$filename.'.SMART';
		
		
		if ( !write_file($foldername,$cmds,'w')){
			$this->session->set_flashdata('notice-error', 'Please try again.');
			redirect('/admin/orders/'.$order_id);
			exit;
		}
		 
		 		
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
		echo '<input type="hidden" value="true" name="pid" id="pid"  value="0"/> ';
		echo '<input type="hidden" name="printerCommands" id="printerCommands" value="'.$filename.'" /> ';
		
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
		$order_id = ltrim($order_id, '0');		
		
		
		//DemoPrintFileProcess.php is at the same page level as WebClientPrint.php
		$demoPrintFileProcessAbsoluteURL =  $this->data['vars']['site_url'].APPPATH . 'third_party/WebClientPrint/WebClientPrintCommandsProcess.php?order='.$order_id.'&printtype='.$printtype.'&tag_printer='.$this->session->userdata['tag_printer'].'&bill_printer='.$this->session->userdata['bil_printer'];
		

		//echo $demoPrintFileProcessAbsoluteURL."<br>";
		
		$session_id = $this->session->userdata('session_id');
		
		//echo $session_id;
		
		//Specify the ABSOLUTE URL to the WebClientPrintController.php and to the file that will create the ClientPrintJob object
		echo WebClientPrint::createScript($webClientPrintControllerAbsoluteURL, $demoPrintFileProcessAbsoluteURL, $session_id);
		 
		 exit;
	}




	
	function __printTag($printtype,$bool='0',$oid)
	{
		
		$orderlines= array();
		$qtys= array();
		$orderlineqtys= array();
		$orderid = ($this->uri->segment(4)) ? $this->uri->segment(4) : $oid ;
		if(isset($_GET['orderlines']) && $_GET['orderlines']!='')
		  $orderlines= explode(',',$_GET['orderlines']);
		
		if(isset($_GET['qty']) && $_GET['qty']!='')
		  $qtys= explode(',',$_GET['qty']);
		 
		 if(count($orderlines) > 0 && count($qtys) > 0)
		 {
			$orderlineqtys = array_combine($orderlines, $qtys);
		 }
		 
		
		$lines=$this->orders_model->getOrderLine($orderid,'print');
		$orderinfo = $this->orders_model->getOrderinfo($orderid);
		$customer = $this->orders_model->getCustomerDetails($orderid);
		$cname  = $customer['clname'].', '.substr($customer['cfname'], 0, 1);
	
		
		$weekdayarray=array("Mon"=>"Man","Tue"=>"Tirs","Wed"=>"Ons","Thu"=>"Tors","Fri"=>"Fre","Sat"=>"lør","Sun"=>"Søn");
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
		
		$companyname = mb_substr($company,0,2);
		
		$search = array('Å','å','Æ','æ','Ø','ø','(',')');
		$replace = array('0x5D','0x7D','0x5B','0x7B','0x5C','0x7C','0x28','0x29');
		
		
		$partnerinfo['id'] = sprintf('%02d', $partnerinfo['id']); ;
		
		
		//Create ESC/POS commands for sample receipt
		$esc = '0x1B'; //ESC byte in hex notation
		$newLine = '0x0A'; //LF byte in hex notation
		$cmds = '';
		$cmds = $esc . "@"; //Initializes the printer (ESC @)
		$cmds .= $esc . '0x52' . '0x09'; //internation charater set ESC R 9
		
			
		//page break
		$pagebreak = $newLine;
		$pagebreak .= '0x1D0x560x00';
		
		$htmlout='';
		if(count($lines) > 0)
		{
			$total_qty = 0;
			
			foreach($lines as $olineitmes)
			{
				
				if(!empty($orderlines)){
					
					
					if(in_array($olineitmes['id'],$orderlines))
					{
						 
						 if($olineitmes['product'] !=1)
						 {  
							//exclde Skjorte bec'z Skjorte has only heatseal not tag
							 $q = ($olineitmes['changed_quantity'] == '') ? $olineitmes['quantity']:$olineitmes['changed_quantity'];
							
							 $prodtype=$this->process_order_model->validateProducttype($olineitmes['product']);
							 
							  if($orderdetails[$i]['in_meter'] == 1)
							  {
								$actualqty=1;
								if(isset($orderlineqtys[$olineitmes['id']]))
								{
									$actualqty=$actualqty*$orderlineqtys[$olineitmes['id']];
								}
							  }
							  else
							  {
								$actualqty=$prodtype*$q;
								if(isset($orderlineqtys[$olineitmes['id']]))
								{
									$actualqty=$actualqty*$orderlineqtys[$olineitmes['id']];
								}
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
								if(isset($orderlineqtys[$olineitmes['id']]))
								{
									$actualqty=$actualqty*$orderlineqtys[$olineitmes['id']];
								}
							  }
							  else
							  {
								$actualqty=$prodtype*$q;
								if(isset($orderlineqtys[$olineitmes['id']]))
								{
									$actualqty=$actualqty*$orderlineqtys[$olineitmes['id']];
								}
							  }
								
							  $q =intval($actualqty);
							
							  $total_qty += intval($q);
						  
						 }
						
				}

			}
			
	
			
			$item = 1;
			foreach($lines as $olineitmes)
			{
			if(!empty($orderlines))
			{
			 
				if(in_array($olineitmes['id'],$orderlines))
				{
			
					$arr = $this->orders_model->getProductDisplayName($olineitmes['product']);
					//print_r($arr);
					$olineitmes['name'] = $arr['name'];
					//echo $olineitmes['name']."<br>";
			
			
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
						if(isset($orderlineqtys[$olineitmes['id']]))
						{
							$actualqty=$actualqty*$orderlineqtys[$olineitmes['id']];
						}
			
					  }
					  else
					  {
						$actualqty=$prodtype*$qty;
						if(isset($orderlineqtys[$olineitmes['id']]))
						{
							$actualqty=$actualqty*$orderlineqtys[$olineitmes['id']];
						}
					  }
						
						$qty =intval($actualqty);
						
					
						if($qty > 0)
						{
							foreach(range(1,$qty) as $qtys)
							{

								$cmds .= $esc . '!' . '0x18'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
								$order = $orderid.' '.str_replace($search, $replace, $companyname).' '.$partnerinfo['id'];	
								$cmds .= str_pad($order, 19) ;
								$cmds .= $esc . 'a' . '0x02';
								$cmds .= $order ;
								$cmds .= $esc . 'a' . '0x00';
								$cmds .= $newLine;
								$rdy = $partnerinfo['id'].' : '.$ready.' ';
								$cmds .= str_pad($rdy, 13); 
								$cmds .= $esc . '!' . '0x00'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex	
								$cmds .= ' I '.date('d/m',strtotime($orderinfo['order_time'])). ' : ' .'Rdy '.$ready;
								$cmds .= $esc . '!' . '0x18'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
								$cmds .= $newLine;
								$day = str_replace($search, $replace,$weekday).':'.$item.'/'.$total_qty;
								$cmds .= str_pad($day, 11) ;
								$cmds .= $esc . '!' . '0x00'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex	
								$cmds .= str_replace($search, $replace,$olineitmes['name']); //text to print
								$cmds .= $newLine;
								$cmds .= $esc . '!' . '0x00'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex	
								$cmds .= str_replace($search, $replace,$cname).' ' . $customer['number'];	
								$cmds .= $newLine;
								$cmds .= '0x1D0x560x00';
								$item ++;
						
					
						}
					}
				
					}
				}	
				
			 }
			else
			{
				 
				if($olineitmes['product'] !=1){  //exclde Skjorte bec'z Skjorte has only heatseal not tag
					$ready='';
					
					
					$arr = $this->orders_model->getProductDisplayName($olineitmes['product']);
					//print_r($arr);
					$olineitmes['name'] = $arr['name'];
					
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
							$cmds .= $esc . '!' . '0x18'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
							$order = $orderid.' '.str_replace($search, $replace, $companyname).' '.$partnerinfo['id'];	
							$cmds .= str_pad($order, 19) ;
							$cmds .= $esc . 'a' . '0x02';
							$cmds .= $order ;
							$cmds .= $esc . 'a' . '0x00';
							$cmds .= $newLine;
							$rdy = $partnerinfo['id'].' : '.$ready.' ';
							$cmds .= str_pad($rdy, 13); 
							$cmds .= $esc . '!' . '0x00'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex	
							$cmds .= ' I '.date('d/m',strtotime($orderinfo['order_time'])). ' : ' .'Rdy '.$ready;
							$cmds .= $esc . '!' . '0x18'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
							$cmds .= $newLine;
							$day = str_replace($search, $replace,$weekday).':'.$item.'/'.$total_qty;
							$cmds .= str_pad($day, 11) ;
							$cmds .= $esc . '!' . '0x00'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex	
							$cmds .= str_replace($search,$replace, $olineitmes['name']); //text to print
							$cmds .= $newLine;
							$cmds .= $esc . '!' . '0x00'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex	
							$cmds .= str_replace($search, $replace,$cname).' ' . $customer['number'];	
							$cmds .= $newLine;
							$cmds .= '0x1D0x560x00';
							$item ++;
						
					}
					
					}
				}
				 
			 }

		}
		
		$cmds .= $newLine;
		$cmds .= $newLine;
		$cmds .= $newLine;
		$cmds .= $newLine;
		$cmds .= $newLine;
		$cmds .= $newLine;
		$cmds .= '0x1D0x560x00';
		
		if($bool){
			return $cmds;
		}
		
			
		$filename = 'tag_'.$orderid;
		$foldername = PRINT_FILES_FOLDER.$filename.'.SMART';
		
		
		if ( !write_file($foldername,$cmds,'w')){
			$this->session->set_flashdata('notice-error', 'Please try again.');
			redirect('/admin/orders/'.$order_id);
			exit;
		}
			
			
			
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
			$printerName='EPSON TM-U220B Receipt';
		}
		
		
		//$printerName='EPSON TM-88III Receipt';	
		//$printerName='EPSON TM-U220B Receipt';
		
		//profiling
        $this->data['controller_profiling'][] = __function__;
		echo '<input type="hidden" value="true" name="pid" id="pid"  value="0"/> ';
		echo '<input type="hidden" name="printerCommands" id="printerCommands" value="'.$filename.'" /> ';
		
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
		
		
		
	}
	
	
    function __printBill($printtype)
    {
		
		$order_id = $this->uri->segment(4);
		$orderinfo = $this->orders_model->getOrderinfo($order_id);
		
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
		}
		
		
		$search = array('Å','å','Æ','æ','Ø','ø','(',')');
		$replace = array('0x5D','0x7D','0x5B','0x7B','0x5C','0x7C','0x28','0x29');
		
		
        //Create ESC/POS commands for sample receipt
        $esc = '0x1B'; //ESC byte in hex notation
        $newLine = '0x0A'; //LF byte in hex notation
		
		
        $cmds = '';
        $cmds = $esc . "@"; //Initializes the printer (ESC @)
        $cmds .= $esc . '!' . '0x38'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
		$cmds .= $esc . 'a' . '0x01';
		$cmds .= $esc . '0x52' . '0x09'; //internation charater set ESC R 9
		
        $cmds .= str_replace($search, $replace,$this->data['fields']['branch']['company']); //text to print
        $cmds .= $newLine ;
        $cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
        $cmds .= str_replace($search, $replace, $this->data['fields']['branch']['street']);
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
        $cmds .= ucwords(str_replace($search, $replace, $this->data['fields']['customer']['customer_name']));
        $cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
       // $cmds .= $this->data['fields']['customer']['address'];
        $cmds .= $newLine;
        //$cmds .= $this->data['fields']['customer']['zip'].' '.$this->data['fields']['customer']['city'] ;
        //$cmds .= $newLine;
        $cmds .= 'Kasserer: '.str_replace($search, $replace, $this->data['fields']['employee']['initial'] );
        $cmds .= $newLine;
        $cmds .= $this->data['fields']['order']['order_time'];
        $cmds .= $newLine.$newLine;
		
		
		//get orderline
		$orderdetails = $this->orders_model->getOrderLine($order_id,'print');
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
			$is_shirt_boolean = false;
			for($i=0;$i< count($orderdetails);$i++)
			{
				$html='';
				$chtml = '';
				$meter_text =0;
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
						$etter  .= $esc . 'a' . '0x00'; 
						$etter .= $newLine.'Etter kl. 13:00';
						//$etter .= $esc . 'a' . '0x00'; 	
					 }
					
					 $checkdeliverydate[$b_delivery_time]=  strtolower(str_replace($search, $replace, $weekdayarray[$day])).' '.$p_b_delivery_time.$etter;
					 
					  $dstr = str_replace($search, $replace, $orderdetails[$i]['name']);
					 
					 
					  $orderlinedelivery1[strtotime($orderdetails[$i]['p_b_delivery_time'])][]=str_replace($search, $replace, $orderdetails[$i]['name']);
					  
					  $orderlinedelivery2[strtotime($orderdetails[$i]['p_b_delivery_time'])][]=strtolower(str_replace($search, $replace, $weekdayarray[$day]));
					  
					 $orderlinedelivery3[strtotime($orderdetails[$i]['p_b_delivery_time'])][]=' '.$p_b_delivery_time.$etter.$newLine;
					 
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
								 
				/*$cmds .= str_pad($quantity, 5);
				$cmds .= str_pad($pname, 27);	*/							 
								 
				$cmds .= str_pad($quantity, 5) . str_pad($pname, 25);
				$chtml .= str_pad($quantity, 5) . str_pad($pname, 25);
				
				
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
				
				
				$chtml .= $esc . 'a' . '0x02';
				$chtml .= 'kr '.formatcurrency($productPrice).$vary;
				$chtml .= $newLine;
				$chtml .= $esc . 'a' . '0x00';
				
				
				if($orderdetails[$i]['special_instruction']!=''){
					$html .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;font-size: 30px; line-height:32px"><b>Fritekst :</b><br>'.$orderdetails[$i]['special_instruction'].'</p>';
					
					$cmds .= 'Fritekst : ';
					$cmds .= $newLine;
					$cmds .= str_replace($search, $replace, $orderdetails[$i]['special_instruction']);				 
					$cmds .= $newLine;
					
					$chtml .= 'Fritekst : ';
					$chtml .= $newLine;
					$chtml .= str_replace($search, $replace, $orderdetails[$i]['special_instruction']);				 
					$chtml .= $newLine;
						
				}
				
				if($orderdetails[$i]['complain']==1){
					$html .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;font-size: 30px; line-height:32px"><b>Reklamasjon</b></p>';
					
					$cmds .= 'Reklamasjon';
					$cmds .= $newLine;	
					
					$chtml .= 'Reklamasjon';
					$chtml .= $newLine;
					
				}
				
				if($orderdetails[$i]['in_house']==1){
					$html .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;font-size: 30px; line-height:32px"><b>Renses på huset</b></p>';
					$cmds .= str_replace($search, $replace, 'Renses på huset');				 
					$cmds .= $newLine;	
					
					$chtml .= str_replace($search, $replace, 'Renses på huset');				 
					$chtml .= $newLine;	
					
				}
					
			  
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
				  
				$cmds .= $newLine;
				$cmds .= $esc . 'a' . '0x02';
				$cmds .= str_pad('Delsum'.str_replace($search, $replace, $min_price_txt), 0, "", STR_PAD_LEFT).'         ';
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
			  
				$cmds .= str_pad('Discount('.str_replace($search, $replace, $vouchercode).')', 0, "", STR_PAD_LEFT).'         ';
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
					  
					$cmds .= str_pad(str_replace($search, $replace,'Minste beløp'), 0, "", STR_PAD_LEFT).'          ';
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
			  
				$cmds .= str_pad('Herav 25% MVA', 0, "", STR_PAD_LEFT).'         ';
				$cmds .= 'kr '.formatcurrency($mva);
				$cmds .= $newLine;	
			  
				$cmds .= str_pad('Totalt', 0, "", STR_PAD_LEFT).'         ';
				$cmds .= 'kr '.formatcurrency($this->data['fields']['order']['totalt']);
				$cmds .= $newLine;
					
				$cmds .= $esc . 'a' . '0x00';
			  
			  if($this->data['fields']['customer']['autofil_invoice'] != NULL){
				  
				  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Fakturakunde</td></tr>';
				  
				$cmds .= $newLine;					  
				$cmds .= 'Fakturakunde';
				$cmds .= $newLine;	
			  }
			  else if($saldo_amount != '' && $saldo_amount != '0,00')
			  {
				  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Kassakredit saldo: kr '.$saldo_amount.'</td></tr>';
				  
				$cmds .= $newLine;					  
				$cmds .= 'Kassakredit saldo: kr '.$saldo_amount;
				$cmds .= $newLine;	
 
			  }
			   
			   if($meter_text == 1){
			   $summery.=' <tr>
              <td colspan="3" valign="top" align="left" style="text-align: left; padding: 10px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;"><i>* Prisen blir kalkulert når ferdig</i></td>
              </tr>';
			  
				$cmds .= str_replace($search, $replace,'* Prisen blir kalkulert når ferdig');
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
				$cmds .= str_replace($search, $replace, $this->data['fields']['order']['delivery_note']);
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
				$cmds .= str_replace($search, $replace, $this->data['fields']['order']['special_instruction']);
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
	   
				    //$orderlinedelivery_time = implode($newLine,$orderlinedeliverydate);
					$orderlinedelivery_time = '';
	   
					if(count($orderlinedelivery1) > 0)
					{
						foreach($orderlinedelivery1 as $newkey=>$newitems)
						{
							$deliveryitems=implode($newLine,$newitems);
							$deliveryitems.=$newLine.ucfirst($orderlinedelivery2[$newkey][0]);
							$deliveryitems.=$orderlinedelivery3[$newkey][0];	
							$orderlinedelivery_time .=$deliveryitems;	
							$orderlinedelivery_time.=$newLine;							
						}
					}
					
					
					$cmds .= $esc . '!' . '0x20'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
					$cmds .= 'Ferdig: ';
					$cmds .= $newLine;	
					$cmds .= $orderlinedelivery_time;
					$cmds .= $newLine;	
					
				}
			}
			
			
			
			$cmds .= $newLine;
			$cmds .= $esc . 'a' . '0x01';	
			$cmds .= $esc . '!' . '0x24'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
			$cmds .= '-Kundens kvittering-';
			$cmds .= $newLine;	
			$cmds .= $esc . 'a' . '0x00';
			
			
		
			$commonheader = '';
			$commonheader .= $esc . '!' . '0x38'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
			$commonheader .= $esc . 'a' . '0x01';
			$commonheader .= str_replace($search, $replace,$this->data['fields']['branch']['company']); //text to print
			$commonheader .= $newLine ;
			$commonheader .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
			$commonheader .= str_replace($search, $replace, $this->data['fields']['branch']['street']);
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
			$commonheader .= ucwords(str_replace($search, $replace, $this->data['fields']['customer']['customer_name']));
			$commonheader .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
			//$commonheader .= $this->data['fields']['customer']['address'];
			$commonheader .= $newLine;
			//$commonheader .= $this->data['fields']['customer']['zip'].' '.$this->data['fields']['customer']['city'] ;
			//$commonheader .= $newLine;
			$commonheader .= 'Kasserer: '.str_replace($search, $replace, $this->data['fields']['employee']['initial']);
			$commonheader .= $newLine;
			$commonheader .= $this->data['fields']['order']['order_time'];
			$commonheader .= $newLine.$newLine;
			
			
			
			
		  $kk = '';
		  if($this->data['fields']['customer']['autofil_invoice'] != NULL){
			  
			  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Fakturakunde</td></tr>';
			  
			$kk .= $newLine;					  
			$kk .= 'Fakturakunde';
			$kk .= $newLine;	
		  }
		  else if($saldo_amount != '' && $saldo_amount != '0,00')
		  {
			  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Kassakredit saldo: kr '.$saldo_amount.'</td></tr>';
			$kk .= $newLine;
			$kk .= $esc . 'a' . '0x00';
			$kk .= 'Kassakredit saldo: kr '.$saldo_amount;
			$kk .= $newLine;	

		  }
		  
			$mt  = str_replace($search, $replace,'* Prisen blir kalkulert når ferdig');
			$mt .= $newLine.$newLine;	
		  
		  
		    $commonfooter ='';
			$commonfooter .= $newLine;	
			$commonfooter .= $esc . 'a' . '0x01';
			$commonfooter .= $esc . '!' . '0x24'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
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
		//echo '<pre>';print_r($categoryorderline);exit;
		
		
		if(count($orderdetails) > 0){
            if(count($categoryorderline) > 0)
			{
				
				foreach($categoryorderline as $catkey=>$catitems)
				{
					$antall = 0;
					$z = 0;
					$amount = 0;
					$in_meter = 0;
					if(count($catitems) > 0)
					{
					
						$categoryprint.=$pagebreak;
						$categoryprint.= $commonheader;
						$catout='';
						$catdelivery = '';
						$w = 0;
						$weekday = array();
						$weekdate = array();
						
						foreach($catitems as $key=>$cat_html)
						{
							$catout.= $catitems[$key]['item'];
							$antall += $catitems[$key]['antall'];
							$amount += $catitems[$key]['amount'];
							$in_meter += $catitems[$key]['in_meter'];
							
							if(count($catitems) == 1){
								$catdelivery .= $catitems[$key]['name'].$newLine;
								$catdelivery .= $catitems[$key]['day']." ".date('d/m/Y',strtotime($catitems[$key]['date']));
								break;
							}
							else{
                                if($w != 0){
                                    $day = $catitems[$key]['day'];
                                    $date = strtotime($catitems[$key]['date']);
                                    if(in_array($date, $weekdate)){
										$catdelivery .= $catitems[$key]['name'].$newLine;
										if($w == (count($catitems)-1)){
											$catdelivery .= ucfirst($temp_day)." ".$temp_date;
										}
										$w++;
										$temp_day = $catitems[$key]['day'];
										$temp_date = date('d/m/Y',strtotime($catitems[$key]['date']));
                                        continue;
                                    }
                                    else{
                                        $catdelivery .= ucfirst($temp_day)." ".$temp_date.$newLine.$newLine;
                                    }
                                }
								$catdelivery .= $catitems[$key]['name'].$newLine;
								$weekday[] = $catitems[$key]['day'];
								$weekdate[] = strtotime($catitems[$key]['date']);
								$temp_day = $catitems[$key]['day'];
								$temp_date = date('d/m/Y',strtotime($catitems[$key]['date']));
							}
							
							if($w == (count($catitems)-1)){
								$catdelivery .= ucfirst($temp_day)." ".$temp_date;
							}
							$w++;
							
						}
					
						$categoryprint.=$catout;
						
						$categoryprint .= $esc . '!' . '0x00';
						$categoryprint .= $newLine;
						$categoryprint .= $esc . 'a' . '0x02';
						$categoryprint .= str_pad('Delsum', 0, "", STR_PAD_LEFT).'         ';
						$categoryprint .= 'kr '.formatcurrency($amount);
						$categoryprint .= $newLine;	
						$categoryprint .= str_pad('Herav 25% MVA', 0, "", STR_PAD_LEFT).'         ';
						$categoryprint .= 'kr '.formatcurrency($cmva);
						$categoryprint .= $newLine;	
						$categoryprint .= str_pad('Totalt', 0, "", STR_PAD_LEFT).'         ';
						$categoryprint .= 'kr '.formatcurrency($amount);
						$categoryprint .= $newLine;
						$categoryprint .= $esc . 'a' . '0x00';
						
						
						$categoryprint .= $esc . '!' . '0x20'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex						
						$categoryprint .= $newLine;
						$categoryprint .= $esc . 'a' . '0x00';
						$categoryprint .=' Antall '.$antall;
						$categoryprint .= $newLine;
						
						$categoryprint .= $esc . '!' . '0x00';
						
						$categoryprint .= $newLine;	
						$categoryprint .= $kk;
						$categoryprint .= $newLine;	
						
						if($in_meter > 0){
							$categoryprint .= $newLine;	
							$categoryprint .= $mt;
						}
						
					   //mva
						$cmva=$amount/1.25;	
						$cmva=round($cmva, 2);
						$cmva=$amount-$cmva;
						
						
						$categoryprint .= $esc . '!' . '0x20'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
						$categoryprint .= 'Ferdig: ';
						$categoryprint .= $newLine;	
						$categoryprint .= $catdelivery;
						$categoryprint .= $newLine;
						$categoryprint .= $esc . '!' . '0x00'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
						$categoryprint.= $commonfooter;
						$categoryprint .= $newLine;
						$categoryprint .= $newLine;
						$categoryprint .= $newLine;
						$categoryprint .= $newLine;
						$categoryprint .= $newLine;
						
					}
				
				}
				
			}
		}

		
		$printCommand = $cmds . $categoryprint;
		
		
		$filename = $printtype.'_'.$order_id;
		$foldername = PRINT_FILES_FOLDER.$filename.'.SMART';
		
		
		if ( !write_file($foldername,$printCommand,'w')){
			$this->session->set_flashdata('notice-error', 'Please try again.');
			redirect('/admin/orders/'.$order_id);
			exit;
		}
		
		$filename2 = '';
		$cmds2 =  '';
		$processfile =  'WebClientPrintCommandsProcess.php';
		
	    if($this->session->userdata['partner_branch']!='1'){	//not branch 1 (Vinderen)
		
			$cmds2 = $this->__printTag('tag','1',$order_id);	
				
			$filename2 = 'tag_'.$order_id;
			$foldername = PRINT_FILES_FOLDER.$filename2.'.SMART';
			
			
			if ( !write_file($foldername,$cmds2,'w')){
				$this->session->set_flashdata('notice-error', 'Please try again.');
				redirect('/admin/');
				exit;
			}
			
			$printerName2=$this->session->userdata['tag_printer'];
			
			$processfile = 'WebClientPrintCommandsProcessMultiple.php';
		
	 	}
		
		
		if($printtype == 'tag')
		{
			$printerName2=$this->session->userdata['tag_printer'];
		}
		else
		{
			$printerName=$this->session->userdata['bil_printer'];
		}
		
		if($printerName == '')
		{
			$printerName='EPSON TM-T88IV Receipt';
		}
		
		/*$printerName='EPSON TM-T88III Receipt';
		$printerName2 = 'EPSON TM-U220 Receipt';*/
		
		
		
		//profiling
        $this->data['controller_profiling'][] = __function__;
		echo '<input type="hidden" value="true" name="pid" id="pid"  value="0"/> ';
		echo '<input type="hidden" name="printerCommands" id="printerCommands" value="'.$filename.'" /> ';
		echo '<input type="hidden" name="printerCommands2" id="printerCommands2" value="'.$filename2.'" /> ';
		
		echo '<a style="visibility:hidden;"  id="printbtn" class="btn btn-success btn-large" onclick="javascript:jsWebClientPrint.print(\'pid=\' + $(\'#pid\').attr(\'checked\')+\'&printerName='.$printerName.'&printerName2='.$printerName2.'&printerCommands=\' + $(\'#printerCommands\').val() + \'&printerCommands2=\' + $(\'#printerCommands2\').val());">Print File...</a>';
		 

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
		$orderid = ltrim($order_id, '0');		
		
		
		//DemoPrintFileProcess.php is at the same page level as WebClientPrint.php
		$demoPrintFileProcessAbsoluteURL =  $this->data['vars']['site_url'].APPPATH . 'third_party/WebClientPrint/'.$processfile.'?order='.$orderid.'&printtype='.$printtype.'&tag_printer='.$this->session->userdata['tag_printer'].'&bill_printer='.$this->session->userdata['bil_printer'];
		

		//echo $demoPrintFileProcessAbsoluteURL."<br>";
		
		$session_id = $this->session->userdata('session_id');
		
		//echo $session_id;
		
		//Specify the ABSOLUTE URL to the WebClientPrintController.php and to the file that will create the ClientPrintJob object
		echo WebClientPrint::createScript($webClientPrintControllerAbsoluteURL, $demoPrintFileProcessAbsoluteURL, $session_id);
		
		 
		 exit;
	}
	
	
	
	/*print kunden bill*/
    function __printKundenBill($printtype)
    {
		
		$order_id = $this->uri->segment(4);
		$orderinfo = $this->orders_model->getOrderinfo($order_id);
		
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
		}
		
		
		$search = array('Å','å','Æ','æ','Ø','ø','(',')');
		$replace = array('0x5D','0x7D','0x5B','0x7B','0x5C','0x7C','0x28','0x29');
		
		
        //Create ESC/POS commands for sample receipt
        $esc = '0x1B'; //ESC byte in hex notation
        $newLine = '0x0A'; //LF byte in hex notation
		
		
        $cmds = '';
        $cmds = $esc . "@"; //Initializes the printer (ESC @)
        $cmds .= $esc . '!' . '0x38'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
		$cmds .= $esc . 'a' . '0x01';
		$cmds .= $esc . '0x52' . '0x09'; //internation charater set ESC R 9
		
        $cmds .= str_replace($search, $replace,$this->data['fields']['branch']['company']); //text to print
        $cmds .= $newLine ;
        $cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
        $cmds .= str_replace($search, $replace, $this->data['fields']['branch']['street']);
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
        $cmds .= ucwords(str_replace($search, $replace, $this->data['fields']['customer']['customer_name']));
        $cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
       // $cmds .= $this->data['fields']['customer']['address'];
        $cmds .= $newLine;
        //$cmds .= $this->data['fields']['customer']['zip'].' '.$this->data['fields']['customer']['city'] ;
        //$cmds .= $newLine;
        $cmds .= 'Kasserer: '.str_replace($search, $replace, $this->data['fields']['employee']['initial'] );
        $cmds .= $newLine;
        $cmds .= $this->data['fields']['order']['order_time'];
        $cmds .= $newLine.$newLine;
		
		
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
						$etter = $esc . 'a' . '0x00'; 
						$etter .= $newLine.'Etter kl. 13:00';
						//$etter .= $esc . 'a' . '0x00'; 	
					 }
					
					 $checkdeliverydate[$b_delivery_time]=  strtolower(str_replace($search, $replace, $weekdayarray[$day])).' '.$p_b_delivery_time.$etter;
					
					  $dstr = str_replace($search, $replace, $orderdetails[$i]['name']);
					  
					  $orderlinedelivery1[strtotime($orderdetails[$i]['p_b_delivery_time'])][]=str_replace($search, $replace, $orderdetails[$i]['name']);
					  
					  $orderlinedelivery2[strtotime($orderdetails[$i]['p_b_delivery_time'])][]=strtolower(str_replace($search, $replace, $weekdayarray[$day]));
					  
					 $orderlinedelivery3[strtotime($orderdetails[$i]['p_b_delivery_time'])][]=' '.$p_b_delivery_time.$etter.$newLine;
					  
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
								 
				/*$cmds .= str_pad($quantity, 5);

				$cmds .= str_pad($pname, 27);	*/							 
								 
				$cmds .= str_pad($quantity, 5) . str_pad($pname, 25);
				$chtml .= str_pad($quantity, 5) . str_pad($pname, 25);
				
				
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
				
				
				$chtml .= $esc . 'a' . '0x02';
				$chtml .= 'kr '.formatcurrency($productPrice).$vary;
				$chtml .= $newLine;
				$chtml .= $esc . 'a' . '0x00';
				
				
				if($orderdetails[$i]['special_instruction']!=''){
					$html .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;font-size: 30px; line-height:32px"><b>Fritekst :</b><br>'.$orderdetails[$i]['special_instruction'].'</p>';
					
					$cmds .= 'Fritekst : ';
					$cmds .= $newLine;
					$cmds .= str_replace($search, $replace, $orderdetails[$i]['special_instruction']);				 
					$cmds .= $newLine;
					
					$chtml .= 'Fritekst : ';
					$chtml .= $newLine;
					$chtml .= str_replace($search, $replace, $orderdetails[$i]['special_instruction']);				 
					$chtml .= $newLine;
						
				}
				
				if($orderdetails[$i]['complain']==1){
					$html .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;font-size: 30px; line-height:32px"><b>Reklamasjon</b></p>';
					
					$cmds .= 'Reklamasjon';
					$cmds .= $newLine;	
					
					$chtml .= 'Reklamasjon';
					$chtml .= $newLine;
					
				}
				
				if($orderdetails[$i]['in_house']==1){
					$html .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;font-size: 30px; line-height:32px"><b>Renses på huset</b></p>';
					$cmds .= str_replace($search, $replace, 'Renses på huset');				 
					$cmds .= $newLine;	
					
					$chtml .= str_replace($search, $replace, 'Renses på huset');				 
					$chtml .= $newLine;	
					
				}
					
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
				  
				$cmds .= $newLine;
				$cmds .= $esc . 'a' . '0x02';
				$cmds .= str_pad('Delsum'.str_replace($search, $replace, $min_price_txt), 0, "", STR_PAD_LEFT).'         ';
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
			  
				$cmds .= str_pad('Discount('.str_replace($search, $replace, $vouchercode).')', 0, "", STR_PAD_LEFT).'         ';
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
					  
					$cmds .= str_pad(str_replace($search, $replace,'Minste beløp'), 0, "", STR_PAD_LEFT).'          ';
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
			  
				$cmds .= str_pad('Herav 25% MVA', 0, "", STR_PAD_LEFT).'         ';
				$cmds .= 'kr '.formatcurrency($mva);
				$cmds .= $newLine;	
			  
				$cmds .= str_pad('Totalt', 0, "", STR_PAD_LEFT).'         ';
				$cmds .= 'kr '.formatcurrency($this->data['fields']['order']['totalt']);
				$cmds .= $newLine;
					
				$cmds .= $esc . 'a' . '0x00';
			  
			  if($this->data['fields']['customer']['autofil_invoice'] != NULL){
				  
				  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Fakturakunde</td></tr>';
				  
				$cmds .= $newLine;					  
				$cmds .= 'Fakturakunde';
				$cmds .= $newLine;	
			  }
			  else if($saldo_amount != '' && $saldo_amount != '0,00')
			  {
				  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Kassakredit saldo: kr '.$saldo_amount.'</td></tr>';
				  
				$cmds .= $newLine;					  
				$cmds .= 'Kassakredit saldo: kr '.$saldo_amount;
				$cmds .= $newLine;	
 
			  }
			   
			   if($meter_text == 1){
			   $summery.=' <tr>
              <td colspan="3" valign="top" align="left" style="text-align: left; padding: 10px 0 0; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal;"><i>* Prisen blir kalkulert når ferdig</i></td>
              </tr>';
			  	$cmds .= $newLine;	
				$cmds .= str_replace($search, $replace,'* Prisen blir kalkulert når ferdig');
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
				$cmds .= str_replace($search, $replace, $this->data['fields']['order']['delivery_note']);
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
				$cmds .= str_replace($search, $replace, $this->data['fields']['order']['special_instruction']);
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
							$deliveryitems=implode($newLine,$newitems);
							$deliveryitems.=$newLine.ucfirst($orderlinedelivery2[$newkey][0]);
							$deliveryitems.=$orderlinedelivery3[$newkey][0];	
							$orderlinedelivery_time .=$deliveryitems;	
							$orderlinedelivery_time.=$newLine;	
							//echo $orderlinedelivery_time;						
						}
					}
					
	   
					
					$cmds .= $esc . '!' . '0x20'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
					$cmds .= 'Ferdig: ';
					$cmds .= $newLine;	
					$cmds .= $orderlinedelivery_time;
					$cmds .= $newLine;	
					
					
				}
			}
		
			
			$deliverytime=$orderinfo['deliverytime'];
			if($deliverytime != '' && $orderinfo['order_status'] == 9)
			{
				
				
				$orderlineemp = $this->employee_model->getEmployeebranchDetail(0,0,$orderinfo['employee_p_branch']);
				$empinitial=$orderlineemp['initial'];
				
				$cmds .= $newLine;	
				$cmds .= $esc . 'a' . '0x01';
				$cmds .= $esc . '!' . '0x24'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
				$cmds .= 'Utlevert   '.$empinitial;
				$cmds .= $newLine;
				$cmds .= $deliverytime;
				$cmds .= $newLine;
				$cmds .= $esc . 'a' . '0x00';
			
			}
			
			
				$cmds .= $newLine;	
				$cmds .= $esc . '!' . '0x24'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
				$cmds .= '-Kundens kvittering-';
				$cmds .= $newLine;	
				$cmds .= $newLine.$newLine;
				$cmds .= $newLine.$newLine;
				$cmds .= $esc . 'a' . '0x00';
				
				
		$filename = 'kunden_'.$order_id;
		$foldername = PRINT_FILES_FOLDER.$filename.'.SMART';
		
		
		if ( !write_file($foldername,$cmds,'w')){
			$this->session->set_flashdata('notice-error', 'Please try again.');
			redirect('/admin/orders/'.$order_id);
			exit;
		}
				
			
		
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
		echo '<input type="hidden" value="true" name="pid" id="pid"  value="0"/> ';
		echo '<input type="hidden" name="printerCommands" id="printerCommands" value="'.$filename.'" /> ';
		
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
		$orderid = ltrim($order_id, '0');		
		
		
		//DemoPrintFileProcess.php is at the same page level as WebClientPrint.php
		$demoPrintFileProcessAbsoluteURL =  $this->data['vars']['site_url'].APPPATH . 'third_party/WebClientPrint/WebClientPrintCommandsProcess.php?order='.$orderid.'&printtype='.$printtype.'&tag_printer='.$this->session->userdata['tag_printer'].'&bill_printer='.$this->session->userdata['bil_printer'];
		

		//echo $demoPrintFileProcessAbsoluteURL."<br>";
		
		$session_id = $this->session->userdata('session_id');
		
		//echo $session_id;
		
		//Specify the ABSOLUTE URL to the WebClientPrintController.php and to the file that will create the ClientPrintJob object
		echo WebClientPrint::createScript($webClientPrintControllerAbsoluteURL, $demoPrintFileProcessAbsoluteURL, $session_id);
		
		 
		 exit;
	}
	
	
	
    /*print intern copi*/
    function __printInternBill($printtype)
    {
		
		$order_id = $this->uri->segment(4);
		$orderinfo = $this->orders_model->getOrderinfo($order_id);
		
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
		}
		
		
		$search = array('Å','å','Æ','æ','Ø','ø','(',')');
		$replace = array('0x5D','0x7D','0x5B','0x7B','0x5C','0x7C','0x28','0x29');
		
		
        //Create ESC/POS commands for sample receipt
        $esc = '0x1B'; //ESC byte in hex notation
        $newLine = '0x0A'; //LF byte in hex notation
		
		
        $cmds = '';
        $cmds .= $esc . "@"; //Initializes the printer (ESC @)
		$cmds .= $esc . '0x52' . '0x09'; //internation charater set ESC R 9
		
		//get orderline
		$orderdetails = $this->orders_model->getOrderLine($order_id,'print');
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
			$is_shirt_boolean = false;
			
			for($i=0;$i< count($orderdetails);$i++)
			{
				$html='';
				$chtml = '';
				$meter_text =0;
				$arr = $this->orders_model->getProductDisplayName($orderdetails[$i]['product']);
				
				$orderdetails[$i]['name'] = $arr['name'];
				
				
				//echo '<pre>';print_r($orderdetails[$i]['p_b_delivery_time']);exit;
				if($orderdetails[$i]['p_b_delivery_time'] != '' && $orderdetails[$i]['p_b_delivery_time'] != '0000-00-00')
				{
					$b_delivery_time=date('Y-m-d',strtotime($orderdetails[$i]['p_b_delivery_time']));
					$p_b_delivery_time = date('d/m/Y',strtotime($orderdetails[$i]['p_b_delivery_time']));
					$day=date('D',strtotime($orderdetails[$i]['p_b_delivery_time']));
					$orderlinedelivery[]= '<span class="pname"> '.$orderdetails[$i]['name'].'</span> '.strtolower($weekdayarray[$day]).' '.$p_b_delivery_time;
					
					 if($is_shirt_boolean){
						$etter = $esc . 'a' . '0x00'; 
						$etter .= $newLine.'Etter kl. 13:00';
						//$etter .= $esc . 'a' . '0x00'; 	
					 }
					
					 $checkdeliverydate[$b_delivery_time]=  strtolower(str_replace($search, $replace, $weekdayarray[$day])).' '.$p_b_delivery_time.$etter;
					
					 $dstr = str_replace($search, $replace, $orderdetails[$i]['name']);
					  
					 $orderlinedelivery1[$i][strtotime($orderdetails[$i]['p_b_delivery_time'])][]=str_replace($search, $replace, $orderdetails[$i]['name']);
					  
					 $orderlinedelivery2[$i][strtotime($orderdetails[$i]['p_b_delivery_time'])][]=strtolower(str_replace($search, $replace, $weekdayarray[$day]));
					  
					 $orderlinedelivery3[$i][strtotime($orderdetails[$i]['p_b_delivery_time'])][]=' '.$p_b_delivery_time.$etter.$newLine;
					  
					  
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
								 
				/*$cmds .= str_pad($quantity, 5);

				$cmds .= str_pad($pname, 27);	*/							 
								 
				
				$chtml .= str_pad($quantity, 5) . str_pad($pname, 25);
				
				
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
			  
				
				$chtml .= $esc . 'a' . '0x02';
				$chtml .= 'kr '.formatcurrency($productPrice).$vary;
				$chtml .= $newLine;
				$chtml .= $esc . 'a' . '0x00';
				
				
				if($orderdetails[$i]['special_instruction']!=''){
					$html .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;font-size: 30px; line-height:32px"><b>Fritekst :</b><br>'.$orderdetails[$i]['special_instruction'].'</p>';
					
					
					$chtml .= 'Fritekst : ';
					$chtml .= $newLine;
					$chtml .= str_replace($search, $replace, $orderdetails[$i]['special_instruction']);				 
					$chtml .= $newLine;
						
				}
				
				if($orderdetails[$i]['complain']==1){
					$html .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;font-size: 30px; line-height:32px"><b>Reklamasjon</b></p>';
					
					
					$chtml .= 'Reklamasjon';
					$chtml .= $newLine;
					
				}
				
				if($orderdetails[$i]['in_house']==1){
					$html .='<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;font-size: 30px; line-height:32px"><b>Renses på huset</b></p>';
					
					$chtml .= str_replace($search, $replace, 'Renses på huset');				 
					$chtml .= $newLine;	
					
				}
					
			  
			  $str.=$html;
			  
			  $categoryorderline[$orderdetails[$i]['category']][$i]['item']= $chtml;
			  
			  $categoryorderline[$orderdetails[$i]['category']][$i]['name']= str_replace($search, $replace, $orderdetails[$i]['name']);		
			  
			  $categoryorderline[$orderdetails[$i]['category']][$i]['day']= strtolower(str_replace($search, $replace, $weekdayarray[$day]));
			  
			  $categoryorderline[$orderdetails[$i]['category']][$i]['date']= $orderdetails[$i]['p_b_delivery_time'];			  
			  
			  
			  if(count($orderdetails) == 1 )
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
				
				$cmds .= $newLine;	
				$cmds .= 'Notater';
				$cmds .= $newLine;	
				$cmds .= str_replace($search, $replace, $this->data['fields']['order']['delivery_note']);
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
				$cmds .= str_replace($search, $replace, $this->data['fields']['order']['special_instruction']);
				$cmds .= $newLine;	
				
			}
			
			
		
			$commonheader = '';
			$commonheader .= $esc . '!' . '0x38'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
			$commonheader .= $esc . 'a' . '0x01';
			$commonheader .= str_replace($search, $replace,$this->data['fields']['branch']['company']); //text to print
			$commonheader .= $newLine ;
			$commonheader .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
			$commonheader .= str_replace($search, $replace, $this->data['fields']['branch']['street']);
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
			
			$commonheader .= $esc . '!' . '0x02'; //Character font A selected (ESC ! 0)
			$commonheader .= $esc . 'a' . '0x00';
			$commonheader .= $this->data['fields']['customer']['number'];
			$commonheader .= $newLine;
			$commonheader .= $esc . '!' . '0x38'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
			$commonheader .= ucwords(str_replace($search, $replace, $this->data['fields']['customer']['customer_name']));
			$commonheader .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
			//$commonheader .= $this->data['fields']['customer']['address'];
			$commonheader .= $newLine;
			//$commonheader .= $this->data['fields']['customer']['zip'].' '.$this->data['fields']['customer']['city'] ;
			//$commonheader .= $newLine;
			$commonheader .= 'Kasserer: '.str_replace($search, $replace, $this->data['fields']['employee']['initial']);
			$commonheader .= $newLine;
			$commonheader .= $this->data['fields']['order']['order_time'];
			$commonheader .= $newLine.$newLine;
			
			
			
		  $kk = '';
		  if($this->data['fields']['customer']['autofil_invoice'] != NULL){
			  
			  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Fakturakunde</td></tr>';
			  
			$cmds .= $newLine;					  
			$cmds .= 'Fakturakunde';
			$cmds .= $newLine;	
		  }
		  else if($saldo_amount != '' && $saldo_amount != '0,00')
		  {
			  $summery.=' <tr><td colspan="3" style="text-align: left; padding: 10px 0 0;; font-family: \'arial\', monospace; font-size: 30px; font-weight:normal ">Kassakredit saldo: kr '.$saldo_amount.'</td></tr>';
			$kk .= $newLine;
			$kk .= $esc . 'a' . '0x00';
			$kk .= 'Kassakredit saldo: kr '.$saldo_amount;
			$kk .= $newLine;	

		  }
		  
			$mt  = str_replace($search, $replace,'* Prisen blir kalkulert når ferdig');
			$mt .= $newLine.$newLine;	
		  
		  
		    $commonfooter ='';
			$commonfooter .= $newLine;	
			$commonfooter .= $esc . 'a' . '0x01';
			$commonfooter .= $esc . '!' . '0x24'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
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
		
		
		if(count($orderdetails) > 0){
			
            if(count($categoryorderline) > 0)
			{	
				$z = 0;
				foreach($categoryorderline as $catkey=>$catitems)
				{
					$antall = 0;
					$amount = 0;
					$in_meter = 0;
					if(count($catitems) > 0)
					{
						
						$categoryprint.= $commonheader;
						$catout='';
						$catdelivery = '';
						$w = 0;
						$weekday = array();
						$weekdate = array();

						foreach($catitems as $key=>$cat_html)
						{
							$catout.= $catitems[$key]['item'];
							$antall += $catitems[$key]['antall'];
							$amount += $catitems[$key]['amount'];
							$in_meter += $catitems[$key]['in_meter'];
							
							if(count($catitems) == 1){
								$catdelivery .= $catitems[$key]['name'].$newLine;
								$catdelivery .= $catitems[$key]['day']." ".date('d/m/Y',strtotime($catitems[$key]['date']));
								break;
							}
							else{
                                if($w != 0){
                                    $day = $catitems[$key]['day'];
                                    $date = strtotime($catitems[$key]['date']);
                                    if(in_array($date, $weekdate)){
										$catdelivery .= $catitems[$key]['name'].$newLine;
										if($w == (count($catitems)-1)){
											$catdelivery .= ucfirst($temp_day)." ".$temp_date;
										}
										$w++;
										$temp_day = $catitems[$key]['day'];
										$temp_date = date('d/m/Y',strtotime($catitems[$key]['date']));
                                        continue;
                                    }
                                    else{
                                        $catdelivery .= ucfirst($temp_day)." ".$temp_date.$newLine.$newLine;
                                    }
                                }
								$catdelivery .= $catitems[$key]['name'].$newLine;
								$weekday[] = $catitems[$key]['day'];
								$weekdate[] = strtotime($catitems[$key]['date']);
								$temp_day = $catitems[$key]['day'];
								$temp_date = date('d/m/Y',strtotime($catitems[$key]['date']));
							}
							
							if($w == (count($catitems)-1)){
								$catdelivery .= ucfirst($temp_day)." ".$temp_date;
							}
							$w++;
						}
					
						$categoryprint.=$catout;
						
						$categoryprint .= $esc . '!' . '0x00';
						$categoryprint .= $newLine;
						$categoryprint .= $esc . 'a' . '0x02';
						$categoryprint .= str_pad('Delsum', 0, "", STR_PAD_LEFT).'         ';
						$categoryprint .= 'kr '.formatcurrency($amount);
						$categoryprint .= $newLine;	
						$categoryprint .= str_pad('Herav 25% MVA', 0, "", STR_PAD_LEFT).'         ';
						$categoryprint .= 'kr '.formatcurrency($cmva);
						$categoryprint .= $newLine;	
						$categoryprint .= str_pad('Totalt', 0, "", STR_PAD_LEFT).'         ';
						$categoryprint .= 'kr '.formatcurrency($amount);
						$categoryprint .= $newLine;
						$categoryprint .= $esc . 'a' . '0x00';
						
						$categoryprint .= $esc . '!' . '0x20'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
						$categoryprint .= $newLine;
						$categoryprint .= $esc . 'a' . '0x00';
						$categoryprint .=' Antall '.$antall;
						$categoryprint .= $newLine;
						
						$categoryprint .= $esc . '!' . '0x00';
						$categoryprint .= $newLine;	
						$categoryprint .= $kk;
						$categoryprint .= $newLine;	
						
						if($in_meter > 0){
							$categoryprint .= $newLine;	
							$categoryprint .= $mt;
						}
						
					   //mva
						$cmva=$amount/1.25;	
						$cmva=round($cmva, 2);
						$cmva=$amount-$cmva;
						
						
						
						$categoryprint .= $esc . '!' . '0x20'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
						$categoryprint .= 'Ferdig: ';
						$categoryprint .= $newLine;	
						$categoryprint .= $catdelivery;
						$categoryprint .= $newLine;
						$categoryprint .= $esc . '!' . '0x00'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
						$categoryprint.= $commonfooter;
						$categoryprint .= $newLine;
						$categoryprint .= $newLine;
						$categoryprint .= $newLine;
						$categoryprint .= $newLine;
						$categoryprint .= $newLine;
						
					}
					
					if( $z < (count($categoryorderline)-1)){
						$categoryprint.=$pagebreak;
					}
					
					$z++;
				
				}
				
			}
			else
			{
				$categoryprint.=$pagebreak;
			}
		}


		$cmds .= $newLine;	
		$cmds = $categoryprint . $cmds;
		
		
		$filename = 'intern_'.$order_id;
		$foldername = PRINT_FILES_FOLDER.$filename.'.SMART';
		
		
		if ( !write_file($foldername,$cmds,'w')){
			$this->session->set_flashdata('notice-error', 'Please try again.');
			redirect('/admin/orders/'.$order_id);
			exit;
		}
		
		
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
		echo '<input type="hidden" value="true" name="pid" id="pid"  value="0"/> ';
		echo '<input type="hidden" name="printerCommands" id="printerCommands" value="'.$filename.'" /> ';
		
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
		$orderid = ltrim($order_id, '0');		
		
		
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
	
	function __testprintBill($printtype)
    {
			
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
			$printerName='EPSON TM-U220B Receipt';
		}
			
		$printerName='EPSON TM-U220 Receipt';
		
		//$printerName='EPSON TM-T88III Receipt';

		//Create ESC/POS commands for sample receipt
		$esc = '0x1B'; //ESC byte in hex notation
		$newLine = '0x0A'; //LF byte in hex notation
		
		
		$cmds = '';
		$cmds = $esc . "@"; //Initializes the printer (ESC @)
		
		$cmds .= $esc . '0x52' . '0x09'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
		$cmds .= $esc . 'a' . '0x00';
	
		
		/*$cmds .=$newLine;
		$cmds .= '0x5D';  //Å
		$cmds .=$newLine;
		$cmds .= '0x7D';  //å
		$cmds .=$newLine;
		$cmds .=$newLine;
		$cmds .= '0x5B'; //Æ
		$cmds .=$newLine;
		$cmds .= '0x7B'; //æ
		$cmds .=$newLine;
		$cmds .= '0x5C'; //Ø
		$cmds .=$newLine;
		$cmds .= '0x7C'; //ø
		$cmds .= $newLine;
		$cmds .= $newLine;
		$cmds .= $newLine;
		$cmds .= $newLine;
		$cmds .= $newLine;
		$cmds .= $newLine;*/
		$cmds .= $esc . '0x52' . '0x09'; //internation charater set ESC R 9

		$search = array('Å','å','Æ','æ','Ø','ø','(',')');
		$replace = array('0x5D','0x7D','0x5B','0x7B','0x5C','0x7C','0x28','0x29');
		
   

		$cmds .= $esc . '!' . '0x18'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
		$cmds .= str_pad('10155334 Vi 1', 20) ;
		$cmds .= $esc . 'a' . '0x02';
		$cmds .= '10155334 Vi 1' ;
		$cmds .= $esc . 'a' . '0x00';
		$cmds .= $newLine;
		$cmds .= str_pad('19:27/02', 14); 
		$cmds .= $esc . '!' . '0x00'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex	
		$cmds .= 'I 20/02' . ' : ' .'Rdy 27/02' ;
		$cmds .= $esc . '!' . '0x18'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
		$cmds .= $newLine;
		$cmds .= str_pad('man:1/4', 14) ;
		$cmds .= $esc . '!' . '0x00'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex	
		$cmds .= str_replace($search, $replace,'Dress'); //text to print
		$cmds .= $newLine;
		$cmds .= $esc . '!' . '0x00'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex	
		$cmds .= 'Customer, shop'.' ' . '97970017';	
		$cmds .= $newLine;
		$cmds .= '0x1D0x560x00';
		
		$cmds .= $esc . '!' . '0x18'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
		$cmds .= str_pad('10155334 Vi 1', 20) ;
		$cmds .= $esc . 'a' . '0x02';
		$cmds .= '10155334 Vi 1' ;
		$cmds .= $esc . 'a' . '0x00';
		$cmds .= $newLine;
		$cmds .= str_pad('19:27/02', 14); 
		$cmds .= $esc . '!' . '0x00'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex	
		$cmds .= 'I 20/02' . ' : ' .'Rdy 27/02' ;
		$cmds .= $esc . '!' . '0x18'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
		$cmds .= $newLine;
		$cmds .= str_pad('man:2/4', 14) ;
		$cmds .= $esc . '!' . '0x00'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex	
		$cmds .= str_replace($search, $replace,'Dress'); //text to print
		$cmds .= $newLine;
		$cmds .= $esc . '!' . '0x00'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex	
		$cmds .= 'Customer, shop'.' ' . '97970017';	
		$cmds .= $newLine;
		$cmds .= '0x1D0x560x00';
		
		$cmds .= $esc . '!' . '0x18'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
		$cmds .= str_pad('10155334 Vi 1', 20) ;
		$cmds .= $esc . 'a' . '0x02';
		$cmds .= '10155334 Vi 1' ;
		$cmds .= $esc . 'a' . '0x00';
		$cmds .= $newLine;
		$cmds .= str_pad('19:27/02', 14); 
		$cmds .= $esc . '!' . '0x00'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex	
		$cmds .= 'I 20/02' . ' : ' .'Rdy 27/02' ;
		$cmds .= $esc . '!' . '0x18'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
		$cmds .= $newLine;
		$cmds .= str_pad('man:3/4', 14) ;
		$cmds .= $esc . '!' . '0x00'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex	
		$cmds .= str_replace($search, $replace,'Dress'); //text to print
		$cmds .= $newLine;
		$cmds .= $esc . '!' . '0x00'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex	
		$cmds .= 'Customer, shop'.' ' . '97970017';	
		$cmds .= $newLine;
		$cmds .= '0x1D0x560x00';
		$cmds .= $newLine;
		
		$cmds .= $esc . '!' . '0x18'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
		$cmds .= str_pad('10155334 Vi 1', 19) ;
		$cmds .= $esc . 'a' . '0x02';
		$cmds .= '10155334 Vi 1' ;
		$cmds .= $esc . 'a' . '0x00';
		$cmds .= $newLine;
		$cmds .= str_pad('19:27/02', 14); 
		$cmds .= $esc . '!' . '0x00'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex	
		$cmds .= 'I 20/02' . ' : ' .'Rdy 27/02' ;
		$cmds .= $esc . '!' . '0x18'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
		$cmds .= $newLine;
		$cmds .= str_pad('man:4/4', 14) ;
		$cmds .= $esc . '!' . '0x00'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex	
		$cmds .= str_replace($search, $replace,'Dress'); //text to print
		$cmds .= $newLine;
		$cmds .= $esc . '!' . '0x00'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex	
		$cmds .= '0x1D0x560x00';
		$cmds .= 'Customer, shop'.' ' . '97970017';	
		$cmds .= $newLine;
		$cmds .= $newLine;
		$cmds .= $newLine;
		$cmds .= $newLine;
		$cmds .= $newLine;
		$cmds .= $newLine;
		$cmds .= $newLine;
		$cmds .= '0x1D0x560x00';
				
		$filename = 'test';
		$foldername = PRINT_FILES_FOLDER.$filename.'.SMART';
		
		
		if ( !write_file($foldername,$cmds,'w')){
			$this->session->set_flashdata('notice-error', 'Please try again.');
			redirect('/admin/');
			exit;
		}
	
		
		//profiling
        $this->data['controller_profiling'][] = __function__;
		echo '<input type="hidden" value="true" name="pid" id="pid"  value="0"/> ';
		echo '<input type="hidden" name="printerCommands" id="printerCommands" value="'.$filename.'" /> ';
		
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
	

}

/* End of file printecspos.php */
/* Location: ./application/controllers/admin/printecspos.php */
