<?php

if (! defined('BASEPATH')) {

    exit('No direct script access allowed');

}

/**

 * class for perfoming all bugs related data abstraction

 */

class Employee_model extends Super_Model
{

    // -- __construct ----------------------------------------------------------------------------------------------
    function __construct()
    {

        //profiling::

        $this->debug_methods_trail[] = __function__;

        // Call the Model constructor

        parent::__construct();

    }
	
	
    // -- getTotalAnsatt ----------------------------------------------------------------------------------------------
    /**
     * return numbers of employee who are all online now
     * accepts order_by and asc/desc values
     *
     * @return	array
     */
    function getTotalAnsatt()
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;
		$current_partner_branch=$this->session->userdata['partner_branch'];

        //declare
        $conditional_sql = " AND epb.partner_branch ='".$current_partner_branch."' AND epb.status='1' ";
		
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
        //----------monitor transaction start----------
        $this->db->trans_start();
        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT  COUNT(et.employee_p_branch) as count
                                          FROM  a_employee_p_branch as epb ,a_employee_timesheet as et
										  WHERE 1=1 $conditional_sql
										  AND et.employee_p_branch = epb.id AND et.end_time='0000-00-00 00:00:00'
										  AND DATE(et.start_time) = CURDATE()	
                                          ORDER BY epb.id ASC");
		
										  
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
	
    // -- getTotalOrder ----------------------------------------------------------------------------------------------
    /**
     * return number of order place today 
     * accepts order_by and asc/desc values
     *
     * @return	array
     */
    function getTotalOrder()
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;
		$current_partner_branch=$this->session->userdata['partner_branch'];

        //declare
        $conditional_sql = " AND partner_branch ='".$current_partner_branch."'";
		
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
        //----------monitor transaction start----------
        $this->db->trans_start();
        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT  COUNT(id) as count
                                          FROM  a_order as o
										  WHERE 1=1 $conditional_sql
										  AND DATE(o.order_time) = CURDATE()	
                                          ORDER BY o.id ASC");
		
										  
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
	
    // -- getOpeningHours ----------------------------------------------------------------------------------------------
    /**
     * return array of all the rows of opening hours for a branch
     * accepts order_by and asc/desc values
     *
     * @return	array
     */
    function getOpeningHours()
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;
		$current_partner_branch=$this->session->userdata['partner_branch'];

