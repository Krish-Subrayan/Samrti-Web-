<?php

// -- email_helper ----------------------------------------------------------------------------------------------
/**
 * @package		CodeIgniter
 * @author		NEXTLOOP
 * @last-updated 8 May 2015
 */

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// -----------------------------------------------------------------------------------------------------------------
/**
 * sets the default settings for CI's emal class
 */
if (!function_exists('email_default_settings')) {
    function email_default_settings($email_template = '', $email_vars = '')
    {

        //get $CI instance
        $ci = &get_instance();

        //email class - config settings
        $config['protocol'] = 'mail';
        $config['charset'] = 'utf-8';
        $config['wordwrap'] = true;
        $config['mailtype'] = 'html';
        if ($config['protocol'] == 'sendmail') {
            $config['mailpath'] = '/usr/sbin/sendmail'; //if using sendmail
        }
        //settings
        $ci->email->initialize($config);
        $ci->email->from($ci->data['settings_company']['company_email'],$ci->data['lang']['lang_website_title']);

        //FUTURE
        //add global smtp settings here
    }
}

// -----------------------------------------------------------------------------------------------------------------
/**
 * prepares an email from a template and returns the content
 * replaces all [var.foo_bar] with value found in array
 *
 * 
 * @param	path to template file
 * @return	if exists: return given file path; if not: returns 404 error
 */
if (!function_exists('parse_email_template')) {
    function parse_email_template($email_template = '', $email_vars = '')
    {

        //get $CI instance
        $CI = &get_instance();

        if (is_array($email_vars)) {
            //loop through array and replace vars in email template
            foreach ($email_vars as $key => $value) {
                $replace_var = "[var.$key]";
                $email_template = str_replace($replace_var, $value, $email_template);
            }
            //remove un-identified [vars]
            return preg_replace("%\[var.[a-z_]+\]%", '', $email_template);

        } else {

            //remove un-identified [vars]
            return preg_replace("%\[var.[a-z_]+\]%", '', $email_template);

        }

    }

}

// -----------------------------------------------------------------------------------------------------------------
/**
 * sets the passed variables into globally accessible vars.
 * its neater and also makes them accessible in the debug data
 */
if (!function_exists('email_set_vars')) {
    function email_set_vars($vars = array())
    {

        //get $CI instance
        $ci = &get_instance();

        //add each to globally accessible vars
        foreach ($vars as $key => $value) {
            $ci->data['email_vars'][$key] = $value;
        }

    }
}

/* End of file emailer_helper.php */
/* Location: ./application/helpers/emailer_helper.php */
