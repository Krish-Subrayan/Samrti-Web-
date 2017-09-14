<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all cloning related data abstraction
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */

class Clone_model extends Super_Model
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


    // -- getProject ----------------------------------------------------------------------------------------------
    /**
     * - get a project
     * @param numeric $staff_id
     * @return	bool
     */

    function getProject($project_id = '')
    {

        //defaults
        $conditional_sql = '';
        $sorting = '';

        //validate id
        if (!is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid SQL Data", '');
            return false;
        }

        //_____SQL QUERY___________________________________
        $query = $this->db->query("SELECT * FROM projects
                                        WHERE projects_id = '$project_id'");

        //other results
        $results = $query->row_array();

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
    }


    // -- getClient ----------------------------------------------------------------------------------------------
    /**
     * - get a client
     * @param numeric $staff_id
     * @return	bool
     */

    function getClient($client_id = '')
    {

        //defaults
        $conditional_sql = '';
        $sorting = '';

        //validate id
        if (!is_numeric($client_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid SQL Data", '');
            return false;
        }

        //_____SQL QUERY___________________________________
        $query = $this->db->query("SELECT clients.*, client_users.*
                                        FROM clients
                                        LEFT OUTER JOIN client_users 
                                             ON client_users.client_users_clients_id = $client_id
                                             AND client_users_main_contact = 'yes'
                                        WHERE clients_id = $client_id");

        //other results
        $results = $query->row_array();

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
    }

    // -- getMilestones ----------------------------------------------------------------------------------------------
    /**
     * - get a projects milestone
     * @param numeric $staff_id
     * @return	bool
     */

    function getMilestones($project_id = '')
    {

        //defaults
        $conditional_sql = '';
        $sorting = '';

        //validate id
        if (!is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid SQL Data", '');
            return false;
        }

        //_____SQL QUERY___________________________________
        $query = $this->db->query("SELECT * FROM milestones
                                        WHERE milestones_project_id = '$project_id'");

        //other results
        $results = $query->result_array();

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
    }


    // -- getMembers ----------------------------------------------------------------------------------------------
    /**
     * - get a projects members
     * @param numeric $staff_id
     * @return	bool
     */

    function getMembers($project_id = '')
    {

        //defaults
        $conditional_sql = '';
        $sorting = '';

        //validate id
        if (!is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid SQL Data", '');
            return false;
        }

        //_____SQL QUERY___________________________________
        $query = $this->db->query("SELECT * FROM project_members
                                        WHERE project_members_project_id = '$project_id'");

        //other results
        $results = $query->result_array();

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
    }


    // -- getTasks ----------------------------------------------------------------------------------------------
    /**
     * - get a projects tasks
     * @param numeric $staff_id
     * @return	bool
     */

    function getTasks($project_id = '')
    {

        //defaults
        $conditional_sql = '';
        $sorting = '';

        //validate id
        if (!is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid SQL Data", '');
            return false;
        }

        //_____SQL QUERY___________________________________
        $query = $this->db->query("SELECT * FROM tasks
                                        WHERE tasks_project_id = '$project_id'");

        //other results
        $results = $query->result_array();

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
    }



    // -- getTasks ----------------------------------------------------------------------------------------------
    /**
     * - get a projects tasks
     * @param numeric $staff_id
     * @return	bool
     */

    function getMilestoneTasks($milestone_id = '')
    {

        //defaults
        $conditional_sql = '';
        $sorting = '';

        //validate id
        if (!is_numeric($milestone_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid SQL Data", '');
            return false;
        }

        //_____SQL QUERY___________________________________
        $query = $this->db->query("SELECT * FROM tasks
                                        WHERE tasks_milestones_id = '$milestone_id'");

        //other results
        $results = $query->result_array();

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
    }
    // -- addProject ----------------------------------------------------------------------------------------------
    /**
     * - add project to database
     * @access	public
     */

    function addProject($sqldata = array())
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
        $query = $this->db->query("INSERT INTO projects (
                                          projects_clients_id,
                                          projects_title,
                                          project_deadline,
                                          projects_description,
                                          projects_optionalfield1,
                                          projects_optionalfield2,
                                          projects_optionalfield3,
                                          projects_optionalfield4,
                                          projects_optionalfield5,
                                          projects_date_created
                                          )VALUES(
                                          $projects_clients_id,
                                          $projects_title,
                                          $project_deadline,
                                          $projects_description,
                                          $projects_optionalfield1,
                                          $projects_optionalfield2,
                                          $projects_optionalfield3,
                                          $projects_optionalfield4,
                                          $projects_optionalfield5,
                                          $projects_date_created)");

        $results = $this->db->insert_id();

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


    // -- addMembers ----------------------------------------------------------------------------------------------
    /**
     * - add new project members
     */

    function addMembers($sqldata = array())
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
        $query = $this->db->query("INSERT INTO project_members (
                                          project_members_team_id,
                                          project_members_project_id,
                                          project_members_project_lead                                        
                                          )VALUES(
                                          $project_members_team_id,
                                          $project_members_project_id,
                                          $project_members_project_lead)");

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


    // -- addMilestones ----------------------------------------------------------------------------------------------
    /**
     * - add new project milestones
     */

    function addMilestones($sqldata = array())
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
        $query = $this->db->query("INSERT INTO milestones (
                                          milestones_project_id,
                                          milestones_client_id,
                                          milestones_title,
                                          milestones_start_date,
                                          milestones_end_date,
                                          milestones_created_by,
                                          milestones_status                                                                                  
                                          )VALUES(
                                          $milestones_project_id,
                                          $milestones_client_id,
                                          $milestones_title,
                                          $milestones_start_date,
                                          $milestones_end_date,
                                          $milestones_created_by,
                                          $milestones_status)");

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


    // -- addTask ----------------------------------------------------------------------------------------------
    /**
     * - add new task
     */

    function addTask($sqldata = array())
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
        $query = $this->db->query("INSERT INTO tasks (
                                          tasks_milestones_id,
                                          tasks_project_id,
                                          tasks_client_id,
                                          tasks_assigned_to_id,
                                          tasks_text,
                                          tasks_start_date,
                                          tasks_end_date,
                                          tasks_created_by_id,
                                          tasks_status,
                                          tasks_description,
                                          tasks_created_by,
                                          tasks_client_access
                                          )VALUES(
                                          $tasks_milestones_id,
                                          $tasks_project_id,
                                          $tasks_client_id,
                                          $tasks_assigned_to_id,
                                          $tasks_text,
                                          $tasks_start_date,
                                          $tasks_end_date,
                                          $tasks_created_by_id,
                                          $tasks_status,
                                          $tasks_description,
                                          $tasks_created_by,
                                          $tasks_client_access)");

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

    // -- deleteProject ----------------------------------------------------------------------------------------------
    /**
     * - delete a project
     * @param numeric $id
     * @return	bool
     */

    function deleteProject($id = '')
    {

        //validate
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM projects 
                                          WHERE projects_id = $id");

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

    // -- deleteProjectMembers ----------------------------------------------------------------------------------------------
    /**
     * - delete project members
     * @param numeric $id
     * @return	bool
     */

    function deleteProjectMembers($id = '')
    {

        //validate
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM project_members 
                                          WHERE project_members_project_id = $id");

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


    // -- deleteMilestones ----------------------------------------------------------------------------------------------
    /**
     * - delete project milestones
     * @param numeric $id
     * @return	bool
     */

    function deleteMilestones($id = '')
    {

        //validate
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM milestones 
                                          WHERE milestones_project_id = $id");

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


    // -- deleteTasks ----------------------------------------------------------------------------------------------
    /**
     * - delete project tasks
     * @param numeric $id
     * @return	bool
     */

    function deleteTasks($id = '')
    {

        //validate
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id]", '');
            return false;
        }

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM tasks 
                                          WHERE tasks_project_id = $id");

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
