<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH.'/libraries/REST_Controller.php';

class Service extends REST_Controller
{
	function parseoutfile_post()
	{				
					
		$data=trim($_POST['data']);
		$response = array('status' => "Success","msg" => urldecode($data));				
		$this->response($response, 200); 
		
	}
	function renameinfile_post()
	{
		if(isset($_POST['file']))
		{
				$filename=$_POST['file'];
				$newfilename=time()."_".$filename;
				$newfilepath="sorting/".$newfilename;
				rename("sorting/".$filename."", $newfilepath);
				$srcfile='sorting/'.$newfilename;
				$dstfile='sorting/completed/'.$newfilename;
				copy($srcfile, $dstfile);
				unlink($srcfile);
				$response = array('status' => "Success","msg" => "Vellykket.");
				$this->response($response, 200);
		}
		else
		{	
				$response = array('status' => "error","msg" => "Invalid.");
				$this->response($response, 400);
		}
	
	}
}


/* End of file service.php */
/* Location: ./application/controllers/admin/service.php */
