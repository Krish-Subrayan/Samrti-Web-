<?php
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all bugs related data abstraction
 *
 * @author   smart-ipos.no
 * @access   public
 * @see      http://smart-ipos.no
 */
class Smart_laundry_model extends Super_Model
{

    var $debug_methods_trail;
    var $number_of_rows;

    // -- __construct ----------------------------------------------------------------------------------------------
    function __construct()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        // Call the Model constructor
        parent::__construct();
    }
	
	
    // -- getTimeSlots ----------------------------------------------------------------------------------------------
    /**
     * return array of all the rows of a date in table
     * accepts order_by and asc/desc values
     *
     * @return	array
     */

    function getTimeSlots($type='',$date='')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = " AND c.status='1'";
		

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____ACTUAL QUERY_______
		$current_partner_branch=$this->session->userdata['partner_branch'];

        $query = $this->db->query("SELECT id,TIME_FORMAT(start_time, '%H:%i') as stime,TIME_FORMAT(end_time, '%H:%i') as etime
                                          FROM a_collection_time as c
										  WHERE 1=1 $conditional_sql
										  AND DATE(start_time) = '$date'
										  AND (type = '$type'  OR type='all')
                                          ORDER BY c.id ASC");


		$results = $query->result_array(); //multi row array
		

        //----------monitor transaction end----------
        $this->db->trans_complete();
        $transaction_result = $this->db->trans_status();
        if ($transaction_result === false) {

            //log this error
            $db_error = $this->db->_error_message();
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Database Error -  $db_error]");

            return false;
        }

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;

    }
	
 	/*Get time slots dropdown*/    
     function getTimeSlotsHTML($data){
         $html = '<option value="">-- Velg --</option>';
         if(count($data) > 0){
                 for ($i = 0; $i < count($data); $i++) {
 					$select = ($selected == $data[$i]['id']) ?   "selected":  '';
 					$html.= "<option ".$select." value='" . $data[$i]['id'] ."'>" . $data[$i]['stime'] .' - '. $data[$i]['etime'] . "</option>";
                 }
             $html.= "";
         }
             return $html;
     }	
	
	

}

/* End of file smart_laundry_model.php */
/* Location: ./application/models/smart_laundry_model.php */
