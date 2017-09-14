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
		
		$printerName = 'EPSON TM-T88IV Receipt';

        $filePath = 'images/intern.png';
		
        if ($qs['filetype'] === 'PDF') {
            $filePath = 'files/LoremIpsum.pdf';
        } else if ($qs['filetype'] === 'TXT') {
            $filePath = 'files/LoremIpsum.txt';
        } else if ($qs['filetype'] === 'DOC') {
            $filePath = 'files/LoremIpsum.doc';
        } else if ($qs['filetype'] === 'XLS') {
            $filePath = 'files/SampleSheet.xls';
        } else if ($qs['filetype'] === 'JPG') {
            $filePath = 'files/penguins300dpi.jpg';
        } else if ($qs['filetype'] === 'PNG') {
            $filePath = 'files/SamplePngImage.png';
        } else if ($qs['filetype'] === 'TIF') {
            $filePath = 'files/patent2pages.tif';
        } else if ($qs['filetype'] === 'HTML') {
            $filePath = 'files/sample.print.html';
        }
		
		//Create array of PrintFile objects you want to print

        if (!Utils::isNullOrEmptyString($filePath)) {
            //Create a ClientPrintJob obj that will be processed at the client side by the WCPP
			
			$fileGroup = array(
			new PrintFile('images/intern.png', 'intern.png', NULL)
			);		
			
			
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