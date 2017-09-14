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
        
        //get printer commands
        $printerCommands = $qs['printerCommands'];
		$printerCommands .= $newLine;
        $printerCommands .= $newLine;
        $printerCommands .= $newLine;
        $printerCommands .= $newLine;
        $printerCommands .= $newLine;
        $printerCommands .= '0x1D0x560x00';
        $printerCommands .= $newLine;
		
		
        //get printer settings
        $printerTypeId = $qs['pid'];
        $clientPrinter = NULL;    
		
        $clientPrinter = new DefaultPrinter();
		

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
    

 