<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all bugs related data abstraction
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Bug_comments_model extends Super_Model
{

    var $debug_methods_trail;
    var $number_of_rows;

    // -- __construct ----------------------------------------------------------------------------------------------
    /**
     * no action
     *
     * 
     */
    function __construct()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        // Call the Model constructor
        parent::__construct();
    }


    // -- getBugComments ----------------------------------------------------------------------------------------------
    /**
     * - get all bug comments
     * @param numeric $staff_id
     * @return	bool
     */

    function getBugComments($bug_id = '')
    {

        //validate id
        if (!is_numeric($bug_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid data [bug_id:$bug_id]", '');
            return false;
        }

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT bug_comments.*, team_profile.*, client_users.*
                                            FROM bug_comments
                                            LEFT OUTER JOIN team_profile ON team_profile.team_profile_id = bug_comments.bug_comments_user_id
                                            LEFT OUTER JOIN client_users ON client_users.client_users_id = bug_comments.bug_comments_user_id
                                            WHERE bug_comments_bug_id = $bug_id
                                            ORDER BY bug_comments.bug_comments_id ASC");

        //other results
        $results = $query->result_array();

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
    }

    // -- addComment ----------------------------------------------------------------------------------------------
    /**
     * - add new bug message
     *
     * @access	public
     * @param	array $sqldata an array containg all data used by model
     * @return	mixed insert id / false
     */

    function addComment($sqldata = array())
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
        $query = $this->db->query("INSERT INTO bug_comments (
                                          bug_comments_bug_id,
                                          bug_comments_project_id,
                                          bug_comments_date_added,
                                          bug_comments_user_id,
                                          bug_comments_text,
                                          bug_comments_user_type                                        
                                          )VALUES(
                                          $bug_comments_bug_id,
                                          $bug_comments_project_id,
                                          NOW(),
                                          $bug_comments_user_id,
                                          $bug_comments_text,
                                          $bug_comments_user_type  )");

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
    
        // -- deleteBugComment ----------------------------------------------------------------------------------------------
    /**
     * - delete bug comment
     *      * @param numeric $id
     * @return	bool
     */

    function deleteBugComment($id = '')
    {

        //validate
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM bug_comments 
                                          WHERE bug_comments_id = $id");

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

/* End of file bugs_model.php */
/* Location: ./application/models/bugs_model.php */
