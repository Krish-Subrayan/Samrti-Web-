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
	
	function heatsealinfo($barcode,$orderline,$producttype,$product='0')	
	{		
		/*$query=$this->db->query("SELECT * FROM a_heat_seal WHERE heat_seal_barcode='".$barcode."' AND orderline='".$orderline."'");*/	
		
		$query=$this->db->query("SELECT * FROM a_heat_seal WHERE heat_seal_barcode='".$barcode."' ORDER BY id DESC LIMIT 0,1");		
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
				$addsql.=",`additional_product`='".$producttype."'";
			}
			if(intval($product) > 0)
			{
				$addsql.=",`product`='".$product."'";
			}
			
			$qry="INSERT INTO a_heat_seal SET `heat_seal_barcode`='".$barcode."',`orderline`='".$orderline."',`regtime`='".date('Y-m-d H:i:s')."'$addsql";												
			$query=$this->db->query($qry);						
			return $this->db->insert_id();		
		}	
	}		
	
	function updateheatseal_log($heat_seal,$orderline,$status)	
	{			
		
		$addsql="";
		
		if($this->session->userdata['partner_branch'] == 14 || $this->session->userdata['partner_branch'] == 28 || $this->session->userdata['company_status'])
		{
			
			$addsql.=",company='".$this->session->userdata['customer']['company']."'";
			$addsql.= ",customer='".$this->session->userdata['customer']['id']."'";
		}
		else
		{
			/*$qry="SELECT a_order.customer FROM a_orderline 
			LEFT JOIN a_order ON a_orderline.order=a_order.id
			WHERE a_orderline.id='".$orderline."'";*/
			
			$qry="SELECT b.customer,b.company FROM a_orderline a,a_order b WHERE a.id='".$orderline."' AND a.order=b.id";
			$query=$this->db->query($qry);	
			$result = $query->row_array();
			$customer=$result['customer'];
			$addsql.= ",customer='".$customer."'";
			if(intval($result['company']) > 0)
			{
				$addsql.= ",company='".$result['company']."'";
			}
		}
		
		
			
			
			$qry="INSERT INTO a_heat_seal_log SET `heat_seal`='".$heat_seal."',`orderline`='".$orderline."',`status`='".$status."',`regtime`='".date('Y-m-d H:i:s')."',employee_p_branch='".$this->session->userdata('employee_p_branch')."'$addsql";	
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

      function getOrderLine($id='',$orderline='')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = "";
		$additional_sql = "";
		
    if(intval($orderline) > 0)
		{
			 $conditional_sql.= " AND a_orderline.id='".$orderline."'";
		}

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
		//LEFT JOIN a_heat_seal ON a_heat_seal.orderline=a_orderline.id 
	/*	$query = $this->db->query("SELECT a_orderline.order,a_orderline.quantity,a_product.name,a_heat_seal.heat_seal_barcode,a_heat_seal.additional_product,a_orderline.id as orderline,DATE_FORMAT(p_b_delivery_time,'%d/%m/%Y') as p_b_delivery_time,a_heat_seal_log.status,a_heat_seal_log.id as logid
		FROM a_orderline 
		LEFT JOIN a_product ON a_product.id=a_orderline.product 
		LEFT JOIN a_heat_seal ON a_heat_seal.id > 0
		LEFT JOIN a_heat_seal_log ON a_heat_seal_log.orderline=a_orderline.id AND a_heat_seal_log.heat_seal=a_heat_seal.id 
		WHERE a_orderline.order='".$id."' AND a_heat_seal_log.id in (SELECT MAX(id) FROM `a_heat_seal_log` WHERE 1=1 AND a_orderline.id=a_heat_seal_log.orderline GROUP BY `heat_seal`) ");
		  */
      	$query = $this->db->query("SELECT a_order.type,a_order.customer,a_order.partner,a_order.partner_branch,a_product.id as product,a_orderline.order,a_orderline.quantity,a_orderline.changed_quantity,a_product.name,a_heat_seal.heat_seal_barcode,a_heat_seal.additional_product,a_orderline.id as orderline,DATE_FORMAT(p_b_delivery_time,'%d/%m/%Y') as p_b_delivery_time,a_heat_seal_log.status,a_heat_seal_log.id as logid
		FROM a_orderline 
		LEFT JOIN a_order ON a_order.id=a_orderline.order 
		LEFT JOIN a_product ON a_product.id=a_orderline.product 
		LEFT JOIN a_heat_seal ON a_heat_seal.id > 0
		LEFT JOIN a_heat_seal_log ON a_heat_seal_log.orderline=a_orderline.id AND a_heat_seal_log.heat_seal=a_heat_seal.id 
		WHERE a_orderline.order='".$id."' $conditional_sql AND a_heat_seal_log.id in (SELECT MAX(id) FROM `a_heat_seal_log` WHERE 1=1 AND a_orderline.id=a_heat_seal_log.orderline GROUP BY `heat_seal`) ");
	
			
			
		
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
	
	function getHeatOrderLine($orderline,$checkstatus='0')
	{
		if($checkstatus){
			$query=$this->db->query("SELECT * FROM a_heat_seal_log as ahsl WHERE ahsl.id = (SELECT max(hsl.id) as ID FROM a_heat_seal_log as hsl,a_orderline WHERE  hsl.orderline = a_orderline.id AND a_orderline.id='".$orderline."') AND ahsl.status= '2' ORDER by ahsl.id DESC");
			if($query->num_rows() > 0){
				
				$query2=$this->db->query("SELECT * FROM a_heat_seal_log WHERE orderline='".$orderline."' AND status='2'");
				return $query2->num_rows();					
			}
		}
		else{
			$query=$this->db->query("SELECT * FROM a_heat_seal_log WHERE orderline='".$orderline."' AND status='2'");	
		}
		return $query->num_rows();					
	}
	
	function heatsealbarcodestatus($barcode,$orderline)
	{
		//$query=$this->db->query("SELECT * FROM a_heat_seal WHERE heat_seal_barcode='".$barcode."' AND orderline='".$orderline."'");	'added','started','washing','finished','rewash','canceled'
		
		$query=$this->db->query("SELECT * FROM a_heat_seal WHERE heat_seal_barcode='".$barcode."'");	
		if($query->num_rows() > 0)
		{
			$results = $query->row_array(); //multi row array
			$heat_sealid=$results['id'];
			$query=$this->db->query("SELECT a_heat_seal_log.status,a_orderline.order FROM a_heat_seal_log 
			LEFT JOIN a_orderline ON a_orderline.id=a_heat_seal_log.orderline
			WHERE a_heat_seal_log.heat_seal='".$heat_sealid."' 
			ORDER BY a_heat_seal_log.id DESC LIMIT 0,1");
			if($query->num_rows() > 0)
			{
				$results = $query->row_array(); //multi row array
				if($results['status'] == '18' || $results['status'] == '19')
				{
					return false;
				}
				else
				{
					return $results;
				}
				
			}
			else
			{
					$query=$this->db->query("SELECT  a_heat_seal_log.status,a_orderline.order FROM a_heat_seal_log
					LEFT JOIN a_orderline ON a_orderline.id=a_heat_seal_log.orderline
					WHERE a_heat_seal_log.heat_seal='".$heat_sealid."' AND a_heat_seal_log.orderline='".$orderline."' AND a_heat_seal_log.status='2' ORDER BY a_heat_seal_log.id DESC LIMIT 0,1");//started
					if($query->num_rows() > 0)
					{
						return $results;
					}
					else
					{	
						return false;
					}
			}
			
			
			
			
			
		}
		else
		{	
			return false;
		}		
	}
	
	function getHeatsealinfo($barcode)
	{
		$query=$this->db->query("SELECT id,product FROM a_heat_seal WHERE heat_seal_barcode='".$barcode."' ORDER BY id DESC LIMIT 0,1");		
		if($query->num_rows() > 0)
		{	
			$heatresults = $query->row_array(); //multi row array
			$heat_sealid=$heatresults['id'];
			
			
			
			
			$query=$this->db->query("SELECT customer,company FROM a_heat_seal_log WHERE heat_seal='".$heat_sealid."' AND customer != '' ORDER BY id DESC LIMIT 0,1");
			$logresults = $query->row_array(); //multi row array
			
			$results=array_merge($heatresults,$logresults);
			
			//echo '<pre>';print_r($results);exit;
			
			return $results;
		}
		else
		{
			return false;
		}
	}
	
	function getHeatBarcodeinfo($barcode)
	{
		
		/*$query=$this->db->query("SELECT a_orderline.order FROM a_heat_seal
		LEFT JOIN a_orderline ON a_orderline.id=a_heat_seal.orderline
		WHERE a_heat_seal.heat_seal_barcode='".$barcode."'");*/
		
		$query=$this->db->query("SELECT id FROM a_heat_seal WHERE heat_seal_barcode='".$barcode."' ORDER BY id DESC LIMIT 0,1");		
		if($query->num_rows() > 0)
		{	
			$results = $query->row_array(); //multi row array
			$heat_sealid=$results['id'];
			
			$query=$this->db->query("SELECT orderline FROM a_heat_seal_log WHERE heat_seal='".$heat_sealid."' ORDER BY id DESC LIMIT 0,1");
			$results = $query->row_array(); //multi row array
			$orderlineid=$results['orderline'];
			
			$query=$this->db->query("SELECT `order` FROM a_orderline WHERE id='".$orderlineid."' ORDER BY id DESC LIMIT 0,1");
			$results = $query->row_array(); //multi row array
			$order=$results['order'];
			return $order;
			
			//echo '<pre>';print_r($order);exit;
			
			/*$query=$this->db->query("SELECT `order`,id,bag FROM a_bag_order_log
					WHERE a_bag_order_log.`order`='".$order."' ORDER BY a_bag_order_log.id DESC LIMIT 0,1");
			if($query->num_rows() > 0)
			{
				$result=$query->row_array();
				
				return $result;
			}
			else
			{
				return false;
			}*/
		
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
			$sql="SELECT a_product_additional_product.additional_product as id,a_additional_product.name FROM a_orderline 
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
					
					$qry=$this->db->query("SELECT id,orderline,company,customer,employee_p_branch FROM a_heat_seal_log WHERE heat_seal='".$heatid."' ORDER BY id DESC LIMIT 0,1");
					$log=$qry->row_array();
					$addsql=""; 
					if($log['employee_p_branch'] != '')
					{
						$addsql.=",employee_p_branch='".$log['employee_p_branch']."'";
					}
					
					if($log['company'] != '')
					{
						$addsql.=",company='".$log['company']."'";
					}
					
					if(intval($log['customer']) > 0)
					{
						$addsql.= ",customer='".$log['customer']."'";
					}
					else
					{
						$qryy="SELECT b.customer FROM a_orderline a,a_order b WHERE a.id='".$log['orderline']."' AND a.order=b.id";
						
						$query=$this->db->query($qryy);	
						$result = $query->row_array();
						$customer=$result['customer'];
						$addsql.= ",customer='".$customer."'";
					}
					//finished
					$qry="INSERT INTO a_heat_seal_log SET `heat_seal`='".$heatid."',`orderline`='".$log['orderline']."',`status`='18',`regtime`='".date('Y-m-d H:i:s')."'$addsql";	
					$query=$this->db->query($qry);	
					
				}
			}
		}
	}
	
	function updateHetseallogstatus($heat_seal_log_id)
	{
		//$sql="UPDATE a_heat_seal_log SET status='washing' WHERE id='".$heat_seal_log_id."'";	
		//$this->db->query($sql);
		
					$qry=$this->db->query("SELECT heat_seal,id,orderline,company,customer,employee_p_branch FROM a_heat_seal_log WHERE id='".$heat_seal_log_id."' ORDER BY id DESC LIMIT 0,1");
					$log=$qry->row_array();
					$addsql=""; 
					if($log['employee_p_branch'] != '')
					{
						$addsql.=",employee_p_branch='".$log['employee_p_branch']."'";
					}
					
					if($log['company'] != '')
					{
						$addsql.=",company='".$log['company']."'";
					}
					
					if(intval($log['customer']) > 0)
					{
						$addsql.= ",customer='".$log['customer']."'";
					}
					else
					{
						
						
						$qry="SELECT b.customer FROM a_orderline a,a_order b WHERE a.id='".$log['orderline']."' AND a.order=b.id";
						
						$query=$this->db->query($qry);	
						$result = $query->row_array();
						$customer=$result['customer'];
						$addsql.= ",customer='".$customer."'";
					}
					//washing
					$qry="INSERT INTO a_heat_seal_log SET `heat_seal`='".$log['heat_seal']."',`orderline`='".$log['orderline']."',`status`='5',`regtime`='".date('Y-m-d H:i:s')."'$addsql";	
					$query=$this->db->query($qry);	
		
		
	}
	
	function getHetseallog($heatseal)
	{
		
		$current_partner = $this->session->userdata['partner'];
		$conditional_sql .=" AND a_order.partner = '".$current_partner."'";
		
		
		$sql="SELECT a_heat_seal.heat_seal_barcode as barcode,a_order.id,a_heat_seal_log.orderline,a_orderline.order,
a_order.order_time,a_order.type,a_order.changed_amount,a_order.total_amount,pb.name as partner_branch
		FROM a_heat_seal 
		LEFT JOIN a_heat_seal_log ON a_heat_seal_log.heat_seal=a_heat_seal.id
		LEFT JOIN a_orderline ON a_orderline.id=a_heat_seal_log.orderline
		LEFT JOIN a_order ON a_order.id=a_orderline.order
		LEFT JOIN a_partner_branch as pb ON a_order.partner_branch=pb.id
		LEFT JOIN a_partner as p ON pb.partner=p.id
		WHERE 1=1 $conditional_sql AND  a_heat_seal.heat_seal_barcode='".$heatseal."' AND a_order.id=a_orderline.order  GROUP BY a_order.id ORDER BY a_heat_seal_log.id DESC";
		
	//started
		
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
					  
					   if($orderline[$i]['payment_status'] == 'waiting' || ($orderline[$i]['payment_status'] == 'pending' && $orderline[$i]['payment_type'] == 'invoice'))
					  {
							$waitamountarray[]=$productPrice;
							$paidstatus=0;
							$discountstatus=0;
					  }
					  
					  if($orderline[$i]['payment_status'] == 'pending' && $orderline[$i]['payment_type'] != 'invoice')
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
	 
   
   	/*downloadfile*/ 
	function downloadInfile()
	{

		$query=$this->db->query("SELECT v.*, (SELECT COUNT(id)FROM vinderen WHERE `TikDetTikNum`=v.TikDetTikNum) as ocount FROM vinderen as v WHERE 1=1  AND status='inserted'");
		
		/*$query=$this->db->query("SELECT v.*, (SELECT COUNT(id)FROM vinderen WHERE `TikDetTikNum`=v.TikDetTikNum) as ocount FROM vinderen as v WHERE 1=1 AND (Left(TikDetTikNum,2) = '28' OR Left(TikDetTikNum,2) = '14') AND  DATE(TikDateIn) >= '2017-03-22'");
		
		
		$query=$this->db->query("SELECT v.*, (SELECT COUNT(id)FROM vinderen WHERE `TikDetTikNum`=v.TikDetTikNum) as ocount FROM vinderen as v WHERE 1=1  AND  TikDetTikNum IN ('05032450')");*/
		

		$orderlines = $query->result_array();
		
		$indata='';
		if(count($orderlines) > 0)
		{	
			$i=1;
			$temp = 0;
			foreach($orderlines as $orderitems)
			{
				if($orderitems['TikDetHeatSeal'] == '')
				{
					continue;
				}
				
				$oid=$orderitems['TikDetTikNum'];
				$total_items =$orderitems['ocount'];
				
				
				if(intval($temp) != intval($oid)){
					$i=1;
				}
				
				$orderid = str_pad($oid, 20);
				$total_items = sprintf("%02d", $total_items);
				$ival = sprintf("%02d", $i);
				$garcode = str_pad($orderitems['TikDetHeatSeal'], 20);  
				//$garment_name = str_pad($orderitems['TikDetFreeFlowDesc'], 30); 
				$garment_name = mb_str_pad($orderitems['TikDetFreeFlowDesc'], 30); 
			
				//$garment_name = $orderitems['TikDetFreeFlowDesc'];
				$garment_per=$orderitems['garment_percent'];
				$hung='S';
				$garment_status=str_pad('R', 56);
				
				$customer_info= ';'.$oid.';In '.date('d/m/Y',strtotime($orderitems['TikDateIn'])).'  Ready '.date('d/m/Y',strtotime($orderitems['TikOrderReadyDate'])).';'.$orderitems['TikLastName'].', '.$orderitems['TikFirstName'].' ;'.$orderitems['TikPhone'].';';
				
				$indata.=$orderid.$total_items.$ival.$garcode.$garment_name.$garment_per.$hung.$garment_status.$customer_info;
				
				
				/*$orderid = str_pad($oid, 20);
				$total_items = sprintf("%02d", $total_items);
				$ival = sprintf("%02d", $i);
				$garcode = str_pad($orderitems['TikDetHeatSeal'], 20);  
				//$garment_name = str_pad($orderitems['TikDetFreeFlowDesc'], 30); 
				$garment_name = mb_str_pad($orderitems['TikDetFreeFlowDesc'], 30); 
			
				//$garment_name = $orderitems['TikDetFreeFlowDesc'];
				$garment_per=$orderitems['garment_percent'];
				$hung='S';
				$garment_status=str_pad('A', 11);
				
				//$customer_info= ';'.$oid.';In '.date('d/m/Y',strtotime($orderitems['TikDateIn'])).'  Ready '.date('d/m/Y',strtotime($orderitems['TikOrderReadyDate'])).';'.$orderitems['TikLastName'].', '.$orderitems['TikFirstName'].' ;'.$orderitems['TikPhone'].';';
				
				$indata.=$orderid.$total_items.$ival.$garcode.$garment_name.$garment_per.$hung.$garment_status;*/
				
				
				if(count($orderlines) > $i) 
				{
					$indata.="\n";
				}
				
				$temp = $orderid;
				
				$i++;
			}
		
			 
			
			//$writedata="\n";
			$writedata=$indata;
			$date = date('Ymd').'_'.time();
			$filename = 'automat_'.$date.'.SMART';
			
			if ( !write_file('sorting/branch/'.$filename,$writedata,'w')){
				return false;
			}
			else
			{
				
				 $download_path ='sorting/branch/';
				 if($filename){
					$this->load->helper('download');
					$data = file_get_contents($download_path.$filename); // Read the file's contents
					$name = $filename;
					force_download($name, $data);
				  }	
				return $filename;
			}
		}
		else
		{
			echo 'Records Not found';
		}
		
	}
	 
	 
	 function get_product_types($product)
	{
	//a_product_additional_product.additional_product
			$sql="SELECT a_additional_product.id as id,a_additional_product.name FROM 
			
			a_product 
			LEFT JOIN a_product_additional_product ON a_product_additional_product.product=a_product.id
			LEFT JOIN a_additional_product ON a_additional_product.id=a_product_additional_product.additional_product
			WHERE a_product.id='".$product."' AND a_product_additional_product.status='1' ORDER BY a_product_additional_product.sort_order ASC";
			
			
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
	
	function getBaginfo($barcode)
	{
		$query=$this->db->query("SELECT * FROM a_bag
					WHERE bag_barcode='".$barcode."' AND status='1' ORDER BY id DESC LIMIT 0,1");
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
	
	function getOrderlogstatus($orderid,$order_status)
	{
		$query=$this->db->query("SELECT * FROM a_order_log WHERE `order`='".$orderid."' AND status='".$order_status."'");
		if($query->num_rows() == 0)
		{
			return 1;
		}
		else
		{
			return 0;
		}
			
	}
	
	/*craete Infile*/ 
	function createInfiledownload()
	{

		$addsql="";

		$orderids=array();
		if(count($orderids) > 0)
		{
			$orderidss=implode(',',$orderids);
			$addsql.=" AND a_order.id IN(".$orderidss.")";
		}
		
		$sql="SELECT a_order.id as orderid,a_order.partner_branch,a_orderline.product,CASE
		WHEN a_orderline.changed_quantity IS NULL THEN a_orderline.quantity
		 ELSE a_orderline.changed_quantity
		  END  AS quantity,
		  a_orderline.id as orderline
		 FROM a_order,a_orderline,a_order_log
		WHERE a_orderline.order=a_order.id AND  a_order.id = a_order_log.order AND  a_order.type='shop' AND a_order.in_status='0' AND a_order_log.order = (SELECT aol.order FROM a_order_log as aol WHERE aol.id = (SELECT max(ol.id) as ID FROM a_order_log as ol WHERE  a_order.id = ol.order) AND aol.status = 5) $addsql GROUP BY a_orderline.id ORDER BY a_order.id";
	
		$query=$this->db->query($sql);
	    $orderlines = $query->result_array();
		
		//print_r($orderlines);
	
		$orderlinearray=array();
		$orderlinearray1=array();
		$orderarray=array();
		$orderarray1=array();
		$olarray= array();
		$j = 0;
		
		
		if(count($orderlines) > 0)
		{
			foreach($orderlines as $oritems)
			{
				
				$heatsealstatus=$this->process_order_model->getHeatsealStatus($oritems['product'],$oritems['partner_branch']);
				if($heatsealstatus['heatseal'] == 1){
					
					$scanqty = $this->process_order_model->getHeatOrderLine($oritems['orderline'],1);
	
					$proqty=0;
					if($heatsealstatus['heatseal'] == 1)
					{
						$proqty = $this->process_order_model->validateProducttype($oritems['product']);
					}
					$actualqty=$oritems['quantity'] * $proqty;
					$orderarray[$oritems['orderid']][]=$scanqty;
					$orderarray1[$oritems['orderid']][]=$actualqty;
					if($scanqty == $actualqty && $scanqty > 0 && $actualqty > 0){
						if (!in_array($oritems['orderline'], $olarray)){
							$olarray[$j] = $oritems['orderline'];
							$j++;
						}
					}
				}
			}
		}
		
		if(count($olarray) > 0)
		{
			$ols=implode(',',$olarray);
			$addsql.=" AND a_orderline.id IN(".$ols.")";
		}
		else
		{
			echo 'Records Not found';exit;
		}
			
		
	$sql="SELECT a_order.id AS orderid,a_order.order_time,a_customer.firstname,a_customer.lastname,CONCAT(IFNULL(a_customer.firstname,''),', ',IFNULL(a_customer.lastname,'')) AS customer_name,a_orderline.regtime,a_orderline.p_b_delivery_time,a_heat_seal.heat_seal_barcode,
	a_phone.number as phone,a_product.name,p.name as partner,pb.name as partner_branch,
	CASE
		  WHEN a_orderline.changed_quantity IS NULL THEN a_orderline.quantity
		  ELSE a_orderline.changed_quantity
		  END  AS quantity,
		  a_orderline.id as orderline,
		  a_heat_seal_log.id as logid,
		  a_heat_seal_log.employee_p_branch,
		  a_heat_seal_log.company,
		  a_heat_seal_log.heat_seal,
		   a_heat_seal_log.status,
		  a_order.customer
	FROM a_heat_seal,a_heat_seal_log,a_orderline
	LEFT JOIN a_order ON a_orderline.order=a_order.id
	LEFT JOIN a_customer ON a_customer.id=a_order.customer
	LEFT JOIN a_phone ON a_phone.customer=a_customer.id
	LEFT JOIN a_product ON a_product.id=a_orderline.product
	LEFT JOIN a_partner_branch as pb ON a_order.partner_branch=pb.id
	LEFT JOIN a_partner as p ON pb.partner=p.id
	WHERE a_order.type='shop' AND a_order.in_status='0' AND a_orderline.id=a_heat_seal_log.orderline AND a_heat_seal.id=a_heat_seal_log.heat_seal $addsql ORDER BY a_order.id";	
	
		$query=$this->db->query($sql);
		$orderlines = $query->result_array();
		
		
		$indata='';
		$finalorderids=array();
		if(count($orderlines) > 0)
		{	
			$i=1;
			$temp = 0;
			foreach($orderlines as $orderitems)
			{
				if($orderitems['heat_seal_barcode'] == '')
				{
					continue;
				}
				
				$oid=$orderitems['orderid'];
				$total_items = array_sum($orderarray1[$orderitems['orderid']]);
				
				
				if(intval($temp) != intval($oid)){
					$i=1;
				}
				
				$orderid = str_pad($oid, 20);
				$orderid = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $orderid); // remove empty space when creating a file in newline
				$total_items = sprintf("%02d", $total_items);
				$ival = sprintf("%02d", $i);				
				$garcode = $total_items.$ival.$orderitems['heat_seal_barcode'];
				$garcode = str_pad($garcode, 24);  
				
				$garment_name = mb_str_pad($orderitems['name'], 30); 
				$garment_per='033';
				$hung='S';
				$garment_status=str_pad('R', 56);
				
				$customer_info= ';'.$oid.';In '.date('d/m/Y',strtotime($orderitems['regtime'])).'  Ready '.date('d/m/Y',strtotime($orderitems['p_b_delivery_time'])).';'.$orderitems['lastname'].', '.$orderitems['firstname'].' ;'.$orderitems['phone'].';';
				
				$finalorderids[$orderid]=$orderid;
				$indata.=$orderid.$garcode.$garment_name.$garment_per.$hung.$garment_status.$customer_info;
				
				//washing
				$addsql=""; 
				//update process status
				$addsql=""; 
				if($orderitems['company'] != '')
				{
					//$addsql.=",company='".$orderitems['company']."'";
					$insertdata[]=array(
					'heat_seal'=>$orderitems['heat_seal'],
					'orderline'=>$orderitems['orderline'],
					'status'=>5,
					'regtime'=>date('Y-m-d H:i:s'),
					'customer'=>$orderitems['customer'],
					'employee_p_branch'=>$orderitems['employee_p_branch'],
					'company'=>$orderitems['company']
					);	
				}
				else
				{
					$insertdata[]=array(
					'heat_seal'=>$orderitems['heat_seal'],
					'orderline'=>$orderitems['orderline'],
					'status'=>5,
					'regtime'=>date('Y-m-d H:i:s'),
					'customer'=>$orderitems['customer'],
					'employee_p_branch'=>$orderitems['employee_p_branch']);	
				}
				
				if(count($orderlines) > $i) 
				{
					$indata.="\n";
				}
				
				$temp = $orderid;
				
				if(count($orderlines) == $i){
					break;
				}
				$i++;
			}
			
			
			//$writedata="\n";
			$writedata=$indata;
			$date = date('Ymd').'_'.time();
			$filename = 'automat_'.$date.'.SMART';
			
			if ( !write_file('sorting/branch/'.$filename,$writedata,'w')){
				return false;
			}
			else
			{
				
				if(count($finalorderids) > 0)
				{
					$forderid=implode(',',$finalorderids);
					$qry="UPDATE a_order SET in_status='1' WHERE a_order.id IN(".$forderid.")";
					$this->db->query($qry);	
				}
		
				if(count($insertdata) > 0)
                {
					//insert log for in process status to all heal seals in single query
					$this->db->insert_batch('a_heat_seal_log', $insertdata);  
                }
				
				
				return $filename;
			}
		}
		else
		{
			echo 'Records Not found';
		}
	}
	
	function updateMissfoundStatus($heat_sealid,$status,$orderline)
	{
		$query=$this->db->query("SELECT * FROM a_heat_seal_log WHERE heat_seal='".$heat_sealid."' AND orderline='".$orderline."' ORDER BY id DESC LIMIT 0,1");
		$results = $query->row_array(); //multi row array
		$heat_seal=$results['heat_seal'];
		$orderline=$results['orderline'];
		$customer=$results['customer'];
		if(intval($customer) == '0')
		{
			//$customer = $this->session->userdata['customer']['id'];
			$qry="SELECT b.customer FROM a_orderline a,a_order b WHERE a.id='".$orderline."' AND a.order=b.id";
			$query=$this->db->query($qry);	
			$result = $query->row_array();
			$customer=$result['customer'];
		}
		$addsql="";
		
		if($this->session->userdata['partner_branch'] == 14 || $this->session->userdata['partner_branch'] == 28 || $this->session->userdata['company_status'])
		{
			
			$addsql.=",company='".$this->session->userdata['customer']['company']."'";
		}
		
		$addsql.= ",customer='".$customer."'";
		$qry="INSERT INTO a_heat_seal_log SET `heat_seal`='".$heat_seal."',`orderline`='".$orderline."',`status`='".$status."',`regtime`='".date('Y-m-d H:i:s')."',employee_p_branch='".$this->session->userdata('employee_p_branch')."'$addsql";	
		$query=$this->db->query($qry);						
		return $this->db->insert_id();
				
	}
	
	function heatsealCustomerstatus($barcode,$orderline)
	{	
		//$customer = $this->session->userdata['customer']['id'];
		if($this->session->userdata['partner_branch'] == 14 || $this->session->userdata['partner_branch'] == 28 || $this->session->userdata['company_status'])
		{
			$customer=$this->session->userdata['customer']['id'];
		}
		else
		{
			
			$qry="SELECT b.customer FROM a_orderline a,a_order b WHERE a.id='".$orderline."' AND a.order=b.id";
			$query=$this->db->query($qry);	
			$result = $query->row_array();
			$customer=$result['customer'];
		}
		
		
		$query=$this->db->query("SELECT * FROM a_heat_seal WHERE heat_seal_barcode='".$barcode."'");	
		if($query->num_rows() > 0)
		{
			$results = $query->row_array(); //multi row array
			$heat_sealid=$results['id'];
			$query=$this->db->query("SELECT a_customer.id as customer,CONCAT(IFNULL(firstname,''),' ',IFNULL(lastname,'')) AS name FROM a_heat_seal_log 
			LEFT JOIN a_customer ON a_heat_seal_log.customer=a_customer.id
			WHERE heat_seal='".$heat_sealid."' 
			ORDER BY a_heat_seal_log.id DESC LIMIT 0,1");
			if($query->num_rows() > 0)
			{
				$results = $query->row_array(); //multi row array
				if($results['customer'] == $customer)
				{
					return false;
				}
				else
				{
				
					
					return $results;
				}
			}
		}
		
		return false;
		
		
		
		/*$qry="SELECT a_order.customer FROM a_orderline 
		LEFT JOIN a_order ON a_orderline.order=a_order.id
		WHERE a_orderline.id='".$orderline."'";
		$query=$this->db->query($qry);	
		$result = $query->row_array();
		
		if($result['customer'] == $customer)
		{
			return false;
		}
		else
		{
			return true;
		}*/
		
	}
	
	function deleteHeatseal($id)
	{
		$sql="DELETE FROM `a_heat_seal_log` WHERE `a_heat_seal_log`.`id` = '".$id."'";
		if($this->db->query($sql))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	
	function updateHeatsealstatus($orderlineheatdata,$status,$orderid)
	{
		if(count($orderlineheatdata) > 0)
		{
			foreach($orderlineheatdata as $heatdata)
			{
				if(intval($heatdata['heatid']) > 0 && intval($heatdata['id']) > 0)
				{
					$addsql=",customer='".$this->session->userdata['pos_customer_id']."'";
					if(intval($heatdata['customer']) > 0)
					{	
						$addsql=",customer='".$heatdata['customer']."'";
					}
					
					$qqry=$this->db->query("SELECT * FROM a_heat_seal_log WHERE heat_seal='".$heatdata['heatid']."' AND orderline='".$heatdata['id']."' AND status='".$status."'");
					 if($qqry->num_rows() == 0)
					 {
						$qry="INSERT INTO a_heat_seal_log 
						SET 
						heat_seal='".$heatdata['heatid']."',
						orderline='".$heatdata['id']."',
						status='".$status."',
						regtime='".date('Y-m-d H:i:s')."',
						employee_p_branch='".$this->session->userdata('employee_p_branch')."'$addsql";
						$this->db->query($qry);
					 }
					 
					
				}
				
					
						
				
				
				
				
			}			
		}
	}
	
	function updateOrderlogstatus($orderid,$olid,$status)
	{
	//echo "SELECT * FROM a_order_log WHERE orderline='".$olid."' AND status='".$status."'";exit;
		$qqry=$this->db->query("SELECT * FROM a_order_log WHERE orderline='".$olid."' AND status='".$status."'");
		
		//echo $qqry->num_rows();exit;
					 if($qqry->num_rows() == 0)
					 {
						$qry="INSERT INTO a_order_log 
						SET 
						orderline='".$olid."',
						partner='".$this->session->userdata['partner']."',
						partner_branch='".$this->session->userdata['partner_branch']."',
						employee_p_branch='".$this->session->userdata('employee_p_branch')."',
						regtime='".date('Y-m-d H:i:s')."',
						status='".$status."'";
						$query=$this->db->query($qry);
					 }
		return true;
	}
	
	function cancelorderline($orderid,$orderline,$status,$cancelreason,$comment)
	{

		if(intval($orderline) > 0)
		{
			$sql="INSERT INTO a_order_canceled 
			SET 
			orderline='".$orderline."',
			partner_branch='".$this->session->userdata['partner_branch']."',
			employee_p_branch='".$this->session->userdata('employee_p_branch')."',
			canceled_reason='".$cancelreason."',
			comment='".$comment."',
			status='".$status."',
			regtime='".date('Y-m-d H:i:s')."'";
		}
		else
		{
			$sql="INSERT INTO a_order_canceled 
			SET 
			`order`='".$orderid."',
			orderline=NULL,
			partner_branch='".$this->session->userdata['partner_branch']."',
			employee_p_branch='".$this->session->userdata('employee_p_branch')."',
			canceled_reason='".$cancelreason."',
			comment='".$comment."',
			status='".$status."',
			regtime='".date('Y-m-d H:i:s')."'";
		}
	
		$this->db->query($sql);
		return $this->db->insert_id();
		
	}
	
	function updateInstatus()
	{

		$addsql="";

		$orderids=array();
		if(count($orderids) > 0)
		{
			$orderidss=implode(',',$orderids);
			$addsql.=" AND a_order.id IN(".$orderidss.")";
		}
		
		$sql="SELECT a_order.id as orderid,a_order.partner_branch,a_orderline.product,CASE
		WHEN a_orderline.changed_quantity IS NULL THEN a_orderline.quantity
		 ELSE a_orderline.changed_quantity
		  END  AS quantity,
		  a_orderline.id as orderline
		 FROM a_order
		LEFT JOIN a_orderline ON a_orderline.order=a_order.id
		LEFT JOIN a_order_log ON a_order.id = a_order_log.order
		WHERE a_order.type='shop' AND a_order.in_status='0' AND (a_order_log.id) in (SELECT id FROM a_order_log WHERE id IN(SELECT max(id) as id FROM a_order_log GROUP BY `order`) AND `status` = 5 ORDER by id DESC) $addsql ORDER BY a_order.id";
	
		
		$query=$this->db->query($sql);
	    $orderlines = $query->result_array();
		 
		
	
		$orderlinearray=array();
		$orderlinearray1=array();
		$orderarray=array();
		$orderarray1=array();
		if(count($orderlines) > 0)
		{
			foreach($orderlines as $oritems)
			{
				$scanqty = $this->process_order_model->getHeatOrderLine($oritems['orderline'],1);
				
				
				$heatsealstatus=$this->process_order_model->getHeatsealStatus($oritems['product'],$oritems['partner_branch']);
			
				$proqty=0;
				if($heatsealstatus['heatseal'] == 1)
				{
					$proqty = $this->process_order_model->validateProducttype($oritems['product']);
				}
				$actualqty=$oritems['quantity'] * $proqty;
				$orderarray[$oritems['orderid']][]=$scanqty;
				$orderarray1[$oritems['orderid']][]=$actualqty;
			}
		}
		
			
		
		$neworderids=array();
		if(count($orderarray) > 0)
		{
			foreach($orderarray as $orderkey=>$orderitems)
			{
				$scanqty=array_sum($orderarray[$orderkey]);
				$actualqty=array_sum($orderarray1[$orderkey]);
				if($scanqty == $actualqty && $scanqty > 0 && $actualqty > 0)
				{
					$neworderids[$orderkey]=$orderkey;
				}
			}
		}
		
		
		
		//validate total qty and heatseal scan qty 
		$addsql="";
		if(count($neworderids) > 0)
		{
			$orderidss=implode(',',$neworderids);
			$addsql.=" AND a_order.id IN(".$orderidss.")";
			
			
			
		}
		
			
		
	$sql="SELECT a_order.id AS orderid,a_order.order_time,a_customer.firstname,a_customer.lastname,CONCAT(IFNULL(a_customer.firstname,''),', ',IFNULL(a_customer.lastname,'')) AS customer_name,a_orderline.regtime,a_orderline.p_b_delivery_time,a_heat_seal.heat_seal_barcode,
	a_phone.number as phone,a_product.name,p.name as partner,pb.name as partner_branch,
	CASE
		  WHEN a_orderline.changed_quantity IS NULL THEN a_orderline.quantity
		  ELSE a_orderline.changed_quantity
		  END  AS quantity,
		  a_orderline.id as orderline,
		  a_heat_seal_log.id as logid,
		  a_heat_seal_log.employee_p_branch,
		  a_heat_seal_log.company,
		  a_heat_seal_log.heat_seal,
		   a_heat_seal_log.status,
		  a_order.customer
	FROM a_order
	LEFT JOIN a_customer ON a_customer.id=a_order.customer
	LEFT JOIN a_phone ON a_phone.customer=a_customer.id
	LEFT JOIN a_orderline ON a_orderline.order=a_order.id
	LEFT JOIN a_heat_seal_log ON a_orderline.id=a_heat_seal_log.orderline
	LEFT JOIN a_heat_seal ON a_heat_seal.id=a_heat_seal_log.heat_seal
	LEFT JOIN a_product ON a_product.id=a_orderline.product
	LEFT JOIN a_partner_branch as pb ON a_order.partner_branch=pb.id
	LEFT JOIN a_partner as p ON pb.partner=p.id
	WHERE a_order.type='shop' AND a_order.in_status='0' AND a_heat_seal_log.id in (SELECT id FROM a_heat_seal_log WHERE id IN(SELECT max(id) as id FROM a_heat_seal_log GROUP BY `heat_seal`) AND `status` = 5 ORDER by id DESC) $addsql ORDER BY a_order.id";

 
			$query=$this->db->query($sql);
			$orderlines = $query->result_array();

	
		$indata='';
		$finalorderids=array();
		if(count($orderlines) > 0)
		{	
				$i=1;
				$temp = 0;
				foreach($orderlines as $orderitems)
				{
					$finalorderids[$orderitems['orderid']]=$orderitems['orderid'];
					$i++;
				}
				
			
				if(count($finalorderids) > 0)
				{
					$forderid=implode(',',$finalorderids);
					$qry="UPDATE a_order SET in_status='1' WHERE a_order.id IN(".$forderid.")";
					$this->db->query($qry);	
				}
		
				
			
		}
		else
		{
			echo 'Records Not found';
		}
	}
	
	
	
	/*downloading in file */ 
	function createDeleteInfile()
	{
		$addsql="";
		
		$type = $this->input->post('download_type');  // in file type create / delete
		
		$orderids = explode(",",$this->input->post('oid'));
		
		if(count($orderids) > 0)
		{
			$orderidss=implode(',',$orderids);
			$addsql.=" AND a_order.id IN(".$orderidss.")";
		}
	
	
	$query=$this->db->query("SELECT a_order.id AS orderid,a_order.order_time,a_customer.firstname,a_customer.lastname,CONCAT(IFNULL(a_customer.firstname,''),', ',IFNULL(a_customer.lastname,'')) AS customer_name,a_orderline.regtime,a_orderline.p_b_delivery_time,a_heat_seal.heat_seal_barcode, a_phone.number as phone,a_product.name, CASE WHEN a_orderline.quantity IS NULL THEN a_orderline.quantity ELSE a_orderline.quantity END AS quantity, a_orderline.id as orderline,DATE_FORMAT(order_time,'%d.%m.%Y') as odate,DATE_FORMAT(order_time,'%H:%i') as otime,DATE_FORMAT(ctime.start_time,'%d/%m/%y') as collection_time,DATE_FORMAT(dtime.start_time,'%d/%m/%y') as delivery_time ,a_address.zip
	 FROM a_order 
LEFT JOIN a_customer ON a_customer.id=a_order.customer 
LEFT JOIN a_phone ON a_phone.customer=a_customer.id 
LEFT JOIN a_address ON a_address.customer=a_customer.id AND a_address.main='1'
LEFT JOIN a_orderline ON a_orderline.order=a_order.id 
LEFT JOIN a_heat_seal_log ON a_orderline.id=a_heat_seal_log.orderline 
LEFT JOIN a_heat_seal ON a_heat_seal.id=a_heat_seal_log.heat_seal 
LEFT JOIN a_product ON a_product.id=a_orderline.product
LEFT JOIN a_collection_time as ctime ON ctime.id = a_order.collection_time 
LEFT JOIN a_collection_time as dtime ON dtime.id = a_order.delivery_time
 WHERE a_order.type='shop' AND a_heat_seal_log.id in (SELECT MAX(id) FROM `a_heat_seal_log` WHERE 1=1 AND a_orderline.id=a_heat_seal_log.orderline GROUP BY `heat_seal` ORDER by id ASC) $addsql ORDER by a_heat_seal_log.id ASC");
	
		
		$orderlines = $query->result_array();
		
		$indata='';
		if(count($orderlines) > 0)
		{	
			$i=1;
			$temp = 0;
			
			
			foreach($orderlines as $orderitems)
			{
				if($orderitems['heat_seal_barcode'] == '')
				{
					continue;
				}
				
				//create item in file
				if($type > 0){
					
					$order_id = $orderitems['orderid'];
					$oid=$orderitems['orderid'];
					$oid=sprintf('%08d', $oid);
					$oid = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $oid); // remove empty space when creating a file in newline
					$total_items = count($orderlines);
					if(intval($temp) != intval($oid)){
						$i=1;
					}
					$orderid = str_pad($oid, 20);
					$total_items = sprintf("%02d", $total_items);
					$ival = sprintf("%02d", $i);
					$garcode = str_pad($orderitems['heat_seal_barcode'], 20);  
					$garment_name = str_pad($orderitems['name'], 30); 
					
					$garment_per='033';
					$hung='S';
					$garment_status=str_pad('R', 56);
					
					
					//$customer_info= ';'.$oid.';In '.$orderitems['collection_time'].'  Ready '.$orderitems['delivery_time'].';'.$orderitems['lastname'].', '.$orderitems['firstname'].', '.$orderitems['zip'].' ;'.$orderitems['phone'].';';
					
					
					$customer_info= ';'.$oid.';In '.date('d/m/Y',strtotime($orderitems['regtime'])).'  Ready '.date('d/m/Y',strtotime($orderitems['p_b_delivery_time'])).';'.$orderitems['lastname'].', '.$orderitems['firstname'].' ;'.$orderitems['phone'].';';
					
					
					$indata.=$orderid.$total_items.$ival.$garcode.$garment_name.$garment_per.$hung.$garment_status.$customer_info;
				}
			else{
					
				//delete an  order
				$oid = $orderitems['orderid'];
				$orderid = str_pad($oid, 20);
				$orderid = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $orderid); // remove empty space when creating a file in newline
				$total_items = count($orderlines);
				$total_items = sprintf("%02d", $total_items);
				$ival = sprintf("%02d", $i);
				$garcode = str_pad($orderitems['heat_seal_barcode'], 20);  
				$garment_name = str_pad($orderitems['name'], 30); 
			
				$garment_per='033';
				$hung='S';
				$garment_status=str_pad('A', 11);
				
				$indata.=$orderid.$total_items.$ival.$garcode.$garment_name.$garment_per.$hung.$garment_status;
				
			}
				
				
				if(count($orderlines) > $i) 
				{
					$indata.="\n";
				}
				
				$temp = $orderid;
				
				$i++;
			}
		
				
			//$writedata="\n";
			$writedata=$indata;
			$date = date('Ymd').'_'.time();
			$filename = 'automat_'.$order_id.'.SMART';
			
			if ( !write_file('sorting/'.$filename,$writedata,'w')){
				return false;
			}
			else
			{
				  $download_path ='sorting/';
				 if($filename){
					$this->load->helper('download');
					$data = file_get_contents($download_path.$filename); // Read the file's contents
					$name = $filename;
					force_download($name, $data);
				}	
				return $filename;
			}
		}
		else
		{
			echo 'Records Not found';
		}
	}
	
	
	
	
	
}

/* End of file process_order_model.php */
/* Location: ./application/models/process_order_model.php */
