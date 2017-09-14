<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Report extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'settings.report.html';

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
        $this->data['vars']['css_menu_report'] = 'current'; //menu
		$this->data['vars']['css_settings_menu_daily'] = 'selected'; //menu

        
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
            case 'edit':
                $this->__editSettings();
                break;

            case 'view':
                $this->__viewSettings();
                break;

            default:
                $this->__viewSettings();
        }

        //css - active tab
        $this->data['vars']['css_active_tab_company'] = 'tab-active';

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * load general settings
     *
     * 
     * 
     * 
     */
    function __viewSettings()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;
		
        //PERMISSIONS CHECK
        //load settings
        if ($next) {
			
			//$date  = $this->uri->segment(4);
			$date  = date('d.m.Y');
			//$date  = '12.05.2017';
			
	
			$this->data['vars']['from'] = ($date=='') ? date('d.m.Y'): date('d.m.Y',strtotime($date));
			$this->data['vars']['to'] = ($date=='') ? date('d.m.Y'): date('d.m.Y',strtotime($date));
			
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
				$subarray=array_sort_by_column($newarray, 'modtime');
				
			
				$this->data['blocks']['account']=$subarray;
				
				$this->data['debug'][] = $this->customer_model->debug_data;
					
					$bankarray=array();
					//set invoice name
					 if (!empty($this->data['blocks']['account'])) {
						 $str = '';
						 $total = 0;
						 $trans_count  = 0;
						 
							$this->data['visible']['report']=1;
						 
						 //print_r($this->data['blocks']['account']);
						 
						for ($i=0; $i < count($this->data['blocks']['account']); $i++) {
		
						 $orderid = ($this->data['blocks']['account'][$i]['order']!='')  ? ''.$this->data['blocks']['account'][$i]['order'] :  '';		
						 
						$in_type  = ($this->data['blocks']['account'][$i]['in_type'] =='gift_card') ? "Gift Card" :  $this->data['blocks']['account'][$i]['in_type'];
						
						$in_type  = $this->general_model->getInType($this->data['blocks']['account'][$i]['in_type']);
						
						$bankarray[$in_type][]=$this->data['blocks']['account'][$i]['amount'];
						
						if(is_numeric($orderid)) {
							$orderid = sprintf("%08d", $orderid);
						}
						
						

                       $str .=' <div class="orderlisting row">
                              <div class="col-md-1 black-text text-center ">'.$this->data['blocks']['account'][$i]['rdatewy'].'</div>
                              <div class="col-md-1 black-text">'.$this->data['blocks']['account'][$i]['rtime'].'</div>
                              <div class="col-md-5 black-text text-left "> '.$this->data['blocks']['account'][$i]['customername'].' </div>
                              <div class="col-md-1 black-text no-padd text-left "> '. $orderid.' </div>
                              <div class="col-md-1 black-text text-right ">'.strtoupper($in_type).'</div>
                              <div class="col-md-3 text-right black-text ">kr '.formatcurrency($this->data['blocks']['account'][$i]['amount']).'</div>
                        </div>';           
							  
							if($in_type != 'KK')
							{  
								$total +=	$this->data['blocks']['account'][$i]['amount']; 
								$trans_count ++;
							}
		
						}
						
						
				$footerarray=array('BK'=>'Bankkort (BK)','KO'=>'Kontant (KO)','FA'=>'Faktura (FA)','GK'=>'Gavekort (GK)','KK'=>'Kasse kredit (KK)');
				
				$footerarray1=array('BK'=>'bk','KO'=>'ko','FA'=>'fa','Gk'=>'gk','KK'=>'kk');
				
				$footer='';
				$totcount=0;
				$totamt=array();
				$casharray=array();
				
				
				foreach($footerarray as $fkey=>$foot)
				{
					$tot_amt=array_sum($bankarray[$fkey]);
					if($fkey != 'KK')
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
					
                     $footer.='<div class="orderlisting row">
                              <div class="col-md-6 black-text text-left">'.$foot.'</div>
                              <div class="col-md-3 black-text text-center "> '.count($bankarray[$fkey]).'</div>
                              <div class="col-md-3 text-right black-text ">'.$amt.'</div>
                        </div>';  
					
					
					/*$footer.='<tr>
					<td valign="top" align="right" style="text-align: left;font-family: \'arial\', monospace; font-size:20px; font-weight:normal;padding: 5px 0;">'.$foot.'</td>
					<td style="text-align: center;  font-family: \'arial\', monospace; font-size: 20px; width:20%;"> '.count($bankarray[$fkey]).'
					</td>                
					<td style="text-align: right;font-family: \'arial\', monospace; font-size: 20px; width:20%;">  '.$amt.'</td></tr>';*/
					
					$totcount=$totcount+count($bankarray[$fkey]);
					
				}
				
				
				
				$tott_amount=array_sum($totamt);
				$total_amount=' kr '.formatcurrency($tott_amount);
				
				$delsumamt=$tott_amount/1.25;	
				$delsumamt=round($delsumamt, 2);
				$delsumvat=$tott_amount-$delsumamt;
				
				$vat_amount=' kr '.formatcurrency($delsumvat);
				
				$casharray['tax']=$delsumvat;
				$casharray['total_transaction']=$trans_count ;
				$casharray['total']=$tott_amount;
				
				
				$str.='<div class="orderlisting row">
					  <div class="col-md-9 black-text  text-left bold">Summer for åpne/lukkede perioder</div>
					  <div class="col-md-3 text-right black-text bold">'.$total_amount.'</div>
				</div>
				<div class="orderlisting row">
					  <div class="col-md-9 black-text  text-left bold">Sum transaksjoner</div>
					  <div class="col-md-3 text-right black-text bold">'.$trans_count.'</div>
				</div>
				<div class="orderlisting row">
					  <div class="col-md-9 black-text  text-left bold">Startbeløp i kassen</div>
					  <div class="col-md-3 text-right black-text bold">kr 1.000,00</div>
				</div>';
				
				
				
				$footer.='<div class="orderlisting row">
					  <div class="col-md-6 black-text text-left bold">Total</div>
					  <div class="col-md-3 black-text text-center bold">'.$trans_count.'</div>
					  <div class="col-md-3 text-right black-text bold">'.$total_amount.' </div>
				</div>  
				<div class="orderlisting row">
					  <div class="col-md-6 black-text text-left bold"><i>MVA</i></div>
					  <div class="col-md-3 black-text text-center "></div>
					  <div class="col-md-3 text-right black-text bold">'.$vat_amount.'</div>
				</div>';  
				
				
				/*$footer.='<tr>
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
            </tr>';*/
			
			
				  $this->data['lists']['total'] =' kr '.formatcurrency($total);
				  $this->data['lists']['transaction'] = $trans_count;
				  
				  $this->data['lists']['report'] = $str;
				  $this->data['lists']['footer'] = $footer;
				  
				  
						  
		    } 			

        }

    }

    /**
     * edit settings
     *
     * 
     * 
     * 
     */
    function __editSettings()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //check if any post data (avoid direct url access)
        if (!isset($_POST['submit'])) {
            redirect('/admin/settings/company/view');
        }

        //validate form & display any errors
        if ($next) {
            if (!$this->__flmFormValidation('edit_settings')) {

                //show error
                $this->notices('error', $this->form_processor->error_message);
                $next = false;
            }
        }

        //edit settings
        if ($next) {
            $result = $this->settings_company_model->editSettings();
            
            if ($result) {
                //show success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);
            } else {
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);
            }
        }

        //show task page
        $this->__viewSettings();
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
