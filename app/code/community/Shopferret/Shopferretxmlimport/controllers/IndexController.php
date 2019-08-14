<?php
class Shopferret_Shopferretxmlimport_IndexController extends Mage_Core_Controller_Front_Action{
	
	/*
	This function is used by shopferret site to get all the product data in xml format. It will fetch the data from the tbl_shopferret_product and create an xml file with name products_xml.xml and then this xml file is used at shpferret.
	*/
	public function IndexAction(){
		ini_set("memory_limit", '1064M');
		ini_set("max_execution_time", '0');
		$storeid = Mage::app()->getStore()->getId();
		$LoIImortStatus = Mage::getStoreConfig('shopferretsection/shopferretgroup/shopferretfieldimport', $storeid);
		$LoIEntityId = (isset($_REQUEST['entityid']) && $_REQUEST['entityid'] != "")?trim($_REQUEST['entityid']):0;
		
		// check is module enable or not.
		if($LoIImortStatus == 0){
			exit;
		}
						
		$tableNameProducts 	= Mage::getSingleton("core/resource")->getTableName("tbl_shopferret_product");		
		$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
 		
		if($LoIEntityId > 0){
			$selectProducts			= $connection->select()->from($tableNameProducts, array('*'))->where('entity_id=?',$LoIEntityId);
		}else{		
			$selectProducts			= $connection->select()->from($tableNameProducts, array('*')); 			
		}	
		$rowsArrayProducts			= $connection->fetchAll($selectProducts);   //return row
		
		// create xml file for the data of the tbl_shopferret_product
		$file = "products_xml.xml";
		if (file_exists($file)) { unlink ($file); }
		$doc = new DOMDocument();
		$doc->formatOutput = true;
		$productsX = $doc->createElement( "products" );
		$doc->appendChild( $productsX );	
		$LoICounter = 0;
		// creating xml object for product data
		foreach($rowsArrayProducts as $_product){				
			$product = $doc->createElement( "product" );			
			foreach($_product as $key=>$val){
				$keydata = $doc->createElement( $key );
				$keydata->appendChild($doc->createTextNode($val));
				$product->appendChild( $keydata );
			}			
			$productsX->appendChild($product);    		
		}
		// place xml data into the xml file
		file_put_contents($file,$doc->saveXML(),FILE_APPEND);
		// read the file and make it encypted.
		if (file_exists($file)) { 
			echo $this->encrypt(file_get_contents(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . $file), "a!2#45DFgh12**");			
		}
	}
	
	public function cronAction(){
		Mage::getModel('shopferretxmlimport/Cron')->index();
	}
	
	public function priceAction(){
		$LoIProductIdsArray = Mage::getModel('catalog/product')->getCollection()->getAllIds();
		$LoIEntityId = (isset($_REQUEST['entityid']) && $_REQUEST['entityid'] != "")?trim($_REQUEST['entityid']):0;

		foreach($LoIProductIdsArray as $key => $val){
			if(sha1($val) == $LoIEntityId){
				$this->getProductDetail($val);
				break;
			}
		}
	}