        //declare
        $conditional_sql = " AND partner_branch ='".$current_partner_branch."' AND status='1' ";
		
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
        //----------monitor transaction start----------
        $this->db->trans_start();
        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT TIME_FORMAT(opening_time, '%H:%i') as opening_time,TIME_FORMAT(closing_time, '%H:%i') as closing_time,weekday,
									CASE weekday
										WHEN 'sunday' THEN 'Søndag' 
										WHEN 'monday' THEN 'Mandag' 
										WHEN 'tuesday' THEN 'Tirsdag' 
										WHEN 'wednesday' THEN 'Onsdag' 
										WHEN 'thursday' THEN 'Torsdag' 
										WHEN 'friday' THEN 'Fredag' 
										WHEN 'saturday' THEN 'Lørdag' 
									END  AS weekday_nor
                                          FROM  a_opening_hours
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
	
	
	function checkTodayLogin($employee_p_branch,$datetime)
	{
		$date=date('Y-m-d',strtotime($datetime));
		$query=$this->db->query("SELECT * FROM a_employee_timesheet  WHERE employee_p_branch='".$employee_p_branch."' AND DATE(start_time)='".$date."' ORDER BY id DESC LIMIT 0,1");
		if($query->num_rows() > 0)
		{
			$results = $query->row_array(); //multi row array
			if($results['end_time'] == '0000-00-00 00:00:00')
			{
				return $results['start_time'];
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
	
	function getTotalworkinghours($employee_p_branch,$date)
	{
		$query=$this->db->query("SELECT * FROM a_employee_timesheet  WHERE employee_p_branch='".$employee_p_branch."' AND DATE(start_time)='".$date."' ORDER BY id ASC");
		if($query->num_rows() > 0)
		{
				$results = $query->result_array();
				$total=0;
				 
				foreach($results as $inouttime)
				{
					$in=date('H:i:s',strtotime($inouttime['start_time']));
					if($inouttime['end_time'] == '0000-00-00 00:00:00')
					{
						$out=date('H:i:s');
					}
					else
					{
						$out=date('H:i:s',strtotime($inouttime['end_time']));
					}
					
					$parts = explode(':', $in);
					$seconds = (int) $parts[0] * 3600 + (int) $parts[1] * 60 + (int) $parts[2];
					$total -=$seconds;
					
					$parts = explode(':', $out);
					$seconds = (int) $parts[0] * 3600 + (int) $parts[1] * 60 + (int) $parts[2];
					$total += $seconds; // Add  to total when out
					


				}
				
				$hours = floor($total / 3600);

				$minutes = floor(($total / 60) % 60);

				$seconds = $total % 60;
				
				return "hours: $hours,minutes: $minutes,seconds: $seconds";

		}
		else
		{
			return "hours: 0,minutes: 0,seconds: 0";
		}
	}
	
	function getTotal_working_hours($employee_p_branch,$date)
	{
		$query=$this->db->query("SELECT * FROM a_employee_timesheet  WHERE employee_p_branch='".$employee_p_branch."' AND DATE(start_time)='".$date."' ORDER BY id ASC");
		if($query->num_rows() > 0)
		{
				$results = $query->result_array();
				
				$total=0;
				 
				foreach($results as $inouttime)
				{
					$in=date('H:i:s',strtotime($inouttime['start_time']));
					if($inouttime['end_time'] == '0000-00-00 00:00:00')
					{
						$out=date('H:i:s');
					}
					else
					{
						$out=date('H:i:s',strtotime($inouttime['end_time']));
					}
					
					$parts = explode(':', $in);
					$seconds = (int) $parts[0] * 3600 + (int) $parts[1] * 60 + (int) $parts[2];
					$total -=$seconds;
					
					$parts = explode(':', $out);
					$seconds = (int) $parts[0] * 3600 + (int) $parts[1] * 60 + (int) $parts[2];
					$total += $seconds; // Add  to total when out
					


				}
				
			
				
				$hours = floor($total / 3600);

				$minutes = floor(($total / 60) % 60);

				$seconds = $total % 60;
					
				return "$hours:$minutes:$seconds";

		}
		else
		{
			return "00:00:00";
		}
	}
	
	function startLogin($employee_p_branch,$datetime)
	{
		$date=date('Y-m-d',strtotime($datetime));
		$query=$this->db->query("SELECT * FROM a_employee_timesheet  WHERE employee_p_branch='".$employee_p_branch."' AND DATE(start_time)='".$date."' ORDER BY id DESC LIMIT 0,1");
		if($query->num_rows() == 0)
		{
			$qry="INSERT INTO a_employee_timesheet 
						SET 
						`employee_p_branch`='".$employee_p_branch."',
						`start_time`='".$datetime."'";
			$query=$this->db->query($qry);
			return $datetime;
		}
		else
		{
			$results = $query->row_array(); //multi row array
			if($results['end_time'] == '0000-00-00 00:00:00')
			{
				return $results['start_time'];
			}
			else
			{
				$qry="INSERT INTO a_employee_timesheet 
						SET 
						`employee_p_branch`='".$employee_p_branch."',
						`start_time`='".$datetime."'";
				$query=$this->db->query($qry);
				return $datetime;
			}
			
			
		}
		
		
	}
	
	function stopLogin($employee_p_branch,$datetime)
	{
		$date=date('Y-m-d',strtotime($datetime));
		$query=$this->db->query("SELECT * FROM a_employee_timesheet  WHERE employee_p_branch='".$employee_p_branch."' AND DATE(start_time)='".$date."' AND end_time='0000-00-00 00:00:00'");
		
		if($query->num_rows() > 0)
		{
			$qry="UPDATE a_employee_timesheet 
						SET 
						`end_time`='".$datetime."' WHERE employee_p_branch='".$employee_p_branch."' AND DATE(start_time)='".$date."' AND end_time='0000-00-00 00:00:00'";
						$query=$this->db->query($qry);
			return true;
		}
		else
		{
			return false;
		}
		
	}
	//logout all employees who are all not logout  when sign out the s/w
	function stopAllLogin()
	{
		$date=date('Y-m-d');
		$datetime=date('Y-m-d H:s:i');
		$query=$this->db->query("SELECT * FROM a_employee_timesheet  WHERE DATE(start_time)='".$date."' AND end_time='0000-00-00 00:00:00'");
		
		if($query->num_rows() > 0)
		{
			$qry="UPDATE a_employee_timesheet 
						SET 
						`end_time`='".$datetime."' WHERE  DATE(start_time)='".$date."' AND end_time='0000-00-00 00:00:00'";
						$query=$this->db->query($qry);
			return true;
		}
		else
		{
			return false;
		}
		
	}
	
	function getValidLogins($username,$pwd)
	{
		$sql="SELECT * FROM api_access_users WHERE username='".$username."' AND password='".md5($pwd)."' AND status='1'";
		$query=$this->db->query($sql);
		$status=$query->num_rows();
		if(intval($status) > 0)
		{
			$userinfo=$query->row();
			return array($userinfo->username=>$pwd);
		}
		else
		{	
			return FALSE;
		}
		
	}
	

	
    // -- getEmployeeDetail ----------------------------------------------------------------------------------------------
    /**
     * return  the  row of a employee in table
     * accepts order_by and asc/desc values
     * 
     * @param $orderby sorting
     * @param $sort sort order
     * @return	array
     */

    function getEmployeeDetail($id='',$field='employee')
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

		if($field=='employee'){
			 $conditional_sql = " AND e.id = $id";
		}
		else{
			$conditional_sql = " AND ep.id = $id";
		}



        //check if sorting type was passed
        $sort = ($sort == 'asc' || $sort == 'desc') ? $sort : 'ASC';

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *,CONCAT(IFNULL(fname,''),' ',IFNULL(mname,''),' ',IFNULL(lname,'')) AS name,CONCAT(IFNULL(SUBSTR(fname, 1, 1),''),IFNULL(SUBSTR(mname, 1, 1),''),IFNULL(SUBSTR(lname, 1, 1),'')) AS initial,partner_branch,employee_type,employment_type,wage_type,job_percentage,jobtitle,start_date,end_date,salary
                                          FROM a_employee as e
										  LEFT JOIN a_employee_p_branch as ep ON e.id = ep.employee 
										  WHERE 1=1 $conditional_sql
										  
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
	
	function loginstafflist($staffs,$date)
	{
	
		
		$staffids=implode(',',$staffs);
		$query=$this->db->query("SELECT * FROM a_employee_timesheet  WHERE employee_p_branch IN(".$staffids.") AND DATE(start_time)='".$date."' ORDER BY id DESC");
		
		
		if($query->num_rows() > 0)
		{
				$results = $query->result_array();
				foreach($results as $key=>$inouttime)
				{	
					if($inouttime['end_time'] != '0000-00-00 00:00:00')
					{
						$total=0;
						$in=date('H:i:s',strtotime($inouttime['start_time']));
						$out=date('H:i:s',strtotime($inouttime['end_time']));
						$parts = explode(':', $in);
						$seconds = (int) $parts[0] * 3600 + (int) $parts[1] * 60 + (int) $parts[2];
						$total -=$seconds;
						
						$parts = explode(':', $out);
						$seconds = (int) $parts[0] * 3600 + (int) $parts[1] * 60 + (int) $parts[2];
						$total += $seconds; // Add  to total when out
						
						$hours = floor($total / 3600);
						$minutes = ($total / 60) % 60;
						$seconds = $total % 60;
						$totalhours=sprintf("%02d:%02d", $hours, $minutes);
						$results[$key]['totalhours']=$totalhours;
					}
					
					
					$results[$key]['in']=date('H:i',strtotime($inouttime['start_time']));
					
					$results[$key]['out']=date('H:i',strtotime($inouttime['end_time']));
					
					
				}
				
			return $results;
		}
		else
		{
			return false;
		}
	}
	
	 // -- getEmployeebranchDetail ----------------------------------------------------------------------------------------------
    /**
     * return  the  row of a employee in table
     * accepts order_by and asc/desc values
     * 
     * @param $orderby sorting
     * @param $sort sort order
     * @return	array
     */

    function getEmployeebranchDetail($id='',$orderid='',$employee_p_branch='')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = "";
		$additional_sql = "";
		
		if(intval($id) > 0 && intval($orderid) > 0)
		{
			$additional_sql.=" AND ( a_order_log.orderline = '".$id."' OR a_order_log.order = '".$orderid."')";
		}
		
		if(intval($employee_p_branch) > 0)
		{
			$additional_sql.=" AND employee_p_branch='".$employee_p_branch."'";
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
        $query = $this->db->query("SELECT *,CONCAT(IFNULL(fname,''),' ',IFNULL(mname,''),' ',IFNULL(lname,'')) AS name,CONCAT(IFNULL(SUBSTR(fname, 1, 1),''),IFNULL(SUBSTR(mname, 1, 1),''),IFNULL(SUBSTR(lname, 1, 1),'')) AS initial,ep.partner_branch,employee_type,employment_type,wage_type,job_percentage,jobtitle,start_date,end_date,salary
                                          FROM a_employee as e
										  LEFT JOIN a_employee_p_branch as ep ON e.id = ep.employee 
										  LEFT JOIN a_order_log ON ep.id = a_order_log.employee_p_branch 
										  WHERE 1=1 $conditional_sql
										  AND a_order_log.status = '9'
										  $additional_sql
										  GROUP BY a_order_log.orderline
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
	

    // -- getOrderstatus ----------------------------------------------------------------------------------------------
    /**
     * return order status
     * accepts order_by and asc/desc values
     *
     * @return	array
     */
    function getOrderstatus($oid)
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;
		$current_partner_branch=$this->session->userdata['partner_branch'];

        //declare
        $conditional_sql = "";
		
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
        //----------monitor transaction start----------
        $this->db->trans_start();
        //_____ACTUAL QUERY_______
        $query = $this->db->query("SELECT  status  
											FROM  a_order_log  
											WHERE 1=1 AND `order` = $oid AND regtime IN (SELECT  MAX(`regtime`) as regtime FROM  a_order_log  WHERE 1=1 AND `order` = $oid ORDER BY id DESC) ORDER BY id DESC 
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
	
	
	
	/*get employee calendar of a month*/
	function getCalendar($employee,$month)
	{
		
		$query=$this->db->query("SELECT *,DATE_FORMAT(start_time,'%d.%m.%Y') as sdate  FROM a_employee_timesheet  WHERE employee_p_branch ='".$employee."' AND MONTH(start_time)='".$month."' ORDER BY id ASC");
		
		
		if($query->num_rows() > 0)
		{
				$results = $query->result_array();
				$month_total  = 0;
				foreach($results as $key=>$inouttime)
				{	
					if($inouttime['end_time'] != '0000-00-00 00:00:00')
					{
						$total=0;
						$in=date('H:i:s',strtotime($inouttime['start_time']));
						$out=date('H:i:s',strtotime($inouttime['end_time']));
						$parts = explode(':', $in);
						$seconds = (int) $parts[0] * 3600 + (int) $parts[1] * 60 + (int) $parts[2];
						$total -=$seconds;
						
						$parts = explode(':', $out);
						$seconds = (int) $parts[0] * 3600 + (int) $parts[1] * 60 + (int) $parts[2];
						$total += $seconds; // Add  to total when out
						$month_total += $total;
						$hours = floor($total / 3600);
						$minutes = ($total / 60) % 60;
						$seconds = $total % 60;
						$totalhours=sprintf("%02d:%02d", $hours, $minutes);
						$results[$key]['totalhours']=$totalhours;
					}
					
					
					$results[$key]['in']=date('H:i',strtotime($inouttime['start_time']));
					
					$results[$key]['out']=date('H:i',strtotime($inouttime['end_time']));
					
				}
				
				$hours = floor($month_total / 3600);
				$minutes = ($month_total / 60) % 60;
				$seconds = $month_total % 60;
				$month_total_hours=sprintf("%02d:%02d", $hours, $minutes);
				$results[0]['month_total_hours']=$month_total_hours;
				
			return $results;
		}
		else
		{
			return false;
		}
	}
	
	

}

/* End of file employee_model.php */
/* Location: ./application/models/employee_model.php */