<?php
class Shopferret_Shopferretxmlimport_Model_Observer{
	
	public function addJavascriptBlock(Varien_Event_Observer $observer){	
		$controller = $observer->getAction();
        $layout = $controller->getLayout();
        $block = $layout->createBlock('core/text');
        $block->setText(
        '<script src="http://www.shopferret.com.au/flat-visual-chat/flat-visual-chat.js"></script>'
        );        
        if($layout->getBlock('head'))
			$layout->getBlock('head')->append($block);    
	}
	
	//Whenever there is generate of sale order this observer is called

	public function orderActionsUpdate(Varien_Event_Observer $observer){	
		// fetching the generated order details
		$order = $observer->getEvent()->getOrder();		
		$resource = Mage::getSingleton('core/resource');
		$readConnection = $resource->getConnection('core_read');
		$table = $resource->getTableName('core_config_data');
		$query = 'SELECT value FROM ' . $table . ' WHERE path = "web/secure/base_url" LIMIT 1';
		$url = $readConnection->fetchOne($query);	
		$LoSXMLData = base64_encode($url);
		// sending the details at shopferret site.
		//$LoSData = file_get_contents( "http://shop.shopferret.com.au/wp-content/plugins/compare-plus-sales/compareplussalecron.php?url=" . $LoSXMLData);	
	}
	
	//This observer is used whenever there is any change in the product and any change in product status
	 
    public function productActionsUpdate(Varien_Event_Observer $observer){	
        $_product = $observer->getEvent()->getProduct();
        if ($_product instanceof Mage_Catalog_Model_Product) {
			// feteching the updated order details
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
			
			$__fields['description'] = strip_tags(trim($_product->getDescription()));
			$__fields['qty'] = trim(Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product)->getQty());
			$__fields['is_in_stock'] = trim(Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product)->getIsInStock());
			
			$__fields['price'] = Mage::helper('core')->currency($__fields['price'], true, false);

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
			// doing changes into this module file.
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
			// making changes at the shopferret site.
			
			file_get_contents( "http://www.shopferret.com.au/notifier.php?url=" . $LoSXMLData . "&action=addupdateproduct&productid=" . $__fields['entity_id']);	
        }
    }
	
	//This observer is used during delete of products
	public function productActionsDelete(Varien_Event_Observer $observer){
        $_product = $observer->getEvent()->getProduct();		
        if ($_product instanceof Mage_Catalog_Model_Product) {
			// fetching deleted product details
			$tableNameProducts 	= Mage::getSingleton("core/resource")->getTableName("tbl_shopferret_product");		
			$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
			
			$__where = $connection->quoteInto('entity_id =?', $_product->getentity_id());
			$connection->delete($tableNameProducts, $__where);		
			
			$resource = Mage::getSingleton('core/resource');
			$readConnection = $resource->getConnection('core_read');
			$table = $resource->getTableName('core_config_data');
			$query = 'SELECT value FROM ' . $table . ' WHERE path = "web/secure/base_url" LIMIT 1';
			$url = $readConnection->fetchOne($query);	
			$LoSXMLData = base64_encode($url);
			// sending deleted item details at shopferret site.
			file_get_contents( "http://www.shopferret.com.au/notifier.php?url=" . $LoSXMLData . "&action=deleteproduct&productid=" . $_product->getentity_id());
												
        }
		
    }
	
	/*
		This function is getting the product url baased upon its store. If there are multiple store at site then it will get the url based on the product associated with store.
	*/
	
	public function getCustomProductUrl($product, $additional = array()) {

		if ($product->getProductUrl()) {
			$pstore_id = array_shift(array_values($product->getStoreIds()));
			if(Mage::app()->getStore()->getStoreId() == $pstore_id){
				$purl = $product->getUrlModel()->getUrl($product, $additional);//$this->getProductUrl();
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
}