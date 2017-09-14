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
			$conditional_sql_1='';
			$subconditional_sql_1='';
			
			//echo $this->session->userdata['logged_in'];

			$current_partner = $this->session->userdata['partner'];
			$current_partner_branch=$this->session->userdata['partner_branch'];
			
			$conditional_sql .=" AND a.partner = '".$current_partner."' AND a.partner_branch = '".$current_partner_branch."'";
			
			$conditional_sql_1 .=" AND a_order_log.partner = '".$current_partner."' AND a_order_log.partner_branch = '".$current_partner_branch."'";
			
			
			if($this->session->userdata['logged_in'] == 'shop'){ 
			
				$conditional_sql .=" AND a.type = 'shop'";
				
			}else{
				$conditional_sql .=" AND (a.type = 'web' OR a.type = 'app')";
			}
			
			if ($this->input->post('from')) {
				$from = $this->db->escape(date('Y-m-d',strtotime($this->input->post('from'))));
				$conditional_sql_1 .= " AND DATE(a_order_log.regtime) >= $from";
			}
			if ($this->input->post('to')) {
				
				$to = $this->db->escape(date('Y-m-d',strtotime($this->input->post('to'))));
				$conditional_sql_1 .= " AND DATE(a_order_log.regtime) <= $to";
			}
			
			if(($this->input->post('from')=='') && ($this->input->post('to')=='')){
				//$conditional_sql_1 .= " AND (DATE(a_order_log.regtime) = '".date('Y-m-d')."' OR  DATE(a_orderline.p_b_delivery_time) = '".date('Y-m-d')."' OR DATE(a_orderline.regtime) = '".date('Y-m-d')."')";
				
				$conditional_sql_1 .= " AND (DATE(a_order_log.regtime) = '".date('Y-m-d')."')";
				
				
			}
			
			$qry="SELECT a_order_log.`order`,a_orderline.`order` as olorder FROM a_order_log
			LEFT JOIN a_orderline ON a_orderline.id=a_order_log.orderline
			LEFT JOIN a_order ON a_order.id=a_order_log.order
			WHERE a_order_log.status IN(3,9,11) $conditional_sql_1 ORDER BY `a_order_log`.`regtime` ASC";
			
			$query=$this->db->query($qry);	
			$orderresult=$query->result_array();
			
			
				//echo '<pre>';print_r($orderresult);exit;
			
			
			$orderids=array();
			$order_ids=array();
			$orderlinearray=array();
			if(count($orderresult) > 0)
			{
				foreach($orderresult as $orderinfo)
				{
						if(intval($orderinfo['olorder']) > 0)
						{
							$orderids[$orderinfo['olorder']]=intval($orderinfo['olorder']);
							
							$orderlinearray[$orderinfo['olorder']]=intval($orderinfo['olorder']);
							
							
						}
						else
						{
							$orderids[$orderinfo['order']]=intval($orderinfo['order']);
							$order_ids[$orderinfo['order']]=intval($orderinfo['order']);
						}
				}
			}

			
			
			if(count($orderlinearray) > 0)
			{
				foreach($orderlinearray as $olids)
				{
					if(isset($order_ids[$olids]))
					{
						unset($orderlinearray[$olids]);
					}
				}				
			}
			
			
			
			
			if(count($orderids) > 0)
			{
				$oids=implode(',',$orderids);
			$conditional_sql=" AND a.id IN(".$oids.")";
			
			$orderby=" GROUP BY a.id ORDER BY FIELD(a.id,".$oids.") ASC";
			

			//$orderby = 'GROUP BY a.id ORDER BY a.id DESC';
		
		$qry="SELECT a.id,a.customer,a.type,
			a.total_amount,
			a.type,
			a.changed_amount,
			a.order_time,
			a.payment_type,
			a.voucher,
			a.delivery_note,
			a.special_instruction,
			a.payment_status,
			a.pubnub_channel_id as channel_id,
			p.name as partner,
			pb.name as partner_branch,
			CONCAT(IFNULL(a_customer.lastname,''),', ',IFNULL(a_customer.firstname,'')) AS customername,
CONCAT(IFNULL(e.fname,''),' ',IFNULL(e.mname,''),' ',IFNULL(e.lname,'')) AS ename,CONCAT(IFNULL(SUBSTR(e.fname, 1, 1),''),IFNULL(SUBSTR(e.mname, 1, 1),''),IFNULL(SUBSTR(e.lname, 1, 1),'')) AS einitial			
			FROM a_order as a
			LEFT JOIN a_employee as e ON e.id = a.employee
			LEFT JOIN a_employee_p_branch as epb ON e.id = epb.employee 
			LEFT JOIN a_partner_branch as pb ON a.partner_branch=pb.id
			LEFT JOIN a_partner as p ON pb.partner=p.id
			LEFT JOIN a_customer ON a_customer.id=a.customer
			$add_sql
			WHERE 1=1
			$conditional_sql $orderby";
			
			
			
			
			$query=$this->db->query($qry);	
			$result=$query->result_array();
			
		
			
			return array('orderinfo'=>$result,'orderids'=>$orderlinearray);
			}
			else
			{
				return array('orderinfo'=>array(),'orderids'=>array());
			}
			
			
			
	  }
	  
	    // -- getOrderLine ----------------------------------------------------------------------------------------------
    /**
     * return array of all the rows of orders in table
     * accepts order_by and asc/desc values
     * 
     * @param $orderby sorting
     * @param $sort sort order
     * @return	array
     */

    function getOrderLine($id='')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = "";
		$additional_sql = "";
		

        //if no valie client id, return false
        if (! is_numeric($id)) {
            return false;
        }

        //check if sorting type was passed
        $sort = ($sort == 'asc' || $sort == 'desc') ? $sort : 'ASC';

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
		
		//find customer subscribtion status
		$cus_id = $this->session->userdata['pos_customer_id'];
		$subscription = $this->payments_model->getSaldostatus($cus_id);
		
		
		//_____SQL QUERY_______
		
		if($this->session->userdata['logged_in']!='shop'){
		
		
       $query = $this->db->query("SELECT *,a_orderline.price as price,a_orderline.special_instruction as description,a_orderline.id as id,DATE_FORMAT(a_orderline.p_b_delivery_time,'%d.%m.%Y') as utlevering,CONCAT('".PATH_IMAGE_FOLDER."',a_images.path) as path, a_product.type as ptype,a_product.name,a_product.price as oprice
	    FROM a_orderline 
		LEFT JOIN a_product ON a_product.id=a_orderline.product 
		LEFT JOIN a_images ON a_images.product=a_product.id 
		WHERE a_orderline.order='".$id."' ORDER BY a_orderline.p_b_delivery_time ASC");
		
		}else{
		
		
			$conditional_sql_1='';
			if($this->session->userdata['logged_in'] == 'shop'){ 
				
				$current_partner = $this->session->userdata['partner'];
				$current_partner_branch=$this->session->userdata['partner_branch'];
				$conditional_sql_1 .=" AND a_order_log.partner = '".$current_partner."' AND a_order_log.partner_branch = '".$current_partner_branch."'";
				
			}
			if ($this->input->post('from')) {
				$from = $this->db->escape(date('Y-m-d',strtotime($this->input->post('from'))));
				$conditional_sql_1 .= " AND DATE(a_order_log.regtime) >= $from";
			}
			if ($this->input->post('to')) {
				
				$to = $this->db->escape(date('Y-m-d',strtotime($this->input->post('to'))));
				$conditional_sql_1 .= " AND DATE(a_order_log.regtime) <= $to";
			}
			if(($this->input->post('from')=='') && ($this->input->post('to')=='')){
			
				$conditional_sql_1 .= " AND (DATE(a_order_log.regtime) = '".date('Y-m-d')."'
				OR DATE(a_orderline.p_b_delivery_time) = '".date('Y-m-d')."')";
			}
			
			
        $query = $this->db->query("SELECT *,a_orderline.price as price,a_orderline.special_instruction as description,a_orderline.id as id,DATE_FORMAT(a_orderline.p_b_delivery_time,'%d.%m.%Y') as utlevering,CONCAT('".PATH_IMAGE_FOLDER."',a_images.path) as path, a_product.type as ptype,a_product.name,c.name as category,c.description as cdescription,
	CASE 
    WHEN $subscription = 0 THEN 
	  CASE 
	  WHEN pb.unsubscribed_price IS NULL THEN pb.price 
	  ELSE pb.unsubscribed_price  
	  END
   ELSE pb.price 	  
END  AS oprice
	    FROM a_product_p_branch_category as pbc,a_category_partner as c,a_orderline 
		LEFT JOIN a_order_log ON a_orderline.id=a_order_log.orderline
		LEFT JOIN a_product ON a_product.id=a_orderline.product 
		LEFT JOIN a_product_p_branch as pb ON pb.product = a_orderline.product 
		LEFT JOIN a_images ON a_images.product=a_product.id 
		WHERE pbc.product_p_branch = a_orderline.product  AND  pbc.category_partner=c.id AND 
		a_orderline.order='".$id."' AND a_order_log.status IN(3,9,11) $conditional_sql_1 GROUP BY a_orderline.id ORDER BY a_orderline.p_b_delivery_time ASC");
		}
		
		
		
		
		
		$results = $query->result_array(); //multi row array
		//benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;

    }
	
	function getOrderstatus($orderid,$column='order')
	{
		$qry="SELECT status FROM a_order_log WHERE `".$column."`='".$orderid."' AND status IN(3,9,11) ORDER BY id DESC LIMIT 0,1";
		$query=$this->db->query($qry);	
		$results=$query->row_array();
		return $results['status'];
	}
	
	function getCancelOrderhistory($orderid=0)
	{
	//	$oids=implode(',',$orderids);
		if(intval($orderid) > 0)
		{
			$conditional_sql=" AND a.id IN(".$orderid.")";
		}
		//$conditional_sql=" AND a.id IN(".$oids.")";
		$orderby=" GROUP BY a.id ORDER BY a.id ASC";
			$qry="SELECT a.id,a.customer,a.type,
			a.total_amount,
			a.type,
			a.changed_amount,
			a.order_time,
			a.payment_type,
			a.voucher,
			a.delivery_note,
			a.special_instruction,
			a.payment_status,
			a.pubnub_channel_id as channel_id,
			p.name as partner,
			pb.name as partner_branch,
			CONCAT(IFNULL(a_customer.lastname,''),', ',IFNULL(a_customer.firstname,'')) AS customername,
CONCAT(IFNULL(e.fname,''),' ',IFNULL(e.mname,''),' ',IFNULL(e.lname,'')) AS ename,CONCAT(IFNULL(SUBSTR(e.fname, 1, 1),''),IFNULL(SUBSTR(e.mname, 1, 1),''),IFNULL(SUBSTR(e.lname, 1, 1),'')) AS einitial,
a_order_canceled.canceled_reason,
a_order_canceled_reason.name as reason,			
a_order_canceled.comment
			FROM a_order as a
			LEFT JOIN a_employee as e ON e.id = a.employee
			LEFT JOIN a_employee_p_branch as epb ON e.id = epb.employee 
			LEFT JOIN a_partner_branch as pb ON a.partner_branch=pb.id
			LEFT JOIN a_partner as p ON pb.partner=p.id
			LEFT JOIN a_customer ON a_customer.id=a.customer
			LEFT JOIN a_orderline ON a_orderline.order=a.id
			LEFT JOIN a_order_canceled ON (a_order_canceled.order=a.id OR a_order_canceled.orderline=a_orderline.id)
			LEFT JOIN a_order_canceled_reason ON a_order_canceled_reason.id=a_order_canceled.canceled_reason
			WHERE 1=1 AND a_order_canceled.status='pending' $conditional_sql $orderby";
			$query=$this->db->query($qry);	
			$result=$query->result_array();
			return $result;
			
	}
	
	function getCancelstatus($orderid,$orderline,$data=false)
	{
		$qry="SELECT * FROM a_order_canceled WHERE `order`='".$orderid."' ORDER BY id DESC LIMIT 0,1";
		$query=$this->db->query($qry);
		if($query->num_rows() > 0)
		{	
			$result=$query->row_array();
			
			if($result['status'] != 'rejected')
			{
				if($data)
				{
					return $result['status'];
				}
				else
				{
					return true;
				}
				
			}
			else
			{
				return false;
			}
			
			
		}
		else
		{
			$qry="SELECT * FROM a_order_canceled WHERE `orderline`='".$orderline."' ORDER BY id DESC LIMIT 0,1";
					$query=$this->db->query($qry);
					if($query->num_rows() > 0)
					{
						$result=$query->row_array();
						if($result['status'] != 'rejected')
						{
							if($data)
							{
								return $result['status'];
							}
							else
							{
								return true;
							}
						}
						else
						{
							return false;
						}
					}
					else
					{
						return false;
					}
		}
	}
	
	function approvecancel($orderid,$status)
	{
	
		$qry="SELECT * FROM a_order_canceled WHERE `order`='".$orderid."' ORDER BY id DESC LIMIT 0,1";
		$query=$this->db->query($qry);
		if($query->num_rows() > 0)
		{
			$result=$query->row_array();
			if($result['status'] == 'pending')
			{
				$sql="INSERT INTO a_order_canceled 
				SET 
				`order`='".$orderid."',
				orderline=NULL,
				partner_branch='".$result['partner_branch']."',
				employee_p_branch='".$result['employee_p_branch']."',
				canceled_reason='".$result['canceled_reason']."',
				comment='".$result['comment']."',
				status='".$status."',
				regtime='".date('Y-m-d H:i:s')."'";
				$this->db->query($sql);
				
				$this->orders_model->updateorderlog($orderid,11);
				return true;
			}
			
		}
		else
		{
			$orderdetails = $this->orders_model->getOrderLine($orderid);
			$newstatus=0;
			if(count($orderdetails) > 0)
			{
				foreach($orderdetails as $orderlineinfo)
				{
					$orderline=$orderlineinfo['id'];
					$qry="SELECT * FROM a_order_canceled WHERE `orderline`='".$orderline."' ORDER BY id DESC LIMIT 0,1";
					$query=$this->db->query($qry);
					if($query->num_rows() > 0)
					{
						$result=$query->row_array();
						if($result['status'] == 'pending')
						{
							$newstatus=1;
							$sql="INSERT INTO a_order_canceled 
							SET 
							`orderline`='".$orderline."',
							partner_branch='".$result['partner_branch']."',
							employee_p_branch='".$result['employee_p_branch']."',
							canceled_reason='".$result['canceled_reason']."',
							comment='".$result['comment']."',
							status='".$status."',
							regtime='".date('Y-m-d H:i:s')."'";
							$this->db->query($sql);
							
							//echo $oline
							$orderline=$this->orders_model->getOrderlineinfo($orderid,$orderline);
							$oid = $orderid;
							$olid = $orderline['id'];
							$qty = 0;
							$price = $orderline['price'];
							$total = $orderline['order_total_amount'];
							$complain = $orderline['complain'];
							$in_house = $orderline['in_house'];
							$desp = $orderline['special_instruction'];
							$price = ($complain!='1')  ?   $price : 0;
							
							//$ocancel = ($totalorderline == $checkedoderline) ? '' : 'canceled';

							$ocancel='canceled';
							
							$data = $this->orders_model->updateOrderline($olid,$oid,$qty,$price,$total,$complain,$in_house,$desp,'canceled',$ocancel);
							
							$heatdata = $this->orders_model->orderlineHeatseal($olid);
							if(count($heatdata) > 0)
							{
								$this->orders_model->updateHeatsealstatus($heatdata,'19');//canceled
							}
							//SELECT * FROM `a_heat_seal_log` WHERE orderline='25' AND status='started' group by heat_seal
				
							
							
							
							
							
							
						}
						
					}
				}
			}
			
			
		
			if(count($orderdetails) > 0)
			{
				$status=0;
				foreach($orderdetails as $oitems)
				{
					if($oitems['payment_status'] == 'canceled' && $oitems['changed_quantity'] != '')
					{
						$status++;
					}
					
				}
				
				if($status == count($orderdetails))
				{
					$this->orders_model->updateorderlog($orderid,11);
				}
			}
			
			if($newstatus == 1)
			{	
				return true;
			}
			else
			{
				return false;
			}
		}
	}
	
	//get cancel orderlines
	function getCancelOrderLine($order_id,$odlines)
	{
		$query=$this->db->query("SELECT id FROM a_order_canceled WHERE `order`=".$order_id." AND id IN (SELECT MAX(`id`) FROM a_order_canceled WHERE `order`=".$order_id." ORDER BY id DESC)");
		
		if($query->num_rows() > 0)
		{
			$result=$query->row_array();
			return $odlines;
			
		}
		else
		{
			$query=$this->db->query("SELECT orderline FROM a_order_canceled WHERE status ='pending' AND orderline IN  ('".implode(',',$odlines)."') AND id IN (SELECT MAX(`id`) FROM a_order_canceled WHERE status ='pending' AND orderline IN  ('".implode(',',$odlines)."') ORDER BY id DESC)");
			
			$arr =array();
			if($query->num_rows() > 0)
			{
				$result=$query->result_array();
				foreach ($result as $row) {
					$arr[] = $row['orderline'];
				}
				return $arr;
				
			}
		}
		
	}


}

/* End of file settings_order_model.php */
/* Location: ./application/models/settings_order_model.php */
