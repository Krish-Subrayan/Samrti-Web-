<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}


class Printecspos extends MY_Controller
{

    /**
     * constructor method
     */
    public function __construct()
    {

        parent::__construct();

        //profiling::
        //$this->data['controller_profiling'][] = __function__;
		
		//  Path to WebClientPrint
		//include APPPATH . 'third_party/WebClientPrint/WebClientPrint.php';		

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
				 
            default:
                $this->__page();
                break;
				
				
        }

        //load view
		$this->__flmView('admin/main');
    }
	
	
	/*save image from html*/
    function __saveImage()
    {
		
		 //profiling
			$orderid=$_POST['order'];
			$name=$_POST['id'];
			$this->data['controller_profiling'][] = __function__;
			//just a random name for the image file
			$random = rand(100, 1000);
			$imagedata=str_replace('[removed]','data:image/png;base64,',$_POST['data']);
			$imagedata=str_replace('[removed ','data:image/png;base64,',$imagedata);
			
			//$_POST[data][1] has the base64 encrypted binary codes. 
			//convert the binary to image using file_put_contents
			
			if (!is_dir(PATHS_PRINT_IMAGES.$orderid)) {
				mkdir(PATHS_PRINT_IMAGES.$orderid, 0777, TRUE);

			}


			$savefile = @file_put_contents(PATHS_PRINT_IMAGES.$orderid.'/'."$name.png", base64_decode(explode(",", $imagedata)[1]));
			//if the file saved properly, print the file name
			if($savefile){
				echo $name;exit;
			}
	}
	
	
    function __page()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;
	
		/*use Neodynamic\SDK\Web\WebClientPrint;
		use Neodynamic\SDK\Web\DefaultPrinter;
		use Neodynamic\SDK\Web\InstalledPrinter;
		use Neodynamic\SDK\Web\ClientPrintJob;*/
		
		// Process request
		// Generate ClientPrintJob? only if clientPrint param is in the query string
		$urlParts = parse_url($_SERVER['REQUEST_URI']);
		
		if (isset($urlParts['query'])) {
			$rawQuery = $urlParts['query'];
			parse_str($rawQuery, $qs);
			if (isset($qs[WebClientPrint::CLIENT_PRINT_JOB])) {
		
				$useDefaultPrinter = ($qs['useDefaultPrinter'] === 'checked');
				$printerName = urldecode($qs['printerName']);
		
				//Create ESC/POS commands for sample receipt
				$esc = '0x1B'; //ESC byte in hex notation
				$newLine = '0x0A'; //LF byte in hex notation
				
				$cmds = '';
				$cmds = $esc . "@"; //Initializes the printer (ESC @)
				$cmds .= $esc . '!' . '0x38'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
				$cmds .= 'BEST DEAL STORES'; //text to print
				$cmds .= $newLine . $newLine;
				$cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
				$cmds .= 'COOKIES                   5.00'; 
				$cmds .= $newLine;
				$cmds .= 'MILK 65 Fl oz             3.78';
				$cmds .= $newLine . $newLine;
				$cmds .= 'SUBTOTAL                  8.78';
				$cmds .= $newLine;
				$cmds .= 'TAX 5%                    0.44';
				$cmds .= $newLine;
				$cmds .= 'TOTAL                     9.22';
				$cmds .= $newLine;
				$cmds .= 'CASH TEND                10.00';
				$cmds .= $newLine;
				$cmds .= 'CASH DUE                  0.78';
				$cmds .= $newLine . $newLine;
				$cmds .= $esc . '!' . '0x18'; //Emphasized + Double-height mode selected (ESC ! (16 + 8)) 24 dec => 18 hex
				$cmds .= '# ITEMS SOLD 2';
				$cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
				$cmds .= $newLine . $newLine;
				$cmds .= '11/03/13  19:53:17';
		
				//Create a ClientPrintJob obj that will be processed at the client side by the WCPP
				$cpj = new ClientPrintJob();
				//set ESCPOS commands to print...
				$cpj->printerCommands = $cmds;
				$cpj->formatHexValues = true;
				
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
