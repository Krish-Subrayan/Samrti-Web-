<?php
include 'WebClientPrint.php';
use Webprint\WebClientPrint;
use Webprint\Utils;
use Webprint\ClientPrintJob;
use Webprint\DefaultPrinter;
use Webprint\UserSelectedPrinter;
use Webprint\InstalledPrinter;
use Webprint\ParallelPortPrinter;
use Webprint\SerialPortPrinter;
use Webprint\NetworkPrinter;
use Webprint\ClientPrintJobGroup;


// Process request
// Generate ClientPrintJob? only if clientPrint param is in the query string
$urlParts = parse_url($_SERVER['REQUEST_URI']);
if (isset($urlParts['query'])) {
    $rawQuery = $urlParts['query'];
    parse_str($rawQuery, $qs);
	
    if (isset($qs[WebClientPrint::CLIENT_PRINT_JOB])) {
		
		
		$useDefaultPrinter=true;
        $pagebreak = '0x1D0x560x00';
		
		$printtype=$_GET['printtype'];
		$bill_printer=$_GET['bill_printer'];
		$tag_printer=$_GET['tag_printer'];
		$filename = $_GET['printerCommands'];
		$filename2 = $_GET['printerCommands2'];
		
		
		$printerName=$_GET['printerName'];
		$printerName2=$_GET['printerName2'];
		$filename = 'files/'.$filename.'.SMART';
		//get printer commands
		$cmds = file_get_contents($filename);
		$cmds .= '0x1D0x560x00';
		
		$cmds2 = ''; 
		//get tag printer commands
		if($filename2!=''){
			$filename2 = 'files/'.$filename2.'.SMART';
			$cmds2 = file_get_contents($filename2);
			$cmds2 .= '0x1D0x560x00';
		}
		
		
		//echo $cmds."<br><br><br>";
		//echo $cmds2."<br><br><br>";
		
		//Create a ClientPrintJob obj that will be processed at the client side by the WCPP
		$cpj1 = new ClientPrintJob();
		//set ESCPOS commands to print...
		$cpj1->printerCommands = $cmds;
        $cpj1->formatHexValues = true;
		if ($printerName === 'null') {
			$cpj1->clientPrinter = new DefaultPrinter();
		} else {
			$cpj1->clientPrinter = new InstalledPrinter($printerName);
		}
		
		
		if($cmds2 !=''){
			//Create a ClientPrintJob obj that will be processed at the client side by the WCPP
			$cpj2 = new ClientPrintJob();
			//set ESCPOS commands to print...
			$cpj2->printerCommands = $cmds2;
			$cpj2->formatHexValues = true;
			
			if ($printerName2 === 'null' || $printerName2 == '' ) {
				$cpj2->clientPrinter = new DefaultPrinter();
			} else {
				$cpj2->clientPrinter = new InstalledPrinter($printerName2);
			}
		}
		
		//Create a ClientPrintJobGroup for printing both ClientPrintJob!
		$cpjg = new ClientPrintJobGroup();
		if($cmds2 !=''){
			//Add ClientPrintJob objects
			$cpjg->clientPrintJobGroup = array($cpj1, $cpj2);
		}
		else{
			//Add ClientPrintJob objects
			$cpjg->clientPrintJobGroup = array($cpj1);
		}

        //Send ClientPrintJob back to the client
        ob_start();
        ob_clean();
        header('Content-type: application/octet-stream');
        echo $cpjg->sendToClient();
        ob_end_flush();
        exit();
    }
            
}
    

 