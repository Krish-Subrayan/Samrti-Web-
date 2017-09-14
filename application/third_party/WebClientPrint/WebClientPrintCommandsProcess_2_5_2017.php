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
		$printtype=$_GET['printtype'];
		$bill_printer=$_GET['bill_printer'];
		$tag_printer=$_GET['tag_printer'];
		$filename = $_GET['printerCommands'];
		
		
		if(strtolower($printtype) == 'tag')
		{
			$useDefaultPrinter=false;
		}
		$printerName=$_GET['printerName'];
		
		$filename = 'files/'.$filename.'.SMART';
		
		
		//get printer commands
		$printerCommands = file_get_contents($filename);
        
		/*$printerCommands .= $newLine;
        $printerCommands .= $newLine;
        $printerCommands .= $newLine;
        $printerCommands .= $newLine;
        $printerCommands .= $newLine;*/
        $printerCommands .= '0x1D0x560x00';
        /*$printerCommands .= $newLine;*/
		
		if($printerName == '')
		{
			$printerName='EPSON TM-T88IV Receipt';
		}
        //get printer settings
        $printerTypeId = $qs['pid'];
        $clientPrinter = NULL;    
		
		// $clientPrinter = new DefaultPrinter();
		
			if ($useDefaultPrinter || $printerName === 'null') {
                $clientPrinter = new DefaultPrinter();
            } else {
                $clientPrinter = new InstalledPrinter($printerName);
            }
		

        //Create a ClientPrintJob obj that will be processed at the client side by the WCPP
        $cpj = new ClientPrintJob();
        $cpj->clientPrinter = $clientPrinter;
        $cpj->printerCommands =   $printerCommands;
        $cpj->formatHexValues = true;

        //Send ClientPrintJob back to the client
        ob_start();
        ob_clean();
        header('Content-type: application/octet-stream');
        echo $cpj->sendToClient();
        ob_end_flush();
        exit();
    }
            
}
    

 