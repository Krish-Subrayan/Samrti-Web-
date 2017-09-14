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

            default:
                $this->__report();
                break;
        }


        //load view
        if ($action != 'pdf') {
            $this->__flmView('admin/main');
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
		
		$this->data['vars']['from'] = ($this->input->post('from')=='') ? date('d.m.Y'): date('d.m.Y',strtotime($this->input->post('from')));
		
		$this->data['vars']['to'] = ($this->input->post('to')=='') ? date('d.m.Y'): date('d.m.Y',strtotime($this->input->post('to')));

		
        //check if invoice exists
        if ($next) {
		
		  
		//get results for orders in process (orders which are placed , in process)
		$this->data['reg_blocks'][] = 'account';
		$this->data['blocks']['account'] = $this->customer_model->getCustomerAccountLog();
		$this->data['debug'][] = $this->customer_model->debug_data;
			
			
            //set invoice name
             if (!empty($this->data['blocks']['account'])) {
				 $str = '';
				 $total = 0;
				for ($i=0; $i < count($this->data['blocks']['account']); $i++) {

				 $orderid = ($this->data['blocks']['account'][$i]['order']!='')  ? ''.$this->data['blocks']['account'][$i]['order'] :  '';		
				 
			  	$in_type  = ($this->data['blocks']['account'][$i]['in_type'] =='gift_card') ? "Gift Card" :  $this->data['blocks']['account'][$i]['in_type'];
				
				$in_type  = $this->__getInType($this->data['blocks']['account'][$i]['in_type']);
			  					
				$str .='<tr>
						<td nowrap="nowrap" style="text-align: left;width:30%; padding: 5px 0 5px; font-family: \'arial\', monospace; font-size: 13px; vertical-align: top;">'.$this->data['blocks']['account'][$i]['rdatewy'].'</td>
						<td style="text-align: left; padding: 5px 0 0;width:15%; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top; ">'.$this->data['blocks']['account'][$i]['rtime'].'</td>
						<td style="text-align: center; padding: 5px 0 0;width:25%; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top; ">'. $orderid.'</td>
						<td style="text-align: center; padding: 5px 0 0;width:3%; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top;vertical-align: top; ">'.ucfirst($in_type).'</td>
						<td nowrap="nowrap" style="text-align: right; padding: 5px 0 0; font-family: \'arial\', monospace; font-size: 13px; font-weight:normal;vertical-align: top;">kr '.formatcurrency($this->data['blocks']['account'][$i]['amount']).'</td>
					  </tr>';
					  
				 $total +=	$this->data['blocks']['account'][$i]['amount']; 

				}
				
				  $this->data['lists']['total'] =' kr '.formatcurrency($total);
				  $this->data['lists']['transaction'] = count($this->data['blocks']['account']);
				  
				  $this->data['lists']['report'] = $str;
				  $filename = 'Report_'.date('d.m.Y').'.pdf';
				  
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

}

/* End of file invoice.php */
/* Location: ./application/controllers/admin/invoice.php */
