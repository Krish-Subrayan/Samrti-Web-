<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all reporting related data abstraction
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */

class Report_model extends Super_Model
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


    // -- projectStats ----------------------------------------------------------------------------------------------
    /**
     * - getbasic project stats
     * @param numeric $staff_id
     * @return	bool
     */

    function projectStats($id = '')
    {

        //validate id
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid SQL Data", '');
            return false;
        }

        //_____SQL QUERY___________________________________
        $query = $this->db->query("SELECT projects.*,
                                          (SELECT COUNT(milestones_id) 
                                                  FROM milestones
                                                  WHERE milestones.milestones_project_id = projects.projects_id)
                                                  AS count_milestones_all,
                                          (SELECT COUNT(milestones_id) 
                                                  FROM milestones
                                                  WHERE milestones.milestones_project_id = projects.projects_id
                                                  AND milestones.milestones_status = 'completed')
                                                  AS count_milestones_completed, 
                                          (SELECT COUNT(bugs_id) 
                                                  FROM bugs
                                                  WHERE bugs.bugs_project_id = projects.projects_id
                                                  AND bugs.bugs_status NOT IN('resolved', 'not-a-bug'))
                                                  AS count_bugs_pending, 
                                          (SELECT COUNT(bugs_id) 
                                                  FROM bugs
                                                  WHERE bugs.bugs_project_id = projects.projects_id
                                                  AND bugs.bugs_status = 'resolved')
                                                  AS count_bugs_resolved, 
                                          (SELECT COUNT(bugs_id) 
                                                  FROM bugs
                                                  WHERE bugs.bugs_project_id = projects.projects_id
                                                  AND bugs.bugs_status NOT IN('not-a-bug'))
                                                  AS count_bugs_all, 
                                          (SELECT SUM(payments_amount)
                                                  FROM payments 
                                                  WHERE DATE_FORMAT(payments_date,'%Y%m%d') = DATE_FORMAT(NOW(),'%Y%m%d')
                                                  AND payments.payments_project_id = projects.projects_id)
                                                  AS sum_payments_today,
                                          (SELECT SUM(payments_amount)
                                                  FROM payments 
                                                  WHERE DATE_FORMAT(payments_date,'%Y%m') = DATE_FORMAT(NOW(),'%Y%m')
                                                  AND payments.payments_project_id = projects.projects_id)
                                                  AS sum_payments_this_month,
                                          (SELECT SUM(payments_amount)
                                                  FROM payments 
                                                  WHERE payments.payments_project_id = projects.projects_id)
                                                  AS sum_payments_all,                                                                                                 
                                          (SELECT COUNT(tasks_id) 
                                                  FROM tasks
                                                  WHERE tasks.tasks_project_id = projects.projects_id)
                                                  AS count_tasks_all,
                                          (SELECT COUNT(tasks_id) 
                                                  FROM tasks
                                                  WHERE tasks.tasks_project_id = projects.projects_id
                                                  AND tasks_status ='pending')
                                                  AS count_tasks_pending,
                                          (SELECT COUNT(tasks_id)
                                                  FROM tasks
                                                  WHERE tasks.tasks_project_id = projects.projects_id
                                                  AND tasks_status ='completed')
                                                  AS count_tasks_completed,      
                                          (SELECT COUNT(tasks_id) 
                                                  FROM tasks
                                                  WHERE tasks.tasks_project_id = projects.projects_id
                                                  AND tasks_status ='behind schedule')
                                                  AS count_tasks_behind, 
                                          (SELECT SUM(timer_seconds) 
                                                  FROM timer
                                                  WHERE timer.timer_project_id = projects.projects_id)
                                                  AS sum_timers, 
                                          (SELECT COUNT(files_id) 
                                                  FROM files
                                                  WHERE files.files_project_id = projects.projects_id)
                                                  AS count_files,   
                                          (SELECT SUM(invoices_amount) 
                                                  FROM invoices
                                                  WHERE invoices.invoices_project_id = projects.projects_id
                                                  AND invoices.invoices_status NOT IN ('new'))
                                                  AS sum_invoices_all, 
                                          (SELECT SUM(invoices_amount) 
                                                  FROM invoices
                                                  WHERE invoices.invoices_project_id = projects.projects_id
                                                  AND invoices.invoices_status ='due')
                                                  AS sum_invoices_due, 
                                          (SELECT SUM(invoices_amount) 
                                                  FROM invoices
                                                  WHERE invoices.invoices_project_id = projects.projects_id
                                                  AND invoices.invoices_status ='overdue')
                                                  AS sum_invoices_overdue, 
                                          (SELECT SUM(payments_amount) 
                                                  FROM payments
                                                  WHERE payments.payments_project_id = projects.projects_id)
                                                  AS sum_payments,
                                           (SELECT sum_invoices_all - sum_payments) AS sum_invoices_balance                                          
                                        FROM projects 
                                        WHERE projects_id = '$id'");

        //other results
        $results = $query->row_array();

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
    }


    // -- projectTimeSheet ----------------------------------------------------------------------------------------------
    /**
     * - get time worked by all team members
     * @param numeric $staff_id
     * @return	bool
     */

    function projectTimeSheet($project_id = '')
    {

        //validate id
        if (!is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid SQL Data", '');
            return false;
        }
        
        //_____SQL QUERY___________________________________
        $query = $this->db->query("SELECT timer.*, team_profile.*
                                          FROM timer
                                          LEFT OUTER JOIN team_profile
                                               ON team_profile.team_profile_id = timer.timer_team_member_id
                                          WHERE timer.timer_project_id = '$project_id'");

        //other results
        $results = $query->result_array();

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
    }
    
    // -- projectTasks ----------------------------------------------------------------------------------------------
    /**
     * - get team members tasks
     * @param numeric $staff_id
     * @return	bool
     */

    function projectTasks($project_id = '')
    {

        //validate id
        if (!is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid SQL Data", '');
            return false;
        }
        
        //_____SQL QUERY___________________________________
        $query = $this->db->query("SELECT project_members.*, team_profile.*,
                                          (SELECT COUNT(tasks_id) FROM tasks
                                                  WHERE tasks.tasks_project_id = $project_id
                                                  AND tasks.tasks_assigned_to_id = project_members.project_members_team_id
                                                  AND tasks.tasks_status = 'pending') AS count_tasks_pending,
                                          (SELECT COUNT(tasks_id) FROM tasks
                                                  WHERE tasks.tasks_project_id = $project_id
                                                  AND tasks.tasks_assigned_to_id = project_members.project_members_team_id
                                                  AND tasks.tasks_status = 'completed') AS count_tasks_completed,
                                          (SELECT COUNT(tasks_id) FROM tasks
                                                  WHERE tasks.tasks_project_id = $project_id
                                                  AND tasks.tasks_assigned_to_id = project_members.project_members_team_id
                                                  AND tasks.tasks_status = 'behind schedule') AS count_tasks_behind
                                          FROM project_members
                                          LEFT OUTER JOIN team_profile
                                               ON team_profile.team_profile_id = project_members.project_members_team_id
                                          WHERE project_members.project_members_project_id = '$project_id'");

        //other results
        $results = $query->result_array();

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
    }  
    
    
    // -- projectPayments ----------------------------------------------------------------------------------------------
    /**
     * - get all payments
     * @param numeric $staff_id
     * @return	bool
     */

    function projectPayments($project_id = '')
    {

        //validate id
        if (!is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid SQL Data", '');
            return false;
        }
        
        //_____SQL QUERY___________________________________
        $query = $this->db->query("SELECT * FROM payments
                                            WHERE payments_project_id = '$project_id'");

        //other results
        $results = $query->result_array();

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
    }       
}
/* End of file staff_model.php */
/* Location: ./application/models/staff_model.php */