	public function getProductDetail($LoIProductId){
		$tableNameProducts 	= Mage::getSingleton("core/resource")->getTableName("tbl_shopferret_product");		
		$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
 		
		$products = Mage::getModel('catalog/product')
					->getCollection()								
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
					->addAttributeToFilter('entity_id', array('eq' => $LoIProductId));
					
		
		foreach($products as $_product){
			// collecting all the required details used at shopfeert site
			$__fields = array();
			
			$__fields['entity_id'] = $_product->getentity_id();
			$__fields['sku'] = $_product->getSku();
			$__fields['name'] = $_product->getName();
			$__fields['image'] =  trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $_product->getImage()); 
			$__fields['small_image'] =  trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product'  . $_product->getSmallImage()); 
			$__fields['thumbnail'] =  trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product'  . $_product->getThumbnail());
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
		}
		echo $LoIProductId;
	}


	public function xmlfeedAction(){
		ini_set("memory_limit", '1064M');
		ini_set("max_execution_time", '0');		
		$tableNameStatus 	= Mage::getSingleton("core/resource")->getTableName("tbl_shopferret_status");
		$tableNameProducts 	= Mage::getSingleton("core/resource")->getTableName("tbl_shopferret_product");
		
		$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
 
		$selectStatus 			= $connection->select()->from($tableNameStatus, array('*')); 
		$rowArrayStatus 		= $connection->fetchRow($selectStatus);   //return row
		
		$LoILastProductId = 0;
		
		// it is fetching the 1000 product from the last entry made into tbl_shopferret_status table
		$products = Mage::getModel('catalog/product')
					->getCollection()								
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
			$__fields['image'] =  trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $_product->getImage()); 
			$__fields['small_image'] =  trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product'  . $_product->getSmallImage()); 
			$__fields['thumbnail'] =  trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product'  . $_product->getThumbnail());
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
		}
		$this->IndexAction();
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
	This finction check whether the cron has fully insert all the data into the tbl_shopferret_product table . If it return 1 then it has successfully done and if not then something is still pending.
	*/
	public function completedAction(){
		ini_set("memory_limit", '1064M');
		ini_set("max_execution_time", '0');		
		$tableNameStatus 	= Mage::getSingleton("core/resource")->getTableName("tbl_shopferret_status");
		$tableNameProducts 	= Mage::getSingleton("core/resource")->getTableName("tbl_shopferret_product");
		
		$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
 
		$selectStatus 			= $connection->select()->from($tableNameStatus, array('*')); 
		$rowArrayStatus 		= $connection->fetchRow($selectStatus);   //return row
		
		$LoILastProductId = 0;
		if(isset($rowArrayStatus['last_inserted_id']) && $rowArrayStatus['last_inserted_id'] != ""){
			$LoILastProductId = $rowArrayStatus['last_inserted_id'];
		}
		
		$products = Mage::getModel('catalog/product')
					->getCollection()
					->setPageSize(10)					
					->addAttributeToSelect('entity_id')					
					->addAttributeToFilter('status', array('eq' => '1'))
					->addAttributeToFilter('type_id', array('eq' => 'simple'))
					->addAttributeToFilter('entity_id', array('gt' => $LoILastProductId));
					
		if(count($products) > 0){
			echo "0";
		}else{
			echo "1";
		}
	
	}
	// it will return the current version of this module
	
	public function VersionAction(){
		$version = Mage::getConfig()->getModuleConfig("Shopferret_Shopferretxmlimport")->version;
		echo "Version - " . $version;
	}
	
	/*
	This function is used to get all the sale data. There are some parameter for filtering the sale data via date. This function used by shopferret wordpress site to get all sale data.
	*/
	public function SaleAction() {
		ini_set("memory_limit", '1064M');
		$LoSDate = (isset($_REQUEST['fromdate']) && $_REQUEST['fromdate'] != "")?trim($_REQUEST['fromdate']):date("Y-m-d");
		// get all the sale order based upon the date parameter
		$orders = Mage::getResourceModel('sales/order_collection')->addAttributeToFilter('updated_at', array('date' => true, 'from' => $LoSDate));
		$storeid = Mage::app()->getStore()->getId();
		$LoIImortStatus = Mage::getStoreConfig('shopferretsection/shopferretgroup/shopferretfieldimport', $storeid);
		
		// create xml file for sale data
		$file = "sale_xml.xml";
   		if (file_exists($file)) { unlink ($file); }
		if($LoIImortStatus == 1){			
			$doc = new DOMDocument();
			$doc->formatOutput = true;
			$sales = $doc->createElement( "sales" );
			$doc->appendChild( $sales );	
			// create xml object for each order
			foreach($orders as $key => $order){			
				$orderObj = $doc->createElement( "order" );				
				
				$LoIOrderId = $order->getData("entity_id");
				
				$entity_id = $doc->createElement( "orderId" );
				$entity_id->appendChild(
					$doc->createTextNode($LoIOrderId)
				);
				$orderObj->appendChild( $entity_id );
				$LoSCustomerEmailAddress = $order->getCustomerEmail();
				
				$customeremailaddress = $doc->createElement( "customeremailaddress" );
				$customeremailaddress->appendChild(
					$doc->createTextNode($LoSCustomerEmailAddress)
				);
				$orderObj->appendChild( $customeremailaddress );
				
				
				$billingDetails = $this->getOrderBillingInfo($order);
				
				$orderBilling = $doc->createElement( "billingaddress" );	
				foreach($billingDetails as $key => $val){
					$ObjDataBilling = "";
					$ObjDataBilling = $doc->createElement( $key );
					$ObjDataBilling->appendChild(
						$doc->createTextNode($val)
					);
					$orderBilling->appendChild( $ObjDataBilling );
					
				}
				
				$orderObj->appendChild( $orderBilling );
				
				$shippingDetails = $this->getOrderShippingInfo($order);
				
				
				$orderShipping = $doc->createElement( "shippingaddress" );	
				foreach($shippingDetails as $key => $val){
					$ObjDataShipping = "";
					$ObjDataShipping = $doc->createElement( $key );
					$ObjDataShipping->appendChild(
						$doc->createTextNode($val)
					);
					$orderShipping->appendChild( $ObjDataShipping );
					
				}
				
				$orderObj->appendChild( $orderShipping );
				
				$orderLineDetails = $this->getOrderLineDetails($order);
				
				$orderproducts = $doc->createElement( "orderproducts" );	
				
				foreach($orderLineDetails as $key => $val){
					$orderproduct = $doc->createElement( "orderproduct" );
					
					
					foreach($val as $key1=>$val1){
						$orderProductData = "";
						$orderProductData = $doc->createElement( $key1 );
						$orderProductData->appendChild(
							$doc->createTextNode($val1)
						);	
						$orderproduct->appendChild( $orderProductData );
					}
					
					$orderproducts->appendChild( $orderproduct );
					
				}
				
				$orderObj->appendChild( $orderproducts );
				
				
				$LoSOrderTotalDetails = array(
												"shipping_description" => $order->getData("shipping_description"), 
												"shipping_amount" => $order->getData("shipping_amount"),
												"discount_amount" => $order->getData("discount_amount"),
												"tax_amount" => $order->getData("tax_amount"),
												"grandtotal" => $order->getGrandTotal(),
												"totalpaid" => $order->getData("total_paid"),
												"paymentMethods" => $order->getPayment()->getMethodInstance()->getTitle(),
												"created_at"  => $order->getData("created_at"),
												"updated_at"  => $order->getData("updated_at"),
												"status"  => $order->getData("status"),
												"customer_firstname"  => $order->getData("customer_firstname"),
												"customer_lastname"  => $order->getData("customer_lastname"),
				
											);
				
				foreach($LoSOrderTotalDetails as $key => $val){
					$ordertotal = "";
					$ordertotal = $doc->createElement( $key );
					$ordertotal->appendChild(
						$doc->createTextNode($val)
					);
					$orderObj->appendChild( $ordertotal );
					
				}
				
				
				$sales->appendChild($orderObj);   
			}
			// write xml file using the above object
			file_put_contents($file,$doc->saveXML(),FILE_APPEND);
			
		}	
		// print xml data in encrypted format
		echo $this->encrypt(file_get_contents(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . "sale_xml.xml"), "a!2#45DFgh12**");	
	}
	// this function is used to get all shipping details of the order
	function getOrderShippingInfo($order)
	{
		$shippingAddress = !$order->getIsVirtual() ? $order->getShippingAddress() : null;
		$address_line1 = "";
		$district = "";
		
		if(strpos($shippingAddress->getData("street"), "\n")){
			$tmp = explode("\n", $shippingAddress->getData("street"));
			$district = $tmp[1];
			$address_line1 = $tmp[0];
		}
		if($address_line1 == ""){
			$address_line1 = $shippingAddress->getData("street");
		}
	 
		return array(
			 "shipping_name" =>  $shippingAddress ? $shippingAddress->getName() : '',
			 "shipping_company" =>   $shippingAddress ? $shippingAddress->getData("company") : '',
			 "shipping_street" =>    $shippingAddress ? $address_line1 : '',
			 "shipping_district" =>  $shippingAddress ? $district : '',
			 "shipping_zip" =>       $shippingAddress ? $shippingAddress->getData("postcode") : '',
			 "shipping_city" =>  $shippingAddress ? $shippingAddress->getData("city") : '',
			 "shipping_state" =>     $shippingAddress ? $shippingAddress->getRegionCode() : '',
			 "shipping_country" =>   $shippingAddress ? $shippingAddress->getCountry() : '',
			"shipping_telephone" => $shippingAddress ? $shippingAddress->getData("telephone") : ''
		);
	}
	// this function is used to get all billing details of the order
	 
	function getOrderBillingInfo($order)
	{
		$billingAddress = !$order->getIsVirtual() ? $order->getBillingAddress() : null;
		$address_line1 = "";
		$district = "";
		
		if(strpos($billingAddress->getData("street"), "\n")){
			$tmp = explode("\n", $billingAddress->getData("street"));
			$district = $tmp[1];
			$address_line1 = $tmp[0];
		}
		if($address_line1 == ""){
			$address_line1 = $billingAddress->getData("street");
		}
		return array(
			 "billing_name" =>       $billingAddress ? $billingAddress->getName() : '',
			 "billing_company" =>    $billingAddress ? $billingAddress->getData("company") : '',
			 "billing_street" =>     $billingAddress ? $address_line1 : '',
			 "billing_district" =>   $billingAddress ? $district : '',
			 "billing_zip" =>        $billingAddress ? $billingAddress->getData("postcode") : '',
			 "billing_city" =>       $billingAddress ? $billingAddress->getData("city") : '',
			 "billing_state" =>  $billingAddress ? $billingAddress->getRegionCode() : '',
			 "billing_country" =>    $billingAddress ? $billingAddress->getCountry() : '',
			"billing_telephone" =>   $billingAddress ? $billingAddress->getData("telephone") : ''
		);
	}
	 
	// this function is used to get all product of the order 
	function getOrderLineDetails($order)
	{
		$lines = array();
		foreach($order->getAllItems() as $prod)
		{
			$line = array();
			$_product = Mage::getModel('catalog/product')->load($prod->getProductId());
			//$_product = Mage::getModel('catalog/product')->getCollection()->addAttributeToFilter('entity_id',$prod->getProductId())->getFirstItem();
			$line['sku'] = $_product->getSku();
			$line['quantity'] = (int)$prod->getQtyOrdered();
			$line['name'] = $prod->getName();
			$line['price'] = (int)$prod->getPrice();
			$line['price'] = Mage::helper('core')->currency($line['price'], true, false);
			$line['itemId'] = (int)$prod->getProductId();		
			$lines[] = $line;
			
		}
		return $lines;
	}
	
	
	function encrypt($string, $key) {				
		return base64_encode($string);
	}	
}