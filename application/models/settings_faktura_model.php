<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Settings_faktura_model extends Super_Model
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


		/* Get  faktura history in a day*/
		function getFakturaList($status='')
		{
			$conditional_sql ="";
			
			//echo $this->session->userdata['logged_in'];

			$current_partner = $this->session->userdata['partner'];
			$current_partner_branch=$this->session->userdata['partner_branch'];
			
			//$conditional_sql .=" AND a.partner = '".$current_partner."' AND a.partner_branch = '".$current_partner_branch."'";
			
			$conditional_sql .=" AND a.partner = '".$current_partner."'";
			//$conditional_sql .=" AND a.in_type = 'invoice' AND in_status='".$status."' AND a.type='in'";
			 
			$conditional_sql .=" AND a.in_type = 'invoice' AND in_status='".$status."'";
			
			
			$field = ($status=='pending') ? 'regtime' : 'modtime';
			
			

			if (($this->input->get('from')!='Fra') && ($this->input->get('from')!='')) {
				$from = $this->db->escape(date('Y-m-d',strtotime($this->input->get('from'))));
				$conditional_sql .= " AND DATE(a.".$field.") >= $from";
			}
			if (($this->input->get('to')!='Til') && ($this->input->get('to')!='')) {
				
				$to = $this->db->escape(date('Y-m-d',strtotime($this->input->get('to'))));
				$conditional_sql .= " AND DATE(a.".$field.") <= $to";
			}
			
			if(($this->input->get('from')=='') || ($this->input->get('to')=='')){
				//$conditional_sql .= " AND DATE(a.regtime) = '".date('Y-m-d')."'";
			}
			
			if ($this->input->get('autofil')) {
				$autofil = $this->db->escape($this->input->get('autofil'));
				$conditional_sql .= " AND ap.autofil_invoice='".$autofil."'";
			}
			else{
				$conditional_sql .= " AND ap.autofil_invoice IS NULL";   //non autofil customer
			}
			
			
			if ($this->input->get('branch')) {
				$branch = $this->db->escape($this->input->get('branch'));
				$conditional_sql .= " AND a.partner_branch = $branch";
			}
			
			$conditional_sql .= " AND ap.invoice='1'";   //non autofil customer
			
			
			$qry="SELECT a.*,c.id as cid,CONCAT(IFNULL(lastname,''),', ',IFNULL(firstname,'')) AS name,p.number,e.email,amount,DATE(a.regtime) as date 
				  FROM a_customer_account_partner as ap,a_customer_account_log as a
				  LEFT JOIN a_customer as c ON a.customer=c.id
				  LEFT JOIN a_phone as p ON p.customer = c.id AND p.main ='1' AND p.status='1'
				  LEFT JOIN a_email as e ON e.customer = c.id AND e.main ='1' AND e.status='1'
				  WHERE 1=1 $conditional_sql AND ap.customer = a.customer 
 ORDER BY `a`.customer ASC";
			
			//echo $qry;	  
			
			$query=$this->db->query($qry);	
			$result =$query->result_array();
			return $result;
			
	  }
	  
	  
	/*update invoice status*/
	function updateInvoiceStatus($inid,$instatus)
	{
		$qry="UPDATE  a_customer_account_log  SET in_status='".$instatus."' WHERE `id`='".$inid."'";
		$query=$this->db->query($qry);
		return true;
	}
	
	
	/*update order invoice status*/
	function updateOrderInvoiceStatus($orderid,$instatus)
	{
		$qry="UPDATE  a_orderline  SET payment_status='".$instatus."' WHERE payment_status='pending' AND payment_type='invoice' AND a_orderline.order='".$orderid."'";
		$query=$this->db->query($qry);
		return true;
	}
	
	  
	
	// -- getInvoiceDetail ----------------------------------------------------------------------------------------------
    /**
     * return array of all the rows of invoice in table
     * accepts order_by and asc/desc values
     *
     * @return	array
     */

    function getInvoiceDetail($id='')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = " ";
		
		if(is_numeric($id)){
			$conditional_sql .= " AND id='".$id."'";
		}

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT * 
                                          FROM  a_customer_account_log 
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

	
	// -- getInvoiceOrderID ----------------------------------------------------------------------------------------------
    /**
     * return orderid of a invoice
     * accepts order_by and asc/desc values
     *
     * @return	array
     */

    function getInvoiceOrderID($time='',$customer='')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = " ";
		
		//$conditional_sql .= " AND a.customer='".$customer."' AND UNIX_TIMESTAMP(a.regtime) = '".strtotime($time)."'";
		
		$conditional_sql .= " AND a.customer='".$customer."' AND UNIX_TIMESTAMP(a.regtime) = '".strtotime($time)."'";
		

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT a.order,a_orderline.order as oid 
                                          FROM  a_customer_account_log as a
										  LEFT JOIN a_orderline ON a.orderline  = a_orderline.id 
										  WHERE 1=1 $conditional_sql
										  ORDER BY a.id DESC LIMIT 0,1
                                          ");
		
										  
		$results = $query->row_array(); //multi row array
		
		$order_id = ($results['order'] != NULL) ? $results['order'] : $results['oid'];
		

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
        return $order_id;

    }

	// -- getPartnerBranches ----------------------------------------------------------------------------------------------
    /**
     * return array of all the rows of partner branches in table
     * accepts order_by and asc/desc values
     *
     * @return	array
     */

    function getPartnerBranches($partner='')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = " ";
		
		if(is_numeric($partner)){
			$conditional_sql .= " AND partner='".$partner."'";
		}

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT * 
                                          FROM  a_partner_branch 
										  WHERE 1=1 $conditional_sql
										  AND status='1'
										  ORDER BY id ASC
                                          ");

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
	

		/* Get  faktura customers*/
		function saldoCustomers()
		{
			$conditional_sql ="";
			
			//echo $this->session->userdata['logged_in'];

			$current_partner = $this->session->userdata['partner'];
			$current_partner_branch=$this->session->userdata['partner_branch'];
			
			
			$conditional_sql .=" AND ap.partner = '".$current_partner."'";
			$field =  'lastname';
			
			
			if ($this->input->get('autofil')) {
				$autofil = $this->db->escape($this->input->get('autofil'));
				$conditional_sql .= " AND ap.autofil_invoice='".$autofil."'";
			}
			else{
				$conditional_sql .= " AND ap.autofil_invoice IS NULL";   //non autofil customer
			}
			
			
			if (is_numeric($this->input->get('branch'))) {
				$branch = $this->db->escape($this->input->get('branch'));
				$conditional_sql .= " AND ap.partner_branch = $branch";
			}
			
			if (is_numeric($this->input->get('saldo'))) {
				
				if($this->input->get('saldo') == 1){
					$conditional_sql .= " AND ap.paid >= 0 ";
				}
				else{
					$conditional_sql .= " AND ap.paid < 0 ";
				}
			}
			
			
			
			$qry="SELECT ap.*,c.id as cid,CONCAT(IFNULL(lastname,''),', ',IFNULL(firstname,'')) AS name,p.number,e.email,DATE_FORMAT(ap.regtime,'%d.%m.%Y') as lastuse,ap.paid 
				  FROM a_customer_account_partner as ap
				  LEFT JOIN a_customer as c ON ap.customer=c.id
				  LEFT JOIN a_phone as p ON p.customer = c.id AND p.main ='1' AND p.status='1'
				  LEFT JOIN a_email as e ON e.customer = c.id AND e.main ='1' AND e.status='1'
				  WHERE 1=1 $conditional_sql
 ORDER BY `c`.`lastname` ASC";
			
			//echo $qry;	  
			
			$query=$this->db->query($qry);	
			$result =$query->result_array();
			return $result;
			
	  }
	
		/* Get  complaints history for a month*/
		function getComplaintsList($month,$year)
		{
			$conditional_sql ="";
			
			$current_partner = $this->session->userdata['partner'];
			
			
			$conditional_sql .=" AND MONTH(regtime)='".$month."'  AND YEAR(regtime)='".$year."'";
			
			$conditional_sql .=" AND a.partner = '".$current_partner."'";
			$conditional_sql .=" AND a.in_type = 'complaint' AND in_status='paid' AND a.type='in'";
			 
			
			if ($this->input->get('branch')) {
				$branch = $this->db->escape($this->input->get('branch'));
				$conditional_sql .= " AND a.partner_branch = $branch";
			}
			
			
			$qry="SELECT a.*,c.id as cid,CONCAT(IFNULL(lastname,''),', ',IFNULL(firstname,'')) AS name,p.number,e.email,DATE_FORMAT(a.regtime,'%d.%m.%Y') as  date 
				  FROM a_customer_account_log as a
				  LEFT JOIN a_customer as c ON a.customer=c.id
				  LEFT JOIN a_phone as p ON p.customer = c.id AND p.main ='1' AND p.status='1'
				  LEFT JOIN a_email as e ON e.customer = c.id AND e.main ='1' AND e.status='1'
				  WHERE 1=1 $conditional_sql
 ORDER BY `a`.regtime ASC";
			
			
			
			$query=$this->db->query($qry);	
			$result =$query->result_array();
			
			return $result;
			
	  }	  
	  
	  
	  
		/* Get  faktura history in a day*/
		function getOrderlineFakturaList($status='')
		{
			$conditional_sql ="";
			
			//echo $this->session->userdata['logged_in'];

			$current_partner = $this->session->userdata['partner'];
			$current_partner_branch=$this->session->userdata['partner_branch'];
			
		
			$conditional_sql .=" AND a.partner = '".$current_partner."'";
			
			$status = ($status=='sent') ? 'waiting' : $status;
			 
			$conditional_sql .=" AND ol.payment_type = 'invoice' AND ol.payment_status='".$status."'";
			
			$field = ($status=='pending') ? 'ol.regtime' : 'ol.modtime';

			if (($this->input->get('from')!='Fra') && ($this->input->get('from')!='')) {
				$from = $this->db->escape(date('Y-m-d',strtotime($this->input->get('from'))));
				$conditional_sql .= " AND DATE(".$field.") >= $from";
			}
			if (($this->input->get('to')!='Til') && ($this->input->get('to')!='')) {
				
				$to = $this->db->escape(date('Y-m-d',strtotime($this->input->get('to'))));
				$conditional_sql .= " AND DATE(".$field.") <= $to";
			}
			
			if(($this->input->get('from')=='') || ($this->input->get('to')=='')){
				//$conditional_sql .= " AND DATE(a.regtime) = '".date('Y-m-d')."'";
			}
			
			
			if ($this->input->get('branch')) {
				$branch = $this->db->escape($this->input->get('branch'));
				$conditional_sql .= " AND a.partner_branch = $branch";
			}
			
			
			$qry="SELECT a.*,c.id as cid,CONCAT(IFNULL(lastname,''),', ',IFNULL(firstname,'')) AS name,p.number,e.email,amount,DATE(ol.regtime) as date,ol.payment_status as ol_payment_status,ol.id as olid,(SELECT COUNT(id)  FROM a_orderline  WHERE a_orderline.order=ol.order GROUP BY a_orderline.order) as total_count,(SELECT COUNT(id) FROM a_orderline  WHERE a_orderline.order=ol.order AND  a_orderline.payment_status = 'pending' AND a_orderline.payment_type = 'invoice'  GROUP BY a_orderline.order) as pending_count,ol.changed_amount as camount,ol.amount as tamount,(SELECT SUM(CASE When a_orderline.changed_amount IS NULL Then a_orderline.amount Else a_orderline.changed_amount End ) FROM a_orderline  WHERE a_orderline.order=ol.order AND  a_orderline.payment_status = '".$status."' AND a_orderline.payment_type = 'invoice'  GROUP BY a_orderline.order)  as total_pending 
				  FROM a_order_log,a_orderline as ol,a_order as a
				  LEFT JOIN a_customer as c ON a.customer=c.id
				  LEFT JOIN a_phone as p ON p.customer = c.id AND p.main ='1' AND p.status='1'
				  LEFT JOIN a_email as e ON e.customer = c.id AND e.main ='1' AND e.status='1'
				  WHERE 1=1 $conditional_sql AND ol.order = a.id 
				  AND a_order_log.order = a.id AND a_order_log.status = 9
 ORDER BY `a`.customer ASC";
			
			//echo $qry;	  
			
			$query=$this->db->query($qry);	
			$result =$query->result_array();
			return $result;
			
	  }
	  
	  
	  
	  
}

/* End of file settings_faktura_model.php */
/* Location: ./application/models/settings_faktura_model.php */
