<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// -- profiling -------------------------------------------------------------------------------------------------
/**
 * use this for debugging part of the application as they are executed. You just call this function
 * anywhere that you need to debug.
 * 
 * [EXAMPLE USAGE]
 * profiling(__function__, __line__, $value);
 * 
 * [AJAX USAGE]
 * When type is set to 'ajax', the data will be written to a log file
 *
 * [DATA ARRAYS]
 * You an pass an array directly for $value and it will be converted to a json string for easy reading and logging
 * @access	public
 * @param	$value astring optional data value that you may want to capature
 * @return	echos output to screen
 */
if (!function_exists('profiling')) {
    function profiling($type = '', $file = '', $function = '', $line = '', $value = '')
    {
        //get $CI instance
        $ci = &get_instance();


        //has any data been passed that is an array
        if (is_array($value)) {
            $value = json_encode($value);
        }

        //get file name
        $filename = end(explode('/', $file));

        //set to big data
        $ci->data['profiling'][] = "$filename --- $function ---- line: $line ---- $value";

        //save to log file if applicable
        if ($ci->config->item('debug_mode') == 1) {
            log_message('error', "PROFILING: $filename --- $function ---- line: $line ---- $value");
        }

        //save data to mysql log
        if (in_array($ci->config->item('database_log'), array(1, 2))) {

            $sqldata = array();
            $sqldata['ci_log_file'] = $filename;
            $sqldata['ci_log_function'] = $function;
            $sqldata['ci_log_line'] = $line;
            $sqldata['ci_log_message'] = $value;
            $sqldata['ci_log_type'] = $type;
            
            //log 'info'
            if ($ci->config->item('database_log') == 1 && $type == 'error') {
                $ci->ci_log_model->addLog($sqldata);
            }
                        
            //log 'error' + 'info'
            if ($ci->config->item('database_log') == 2 && ($type == 'error' || $type == 'info')) {
                $ci->ci_log_model->addLog($sqldata);
            }
        }

    }
}

// -- pretty_print_r -------------------------------------------------------------------------------------------------
/**
 * Print pretty arrays
 *
 * 
 * @param	data array
 * @return	echos output to screen
 */
if (!function_exists('pretty_print_r')) {
    function pretty_print_r($data = '')
    {
        if (is_array($data)) {
            echo '<pre>';
            print_r($data);
            echo '</pre>';
        }
    }
}

// -- string_print_r -------------------------------------------------------------------------------------------------
/**
 * Print pretty arrays and return it as a STRING, whch you can use in e.g. log files
 *
 * 
 * @param	array
 * @return	string   [returns the print_r as a string]
 */
if (!function_exists('string_print_r')) {
    function string_print_r($data = '')
    {
        if (is_array($data)) {
            ob_start();
            echo '<pre>';
            if (is_array($data)) {
                print_r($data);
            } else {
                echo $data;
            }
            echo '</pre>';
            $data = ob_get_contents();
            ob_end_clean();

            return $data;
        }
    }
}

// -- sanitize_string -------------------------------------------------------------------------------------------------
/**
 * Removes all unwanted characters from a string. This is usefeull for data that may be passed into javascript
 * to prevent it breaking the js with unwanted tags such as single quoations etc
 * html tags
 * single quotations
 *
 *
 * 
 * @param	data array
 * @return	echos output to screen
 */
if (!function_exists('sanitize_string')) {
    function sanitize_string($data = '')
    {

        //get $CI instance
        $CI = &get_instance();

        return $data;
    }
}

// -- numberFormatDecimal -------------------------------------------------------------------------------------------------
/**
 * converts a a number to friendly format (1,000.00)
 * with decial places
 * 
 * @param	data array
 * @return	echos output to screen
 */
if (!function_exists('numberFormatDecimal')) {
    function numberFormatDecimal($number = '')
    {

        //get $CI instance
        $CI = &get_instance();

        if (is_numeric($number)) {
            return number_format($number, 2, '.', ',');
        } else {
            return;
        }
    }
}

// -- numberFormatDecimal -------------------------------------------------------------------------------------------------
/**
 * checks if given input is an email address
 * 
 * @param	string
 * @return	bool
 */
if (!function_exists('is_email_address')) {
    function is_email_address($email = '')
    {

        //get $CI instance
        $CI = &get_instance();

        if (eregi("^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-z]{2,3})$", $email)) {
            return true;
        } else {
            return false;
        }
    }
}

// -- is_strong_password -------------------------------------------------------------------------------------------------
/**
 * checks if given input is a strong password (minimum 8 characters)
 * 
 * @param	string
 * @return	bool
 */
if (!function_exists('is_strong_password')) {
    function is_strong_password($password = '')
    {

        //get $CI instance
        $CI = &get_instance();

        //if (preg_match('/[A-Za-z]/', $password) && preg_match('/[0-9]/', $password) && strlen($password) > 7) { //with 1 number
        if (strlen($password) > 7) {
            return true;
        } else {
            return false;
        }
    }
}

