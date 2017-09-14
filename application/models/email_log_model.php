<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Email_log_model extends Super_Model
{

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

    // -- addToLog ----------------------------------------------------------------------------------------------
    /**
     * - add an email to the log file
     *
     * @return	bool
     */

    function addToLog($email_log_to_address = '', $email_log_subject = '', $email_log_message = '')
    {

        $email_log_to_address = $this->db->escape($email_log_to_address);
        $email_log_subject = $this->db->escape($email_log_subject);
        $email_log_message = $this->db->escape($email_log_message);

        //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO email_log (
                                          email_log_date,
                                          email_log_time,
                                          email_log_to_address,
                                          email_log_subject,
                                          email_log_message
                                          )VALUES(
                                          NOW(),
                                          NOW(),
                                          $email_log_to_address,
                                          $email_log_subject,
                                          $email_log_message)");

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


    // -- purgeLog ----------------------------------------------------------------------------------------------
    /**
     * - delete all records from the log
     *
     * @return	bool
     */

    function purgeLog()
    {

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM email_log");

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


    // -- listLog ----------------------------------------------------------------------------------------------
    /**
     * search/list email log
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
        $query = $this->db->query("SELECT * FROM email_log
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
     * - get email fromlog
     * @param numeric $id
     * @return	bool
     */

    function getEmail($id = '')
    {

        //validate
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT * FROM email_log 
                                            WHERE email_log_id = $id");

        //other results
        $results = $query->row_array();

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
    }
}

/* End of file users_model.php */
/* Location: ./application/models/users_model.php */
