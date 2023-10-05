<?php

ini_set('display_errors', true);
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('utf-8');
	 
//echo "<pre>";
$fileName = '';

//CARICO LIBRERIA PHP-EXCEL
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

//CARTELLE UPLOAD e ZIP
$uploadDir = __DIR__.'/upload';


$folder_download = "download/";


$filexml = "payments.xml";
$extension = "";
$sheetData = [];

if(file_exists($filexml)){
    unlink($filexml);
}

//UPLOAD FILE
if(isset($_POST['SubmitButton'])){ 	


	
	
	foreach ($_FILES as $file) {
		if (UPLOAD_ERR_OK === $file['error']) {
			$fileName = basename($file['name']);
			move_uploaded_file($file['tmp_name'], $uploadDir.DIRECTORY_SEPARATOR.$fileName);
			
			$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
		}
	}
	
	if($extension != 'xls' && $extension != "xlsx" && $extension != "csv" ) {
		echo "Devi caricare un file excel o csv!<br/>";
		echo '<a href="/sepaphp" >Torna indietro</a>';
		die();
	} 
	
	
	if('csv' == $extension) {
		$reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
		$reader->setInputEncoding('CP1252');
	} 
	
	
	//LEGGO IL FILE EXCEL e PREPARO l'ARRAY PER I DATI
	$spreadsheet = $reader->load("upload/".$fileName);
	//$d=$spreadsheet->getSheet(0)->toArray();
	$sheetData = $spreadsheet->getActiveSheet()->toArray();
	
	unset($sheetData[0]);
		
	$totalSum = 0;
	$totalTransfer = 0;
	foreach ($sheetData as $k=> $t) {
		$amount = $t[2];
	
		$amount = normilizeAmount($amount);

		
		$totalSum += (float)$amount;
		$totalTransfer++;
	}
	

	// Creazione del file XML
	$xml = new SimpleXMLElement('<CBIPaymentRequest></CBIPaymentRequest>');	
	$xml->addAttribute("xmlns", "urn:CBI:xsd:CBIPaymentRequest.00.04.00");

	
	
	// Creazione di un elemento di pagamento nel file XML
	$GrpHdr = $xml->addChild('GrpHdr');
	$GrpHdr->addChild('MsgId', "Wiz20230601n2/" . date("Y-m-d\TH:i:s"));
	$GrpHdr->addChild('CreDtTm', date("Y-m-d\TH:i:s\Z"));	
	$GrpHdr->addChild('NbOfTxs', $totalTransfer);	
	$GrpHdr->addChild('CtrlSum', $totalSum);	
	$InitgPty = $GrpHdr->addChild('InitgPty');
	$InitgPty->addChild('Nm', "UNION GAS METANO S.P.A.");	
	$id = $InitgPty->addChild('Id');
	$OrgId = $id->addChild("OrgId");
	$Othr = $OrgId->addChild("Othr");
	$Othr->addChild("Id", "0274005M");
	$Othr->addChild("Issr", "CBI");


		
	$PmtInf = $xml->addChild('PmtInf');
	$PmtInf->addChild("PmtInfId", "Wiz20230601n2/" . date("Y-m-d"));
	$PmtInf->addChild("PmtMtd", "TRF");

	$PmtTpInf = $PmtInf->addChild("PmtTpInf");
	$SvcLvl = $PmtTpInf->addChild("SvcLvl");
	$SvcLvl->addChild("Cd", "SEPA");
	
	$PmtInf->addChild("ReqdExctnDt", date("Y-m-d"));
	$Dbtr = $PmtInf->addChild("Dbtr");
	$Dbtr->addChild("Nm", "UNION GAS METANO S.P.A.");
	$PstlAdr = $Dbtr->addChild("PstlAdr");
	$PstlAdr->addChild("StrtNm", "VIA DOMENICO SCARLATTI");
	$PstlAdr->addChild("PstCd", "20124");
	$PstlAdr->addChild("TwnNm", "MILANO");
	$PstlAdr->addChild("CtrySubDvsn", "MILANO");
	$PstlAdr->addChild("Ctry", "IT");
	$PstlAdr->addChild("AdrLine", "VIA DOMENICO SCARLATTI, 20124, MILANO, Milano, IT");
	
	
	$Id = $Dbtr->addChild("Id");
	$OrgId = $Id->addChild("OrgId");
	$Othr = $OrgId->addChild("Othr");
	$Othr->addChild("Id", "IT03163990611");
	$Othr->addChild("Issr", "ADE");

	
	

	$DbtrAcct = $PmtInf->addChild("DbtrAcct");
	$Id = $DbtrAcct->addChild("Id");
	$Id->addChild("IBAN", "IT20N0200803443000106126726");
	
	$DbtrAgt = $PmtInf->addChild("DbtrAgt");
	$FinInstnId = $DbtrAgt->addChild("FinInstnId");
	$FinInstnId->addChild("BIC", "BCITITMMXXX");
	$ClrSysMmbId = $FinInstnId->addChild("ClrSysMmbId");
	$ClrSysMmbId->addChild("MmbId", "02008");
	$PmtInf->addChild("ChrgBr", "SLEV");
	
	foreach ($sheetData as $k=> $t) {
		
		$myId = $k+1;
		
		if($t[0] == ""){
			continue;
		}
		$nominativo = $t[0];
		$iban = $t[1];
		$amount = $t[2];
		$amount = normilizeAmount($amount);
		
		$causale = $t[3];
		
		$CdtTrfTxInf = $PmtInf->addChild("CdtTrfTxInf");
		$PmtId = $CdtTrfTxInf->addChild("PmtId");
		$PmtId->addChild("InstrId", $myId);
		$PmtId->addChild("EndToEndId", $myId);
		
		$PmtTpInf = $CdtTrfTxInf->addChild("PmtTpInf");
		$CtgyPurp = $PmtTpInf->addChild("CtgyPurp");
		$CtgyPurp->addChild("Cd", "SUPP");

		
		$Amt = $CdtTrfTxInf->addChild("Amt");
		$InstdAmt = $Amt->addChild("InstdAmt", $amount);
		$InstdAmt->addAttribute("Ccy", "EUR");
		
		
		$Cdtr = $CdtTrfTxInf->addChild("Cdtr");
		$Cdtr->addChild("Nm", $nominativo);
		
		
		/*
		$PstlAdr = $Cdtr->addChild("PstlAdr");
		$PstlAdr->addChild("StrtNm", "VIA DOMENICO SCARLATTI");
		$PstlAdr->addChild("PstCd", "20124");
		$PstlAdr->addChild("TwnNm", "MILANO");
		$PstlAdr->addChild("CtrySubDvsn", "MILANO");
		$PstlAdr->addChild("Ctry", "IT");
		$PstlAdr->addChild("AdrLine", "VIA DOMENICO SCARLATTI, 20124, MILANO, Milano, IT");
		*/
		
		/*
		$Id = $Cdtr->addChild("Id");
	
		$OrgId = $Id->addChild("OrgId");
		$Othr = $OrgId->addChild("Othr");
		$Othr->addChild("Id", $myId);
		$Othr->addChild("Issr", "ADE");
		
		*/
	
		
		$CdtrAcct = $CdtTrfTxInf->addChild("CdtrAcct");
		$Id = $CdtrAcct->addChild("Id");
		$Id->addChild("IBAN", $iban);
		
		$RmtInf = $CdtTrfTxInf->addChild("RmtInf");
		
		$RmtInf->addChild("Ustrd", $causale);
	}
	
	$xml->asXML($filexml);

}


