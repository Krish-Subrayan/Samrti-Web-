<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all payments related data abstraction
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Payments_model extends Super_Model
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

    // -- getInvoicePayments ----------------------------------------------------------------------------------------------
    /**
     * return all payments attached to a particulat invoice. Based on invoice ID
     *    
     * @param numeric $id 
     * @return array
     */

    function getInvoicePayments($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [invoice id=$id]", '');
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM payments
                                          WHERE payments_invoice_id = $id");

        $results = $query->result_array(); //single row array

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;
    }

    // -- sumInvoicePayments ----------------------------------------------------------------------------------------------
    /**
     * sum payments for a particular invoices
     *   
     * @param numeric $id (optional)
     * @return numeric  [sum of payments]
     */

    function sumInvoicePayments($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valid id, return false
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT SUM(payments_amount) AS sum
                                          FROM payments 
                                          WHERE payments_invoice_id	= $id");

        $results = $query->row_array(); //single row array
        $results = $results['sum'];

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        if (is_numeric($results)) {
            return $results;
        } else {
            return 0;
        }
    }

    // -- periodicPaymentsCount ----------------------------------------------------------------------------------------------
    /**
     * returns array of payments (COUNT) made 'today', 'this_week', 'this_month', 'last_month', 'this_year', 'last_year'
     * results can be for 'all_payments' or a numeric 'id for a given client or project
     *
     * @param numeric $id this can be client id pr project id
     * @param string $id_type his can be 'client' / 'project'
     * @return array
     */

    function periodicPaymentsCount($id = '', $id_type = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //are we searching for all payments or for client/project
        if (is_numeric($id) && ($id_type == 'client' || $id_type == 'project')) {

        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT (SELECT COUNT(payments_id)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y%m%d') = DATE_FORMAT(NOW(),'%Y%m%d')) AS today,
                                          (SELECT COUNT(payments_id)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y%m') = DATE_FORMAT(NOW(),'%Y%m')) AS this_month,
                                          (SELECT COUNT(payments_id)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y%m') = DATE_FORMAT(NOW() - INTERVAL 1 MONTH,'%Y%m')) AS last_month,
                                          (SELECT COUNT(payments_id)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y') = DATE_FORMAT(NOW(),'%Y')) AS this_year,
                                          (SELECT COUNT(payments_id)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y') = DATE_FORMAT(NOW() - INTERVAL 1 YEAR,'%Y')) AS last_year
                                          FROM payments
                                          LIMIT 1");
        $results = $query->row_array();

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;

    }

    // -- periodicPaymentsSum ----------------------------------------------------------------------------------------------
    /**
     * returns array of payments (SUM) made 'today', 'this_week', 'this_month', 'last_month', 'this_year', 'last_year'
     * results can be for 'all_payments' or a numeric 'id for a given client or project
     *
     * @param numeric $id this can be client id pr project id
     * @param string $id_type this can be 'client' / 'project'
     * @return rray
     */

    function periodicPaymentsSum($id = '', $id_type = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //are we searching for all payments or for client/project
        if (is_numeric($id) && ($id_type == 'client' || $id_type == 'project')) {

        }

        //conditional sql
        if ($id_type == 'client') {
            $conditional_sql = " AND payments_client_id = '$id'";
        }
        if ($id_type == 'project') {
            $conditional_sql = " AND payments_project_id = '$id'";
        }
        
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT (SELECT SUM(payments_amount)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y%m%d') = DATE_FORMAT(NOW(),'%Y%m%d')
                                            $conditional_sql) AS today,
                                          (SELECT SUM(payments_amount)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y%m%d') = DATE_FORMAT(NOW() - INTERVAL 1 DAY,'%Y%m%d')
                                            $conditional_sql) AS yesterday,
                                          (SELECT SUM(payments_amount)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y%m') = DATE_FORMAT(NOW(),'%Y%m')
                                            $conditional_sql) AS this_month,
                                          (SELECT SUM(payments_amount)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y%m') = DATE_FORMAT(NOW() - INTERVAL 1 MONTH,'%Y%m')
                                            $conditional_sql) AS last_month,
                                          (SELECT SUM(payments_amount)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y') = DATE_FORMAT(NOW(),'%Y')
                                            $conditional_sql) AS this_year,
                                          (SELECT SUM(payments_amount)
                                            FROM payments 
                                            WHERE DATE_FORMAT(payments_date,'%Y') = DATE_FORMAT(NOW() - INTERVAL 1 YEAR,'%Y')
                                            $conditional_sql) AS last_year
                                          FROM payments
                                          LIMIT 1");
        $results = $query->row_array();

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;

    }

    // -- addPayment ----------------------------------------------------------------------------------------------
    /**
     * add a new payment
     *
     *
     * @param array $thedata normally the $_post array
     * @return array
     */

    function addPayment($thedata = array())
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //turn array into vars
        foreach ($thedata as $key => $value) {
            $$key = $this->db->escape($value);
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO payments (
                                          payments_invoice_id,
                                          payments_project_id,
                                          payments_client_id,
                                          payments_amount,
                                          payments_currency_code,
                                          payments_transaction_id,
                                          payments_date,
                                          payments_method,
                                          payments_notes
                                          )VALUES(
                                          $payments_invoice_id,
                                          $payments_project_id,
                                          $payments_client_id,
                                          $payments_amount,
                                          $payments_currency_code,
                                          $payments_transaction_id,
                                          NOW(),
                                          $payments_method,
                                          $payments_notes)");

        $results = $this->db->insert_id(); //last item insert id

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        if ($results > 0) {
            return $results;
        } else {
            return false;
        }
    }

    // -- searchPayments ----------------------------------------------------------------------------------------------
    /**
     * list/search payments made
     *
     *
     * @param numeric $offset
     * @param	string $type 'search', 'count'
     * @param	mixed $id'all', 'numeric id', ''
     * @param	mixed $list_by 'all', 'client', 'project'
     * @return	mixed table array | bool (false)]
     */

    function searchPayments($offset = 0, $type = 'search', $id = '', $list_by = 'all')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';
        $limiting = '';

        //system page limit or set default 25
        $limit = (is_numeric($this->data['settings_general']['results_limit'])) ? $this->data['settings_general']['results_limit'] : 25;

        //---------------SEARCH FORM CONDITONAL STATMENTS------------------
        if (is_numeric($this->input->get('payments_id'))) {
            $payments_id = $this->db->escape($this->input->get('payments_id'));
            $conditional_sql .= " AND payments.payments_id = $payments_id";
        }
        if ($this->input->get('payments_transaction_id')) {
            $payments_transaction_id = $this->db->escape($this->input->get('payments_transaction_id'));
            $conditional_sql .= " AND payments.payments_transaction_id = $payments_transaction_id";
        }
        if ($this->input->get('payments_method')) {
            $payments_method = $this->db->escape($this->input->get('payments_method'));
            $conditional_sql .= " AND payments.payments_method = $payments_method";
        }
        if ($this->input->get('payment_date')) {
            $payment_date = $this->db->escape($this->input->get('payment_date'));
            $conditional_sql .= " AND payments.payments_date = $payment_date";
        }
        if ($this->input->get('start_date')) {
            $start_date = $this->db->escape($this->input->get('start_date'));
            $conditional_sql .= " AND payments.payments_date >= $start_date";
        }
        if ($this->input->get('end_date')) {
            $end_date = $this->db->escape($this->input->get('end_date'));
            $conditional_sql .= " AND payments.payments_date <= $end_date";
        }
        if (is_numeric($this->input->get('payments_project_id'))) {
            $payments_project_id = $this->db->escape($this->input->get('payments_project_id'));
            $conditional_sql .= " AND payments.payments_project_id = $payments_project_id";
        }
        if (is_numeric($this->input->get('payments_client_id'))) {
            $payments_client_id = $this->db->escape($this->input->get('payments_client_id'));
            $conditional_sql .= " AND payments.payments_client_id = $payments_client_id";
        }
        if (is_numeric($this->input->get('payments_invoice_id'))) {
            $payments_invoice_id = $this->db->escape($this->input->get('payments_invoice_id'));
            $conditional_sql .= " AND payments.payments_invoice_id = $payments_invoice_id";
        }

        //---------------CLIENT - PROJECT - ALL -- INVOICES------------------
        if ($list_by != 'all' && is_numeric($id)) {
            switch ($list_by) {

                case 'client':
                    $conditional_sql .= " AND payments.payments_client_id = $id";
                    break;

                case 'project':
                    $conditional_sql .= " AND payments.payments_project_id = $id";
                    break;
            }
        }

        //---------------URL QUERY - ORDER BY STATMENTS-------------------------
        $sort_order = ($this->uri->segment(5) == 'desc') ? 'desc' : 'asc';
        $sort_columns = array(
            'sortby_id' => 'payments.payments_id',
            'sortby_date' => 'payments.payments_date',
            'sortby_amount' => 'payments.payments_amount',
            'sortby_method' => 'payments.payments_method',
            'sortby_project' => 'payments.payments_project_id',
            'sortby_invoice' => 'payments.payments_invoice_id',
            'sortby_client' => 'payments.invoices_status');
        $sort_by = (array_key_exists('' . $this->uri->segment(6), $sort_columns)) ? $sort_columns[$this->uri->segment(6)] : 'payments.payments_id';
        $sorting_sql = "ORDER BY $sort_by $sort_order";

        //---------------IF SEARCHING - LIMIT FOR PAGINATION----------------------
        if ($type == 'search' || $type == 'results') {
            $limiting = "LIMIT $limit OFFSET $offset";
        }

        //clients dashboard limitation
        if (is_numeric($this->session->userdata('client_users_clients_id'))) {
            $conditional_sql .= " AND payments.payments_client_id = " . $this->session->userdata('client_users_clients_id');
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT payments.*, clients.*, projects.*
                                            FROM payments
                                            LEFT OUTER JOIN clients
                                            ON clients.clients_id = payments.payments_client_id
                                            LEFT OUTER JOIN projects
                                            ON projects.projects_id = payments.payments_project_id
                                            WHERE 1 = 1
                                            $conditional_sql
                                            $sorting_sql
                                            $limiting");
        //results (search or rows)
        //rows are used by pagination class & results are used by tbs block merge
        if ($type == 'search' || $type == 'results') {
            $results = $query->result_array();
        } else {
            $results = $query->num_rows();
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

    // -- getByTransactionID ----------------------------------------------------------------------------------------------
    /**
     * retrieve a payment based on its payment transaction ID
     *
     *
     * @param	string $transaction_id transaction id 
     * @return	array
     */

    function getByTransactionID($transaction_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if ($transaction_id == '') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data ($transaction_id)", '');
            return false;
        }

        //escape params items
        $transaction_id = $this->db->escape($transaction_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //clients dashboard limitation
        if (is_numeric($this->session->userdata('client_users_clients_id'))) {
            $conditional_sql .= " AND payments.payments_client_id = " . $this->session->userdata('client_users_clients_id');
        }

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM payments 
                                          WHERE payments_transaction_id = $transaction_id");

        $results = $query->row_array(); //single row array

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
    }

    // -- updatePaymentStatus ----------------------------------------------------------------------------------------------
    /**
     * update payment status (normally used by IPN updates)
     *
     * @param string $status status
     * @param string $transaction_id  transaction id
     * @return bool
     */

    function updatePaymentStatus($transaction_id = '', $status = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if ($status == '' || $transaction_id == '') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data ($status) ($transaction_id)", '');
            return false;
        }

        //escape params items
        $status = $this->db->escape($status);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE payments
                                          SET 
                                          payments_transaction_status = $status,
                                          WHERE payments_transaction_id = $transaction_id");

        $results = $this->db->affected_rows(); //affected rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }
    }

    // -- deletePayment ----------------------------------------------------------------------------------------------
    /**
     * delete invoice payment
     * @param numeric $id reference id of item(s)
     * @return	bool
     */

    function deletePayment($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            //ajax-log error to file
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: deleting invoice item failed (id: $id is invalid)]");
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM payments
                                          WHERE payments_id = $id");

        $results = $this->db->affected_rows(); //affected rows

        //----------monitor transaction end----------
        $this->db->trans_complete();
        $transaction_result = $this->db->trans_status();
        if ($transaction_result === false) {

            //log this error
            $db_error = $this->db->_error_message();
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Database Error -  $db_error]");

            return false;
        }

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        if ($results > 0 || $transaction_result === true) {
            return true;
        } else {
            return false;
        }
    }

    // -- bulkDelete ----------------------------------------------------------------------------------------------
    /**
     * bulk delete based on list of project ID's
     * typically used when deleting project/s 
     *
     *
     * @param	string $projects_list a mysql array/list formatted projects list [e.g. 1,2,3,4]
     * @return	bool
     */

    function bulkDelete($projects_list = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //flow control
        $next = true;

        //sanity check - ensure we have a valid projects_list, with only numeric id's
        $lists = explode(',', $projects_list);
        for ($i = 0; $i < count($lists); $i++) {
            if (!is_numeric(trim($lists[$i]))) {
                //log error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Bulk Deleting payments, for projects($clients_projects) failed. Invalid projects list]");
                //exit
                return false;
            }
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        if ($next) {
            $query = $this->db->query("DELETE FROM payments
                                          WHERE payments_project_id IN($projects_list)");
        }
        $results = $this->db->affected_rows(); //affected rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }
    }
	
	// -- add Customer Payment ----------------------------------------------------------------------------------------------
    /**
     * add a new customer payment
     *
     *
     * @param array $thedata normally the $_post array
     * @return array
     */

    function addCustomerPayment($paymentarray,$addsaldo='0')
    {
	
		//profiling::
        $this->debug_methods_trail[] = __function__;
		//declare
        $conditional_sql = '';
		//----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
		
		$current_partner = $this->session->userdata['partner'];
		$current_partner_branch=$this->session->userdata['partner_branch'];
		
		$addsql=" partner = '".$current_partner."',";
		$addsql.=" partner_branch = '".$current_partner_branch."',";
		
		$add_sql="  AND partner = '".$current_partner."'";
		$add_sql.="  AND partner_branch = '".$current_partner_branch."'";
	
		
		//partner_branch 
		
		if(isset($paymentarray['order']))
		{
			$addsql.="`order`='".$paymentarray['order']."',";
			$add_sql.=" AND `order`='".$paymentarray['order']."' AND `orderline` IS NULL";
		}
		
		if(isset($paymentarray['orderline']))
		{
			$addsql.="`orderline`='".$paymentarray['orderline']."',";
			$add_sql.=" AND `orderline`='".$paymentarray['orderline']."'";
		}
		
		if($paymentarray['type'] == 'out'){
			
			$sql="SELECT * FROM a_customer_account_log  WHERE customer = '".$paymentarray['customer']."' AND `type`='out' $add_sql";
			$squery = $this->db->query($sql);
			$paystatus = $squery->num_rows();
			if($paystatus > 0)
			{
				 return false;
			}
		}
		
		
		if(isset($paymentarray['gift_card']))
		{
			if(intval($paymentarray['gift_card']) > 0)
			{
				$addsql.="`gift_card`='".$paymentarray['gift_card']."',";
			}
		}
		
		
		if($paymentarray['in_type'] == 'complaint'){
				$addsql.="`complaint_reason`='".$paymentarray['complaint_reason']."',";
		}
		
		
		$query = $this->db->query("SELECT * FROM a_customer_account_log WHERE customer = '".$paymentarray['customer']."' AND `type`='in' $add_sql");
		$p_b_account_status=$query->num_rows();
		
		// dont add faktura when the customer selected no for sending faktura
		if($addsaldo==0){
			if($paymentarray['in_type'] == 'invoice'){
				$sql="SELECT invoice,status FROM a_customer_account_partner  WHERE customer = '".$paymentarray['customer']."'";
				$squery = $this->db->query($sql);
				$results = $squery->row_array(); //row array
				if($results['status'] == 1){
					if($results['invoice'] == 0){
						return ;
					}
				}
			}
		}
		
		//_____SQL QUERY_______
		$qry="INSERT INTO a_customer_account_log SET 
		`type`='".$paymentarray['type']."',
		`in_type`='".$paymentarray['in_type']."',
		`in_status`='".$paymentarray['in_status']."',
		`customer`='".$paymentarray['customer']."',
		 $addsql
		`amount`='".$paymentarray['amount']."',
		 employee='".$this->session->userdata['current_staff']."',
		`regtime`='".$paymentarray['regtime']."'";
		
        $query = $this->db->query($qry);

        $results = $this->db->insert_id(); //last item insert id
		
		

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');
		//debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------
		if ($results > 0) {
		
			if($paymentarray['in_type'] == 'gift_card')
			{
				$sql="UPDATE a_gift_card SET status='used' WHERE id='".$paymentarray['gift_card']."' $add_sql";
				$this->db->query($sql);
			}
            return $results;
        } else {
            return false;
        }
    }
	
	
	/*update customer account balance*/
	function updateCustomerBalance($cusotmer,$amount,$in_status='',$type='credit',$status='1',$addsaldo='0')
	{
		
		$current_partner=$this->session->userdata['partner'];
		$current_partner_branch=$this->session->userdata['partner_branch'];
		if($in_status != '')
		 $filed = $in_status;
		
        $query = $this->db->query("SELECT  paid,pending	 FROM a_customer_account_partner WHERE customer='".$cusotmer."' AND partner ='".$current_partner."'");
        $results = $query->row_array(); //row array
		
		if(count($results) > 0 ){
			$paid  = $results['paid'];
			$pending  = $results['pending'];
			
			if($type=='credit'){
				$amount = $results[$in_status] + $amount;
				
				// dont add faktura when teh customer selected no for sending faktura
				if($addsaldo==0){
					if($in_status == 'pending'){
						$sql="SELECT invoice,status FROM a_customer_account_partner  WHERE customer = '".$cusotmer."'";
						$squery = $this->db->query($sql);
						$results = $squery->row_array(); //row array
						if($results['status'] == 1){
							if($results['invoice'] == 0){
								return ;
							}
						}
					}
				}
				
				
				$qry="UPDATE a_customer_account_partner SET $filed='".$amount."' WHERE customer='".$cusotmer."' AND partner ='".$current_partner."'";
				$this->db->query($qry);
				return true;
			}
			else{
				/*
				$diff = $paid - $amount;
				if($diff < 0){
					$paid = 0;
					$pending =  $pending + $diff;
				}
				else{
					$paid = $diff;
				}
				
				$qry="UPDATE a_customer_account_partner SET paid='".$paid."',pending='".$pending."' WHERE customer='".$cusotmer."' AND partner ='".$current_partner."'";
				$this->db->query($qry);
				return true;*/
				
				if($paid > $amount)
				{
					$paid = $paid - $amount;
					$qry="UPDATE a_customer_account_partner SET paid='".$paid."' WHERE customer='".$cusotmer."' AND partner ='".$current_partner."'";
					$this->db->query($qry);
					return true;
				}
				else
				{
					//$paid = $amount - $paid;
					$paid = $paid - $amount;
					$qry="UPDATE a_customer_account_partner SET paid='".$paid."' WHERE customer='".$cusotmer."' AND partner ='".$current_partner."'";
					$this->db->query($qry);
					return true;
				}
							
			}
		}
		else{
			
			$qry="INSERT INTO a_customer_account_partner SET 
			 $filed='".$amount."',
			 status='".$status."',
			 customer='".$cusotmer."',partner ='".$current_partner."',partner_branch = '".$current_partner_branch."'";
			
			$query = $this->db->query($qry);
			$results = $this->db->insert_id(); //last item insert id
			
		}
	}
	
	
	
    // -- getAccountBalance ----------------------------------------------------------------------------------------------
    /**
     * return account balance of a customer
     * accepts order_by and asc/desc values
     * 
     * @param $orderby sorting
     * @param $sort sort order
     * @return	array
     */
    function getAccountBalance($cusotmer)
    {
        //profiling::
        $this->debug_methods_trail[] = __function__;
        //declare
        $conditional_sql = '';
        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
		
        //_____SQL QUERY_______
		
		$current_partner=$this->session->userdata['partner'];

        //$query = $this->db->query("SELECT  p_b_paid_account_balance as paid,p_b_pending_account_balance as pending FROM a_customer WHERE id='".$cusotmer."'");
		
        $query = $this->db->query("SELECT  paid,pending	 FROM a_customer_account_partner WHERE customer='".$cusotmer."' AND partner ='".$current_partner."'");
										  
										  
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

	
	//--getfullSaldo-------------------------
    /**
     * retrieve a saldo based on its customer
     *
     *
     * @param	int $customer
     * @return	float
     */

    function getfullSaldo($customer=0)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if ($customer == '0') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data ($customer)", '');
            return false;
        }
		//escape params items
       
		//----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
		//_____SQL QUERY_______
		
        $query = $this->db->query("SELECT sum(amount) as credit FROM a_customer_account_log WHERE customer = '".$customer."' AND (in_status='paid' OR in_status='pending') AND `type`='in'");
		$results = $query->row_array(); //single row array
		
		$cr=$results['credit'];
		
		//----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
       //---return
	   
	   
	   //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
		
		
		$query = $this->db->query("SELECT sum(amount) as debit FROM a_customer_account_log WHERE customer = '".$customer."' AND (in_status='paid' OR in_status='pending') AND `type`='out'");
		$results = $query->row_array(); //single row array
		
		
		//----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
       //---return
	   
	   
		
		$dr=$results['debit'];
		
		$totalamount=$cr-$dr;
		
		
        return $totalamount;
    }
	
	
	
	//--getSaldo-------------------------
    /**
     * retrieve a saldo based on its customer
     *
     *
     * @param	int $customer
     * @return	float
     */

    function getSaldo($customer=0)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if ($customer == '0') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data ($customer)", '');
            return false;
        }
		//escape params items
       
		//----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
		//_____SQL QUERY_______
		
        $query = $this->db->query("SELECT sum(amount) as credit FROM a_customer_account_log WHERE customer = '".$customer."' AND in_status='paid' AND `type`='in'");
		$results = $query->row_array(); //single row array
		
		$cr=$results['credit'];
		
		//----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
       //---return
	   
	   
	   //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
		
		
		$query = $this->db->query("SELECT sum(amount) as debit FROM a_customer_account_log WHERE customer = '".$customer."' AND (in_status='paid' OR in_status='pending') AND `type`='out'");
		
		$results = $query->row_array(); //single row array
		
		
		//----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
       //---return
	   
	   
		
		/*$dr=$results['debit'];
		$totalamount=0;
		if($cr >= $dr)
		{
			$totalamount=$cr-$dr;
		}*/
		
        return $cr;
    }
	
	//--getSaldopending-------------------------
    /**
     * retrieve a saldo based on its customer
     *
     *
     * @param	int $customer
     * @return	float
     */

    function getSaldopending($customer=0)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if ($customer == '0') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data ($customer)", '');
            return false;
        }
		//escape params items
		
		 $query = $this->db->query("SELECT sum(amount) as credit FROM a_customer_account_log WHERE customer = '".$customer."' AND in_status='paid' AND `type`='in'");
		 $results = $query->row_array(); //single row array
		 
		 $cr=$results['credit'];
		 
		 $query = $this->db->query("SELECT sum(amount) as debit FROM a_customer_account_log WHERE customer = '".$customer."' AND (in_status='paid' OR in_status='pending') AND `type`='out'");
		 $results = $query->row_array(); //single row array
		 
		 $dr=$results['debit'];
		 
		 $ptotalamount=$cr-$dr;
		 
		//echo $ptotalamount;exit;
       
		//----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
		//_____SQL QUERY_______
		
        $query = $this->db->query("SELECT sum(amount) as credit FROM a_customer_account_log WHERE customer = '".$customer."' AND in_status='pending' AND `type`='in'");
		$results = $query->row_array(); //single row array
		$totalamount = $results['credit'];
		
		 /*if(0 > $ptotalamount)
		 {
			$totalamount=$totalamount+$ptotalamount;
		 }*/
		
		
		//----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
       //---return
	   
	   
		
        return $totalamount;
    }
	
	
	//--getPaidAmount-------------------------
    /**
     * retrieve a saldo based on its customer
     *
     *
     * @param	int $customer
     * @return	float
     */

    function getPaidAmount($customer=0)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if ($customer == '0') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data ($customer)", '');
            return false;
        }
		//escape params items
		 $query = $this->db->query("SELECT sum(amount) as debit FROM a_customer_account_log WHERE customer = '".$customer."' AND (in_status='paid' OR in_status='pending') AND `type`='out'");
		 $results = $query->row_array(); //single row array
		 
		 $totalamount = $results['debit'];
		 
		
		//----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
       //---return
	   
	   
		
        return $totalamount;
    }
	
	
	//--customer saldo account status-------------------------
    /**
     * retrieve a saldo based on its customer
     *
     *
     * @param	int $customer
     * @return	float
     */

	function getSaldostatus($customer,$checkstatus=true,$status=true)
	{
	
		 if($checkstatus)
		 {
			if($this->session->userdata['partner_branch'] == 14 || $this->session->userdata['partner_branch'] == 28)
			{
				return 1;	 
			}
		 }
	
		 
		//profiling::
        $this->debug_methods_trail[] = __function__;

		 $conditional_sql = "";
        //declare
		if($status)
		{
			 $conditional_sql = " AND status='1'";
		}
       

        //validate id
        if ($customer == '0') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data ($customer)", '');
            return false;
        }
		//escape params items
       
		//----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
		//_____SQL QUERY_______
		
		$current_partner=$this->session->userdata['partner'];

		
        //$query = $this->db->query("SELECT * FROM a_customer_account_log WHERE customer = '".$customer."' AND `type`='in' AND partner = '".$current_partner."' ");
		
        $query = $this->db->query("SELECT * FROM a_customer_account_partner WHERE customer = '".$customer."' AND partner = '".$current_partner."' $conditional_sql");
		
		$results = $query->num_rows();
		
		//----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
       //---return
 
		
        return $results;
	}
	
	/* update order payment status checked all orderline payments in paid status*/
	function updateorderPaymentstatus($orderid)
	{	
		$orderlines = $this->orders_model->getOrderLine($orderid);
		$paymentstatus=1;
		if (count($orderlines) > 0) 
		{
				for($i=0;$i<count($orderlines);$i++)
				{ 
					  $paidamountarray=array();
					  if($orderlines[$i]['payment_status'] == 'pending')
					  {
						$paymentstatus=0;
					  }
					  
				}
				
				if(intval($paymentstatus) > 0)
				{
					$qry="UPDATE a_order SET payment_status='paid' WHERE id='".$orderid."'";
					$query = $this->db->query($qry);
					return true;
					
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
	
	
	//-- validate Gift card-------------------------
    /**
     * retrieve a gift card based on its customer
     *
     *
     * @param	int $customer
     * @return	array
     */

    function validateGiftcard($giftcard,$customer)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if ($customer == '0') {
            $this->__debugging(__line__, __function__, 0, "Invalid Data ($customer)", '');
            return false;
        }
		//escape params items
       
		//----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');
		//_____SQL QUERY_______
		
        $query = $this->db->query("SELECT * FROM a_gift_card WHERE card_id='".$giftcard."'");
		
		if($query->num_rows() > 0)
		{
			$results = $query->row_array(); //single row array
		}
		else
		{
			return false;
		}
		
		
	
		
		//----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
       //---return
	   
	   
		
        return $results;
    }
	
	
	function getDeliverystatus($order_id,$orderline)
	{
		$query=$this->db->query("SELECT * FROM a_order_log WHERE `order`='".$order_id."' AND status='9'");
		if($query->num_rows() > 0)
		{
			return true;
		}
		else
		{
			$query=$this->db->query("SELECT * FROM a_order_log WHERE `orderline`='".$orderline."' AND status='9'");
			if($query->num_rows() > 0)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		
	}
	
	/*saldo payment place order update order line payment status*/
	function saldoorderlinePayment($orderid,$payment_type,$payment_status,$skipids)
	{	
		$addsql='';
		if($skipids != '')
		{
			$addsql=" AND id NOT IN(".$skipids.")"; 
		}
		$qry="UPDATE  a_orderline SET payment_type='".$payment_type."',payment_status='".$payment_status."' WHERE `order`='".$orderid."' $addsql";
		$this->db->query($qry);
	}
	
	function updateOrderBalance($orderid,$pendingsaldo,$paidsaldo)
	{
		$qry="UPDATE a_order SET c_account_pending='".$pendingsaldo."',c_account_paid='".$paidsaldo."' WHERE `id`='".$orderid."' ";
		$this->db->query($qry);
	}
	
	function getCancelReasons()
	{
		$query = $this->db->query("SELECT * FROM a_order_canceled_reason ORDER BY id ASC");
		$results = $query->result_array();
		return $results;
		
	}



}

/* End of file payments_model.php */
/* Location: ./application/models/payments_model.php */
