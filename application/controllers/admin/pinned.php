<?php

class Pinned extends MY_Controller
{

    // --  constructor- -------------------------------------------------------------------------------------------------------
    public function __construct()
    {

        parent::__construct();

        //template file
        $this->data['vars']['template_file'] = PATHS_ADMIN_THEME . 'home.html';
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


        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();
        
        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {

            case 'update':
                $this->__updatePinned();
                break;

            default:
                $this->__updatePinned();
        }

        //load view
        $this->__loadMainView('view');
    }


    // -- __updatePinned- -------------------------------------------------------------------------------------------------------
    /**
     * update pinned projects
     */

    function __updatePinned()
    {

        //flow control
        $next = true;

        //is this post access
        if (!isset($_POST['submit'])) {
            redirect('/admin/hone');
        }

        //update pinned
        if ($next) {

            //avoid duplication
            $check = array();
            $sqldata = array();
            for ($i = 1; $i < 5; $i++) {
                $var = "team_profile_pinned_$i";
                if (!in_array($this->input->post($var), $check)) {
                    $sqldata[$var] = $this->input->post($var);
                } else {
                    $sqldata[$var] = '';
                }
                $check[] = $this->input->post($var);
            }
            
            //update
            $sqldata['team_profile_id'] = $this->data['vars']['my_id'];
            $this->teamprofile_model->updatePinned($sqldata);
            
            //redirect
            if ($this->input->post('redirect') != '') {
                $this->session->set_flashdata('notice-success', $this->data['lang']['lang_request_has_been_completed']);
                redirect($this->input->post('redirect'));
            } else {
                redirect('/admin/hone');
            }
        }
    }


}

/* End of file template.php */
/* Location: ./application/controller/admin/template.php */
