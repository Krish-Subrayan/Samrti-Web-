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

class TextSearch
{	 
	 var $extensions         = array();
	 var $searchKey          = '';	 
	 var $replacementKey     = '';
	 var $caseSensitive      = 0; //by default case sensitivity is OFF
	 var $findAllExts        = 1; //by default all extensions
	 var $isReplacingEnabled = 0;
	 var $logString          = '';
	 var $errorText          = '';
	 var $totalFound         = 0; //total matches
	 
   /** 
   *   Sets extensions to look
   *   @param Array extensions
   *   @return none
   */   
   function setExtensions($extensions = array())
   {
      $this->extensions = $extensions;
      
      if(sizeof($this->extensions))	
      {
         $this->findAllExts = 0; //not all extensions
      }
   }//End of Method

   /** 
   * Adds a search extension
   * @param  file extension
   * @return none
   */   
   function addExtension($extension)
   {
      
      array_push($this->extensions, $extension);      
      $this->findAllExts = 0; //not all extensions 
      
   }//End of function

  
   /** 
   * Sets search key and case sensitivity
   * @param search key, case sensitivity
   * @return none
   */   
   function setSearchKey($searchKey, $caseSensitive = 0)
   {
      $this->searchKey = $searchKey;
      
      if($caseSensitive)
      {
         $this->caseSensitive	= 1; //yeah, case sensitive
      }
   }//End of function

   /** 
   *   Sets key to replace searchKey with
   *   @param : replacement key
   *   @return none
   */   
   function setReplacementKey($replacementKey)
   {
   
      $this->replacementKey     = $replacementKey;
      $this->isReplacingEnabled = 1;   
   
   }//End of function
   
   /**
   * Wrapper function around function findDirFiles()
   * @param $path to search
   * @return none
   */
   function startSearching($path)
   {
      $this->findDirFiles($path);      
   }//EO Method
   
   /** 
   * Recursively traverses files of a specified path 
   * @param  path to execute
   * @return  none
   */   
   function findDirFiles($path)
   {
      $dir = opendir ($path);
      
      while ($file = readdir ($dir)) 
      {
         if (($file == ".") or ($file == ".."))
         {
            continue;
         }                
		     	
		     if (filetype ("$path/$file") == "dir")
		     {		     	
            $this->findDirFiles("$path/$file"); //recursive traversing here
         }                         
				 elseif($this->matchedExtension($file)) //checks extension if we need to search this file
				 {				 	  
           if(filesize("$path/$file"))
           {
               $this->searchFileData("$path/$file"); //search file data           	
           }   
         }               	 
      } //End of while
      
      closedir($dir);
         
   }//EO Method

   /** 
   * Finds extension of a file
   * @param filename
   * @return file extension
   */
   function findExtension($file)
   {
	   return array_pop(explode(".",$file));
   }//End of function
   
   /**
   * Checks if a file extension is one the extensions we are going to search 
   * @param filename
   * @return true in success, false otherwise
   */   
   function matchedExtension($file)
   {   
      if($this->findAllExts) //checks if all extensions are to be searched
      {
         return true;	
      }      
      elseif(sizeof(array_keys($this->extensions, $this->findExtension($file)))==1)
      {
         return true;	
      }
      
      return false;		
   
   }//EO Method
   
   /**
   * Searches data, replaces (if enabled) with given key, prepares log 
   * @param $file
   * @return none
   */
   function searchFileData($file)
   {
      $searchKey  = preg_quote($this->searchKey, '/');
      
      if($this->caseSensitive)
      {
         $pattern    = "/$searchKey/U";
      }
      else
      {
      	 $pattern    = "/$searchKey/Ui";
      }
      
      $subject       = file_get_contents($file);
            
      $found = 0;
            
      $found = preg_match_all($pattern, $subject, $matches, PREG_PATTERN_ORDER);	           
      
      $this->totalFound +=$found;
                  
      if($found)
      {
      	 $foundStr = "Found in $found places";
         $this->appendToLog($file, $foundStr);
      }
      
       
      if($this->isReplacingEnabled && $this->replacementKey && $found)
      {           
         $outputStr = preg_replace($pattern, $this->replacementKey, $subject);	                             
         $foundStr = "Found in $found places";
         $this->writeToFile($file, $outputStr);
         $this->appendToLog($file, $foundStr, $this->replacementKey);
        
      }
      elseif($this->isReplacingEnabled && $this->replacementKey == '')
      {
         $this->errorText .= "Replacement Text is not defined\n";
         $this->appendToLog($file, "Replacement Text is not defined", $this->replacementKey);
      }
      elseif(!found)
      {
         $this->appendToLog($file, "No matching Found", $this->replacementKey);
      }
      
   }//EO Method
   
   /**
   * Writes new data (after the replacement) to file
   * @param $file, $data
   * @return none
   */
   function writeToFile($file, $data)
   {           
      if(is_writable($file))
      {
         $fp = fopen($file, "w");
         fwrite($fp, $data);
         fclose($fp);	
      }
      else
      {
         $this->errorText .= "Can not replace text. File $file is not writable. \nPlease make it writable\n";	
      }
      
   }//EO Method

   /**
   * Appends log data to previous log data
   * @param filename, match string, replacement key if any
   * @return none
   */   
   function appendToLog($file, $matchStr, $replacementKey = null)
   {
   	  if($this->logString == '')
   	  {
   	     $this->logString = " --- Searching for '".$this->searchKey."' --- \n";
   	  } 
      
      if($replacementKey == null)
      {
         $this->logString .= "Searching File $file : " . $matchStr."\n";       	
      }
      else
      {
         $this->logString .= "Searching File $file : " . $matchStr.". Replaced by '$replacementKey'\n";       	
      }
      
   }//EO Method
   
   /**
   * Shows Log
   * @param none
   * @return none
   */
   function showLog()
   {
      $this->dBug("------ Total ".$this->totalFound." Matches Found -----");
      $this->dBug(nl2br($this->logString));              
      
      if($this->errorText!='')
      {
      	 $this->dBug("------Error-----");
         $this->dBug(nl2br($this->errorText));   	 	
      }
   }//EO Method
   
   /**
   * Writes log to file
   * @param log filename
   * @return none
   */
   function writeLogToFile($file)
   {      
      $fp = fopen($file, "wb") OR die("Can not open file <b>$file</b>");            
      fwrite($fp, $this->logString);
      fwrite($fp, "\n------ Total ".$this->totalFound." Matches Found -----\n");
      if($this->errorText!='')
      {
         fwrite($fp, "\n------Error-----\n");     
         fwrite($fp, $this->errorText);
      }
      
      fclose($fp);      
   }//EO Method
   
   /**
   * Dumps data
   * @param data to be dumped
   * @return none
   */
   function dBug($dump)
   {
      echo "<pre>";
      print_r($dump);
      echo "</pre>";	 
   }//EO Method
   
} //End of class

?>
