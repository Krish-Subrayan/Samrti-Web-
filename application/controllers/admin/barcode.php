<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class Barcode extends MY_Controller
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

		
		if(!isset($this->session->userdata['start_current_staff']))
		{
			$this->session->set_flashdata('notice-error', $this->data['lang']['lang_session_timed_out']);
			redirect('/admin/');
			exit();
		}
		
        //get the action from url
        $bar = $this->uri->segment(3);
		$this->set_barcode($bar);
	
		
    }
	
	private function set_barcode($code)
	{
		//load library
		$this->load->library('zend');
		//load in folder Zend
		$this->zend->load('Zend/Barcode');
		//generate barcode
		Zend_Barcode::render('Code25interleaved', 'image', array('text'=>$code), array());
	}
	
}
/* End of file orders.php */
/* Location: ./application/controllers/admin/orders.php */
