<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all client related data abstraction
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */

class Paypal_ipn_log_model extends Super_Model
{

    var $number_of_rows;

    // -- __construct ----------------------------------------------------------------------------------------------
    /**
     * constructor
     */
    function __construct()
    {
        parent::__construct();
    }


    // -- addLog ----------------------------------------------------------------------------------------------
    /**
     * - log a paypal ipn call
     *
     * @access	public
     * @param	array $sqldata an array containg all data used by model
     * @return	mixed insert id / false
     */

    function addLog($sqldata = array())
    {

        //validate id
        if (empty($sqldata)) {
            $this->__debugging(__line__, __function__, 0, "Invalid SQL Data", '');
            return false;
        }

        //escape all data items
        foreach ($sqldata as $key => $value) {
            $$key = $this->db->escape($value);
        }

        //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO paypal_ipn_log (
                                          ipn_date,
                                          ipn_invoice_id,
                                          ipn_invoice_unique_id,
                                          ipn_transaction_id,
                                          ipn_transaction_amount,
                                          ipn_transaction_status,
                                          ipn_project_id,
                                          ipn_data_dump                                  
                                          )VALUES(
                                          NOW(),
                                          $ipn_invoice_id,
                                          $ipn_invoice_unique_id,
                                          $ipn_transaction_id,
                                          $ipn_transaction_amount,
                                          $ipn_transaction_status,
                                          $ipn_project_id,
                                          $ipn_data_dump)");

        $results = $this->db->insert_id(); //last item insert id

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


    // -- listLog ----------------------------------------------------------------------------------------------
    /**
     * search/list paypal ipn log
     *
     * @access	public
     * @param	string [offset: pagination], [type: search/count]
     * @return	mixed (table array / false)
     */

    function listLog($offset = 0, $type = 'search')
    {

        $limit = (is_numeric($this->data['settings_general']['results_limit'])) ? $this->data['settings_general']['results_limit'] : 25;

        //---------SEARCHING OR COUNTING--------------------------------------------
        if ($type == 'search' || $type == 'results') {
            $limiting = "LIMIT $limit OFFSET $offset";
        }

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT * FROM paypal_ipn_log
                                             $limiting");
        //results (search or rows)
        if ($type == 'search' || $type == 'results') {
            $results = $query->result_array();
        } else {
            $results = $query->num_rows();
        }

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);

        //return results
        return $results;

    }


    // -- getEmail ----------------------------------------------------------------------------------------------
    /**
     * - get ipn log raw data
     * @param numeric $id
     * @return	bool
     */

    function getLog($id = '')
    {

        //validate
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT * FROM paypal_ipn_log 
                                            WHERE ipn_id = $id");

        //other results
        $results = $query->row_array();

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
    }


    // -- purgeLog ----------------------------------------------------------------------------------------------
    /**
     * - delete all records from the log
     *
     * @return	bool
     */

    function purgeLog()
    {

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM paypal_ipn_log");

        //other results
        $results = $this->db->affected_rows();

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

}
/* End of file staff_model.php */
/* Location: ./application/models/staff_model.php */
