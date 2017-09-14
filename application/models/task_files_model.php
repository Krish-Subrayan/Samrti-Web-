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

class Task_files_model extends Super_Model
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

    // -- addFile ----------------------------------------------------------------------------------------------
    /**
     * - add new file
     *
     * @access	public
     * @param	array $sqldata an array containg all data used by model
     * @return	mixed insert id / false
     */

    function addFile($sqldata = array())
    {

        //validate id
        if (empty($sqldata)) {
            $this->__debugging(__line__, __function__, 0, "Invalid SQL Data", '');
            return false;
        }

        //______ESCAPE ALL AVAILABLE DATA____________________________________________
        foreach ($sqldata as $key => $value) {
            $$key = $this->db->escape($value);
        }

        //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO task_files (
                                          task_files_task_id,
                                          task_files_project_id,
                                          task_files_client_id,
                                          task_files_uploaded_by,
                                          task_files_uploaded_by_id,
                                          task_files_name,
                                          task_files_description,
                                          task_files_size,
                                          task_files_size_human,
                                          task_files_date_uploaded,
                                          task_files_foldername,
                                          task_files_extension
                                          )VALUES(
                                          $task_files_task_id,
                                          $task_files_project_id,
                                          $task_files_client_id,
                                          $task_files_uploaded_by,
                                          $task_files_uploaded_by_id,
                                          $task_files_name,
                                          $task_files_description,
                                          $task_files_size,
                                          $task_files_size_human,
                                          NOW(),
                                          $task_files_foldername,
                                          $task_files_extension)");

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


    // -- getFiles ----------------------------------------------------------------------------------------------
    /**
     * - get task files
     * @param numeric $staff_id
     * @return	array
     */

    function getFiles($sqldata = array())
    {

        //conditional
        $conditional_sql = '';

        //validate id
        if (empty($sqldata)) {
            $this->__debugging(__line__, __function__, 0, "Invalid SQL Data", '');
            return false;
        }

        //escape all data items
        foreach ($sqldata as $key => $value) {
            $$key = $this->db->escape($value);
        }

        //_____CONDITIONALS_______
        if (is_numeric($sqldata['task_files_task_id'])) {
            $conditional_sql .= "AND task_files.task_files_task_id = $task_files_task_id";
        }

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT task_files.*, client_users.*, team_profile.*
                                            FROM task_files 
                                            LEFT OUTER JOIN client_users 
                                                 ON client_users.client_users_id = task_files.task_files_uploaded_by_id
                                            LEFT OUTER JOIN team_profile 
                                                 ON team_profile.team_profile_id = task_files.task_files_uploaded_by_id
                                            WHERE 1 = 1
                                            $conditional_sql");

        //other results
        $results = $query->result_array();

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
    }

    // -- getFile ----------------------------------------------------------------------------------------------
    /**
     * - get a task file
     * @param numeric $staff_id
     * @return	bool
     */

    function getFile($file_id = '')
    {

        //validate id
        if (!is_numeric($file_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid SQL Data", '');
            return false;
        }

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT * FROM task_files 
                                            WHERE task_files_id = $file_id");

        //other results
        $results = $query->row_array();

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
    }


    // -- deleteFile ----------------------------------------------------------------------------------------------
    /**
     * - delete file
     * @param numeric $id
     * @return	bool
     */
    function deleteFile($id = '')
    {

        //validate
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM task_files 
                                            WHERE task_files_id = $id");

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
