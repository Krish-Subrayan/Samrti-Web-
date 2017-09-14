<?php
/**
* Class : TextSearch
*
* @author  :  MA Razzaque Rupom <rupom_315@yahoo.com>, <rupom.bd@gmail.com>
*             Moderator, phpResource Group(http://groups.yahoo.com/group/phpresource/)
*             URL: http://rupom.wordpress.com
*        
* @version :  1.0
* Date     :  06/25/2006
* Purpose  :  Searching and replacing text within files of specified path
*/

require_once('TextSearch.class.php');
$path = ".."; //setting search path
$logFile = "test/searchResult.txt"; //setting log file
 
$obj = new TextSearch();
$obj->setExtensions(array('html','js')); //setting extensions to search files within
$obj->addExtension('php');//adding an extension to search within
$obj->setSearchKey('font');
//$obj->setReplacementKey('unescape--');//setting replacement text if you want to replace matches with that
$obj->startSearching($path);//starting search
$obj->showLog();//showing log
$obj->writeLogToFile($logFile); //writting result to log file

?>