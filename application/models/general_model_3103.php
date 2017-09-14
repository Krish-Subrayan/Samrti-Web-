<?php
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/**
 * class for perfoming all bugs related data abstraction
 *
 * @author   smart-laundry.no
 * @access   public
 * @see      http://smart-laundry.no
 */
class General_model extends Super_Model
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
	
    // -- getAvailableArea ----------------------------------------------------------------------------------------------
    /**
     * return array of all the rows of avalibale area in table
     * accepts order_by and asc/desc values
     *
     * @return	array
     */
    function getAvailableArea($zip='')
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;
        //declare
        $conditional_sql = " AND z.id=$zip AND a.status='1' AND z.zone=a.zone AND zn.id=z.zone AND zn.status='1' AND z.city=c.id";
		
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
        //----------monitor transaction start----------
        $this->db->trans_start();
        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT z.id,z.zone,zn.name,zn.description,c.city
                                          FROM  a_zip as z,a_available_area a,a_zone as zn,a_city as c
										  WHERE 1=1 $conditional_sql
                                          ORDER BY id ASC");
        $results = $query->row_array(); //multi row array
		
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
    
    // -- isValidZip ----------------------------------------------------------------------------------------------
    /**
     * return true if the zip is valid
     * accepts order_by and asc/desc values
     *
     * @return	array
     */
    function isValidZip($zip='')
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;
        //declare
        $conditional_sql = " AND id='$zip'";
		
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
        //----------monitor transaction start----------
        $this->db->trans_start();
        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT id
                                          FROM a_zip
										  WHERE 1=1 $conditional_sql
                                          ORDER BY id ASC");
        $results = $query->num_rows();
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
	
	
    // -- getMinCharge ----------------------------------------------------------------------------------------------
    /**
     * return array of all the rows of language in table
     * accepts order_by and asc/desc values
     *
     * @return	array
     */
    function getMinCharge()
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;
        //declare
        $conditional_sql = " AND zone='1'";
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
        //----------monitor transaction start----------
        $this->db->trans_start();
        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT normal_min_price
                                          FROM a_zone_additional_info
										  WHERE 1=1 $conditional_sql
                                          ORDER BY id ASC");
        $results = $query->row_array(); //multi row array
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
	
	
	 // get zone additional information
     /**
     * @return	array
     */
	function getZoneinfo($zone)
	{
		//profiling::
        $this->debug_methods_trail[] = __function__;
        //declare
        $conditional_sql = " AND zone='".$zone."'";
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
        //----------monitor transaction start----------
        $this->db->trans_start();
        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM a_zone_additional_info
										  WHERE 1=1 $conditional_sql
                                          ORDER BY id ASC");
        $results = $query->row_array(); //multi row array
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
	
	// -- getPartnerDetails ----------------------------------------------------------------------------------------------
	  /**
	   * return array of all the rows of orders in table
	   * accepts order_by and asc/desc values
	   * 
	   * @param $orderby sorting
	   * @param $sort sort order
	   * @return	array
	   */
	  function getPartnerDetails($partner,$partner_branch)
	  {
		//profiling::
		  $this->debug_methods_trail[] = __function__;
  
		  //declare
		  $conditional_sql = "";
		  $additional_sql = "";
		  
  
		  //----------sql & benchmarking start----------
		  $this->benchmark->mark('code_start');
  
		  //_____SQL QUERY_______
		  //$query = $this->db->query("SELECT * FROM  a_partner WHERE id='".$partner."'");
		  
		  $query = $this->db->query("SELECT a_partner_branch.*, p.name as partner_name,p.phone as partner_phone,p.email as partner_email,p.vat_nr as partner_vat_nr,a_city.city 
		  									FROM a_partner_branch
                                            LEFT OUTER JOIN a_partner as p
                                            ON p.id = a_partner_branch.partner AND p.status ='1' 
                                            LEFT JOIN a_zip
                                            ON a_zip.id = a_partner_branch.zip
                                            LEFT JOIN a_city
                                            ON a_zip.city = a_city.id
											WHERE a_partner_branch.id='".$partner_branch."' AND partner='".$partner."'");
		  
		  $results = $query->row_array(); //multi row array
  
		  //benchmark/debug
		  $this->benchmark->mark('code_end');
		  $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');
  
		  //debugging data
		  $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
		  //----------sql & benchmarking end----------
  
		  //return results
		  return $results;
	  }
		
	
    // -- checkMinimumAmount ----------------------------------------------------------------------------------------------
    /**
     * return array of a row
     * accepts order_by and asc/desc values
     *
     * @return	array
     */
    function checkMinimumAmount($type='',$customer='',$amount='',$zone='')
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;
		$tbl ='';
		
		
		//declare
		$conditional_sql = " AND zone='$zone'";
			
		if($type=='express')
			$conditional_sql .= " AND express_delivery_available='1'";
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
        //----------monitor transaction start----------
        $this->db->trans_start();
        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT ROUND(".$type."_min_price, 0) as min_price, ROUND(".$type."_delivery_charge, 0) as delivery_charge,ROUND(".$type."_free_delivery_after, 0) as free_delivery_after,".$type."_min_delivery_hours as min_delivery_hours
                                          FROM  a_zone_additional_info as z
										  WHERE 1=1 $conditional_sql
                                          ");
        $results = $query->row_array(); //multi row array
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
	
    // -- updateFieldValue ----------------------------------------------------------------------------------------------
    /**
     * update field value
     * @param numeric $project_id
     * @param numeric $progress
     * @return bool
     */
    function updateFieldValue($id='',$field='',$value='',$table='')
    {
		
        //profiling::
        $this->debug_methods_trail[] = __function__;
        //validate
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE $table
                                          SET 
                                          $field = '$value'
                                          WHERE id = '$id'");
										  
        $results = $this->db->affected_rows(); //affected rows
        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end'); //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results); //----------sql & benchmarking end----------
        //---return
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }
    }

// -- getVoucherType ----------------------------------------------------------------------------------------------

    /**

     * return array of all the rows of Voucher in table

     * accepts order_by and asc/desc values

     *

     * @return	array

     */

    function getVoucherType($voucher='',$customer='',$amount)

    {

        //profiling::

        $this->debug_methods_trail[] = __function__;

		$tbl ='';

		

		//declare

		$conditional_sql = " AND v.voucher='$voucher' AND v.status='1' AND startdate <= NOW() AND  NOW() <=  enddate AND v.min_amount <= $amount";

	

        //----------sql & benchmarking start----------

        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------

        $this->db->trans_start();

        //_____ACTUAL QUERY_______


        $query = $this->db->query("SELECT v.id,name,description,price,percentage,free_delivery_charge,min_amount,v.type

                                          FROM  a_voucher as v $tbl

										  WHERE 1=1 $conditional_sql

                                          ");

		/*echo 	"SELECT v.id,name,description,price,percentage,free_delivery_charge,min_amount,v.type

                                          FROM  a_voucher as v $tbl

										  WHERE 1=1 $conditional_sql

                                          ";*/							  

        $results = $query->row_array(); //multi row array

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
	
	 // -- getVoucherInfo ----------------------------------------------------------------------------------------------

    /**

     * return array of all the rows of Voucher in table

     * accepts order_by and asc/desc values

     *

     * @return	array

     */

    function getVoucherInfo($voucher='',$customer='',$amount,$type='normal')

    {

        //profiling::

        $this->debug_methods_trail[] = __function__;

		$tbl ='';

		

		if($type != 'normal'){

			//declare

			$conditional_sql = " AND v.voucher='$voucher' AND v.status='1' AND startdate <= NOW() AND  NOW() <=  enddate AND v.min_amount <= $amount AND v.type='$type'";

			

		}

		else{

		

        //declare

        $conditional_sql = " AND v.voucher='$voucher' AND v.status='1' AND startdate <= NOW() AND  NOW() <=  enddate  AND vc.voucher = v.id AND vc.customer= $customer AND vc.status='proceed' AND v.min_amount <= $amount AND v.type='$type'";

		$tbl = ',a_voucher_customer vc';

		}

		

        //----------sql & benchmarking start----------

        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------

        $this->db->trans_start();

        //_____ACTUAL QUERY_______

        $query = $this->db->query("SELECT v.id,name,description,price,percentage,free_delivery_charge,min_amount

                                          FROM  a_voucher as v $tbl

										  WHERE 1=1 $conditional_sql

                                          ");

        $results = $query->row_array(); //multi row array

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

	
	function validateVoucher($voucher,$customer)

	{

		$qry="SELECT * FROM a_voucher_customer WHERE voucher='".$voucher."' AND customer='".$customer."' AND status='used'";

		$query = $this->db->query($qry);

		if($query->num_rows() > 0)

		{
			
			return true;

		}

		else

		{

			return false;

		}

		

	}
	
	

	function stafflogin($staff)
	{
		
		$current_partner=$this->session->userdata['partner'];
		$current_partner_branch=$this->session->userdata['partner_branch'];
		
		$qry="SELECT a_employee.*,a_employee_p_branch.id as employee_p_branch ,a_employee_p_branch.employee_type as employee_type 
		FROM a_employee 
		LEFT JOIN a_employee_p_branch ON a_employee_p_branch.employee=a_employee.id
		WHERE a_employee.id='".$staff."'  AND a_employee.status='1' AND a_employee_p_branch.partner_branch='".$current_partner_branch."'";
		
		$query = $this->db->query($qry);
		if($query->num_rows() > 0)
		{
			$results = $query->row_array(); //multi row array
			return $results;
		}
		else
		{
			return false;
		}
	}

 /**

	   * return array of all the rows of orders in table

	   * accepts order_by and asc/desc values

	   * 

	   * @param $orderby sorting

	   * @param $sort sort order

	   * @return	array

	   */

  

	  function getCompanyDetails()

	  {

  

		  //profiling::

		  $this->debug_methods_trail[] = __function__;

  

		  //declare

		  $conditional_sql = "";

		  $additional_sql = "";

		  

  

		  //----------sql & benchmarking start----------

		  $this->benchmark->mark('code_start');

  

		  //_____SQL QUERY_______

		  $query = $this->db->query("SELECT * FROM  settings_company");

										   

		  $results = $query->row_array(); //multi row array

  

		  //benchmark/debug

		  $this->benchmark->mark('code_end');

		  $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

  

		  //debugging data

		  $this->__debugging(__line__, __function__, $execution_time, __class__, $results);

		  //----------sql & benchmarking end----------

  

		  //return results

		  return $results;

  

	  }
	  
	  function checkVoucherused($voucher,$customer,$amount)

	{

		//profiling::

        $this->debug_methods_trail[] = __function__;

        //declare

        $conditional_sql = "WHERE friend='".$customer."'";

		//----------sql & benchmarking start----------

        $this->benchmark->mark('code_start');

		//----------monitor transaction start----------

        $this->db->trans_start();

		

		//_____ACTUAL QUERY_______

        $query = $this->db->query("SELECT *

                                          FROM a_voucher_customer

										  $conditional_sql

											ORDER BY id ASC");

        $results = $query->row_array(); //multi row array

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

         if (count($results) > 0) {

		

           return true;

        } else {

          return false;

        }

	}
	
	function branchstafflist($current_partner_branch)
	{
		$qry="SELECT a_employee.*,a_employee_p_branch.id as employee_p_branch 
		FROM a_employee 
		LEFT JOIN a_employee_p_branch ON a_employee_p_branch.employee=a_employee.id
		WHERE a_employee.status='1' AND a_employee_p_branch.partner_branch='".$current_partner_branch."'";
		$query = $this->db->query($qry);
		if($query->num_rows() > 0)
		{
			$results = $query->result_array();//multi row array
			return $results;
		}
		else
		{
			return false;
		}
	}
	
	/*Trim text*/
	function trim_text($input, $length=15, $ellipses = true, $strip_html = true) {
		//strip tags, if desired
		if ($strip_html) {
			$input = strip_tags($input);
		}
	  
		//no need to trim, already shorter than trim length
		if (strlen($input) <= $length) {
			return $input;
		}
	  
		//find last space within length
		$last_space = strrpos(substr($input, 0, $length), ' ');
		$trimmed_text = substr($input, 0, $last_space);
	  
		//add ellipses (...)
		if ($ellipses) {
			$trimmed_text .= '...';
		}
	  
		return $trimmed_text;
	}	
	
	
	//get in types in norwegiean
    function getInType($type = '')
    {
		
        switch ($type) {
			
			case 'visa':
			    $str = 'BK';
                break;
			case 'invoice':
			    $str = 'FA';
                break;
			case 'gift_card':
			    $str = 'GC';
                break;
			case 'cash':
			    $str = 'KO';
                break;
			case 'account':
			    $str = 'KK';
                break;
			case '':
			    $str = 'KK';
                break;
            default:
			    $str = 'BK';
                break;
				
		}
		
		return $str;
				
	}
	

    // -- getSalesInfo ----------------------------------------------------------------------------------------------
    /**
     * return sales report of a day
     * accepts order_by and asc/desc values
     *
     * @return	array
     */
    function getSalesInfo($date='')
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;
        //declare
        $conditional_sql = " AND date='$date'";
		
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
        //----------monitor transaction start----------
        $this->db->trans_start();
        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM a_daily_cash_report
										  WHERE 1=1 $conditional_sql
                                          ");
										  
        $results = $query->row_array(); //multi row array
		
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
	
	
	function updateCashReport($casharray,$date)
	{
	
		$date = date('Y-m-d',strtotime($date));
		$query = $this->db->query("SELECT *  FROM a_daily_cash_report  WHERE `date`='".$date."'");
		
		
		if($query->num_rows() == 0)
		{
			$qry="INSERT INTO a_daily_cash_report SET
			partner_branch='".$this->session->userdata['partner_branch']."',
			`date`='".$date."',
			employee_p_branch='".$this->session->userdata('employee_p_branch')."',
			bk='".$casharray['bk']."',
			bk_total='".$casharray['bk_total']."',
			ko='".$casharray['ko']."',
			ko_total='".$casharray['ko_total']."',
			gk='".$casharray['gk']."',
			gk_total='".$casharray['gk_total']."',
			fa='".$casharray['fa']."',
			fa_total='".$casharray['fa_total']."',
			kk='".$casharray['kk']."',
			kk_total='".$casharray['kk_total']."',
			total_transaction='".$casharray['total_transaction']."',
			start_amount='".$casharray['start_amount']."',
			tax='".$casharray['tax']."',
			total='".$casharray['total']."',
			pdf_url='".$casharray['pdf_url']."',
			print_time='".date('Y-m-d H:i:s')."'";
			$result=$this->db->query($qry);
			return $this->db->insert_id();
		}
		else
		{
			return false;
		}
										  
				
		
	}
	
	

}
/* End of file general_model.php */
/* Location: ./application/models/general_model.php */
