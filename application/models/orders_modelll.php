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
class Orders_model extends Super_Model{

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


		/* Get customer order history*/
		function getCustomerOrder($customer,$orderid='')
		{
			
			$customerdata=$this->customer_model->getCustomerinfo($customer);
			$destination_addresses=$customerdata['street_line_1'];
			if($customerdata['street_line_2'] != '')
			{
				$destination_addresses.=' '.$customerdata['street_line_2'];
			}
			$destination_addresses.=' '.$customerdata['zip'];
			$destination_addresses.=' '.$customerdata['city'];
			$destination_addresses = str_replace(' ','+',$destination_addresses);
			$geocodeTo = file_get_contents('http://maps.google.com/maps/api/geocode/json?address='.$destination_addresses.'&sensor=false');
			$outputTo = json_decode($geocodeTo);
			$latitude = $outputTo->results[0]->geometry->location->lat;
			$longitude = $outputTo->results[0]->geometry->location->lng;
		
			// AND `type`='".$type."' AND device='".$device."'
			$conditional_sql='';			
			if(intval($orderid) > 0)
			{				
				$conditional_sql = " AND a.id='".$orderid."'";			
			}
			
			$qry="SELECT a.id,a.customer,
			b.start_time as collection_start_time,
			b.end_time as collection_end_time,
			c.start_time as delivery_start_time,
			c.end_time as delivery_end_time,
			a.total_amount,
			a.order_time,
			a.payment_type,
			d.payment_id,
			a.voucher,
			a.delivery_note,
			a.special_instruction,
			a.payment_status,
			a.pubnub_channel_id as channel_id
			FROM a_order as a
			LEFT JOIN a_collection_time as b ON a.collection_time=b.id
			LEFT JOIN a_collection_time as c ON a.delivery_time=c.id
			LEFT JOIN a_customer_payment as d ON a.customer_payment=d.id
			WHERE a.customer='".$customer."'  $conditional_sql ORDER BY a.order_time DESC";
			
	
							
			
			$query=$this->db->query($qry);	
			$result=$query->result_array();
			
			//$process=array('1','2','3','4','5','6','7','8');
			//$completed=array('9','10','11','12');
			
			$purchase=array('1','2');
			$collect=array('3','4');
			$inprocess=array('5','6','7','8','10','12');
			$completed=array('9','11');
			
			$mainarray=array('1','3','5','9');
			
			
			$orderhistory=array();
			$orderhistory['current']=array();
			$orderhistory['completed']=array();
			if(count($result) > 0)
			{
			
			
				foreach($result as $order)
				{
					$order['latitude']=$latitude;
					$order['longitude']=$longitude;
					$amount=$order['total_amount'];
					$voucher=$order['voucher'];
					$conditional_sql = " AND v.id='$voucher' AND v.status='1' AND startdate >= NOW() <= enddate AND vc.voucher = v.id AND vc.customer= $customer AND vc.status='proceed' AND v.min_amount <= $amount";
					$query = $this->db->query("SELECT name,description,price,percentage,free_delivery_charge,min_amount
                                          FROM  a_voucher as v,a_voucher_customer vc
										  WHERE 1=1 $conditional_sql");

					$vresults = $query->row_array(); 
					
					$order['voucher']=$vresults;
					
					$qry1="SELECT a_order_log.status as id,a_order_log.regtime ,a_order_log_status.name,a_order_log_status.name_nb_no FROM a_order_log
					LEFT JOIN a_order_log_status ON a_order_log.status=a_order_log_status.id
					 WHERE `order`='".$order['id']."' ORDER BY a_order_log.id DESC LIMIT 0,1";
					$query1=$this->db->query($qry1);	
					$res=$query1->row_array();
					$res['days']=self::timeAgo($res['regtime']);
					$res['days']=$res['name_nb_no'].' '.$res['days'];
					
					if(in_array($res['id'],$mainarray))
					{
						$res['process']=array_search($res['id'], $mainarray);
						$order['status']['main']=$res;
						$order['status']['sub']=null;
					}
					else
					{
						if(in_array($res['id'],$purchase))
						{
							$mainres=self::getMainlogstatus(1);
							$mainres['process']=array_search(1, $mainarray);
							$mainres['regtime']=null;							
							$order['status']['main']=$mainres;
							$order['status']['sub']=$res;
						}
						
						
						if(in_array($res['id'],$collect))
						{
							$mainres=self::getMainlogstatus(3);
							$mainres['process']=array_search(3, $mainarray);
							$mainres['regtime']=null;
							$order['status']['main']=$mainres;
							$order['status']['sub']=$res;
						}
						
						if(in_array($res['id'],$inprocess))
						{
							$mainres=self::getMainlogstatus(5);
							$mainres['process']=array_search(5, $mainarray);
							$mainres['regtime']=null;
							$order['status']['main']=$mainres;
							$order['status']['sub']=$res;
						}
						
						if(in_array($res['id'],$completed))
						{
							$mainres=self::getMainlogstatus(9);
							$mainres['process']=array_search(9, $mainarray);
							$mainres['regtime']=null;
							$order['status']['main']=$mainres;
							$order['status']['sub']=$res;
						}
					}
					
					
					
					
					$sqry="SELECT sum(quantity) as qty from a_orderline
							WHERE `order`='".$order['id']."'";
							$query2=$this->db->query($sqry);	
							$res2=$query2->row_array();
							
							$order['quantity']=$res2['qty'];
							$order['status_name']=$res['name'];
							$order['status_name_nb_no']=$res['name_nb_no'];
							
							$sqry="SELECT a_product.id,a_orderline.quantity,
							a_orderline.amount,
							a_product.name,
							CONCAT('".PATH_IMAGE_FOLDER."',a_images.path) AS image 
							 FROM a_orderline 
							LEFT JOIN a_product ON a_product.id=a_orderline.product
							LEFT JOIN a_images ON a_images.product=a_orderline.product
							WHERE a_orderline.order='".$order['id']."'";
							
							$query2=$this->db->query($sqry);	
							$res2=$query2->result_array();
							
							
							$collection_start_time=$order['collection_start_time'];
							$collection_end_time=$order['collection_end_time'];
							$delivery_start_time=$order['delivery_start_time'];
							$delivery_end_time=$order['delivery_end_time'];
					
							$date=date('Y-m-d',strtotime($collection_start_time));
						
							//$day=date('D',strtotime($date));
							//$weekdayarray=array("Mon"=>"Man","Tue"=>"Tirs","Wed"=>"Ons","Thu"=>"Tors","Fri"=>"Fre","Sat"=>"lør","Sun"=>"Søn");
							
							$weekdayarray=array("Monday"=>"Mandag","Tuesday"=>"Tirsdag","Wednesday"=>"Onsdag","Thursday"=>"Torsdag","Friday"=>"Fredag","Saturday"=>"Lørdag","Sunday"=>"Søndag");
							
							$day=date('l',strtotime($date));
							$order['collection']['day']=$weekdayarray[$day];
							$order['collection']['date']=$date;
							$order['collection']['time']=date('H:i:s',strtotime($collection_start_time)).' - '.date('H:i:s',strtotime($collection_end_time));
							
							$date=date('Y-m-d',strtotime($delivery_start_time));
							//$day=date('D',strtotime($date));
							$day=date('l',strtotime($date));
							//$weekdayarray=array("Mon"=>"Man","Tue"=>"Tirs","Wed"=>"Ons","Thu"=>"Tors","Fri"=>"Fre","Sat"=>"lør","Sun"=>"Søn");							
							$order['delivery']['day']=$weekdayarray[$day];
							$order['delivery']['date']=$date;
							$order['delivery']['time']=date('H:i:s',strtotime($delivery_start_time)).' - '.date('H:i:s',strtotime($delivery_end_time));
					
							unset($order['collection_start_time']);
							unset($order['collection_end_time']);
							unset($order['delivery_start_time']);
							unset($order['delivery_end_time']);
					$order['products']=$res2;											
					if(intval($orderid) > 0)				
					{					
						$orderhistory=$order;									
					}				
					else
					{										
						if(in_array($res['id'],$purchase))					
						{						
							$orderhistory['current'][]=$order;						
						}															
						if(in_array($res['id'],$collect))					
						{						
							$orderhistory['current'][]=$order;						
						}										
						if(in_array($res['id'],$inprocess))					
						{						
							$orderhistory['current'][]=$order;						
						}										
						if(in_array($res['id'],$completed))					
						{						
							$orderhistory['completed'][]=$order;					
						}								
					}				
	
					
					
					
				
				}
				
				return $orderhistory;
				
			}
			else
			{
				return null;
			}
			
		}
		
		
		/* Get customer order history*/
		function getCustomerOrderhistory($customer,$type ='all',$infotype='')
		{
			$add_sql='';
			$conditional_sql ="";
			
			if($infotype == '')
			{
				if($this->session->userdata['logged_in'] == 'shop'){ 
				$conditional_sql .=" AND a.type = 'shop'";
				$current_partner = $this->session->userdata['partner'];
				$conditional_sql .=" AND a.partner = '".$current_partner."'";
				
				}else{
					$conditional_sql .=" AND (a.type = 'web' OR a.type = 'app')";
				}
			}
			
			
			
			
			
			$orderby = 'ORDER BY a.order_time DESC';
	
			if($type=='utlevering'){
				$add_sql='LEFT JOIN a_order_log ON a.id = a_order_log.order';
				$conditional_sql .=" AND (a_order_log.id) in (SELECT id FROM a_order_log WHERE id IN(SELECT max(id) as id FROM a_order_log GROUP BY `order`) AND `status` < 9 ORDER by id DESC)";
			   $orderby = 'ORDER BY a.id DESC';
				
			}
			
			if($type=='process'){
				$add_sql='LEFT JOIN a_order_log ON a.id = a_order_log.order';
				$conditional_sql .=" AND (a_order_log.id) in (SELECT id FROM a_order_log WHERE id IN(SELECT max(id) as id FROM a_order_log GROUP BY `order`) AND `status` <= 5 ORDER by id DESC)";
				$orderby = 'ORDER BY a.id DESC';
				
			}
			
			if($type=='delivered'){
				$add_sql='LEFT JOIN a_order_log ON a.id = a_order_log.order';
				$conditional_sql .=" AND (a_order_log.id) in (SELECT id FROM a_order_log WHERE id IN(SELECT max(id) as id FROM a_order_log GROUP BY `order`) AND `status` >= 9 ORDER by id DESC)";
				$orderby = 'ORDER BY a.id DESC';
				
			}
			
			$qry="SELECT a.id,a.customer,a.type,
			b.start_time as collection_start_time,
			b.end_time as collection_end_time,
			c.start_time as delivery_start_time,
			c.end_time as delivery_end_time,
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
			a.employee,
			a.pubnub_channel_id as channel_id,
			p.name as partner,
			pb.name as partner_branch,
			a.payment_status,
			DATE_FORMAT(a.order_time,'%d.%m.%Y') as odate
			FROM a_order as a
			LEFT JOIN a_collection_time as b ON a.collection_time=b.id
			LEFT JOIN a_collection_time as c ON a.delivery_time=c.id
			LEFT JOIN a_customer_payment as d ON a.customer_payment=d.id
			LEFT JOIN a_partner_branch as pb ON a.partner_branch=pb.id
			LEFT JOIN a_partner as p ON pb.partner=p.id
			$add_sql
			WHERE a.customer='".$customer."'
			$conditional_sql $orderby";
			
			
			$query=$this->db->query($qry);	
			$result=$query->result_array();
			return $result;
			
	  }
		
	
	  function getCustomerDetails($id='')
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
								   
								   
								   
		  $query = $this->db->query("SELECT a_order.customer,a_order.id,a_order.delivery_note,DATE_FORMAT(order_time,'%d.%m.%Y') as odate,DATE_FORMAT(order_time,'%H:%i') as otime,DATE_FORMAT(delivery_time,'%H:%i') as dtime,CONCAT(IFNULL(a_customer.firstname,''),' ',IFNULL(a_customer.lastname,'')) AS customer_name,a_address.street_line_1,a_address.street_line_2,a_address.floor,a_address.calling_bell,a_city.city as city,a_address.zip as zip,a_phone.number,a_email.email,a_customer.id as customerid,a_zip.zone as zone
		  							FROM a_order
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
	  
	  
	 // -- getOrderinfo ----------------------------------------------------------------------------------------------
    /**
     * return array of all the rows of orders in table
     * accepts order_by and asc/desc values
     * 
     * @param $orderby sorting
     * @param $sort sort order
     * @return	array
     */

    function getOrderinfo($id='',$infotype='')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
		$add_sql='';
		$conditional_sql ="";
		
		if($infotype == '')
		{
			if($this->session->userdata['logged_in'] == 'shop'){ 
			$conditional_sql .=" AND a_order.type = 'shop'";
			$current_partner = $this->session->userdata['partner'];
			$conditional_sql .=" AND a_order.partner = '".$current_partner."'";
			}else{
				$conditional_sql .=" AND (a_order.type = 'web' OR a_order.type = 'app')";
			}
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
        //$query = $this->db->query("SELECT *,ROUND(total_amount, 0) as total_amount,ROUND(changed_amount, 0) as changed_amount FROM a_order WHERE id='".$id."'");
		
    /*	$query = $this->db->query(" SELECT a_order_log.status as order_status,a_order.*,ROUND(total_amount, 0) as total_amount,ROUND(changed_amount, 0) as changed_amount 
		FROM a_order 
		LEFT JOIN a_order_log
ON a_order.id=a_order_log.order WHERE a_order.id='".$id."' order by a_order_log.status desc limit 0, 1");*/

	$query = $this->db->query(" SELECT a_order_log.status as order_status,a_order.*,total_amount,changed_amount
		FROM a_order 
		LEFT JOIN a_order_log
ON a_order.id=a_order_log.order WHERE a_order.id='".$id."' $conditional_sql order by a_order_log.status desc limit 0, 1");

		
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
	function getCollectionDeliverytime($type,$id)	
	{		//profiling::        
	$this->debug_methods_trail[] = __function__;       
	//declare       
	$conditional_sql = " AND c.status='1' AND(type = '".$type."' OR type = 'all') AND id='".$id."'";		
	$additional_sql = "";				
	//if no valie client id, return false        
	if (! is_numeric($id)) {           
	return false;       
	}       
	//check if sorting type was passed        
	$sort = ($sort == 'asc' || $sort == 'desc') ? $sort : 'ASC';        
	//----------sql & benchmarking start----------        $this->benchmark->mark('code_start');		//_____SQL QUERY_______       
	$query = $this->db->query("SELECT DATE_FORMAT(c.start_time,'%d.%m.%Y') as sdate,DATE_FORMAT(c.end_time,'%d.%m.%Y') as edate,TIME_FORMAT(c.start_time, '%H:%i') as stime,TIME_FORMAT(c.end_time, '%H:%i') as etime                                          FROM a_collection_time as c										  WHERE 1=1  $conditional_sql                                          ORDER BY start_time ASC");						$results = $query->row_array(); 
	//multi row array		
	//benchmark/debug        
	$this->benchmark->mark('code_end');       
	$execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');        //debugging data        
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
		
		//find customer subscribtion status
		$cus_id = $this->session->userdata['pos_customer_id'];
		$subscription = $this->payments_model->getSaldostatus($cus_id);
		
		$pricesql="CASE 
		WHEN $subscription = 0 THEN 
		  CASE 
		  WHEN pb.unsubscribed_price IS NULL THEN pb.price 
		  ELSE pb.unsubscribed_price  
		  END
	   ELSE pb.price 	  
	END AS oprice";

	
			if(intval($current_partner_branch) == 14  || intval($current_partner_branch) == 1000  && $current_partner_branch == 28) //hvittsnip branch  ( only this branch aceept orders from comapny)
			{
				$customertype=$this->session->userdata['companytype'];
				$customercompany=$this->session->userdata['company'];
				$additional_sql.=" LEFT JOIN a_company_price ON a_company_price.type='".$customertype."' AND a_company_price.company='".intval($customercompany)."' AND a_company_price.product=p.id AND a_company_price.status='1'";
				
				
				$pricesql=" CASE 
					  WHEN a_company_price.price IS NULL THEN 
					  CASE 
						WHEN $subscription = 0 THEN 
						  CASE 
							WHEN pb.unsubscribed_price IS NULL THEN pb.price 
							ELSE pb.unsubscribed_price  
							END
						  ELSE pb.price 	  
						END
					  ELSE a_company_price.price 
					  END AS oprice";
			}
		
		
		

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
		
		
		
		
		//_____SQL QUERY_______
		
		if($this->session->userdata['logged_in']!='shop'){
		
		 
       $query = $this->db->query("SELECT *,a_orderline.price as price,a_orderline.special_instruction as description,a_orderline.id as id,DATE_FORMAT(a_orderline.p_b_delivery_time,'%d.%m.%Y') as utlevering,CONCAT('".PATH_IMAGE_FOLDER."',a_images.path) as path, a_product.type as ptype,a_product.name,a_orderline.product,a_product.price as oprice
	    FROM a_orderline 
		LEFT JOIN a_product ON a_product.id=a_orderline.product 
		LEFT JOIN a_images ON a_images.product=a_product.id 
		WHERE a_orderline.order='".$id."' ORDER BY a_orderline.p_b_delivery_time ASC");
		
		}else{
			
	
        $query = $this->db->query("SELECT *,a_orderline.price as price,a_orderline.special_instruction as description,a_orderline.id as id,DATE_FORMAT(a_orderline.p_b_delivery_time,'%d.%m.%Y') as utlevering,CONCAT('".PATH_IMAGE_FOLDER."',a_images.path) as path, a_product.type as ptype,a_product.name,a_orderline.product,c.name as category,c.description as cdescription,
	$pricesql
	    FROM a_product_p_branch_category as pbc,a_category_partner as c,a_orderline 
		LEFT JOIN a_product ON a_product.id=a_orderline.product 
		LEFT JOIN a_product_p_branch as pb ON pb.product = a_orderline.product 
		LEFT JOIN a_images ON a_images.product=a_product.id 
		$additional_sql
		WHERE pbc.product_p_branch = a_orderline.product  AND  pbc.category_partner=c.id AND a_orderline.order='".$id."' GROUP BY a_orderline.id ORDER BY a_orderline.p_b_delivery_time ASC");
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
	

	//get display name of a product
	function getProductDisplayName($id='')
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
		
		$query=$this->db->query("SELECT CASE 
	  WHEN pbc.display_name IS NULL THEN a_product.name 
	  ELSE pbc.display_name
	  END  AS name 
	  FROM a_product , a_product_p_branch_category as pbc
	  WHERE  a_product.id = pbc.product_p_branch AND pbc.product_p_branch = $id ");
	  
		if($query->num_rows() > 0)
		{
			$result=$query->row_array();
			return $result;
			
		}
		
	}
	
	
	function getBagBarcodeinfo($barcode,$type)
	{
		$type='laundry';
		$query=$this->db->query("SELECT `id` FROM a_bag WHERE bag_barcode='".$barcode."' AND `type`='".$type."'");
		if($query->num_rows() > 0)
		{
			$result=$query->row_array();
			return $result;
			
		}
		else
		{
		
			$qry="SELECT a_bag.id FROM a_bag_order_log
			LEFT JOIN a_bag ON  a_bag.id=a_bag_order_log.bag
			WHERE a_bag_order_log.order='".$barcode."' AND a_bag.`type`='".$type."'";
			$query=$this->db->query($qry);
			if($query->num_rows() > 0)
			{
				$result=$query->row_array();
				return $result;
			}
			
			/*$qry="INSERT INTO a_bag SET 
			`bag_barcode`='".$barcode."',
			`type`='".$type."',
			`regtime`='".date('Y-m-d H:i:s')."',
			`status`='1'";
			$this->db->query($qry);
			$id=$this->db->insert_id();
			return array('id'=>$id);*/
			
		}
		
	}
	
	function updateBagBarcodelog($bag,$order,$barcode_status)
	{
		$qry="INSERT INTO a_bag_order_log 
						SET 
						`bag`='".$bag."',
						`order`='".$order."',
						`regtime`='".date('Y-m-d H:i:s')."',
						`status`='".$barcode_status."'";
						$query=$this->db->query($qry);
		return true;
	}
	
	function getOrderid($orderline)
	{
		$query=$this->db->query("SELECT `order` FROM a_orderline WHERE id='".$orderline."'");
		if($query->num_rows() > 0)
		{
			$result=$query->row_array();
			return $result['order'];
			
		}
		else
		{
			return false;
		}
		
	}
	
	function getBagOrder($bag)
	{
		$query=$this->db->query("SELECT `order`,id FROM a_bag_order_log WHERE bag='".$bag."' ORDER BY id DESC LIMIT 0,1");
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
	
	function updateorderlog($orderid,$order_status)
	{
	
		$query=$this->db->query("SELECT * FROM a_order_log WHERE `order`='".$orderid."' AND status='".$order_status."'");
		
		if($query->num_rows() == 0)
		{
			/*$query=$this->db->query("SELECT partner,partner_branch FROM a_order WHERE id='".$orderid."'");
			$partners=$query->row_array();
			$addsql='';
			if(intval($partners['partner']) > 0)
			{
				$partner=$partners['partner'];
				$addsql.="partner='".$partner."',";
			}
			if(intval($partners['partner_branch']) > 0)
			{
				$partner_branch=$partners['partner_branch'];
				$addsql.="partner_branch='".$partner_branch."',";
			}*/
			
			$qry="INSERT INTO a_order_log 
						SET 
						`order`='".$orderid."',
						partner='".$this->session->userdata['partner']."',
						partner_branch='".$this->session->userdata['partner_branch']."',
						employee_p_branch='".$this->session->userdata('employee_p_branch')."',
						regtime='".date('Y-m-d H:i:s')."',
						status='".$order_status."'";
						$query=$this->db->query($qry);
						
			if($order_status == 11){
				 $qry2="UPDATE a_order SET payment_status='canceled' WHERE `id`='".$orderid."'";
				 $query=$this->db->query($qry2);
			}
			
			return true;
			
		}
		else
		{
			return false;
		}
			
	}
	
	/*update utlevering*/
	function updateOrderline($orderline,$orderid,$changed_quantity,$price,$total,$complain,$in_house,$desp,$flag='',$ocancel='')
	{
	
		
		$query=$this->db->query("SELECT * FROM a_orderline WHERE `id`='".$orderline."'");
		if($query->num_rows() > 0)
		{
			 /*$orderline_info = $query->row_array();
			 $quantity =  $orderline_info['quantity'];
			 $productid=$orderline_info['product'];
			 $productinfo= $this->products_model->getProduct($productid);
			 $old_changeqty= $orderline_info['changed_quantity'];
			 if($old_changeqty != '')
			 {
				$oldamt= $old_changeqty*$price;
			 }
			 else
			 {
				$oldamt= $quantity*$price;
			 }
			 
		
			 $total=$total-$oldamt;
			  
			  $addsql="";
			  
			 if($orderline_info['payment_status'] == 'canceled')
			 {
				$addsql=",payment_status='pending'";
			 }
			 
			 $subtotal = $changed_quantity * $price;*/
			 
			
			$data = $this->orders_model->getOrderLine($orderid);
			
			$orderinfo = self::getOrderinfo($orderid);
			
			$addsql ='';
			
			if($flag == 'canceled')
			{
				$addsql.=",payment_status='canceled'";
			}
			
			if(!empty($data))
			{
				
				for($j=0;$j<count($data);$j++)
				{
				
					 $discount = $this->products_model->getProDiscount($data[$j]['product'],$orderinfo['order_time']);
					 
					if($orderline == $data[$j]['id']){
						$data[$j]['price'] = $price ;
					}
					 
					// echo '<pre>';print_r($data[$j]['changed_quantity']);exit;
					 $discount=$discount[0];
					 if($orderline != $data[$j]['id']){
					 $quantity = ($data[$j]['changed_quantity'] != '') ?  $data[$j]['changed_quantity'] : $data[$j]['quantity'];
					 }
					 else{
						 $quantity = $changed_quantity;
					 }
					 
					 $subtotal = $data[$j]['price'] * $quantity;
					 $productPrice=$subtotal;
					 
					 if($discount['type'] == 1)////min_qty
					 {
								$dis_min_quantity=intval($discount['min_quantity']);
								$dis_price=$discount['price'];
								$dis_repeat=intval($discount['repeat']);
								if($quantity >= $dis_min_quantity)
								{
									$productPrice=$quantity*$dis_price;
								}
								$addsql .=",product_discount='".$discount['id']."'";
									
					  }
					  else if($discount['type'] == 2)//percent 2
					  {
								
								$dis_percentage=100-$discount['percentage'];
								$dis_repeat=intval($discount['repeat']);
								if($dis_repeat == 1)
								{
										$dis_price=($data[$j]['price']*$dis_percentage)/100;
										$new_dis_price=$quantity*$dis_price;
										$productPrice=$productPrice-$new_dis_price;
										
								}
								else
								{
										$dis_price=($data[$j]['price']*$dis_percentage)/100;
										$productPrice=$productPrice-$dis_price;
								}
										
								$addsql .=",product_discount='".$discount['id']."'";
							}
						 else if($discount['type'] == 3)//buy_free 3
						 {
								
								$dis_buy_get=$discount['buy_get'];
								$dis_buy_get_free=$discount['buy_get_free'];
								$dis_repeat=intval($discount['repeat']);
								if($dis_repeat == 1)
								{
							
										if($quantity >= $dis_buy_get)
										{
											$dsqty=($quantity - $quantity % $dis_buy_get) / $dis_buy_get;
											$dqty=($dis_buy_get_free*$dsqty);
											$dis_price=($data[$j]['price']*$dqty);
											$productPrice=$productPrice-$dis_price;
										}
										//var sqty=(qty - qty % dis_min_quantity) / dis_min_quantity;
								}
								else
								{
										if($quantity >= $dis_buy_get)
										{
											$dis_price=($data[$j]['price']*$dis_buy_get_free);
											$productPrice=$productPrice-$dis_price;
										}
										
									
								}
								$addsql .=",product_discount='".$discount['id']."'";
								
							}
						
						 $productPrice=round($productPrice);
					 
					 
					$subtotalarray[]= $productPrice;
					
					if($orderline == $data[$j]['id']){
						
			 			$qry="UPDATE a_orderline SET price='".$price."',complain='".$complain."',in_house='".$in_house."',special_instruction='".$desp."',changed_quantity='".$changed_quantity."',changed_amount='".$productPrice."'$addsql WHERE id='".$orderline."' AND `order`='".$orderid."'";
						
						
						if($ocancel == 'canceled')
						{
							$qry2="INSERT INTO a_order_log SET 
							`orderline`='".$orderline."',
							`status`='11',
							 partner='".$this->session->userdata['partner']."',
							 partner_branch='".$this->session->userdata['partner_branch']."',
							 employee_p_branch='".$this->session->userdata('employee_p_branch')."'";
							 $this->db->query($qry2);
						}
					}
					
				}
				
			 
				$query=$this->db->query($qry);
				$total=array_sum($subtotalarray);
				$customer = $this->orders_model->getCustomerDetails($orderid);
					
				$subtotal=$total;
				$cus_sub_total=$subtotal;
				$customerid = $customer['customerid'];
				$zone = $customer['zone'];
				$delivery_type = 'normal'; //default
				$this->data['reg_fields'][] = 'delivery';
				$this->data['fields']['delivery'] =$this->general_model->checkMinimumAmount($delivery_type,$customerid,$total,$zone);
				
				$min_price=$this->data['fields']['delivery']['min_price'];
				$min_price_txt = '';
				$min_price_status=0;
				if($cus_sub_total < $min_price)
				{
					$min_price_txt =  ' (Minste beløp kr '.formatcurrency($min_price).')';
					$min_price_status=1;
				}
				
				$subtotal = ($subtotal < $min_price) ?  $min_price : $subtotal;
				$delsum=$subtotal;
				
				$delviery = ($subtotal >= $this->data['fields']['delivery']['free_delivery_after']) ?  '0' : $this->data['fields']['delivery']['delivery_charge'];
				
				$old_delivery_charge = $this->data['fields']['delivery']['delivery_charge'];


			    $orderinfo = $this->orders_model->getOrderinfo($orderid);
				
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
						$discount=$total*$percentage;
						
						
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
							$discount =  $total * ($percentage/100);
						}
						else{
							
							$discount = str_replace("kr ","",$vouchercode);
							$vouchercode = "kr ".formatcurrency($discount);
						}
						
					}
	
				}
				
			
			    $total  = $total - $discount;
				
				$qry="UPDATE a_order SET changed_amount='".$total."' WHERE `id`='".$orderid."'";
			    $query=$this->db->query($qry);
				
			}
			
			
			return true;
			
		}
		else
		{
			return false;
		}
	}
	
	/*update utlevering date*/
	function updateOrderlineDate($orderid,$olid,$utlevering)
	{
		for($i=0;$i<count($olid);$i++){
       
			$qry="UPDATE a_orderline SET p_b_delivery_time='".$utlevering."' WHERE id='".$olid[$i]."' AND `order`='".$orderid."'";
			$query=$this->db->query($qry);
		}	
		return true;
	}
	
	
	/*update order dicount*/
	function updateOrderDiscount($orderid,$discount,$discount_type,$total,$customer)
	{
		
        //declare
		if($discount_type=='%'){
			$discount = $discount.'%';
			$additional_sql = " ,voucher = NULL,order_discount= '".$discount."'";
		}
		else if($discount_type=='Kr'){
			$discount = 'kr '.$discount;
			$additional_sql = " ,voucher = NULL,order_discount= '".$discount."'";
		}
		else if($discount_type=='Kupongkode'){
			$additional_sql = " ,order_discount = NULL,voucher= '".$discount."'";
			self::saveVoucher($discount,$customer);
		}
		
		$qry="UPDATE a_order SET changed_amount='".$total."' $additional_sql WHERE `id`='".$orderid."'";
		$query=$this->db->query($qry);
		return true;
	}	
	
	
	
	function updateorderlineinfo($proitems,$orderid)
	{
				$orderline=$proitems['orderline'];
				$amount=$proitems['price'];
				$product=$proitems['id'];
				$subtotal=$proitems['subtotal'];
				$quantity=$proitems['oqty'];
				$changed_quantity=$proitems['qty'];
				
				$orderinfo = self::getOrderinfo($orderid);
				$addsql='';
				//if($quantity != $changed_quantity)
				//{
					$addsql="";
					$query=$this->db->query("SELECT * FROM a_orderline WHERE `id`='".$orderline."'");
					if($query->num_rows() > 0)
					{
						$orderline_info = $query->row_array();
						if($orderline_info['payment_status'] == 'canceled')
						{
							$addsql.=",payment_status='pending'";
						}
						
						if($changed_quantity == 0)
						{
							$addsql.=",payment_status='canceled'";
						}
					  $discount = $this->products_model->getProDiscount($orderline_info['product'],$orderinfo['order_time']);
					  $discount=$discount[0];
					  $quantity = $changed_quantity;
					  $subtotal = $orderline_info['price'] * $quantity;
					  $productPrice=$subtotal;
					  
					 if($discount['type'] == 1)////min_qty
					 {
								$dis_min_quantity=intval($discount['min_quantity']);
								$dis_price=$discount['price'];
								$dis_repeat=intval($discount['repeat']);
								if($quantity >= $dis_min_quantity)
								{
									$productPrice=$quantity*$dis_price;
								}
									
					  }
					  else if($discount['type'] == 2)//percent 2
					  {
								
								$dis_percentage=100-$discount['percentage'];
								$dis_repeat=intval($discount['repeat']);
								if($dis_repeat == 1)
								{
										$dis_price=($orderline_info['price']*$dis_percentage)/100;
										$new_dis_price=$quantity*$dis_price;
										$productPrice=$productPrice-$new_dis_price;
										
								}
								else
								{
										$dis_price=($orderline_info['price']*$dis_percentage)/100;
										$productPrice=$productPrice-$dis_price;
								}
								
						}
						else if($discount['type'] == 3)//buy_free 3
						{
								
								$dis_buy_get=$discount['buy_get'];
								$dis_buy_get_free=$discount['buy_get_free'];
								$dis_repeat=intval($discount['repeat']);
								if($dis_repeat == 1)
								{
							
										if($quantity >= $dis_buy_get)
										{
											$dsqty=($quantity - $quantity % $dis_buy_get) / $dis_buy_get;
											$dqty=($dis_buy_get_free*$dsqty);
											$dis_price=($orderline_info['price']*$dqty);
											$productPrice=$productPrice-$dis_price;
										}
										//var sqty=(qty - qty % dis_min_quantity) / dis_min_quantity;
								}
								else
								{
										if($quantity >= $dis_buy_get)
										{
											$dis_price=($orderline_info['price']*$dis_buy_get_free);
											$productPrice=$productPrice-$dis_price;
										}
										
									
								}
										
							}
					  
					  
					 $productPrice=round($productPrice);
					
						
					 $qry="UPDATE a_orderline SET changed_quantity='".$changed_quantity."',changed_amount='".$productPrice."'$addsql WHERE id='".$orderline."' AND `order`='".$orderid."'";
					
					$query=$this->db->query($qry);
					
				return $productPrice;
					}
				//}
	}
	
	function updateorderinfo($orderid,$order_total_amount,$order_delviery_amount)
	{
		$qry="UPDATE a_order SET changed_amount='".$order_total_amount."',delivery_charge='".$order_delviery_amount."' WHERE `id`='".$orderid."'";
		$query=$this->db->query($qry);
		return true;
	}
	
	function addorderlineinfo($orderid,$productid,$price,$p_b_delivery_time)
	{
		$qry="INSERT INTO a_orderline 
					SET 
					`order`='".$orderid."',
					`regtime`='".date('Y-m-d H:i:s')."',
					product='".$productid."',
					p_b_delivery_time='".$p_b_delivery_time."',
					payment_type='',
					quantity='1',
					price='".$price."',
					amount='".$price."'";
				
					$query=$this->db->query($qry);
		$orderline=$this->db->insert_id();
		return $orderline;
	}
	
	/*Save order*/
	function saveOrder($request,$payment_status)
	{
	
			$customer=$request["customer"];
			$products=$request["products"];
			
			
			$collection_time=$request["collection_time"];
			$delivery_time=$request["delivery_time"];
			$total_amount=$request["total_amount"];
			
			$payment_type=$request["payment_type"];
			$voucher=$request["voucher"];
			$delivery_note=mysql_real_escape_string($request["delivery_note"]);
			$special_instruction=mysql_real_escape_string($request["special_instruction"]);
		
			$order_status=$request["order_status"];
			$device_type=$request["device_type"];
			$did=$request["did"];
			$product_discount=$request['discount'];
			
			$employee_p_branch=$this->session->userdata['employee_p_branch'];
			
			$current_partner=$this->session->userdata['partner'];
			$current_partner_branch=$this->session->userdata['partner_branch'];
			
			
			$query=$this->db->query("SELECT order_id_start FROM a_partner_branch WHERE id='".$current_partner_branch."' AND partner='".$current_partner."'");
			$partners=$query->row_array();
			$order_id_start=$partners['order_id_start'];
			
			
			
			
			
		
			$query=$this->db->query("SELECT MAX(`id`) as orderid FROM a_order WHERE partner='".$current_partner."' AND partner_branch='".$current_partner_branch."'");
			
			$lastorder=0;
			if($query->num_rows() > 0)
			{
				$orderlog=$query->row_array();
				$lastorder=$orderlog['orderid'];
			}
			$nextorderid=0;
			if($lastorder >= $order_id_start)
			{
				$nextorderid=$lastorder+1;
			}
			else
			{
				$nextorderid=$order_id_start;
			}
			$partner='';
			$partner_branch='';
			$addsql='';
			$orderlineaddsql='';
			
			if($this->session->userdata['partner_branch'] == 14 || $this->session->userdata['partner_branch'] == 28)
			{
				$addsql="company='".$this->session->userdata['company']."',";	
				$orderlineaddsql="payment_type='invoice',";	
				$payment_type='invoice';
				$payment_status='pending';
			}
			
			if($voucher != '')
			{
				$addsql="voucher='".$voucher."',";
				self::saveVoucher($voucher,$customer);
			}
			$addsql.="ip_address='".$_SERVER['REMOTE_ADDR']."',";
			$qry="INSERT INTO a_order SET 
			id='".$nextorderid."',
			customer='".$customer."',
			`type`='".$device_type."',
			partner='".$current_partner."',
			partner_branch='".$current_partner_branch."',
			$addsql
			employee='".$this->session->userdata['current_staff']."',
			total_amount='".$total_amount."',
			order_discount='".$product_discount."',
			order_time='".date('Y-m-d H:i:s')."',
			payment_type='".$payment_type."',
			delivery_note='".$delivery_note."',
			special_instruction='".$special_instruction."',
			payment_status='".$payment_status."'";
			$this->db->query($qry);
			//$orderid=$this->db->insert_id();
			$orderid=$nextorderid;
			
			
			//print_r($products);
			if(count($products) > 0)
			{
				unset($products['cart_total']);
				unset($products['total_items']);
				foreach($products as $cartitem)
				{
					if(isset($cartitem['qty']))
					{
						$cartitem['quantity']=$cartitem['qty'];
					}
					
					//$p_b_delivery_time=date('Y-m-d H:i:s',strtotime($p_b_delivery_time));

					$p_b_delivery_time= date('Y-m-d H:i:s',strtotime($cartitem['utlevering']));
					
					//echo $cartitem['utlevering'];
					//product_discount	
					
					$discountdata = $this->products_model->getProDiscount($cartitem['id']);
					$dissql='';
					if(count($discountdata[0]) > 0)
					{
						$dissql="product_discount='".$discountdata[0]['id']."',";
					}
					$qry="INSERT INTO a_orderline 
					SET 
					`order`='".$orderid."',
					regtime='".date('Y-m-d H:i:s')."',
					p_b_delivery_time='".$p_b_delivery_time."',
					product='".$cartitem['id']."',
					$dissql
					$orderlineaddsql
					quantity='".$cartitem['quantity']."',
					amount='".$cartitem['subtotal']."',
					price='".$cartitem['price']."',
					special_instruction='".$cartitem['options']['description']."',
					complain='".$cartitem['options']['complain']."',
					in_house='".$cartitem['options']['in_house']."',
					payment_status='pending'";
					//echo $qry;
					$query=$this->db->query($qry);
					$orderline=$this->db->insert_id();
					if($this->session->userdata['partner_branch'] == 14 || $this->session->userdata['partner_branch'] == 1000 || $this->session->userdata['partner_branch'] == 28)
					{
					
						$proqty = $this->process_order_model->validateProducttype($cartitem['id']);
						if($proqty == 1)
						{
							if($cartitem['options']['heatseal'] != '')
							{
							
								$query=$this->db->query("SELECT * FROM a_heat_seal WHERE heat_seal_barcode='".$cartitem['options']['heatseal']."' ORDER BY id DESC LIMIT 0,1");		
								if($query->num_rows() > 0)		
								{				
									$result=$query->row_array();			
									$heatsealid=$result['id'];					
								}
								else
								{
									$qry="INSERT INTO a_heat_seal SET 
									`heat_seal_barcode`='".$cartitem['options']['heatseal']."',
									product='".$cartitem['id']."',
									`orderline`='".$orderline."',
									`regtime`='".date('Y-m-d H:i:s')."'";
									$query=$this->db->query($qry);	
									$heatsealid=$this->db->insert_id();
								}
								
								$qry="INSERT INTO a_heat_seal_log SET `heat_seal`='".$heatsealid."',`orderline`='".$orderline."',`status`='started',`regtime`='".date('Y-m-d H:i:s')."',employee_p_branch='".$this->session->userdata('employee_p_branch')."',customer='".$customer."',company='".$this->session->userdata['customer']['company']."'";	
								$this->db->query($qry);
								//$this->db->insert_id();
							}
							
						}
						else
						{
							if($this->session->userdata['producttypecart'][$cartitem['id']] && count($this->session->userdata['producttypecart'][$cartitem['id']]) > 0)
							{
								foreach($this->session->userdata['producttypecart'][$cartitem['id']] as $ptype=>$heatseal)
								{
										if($heatseal != '')
										{
											$query=$this->db->query("SELECT * FROM a_heat_seal WHERE heat_seal_barcode='".$heatseal."' ORDER BY id DESC LIMIT 0,1");		
											if($query->num_rows() > 0)		
											{				
												$result=$query->row_array();			
												$heatsealid=$result['id'];					
											}
											else
											{
												$qry="INSERT INTO a_heat_seal SET 
												`heat_seal_barcode`='".$heatseal."',
												product='".$cartitem['id']."',
												additional_product='".$ptype."',
												`orderline`='".$orderline."',
												`regtime`='".date('Y-m-d H:i:s')."'";
												$query=$this->db->query($qry);	
												$heatsealid=$this->db->insert_id();
											}
											
											$qry="INSERT INTO a_heat_seal_log SET `heat_seal`='".$heatsealid."',`orderline`='".$orderline."',`status`='added',`regtime`='".date('Y-m-d H:i:s')."',employee_p_branch='".$this->session->userdata('employee_p_branch')."',customer='".$customer."',company='".$this->session->userdata['company']."'";	
											$this->db->query($qry);
											//$this->db->insert_id();
										}
							
								}
							}
							//producttypecart
							
						}
					
					
							
						
					}
					
					
				}
			}
			
			
			$qry="INSERT INTO a_order_log 
						SET 
						`order`='".$orderid."',
						partner='".$current_partner."',
						partner_branch='".$current_partner_branch."',
						employee_p_branch='".$this->session->userdata['employee_p_branch']."',
						status='".$order_status."'";
						$query=$this->db->query($qry);
						
			
				/*$sql="UPDATE a_customer SET p_b_last_use='".date('Y-m-d H:i:s')."' WHERE id='".$customer."'";
				$this->db->query($sql);*/
				
			
			return $orderid;
			
		}
		
		function sendorderemail($order_id)
	{

			//get company details

			$company =  $this->general_model->getCompanyDetails();

			//get branch details

			$customer = self::getCustomerDetails($order_id);

			

			//get order details

			$orderinfo = self::getOrderinfo($order_id);

			$orderdetails = self::getOrderLine($order_id);

			$partnerinfo = $this->general_model->getPartnerDetails($orderinfo['partner']);

			//$collectiontinfo=self::getCollectionDeliverytime('collection',$orderinfo['collection_time']);

			//$deliveryinfo=self::getCollectionDeliverytime('delivery',$orderinfo['delivery_time']);

			$mailmessage='<div>

        <table width="360" align="center" border="0" cellpadding="0" cellspacing="0" > 

<tbody><tr>

  <th width="50%" valign="top" align="left" style="padding:0 0 40px 0;"><img width="160" src="'.$this->data['vars']['site_url'].'/application/themes/default/frontend/img/logo.png" /></th>

   <th width="50%" valign="top" align="left" style="padding:0 0 40px 0;"><ul style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal">

      <li style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal; color: #555; list-style: none;"><strong>'.$company['company_name'].'</strong></li>

      <li style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal; color: #555; list-style: none;">'.$company['company_address_street'].'</li>

      <li style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal; color: #555; list-style: none;">'.$company['company_address_zip'].' '.$company['company_address_city'].'</li>

    <li style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal; color: #555; list-style: none;">Telefon: '.$company['company_telephone'].'</li>

	   <li style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal; color: #555; list-style: none;">'.$company['company_email'].'</li>

	   <li style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal; color: #555; list-style: none;">'.$company['url'].'</li>

	  </ul></th>

</tr>

	

<tr>

<td width="50%" valign="top" align="left"  style="padding:0 0 40px 0;"><ul style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal">

      <li style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal; color: #555; list-style: none;">'.$customer['number'].'</li>

      <li style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal; color: #555; list-style: none;">'.$customer['customer_name'];

	 

	 /*$mailmessage.=trim($customer['firstname']);

	 if(trim($customer['lastname']) != '')

	  {

		 $mailmessage.=' '.trim($customer['lastname']);

	  }*/



	  

	  $mailmessage.='</li>';

	  if($customer['street_line_1'] != '')

	  {

		$mailmessage.='<li style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal; color: #555; list-style: none;">'.$customer['street_line_1'].'</li>';

	  }

	 

       

	  if($customer['street_line_2'] != '')

	  {

		$mailmessage.='<li style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal; color: #555; list-style: none;">'.$customer['street_line_2'].'</li>';

	  }

	   

	   $mailmessage.='<li style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal; color: #555; list-style: none;">'.$customer['zip'].' '.$customer['city'].'</li>';

	   

	   if($customer['floor'] != '')

	   {

		 $mailmessage.='<li style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal; color: #555; list-style: none;"><strong>Etg.: '.$customer['floor'].'</strong></li>';

	   }

	   

	    if($customer['calling_bell'] != '')

	   {

		$mailmessage.='<li style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal; color: #555; list-style: none;"><strong>RK: '.$customer['calling_bell'].'</strong></li>';

	   }

		

		 

     

$mailmessage.='</ul></td>



<td width="50%" valign="top" align="left"  style="padding:0 0 40px 0;"><ul style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal">

      <li style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal; color: #555; list-style: none;">Dato..........: '.$customer['odate'].'</li>';

      /*<li style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal; color: #555; list-style: none;">Best. kl....: '.$customer['otime'].'</li>*/

	  

	 //$mailmessage.='<li style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal; color: #555; list-style: none;">Henting dato: '.$collectiontinfo['sdate'].'</li>';

	 

	// $mailmessage.='<li style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal; color: #555; list-style: none;">Best. kl.....: '.$collectiontinfo['stime'].'-'.$collectiontinfo['etime'].'</li>';

	 

	 

	// $mailmessage.='<li style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal; color: #555; list-style: none;">Levering dato: '.$deliveryinfo['sdate'].'</li>';

	 //$mailmessage.='<li style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal; color: #555; list-style: none;">Del. kl......: '.$deliveryinfo['stime'].'-'.$deliveryinfo['etime'].'</li>';



	 

	       $mailmessage.='<li style="margin:0; padding: 0; font-family: arial; font-size: 12px; font-weight: normal; color: #555; list-style: none;">Ordrenr.....: '.$order_id.'</li>';

     

$mailmessage.='</ul></td>

</tr>';



/*$mailmessage.='<tr>

    <td colspan="2" valign="top" align="center" style="margin:0; padding:0 0 35px 0; font-family: arial; font-size: 16px; font-weight: bold; letter-spacing: 5px; color: #555">';

	

	

			$mailmessage.='Levert på døra';

		

		 

		if($orderinfo['type'] == 'app')

		 {

			$mailmessage.=' (App)';

		 }

		 

	$mailmessage.='</td>

</tr>';*/





$mailmessage.='<tr>

    <td colspan="2" valign="top" align="center">

	

	<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0">

                            <thead>

                              <tr>

							   <th style="text-align: left; border-top:#000 dashed 1px; border-bottom:#000 dashed 1px; padding: 10px 0; font-family: arial; font-size: 14px; font-weight:normal ">Vare</th>

								 <th style="text-align: center; border-top:#000 dashed 1px; border-bottom:#000 dashed 1px; padding: 10px 0; font-family: arial; font-size: 14px; font-weight:normal ">Antall</th>

                                <th style="text-align: right; border-top:#000 dashed 1px; border-bottom:#000 dashed 1px; padding: 10px 0; font-family: arial; font-size: 14px; font-weight:normal ">Beløp</th>

                              </tr>

                            </thead>

                            <tbody>';

							
$subtotalarray=array();
							if(count($orderdetails) > 0)

							{

								foreach($orderdetails as $products)

								{

								 $discount = $this->products_model->getProDiscount($products['product']);
								 $discount=$discount[0];
								 $ddesc='&nbsp;';
								 if(isset($discount['description']))
								 {
									 $ddesc='('.$discount['description'].')';
								 }
								 

									$mailmessage.='<tr>

								<td nowrap="nowrap" style="text-align: left; padding: 10px 0; font-family: arial; font-size: 12px; font-weight:normal;  vertical-align: top;">'.$products['name'].'<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;">'.$ddesc.'</p></td>

								

								

								<td  style="text-align: center; padding: 10px 0; font-family: arial; font-size: 12px; font-weight:normal;vertical-align: top; ">'.$products['quantity'].'<p style="margin-top: 0px; margin-bottom: 0px; padding: 0px;">&nbsp;</p>';

                        

						 $sub_total=$products['quantity']*$products['price'];
//$subtotalarray[]= $sub_total;

 $productPrice=$sub_total;
						 
						 //$subtotalarray[]= $sub_total;
							 
						 if($discount['type'] == 1)////min_qty
						 {
								$dis_min_quantity=intval($discount['min_quantity']);
								$dis_price=$discount['price'];
								$dis_repeat=intval($discount['repeat']);
								if($products['quantity'] >= $dis_min_quantity)
								{
									/*if($dis_repeat == 1)
									{
										$sqty=($products['quantity'] - $products['quantity'] % $dis_min_quantity) / $dis_min_quantity;
										$new_dis_price=$sqty*$dis_price;
										$productPrice=$productPrice-$new_dis_price;
										
									}
									else
									{
										$productPrice=$productPrice-$dis_price;
									}*/
									$productPrice=$products['quantity']*$dis_price;
								}	
						}
						 else if($discount['type'] == 2)//percent 2
						 {
								
								$dis_percentage=100-$discount['percentage'];
								$dis_repeat=intval($discount['repeat']);
								if($dis_repeat == 1)
								{
										$dis_price=($products['price']*$dis_percentage)/100;
										$new_dis_price=$products['quantity']*$dis_price;
										$productPrice=$productPrice-$new_dis_price;
										
								}
								else
								{
										$dis_price=($products['price']*$dis_percentage)/100;
										$productPrice=$productPrice-$dis_price;
								}
										
								
							}
						 else if($discount['type'] == 3)//buy_free 3
						 {
								
								$dis_buy_get=$discount['buy_get'];
								$dis_buy_get_free=$discount['buy_get_free'];
								$dis_repeat=intval($discount['repeat']);
								if($dis_repeat == 1)
								{
							
										if($products['quantity'] >= $dis_buy_get)
										{
											$dsqty=($products['quantity'] - $products['quantity'] % $dis_buy_get) / $dis_buy_get;
											$dqty=($dis_buy_get_free*$dsqty);
											$dis_price=($products['price']*$dqty);
											$productPrice=$productPrice-$dis_price;
										}
										//var sqty=(qty - qty % dis_min_quantity) / dis_min_quantity;
								}
								else
								{
										if($products['quantity'] >= $dis_buy_get)
										{
											$dis_price=($products['price']*$dis_buy_get_free);
											$productPrice=$productPrice-$dis_price;
										}
										
									
								}
										
								
							}
						
						 $productPrice=round($productPrice);
						 $subtotalarray[]=$productPrice;






                              $mailmessage.='<td nowrap="nowrap" style="text-align: right; padding: 10px 0; font-family: arial; font-size: 12px; font-weight:normal;vertical-align: top;">kr. '.$productPrice.',-</td>';

                             

                              

                              

							  $mailmessage.='

							  <td nowrap="nowrap" style="text-align: left; padding: 10px 0; font-family: arial; font-size: 12px; font-weight:normal;  vertical-align: top;">&nbsp;</td>

							  

                            </tr>';

									

								}

							}

							

		

		$customerid = $customer['customerid'];

		

		$zone = $customer['zone'];

		

		$delivery_type = 'normal'; //default

		

		//$this->data['reg_fields'][] = 'delivery';

		//$this->data['fields']['delivery'] =$this->general_model->checkMinimumAmount($delivery_type,$customerid,$delsum,$zone);

		//$this->data['debug'][] = $this->general_model->debug_data;	
		
		
		

		

	//	$frakt = ($amount >= $this->data['fields']['delivery']['free_delivery_after']) ?  '0' : $this->data['fields']['delivery']['delivery_charge'];

		$subtotal=array_sum($subtotalarray);
		$cus_sub_total=$subtotal;
		
		$this->data['reg_fields'][] = 'delivery';
		$fieldsdelivery = $this->general_model->checkMinimumAmount($delivery_type,$customerid,$subtotal,$zone);
		$this->data['fields']['delivery']=$fieldsdelivery;
		
		$min_price=$fieldsdelivery['min_price'];
		$min_price_txt = '';
		if($cus_sub_total < $min_price)
		{
			$min_price_txt =  ' (Minste beløp kr.'.$min_price.',-)';
		}
		$subtotal = ($subtotal < $min_price) ?  $min_price : $subtotal;
		$delsum=$subtotal;
		
		$this->data['debug'][] = $this->general_model->debug_data;	
		$delviery = ($subtotal >= $fieldsdelivery['free_delivery_after']) ?  '0' : $fieldsdelivery['delivery_charge'];
		$old_delivery_charge=$fieldsdelivery['delivery_charge'];
		
		$mailmessage.='<tr>

		

        <td style="text-align: left; padding:10px 0; font-family: arial; font-size: 14px; font-weight:normal ">&nbsp;</td>

        <td style="text-align: left; padding:10px; font-family: arial; font-size: 14px; font-weight:normal ">Delsum'.$min_price_txt.'</td>';

        $mailmessage.='<td style="text-align: right; padding:10px 0; font-family: arial; font-size: 14px; font-weight:normal; border-top:#000 dashed 1px;">kr. '.$subtotal.',-</td>

        </tr>';	
		
		
		

		//$delsum = $orderinfo['total_amount'] - $frakt;
		$delsum = $subtotal;
		$discount=0;

		if(intval($orderinfo['voucher']) > 0)
		{
			$qry="SELECT * FROM a_voucher WHERE id='".$orderinfo['voucher']."'";
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
			
			if($vdata['free_delivery_charge'] == 1)
			{
				$delviery=0;
			}
			else
			{
				$delviery=$old_delivery_charge;
			}
			
			
			$discount=$discount+0;
				$mailmessage.='<tr>

		

        <td style="text-align: left; padding:10px 0; font-family: arial; font-size: 14px; font-weight:normal ">&nbsp;</td>

        <td style="text-align: left; padding:10px; font-family: arial; font-size: 14px; font-weight:normal ">Discount ( '.$vouchercode.' )</td>

        <td style="text-align: right; padding:10px 0; font-family: arial; font-size: 14px; font-weight:normal;">kr. '.$discount.',-</td>

        </tr>';	

		$fprice=$cus_sub_total-$discount;
		
		if($fprice < $min_price)
		{
			
			$mailmessage.='<tr> <td style="text-align: left; padding:10px 0; font-family: arial; font-size: 14px; font-weight:normal ">&nbsp;</td>

			<td style="text-align: left; padding:10px; font-family: arial; font-size: 14px; font-weight:normal ">Minste beløp</td>
	
			<td style="text-align: right; padding:10px 0; font-family: arial; font-size: 14px; font-weight:normal;">kr. '.$min_price.',-</td>

			</tr>';	
		}		
		
		
			
		}
		
		$price=$subtotal-$discount;
			
		//$delviery = $orderinfo['total_amount'] - $price;
		$frakt=$delviery;
		
		$mailmessage.='<tr>

		 

        <td style="text-align: left; padding:10px 0; font-family: arial; font-size: 14px; font-weight:normal ">&nbsp;</td>

        <td style="text-align: left; padding:10px; font-family: arial; font-size: 14px; font-weight:normal ">Levering</td>';
		if($delviery == 0)
		{
			 $mailmessage.='<td style="text-align: right; padding:10px 0; font-family: arial; font-size: 14px; font-weight:normal;">kr. 0,-</td>';
		}
		else
		{
			 $mailmessage.='<td style="text-align: right; padding:10px 0; font-family: arial; font-size: 14px; font-weight:normal;">kr. '.$delviery.',-</td>';
		}
        
		
		

        $mailmessage.='</tr>';	
$orderinfo['total_amount']=$orderinfo['total_amount']+0;
		$mailmessage.='<tr>

		

        <td style="text-align: left; padding:10px 0; font-family: arial; font-size: 14px; font-weight:normal ">&nbsp;</td>

        <td style="text-align: left; padding:10px; font-family: arial; font-size: 14px; font-weight:normal ">Totalt</td>

        <td style="text-align: right; padding:10px 0; font-family: arial; font-size: 14px; font-weight:normal; border-top:#000 dashed 1px; border-bottom:#000 dashed 1px; ">kr. '.$orderinfo['total_amount'].',-</td>

        </tr>';		

				$delsum=$delsum-$discount;

				/*deduct vat from delsum amount

				$delsumamt=$delsum/1.25;	

				$delsumamt=round($delsumamt, 2);

				$delsumvat=$delsum-$delsumamt;	*/
				$delsumamt=$orderinfo['total_amount']/1.25;	

				$delsumamt=round($delsumamt, 2);

				$delsumvat=$orderinfo['total_amount']-$delsumamt;				

				/*deduct vat from delivery charge*/		

				//$deliveryamt=$frakt/1.25;		

				//$deliveryvat=$frakt-$deliveryamt;						

		

		$mailmessage .='				<tr>				<td nowrap="nowrap" colspan="4" style="text-align: center; padding: 10px 0; font-family: arial; font-size: 14px; font-weight:normal;  vertical-align: top;">&nbsp;</td></tr>				<tr>				<td nowrap="nowrap" style="border-top:#000 dashed 1px; border-bottom:#000 dashed 1px;text-align: center; padding: 10px 0; font-family: arial; font-size: 14px; font-weight:normal;  vertical-align: top;">MVA%</td>				<td  style="border-top:#000 dashed 1px; border-bottom:#000 dashed 1px;text-align: center; padding: 10px 0; font-family: arial; font-size: 14px; font-weight:normal ">Netto</td>				<td  nowrap="nowrap" style="border-top:#000 dashed 1px; border-bottom:#000 dashed 1px;text-align: center; padding: 10px 0; font-family: arial; font-size: 14px; font-weight:normal;vertical-align: top;">MVA</td>				<td nowrap="nowrap" style="text-align: right; padding: 10px 0; font-family: arial; font-size: 14px; font-weight:normal;vertical-align: top;"></td>							</tr>				<tr>				<td  nowrap="nowrap" style="text-align: center; padding: 10px 0; font-family: arial; font-size: 14px; font-weight:normal;  vertical-align: top;">25%</td>				<td  style="text-align: center; padding: 10px 0; font-family: arial; font-size: 14px; font-weight:normal ">'.$delsumamt.'</td>				<td  nowrap="nowrap" style="text-align: center; padding: 10px 0; font-family: arial; font-size: 14px; font-weight:normal;vertical-align: top;">'.$delsumvat.'</td>				<td nowrap="nowrap" style="text-align: right; padding: 10px 0; font-family: arial; font-size: 14px; font-weight:normal;vertical-align: top;"></td>								</tr>';	

		

				 /* if($frakt > 0){

													$mailmessage.='<tr>				<td nowrap="nowrap"  style="text-align: center; padding: 10px 0; font-family: arial; font-size: 14px; font-weight:normal;  vertical-align: top;">25%</td>				<td  style="text-align: center; padding: 10px 0; font-family: arial; font-size: 14px; font-weight:normal ">'.$deliveryamt.'</td>				<td  nowrap="nowrap" style="text-align: center; padding: 10px 0; font-family: arial; font-size: 14px; font-weight:normal;vertical-align: top;">'.$deliveryvat.'</td>				<td nowrap="nowrap" style="text-align: right; padding: 10px 0; font-family: arial; font-size: 14px; font-weight:normal;vertical-align: top;"></td>				</tr>';																	$mailmessage.='</tbody></table>';				$mailmessage.='</td></tr>';



				  }*/





			if(trim($customer['delivery_note']) != '')

			{

				$mailmessage.='<tr>

				<td colspan="2" valign="top" align="left" style="margin:0; padding:0; font-family: arial; font-size: 16px; font-weight: bold;  color: #555">Kommentar</td></tr>

				<tr><td colspan="2" valign="top" align="left"  style="text-align: left; padding: 10; font-family: arial; font-size: 14px; font-weight:normal;">'.trim($customer['delivery_note']).'</td></tr>';

			}

		

		$mailmessage.='</tbody></table></div>';

		

		$to_email=$customer['email'];

				//send email now

				email_default_settings(); //defaults (from emailer helper)

				$this->email->to($to_email);

				$this->email->subject('Din Bestillings detaljene');

				$this->email->message($mailmessage);

				$this->email->send();

				$email_log_message = $this->db->escape($mailmessage);

		

		//log this

				//$this->__emailLog($to_email, 'Your Order Details', $email_message);	

				

				   $query = $this->db->query("INSERT INTO email_log 			(													                                         email_log_date,

                                          email_log_time,

                                          email_log_to_address,

                                          email_log_subject,

                                          email_log_message

                                          )VALUES(

										  NOW(),

                                          NOW(),

                                          '".$to_email."',

                                          'Your Order Details',

                                         $email_log_message)");


										
										 
										 $to_email=$partnerinfo['email'];
//send email now
				email_default_settings(); //defaults (from emailer helper)
				//$this->email->to($to_email);
				$this->email->to($company['company_email']);
				$this->email->subject('Kunde Bestillings detaljene');
				$this->email->message($mailmessage);
				$this->email->send();
				//log this
				//$this->__emailLog($to_email, 'Customer Order Details', $email_message);

 $query = $this->db->query("INSERT INTO email_log (
                                          email_log_date,
                                          email_log_time,
                                          email_log_to_address,
                                          email_log_subject,
                                          email_log_message
                                          )VALUES(
                                          NOW(),
                                          NOW(),
                                          '".$to_email."',
                                          'Customer Order Details',
                                         $email_log_message)");	
										 

		return true;

			

	}

	/* update order line payment status*/
	function orderlinePayment($orderid,$orderline,$payment_type,$payment_status,$totalorderline)
	{	
		$addsql="";
		if(intval($orderline) > 0)
		{
			$addsql="AND id='".$orderline."'";
		}
		
		$qry="UPDATE  a_orderline SET payment_type='".$payment_type."',payment_status='".$payment_status."' WHERE `order`='".$orderid."' $addsql";
		$this->db->query($qry);
		
		//update order log status to delivered(9) in shop when they paid
		$orderlines = $this->orders_model->getOrderLine($orderid);
		
		if($totalorderline == count($orderlines)){
			//_____SQL QUERY_______
			$qry2="INSERT INTO a_order_log SET 
			`order`='".$orderid."',
			`status`='9',
			 partner='".$this->session->userdata['partner']."',
			 partner_branch='".$this->session->userdata['partner_branch']."',
			 employee_p_branch='".$this->session->userdata('employee_p_branch')."'";
			
			$query = $this->db->query($qry2);
			$results = $this->db->insert_id(); //last item insert id
			
		}
		else{
			
			//_____SQL QUERY_______
			$qry2="INSERT INTO a_order_log SET 
			`orderline`='".$orderline."',
			`status`='9',
			 partner='".$this->session->userdata['partner']."',
			 partner_branch='".$this->session->userdata['partner_branch']."',
			 employee_p_branch='".$this->session->userdata('employee_p_branch')."'";
			
			$query = $this->db->query($qry2);
			$results = $this->db->insert_id(); //last item insert id
			
			
		}
	
		
		
	}

		/*Remove the order line*/
	function removeOrderline($orderline)
	{	
		$qry="UPDATE a_orderline SET changed_quantity='0',changed_amount='0',payment_status='canceled' WHERE id='".$orderline."'";
		$query=$this->db->query($qry);
		return true;
	}

	/*Get particular orderline have today delivery based on order id*/
	function validateTodaydelivery($orderid)
	{
			$qry="SELECT * FROM a_orderline WHERE `order`='".$orderid."' AND DATE(p_b_delivery_time)='".date('Y-m-d')."'";
			//echo $qry."<br>";
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
	
	/* Get heatseal based on orderline*/
	
	function orderlineHeatseal($id='')
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
        $query = $this->db->query("SELECT a_product.name,a_orderline.id as id,a_orderline.amount,a_orderline.changed_amount,DATE_FORMAT(a_orderline.p_b_delivery_time,'%d.%m.%Y') as utlevering,		a_product.description,a_product.type as ptype,a_heat_seal.heat_seal_barcode	 as barcode,a_heat_seal.id as heatid,a_heat_seal.additional_product
		FROM a_orderline 
		LEFT JOIN a_product ON a_product.id=a_orderline.product 
		LEFT JOIN  a_heat_seal_log ON  a_heat_seal_log.orderline=a_orderline.id
		LEFT JOIN a_heat_seal ON a_heat_seal.id=a_heat_seal_log.heat_seal
		WHERE a_orderline.id='".$id."'");
		
	
		
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
	
	function saveVoucher($voucher,$customer)
	{
			$qry="SELECT type,voucher FROM a_voucher WHERE `id`='".$voucher."'";
			$query=$this->db->query($qry);
			if($query->num_rows() > 0)
			{
				$res=$query->row_array();
				
				if($res['type'] == 'normal')
				{
					$sql="UPDATE a_voucher_customer SET status='used' WHERE voucher='".$voucher."' AND customer='".$customer."'";
					$this->db->query($sql);
				}
				if($res['type'] == 'all')
				{
					$sql="INSERT INTO a_voucher_customer SET 
					voucher='".$voucher."',
					customer='".$customer."',
					status='used'";
					$this->db->query($sql);
				}
				
				if($res['type'] == 'invitation')
				{
					$vouchercode=$res['voucher'];
					$voc=explode('-',$vouchercode);
					$friend=$customer;
					$cus_id=$voc[1];
					
					$sql="INSERT INTO a_voucher_customer SET 
					voucher='".$voucher."',
					friend='".$friend."',
					status='used'";
					$this->db->query($sql);
					
					$sql="INSERT INTO a_voucher_customer SET 
					voucher='".$voucher."',
					customer='".$cus_id."',
					status='proceed'";
					$this->db->query($sql);
					
					
				}
			}
			
		}
		
		function removedamageline($id)
		{
			
			$sql="UPDATE `a_product_damage` SET status='0' WHERE id='".$id."'";		
			//$sql="DELETE FROM `a_product_damage` WHERE `a_product_damage`.`id` ='".$id."'";
			$this->db->query($sql);
			return true;
		}
		
	/*Get first delivery time from order*/
	function getShopdeliverydate($orderid,$old,$sort='ASC',$type='process')
	{
		$conditional_sql = '';
		$additional_sql = " ,a_order_log";
		
		/*if($type=='process'){
			$conditional_sql .= " AND a_order_log.orderline = a_orderline.id AND a_order_log.orderline NOT IN(".implode(',',$old).") AND a_order_log.status < '9' "  ;
		}
		else{
			$conditional_sql .= " AND a_order_log.orderline = a_orderline.id AND a_order_log.orderline NOT IN(".implode(',',$old).") AND  a_order_log.status = '9' "  ;
		}*/
	
		//AND DATE(p_b_delivery_time) > '".date('Y-m-d')."'
		$qry="SELECT DATE_FORMAT(p_b_delivery_time,'%d.%m.%Y') as delivery FROM a_orderline $additional_sql WHERE a_orderline.order='".$orderid."' $conditional_sql ORDER BY p_b_delivery_time ".$sort." LIMIT 0,1";
		
		
		$query = $this->db->query($qry);
		$orderlineinfo=$query->row_array();
		return $orderlineinfo['delivery'];
		
	}
	
	/* Get customer order history*/
		function getInfileOrders()
		{
		
		
			/*	$qry="SELECT * FROM a_orderline 
			LEFT JOIN a_heat_seal_log ON a_heat_seal_log.orderline=a_orderline.id
			WHERE a_orderline.id IN(SELECT max(id) as id FROM a_order_log GROUP BY `order`) AND `status` <= 5 ORDER by id DESC)
			";
			*/
		
			//$qry="SELECT `order` FROM a_orderline WHERE id IN (SELECT orderline FROM a_heat_seal_log WHERE id IN(SELECT max(id) as id FROM a_heat_seal_log GROUP BY `orderline`) AND status='started') GROUP BY `order`";
			
			$qry="SELECT `order` FROM a_orderline WHERE id IN (SELECT orderline FROM a_heat_seal_log WHERE id IN(SELECT max(id) as id FROM a_heat_seal_log GROUP BY `orderline`) AND status='started') GROUP BY `order`";
			$query = $this->db->query($qry);
			$results = $query->result_array();
			$orderarray=array();
			if(count($results) > 0)
			{
				foreach($results as $oritems)
				{
					if($oritems['order'] > 0)
					{
						$orderarray[]=$oritems['order'];
					}
					
				}
			}
			$orderids=implode(',',$orderarray);
			
			//echo '<pre>';print_r();exit;
			
		
		
			$conditional_sql='';
			$add_sql='';
			
			
			$add_sql='LEFT JOIN a_order_log ON a.id = a_order_log.order';
			
			
			
			
			$conditional_sql=" AND (a_order_log.id) in (SELECT id FROM a_order_log WHERE id IN(SELECT max(id) as id FROM a_order_log GROUP BY `order`) AND `status` <= 5 ORDER by id DESC)";
			$conditional_sql.=" AND (a.id) in (".$orderids.")";
			
			$qry="SELECT a.id,a.customer,a.type,
			b.start_time as collection_start_time,
			b.end_time as collection_end_time,
			c.start_time as delivery_start_time,
			c.end_time as delivery_end_time,
			a.total_amount,
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
			DATE_FORMAT(a.order_time,'%d.%m.%Y') as odate,
			a.partner,
			a.partner_branch,
			CONCAT(IFNULL(a_customer.firstname,''),' ',IFNULL(a_customer.lastname,'')) AS customer_name,a_address.street_line_1,a_address.street_line_2,a_address.floor,a_address.calling_bell,a_address.calling_bell,a_city.city as city,a_address.zip as zip,a_phone.number
			FROM a_order as a
			LEFT JOIN a_customer ON a_customer.id = a.customer 
			LEFT JOIN a_address ON a_customer.id = a_address.customer AND a_address.main = 1
			LEFT JOIN a_zip ON a_zip.id = a_address.zip
			LEFT JOIN a_city ON a_zip.city= a_city.id
			LEFT JOIN a_phone ON a_customer.id = a_phone.customer  AND a_phone.main = 1 			
			LEFT JOIN a_collection_time as b ON a.collection_time=b.id
			LEFT JOIN a_collection_time as c ON a.delivery_time=c.id
			LEFT JOIN a_customer_payment as d ON a.customer_payment=d.id
			LEFT JOIN a_partner_branch as pb ON a.partner_branch=pb.id
			LEFT JOIN a_partner as p ON pb.partner=p.id
			$add_sql
			WHERE 1=1
			$conditional_sql ORDER BY a.order_time DESC";
			
			$query=$this->db->query($qry);	
			$result=$query->result_array();
			return $result;
			
	  }
	  
	  	/*Check orderline delivered or not*/
	
	function validateOrderlinedelivery($column,$id)
	{
	
		$query=$this->db->query("SELECT * FROM a_order_log WHERE `$column`='".$id."' AND status='9'");
		if($query->num_rows() > 0)
		{
			return false;
		}
		else
		{
			return true;
		}
	} 
	
	function getOrderlineinfo($orderid,$oline)
	 {

  //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = "";
		$additional_sql = "";
		

        //if no valie client id, return false
        if (! is_numeric($orderid)) {
            return false;
        }

        //check if sorting type was passed
        $sort = ($sort == 'asc' || $sort == 'desc') ? $sort : 'ASC';
		
		//find customer subscribtion status
		$cus_id = $this->session->userdata['pos_customer_id'];
		$subscription = $this->payments_model->getSaldostatus($cus_id);
		
		$pricesql="CASE 
		WHEN $subscription = 0 THEN 
		  CASE 
		  WHEN pb.unsubscribed_price IS NULL THEN pb.price 
		  ELSE pb.unsubscribed_price  
		  END
	   ELSE pb.price 	  
	END AS oprice";
	
			if(intval($current_partner_branch) == 14 || intval($current_partner_branch) == 1000 || intval($current_partner_branch) == 28) //hvittsnip branch  ( only this branch aceept orders from comapny)
			{
				$customertype=$this->session->userdata['companytype'];
				$customercompany=$this->session->userdata['company'];
				$additional_sql.=" LEFT JOIN a_company_price ON a_company_price.type='".$customertype."' AND a_company_price.company='".intval($customercompany)."' AND a_company_price.product=p.id AND a_company_price.status='1'";
				
				$pricesql=" CASE 
					  WHEN a_company_price.price IS NULL THEN 
					  CASE 
						WHEN $subscription = 0 THEN 
						  CASE 
							WHEN pb.unsubscribed_price IS NULL THEN pb.price 
							ELSE pb.unsubscribed_price  
							END
						  ELSE pb.price 	  
						END
					  ELSE a_company_price.price 
					  END AS oprice";
			}
	
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
		
	
		
		
		//_____SQL QUERY_______
		
		if($this->session->userdata['logged_in']!='shop'){
		
		 
       $query = $this->db->query("SELECT *,a_orderline.price as price,a_orderline.special_instruction as description,a_orderline.id as id,DATE_FORMAT(a_orderline.p_b_delivery_time,'%d.%m.%Y') as utlevering,CONCAT('".PATH_IMAGE_FOLDER."',a_images.path) as path, a_product.type as ptype,a_product.name,a_orderline.product,a_product.price as oprice
	    FROM a_orderline 
		LEFT JOIN a_product ON a_product.id=a_orderline.product 
		LEFT JOIN a_images ON a_images.product=a_product.id 
		WHERE a_orderline.order='".$orderid."' AND a_orderline.id='".$oline."' ORDER BY a_orderline.p_b_delivery_time ASC");
		
		}else{
			
        $query = $this->db->query("SELECT *,a_orderline.price as price,a_orderline.special_instruction as description,a_orderline.id as id,DATE_FORMAT(a_orderline.p_b_delivery_time,'%d.%m.%Y') as utlevering,CONCAT('".PATH_IMAGE_FOLDER."',a_images.path) as path, a_product.type as ptype,a_product.name,a_orderline.product,c.name as category,c.description as cdescription,
	$pricesql
	    FROM a_product_p_branch_category as pbc,a_category_partner as c,a_orderline 
		LEFT JOIN a_product ON a_product.id=a_orderline.product 
		LEFT JOIN a_product_p_branch as pb ON pb.product = a_orderline.product 
		LEFT JOIN a_images ON a_images.product=a_product.id 
		WHERE pbc.product_p_branch = a_orderline.product  AND  pbc.category_partner=c.id AND a_orderline.order='".$orderid."' AND a_orderline.id='".$oline."' GROUP BY a_orderline.id ORDER BY a_orderline.p_b_delivery_time ASC");
		}
		
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
	
	  
		
	
}

/* End of file orders_model.php */
/* Location: ./application/models/orders_model.php */