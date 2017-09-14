<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Settings_order_model extends Super_Model
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


		/* Get  order history in a day*/
		function getOrderhistory()
		{
			$add_sql='';
			$conditional_sql ="";
			
			if($this->session->userdata['logged_in'] == 'shop'){ 
				$conditional_sql .=" AND a.type = 'shop'";
				$current_partner = $this->session->userdata['partner'];
				$current_partner_branch=$this->session->userdata['partner_branch'];
				$conditional_sql .=" AND a.partner = '".$current_partner."' AND a.partner_branch = '".$current_partner_branch."'";
				
			}else{
				$conditional_sql .=" AND (a.type = 'web' OR a.type = 'app')";
			}
			
			if ($this->input->post('from')) {
				$from = $this->db->escape(date('Y-m-d',strtotime($this->input->post('from'))));
				$conditional_sql .= " AND DATE(a_order_log.regtime) >= $from";
			}
			if ($this->input->post('to')) {
				
				$to = $this->db->escape(date('Y-m-d',strtotime($this->input->post('to'))));
				$conditional_sql .= " AND DATE(a_order_log.regtime) <= $to";
			}
			
			if(($this->input->post('from')=='') && ($this->input->post('to')=='')){
				//$conditional_sql .= " AND DATE(a_order_log.regtime) = CURDATE()";
				$conditional_sql .= " AND DATE(a_order_log.regtime) = '2017-02-01'";
			}
			
			$orderby = 'ORDER BY a.id DESC';
			
			
			$qry="SELECT a.id,a.customer,a.type,
			a.total_amount,
			a.type,
			a.changed_amount,
			a.order_time,
			a.payment_type,
			d.payment_id,
			a.voucher,
			a.delivery_note,
			a.special_instruction,
			a.payment_status,
			a.pubnub_channel_id as channel_id,
			p.name as partner,
			pb.name as partner_branch,
			a.payment_status,
			a_order_log.status as ostatus,
			DATE_FORMAT(a.order_time,'%d.%m.%Y') as odate,
			CONCAT(IFNULL(e.fname,''),' ',IFNULL(e.mname,''),' ',IFNULL(e.lname,'')) AS ename,CONCAT(IFNULL(SUBSTR(e.fname, 1, 1),''),IFNULL(SUBSTR(e.mname, 1, 1),''),IFNULL(SUBSTR(e.lname, 1, 1),'')) AS einitial			
			FROM a_employee_p_branch as epb ,a_employee as e,a_order_log,a_order as a
			LEFT JOIN a_customer_payment as d ON a.customer_payment=d.id
			LEFT JOIN a_partner_branch as pb ON a.partner_branch=pb.id
			LEFT JOIN a_partner as p ON pb.partner=p.id
			$add_sql
			WHERE 1=1
			AND a.id = a_order_log.order
			AND e.id = epb.employee  
			AND epb.id=a_order_log.employee_p_branch
			$conditional_sql $orderby";
			
			/*$qry="SELECT a.*
			FROM a_order_log,a_order as a
			WHERE 1=1
			ANd a.id = a_order_log.order
			$conditional_sql $orderby";*/
			
			
			$query=$this->db->query($qry);	
			$result=$query->result_array();
			return $result;
			
	  }


}

/* End of file settings_order_model.php */
/* Location: ./application/models/settings_order_model.php */
