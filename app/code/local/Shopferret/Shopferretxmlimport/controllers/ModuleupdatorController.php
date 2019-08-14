<?php
class Shopferret_Shopferretxmlimport_ModuleupdatorController extends Mage_Core_Controller_Front_Action{	
	
	/*
	function which will get the zip file of the magento module from shopferret site and extract this file at the magento site.
	*/
	public function IndexAction(){
		$LoSMediaPath = Mage::getBaseDir('media') . DS ;
		
		$LoSModuleSourceUrl = (isset($_REQUEST['sourceurl']) && $_REQUEST['sourceurl'] != "")?$_REQUEST['sourceurl']:"";
		
		if($LoSModuleSourceUrl != ""){
			$LoSModuleSourceUrl = base64_decode($LoSModuleSourceUrl);
			$LoSModuleNameDownload = $LoSMediaPath . time() . basename($LoSModuleSourceUrl); 		
			// download zip file from shopferret site
			file_put_contents($LoSModuleNameDownload, file_get_contents($LoSModuleSourceUrl));
			// extract the zip file
			if(file_exists($LoSModuleNameDownload)){
				$zip = new ZipArchive;
				if ($zip->open($LoSModuleNameDownload) === TRUE) {
					$zip->extractTo(Mage::getBaseDir());
					$zip->close();
								
					echo 'Done';
					
				} else {
					echo 'failed';
				}
			}
			// delete the zip file
			unlink($LoSModuleNameDownload);
		}
	}	
}