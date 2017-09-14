<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Updating_model extends Super_Model
{

    var $debug_methods_trail; //method profiling
    var $number_of_rows;

    // -- __construct ----------------------------------------------------------------------------------------------
    /**
     * no action
     *
     * @access	private
     * @param	none
     * @return	none
     */
    function __construct()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        // Call the Model constructor
        parent::__construct();
    }

    // -- updateDatabase ----------------------------------------------------------------------------------------------
    /**
     * - runs individula queries passed to it from MY_Controller for any mysql.sql updates in /updates folder
     *
     * @param	string $query the mysql query to execute
     * @return	bool
     */

    function updateDatabase($query = '')
    {
        //check query
        if ($query == '') {
            return;
        }
        
        $db_debug = $this->db->db_debug; //save setting

        $this->db->db_debug = false; //disable debugging for queries

        //_____SQL QUERY_______
        $query = $this->db->query($query);

        $this->db->db_debug = $db_debug; //restore setting
        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);

    }

}
