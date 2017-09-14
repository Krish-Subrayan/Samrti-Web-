<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class Infile extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'settings.infile.html';

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
        $action = $this->uri->segment(4);


        //route the rrequest
        switch ($action) {
          
		  case 'update-in-status':
                $this->__updateInstatus();
                break;
				
		   case 'download-in-file':
                $this->__downloadInFile();
                break;
				
			case 'create-infile':
				 $this->__createInfile();
				
				
            default:				
                break;
        }

        //load view
        $this->__flmView('admin/main');

    }
	
	

    /**
     *download in file
     */
    function __downloadInFile()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;
        //flow control
        $next = true;
        //load helper
        $this->load->helper('download');

        //load settings
        if ($next) {

            //get the current data
            //$filename = $this->process_order_model->downloadInfile();
            $filename = $this->process_order_model->createInfiledownload();
		   
		   $download_path ='sorting/branch/';
		   
			if($filename){
		
				$data = file_get_contents($download_path.$filename); // Read the file's contents
				$name = $filename;
				force_download($name, $data);
				$result = array("status"=>'success');
				echo json_encode($result);exit;
			}		   
			 else {
				//show error
				$this->notifications('wi_tabs_notification', $this->data['lang']['lang_request_could_not_be_completed']);
				$result = array("message"=>$this->data['lang']['lang_request_could_not_be_completed']);
				echo json_encode($result);exit;
	
			}

        }

    }

   
   function __updateInstatus()
   {
		$filename = $this->process_order_model->updateInstatus();
		echo 'success';exit;
   }
   

	/*create and download infile for an order*/
	function __createInfile()
	{
		$this->process_order_model->createDeleteInfile();
		exit;
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

}

/* End of file infile.php */
/* Location: ./application/controllers/admin/infile.php */
