<?php
include 'WebClientPrint.php';

use Webprint\WebClientPrint;
use Webprint\Utils;
use Webprint\DefaultPrinter;
use Webprint\InstalledPrinter;
use Webprint\PrintFile;
use Webprint\ClientPrintJob;


// Process request
// Generate ClientPrintJob? only if clientPrint param is in the query string
$urlParts = parse_url($_SERVER['REQUEST_URI']);
if (isset($urlParts['query'])) {
    $rawQuery = $urlParts['query'];
    parse_str($rawQuery, $qs);
	
    if (isset($qs[WebClientPrint::CLIENT_PRINT_JOB])) {

        /*$useDefaultPrinter = ($qs['useDefaultPrinter'] === 'checked');
        $printerName = urldecode($qs['printerName']);
		$fileName = uniqid() . '.' . $qs['filetype'];*/
		$useDefaultPrinter=true;
		//$printerName = 'EPSON TM-T88IV Receipt';
		$filePath = 'images/intern.png';
		$orderid=$_GET['order'];
		$orderid = ltrim($orderid, '0');		
		$printtype=$_GET['printtype'];
		if(strtolower($printtype) == 'tag')
		{
			$useDefaultPrinter=false;
		}
		$printerName=$_GET['printerName'];
		if($orderid == 'download')
		{
			$directory = "images/report/";
		}
		else
		{	
			$directory = "images/".$orderid."/".$printtype.'/';
		}
		
		
		//get all image files with a .jpg extension. This way you can add extension parser
		$images = glob($directory . "{*.png}", GLOB_BRACE);
		$listImages=array();
		foreach($images as $image){
			if(file_exists($image))
			{
				$listImages[]=$image;
			}
		}
		
		
		//Create array of PrintFile objects you want to print
		if (!Utils::isNullOrEmptyString($filePath)) {
            //Create a ClientPrintJob obj that will be processed at the client side by the WCPP
			$i=1;
				$fileGroup = array();	
				foreach($listImages as $img)
				{
					$imgarray=explode('/',$img);
					
					if(count($listImages) == $i)
					{
						$fileGroup[]=new PrintFile($img, end($imgarray), NULL);
					}
					else
					{
						$fileGroup[]=new PrintFile($img, end($imgarray), NULL);
					}
					
					$i++;
				}
			
			
			
			
            $cpj = new ClientPrintJob();
            //$cpj->printFile = new PrintFile($filePath, $fileName, null);
			$cpj->printFileGroup = $fileGroup;
 
            if ($useDefaultPrinter || $printerName === 'null') {
                $cpj->clientPrinter = new DefaultPrinter();
            } else {
                $cpj->clientPrinter = new InstalledPrinter($printerName);
            }

            //Send ClientPrintJob back to the client
            ob_start();
            ob_clean();
            header('Content-type: application/octet-stream');
            echo $cpj->sendToClient();
            ob_end_flush();
            exit();
        }
   }
}