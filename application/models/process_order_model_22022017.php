<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class Process_order_model extends Super_Model
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
	
    // -- getOrders ----------------------------------------------------------------------------------------------
    /**
     * get Orders table and return results as an array
     *
     */

    function getOrders($bag='',$order,$baglog)
    {
	
		//profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';
		$log_conditional_sql='';
        $limiting = '';
		
        //system page limit or set default 25
        $limit = (is_numeric($this->data['settings_general']['results_limit'])) ? $this->data['settings_general']['results_limit'] : 25;

        //---------------SEARCH FORM CONDITONAL STATMENTS------------------
		
		$conditional_sql .= " AND a_bag_order_log.bag='".$bag."'";
		
		$conditional_sql .= " AND a_bag_order_log.order='".$order."'";
		
		$conditional_sql .= " AND a_bag_order_log.id='".$baglog."'";
		
	
		
		$conditional_sql .= " AND a_order.id='".$order."'";
		
		
		
		$log_conditional_sql .= " AND a_order_log.order='".$order."'";
		
		
		//$conditional_sql .= " AND a_bag_order_log.status='1'";
		
        //---------------URL QUERY - CONDITONAL SEARCH STATMENTS---------------
		//$conditional_sql .= " AND a_order_log_status.id=a_order_log.status";
		

        //---------------URL QUERY - ORDER BY STATMENTS-------------------------
        //$sort_order = ($this->uri->segment(6) == 'desc') ? 'desc' : 'asc';
		
        $sort_columns = array(
            //'sortby_deliverytime' => 'a_order.deliverytime',
            'sortby_status' => 'a_order_log.status');
        //validate if passed sort is valid
        $sort_by = (array_key_exists('' . $this->uri->segment(6), $sort_columns)) ? $sort_columns[$this->uri->segment(6)] : 'a_order.order_time';
        $sorting_sql = "ORDER BY $sort_by $sort_order";

        //---------------IF SEARCHING - LIMIT FOR PAGINATION----------------------
        if ($type == 'search') {
            $limiting = "LIMIT $limit OFFSET $offset";
        }


        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

		$sql="SELECT a_order.*,a_order.id,a_order.customer,ROUND(a_order.total_amount, 0) as total,a_order_log.status as ostatus,a_order_log.id as oid,a_order_log.partner as processing_partner,a_order_log.partner_branch as processing_partner_branch,a_order_log_status.name_nb_no as  snamenb,a_order_log_status.name as sname,DATE_FORMAT(order_time,'%d.%m.%Y') as odate,DATE_FORMAT(order_time,'%H:%i') as otime,DATE_FORMAT(a_order_log.regtime,'%H:%i') as stime,DATE_FORMAT(a_order_log.regtime,'%d.%m.%Y') as sdate,CONCAT(IFNULL(a_customer.firstname,''),' ',IFNULL(a_customer.lastname,'')) AS customer_name,a_address.street_line_1,a_address.street_line_2,a_address.floor,a_address.calling_bell,a_address.calling_bell,a_city.city as city,a_address.zip as zip,a_order.payment_type,ct1.start_time as cstime,ct1.end_time as cetime,ct2.start_time as dstime,ct2.end_time as detime,a_zip.zone
                                            FROM a_bag_order_log
											
LEFT JOIN a_order ON a_order.id = a_bag_order_log.order											
LEFT JOIN a_order_log ON a_order.id = a_order_log.order
LEFT JOIN a_order_log_status ON a_order_log_status.id = a_order_log.status
LEFT JOIN a_customer ON a_customer.id = a_order.customer 
LEFT JOIN a_address ON a_customer.id = a_address.customer AND a_address.main = 1
LEFT JOIN a_zip ON a_zip.id = a_address.zip
LEFT JOIN a_city ON a_zip.city= a_city.id								
JOIN a_collection_time AS ct1 ON a_order.collection_time  = ct1.id
JOIN a_collection_time AS ct2 ON a_order.delivery_time = ct2.id
											
                                            WHERE 1=1 AND a_bag_order_log.order = a_order.id AND (a_order_log.id) in (SELECT MAX(id) FROM `a_order_log` WHERE 1=1 $log_conditional_sql GROUP BY `order` )
											$conditional_sql
											$sorting_sql
										    $limiting";
											
											
											
		
        //_____SQL QUERY_______
        $query = $this->db->query($sql);
											

										  
        $results = $query->result_array();

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
	
	function heatsealinfo($barcode,$orderline,$producttype)	
	{		
		$query=$this->db->query("SELECT * FROM a_heat_seal WHERE heat_seal_barcode='".$barcode."' AND orderline='".$orderline."'");		
		if($query->num_rows() > 0)		
		{				
			$result=$query->row_array();			
			return $result['id'];					
		}		
		else		
		{			
			$addsql="";
			if(trim($producttype) != '')
			{
				$addsql=",`additional_product`='".$producttype."'";
			}
			
			$qry="INSERT INTO a_heat_seal SET `heat_seal_barcode`='".$barcode."',`orderline`='".$orderline."',`regtime`='".date('Y-m-d H:i:s')."'$addsql";												
			$query=$this->db->query($qry);						
			return $this->db->insert_id();		
		}	
	}		
	
	function updateheatseal_log($heat_seal,$orderline,$status)	
	{			
			$qry="INSERT INTO a_heat_seal_log SET `heat_seal`='".$heat_seal."',`orderline`='".$orderline."',`status`='".$status."',`regtime`='".date('Y-m-d H:i:s')."',employee_p_branch='".$this->session->userdata('employee_p_branch')."'";	
			$query=$this->db->query($qry);						
			return $this->db->insert_id();	
	}
	
	function craeteInfile($orderid)
	{
		$orderlines=self::getOrderLine($orderid);	
	
		$orderinfo=self::getCustomerDetails($orderid);

		$indata='';
		if(count($orderlines) > 0)
		{	
			$i=1;
			foreach($orderlines aS $orderitems)
			{
			
			
				if($orderitems['heat_seal_barcode'] == '')
				{
					continue;
				}
				
				$oid=$orderitems['order'];
				$total_items = count($orderlines);
				
				if(intval($orderitems['additional_product']) > 0)
				{	
					$query=$this->db->query("SELECT a_additional_product.name FROM a_product_additional_product
					LEFT JOIN a_additional_product ON a_additional_product.id=a_product_additional_product.additional_product
					WHERE a_product_additional_product.id='".$orderitems['additional_product']."'");		
					if($query->num_rows() > 0)		
					{				
						$result=$query->row_array();
						$orderitems['name']=$result['name'];
					}
				}
				
				
				$orderid = str_pad($oid, 20);
				$total_items = sprintf("%02d", $total_items);
				$ival = sprintf("%02d", $i);
				$garcode = str_pad($orderitems['heat_seal_barcode'], 20);  
				$garment_name = str_pad($orderitems['name'], 30); 
				$garment_per='033';
				$hung='S';
				$garment_status=str_pad('R', 56);
				//$space=str_pad('', 55);
				
				if($orderinfo['type'] == 'shop')
				{
					$query=$this->db->query("SELECT name FROM a_partner_branch
					WHERE id='".$orderinfo['partner_branch']."'");		
					$bresult=$query->row_array();
					
					$customer_info= ';'.$oid.';In '.$orderinfo['odate'].'  Ready '.$orderitems['p_b_delivery_time'].';'.$orderinfo['customer_name'].', '.$orderinfo['zip'].' ;'.$orderinfo['number'].';'.$bresult['name'].';';
				}
				else
				{
					$customer_info= ';'.$oid.';In '.$orderinfo['collection_time'].'  Ready '.$orderinfo['delivery_time'].';'.$orderinfo['customer_name'].', '.$orderinfo['zip'].' ;'.$orderinfo['number'].';Smart Laundry;';
				}
				
				
				
				
				$indata.=$orderid.$total_items.$ival.$garcode.$garment_name.$garment_per.$hung.$garment_status.$customer_info;
				
				
				if(count($orderlines) > $i) 
				{
					$indata.="\n";
				}
				
				$i++;
			}
			//$writedata="\n";
			$writedata=$indata;
			
			if ( !write_file('sorting/automat_'.$oid.'.in',$writedata,'w')){
				return false;
			}
			else
			{
				return true;
			}
		}
		
			
			
		
	}
	
	 function getCustomerDetails($id)
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

  
		  //----------sql & benchmarking start----------
		  $this->benchmark->mark('code_start');
  
		  //_____SQL QUERY_______
		  /*$query = $this->db->query("SELECT a_order.id,a_order.comment,DATE_FORMAT(ordertime,'%d.%m.%Y') as odate,DATE_FORMAT(ordertime,'%H:%i') as otime,CONCAT(IFNULL(a_customer.firstname,''),' ',IFNULL(a_customer.lastname,'')) AS customer_name,a_address.street_line_1,a_address.street_line_2,a_address.floor,a_address.calling_bell,a_city.city as city,a_address.zip as zip,a_phone.number
		  							FROM a_order,a_customer,a_address,a_city,a_zip,a_phone
									WHERE a_order.id = $id 
									AND a_customer.id = a_order.customer 
									AND a_customer.id = a_address.customer 
									AND a_address.main = 1 
									AND a_zip.id = a_address.zip 
									AND a_zip.city= a_city.id	
									AND a_phone.main = 1 
									AND a_customer.id = a_phone.customer 	  
								   ");*/
								   
								   
								   
		  $query = $this->db->query("SELECT a_order.id,a_order.type,a_order.partner_branch,a_order.delivery_note,DATE_FORMAT(order_time,'%d/%m/%Y') as odate,DATE_FORMAT(order_time,'%H:%i') as otime,DATE_FORMAT(ctime.start_time,'%d/%m/%y') as collection_time,DATE_FORMAT(dtime.start_time,'%d/%m/%y') as delivery_time,CONCAT(IFNULL(a_customer.firstname,''),' ',IFNULL(a_customer.lastname,'')) AS customer_name,a_address.street_line_1,a_address.street_line_2,a_address.floor,a_address.calling_bell,a_city.city as city,a_address.zip as zip,a_phone.number,a_email.email,a_customer.id as customerid,a_zip.zone as zone
		  							FROM a_order
									 LEFT JOIN a_collection_time as ctime ON ctime.id = a_order.collection_time 
									  LEFT JOIN a_collection_time as dtime ON dtime.id = a_order.delivery_time
                                    LEFT JOIN a_customer ON a_customer.id = a_order.customer 
									LEFT JOIN a_email ON a_email.customer=a_customer.id
                                    LEFT JOIN a_address ON a_customer.id = a_address.customer AND a_address.main = 1
                                    LEFT JOIN a_zip ON a_zip.id = a_address.zip
                                    LEFT JOIN a_city ON a_zip.city= a_city.id
                                    LEFT JOIN a_phone ON a_customer.id = a_phone.customer  AND a_phone.main = 1 
									WHERE a_order.id = $id ");

										   
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
		//_____SQL QUERY_______
       /* $query = $this->db->query("SELECT a_orderline.order,a_orderline.quantity,a_product.name,a_heat_seal.heat_seal_barcode,a_heat_seal.additional_product,a_orderline.id as orderline,DATE_FORMAT(p_b_delivery_time,'%d/%m/%Y') as p_b_delivery_time
		
		
		FROM a_orderline 
		LEFT JOIN a_product ON a_product.id=a_orderline.product 
		LEFT JOIN a_heat_seal ON a_heat_seal.orderline=a_orderline.id 
		WHERE a_orderline.order='".$id."'");*/
		
		$query = $this->db->query("SELECT a_orderline.order,a_orderline.quantity,a_product.name,a_heat_seal.heat_seal_barcode,a_heat_seal.additional_product,a_orderline.id as orderline,DATE_FORMAT(p_b_delivery_time,'%d/%m/%Y') as p_b_delivery_time,a_heat_seal_log.status,a_heat_seal_log.id as logid
		FROM a_orderline 
		LEFT JOIN a_product ON a_product.id=a_orderline.product 
		LEFT JOIN a_heat_seal ON a_heat_seal.orderline=a_orderline.id 
		LEFT JOIN a_heat_seal_log ON a_heat_seal_log.orderline=a_orderline.id AND a_heat_seal_log.heat_seal=a_heat_seal.id 
		WHERE a_orderline.order='".$id."' AND a_heat_seal_log.id in (SELECT MAX(id) FROM `a_heat_seal_log` WHERE 1=1 AND a_orderline.id=a_heat_seal_log.orderline GROUP BY `heat_seal`) ");
		
			
			
		
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
	
	function updatebagorderlogstatus($orderid)
	{
		$qry="UPDATE a_bag_order_log SET `status`='0',modtime='".date('Y-m-d H:i:s')."' WHERE `order`='".$orderid."' AND `status`='1'";
		$query=$this->db->query($qry);						
		return true;	
	}
	
	function getHeatOrderLine($orderline)
	{
		$query=$this->db->query("SELECT * FROM a_heat_seal WHERE orderline='".$orderline."'");		
		return $query->num_rows();					
	}
	
	function heatsealbarcodestatus($barcode,$orderline)
	{
		$query=$this->db->query("SELECT * FROM a_heat_seal WHERE heat_seal_barcode='".$barcode."' AND orderline='".$orderline."'");		
		if($query->num_rows() > 0)
		{
			return true;
		}
		else
		{	
			return false;
		}		
	}
	
	function getHeatBarcodeinfo($barcode)
	{
		$query=$this->db->query("SELECT a_orderline.order FROM a_heat_seal
		LEFT JOIN a_orderline ON a_orderline.id=a_heat_seal.orderline
		WHERE a_heat_seal.heat_seal_barcode='".$barcode."'");		
		if($query->num_rows() > 0)
		{
			$results = $query->row_array(); //multi row array
			$order=$results['order'];
			$query=$this->db->query("SELECT `order`,id,bag FROM a_bag_order_log
					WHERE a_bag_order_log.`order`='".$order."' ORDER BY a_bag_order_log.id DESC LIMIT 0,1");
			if($query->num_rows() > 0)
			{
				$result=$query->row_array();
				return $result;
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
	
	/*Heat seal status for order scanning based on partner branch*/
	
	function getHeatsealStatus($product,$partner_branch)
	{
		//echo "SELECT * FROM a_product_p_branch WHERE product='".$product."' AND partner_branch='".$partner_branch."' AND status='1'";exit;
			$query=$this->db->query("SELECT * FROM a_product_p_branch WHERE product='".$product."' AND partner_branch='".$partner_branch."' AND status='1'");
			if($query->num_rows() > 0)
			{
				$result=$query->row_array();
				return $result;
			}
			else
			{
				return false;
			}
	}
	
	/*Validate proudct type*/
	function validateProducttype($product)
	{
		$query=$this->db->query("SELECT * FROM a_product_additional_product WHERE  product='".$product."' AND status='1'");
		$totrows=$query->num_rows();
		if($totrows > 0)
		{
			$status=$totrows;
		}
		else
		{
			$status=1;
		}
		return $status;
	}
	
	function getproducttypes($orderline)
	{
			$sql="SELECT a_product_additional_product.id,a_additional_product.name FROM a_orderline 
			LEFT JOIN a_product_additional_product ON a_product_additional_product.product=a_orderline.product
			LEFT JOIN a_additional_product ON a_additional_product.id=a_product_additional_product.additional_product
			WHERE a_orderline.id='".$orderline."' AND a_product_additional_product.status='1' ORDER BY a_product_additional_product.sort_order ASC";
			$query=$this->db->query($sql);
			if($query->num_rows() > 0)
			{
				$result=$query->result_array();
				return $result;
			}
			else
			{
				return false;
			}
	}
	
	function getproducttypename($heatseal)
	{
			$sql="SELECT a_product_additional_product.id,a_additional_product.name FROM  a_heat_seal
			
			LEFT JOIN a_product_additional_product ON a_product_additional_product.id=a_heat_seal.additional_product
			
			LEFT JOIN a_additional_product ON a_additional_product.id=a_product_additional_product.additional_product
			
			WHERE a_heat_seal.id='".$heatseal."'";
	
			$query=$this->db->query($sql);
			if($query->num_rows() > 0)
			{
				$result=$query->row_array();
				return $result;
			}
			else
			{
				return false;
			}
	}
	
	function updatedamageheatseal($damageorderline)
	{
		$addsql="";
		$damagearray=array(
		'heat_seal'=>$heatseal,
		'savetype'=>$savetype,
		'ordreline'=>$ordreline,
		'image_path'=>$images,
		'comment'=>$comments);
			
		if($damageorderline['savetype'] == 'heatseal')
		{
			$addsql="heat_seal='".$damageorderline['heat_seal']."',";
		}
		else
		{
			$addsql="orderline='".$damageorderline['ordreline']."',";
		}
		$newsql=str_replace(',','',$addsql);
		$sqry="SELECT * FROM a_product_damage WHERE $newsql";
		$query=$this->db->query($sqry);
		if($query->num_rows() > 0)
		{
			$result=$query->row_array();
			$oldimg=unserialize($result['image_path']);
			if(count($oldimg) > 0)
			{
				$damageorderline['image_path']=array_merge($oldimg, $damageorderline['image_path']);
			}
			$qry="UPDATE a_product_damage SET 
			image_path='".serialize($damageorderline['image_path'])."' WHERE id='".$result['id']."'";	
			$this->db->query($qry);
			return $result['id'];
		}
		else
		{
			$qry="INSERT INTO a_product_damage SET 
			$addsql
			image_path='".serialize($damageorderline['image_path'])."',
			comment='".$damageorderline['comment']."'";	
			$query=$this->db->query($qry);	
			return $this->db->insert_id();
		}
		
		
	}
	
	function getdamageheatseal($orderlinearray,$heatarray)
	{
		
			$addsql='(';
			$hstatus=0;
			if(count($heatarray) > 0)
			{	
				$hstatus=1;
				$heat=implode(',',$heatarray);
				$addsql.="heat_seal IN(".$heat.")";
			}
			
			if(count($orderlinearray) > 0)
			{	
				$orderline=implode(',',$orderlinearray);
				if($hstatus > 0)
				{
					$addsql.=" OR orderline IN(".$orderline.")";
				}
				else
				{
					$addsql.="orderline IN(".$orderline.")";
				}
			}
			
			$addsql.=')';
			$sql="SELECT * FROM a_product_damage WHERE 1=1 AND status='1' AND $addsql";
			
			/*
			$sql="SELECT a_product_damage.*,a_product.name FROM a_product_damage
			LEFT JOIN a_heat_seal ON a_heat_seal.id=a_product_damage.heat_seal
			LEFT JOIN a_orderline ON (a_orderline.id=a_product_damage.orderline OR a_orderline.id=a_heat_seal.orderline)
			LEFT JOIN a_product ON a_product.id=a_orderline.product
			WHERE 1=1 AND $addsql
			";
			*/
			$query=$this->db->query($sql);
			if($query->num_rows() > 0)
			{
				$result=$query->result_array();
				return $result;
			}
			
	}
	
	function updateOrderstatus($heatseal)
	{
		if(count($heatseal) > 0)
		{	
			foreach($heatseal as $barcode)
			{
				$query=$this->db->query("SELECT id FROM a_heat_seal WHERE  heat_seal_barcode='".$barcode."' ORDER BY id DESC LIMIT 0,1");
				$totrows=$query->num_rows();
				if($totrows > 0)
				{
					$result=$query->row_array();
					$heatid=$result['id'];
				
					$qry=$this->db->query("SELECT id FROM a_heat_seal_log WHERE  heat_seal='".$heatid."' ORDER BY id DESC LIMIT 0,1");
					$log=$qry->row_array();
					
					$sql="UPDATE a_heat_seal_log SET 
					status='finished' WHERE id='".$log['id']."'";	
					$this->db->query($sql);
					
					
				}
			}
		}
	}
	
	function updateHetseallogstatus($heat_seal_log_id)
	{
		$sql="UPDATE a_heat_seal_log SET status='washing' WHERE id='".$heat_seal_log_id."'";	
		$this->db->query($sql);
	}
	
	function getHetseallog($heatseal)
	{
			$sql="SELECT a_heat_seal.heat_seal_barcode as barcode,a_order.id,a_heat_seal_log.orderline,a_orderline.order,
a_order.order_time,a_order.type,a_order.partner_branch,a_order.changed_amount,a_order.total_amount
		FROM a_heat_seal 
		LEFT JOIN a_heat_seal_log ON a_heat_seal_log.heat_seal=a_heat_seal.id
		LEFT JOIN a_orderline ON a_orderline.id=a_heat_seal_log.orderline
		LEFT JOIN a_order ON a_order.id=a_orderline.order
		WHERE a_heat_seal.heat_seal_barcode='".$heatseal."' AND a_order.id=a_orderline.order AND a_heat_seal_log.status='started' ORDER BY a_heat_seal_log.id DESC";
		
	
		
		$query=$this->db->query($sql);
			if($query->num_rows() > 0)
			{
				$result=$query->result_array();
				//echo '<pre>';print_r($result);exit;
				return $result;
			}
			else
			{	
				return false;
			}
	}
	
	
	 /**
      * list utlevering orders of a customer
      */
     function getOrderInfo($order_id){
 		
     
		$orderinfo = $this->orders_model->getOrderinfo($order_id);
		$cus_id=$orderinfo['customer'];
		$customer = $this->orders_model->getCustomerDetails($order_id);
		
		$total_amount = $this->data['fields']['order']['total_amount'];
		
		$discount = 0;
		
		$orderline= $this->orders_model->getOrderLine($order_id);
		
		
		
		
			$str ='';
			$total = 0;
			
			 //echo '<pre>';print_r($this->data['blocks']['orderline']); exit;
		     $rens='<div id="ajax-loader-rens"><img src="img/ajax-loader.gif"/></div>';
		     $vask='<div id="ajax-loader-vask"><img src="img/ajax-loader.gif"/></div>';
			 $rensstatus=0;
			 $vaskstatus=0;
			 $paidamountarray=array();
			 $waitamountarray=array();
			 $paidstatus=1;
			 $discountstatus=1;
			 $pendingstatus=0;
			 $this->data['reg_blocks'][] = 'rens';
			 $this->data['reg_blocks'][] = 'vask';
			 
			 
			 
			if (count($orderline) > 0) {
				$k = 0;
				$str ='';
				$row = array();
				for($i=0;$i<count($orderline);$i++){ 
					$subtotal = 0;
					$quantity = 0;
					$gtype = $orderline[$i]['ptype'];
					$product_id = $orderline[$i]['id'];
					$orderline[$i]['i'] = $i;
					$quantity = ($orderline[$i]['changed_quantity']!='') ? $orderline[$i]['changed_quantity'] : $orderline[$i]['quantity'];
					if (round($quantity, 0) == $quantity)
					{
						// is whole number
						$quantity = round($quantity, 0);
					}					
					
					 $discount = $this->products_model->getProDiscount($orderline[$i]['product']);
					 $discount=$discount[0];
					 $ddesc='&nbsp;';
					 if(isset($discount['description']))
					 {
						$ddesc=' ('.$discount['description'].')';
					 }
					 
					 $subtotal = ($orderline[$i]['changed_amount']!='') ? $orderline[$i]['changed_amount'] : $orderline[$i]['amount'];
					 
					 $productPrice=$subtotal;
					
						
					 $productPrice=round($productPrice);
						 
					  if($orderline[$i]['payment_status'] == 'paid')
					  {
							$paidamountarray[]=$productPrice;
							$discountstatus=0;
					  }
					  
					   if($orderline[$i]['payment_status'] == 'waiting')
					  {
							$waitamountarray[]=$productPrice;
							$paidstatus=0;
							$discountstatus=0;
					  }
					  
					  if($orderline[$i]['payment_status'] == 'pending')
					  {
							$paidstatus=0;
							$pendingstatus=1;
					  }
					
					
					$subtotalarray[]= $productPrice;
					
					$total += $subtotal;
					
					$cart_type = ($gtype=='rens') ? 'rens' : 'vask';
					$day = $orderline[$i]['utlevering'];
					
					$row[$day][$cart_type][$k] = $orderline[$i]['id'];
					
					$this->data['blocks'][$cart_type][$k]['orderline_emp']='';
					if($orderline[$i]['payment_status'] == 'paid' || $orderline[$i]['payment_status'] == 'waiting')
					{
			//	echo '<pre>';print_r();exit;
						$orderlineemp = $this->employee_model->getEmployeebranchDetail($orderline[$i]['id'],$orderline[$i]['order']);
						if($orderlineemp)
						{
						
							//$this->data['blocks'][$cart_type][$k]['orderline_emp']='<p>Kasserer: '.$orderlineemp['initial'].'</p>';
							$this->data['blocks'][$cart_type][$k]['orderline_emp']='('.$orderlineemp['initial'].')';
						}
						
						
					
						
					}
					
					
					
					
					$this->data['blocks'][$cart_type][$k]['utlevering'] = $orderline[$i]['utlevering'];					
					$this->data['blocks'][$cart_type][$k]['id'] = $orderline[$i]['id'];
					
					$this->data['blocks'][$cart_type][$k]['oprice'] = round($orderline[$i]['oprice']);
					
					
					$arr = $this->orders_model->getProductDisplayName($orderline[$i]['product']);
					
					$this->data['blocks'][$cart_type][$k]['name'] = $arr['name'];
					$this->data['blocks'][$cart_type][$k]['price'] = $orderline[$i]['price'];
					$this->data['blocks'][$cart_type][$k]['description'] = $orderline[$i]['special_instruction'];
					$this->data['blocks'][$cart_type][$k]['complain'] = $orderline[$i]['complain'];
					$this->data['blocks'][$cart_type][$k]['in_house'] = $orderline[$i]['in_house'];
					
					$this->data['blocks'][$cart_type][$k]['qty'] = $quantity;
					$this->data['blocks'][$cart_type][$k]['gtype'] = $gtype;
					$this->data['blocks'][$cart_type][$k]['subtotal'] = $productPrice;
					
					$path_parts = pathinfo($orderline[$i]['path']);
					$this->data['blocks'][$cart_type][$k]['thumb'] = $path_parts['filename'] . "." .$path_parts['extension'];
					$this->data['blocks'][$cart_type][$k]['status'] =  $orderline[$i]['payment_status'];
					$k++;
				
			  }//for
			 
			   
			}//if
		
			
			
			$total=array_sum($subtotalarray);
			$subtotal=$total;
			$cus_sub_total=$subtotal;
			$customerid = $customer['customerid'];
			$zone = $customer['zone'];
			$delivery_type = 'normal'; //default
			
			$delivery =$this->general_model->checkMinimumAmount($delivery_type,$customerid,$total,$zone);
			
			$min_price=$delivery['min_price'];
			$min_price_txt = '';
			$min_price_status=0;
			if($cus_sub_total < $min_price)
			{
				$min_price_txt =  ' (Minste beløp kr '.formatcurrency($min_price).')';
				$min_price_status=1;
			}
			
			$subtotal = ($subtotal < $min_price) ?  $min_price : $subtotal;
			$delsum=$subtotal;
			
			$delviery = ($subtotal >= $tdelivery['free_delivery_after']) ?  '0' : $delivery['delivery_charge'];
			
			$old_delivery_charge = $delivery['delivery_charge'];


			$discountstatus=0;
			$vouchercode='';
			$discount=0;
			if(intval($orderinfo['voucher']) > 0)
			{
				$discountstatus=1;
				$qry="SELECT *,ROUND(price, 0) as price FROM a_voucher WHERE id='".$orderinfo['voucher']."'";
				$query = $this->db->query($qry);
				$vdata = $query->row_array();//multi row array
				
				$vouchercode=$vdata['voucher'];
				
				if($vdata['percentage'] != '')
				{
					$percentage=$vdata['percentage']/100;
					$discount=$subtotal*$percentage;
					
					
				}
				else
				{
					$discount=$vdata['price'];
				}
				if(($vdata['free_delivery_charge'] == 1)  && 	($orderinfo['type'] != 'shop'))
				{
					$delviery=0;
				}
				else
				{
					$delviery=$old_delivery_charge;
				}
				
				$fprice=$cus_sub_total-$discount;
			
					if($fprice < $min_price)
					{
						$min_price_status=2;
					}
			}
			else{
				//if the discount in % or kr
				if($orderinfo['order_discount'] !=''){
					$vouchercode = $orderinfo['order_discount'];
					if(stripos($vouchercode, '%')){
						$percentage = str_replace("%","",$vouchercode);
						$discount =  $delsum * ($percentage/100);
					}
					else{
						
						$discount = str_replace("kr ","",$vouchercode);
						$vouchercode = "kr ".formatcurrency($discount);
					}
					
				}

			}
			
			$newdiscount=$discount;
			
	
			
			
			if($orderinfo['type'] == 'shop')
			{
				$delviery=0;
				$min_price_status=0;
			}
			
		
			$totalamount=$orderinfo['total_amount'];
			if(trim($orderinfo['changed_amount']) != '')
			{
				$totalamount=$orderinfo['changed_amount'];
			}
			
			$paidamountstatus=0;
			$pendingamountstatus=0;
			$betalstatus=1;
			$waitamountstatus=0;
			$totawaitamount=0;
			if(count($waitamountarray) > 0)
			{
				$waitamountstatus=1;
				$totawaitamount=array_sum($waitamountarray);
			}
			
			if(count($paidamountarray) > 0)
			{
				$paidamountstatus=1;
				$totapaidamount=array_sum($paidamountarray);
				
			}

				
					
			if($pendingstatus == 1 && $discount > 0)
			{
					 if(intval($totapaidamount) > 0)
					 {
						if($discountstatus == 0)
						{
							if($totapaidamount >= $discount)
							{
								$discountapplystatus=1;
								$totapaidamount=$totapaidamount-$discount;
							}
							else
							{
								if($discount >= $totapaidamount)
								{
									$discountapplystatus=1;
									$discount=$discount-$totapaidamount;
									$totapaidamount=0;
								}
							}
						}
						
					  }
					  
					 if(intval($totawaitamount) > 0)
					 {
					 
				
						
						if($discountstatus == 0)
						{
							if($totawaitamount >= $discount)
							{
								$discountapplystatus=1;
								$totawaitamount=$totawaitamount-$discount;
							}
							else
							{
								if($discount >= $totawaitamount)
								{
									$discountapplystatus=1;
									$discount=$discount-$totawaitamount;
									$totawaitamount=0;
								}
							}
								
						}
							 	
					}  
					
					if(intval($totapaidamount) == 0 && intval($totawaitamount) == 0)
					{
					
						$balanceamount = $totalamount;
						
					}
					else
					{
						$balanceamount= $totalamount-$totapaidamount;
						$balanceamount= $balanceamount-$totawaitamount;
					}
					
	
			}
			else
			{
			
			
				if($discountstatus == 0 && $discount > 0)
				{
						$totalpayamt=($totapaidamount+$totawaitamount) - $orderinfo['total_amount'];
						$discountapplystatus=1;
						$balanceamount=$totalpayamt-$discount;
						
							if($totapaidamount >= $discount)
							{
								$totapaidamount=$totapaidamount-$discount;
							}
							else
							{
								if($discount >= $totapaidamount)
								{
									$discount=$discount-$totapaidamount;
								}
							}
							
							if($discount > 0)
							{
								if($totawaitamount >= $discount)
								{
									$discountapplystatus=1;
									$totawaitamount=$totawaitamount-$discount;
								}
								else
								{
									if($discount >= $totawaitamount)
									{
										$discountapplystatus=1;
										$discount=$discount-$totawaitamount;
										$totawaitamount=0;
									}
								}
							}
							
							
								
						
						
						
						
						
					
				}
				else
				{
					//$balanceamount= $orderinfo['total_amount']-$totapaidamount;
					//$balanceamount= $balanceamount-$totawaitamount;
					if(count($paidamountarray) > 0)
					{
						//$paidamountstatus=1;
						//$totapaidamount=array_sum($paidamountarray);
						$balanceamount= $totalamount-$totapaidamount;
						$balanceamount=$balanceamount-$totawaitamount;
					
						if(intval($balanceamount) > 0)
						{
							$pendingamountstatus=1;
						}
						else
						{
							$betalstatus=0;
						}
					}
					else if((count($waitamountarray) > 0) || (count($paidamountarray) > 0)){
						
						$pendingamountstatus=1;
						$balanceamount=$totalamount-$totawaitamount;
						
					}
				}
			}
			
			
			
			
			
			if(count($paidamountarray) > 0 || count($waitamountarray) > 0)
			{
					if(intval($balanceamount) > 0)
					{
						$pendingamountstatus=1;
					}
					else
					{
						$betalstatus=0;
					}
			
			}
			
			
			/*
			if(count($paidamountarray) > 0)
			{
				//$paidamountstatus=1;
				//$totapaidamount=array_sum($paidamountarray);
				$balanceamount= $totalamount-$totapaidamount;
				
					$balanceamount=$balanceamount-$totawaitamount;
				
					if(intval($balanceamount) > 0)
					{
						$pendingamountstatus=1;
					}
					else
					{
						$betalstatus=0;
					}
			}
			else if((count($waitamountarray) > 0) || (count($paidamountarray) > 0)){
				
				$pendingamountstatus=1;
				$balanceamount=$totalamount-$totawaitamount;
				
			}
			*/
			$deliveryreadystatus=0;
			if($betalstatus == 0)
			{
				if($orderinfo['order_status'] != 9)
				{
					$deliveryreadystatus=1;
				}
			}
			
				
				
			
			$count = $this->cart->total_items();
			$delsumamt=$totalamount/1.25;	
			$delsumamt=round($delsumamt, 2);
			$delsumvat=$totalamount-$delsumamt;	
			
			
			
				
				
				
				
			
			
			$result = array("delsum_currency"=>formatcurrency($total),"mva_currency"=>formatcurrency($delsumvat),
			"delviery"=>$delviery,"vouchercode"=>$vouchercode,"discount"=>$newdiscount,"min_price_status"=>$min_price_status,"min_price"=>$min_price,'min_price_txt'=>$min_price_txt,'total_amount_currency'=>formatcurrency($totalamount),'total_amount'=>$totalamount,'discount_currency'=>formatcurrency($newdiscount),'paidamount'=>formatcurrency($totapaidamount),'totawaitamount'=>formatcurrency($totawaitamount),'balanceamount'=>formatcurrency($balanceamount),'balance_amount'=>$balanceamount,'paidamountstatus'=>$paidamountstatus,'pendingamountstatus'=>$pendingamountstatus,'betalstatus'=>$betalstatus,'deliveryreadystatus'=>$deliveryreadystatus,'waitamountstatus'=>$waitamountstatus,'order_id'=>'#'.$order_id);
			
			return $result;
			
	
	 }
	 
	 
	 
	
	
}

/* End of file process_order_model.php */
/* Location: ./application/models/process_order_model.php */
