<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all mysql related data abstraction
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */

class Super_model extends CI_Model {

    var $model_debug_output; //debug data
    var $debug_data; //debug data
    var $number_of_rows;
    var $ci;





    // -- __construct ----------------------------------------------------------------------------------------------
    /**
     * constructor
     */
    function __construct() {

        // Call the Model constructor
        parent::__construct();
        
        /** 
         * Add CI instanace so that we can write access $this->data
         * now we can write sql debug data here & not in controller
         * $this->data['debug'][] as $this->ci->data['debug'][]
         */
        $this->ci = &get_instance();

        //Set timezone offset
        $this->__setTimeZoneOffset();
    }






    // -- __setTimeZoneOffset ----------------------------------------------------------------------------------------------
    /**
     * - some descrition here
     *
     * @access	public
     * @param	none
     * @return	mixed (table array / false)
     */

    function __setTimeZoneOffset() {

        //get the offset, based on what timezone php is using. (php timezone as set in config)
        $date = new DateTime();
        $offset = $date->format("P"); //e.g +2:00
        $this->db->query("SET time_zone='$offset'");

        //bugging
        $last_query = $this->db->last_query();
        $last_error = $this->db->_error_message();
        $debug = "<pre>" . __function__ . "<br/>$last_query<br/><br/>$last_error</pre>";
    }





    // -- __debugging ----------------------------------------------------------------------------------------------
    /**
     * - debug prepares debug data and saves it to debug_data
     *
     * @access	public
     * @param	mixed (number/string)
     * @return	void
     */

    function __debugging($line_number = '', $function = '', $execution_time = '', $notes = '', $sql_results = '') {

        //is there aany need for mysql data
        $last_query = ($sql_results === '') ? 'N/A' : $this->db->last_query();
        $last_error = ($sql_results === '') ? 'N/A' : $this->db->_error_message();

        $debug_array = array(
            'last_query' => $last_query,
            '_error_message' => $last_error,
            'results' => $sql_results,
            'file' => __file__,
            'line' => $line_number,
            'function' => $function,
            'execution_time' => $execution_time,
            'notes' => $notes);

        $this->debug_data = debug_models($debug_array);
        
        $this->ci->data['debug'][] = $this->debug_data;
        
        //easy dubuginga data
        $this->ci->sql_last_query = "<pre>$last_query</pre>";
        $this->ci->sql_last_error = "<pre>$last_error</pre>";
        $this->ci->sql_last_query_and_error = $this->sql_last_query.$this->sql_last_error;

    }


}
/* End of file super_model.php */
/* Location: ./application/models/super_model.php */