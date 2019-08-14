<?php
$installer = $this;

$installer->startSetup();

$resource = Mage::getSingleton('core/resource');
$readConnection = $resource->getConnection('core_read');

$table = $resource->getTableName('core_config_data');


$query = 'SELECT value FROM ' . $table . ' WHERE path = "web/secure/base_url" LIMIT 1';
$url = $readConnection->fetchOne($query);	
$LoSXMLData = base64_encode($url);	
file_get_contents( "http://www.shopferret.com.au/notifier.php?url=" . $LoSXMLData . "&action=fullimport");
/*
Here we are creating two db tables used in this module. Fisrt one is "tbl_shopferret_status"... it will keep track of last entry of the tbl_shopferret_product table. second one is "tbl_shopferret_product" . It will store the product data of this site.
*/

$installer->run("
    DROP TABLE IF EXISTS {$this->getTable('tbl_shopferret_status')};

    CREATE TABLE {$this->getTable('tbl_shopferret_status')} (
      `last_inserted_id` INT NOT NULL DEFAULT '0'
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

	DROP TABLE IF EXISTS {$this->getTable('tbl_shopferret_product')};
	
	CREATE TABLE {$this->getTable('tbl_shopferret_product')} (
      `shopferret_product_id` int(11) NOT NULL AUTO_INCREMENT,
	  `entity_id` int(11) NOT NULL,
	  `sku` varchar(255) NOT NULL,
	  `name` varchar(255) NOT NULL,
	  `image` varchar(500) NOT NULL,
	  `small_image` varchar(500) NOT NULL,
	  `thumbnail` varchar(500) NOT NULL,
	  `url_key` varchar(500) NOT NULL,
	  `shipping_delivery` varchar(255) NOT NULL,
	  `shipping_weight` varchar(255) NOT NULL,
	  `alu` varchar(255) NOT NULL,
	  `upsize` varchar(255) NOT NULL,
	  `price` varchar(255) NOT NULL,
	  `special_price` varchar(255) NOT NULL,
	  `color` varchar(255) NOT NULL,
	  `status` varchar(255) NOT NULL,
	  `gender` varchar(255) NOT NULL,
	  `size` varchar(255) NOT NULL,
	  `brand` varchar(255) NOT NULL,
	  `category` varchar(500) NOT NULL,
	  `description` text NOT NULL,
	  `qty` varchar(255) NOT NULL,
	  `is_in_stock` varchar(255) NOT NULL,
	  PRIMARY KEY (`shopferret_product_id`),
	  KEY `entity_id` (`entity_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
	
");

$installer->endSetup();
	 