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
class Products_model extends Super_Model
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

    // -- getCategories ----------------------------------------------------------------------------------------------
    /**
     * return array of all the rows of categories in table
     * accepts order_by and asc/desc values
     *
     * @return	array
     */

    function getCategories($id='',$parent = 0)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;
		
		$current_partner=$this->session->userdata['partner'];
		

        //declare
        $conditional_sql = " AND c.status='1'";
		
		if(is_numeric($id)){
			$conditional_sql .= " AND c.id='".$id."'";
		}
		
		$conditional_sql .= " AND c.parent='".$parent."' AND c.partner='".$current_partner."'";
				

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____ACTUAL QUERY_______
		$current_partner_branch=$this->session->userdata['partner_branch'];

        $query = $this->db->query("SELECT c.id,c.name,c.slug,c.description,c.bg_color,  
									   	  CONCAT('".PATH_IMAGE_FOLDER."',i.path) as path,(SELECT COUNT(category) from a_product,a_product_p_branch as pb WHERE category = c.id AND pb.product= a_product.id AND pb.partner_branch ='".$current_partner_branch."' AND pb.status=1 GROUP by category ) as count 
                                          FROM   a_category_partner as c
										  LEFT JOIN a_images as i ON c.id = i.category AND i.type='icon'  AND i.status='1'
										  WHERE 1=1 $conditional_sql
                                          ORDER BY c.sort_order ASC");

		if(is_numeric($id)){
			$results = $query->row_array(); //multi row array
		}
		else{
			$results = $query->result_array(); //multi row array
		}
		

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
	
    // -- getProductImage ----------------------------------------------------------------------------------------------
    /**
     * return  the image of a Product in table
     * accepts order_by and asc/desc values
     * 
     * @param $orderby sorting
     * @param $sort sort order
     * @return	array
     */

    function getProductImage($id='')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = " AND p.status='1'";
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
        $query = $this->db->query("SELECT CONCAT('".PATH_IMAGE_FOLDER."',i.path) as path
                                          FROM a_product as p
										  LEFT JOIN a_images as i ON p.id = i.product AND i.type='main' AND i.status='1' 
										  WHERE 1=1 $conditional_sql
										  AND p.id = $id
										  $additional_sql
                                         ");
										 

        $results = $query->row_array(); // row array

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;

    }	

    // -- getProduct ----------------------------------------------------------------------------------------------
    /**
     * return row of a product in table
     * accepts order_by and asc/desc values
     *
     * @return	array
     */

    function getProduct($id='',$imgtype='main',$cat='',$type='shop',$subscription='1')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = " AND p.status='1' AND c.status='1'";
		$additional_sql ="";
		$subscription_sql ="";
		
		if($id!=''){
			$conditional_sql .= " AND p.id=$id";
		}
		/*if($subscription=='1'){
			$subscription_sql .= ",pb.price as price ";
		}
		else{
			$subscription_sql .= ",pb.unsubscribed_price as price ";
		}*/
		if($imgtype=='main'){
			$additional_sql .= "LEFT JOIN a_images as i ON p.id = i.product AND i.type='main'  AND i.status='1'";
			$img = ' ,CONCAT("'.PATH_IMAGE_FOLDER.'",i.path) as path';
		}

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____ACTUAL QUERY_______
		
		
		if($type == 'shop'){
			
		if($cat!=''){
			$conditional_sql .= " AND p.category_partner=$cat";
		}
			
		
		$current_partner_branch=$this->session->userdata['partner_branch'];
		
        $query = $this->db->query("SELECT p.id,p.name,pb.heatseal,pb.note,pb.cleaning_duration as duration,p.description,o.price as offer_price,o.percent$img,c.name as category,c.slug as cslug,c.id as cid,p.alert,p.type as gtype,
	CASE 
    WHEN $subscription = 0 THEN 
	  CASE 
	  WHEN pb.unsubscribed_price IS NULL THEN pb.price 
	  ELSE pb.unsubscribed_price  
	  END
   ELSE pb.price 	  
END  AS price
                                          FROM a_product_p_branch as pb,a_category_partner as c,a_product as p
										  LEFT JOIN a_offer as o ON o.product = p.id AND start_time <= NOW() AND end_time >= NOW() AND o.status='1' 
										  $additional_sql
										  WHERE 1=1 $conditional_sql
										  AND pb.product= p.id AND pb.status=1 AND pb.partner_branch ='".$current_partner_branch."' 
										  AND c.id=p.category_partner
                                          ORDER BY pb.sort_order ASC");
		}
		else{
			
		if($cat!=''){
			$conditional_sql .= " AND p.category=$cat";
		}
			
        $query = $this->db->query("SELECT p.id,p.name,ROUND(p.price, 0) as price,p.description,ROUND(o.price, 0) as offer_price,o.percent$img,c.name as category,c.slug as cslug,c.id as cid,p.alert
                                          FROM a_category_partner as c,a_product as p
										  LEFT JOIN a_offer as o ON o.product = p.id AND start_time <= NOW() AND end_time >= NOW() AND o.status='1' 
										  $additional_sql
										  WHERE 1=1 $conditional_sql
										  AND c.id=p.category
                                          ORDER BY p.sort_order ASC");
		}
										  
										  
		if($id!=''){
			$results = $query->row_array();
		}
		else{
			$results = $query->result_array(); //multi row array
		}

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
	
	

    // -- getPopularProduct ----------------------------------------------------------------------------------------------
    /**
     * return row of a product in table
     * accepts order_by and asc/desc values
     *
     * @return	array
     */

    function getPopularProduct($customer_id='',$imgtype='main',$type='shop',$subscription='1')
    {
		
        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = " AND p.status='1' AND c.status='1'";
		$additional_sql = $customer_sql = "";
		$limit = 10;  //number of products
		$res = array();
		
		if($imgtype=='main'){
			$additional_sql .= "LEFT JOIN a_images as i ON p.id = i.product AND i.type='main'  AND i.status='1'";
			$img = ' ,CONCAT("'.PATH_IMAGE_FOLDER.'",i.path) as path';
		}


        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();
		
        //_____ACTUAL QUERY_______
		if($customer_id!=''){
			$customer_sql .= "AND customer = $customer_id AND a_orderline.order=a_order.id ";
			$tbl = ", a_order";

			$query_1 = $this->db->query("SELECT product FROM `a_orderline` $tbl WHERE 1=1 $customer_sql GROUP BY product LIMIT $limit OFFSET 0 ");
			$results_1 = $query_1->result_array(); //multi row array
			$num = $query_1->num_rows();
			$limit  = $limit - $num;
			$res = $results_1;
		}
		
		$query_2 = $this->db->query("SELECT product FROM `a_orderline` WHERE 1=1  GROUP BY product LIMIT $limit OFFSET 0 ");
		$results_2 = $query_2->result_array(); //multi row array
		
		//print_r($results_2);
		$result = array_merge($res, $results_2);
		//print_r($result);		
		
		$uniqueArray = array_map("unserialize", array_unique(array_map("serialize", $result)));
		//print_r($uniqueArray);
		//product id = 66 => kilovask => remove kilovask from popular	 		
				
	
	
		if($type == 'shop'){
			
		
		$current_partner_branch=$this->session->userdata['partner_branch'];
		
        $query = $this->db->query("SELECT p.id,p.name,p.description,o.price as offer_price,o.percent$img,c.name as category,c.slug as cslug,c.id as cid,p.alert,pb.heatseal,pb.note,pb.cleaning_duration as duration,p.type as gtype,
	CASE 
    WHEN $subscription = 0 THEN 
	  CASE 
	  WHEN pb.unsubscribed_price IS NULL THEN pb.price 
	  ELSE pb.unsubscribed_price  
	  END
   ELSE pb.price 	  
END  AS price
                                          FROM a_product_p_branch as pb,a_category_partner as c,a_product as p
										  LEFT JOIN a_offer as o ON o.product = p.id AND start_time <= NOW() AND end_time >= NOW() AND o.status='1' 
										  $additional_sql
										  WHERE 1=1 $conditional_sql
										  AND pb.product= p.id AND pb.status=1 AND pb.partner_branch ='".$current_partner_branch."' 										  
										  AND c.id=p.category_partner
										  AND p.id IN('".implode("','",array_map('reset', $uniqueArray))."')
										  AND p.id NOT IN ('66')
                                          ORDER BY pb.sort_order ASC");
	
		}
		
		else{
        $query = $this->db->query("SELECT p.id,p.name,p.price as price,p.description,o.price as offer_price,o.percent$img,c.name as category,c.slug as cslug,c.id as cid,p.alert,p.type as gtype
                                          FROM a_category_partner as c,a_product as p
										  LEFT JOIN a_offer as o ON o.product = p.id AND start_time <= NOW() AND end_time >= NOW() AND o.status='1' 
										  $additional_sql
										  WHERE 1=1 $conditional_sql
										  AND c.id=p.category
										  AND p.id IN('".implode("','",array_map('reset', $uniqueArray))."')
										  AND p.id NOT IN ('66')
                                          ORDER BY p.sort_order ASC");
										  
										  
		}
										  
		
										  

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
	

    // -- getImages ----------------------------------------------------------------------------------------------
    /**
     * return array of all the rows of categories in table
     * accepts order_by and asc/desc values
     *
     * @return	array
     */

    function getImages($id='',$type='',$field='')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = " AND status='1' AND $field=$id ";
		
		if($type!=''){
			$conditional_sql = " AND type='$type'";
		}

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT id,CONCAT('".PATH_IMAGE_FOLDER."',path) as path,type
                                          FROM a_images
										  WHERE 1=1 $conditional_sql
                                          ORDER BY id ASC");

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
	
	
    // -- getProductFAQ ----------------------------------------------------------------------------------------------
    /**
     * return array of all the rows of getProductFAQ in table
     * accepts order_by and asc/desc values
     *
     * @return	array
     */

    function getProductFAQ($id='')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = " AND  f.status='1' AND pf.status='1'";

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT f.id,f.question,f.answer
                                          FROM  a_faq as f,a_product_faq pf
										  WHERE 1=1 $conditional_sql
										  AND pf.product = $id
										  AND pf.faq = f.id
                                          ORDER BY pf.sort_order ASC");

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
	
	

    // -- getProductDiscount ----------------------------------------------------------------------------------------------
    /**
     * return array of all the rows of discount in table
     * accepts order_by and asc/desc values
     *
     * @return	array
     */

    function getProductDiscount($id='0')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = " AND  p.status='1' AND d.status='1'";

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT d.min_quantity,ROUND(d.price, 0) as price
                                          FROM  a_product_discount d,a_product p
										  WHERE 1=1 $conditional_sql
										  AND d.product = p.id
										  AND p.id = $id
                                          ORDER BY d.min_quantity ASC");

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
	

	 // -- getProductDiscount ----------------------------------------------------------------------------------------------
    /**
     * return array of all the rows of discount in table
     * accepts order_by and asc/desc values
     *
     * @return	array
     */

    function getProDiscount($id='0',$date='now')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;
		
		if($date=='now'){
			$day =  " CURDATE() ";
		}
		else{
			$day = " DATE('$date') ";
		}

        //declare
        $conditional_sql = " AND  p.status='1' AND d.status='1'";
		$conditional_sql.= " AND d.start_date <= ".$day."  AND d.end_date >= ".$day;

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

		$current_partner_branch=$this->session->userdata['partner_branch'];
		

        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT d.*
                                          FROM  a_product_discount d,a_product p
										  WHERE 1=1 $conditional_sql
										  AND d.product = p.id
										  AND p.id = '".intval($id)."'
										  AND d.partner_branch=$current_partner_branch
                                          ORDER BY d.min_quantity ASC");
										  

        $res = $query->row_array(); //multi row array
	//enum('min_qty','percent','buy_free') NULL	
	
		if($res['type'] == 'percent')
		{
			$results=array(array('id'=>$res['id'],'title'=>$res['title'],'type'=>2,'percentage'=>$res['percentage'],'repeat'=>$res['repeat']));
			
		}
		else if($res['type'] == 'buy_free')
		{
			$results=array(array('id'=>$res['id'],'title'=>$res['title'],'type'=>3,'buy_get'=>$res['buy_get'],'buy_get_free'=>$res['buy_get_free'],'repeat'=>$res['repeat']));
		}
		else if($res['type'] == 'min_qty')
		{
			$results=array(array('id'=>$res['id'],'title'=>$res['title'],'type'=>1,'min_quantity'=>$res['min_quantity'],'price'=>$res['price'],'repeat'=>$res['repeat']));
		}
		else
		{
			$results=array();
		}
	
	

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
	
	 function getTilbudProducts($id='',$imgtype='main',$cat='',$type='shop',$subscription='1')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;
		
		
		$conditionalsql = " AND  p.status='1' AND d.status='1'";
		$conditionalsql.= " AND d.start_date <= '".date('Y-m-d')."' AND d.end_date >= '".date('Y-m-d')."'";
		$sql="SELECT d.product
                                          FROM  a_product_discount d,a_product p
										  WHERE 1=1 $conditionalsql
										  AND d.product = p.id
											ORDER BY d.min_quantity ASC";
											
										
		$qry = $this->db->query($sql);

        $res = $qry->result_array();
		
		
		
		$proid=array();
		if($qry->num_rows() > 0)
		{
			foreach($res as $resid)
			{
				$proid[$resid['product']]=$resid['product'];
			}
		}
		else
		{
			return false;
		}
		
		 //declare
        $conditional_sql = " AND p.status='1' AND c.status='1'";
		$additional_sql ="";
		
		/*if($id!=''){
			$conditional_sql .= " AND p.id=$id";
		}
		if($cat!=''){
			$conditional_sql .= " AND p.category_partner=$cat";
		}*/
		
		if(count($proid) > 0)
		{
			$ids=implode(',',$proid);
			
			$conditional_sql .= " AND p.id IN($ids)";
		}
		
		
		//echo '<pre>';print_r($conditional_sql);exit;
		
		if($imgtype=='main'){
			$additional_sql .= "LEFT JOIN a_images as i ON p.id = i.product AND i.type='main'  AND i.status='1'";
			$img = ' ,CONCAT("'.PATH_IMAGE_FOLDER.'",i.path) as path';
		}

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____ACTUAL QUERY_______
		if($type == 'shop'){
		
		$current_partner_branch=$this->session->userdata['partner_branch'];
		
        $query = $this->db->query("SELECT p.id,p.name,pb.heatseal,pb.note,pb.cleaning_duration as duration,p.description,o.price as offer_price,o.percent$img,c.name as category,c.slug as cslug,c.id as cid,p.alert,p.type as gtype,
	CASE 
    WHEN $subscription = 0 THEN 
	  CASE 
	  WHEN pb.unsubscribed_price IS NULL THEN pb.price 
	  ELSE pb.unsubscribed_price  
	  END
   ELSE pb.price 	  
END  AS price
                                          FROM a_product_p_branch as pb,a_category_partner as c,a_product as p
										  LEFT JOIN a_offer as o ON o.product = p.id AND start_time <= NOW() AND end_time >= NOW() AND o.status='1' 
										  $additional_sql
										  WHERE 1=1 $conditional_sql
										  AND pb.product= p.id AND pb.status=1 AND pb.partner_branch ='".$current_partner_branch."' 
										  AND c.id=p.category_partner
                                          ORDER BY pb.sort_order ASC");
		}
		else{
        $query = $this->db->query("SELECT p.id,p.name,ROUND(p.price, 0) as price,p.description,ROUND(o.price, 0) as offer_price,o.percent$img,c.name as category,c.slug as cslug,c.id as cid,p.alert
                                          FROM a_category_partner as c,a_product as p
										  LEFT JOIN a_offer as o ON o.product = p.id AND start_time <= NOW() AND end_time >= NOW() AND o.status='1' 
										  $additional_sql
										  WHERE 1=1 $conditional_sql
										  AND c.id=p.category
                                          ORDER BY p.sort_order ASC");
		}										  
										  
										  

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
	
	// -- getProductDiscount ----------------------------------------------------------------------------------------------
    /**
     * return array of all the rows of discount in table
     * accepts order_by and asc/desc values
     *
     * @return	array
     */

    function getProduct_Discount($id='0')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = " AND  p.status='1' AND d.status='1'";
		$conditional_sql.= " AND d.start_date <= '".date('Y-m-d')."' AND d.end_date >= '".date('Y-m-d')."'";

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();
		
		$current_partner_branch=$this->session->userdata['partner_branch'];

        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT d.*
                                          FROM  a_product_discount d,a_product p
										  WHERE 1=1 $conditional_sql
										  AND d.product = p.id
										  AND p.id = $id
										  AND d.partner_branch=$current_partner_branch
                                          ORDER BY d.min_quantity ASC");

        $res = $query->row_array(); //multi row array
	//enum('min_qty','percent','buy_free') NULL	
	
		if($res['type'] == 'percent')
		{
			$results=array(array('title'=>$res['title'],'description'=>$res['description'],'type'=>2,'percentage'=>$res['percentage'],'repeat'=>$res['repeat']));
			
		}
		else if($res['type'] == 'buy_free')
		{
			$results=array(array('title'=>$res['title'],'description'=>$res['description'],'type'=>3,'buy_get'=>$res['buy_get'],'buy_get_free'=>$res['buy_get_free'],'repeat'=>$res['repeat']));
		}
		else if($res['type'] == 'min_qty')
		{
			$results=array(array('title'=>$res['title'],'description'=>$res['description'],'type'=>1,'min_quantity'=>$res['min_quantity'],'price'=>$res['price'],'repeat'=>$res['repeat']));
		}
		else
		{
			$results=array();
		}
	
	

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
	
    // -- searchProduct ----------------------------------------------------------------------------------------------
    /**
     * return array of all the rows of product in table
     * accepts order_by and asc/desc values
     * 
     * @param $orderby sorting
     * @param $sort sort order
     * @return	array
     */
    function searchProduct($keyword = '', $sort = 'ASC')
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;
		
		
		$additional_sql .= "LEFT JOIN a_images as i ON p.id = i.product AND i.type='main'  AND i.status='1'";
		$img = ' ,CONCAT("'.PATH_IMAGE_FOLDER.'",i.path) as path';
		
		
        //declare
        $conditional_sql = '';
        //check if any specifi ordering was passed
        if (! $this->db->field_exists($orderby, 'a_product')) {
            $orderby = 'p.name';
        }
        //check if sorting type was passed
        $sort = ($sort == 'asc' || $sort == 'desc') ? $sort : 'ASC';
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
        //_____SQL QUERY_______
        $query = $this->db->query("SELECT * $img
								FROM a_product as p
								$additional_sql
								WHERE p.name LIKE '%".$keyword."%' ORDER BY p.name ASC");
		
	  
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
	
	// -- getProductsearch ----------------------------------------------------------------------------------------------
    /**
     * return row of a product in table
     * accepts order_by and asc/desc values
     *
     * @return	array
     */

    function getProductsearch($id='',$imgtype='main',$cat='',$type='shop',$subscription='1',$keyword = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = " AND p.status='1' AND c.status='1'";
		$additional_sql ="";
		$subscription_sql ="";
		
		if($id!=''){
			$conditional_sql .= " AND p.id=$id";
		}
		/*if($subscription=='1'){
			$subscription_sql .= ",pb.price as price ";
		}
		else{
			$subscription_sql .= ",pb.unsubscribed_price as price ";
		}*/
		if($imgtype=='main'){
			$additional_sql .= "LEFT JOIN a_images as i ON p.id = i.product AND i.type='main'  AND i.status='1'";
			$img = ' ,CONCAT("'.PATH_IMAGE_FOLDER.'",i.path) as path';
		}

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____ACTUAL QUERY_______
		
		
		if($type == 'shop'){
			
		if($cat!=''){
			$conditional_sql .= " AND p.category_partner=$cat";
		}
			
		
		$current_partner_branch=$this->session->userdata['partner_branch'];
		
        $query = $this->db->query("SELECT p.id,p.name,pb.heatseal,pb.note,pb.cleaning_duration as duration,p.description,o.price as offer_price,o.percent$img,c.name as category,c.slug as cslug,c.id as cid,p.alert,p.type as gtype,
	CASE 
    WHEN $subscription = 0 THEN 
	  CASE 
	  WHEN pb.unsubscribed_price IS NULL THEN pb.price 
	  ELSE pb.unsubscribed_price  
	  END
   ELSE pb.price 	  
END  AS price
                                          FROM a_product_p_branch as pb,a_category_partner as c,a_product as p
										  LEFT JOIN a_offer as o ON o.product = p.id AND start_time <= NOW() AND end_time >= NOW() AND o.status='1' 
										  $additional_sql
										  WHERE 1=1 $conditional_sql
										  AND p.name LIKE '%".$keyword."%' AND pb.product= p.id AND pb.status=1 AND pb.partner_branch ='".$current_partner_branch."' 
										  AND c.id=p.category_partner
                                          ORDER BY pb.sort_order ASC");
		}
		else{
			
		if($cat!=''){
			$conditional_sql .= " AND p.category=$cat";
		}
			
			
        $query = $this->db->query("SELECT p.id,p.name,ROUND(p.price, 0) as price,p.description,ROUND(o.price, 0) as offer_price,o.percent$img,c.name as category,c.slug as cslug,c.id as cid,p.alert
                                          FROM a_category_partner as c,a_product as p
										  LEFT JOIN a_offer as o ON o.product = p.id AND start_time <= NOW() AND end_time >= NOW() AND o.status='1' 
										  $additional_sql
										  WHERE 1=1 $conditional_sql
										  AND p.name LIKE '%".$keyword."%'
										  AND c.id=p.category
                                          ORDER BY p.sort_order ASC");
		}
										  
										  
		if($id!=''){
			$results = $query->row_array();
		}
		else{
			$results = $query->result_array(); //multi row array
		}

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
	
	

}

/* End of file products_model.php */
/* Location: ./application/models/products_model.php */
