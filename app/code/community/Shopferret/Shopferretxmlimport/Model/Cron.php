<?php
class Shopferret_Shopferretxmlimport_Model_Cron{	

	// this function is used to make entry of the products into the tbl_shopferret_product . On each run it will import the 1000 products at a time.
	public function index(){
		ini_set("memory_limit", '1064M');
		ini_set("max_execution_time", '0');		
		$tableNameStatus 	= Mage::getSingleton("core/resource")->getTableName("tbl_shopferret_status");
		$tableNameProducts 	= Mage::getSingleton("core/resource")->getTableName("tbl_shopferret_product");
				
		$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
 
		$selectStatus 			= $connection->select()->from($tableNameStatus, array('*')); 
		$rowArrayStatus 		= $connection->fetchRow($selectStatus);   //return row


		$resource = Mage::getSingleton('core/resource');
		$readConnection = $resource->getConnection('core_read');
		$table = $resource->getTableName('core_config_data');
		$query = 'SELECT value FROM ' . $table . ' WHERE path = "web/secure/base_url" LIMIT 1';
		$url = $readConnection->fetchOne($query);	
		$LoSXMLData = base64_encode($url);	

		
		$LoILastProductId = 0;
		if(isset($rowArrayStatus['last_inserted_id']) && $rowArrayStatus['last_inserted_id'] != ""){
			$LoILastProductId = $rowArrayStatus['last_inserted_id'];
		}else{
			$connection->beginTransaction();
			$__fields = array();
			$__fields['last_inserted_id'] = '0';
			$connection->insert($tableNameStatus, $__fields);
			$connection->commit();
		}
		// it is fetching the 1000 product from the last entry made into tbl_shopferret_status table
		$products = Mage::getModel('catalog/product')
					->getCollection()
					->setPageSize(100)					
					->addAttributeToSelect('entity_id')
					->addAttributeToSelect('sku')
					->addAttributeToSelect('name')
					->addAttributeToSelect('image')
					->addAttributeToSelect('small_image')
					->addAttributeToSelect('thumbnail')
					->addAttributeToSelect('url_key')
					->addAttributeToSelect('shipping_delivery')
					->addAttributeToSelect('shipping_weight')
					->addAttributeToSelect('alu')
					->addAttributeToSelect('upsize')
					->addAttributeToSelect('price')
					->addAttributeToSelect('special_price')
					->addAttributeToSelect('color')
					->addAttributeToSelect('status')
					->addAttributeToSelect('gender')
					->addAttributeToSelect('size')
					->addAttributeToSelect('brand')
					->addAttributeToSelect('description')
					->addAttributeToSelect('qty')
					->addAttributeToSelect('is_in_stock')
					->addAttributeToFilter('status', array('eq' => '1'))
					->addAttributeToFilter('type_id', array('eq' => 'simple'))
					->addAttributeToFilter('entity_id', array('gt' => $LoILastProductId));
					
		$LoIStatusRecord = 0;
		foreach($products as $_product){
			$LoIStatusRecord = 1;
			// collecting all the required details used at shopfeert site
			$__fields = array();
			
			$__fields['entity_id'] = $_product->getentity_id();
			$__fields['sku'] = $_product->getSku();
			$__fields['name'] = $_product->getName();
			$__fields['image'] =  trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $_product->getData('image')); 
			$__fields['small_image'] =  trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product'  . $_product->getData('small_image')); 
			$__fields['thumbnail'] =  trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product'  . $_product->getData('thumbnail'));
			$__fields['url_key'] =  trim($this->getCustomProductUrl($_product)); 
			$__fields['shipping_delivery'] =  trim($_product->getShippingDelivery()); 
			$__fields['shipping_weight'] =  trim($_product->getShippingWeight());
			$__fields['alu'] = trim($_product->getAlu());
			$__fields['upsize'] = trim($_product->getUpsize());
			$__fields['price'] =  (($_product->getSpecialPrice()== "")?trim($_product->getPrice()):$_product->getSpecialPrice());
			$__fields['special_price'] = (($_product->getSpecialPrice()== "")?trim($_product->getPrice()):$_product->getSpecialPrice());
			$__fields['color'] = trim($_product->getResource()->getAttribute('color')->getFrontend()->getValue($_product));
			$__fields['status'] = trim($_product->getResource()->getAttribute('status')->getFrontend()->getValue($_product));
			if($_product->getResource()->getAttribute('gender'))
				$__fields['gender'] = trim($_product->getResource()->getAttribute('gender')->getFrontend()->getValue($_product));
			else
				$__fields['gender'] = "";
			
			if($_product->getResource()->getAttribute('size'))		
				$__fields['size'] = trim($_product->getResource()->getAttribute('size')->getFrontend()->getValue($_product));
			else
				$__fields['size'] = "";
				
			if($_product->getResource()->getAttribute('brand'))		
				$__fields['brand'] = trim($_product->getAttributeText('brand'));
			else
				$__fields['brand'] = "";
			$__fields['description'] = trim($_product->getDescription());
			$__fields['qty'] = trim(Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product)->getQty());
			$__fields['is_in_stock'] = trim(Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product)->getIsInStock());
			
			$cats = $_product->getCategoryIds();
			$LoSCategory = "";
			foreach ($cats as $category_id) {
				$_cat = Mage::getModel('catalog/category')->load($category_id) ;
				$LoSCategory .= ($LoSCategory != "")? "###":"";
				$LoSCategory .= $_cat->getName();
			} 
			if($LoSCategory == ""){
				$LoSCategory = "Default";
			}			
			$__fields['category'] = trim($LoSCategory);
			// insert the data at the tbl_shopferret_product table
			$connection->beginTransaction();
			$selectIsExists			= $connection->select()->from($tableNameProducts, array('*'))->where('entity_id=?',$__fields['entity_id']); 
			$rowArrayExists 		= $connection->fetchRow($selectIsExists);   //return row
			$LoILastId = $__fields['entity_id'];
			if(isset($rowArrayExists['shopferret_product_id']) && $rowArrayExists['shopferret_product_id'] != ""){
				$__where = $connection->quoteInto('shopferret_product_id =?', $rowArrayExists['shopferret_product_id']);
				$connection->update($tableNameProducts, $__fields, $__where);								
			}else{
				$connection->insert($tableNameProducts, $__fields);
			}			
			$connection->commit();
			
			$connection->beginTransaction();
			$__fieldsInsertion = array();
			$__fieldsInsertion['last_inserted_id'] = $LoILastId;
			$connection->update($tableNameStatus, $__fieldsInsertion);
			$connection->commit();
			
			file_get_contents( "http://www.shopferret.com.au/notifier.php?url=" . $LoSXMLData . "&action=addupdateproduct&productid=" . $__fields['entity_id']);
		}
	}
	
	/*
		This function is getting the product url baased upon its store. If there are multiple store at site then it will get the url based on the product associated with store.
	*/
	
	public function getCustomProductUrl($product, $additional = array()) {
		
		if ($product->getProductUrl()) {
			$pstore_id = array_shift(array_values($product->getStoreIds()));
			if(Mage::app()->getStore()->getStoreId() == $pstore_id){
				$purl = $product->getUrlModel()->getUrl($product, $additional);
			}else{				
				$productnew= Mage::getModel('catalog/product')->setStoreId($pstore_id)->load($product->getentity_id());				
				$purl = $productnew->getProductUrl();
			}
			if (!isset($additional['_escape'])) {
				$additional['_escape'] = true;
			}
			return $purl;
		}
		return '#';
		
	}
	
	/*
		cron run once a day at 5th minute of the night (00:05 PM) And it will get all the product whose special price start date is today or whose special price end date is yesterday and it will update the product prices at shopferret site.
	*/
	
	public function discountindex(){
	
		$product    = Mage::getModel('catalog/product');
		// getting all the product whose special price data is of today
		$todayDate  = date("Y-m-d", time());
		$yesterdayDate  = date("Y-m-d", mktime(0, 0, 0, date('m'), date('d')-1, date('y')));		
		$products =  $product->getCollection()	
					->addAttributeToSelect('entity_id')
					->addAttributeToSelect('sku')
					->addAttributeToSelect('name')
					->addAttributeToSelect('image')
					->addAttributeToSelect('small_image')
					->addAttributeToSelect('thumbnail')
					->addAttributeToSelect('url_key')
					->addAttributeToSelect('shipping_delivery')
					->addAttributeToSelect('shipping_weight')
					->addAttributeToSelect('alu')
					->addAttributeToSelect('upsize')
					->addAttributeToSelect('price')
					->addAttributeToSelect('special_price')
					->addAttributeToSelect('color')
					->addAttributeToSelect('status')
					->addAttributeToSelect('gender')
					->addAttributeToSelect('size')
					->addAttributeToSelect('brand')
					->addAttributeToSelect('description')
					->addAttributeToSelect('qty')
					->addAttributeToSelect('is_in_stock')										
					->addAttributeToFilter('special_from_date', array('date'=>true, 'from'=> $todayDate))
					->addAttributeToFilter('status', array('eq' => '1'))
					->addAttributeToFilter('type_id', array('eq' => 'simple'));
					
		foreach($products as $_product){
			// it will update the product at this module table and as well as into the shopferret site
			$this->productActionsUpdateCron($_product);				
		}		
		
		// getting all the product whose special price end data is of yesterday
		
		$products =  $product->getCollection()	
					->addAttributeToSelect('entity_id')
					->addAttributeToSelect('sku')
					->addAttributeToSelect('name')
					->addAttributeToSelect('image')
					->addAttributeToSelect('small_image')
					->addAttributeToSelect('thumbnail')
					->addAttributeToSelect('url_key')
					->addAttributeToSelect('shipping_delivery')
					->addAttributeToSelect('shipping_weight')
					->addAttributeToSelect('alu')
					->addAttributeToSelect('upsize')
					->addAttributeToSelect('price')
					->addAttributeToSelect('special_price')
					->addAttributeToSelect('color')
					->addAttributeToSelect('status')
					->addAttributeToSelect('gender')
					->addAttributeToSelect('size')
					->addAttributeToSelect('brand')
					->addAttributeToSelect('description')
					->addAttributeToSelect('qty')
					->addAttributeToSelect('is_in_stock')										
					->addAttributeToFilter('special_to_date', array('date'=>true, 'from'=> $yesterdayDate))
					->addAttributeToFilter('status', array('eq' => '1'))
					->addAttributeToFilter('type_id', array('eq' => 'simple'));
					
		foreach($products as $_product){
			// it will update the product at this module table and as well as into the shopferret site
			$this->productActionsUpdateCron($_product);				
		}
		
	}
	
	// it will update the product at this module table and as well as into the shopferret site
	public function productActionsUpdateCron( $_product){		
        if ($_product instanceof Mage_Catalog_Model_Product) {
			$tableNameStatus 	= Mage::getSingleton("core/resource")->getTableName("tbl_shopferret_status");
			$tableNameProducts 	= Mage::getSingleton("core/resource")->getTableName("tbl_shopferret_product");		
			$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
			
			$selectStatus 			= $connection->select()->from($tableNameStatus, array('*')); 
			$rowArrayStatus 		= $connection->fetchRow($selectStatus);   //return row
			
			if(isset($rowArrayStatus['last_inserted_id']) && $rowArrayStatus['last_inserted_id'] != ""){
				
			}else{
				$connection->beginTransaction();
				$__fields = array();
				$__fields['last_inserted_id'] = '0';
				$connection->insert($tableNameStatus, $__fields);
				$connection->commit();
			}
		    // creating an arry of data regarding the product       
			$__fields = array();			
			$__fields['entity_id'] = $_product->getentity_id();
			$__fields['sku'] = $_product->getSku();
			$__fields['name'] = $_product->getName();
			$__fields['image'] =  trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $_product->getData('image')); 
			$__fields['small_image'] =  trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product'  . $_product->getData('small_image')); 
			$__fields['thumbnail'] =  trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product'  . $_product->getData('thumbnail'));
			$__fields['url_key'] =  trim($this->getCustomProductUrl($_product)); 
			$__fields['shipping_delivery'] =  trim($_product->getShippingDelivery()); 
			$__fields['shipping_weight'] =  trim($_product->getShippingWeight());
			$__fields['alu'] = trim($_product->getAlu());
			$__fields['upsize'] = trim($_product->getUpsize());
			$__fields['price'] =  (($_product->getSpecialPrice()== "")?trim($_product->getPrice()):$_product->getSpecialPrice());
			$__fields['special_price'] = (($_product->getSpecialPrice()== "")?trim($_product->getPrice()):$_product->getSpecialPrice());
			$__fields['color'] = trim($_product->getResource()->getAttribute('color')->getFrontend()->getValue($_product));
			$__fields['status'] = trim($_product->getResource()->getAttribute('status')->getFrontend()->getValue($_product));
			if($_product->getResource()->getAttribute('gender'))
				$__fields['gender'] = trim($_product->getResource()->getAttribute('gender')->getFrontend()->getValue($_product));
			else
				$__fields['gender'] = "";
			
			if($_product->getResource()->getAttribute('size'))		
				$__fields['size'] = trim($_product->getResource()->getAttribute('size')->getFrontend()->getValue($_product));
			else
				$__fields['size'] = "";
				
			$__fields['brand'] = trim($_product->getAttributeText('brand'));
			$__fields['description'] = trim($_product->getDescription());
			$__fields['qty'] = trim(Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product)->getQty());
			$__fields['is_in_stock'] = trim(Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product)->getIsInStock());
			
			$cats = $_product->getCategoryIds();
			$LoSCategory = "";
			foreach ($cats as $category_id) {
				$_cat = Mage::getModel('catalog/category')->load($category_id) ;
				$LoSCategory .= ($LoSCategory != "")? "###":"";
				$LoSCategory .= $_cat->getName();
			} 
			if($LoSCategory == ""){
				$LoSCategory = "Default";
			}			
			$__fields['category'] = trim($LoSCategory);
			
			$connection->beginTransaction();
			$selectIsExists			= $connection->select()->from($tableNameProducts, array('*'))->where('entity_id=?',$__fields['entity_id']); 
			$rowArrayExists 		= $connection->fetchRow($selectIsExists);   //return row
			$LoILastId = $__fields['entity_id'];
			$LoIIsinserted = 0;
			// updating the product into our existing table
			if(isset($rowArrayExists['shopferret_product_id']) && $rowArrayExists['shopferret_product_id'] != ""){
				$__where = $connection->quoteInto('shopferret_product_id =?', $rowArrayExists['shopferret_product_id']);
				$connection->update($tableNameProducts, $__fields, $__where);								
			}else{
				$connection->insert($tableNameProducts, $__fields);
				$LoIIsinserted = 1;
			}			
			$connection->commit();	
			
			if($LoIIsinserted == 1){
				$connection->beginTransaction();
				$__fieldsInsertion = array();
				$__fieldsInsertion['last_inserted_id'] = $LoILastId;
				$connection->update($tableNameStatus, $__fieldsInsertion);
				$connection->commit();
			}
			$resource = Mage::getSingleton('core/resource');
			$readConnection = $resource->getConnection('core_read');
			$table = $resource->getTableName('core_config_data');
			$query = 'SELECT value FROM ' . $table . ' WHERE path = "web/secure/base_url" LIMIT 1';
			$url = $readConnection->fetchOne($query);	
			$LoSXMLData = base64_encode($url);	
			// updating the product at shopferret site.		
			
			$resource = Mage::getSingleton('core/resource');
			$readConnection = $resource->getConnection('core_read');
			$table = $resource->getTableName('core_config_data');
			$query = 'SELECT value FROM ' . $table . ' WHERE path = "web/secure/base_url" LIMIT 1';
			$url = $readConnection->fetchOne($query);	
			$LoSXMLData = base64_encode($url);		
			// making changes at the shopferret site.
			
			file_get_contents( "http://www.shopferret.com.au/notifier.php?url=" . $LoSXMLData . "&action=addupdateproduct&productid=" . $__fields['entity_id']);	
        }
    }	
}