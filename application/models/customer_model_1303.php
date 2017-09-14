<?php
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/**
 * class for perfoming all bugs related data abstraction
 */
class Customer_model extends Super_Model
{
    // -- __construct ----------------------------------------------------------------------------------------------
    function __construct()
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;
        // Call the Model constructor
        parent::__construct();
    }
	
	
    // -- allCustomer ----------------------------------------------------------------------------------------------
    /**
     * return array of all the rows of Patients in table
     * accepts order_by and asc/desc values
     * 
     * @param $orderby sorting
     * @param $sort sort order
     * @return	array
     */
    function allCustomer($orderby = 'id', $sort = 'ASC')
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;
        //declare
        $conditional_sql = '';
        //check if any specifi ordering was passed
        if (! $this->db->field_exists($orderby, 'a_customer')) {
            $orderby = 'name';
        }
        //check if sorting type was passed
        $sort = ($sort == 'asc' || $sort == 'desc') ? $sort : 'ASC';
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
		
		
        //_____SQL QUERY_______
        $query = $this->db->query("SELECT a_customer.id,CONCAT(IFNULL(firstname,''),' ',IFNULL(lastname,'')) AS name,p.number,e.email
                                          FROM a_customer
										  LEFT JOIN a_phone as p ON p.customer = a_customer.id AND p.main ='1' AND p.status='1'
										   LEFT JOIN a_email as e ON e.customer = a_customer.id AND e.main ='1' AND e.status='1'
										  WHERE 1=1 $conditional_sql 
                                          ORDER BY $orderby $sort");
										  
										  
										  
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
	
	

	/* Get customer account log*/
	function getCustomerAccountLog($customer='',$type='',$in_type='',$in_status=''){
		
			$add_sql='';
			$conditional_sql ="";
			
			$current_partner = $this->session->userdata['partner'];
			
			$conditional_sql .=" AND a.partner = '".$current_partner."'";
			
			$orderby = 'ORDER BY a.regtime DESC';
			
			if($customer!='')
				$conditional_sql .= " AND a.customer='".$customer."'";
	
		    //in or out
			if($type!='') 
				$conditional_sql .= " AND a.type='".$type."'";

			//invoice, card, cash, gift card
			if($in_type!='')
				$conditional_sql .= " AND a.in_type='".$in_type."'";
				
			//paid , pending , cancelled	
			if($in_status!='')
				$conditional_sql .= " AND a.in_status='".$in_status."'";
			
			
			if ($this->input->post('from')) {
				$from = $this->db->escape(date('Y-m-d',strtotime($this->input->post('from'))));
				$conditional_sql .= " AND DATE(a.regtime) >= $from";
			}
			if ($this->input->post('to')) {
				
				$to = $this->db->escape(date('Y-m-d',strtotime($this->input->post('to'))));
				$conditional_sql .= " AND DATE(a.regtime) <= $to";
			}
			
			
			if($this->input->post('from') == '' && $this->input->post('to') == '')
			{
				$conditional_sql .= " AND DATE(`a`.`regtime`)='".date('Y-m-d')."'";
			}
	
			$qry="SELECT a.*,DATE_FORMAT(a.regtime,'%d.%m.%Y') as rdate,TIME_FORMAT(a.regtime, '%H:%i') as rtime,TIME_FORMAT(a.regtime, '%H:%i:%s') as rtimews,DATE_FORMAT(a.regtime,'%d/%m/%y') as rdatewy,
				  CASE 
				  WHEN pbc.display_name IS NULL THEN p.name 
				  ELSE pbc.display_name
				  END  AS name
				  FROM a_customer_account_log as a
				  LEFT JOIN a_orderline as o ON o.id = a.orderline 
				  LEFT JOIN a_product as p ON p.id = o.product
				  LEFT JOIN a_product_p_branch_category as pbc ON p.id = pbc.product_p_branch
				  $add_sql
				  WHERE 1=1
				  $conditional_sql GROUP BY a.id $orderby";
			
			
			$query=$this->db->query($qry);	
			$result=$query->result_array();
			
			return $result;
			
	  }
	
	
	
	  /* Get customer payment without saldo amount*/
	function getCustomerpayment($customer='',$type='',$in_type='',$in_status='')
	{
	  
		$add_sql='';
			$conditional_sql ="";
			
			$current_partner = $this->session->userdata['partner'];
			
			$conditional_sql .=" AND a_order.partner = '".$current_partner."'";
			
			$orderby = 'ORDER BY a_orderline.modtime ASC';
			
			if($customer!='')
				$conditional_sql .= " AND a_order.customer='".$customer."'";
	
		    //in or out
			//if($type!='') 
				//$conditional_sql .= " AND a.type='".$type."'";

			//('visa','invoice','account','cash'	
			if($in_type!='')
				$conditional_sql .= " AND a_orderline.payment_type='".$in_type."'";
				
			//'pending','paid','canceled','waiting'
			if($in_status!='')
				$conditional_sql .= " AND a_orderline.payment_status='".$in_status."'";
			
			
			if ($this->input->post('from')) {
				$from = $this->db->escape(date('Y-m-d',strtotime($this->input->post('from'))));
				$conditional_sql .= " AND DATE(a_orderline.modtime) >= $from";
			}
			
			
			if ($this->input->post('to')) {
				
				$to = $this->db->escape(date('Y-m-d',strtotime($this->input->post('to'))));
				$conditional_sql .= " AND DATE(a_orderline.modtime) <= $to";
			}
			
			if($this->input->post('from') == '' && $this->input->post('to') == '')
			{
				$conditional_sql .= " AND DATE(`a_orderline`.`modtime`)='".date('Y-m-d')."'";
			}
		
		
			
			
			
		$qry="SELECT a_order.customer,a_order.partner,
		CASE 
				  WHEN a_orderline.changed_amount IS NULL THEN a_orderline.amount 
				  ELSE a_orderline.changed_amount
				  END  AS amount,a_order.id as `order`,a_orderline.id as orderline,
				  a_orderline.modtime,a_orderline.modtime as regtime,
				  a_orderline.payment_type as in_type,a_orderline.payment_status as in_status,
				DATE_FORMAT(a_orderline.modtime,'%d.%m.%Y') as rdate,TIME_FORMAT(a_orderline.modtime, '%H:%i') as rtime,TIME_FORMAT(a_orderline.modtime, '%H:%i:%s') as rtimews,DATE_FORMAT(a_orderline.modtime,'%d/%m/%y') as rdatewy,
				  CASE 
				  WHEN a_product_p_branch_category.display_name IS NULL THEN a_product.name 
				  ELSE a_product_p_branch_category.display_name
				  END  AS name
				  FROM a_orderline
				  LEFT JOIN a_order ON a_order.id = a_orderline.order
				  LEFT JOIN a_product ON a_product.id = a_orderline.product
				  LEFT JOIN a_product_p_branch_category  ON a_product.id = a_product_p_branch_category.product_p_branch
		
				  $add_sql
				  WHERE 1=1 AND a_orderline.payment_type != '' AND (a_orderline.payment_status = 'paid' OR a_orderline.payment_status = 'waiting') AND
				  a_orderline.id NOT IN(SELECT id FROM a_orderline WHERE id IN(SELECT orderline FROM a_customer_account_log)) 
				  AND 
				  a_orderline.`order` NOT IN(SELECT `order` FROM a_orderline WHERE `order` IN(SELECT `order` FROM a_customer_account_log))
				  $conditional_sql GROUP BY a_orderline.id $orderby";
			

			
			$query=$this->db->query($qry);	
			$result=$query->result_array();
			return $result;
		
	  }
	
    // -- searchCompanyCustomer ----------------------------------------------------------------------------------------------
    /**
     * return array of all the rows of customer in table
     * accepts order_by and asc/desc values
     * 
     * @param $orderby sorting
     * @param $sort sort order
     * @return	array
     */
    function searchCompanyCustomer($keyword='',$company='1',$type ='name',$sort = 'ASC',$orderby = 'id')
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;
		$current_partner_branch = $this->session->userdata['partner_branch'];
		
		
		$additional_sql = '';
		//declare
		if($type == 'name'){
			
				$conditional_sql = " AND a_customer.firstname LIKE '%{$keyword}%' OR a_customer.lastname LIKE '%{$keyword}%'" ;
				
				
				$additional_sql .= " ,CONCAT(IFNULL(a_customer.firstname,''),' ',IFNULL(a_customer.lastname,''),' (',IFNULL(p.number,''),')') AS name";
				$tbl = "a_customer";
				
		}
		else{
	        $conditional_sql = " AND p.number LIKE '%{$keyword}%'" ;
		}
		
		if($company != '')
		{
			 $conditional_sql = " AND  cc.company = '".$company."'" ;
		}
		
		
		
	
	
	    //check if any specifi ordering was passed
        if (! $this->db->field_exists($orderby, 'a_customer')) {
            $orderby = 'firstname';
        }
        //check if sorting type was passed
        $sort = ($sort == 'asc' || $sort == 'desc') ? $sort : 'ASC';
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
		
		
		
        //_____SQL QUERY_______
        $query = $this->db->query("SELECT a_customer.id $additional_sql,p.number,e.email
                                          FROM a_company_customer as cc,a_customer
										  LEFT JOIN a_phone as p ON p.customer = a_customer.id AND p.main ='1' AND p.status='1'
										  LEFT JOIN a_email as e ON e.customer = a_customer.id AND e.main ='1' AND e.status='1'
										  WHERE 1=1 
										   AND  a_customer.id = cc.customer AND  cc.status='1'
										  $conditional_sql 
                                          ORDER BY $orderby $sort");
										  
		
		/*echo "SELECT a_customer.id, $additional_sql ,p.number,e.email
                                          FROM a_company_customer as cc,a_customer
										  LEFT JOIN a_phone as p ON p.customer = a_customer.id AND p.main ='1' AND p.status='1'
										   LEFT JOIN a_email as e ON e.customer = a_customer.id AND e.main ='1' AND e.status='1'
										  WHERE 1=1 $conditional_sql
										  AND  cc.company = $company AND  a_customer.id = cc.customer AND  cc.status='1'
                                          ORDER BY $orderby $sort";	*/
										  
										  								  							  
										  
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

	
    // -- searchCustomer ----------------------------------------------------------------------------------------------
    /**
     * return array of all the rows of customer in table
     * accepts order_by and asc/desc values
     * 
     * @param $orderby sorting
     * @param $sort sort order
     * @return	array
     */
    function searchCustomer($keyword='',$orderby = 'id', $sort = 'ASC',$type ='name')
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;
		$current_partner_branch = $this->session->userdata['partner_branch'];
		
		
		$additional_sql = '';
		//declare
		if($type == 'name'){
			
			if($current_partner_branch != 14 && $current_partner_branch != 1000 && $current_partner_branch != 28){
				$conditional_sql = " AND firstname LIKE '%{$keyword}%' OR lastname LIKE '%{$keyword}%'" ;
				$additional_sql .= "CONCAT(IFNULL(firstname,''),' ',IFNULL(lastname,''),' (',IFNULL(p.number,''),')') AS name,";
				$tbl = "a_customer";
				
			}
			else{
				$conditional_sql = " AND name LIKE '%{$keyword}%'";
				$additional_sql .= " CONCAT(IFNULL(name,''),' (',IFNULL(phone,''),')') AS name, ";
				$tbl = "a_company";
				
			}
		}
		else{
	        $conditional_sql = " AND p.number LIKE '%{$keyword}%'" ;
		}
	
	
	    //check if any specifi ordering was passed
        if (! $this->db->field_exists($orderby, 'a_customer')) {
            $orderby = 'name';
        }
        //check if sorting type was passed
        $sort = ($sort == 'asc' || $sort == 'desc') ? $sort : 'ASC';
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
		
		if($current_partner_branch != 14 && $current_partner_branch != 1000 && $current_partner_branch != 28){

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT a_customer.id, $additional_sql p.number,e.email
                                          FROM a_customer
										  LEFT JOIN a_phone as p ON p.customer = a_customer.id AND p.main ='1' AND p.status='1'
										   LEFT JOIN a_email as e ON e.customer = a_customer.id AND e.main ='1' AND e.status='1'
										  WHERE 1=1 $conditional_sql 
                                          ORDER BY $orderby $sort");
										  
		}
		else{
			
        $query = $this->db->query("SELECT a_company.id, $additional_sql phone
                                          FROM a_company
										  WHERE 1=1 $conditional_sql 
                                          ORDER BY $orderby $sort");
			
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
	
	
	
	function addCustomer($fields,$cid)
    {
		if(intval($cid) > 0)
		{
			if(isset($fields['password']))
			{
				if(trim($fields['password']) != '')
				{
					$qry="UPDATE a_customer SET 
					password='".trim($fields['password'])."'
					WHERE id='".$cid."'";
					$this->db->query($qry);
			
				}
			}
			if(isset($fields['sex']))
			{
				if(trim($fields['sex']) != '')
				{
					$qry="UPDATE a_customer SET 
					sex='".trim($fields['sex'])."'
					WHERE id='".$cid."'";
					$this->db->query($qry);
			
				}
			}
			
			if(isset($fields['dob']))
			{
				if(trim($fields['dob']) != '')
				{
					$qry="UPDATE a_customer SET 
					dob='".trim($fields['dob'])."'
					WHERE id='".$cid."'";
					$this->db->query($qry);
			
				}
			}
			
			$qry="UPDATE a_customer SET 
			firstname='".trim($fields['firstname'])."',
			lastname='".trim($fields['lastname'])."'
			WHERE id='".$cid."'";
			$this->db->query($qry);
			return $cid;
		}
		else
		{
			$this->db->insert('a_customer',$fields);
			if($this->db->affected_rows() > 0)
			{
				return $this->db->insert_id();
			} else {
				return false;
			}
		}
      
    }
	
	function addEmail($fields)
    {
		$sql="SELECT * FROM a_email WHERE email='".trim($fields['email'])."'";
		$query=$this->db->query($sql);
		$status=$query->num_rows();
		if(intval($status) == 0)
		{
			$this->db->insert('a_email',$fields);
			if($this->db->affected_rows() > 0)
			{
				return true;
			} else {
				return false;
			}
		}
		else
		{
			return false;
			
		}
	 }
	
	function addAddress($address1,$zip,$cus_id)
	{
		$sql="SELECT * FROM a_address WHERE customer='".trim($cus_id)."'";
		$query=$this->db->query($sql);
		$status=$query->num_rows();
		if(intval($status) == 0)
		{
			$qry="INSERT a_address SET 
			customer='".trim($cus_id)."',
			`type`='delivery',
			street_line_1='".trim($address1)."',
			zip='".$zip."'";
			$this->db->query($qry);
			return true;
		}
	}
	
	function addPhone($fields)
    {
		$sql="SELECT * FROM a_phone WHERE number='".trim($fields['number'])."' AND customer='".trim($fields['customer'])."'";
		$query=$this->db->query($sql);
		$status=$query->num_rows();
		if(intval($status) == 0)
		{
			 $this->db->insert('a_phone',$fields);
			if($this->db->affected_rows() > 0)
			{
				return true;
			} else {
				return false;
			}
		}
		
    }
	
	function updateCustomer($cid)
	{
		$qry="UPDATE a_customer SET activation_code='".md5($cid)."' WHERE id='".$cid."'";
		$this->db->query($qry);
	}
	
	function getCustomerinfo($cus_id)
	{
			$qry="SELECT a_customer.id,a_customer.firstname,a_customer.lastname,a_customer.sex,a_email.email,a_address.street_line_1,a_address.street_line_2,a_address.floor,a_address.calling_bell,a_address.zip,a_city.city,a_phone.number as phone,a_phone.number as mobile,a_customer.dob,a_email.verified as emailverified,a_company_customer.type,a_company_customer.company FROM a_customer 
			LEFT JOIN a_email ON a_email.customer='".intval($cus_id)."' AND a_email.type='priv' AND a_email.main='1'
			LEFT JOIN a_company_customer ON a_company_customer.customer=a_customer.id AND a_company_customer.customer=a_customer.id AND a_company_customer.status='1'
			LEFT JOIN a_address ON a_address.customer='".intval($cus_id)."' AND a_address.main='1'
			LEFT JOIN a_phone ON a_phone.customer='".intval($cus_id)."' 
			LEFT JOIN a_zip ON a_zip.id=a_address.zip
			LEFT JOIN a_city ON a_city.id=a_zip.city
			WHERE  a_customer.id='".intval($cus_id)."'";
			
			
			 $query = $this->db->query($qry);
			 $results = $query->row_array();
			  if ($query->num_rows() > 0) 
			  {
					return $results;
			  } else {
					return false;
			   }
		
	}
	
	function updateAddress($street_line_1,$street_line_2,$floor,$calling_bell,$zip,$cid)
	{
		$sql="SELECT * FROM a_address WHERE customer='".$cid."'";
		$query=$this->db->query($sql);
		$status=$query->num_rows();
		if(intval($status) == 0)
		{
			$qry="INSERT a_address SET 
			customer='".$cid."',
			street_line_1='".$street_line_1."',
			street_line_2='".$street_line_2."',
			floor='".$floor."',
			calling_bell='".$calling_bell."',
			zip='".$zip."',
			type='delivery',
			main='1'";
			$this->db->query($qry);
		}
		else
		{
			$sql="SELECT * FROM a_address WHERE customer='".$cid."' AND street_line_1='".$street_line_1."' AND zip='".$zip."'";
			$query=$this->db->query($sql);
			$status=$query->num_rows();
			if(intval($status) > 0)
			{
				$qry="UPDATE a_address SET 
				main='0'
				WHERE customer='".$cid."'";
				$this->db->query($qry);
				
				$qry="UPDATE a_address SET 
				street_line_2='".$street_line_2."',
				floor='".mysql_real_escape_string($floor)."',
				calling_bell='".mysql_real_escape_string($calling_bell)."',
				main='1'
				WHERE customer='".$cid."' AND street_line_1='".$street_line_1."' AND zip='".$zip."'";
				$this->db->query($qry);
			}
			else
			{
				$qry="UPDATE a_address SET 
				main='0'
				WHERE customer='".$cid."'";
				$this->db->query($qry);
				
				$qry="INSERT a_address SET 
				customer='".$cid."',
				street_line_1='".$street_line_1."',
				street_line_2='".$street_line_2."',
				floor='".$floor."',
				calling_bell='".$calling_bell."',
				zip='".$zip."',
				type='delivery',
				main='1'";
				$this->db->query($qry);
			}
			
		}
		
		return true;
		
	}
	
	function updatepassword($password,$id)
	{
		$qry="UPDATE a_customer SET password='".md5($password)."' WHERE id='".$id."'";
		$this->db->query($qry);
		if($this->db->affected_rows() > 0)
        {
			return true;
        } else {
			return false;
        }
	}
	
	function customermobileinfo($mobile)
	{
		
		$current_partner=$this->session->userdata['partner'];
		$current_partner_branch=$this->session->userdata['partner_branch'];
		$query=$this->db->query("SELECT b.firstname,b.lastname,b.sex,c.email,a.number as mobile,a.customer as id,d.street_line_1,d.street_line_2,d.floor,d.calling_bell,d.zip,p.name as partner_name,pb.name as partner_branch_name
		FROM a_phone a 
		LEFT JOIN a_customer as b ON a.customer=b.id
		LEFT JOIN a_partner_branch as pb ON pb.id=b.p_b_account_created
		LEFT JOIN a_partner as p ON p.id = pb.partner
		LEFT JOIN a_email as c ON c.customer=a.customer
		LEFT JOIN a_address as d ON d.customer=a.customer
		WHERE a.number='".trim($mobile)."'");
		$total=$query->num_rows();
		$response='';
		if(intval($total) > 0)
		{
			$response=$query->row_array();
			return $response;
			
		}
		else
        {
		
			$query=$this->db->query("SELECT * from a_api WHERE status='1' AND id='1'");
			$res=$query->row();
			$url=$res->path;
			$url.=$mobile;
			//GET Method
			$ch = curl_init();  
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
			curl_setopt($ch,CURLOPT_URL,$url);
			$response=curl_exec($ch);
			curl_close($ch);
			$result=json_decode($response, true);
	
			if(count($result['Results']) > 0)
			{	
				$firstname='';
				$lastname='';
				$gender='male';
				$email='';
				$address1='';
				$zip='';
				if(isset($result['Results'][0]['FirstName']))
				{
					if(trim($result['Results'][0]['FirstName']) != '')
					{
						$firstname=trim($result['Results'][0]['FirstName']);
					}
				}
					
				if(isset($result['Results'][0]['LastName']))
				{
					if(trim($result['Results'][0]['LastName']) != '')
					{
						$lastname=trim($result['Results'][0]['LastName']);
					}
				}
				
				if(isset($result['Results'][0]['Gender']))
				{
					if(trim($result['Results'][0]['Gender']) != '')
					{
						$gender=strtolower(trim($result['Results'][0]['Gender']));
					}
				}
				
				if(isset($result['Results'][0]['Addresses'][0]))
				{
					$address1=$result['Results'][0]['Addresses'][0]['Street'].' '.$result['Results'][0]['Addresses'][0]['HouseNumber'].$result['Results'][0]['Addresses'][0]['HouseLetter'];
					$zip=$result['Results'][0]['Addresses'][0]['Zip'];
					
				}
				$response=array(
				'firstname'=>$firstname,
				'lastname'=>$lastname,
				'sex'=>$gender,
				'email'=>'',
				'mobile'=>$mobile,
				'street_line_1'=>$address1,
				'street_line_2'=>'',				
				'zip'=>$zip,
				'id'=>''
				);
			
				return $response;
			}
			else
			{
				return false;
			}
			
        }
	}
	
	
	function companycustomermobileinfo($mobile)
	{
		
		$current_partner=$this->session->userdata['partner'];
		$current_partner_branch=$this->session->userdata['partner_branch'];
		
		$query=$this->db->query("SELECT b.firstname,b.lastname,b.sex,c.email,a.number as mobile,a.customer as id,d.street_line_1,d.street_line_2,d.floor,d.calling_bell,d.zip,p.name as partner_name,pb.name as partner_branch_name,a_company_customer.type,a_company_customer.company,a_company.name as companyname
		FROM a_phone a 
		LEFT JOIN a_customer as b ON a.customer=b.id
		
		LEFT JOIN a_company_customer ON a_company_customer.customer=b.id
		LEFT JOIN a_company ON a_company.id=a_company_customer.company
		
		LEFT JOIN a_partner_branch as pb ON pb.id=b.p_b_account_created
		LEFT JOIN a_partner as p ON p.id = pb.partner
		LEFT JOIN a_email as c ON c.customer=a.customer
		LEFT JOIN a_address as d ON d.customer=a.customer
		WHERE a.number='".trim($mobile)."' AND a_company_customer.customer=b.id AND a_company_customer.status='1'"); 
		$total=$query->num_rows();
		$response='';
		if(intval($total) > 0)
		{
			$response=$query->row_array();
			return $response;
			
		}
		else
        {
		
			$query=$this->db->query("SELECT * from a_api WHERE status='1' AND id='1'");
			$res=$query->row();
			$url=$res->path;
			$url.=$mobile;
			//GET Method
			$ch = curl_init();  
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
			curl_setopt($ch,CURLOPT_URL,$url);
			$response=curl_exec($ch);
			curl_close($ch);
			$result=json_decode($response, true);
	
			if(count($result['Results']) > 0)
			{	
				$firstname='';
				$lastname='';
				$gender='male';
				$email='';
				$address1='';
				$zip='';
				if(isset($result['Results'][0]['FirstName']))
				{
					if(trim($result['Results'][0]['FirstName']) != '')
					{
						$firstname=trim($result['Results'][0]['FirstName']);
					}
				}
					
				if(isset($result['Results'][0]['LastName']))
				{
					if(trim($result['Results'][0]['LastName']) != '')
					{
						$lastname=trim($result['Results'][0]['LastName']);
					}
				}
				
				if(isset($result['Results'][0]['Gender']))
				{
					if(trim($result['Results'][0]['Gender']) != '')
					{
						$gender=strtolower(trim($result['Results'][0]['Gender']));
					}
				}
				
				if(isset($result['Results'][0]['Addresses'][0]))
				{
					$address1=$result['Results'][0]['Addresses'][0]['Street'].' '.$result['Results'][0]['Addresses'][0]['HouseNumber'].$result['Results'][0]['Addresses'][0]['HouseLetter'];
					$zip=$result['Results'][0]['Addresses'][0]['Zip'];
					
				}
				$response=array(
				'firstname'=>$firstname,
				'lastname'=>$lastname,
				'sex'=>$gender,
				'email'=>'',
				'mobile'=>$mobile,
				'street_line_1'=>$address1,
				'street_line_2'=>'',				
				'zip'=>$zip,
				'id'=>''
				);
			
				return $response;
			}
			else
			{
				return false;
			}
			
        }
	}
	
	
	function updateEmail($email,$cid)
	{
		$sql="SELECT * FROM a_email WHERE customer='".$cid."'";
		$query=$this->db->query($sql);
		$status=$query->num_rows();
		if(intval($status) == 0)
		{
			$qry="INSERT a_email SET 
			customer='".$cid."',
			type='priv',
			email='".$email."',
			main='1'";
			$this->db->query($qry);
		}
		else
		{
			$sql="SELECT * FROM a_email WHERE email='".$email."' AND customer='".$cid."'";
			$query=$this->db->query($sql);
			$status=$query->num_rows();
			if(intval($status) > 0)
			{
				$qry="UPDATE a_email SET 
				main='0'
				WHERE customer='".$cid."'";
				$this->db->query($qry);
				
				$qry="UPDATE a_email SET 
				main='1'
				WHERE customer='".$cid."' AND email='".$email."'";
				$this->db->query($qry);
			}
			else
			{
				$qry="UPDATE a_email SET 
				main='0'
				WHERE customer='".$cid."'";
				$this->db->query($qry);
				
				$qry="INSERT a_email SET 
				customer='".$cid."',
				type='priv',
				email='".$email."',
				main='1'";
				$this->db->query($qry);
			}
			
		}
		
		return true;
		
	}
	
	function getCompanylist($cookie=true)
	{
		$addsql="";
		if($cookie)
		{
			if(isset($_COOKIE['navn_id']))
			{
				$company =$_COOKIE['navn_id'];
				$addsql=" AND id='".$company."'";
			}
		}
		
		$sql="SELECT * FROM a_company WHERE status='1' $addsql";
		$query=$this->db->query($sql);
		$response=$query->result_array();
		return $response;
		
	}
	
	function validateCompanycustomer($cus_id,$result=false)
	{
		$sql="SELECT company FROM a_company_customer WHERE customer='".$cus_id."'";
		$query=$this->db->query($sql);
		if($result)
		{
			$response=$query->result_array();
			return $response;
		}
		else
		{
			$status=$query->num_rows();
		return $status;
		}
		
		
	}
	
	
	function addcompanyCustomer($cus_id,$company)
	{
			$sql="SELECT * FROM a_company_customer WHERE company='".$company."' AND customer='".$cus_id."'";
			$query=$this->db->query($sql);
			$status=$query->num_rows();
			if(intval($status) == 0)
			{
				$qry="INSERT a_company_customer SET 
				customer='".$cus_id."',
				company='".$company."',
				status='1'";
				$this->db->query($qry);
			}
	}
	
	function getCompanyinfo($company)
	{
		$sql="SELECT * FROM a_company WHERE status='1' AND id='".$company."'";
		$query=$this->db->query($sql);
		$response=$query->row_array();
		return $response;
	}
	
	function getMobileDetails()
	{
		
	}
		
	
	
}
/* End of file customer_model.php */
/* Location: ./application/models/customer_model.php */