<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * create 'profiling' log data when setting is enabled in confif
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */

class Ci_log_model extends Super_Model
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
     * - add data to log
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

        //user data
        $ci_log_ip_address = $this->db->escape($this->input->ip_address());
        $ci_log_browser = $this->db->escape($this->agent->browser());
        $ci_log_user_agent = $this->db->escape($this->agent->agent_string());
        $ci_log_mobile_user = $this->db->escape(($this->agent->is_mobile()) ? 'yes' : 'no');

        //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO ci_log (
                                          ci_log_date,
                                          ci_log_type,
                                          ci_log_file,
                                          ci_log_function,
                                          ci_log_line,
                                          ci_log_message,
                                          ci_log_ip_address,
                                          ci_log_browser,
                                          ci_log_user_agent,
                                          ci_log_mobile_user                                    
                                          )VALUES(
                                          NOW(),
                                          $ci_log_type,
                                          $ci_log_file,
                                          $ci_log_function,
                                          $ci_log_line,
                                          $ci_log_message,
                                          $ci_log_ip_address,
                                          $ci_log_browser,
                                          $ci_log_user_agent,
                                          $ci_log_mobile_user)");

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


}
/* End of file staff_model.php */
/* Location: ./application/models/staff_model.php */