function normilizeAmount($amount){
	$amount = str_replace("€", "", $amount);
	$amount = str_replace(",",".",$amount);
	$amount = preg_replace('/\.(?=.*\.)/', '', $amount);
	$amount = trim($amount);
	$amount = number_format((float)$amount, 2, '.', '');
	return $amount;
}


?>



<!DOCTYPE html>
<html>
<head>
	<title>Pagamenti Massivi SEPA</title>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
	<style>
		.sidebar{
			background: #eee;
			height: 100vH;
			overflow:scroll;
			overflow-x:hidden;
		}
		
		.primary{
			height: 100vH;
			overflow:hidden;
		}
		
		table{
			font-size: 12px;
		}
		
		.logo-container{
			position: absolute;
			bottom: 0;
			left: 0;
		}
	</style>
</head>
<body>
<div class="container-fluid">

	<div class="row">
		<div class="primary col-md-4">
			<h2>Pagamenti Massivi SEPA</h2>
			<form method="POST" action="" enctype="multipart/form-data">
			
		
				<div class="dsp form-group">
					<label>Carica File in formato Excel o CSV</label>
					<input type="file" name="file" class="form-control">
				</div>
				<div class="dsp form-group">
					<button type="submit" name="SubmitButton" class="btn btn-success">Carica</button>
				</div>
			</form>
			
			<?php
			if(file_exists($filexml)){

			?>
			<button class="btn btn-primary"><a style="color:#fff" target="_blank" href="<?php echo $filexml?>" download>clicca per scaricare il file xml</a></button>
			<?php
		        
			}
			
			
			
		?>
			<div class="logo-container">
				<div id="logo" class="full-width">
					<img src="logo.png" alt="Eva Energy Service - We can do, for you!">
				</div>
			</div>
		</div>
		<div class="sidebar col-md-8">
			<h2>Lista Pagamenti</h2>
			
			<table class="table table-striped">
				<thead>
					<tr><th>#</th><th>Beneficiario</th><th>IBAN</th><th>Importo</th><th>Causale</th></tr>
						
				</thead>
				<tbody>
			<?php 
				
				foreach ($sheetData as $k=> $t) {
			?>	
				<tr><td><?php echo $k  ?><td><?php echo $t[0] ?></td><td><?php echo $t[1] ?></td><td><?php echo $t[2] ?></td><td><?php echo $t[3] ?></td></tr>
			<?php 
				}
			?>
				</tbody>
			</table>
		</div>

	</div>
	
	
</div>


</body>
</html>