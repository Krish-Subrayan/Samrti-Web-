<?php

class Ajaxtwo extends MY_Controller
{


    // --  constructor- -------------------------------------------------------------------------------------------------------
    public function __construct()
    {

        parent::__construct();

        //profiling::
        $this->data['controller_profiling'][] = __function__;

        //reduce error reporting to only critical
        @error_reporting(E_ERROR);

        //turn off codeigniter profiler (which may be on in MY_Controller) during debug mode
        $this->output->enable_profiler(false);

    }

    // -- index -------------------------------------------------------------------------------------------------------
    /**
     * This is our re-routing function and is the inital function called
     *
     * @access	public
     * @param	void
     * @return	void
     */
    function index()
    {

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {
            case 'upload-task-file':
                $this->__uploadTaskFile();
                break;

            default:
                $this->__default();
        }

        //log debug data
        $this->__ajaxdebugging();
    }


    // -- __default- -------------------------------------------------------------------------------------------------------
    /**
     * if nothing was passed in url
     */

    function __default($action = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        header('HTTP/1.0 400 Bad Request', true, 400);
        $this->jsondata = array(
            'result' => 'error',
            'message' => 'An error has occurred',
            'debug_line' => __line__);

        //log this error
        log_message('error', 'AJAX-LOG:: [FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Routing errror. Specified method/action ($action) not found]");

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }


    // -- __uploadTaskFile- -------------------------------------------------------------------------------------------------------
    /**
     * handles task file uploads (both team member & client users)
     */

    function __uploadTaskFile()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow
        $next = true;

        //get the task id
        $task_id = $this->uri->segment(4);


        //validate task id id
        if ($next) {
            if (!is_numeric($task_id)) {
                $jsondata = array(
                    'success' => 0,
                    'message' => $this->data['lang']['lang_upload_system_error'],
                    'line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);
                $next = false;
            }
        }

        //get task
        if ($next) {
            if (!$task = $this->tasks_model->getTask($task_id)) {
                $jsondata = array(
                    'success' => 0,
                    'message' => $this->data['lang']['lang_upload_system_error'],
                    'line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);
                $next = false;
            } else {
                //redirect url for later
                if ($this->data['vars']['my_user_type'] == 'team') {
                    $redirect_url = $this->data['vars']['site_url'] . 'admin/task/' . $task['tasks_project_id'] . '/view/' . $task['tasks_id'];
                } else {
                    $redirect_url = $this->data['vars']['site_url'] . 'client/task/' . $task['tasks_project_id'] . '/view/' . $task['tasks_id'];
                }

            }
        }


        /** ----------------------------------------------------------------
         * [PERMISSIONS] [TEAM]
         * ----------------------------------------------------------------*/
        if ($next) {
            if ($this->data['vars']['my_user_type'] == 'team') {
                if (!$this->__permissionsCheckTask($task['tasks_id'], 'edit', 'team')) {
                    $jsondata = array(
                        'success' => 0,
                        'message' => $this->data['lang']['lang_permission_denied'],
                        'line' => __line__);
                    header('HTTP/1.0 400 Bad Request', true, 400);
                    $next = false;
                }
            }
        }


        //---START UPLOAD PROECSS--------------------------------------------------------------
        if ($next) {
            //some settings
            $this->data['field_name'] = 'uploadedfile';
            $this->data['allowed_extensions'] = $this->__uploadProjectAllowedFileTypes();

            /*
            * destination folder
            * file is temporarily placed in the temp folder.
            * [EXAMPLE]
            *      /home/files/tasks/task_id/jdhy38risuee8w88/picture.jpg
            *      /home/files/tasks/23/jdhy38risuee8w88/picture.jpg
            */
            $this->data['file_foldername'] = random_string('alnum', 20);
            $this->data['file_folder_path'] = FILES_TASKS_FOLDER . $task['tasks_id'] . '/' . $this->data['file_foldername'];

            //start the upload
            $this->load->library('fileupload');
            $this->fileupload->allowedExtensions = $this->data['allowed_extensions'];
            //$this->fileupload->newFileName = 'newFile.jpg'; //(optional)
            $result = $this->fileupload->handleUpload($this->data['file_folder_path']);

            //some data about new file
            $filedata['upload_errors'] = $this->fileupload->getErrorMsg();
            $filedata['file_size'] = $this->fileupload->getFileSize();
            $filedata['file_name'] = $this->fileupload->getFileName();
            $filedata['file_extension'] = $this->fileupload->getExtension();
            $filedata['file_path'] = $this->fileupload->getSavedFile();
            $filedata['file_folder_path'] = $this->data['file_folder_path'];
            $filedata['file_foldername'] = $this->data['file_foldername'];
            //---END UPLOAD PROECSS------------------------------------------------------------------

            //Upload passed - continue
            if ($result) {
                //check that new file exists
                if (is_file($filedata['file_path'])) {
                    //json
                    $jsondata = array('success' => 1, 'message' => $this->data['lang']['lang_file_has_been_uploaded']);
                } else {
                    //json
                    $jsondata = array('success' => 0, 'message' => $this->data['lang']['lang_upload_system_error']);
                }

                //merge all data for json array
                $jsondata = array_merge($jsondata, $filedata);
                header('HTTP/1.0 200 OK', true, 200);
            }

            //Upload failed - continue
            if (!$result) {
                //delete folder
                if ($this->data['file_folder_path'] != FILES_TASKS_FOLDER) {
                    delete_directory($this->data['file_folder_path']);
                }

                //what error message to show
                $message = ($filedata['upload_errors'] != '') ? $filedata['upload_errors'] : $this->data['lang']['lang_file_could_not_uploaded'];
                //create json array, with merge of file data
                $jsondata = array('success' => 0, 'message' => $message);

                //merge with data from upload class
                $jsondata = array_merge($jsondata, $filedata);
                header('HTTP/1.0 400 Bad Request', true, 400);
            }
        }


        //add file to database
        if ($next) {
            $sqldata = array();
            $sqldata['task_files_task_id'] = $task['tasks_id'];
            $sqldata['task_files_project_id'] = $task['tasks_project_id'];
            $sqldata['task_files_client_id'] = $task['tasks_client_id'];
            $sqldata['task_files_uploaded_by'] = $this->data['vars']['my_user_type'];
            $sqldata['task_files_uploaded_by_id'] = $this->data['vars']['my_id'];
            $sqldata['task_files_size'] = $filedata['file_size'];
            $sqldata['task_files_extension'] = $filedata['file_extension'];
            $sqldata['task_files_size_human'] = convert_file_size($filedata['file_size']);
            $sqldata['task_files_name'] = $filedata['file_name'];
            $sqldata['task_files_foldername'] = $filedata['file_foldername'];
            $sqldata['task_files_description'] = '';
            if ($file_id = $this->task_files_model->addFile($sqldata)) {
                $jsondata = array(
                    'success' => 1,
                    'message' => '',
                    'redirect' => 'yes',
                    'redirect_url' => $redirect_url,
                    'line' => __line__);
                header('HTTP/1.0 200 OK', true, 200);
            } else {
                $jsondata = array(
                    'success' => 0,
                    'message' => $this->data['lang']['lang_upload_system_error'],
                    'line' => __line__);
                header('HTTP/1.0 400 Bad Request', true, 400);
                $next = false;
            }
        }

        //set the json data
        $this->jsondata = $jsondata;

        //log debug data
        $this->__ajaxdebugging();

        //load the view for json echo
        $this->__flmView('common/json');
    }

    // -- fmlView-------------------------------------------------------------------------------------------------------
    /**
     * loads json outputting view
     */
    function __flmView($view = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //sent to TBS engine
        $this->load->view($view, array('data' => $this->jsondata));
    }


    // -- validateTeamPermissions-------------------------------------------------------------------------------------------------------
    /**
     * checks if a team member has access to carry out an action like deleting a file
     * [EXAMPLE USAGE]
     * $next = validateTeamPermissions($project_id, 'delete_item_my_project_files');
     *
     * @access	private
     * @param numeric $project_id
     * @param	string $action example: delete_item_my_project_files
     * @return	bool
     */
    function __validateTeamPermissions($project_id = 0, $action = 'none_specified')
    {

        //error control
        $next = true;

        //profiling
        $this->data['controller_profiling'][] = __function__;
        /* --------------------------TEAM MEMBER PROJECT ACCESS---------------------*/
        if ($this->data['vars']['my_user_type'] == 'team') {
            if ($this->data['vars']['my_group'] != 1) {
                if (!in_array($project_id, $this->data['my_projects_array'])) {
                    //halt
                    $next = false;
                }
            }
        }

        /* --------------------------TEAM MEMBER PPROJECT PERMISSIONS---------------------*/
        if ($this->data['vars']['my_user_type'] == 'team') {
            if ($this->data['vars']['my_group'] != 1) {
                //load project basics - this also sets my 'this project' permissions
                $this->__commonAll_ProjectBasics($project_id, 'no');
                if ($this->data['project_permissions'][$action] != 1) {
                    //halt
                    $next = false;
                }
            }
        }

        //return results
        if ($next) {
            return true;
        } else {
            return false;
        }

    }

    // -- __uploadAllowedFileTypes- -------------------------------------------------------------------------------------------------------
    /**
     * Generate an array of allowed file types from settings.php config
     */

    function __uploadAllowedFileTypes()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if allow all file types
        if ($this->config->item('files_tickets_max_size') === 0) {

            return array();
        }

        //explode array from settings.php config file
        $allowed = explode("|", $this->config->item('files_tickets_max_size'));

        //loop through and create new flat array of file types
        for ($i = 0; $i < count($allowed); $i++) {
            $file_extension = strtolower(trim(str_replace("'", '', $allowed[$i])));

            //if $file_extension is valid alphabetic
            if (ctype_alpha($file_extension) || ctype_alnum($file_extension)) {
                $allowed_array[] = $file_extension;
            }
        }

        return $allowed_array;

    }

    // -- __uploadProjectAllowedFileTypes- -------------------------------------------------------------------------------------------------------
    /**
     * Generate an array of allowed file types from settings.php config
     */

    function __uploadProjectAllowedFileTypes()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if allow all file types
        if ($this->config->item('files_allowed_types') === 0) {

            return array();
        }

        //explode array from settings.php config file
        $allowed = explode("|", $this->config->item('files_allowed_types'));

        //loop through and create new flat array of file types
        for ($i = 0; $i < count($allowed); $i++) {
            $file_extension = strtolower(trim(str_replace("'", '', $allowed[$i])));

            //if $file_extension is valid alphabetic
            if (ctype_alpha($file_extension) || ctype_alnum($file_extension)) {
                $allowed_array[] = $file_extension;
            }
        }

        return $allowed_array;

    }
    // -- DEBUGGING --------------------------------------------------------------------------------------------------------------
    /**
     * - ajax runs in the background, so we want to do as much logging as possibe for debugging
     * 
     */
    function __ajaxdebugging()
    {

        //url segmets array
        $this->data['url_segments'] = $this->uri->segment_array();

        //format debug data for log file
        ob_start();
        print_r($this->data);
        print_r($this->jsondata);
        $all_data = ob_get_contents();
        ob_end_clean();

        //write to logi file
        if ($this->config->item('debug_mode') == 2 || $this->config->item('debug_mode') == 1) {
            log_message('debug', "AJAX-LOG:: BIG DATA $all_data");
        }
    }
}

/* End of file*/