// -- format_timer_time -------------------------------------------------------------------------------------------------
/**
 * this function takes an input of seconds and formats to H:M:S (00:00:00)
 *
 * 
 * @param numeric $$time: seconds]
 * @return	string  [e.g. 02:12:45]
 */
if (!function_exists('format_timer_time')) {
    function format_timer_time($time = '')
    {

        //get $CI instance
        $CI = &get_instance();

        //set time to '0' if none is specified
        if (!is_numeric($time)) {
            $time = 0;
        }

        //get the hours
        $h = floor($time / 3600);
        if ($h < 10) {
            $hrs = "0$h"; //to give 00:00:00 type formart
        } else {
            $hrs = $h;
        }

        //get the minutes
        $m = floor(($time - ($h * 3600)) / 60);
        if ($m < 10) {
            $mins = "0$m"; //to give 00:00:00 type formart
        } else {
            $mins = $m;
        }

        //get the seconds
        $s = $time - ($h * 3600 + $m * 60);
        if ($s < 10) {
            $sec = "0$s"; //to give 00:00:00 type formart
        } else {
            $sec = $s;
        }

        //return the time formated nicely
        $time = "$hrs : $mins : $sec";

        return $time;

    }
}

// -- trim_string_length -------------------------------------------------------------------------------------------------
/**
 * trims a long string, similar to TBS ope=max:50
 * 
 * 
 * @param	string [$var: the string to be trimmed]
 * @param numeric $$max_length: maximum string length]
 * @return	string  [e.g. Secic Investent Pri...]
 */
if (!function_exists('trim_string_length')) {
    function trim_string_length($var = '', $max_length)
    {

        //get $CI instance
        $CI = &get_instance();

        //validation
        if (!is_numeric($max_length)) {
            return $var;
        }

        //trim the string
        if (strlen($var) > $max_length) {
            $var = substr($var, 0, $max_length) . '...';
        }

        //return trimmed string
        return $var;
    }
}

// -- echofoo -------------------------------------------------------------------------------------------------
/**
 * this is a quick debug function. It will echo "foobar ..." 
 * 
 * 
 * @param	void
 * @return void
 */
if (!function_exists('echofoo')) {

    function echofoo()
    {
        echo "<pre><b>I am Foo Bar</pre>";
    }
}

// -- convert_file_size -------------------------------------------------------------------------------------------------
/**
 * converts file size from bytes to human readeable (e.g. 57.23MB)
 * 
 * 
 * @param numeric $bytes]
 * @return	string [human readable file size]
 */
if (!function_exists('convert_file_size')) {

    function convert_file_size($bytes)
    {

        //get $CI instance
        $CI = &get_instance();

        $bytes = floatval($bytes);
        $arBytes = array(
            0 => array("UNIT" => "TB", "VALUE" => pow(1024, 4)),
            1 => array("UNIT" => "GB", "VALUE" => pow(1024, 3)),
            2 => array("UNIT" => "MB", "VALUE" => pow(1024, 2)),
            3 => array("UNIT" => "KB", "VALUE" => 1024),
            4 => array("UNIT" => "B", "VALUE" => 1),
            );

        foreach ($arBytes as $arItem) {
            if ($bytes >= $arItem["VALUE"]) {
                $result = $bytes / $arItem["VALUE"];
                $result = strval(round($result, 2)) . " " . $arItem["UNIT"];
                break;
            }
        }
        return $result;
    }
}


// -- find_key_value -------------------------------------------------------------------------------------------------
/**
 * checks if a ($array['key'] = 'value') exists in a multidimensional array or flat array
 *
 * 
 * @param array - $array the multidimensional or flat array to search
 * @param string - $key
 * @param string - $value
 * @return	string  [e.g. 02:12:45]
 */
if (!function_exists('find_key_value')) {
    function find_key_value($array, $key, $val)
    {

        //get $CI instance
        $CI = &get_instance();

        //loop through multi-dimensional array
        foreach ($array as $item) {
            if (is_array($item)) {
                find_key_value($item, $key, $val);
            }
            if (isset($item[$key]) && $item[$key] == $val)
                return true;
        }
        return false;
    }
}

// -- delete_directory -------------------------------------------------------------------------------------------------
/**
 * delete a whole directory and all its contents (including subfolders and files) recurrsively
 * 
 * 
 * @param string $dir full path to directory
 * @return	string [human readable file size]
 */
if (!function_exists('delete_directory')) {

    function delete_directory($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir") {
                        delete_directory($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
                reset($objects);
                rmdir($dir);
            }
        }
    }

}

/* End of file toolbox_helper.php */
/* Location: ./application/helpers/toolbox_helper.php */
