<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Settings_clientform_model extends Super_Model
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

    // -- getSettings ----------------------------------------------------------------------------------------------
    /**
     * pull all general settings
     *
     * 
     * @return	array
     */

    function getSettings()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM settings_clientform 
                                          WHERE settings_id = 'default'");

        $results = $query->row_array();

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
        return $results;
    }

    // -- editSettings ----------------------------------------------------------------------------------------------
    /**
     * edit settings
     *
     * 
     * @param	void
     * @return	numeric [affected rows]
     */

    function editSettings()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //escape all post item
        foreach ($_POST as $key => $value) {
            $$key = $this->db->escape($this->input->post($key));
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE settings_clientform
                                          SET
                                          clients_address= $clients_address,
                                          clients_city = $clients_city,
                                          clients_state = $clients_state,
                                          clients_zipcode = $clients_zipcode,
                                          clients_country = $clients_country,
                                          clients_website = $clients_website,
                                          client_users_telephone = $client_users_telephone,
                                          client_users_job_position_title = $client_users_job_position_title
                                          WHERE settings_id = 'default'");

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

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        if (is_numeric($results) || $transaction_result === true) {
            return true;
        } else {
            return false;
        }
    }
}

/* End of file settings_general_model.php */
/* Location: ./application/models/settings_general_model.php */
