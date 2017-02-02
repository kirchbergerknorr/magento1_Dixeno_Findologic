<?php
/**
 * @package Findologic csv generation
 */
class Dixeno_Findologic_Model_ExportCatalog extends Mage_Core_Model_Abstract {
	
	static $PAGE_SIZE = 100;

	private $_storeModel = null;
	private $_entityIypeId    = null;
	private $_attributeImage = null;	 
	public $_row = 0;	 
	public $product_count = 0;	 
	public $select_attributes = array();

	const LOG_FILE_NAME		= 'Export-Findologic';
	const FILE_NAME     	= 'findologic';
	const CSV_DELIMITEUR	= '\t';
	const PRODUCT			= 1;
	const IMAGE				= 2;
	
	public function currency_code()
	{
		return Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
	}
	
	protected function _store($code=''){
		if($code != '')
			return Mage::getModel('core/store')->load($code);
		else
			return Mage::app()->getStore();
			
	}
	/**
	 * return CSV delimiter
	 * @return string
	 */
	public function getDelimiter(){
		return self::CSV_DELIMITEUR ;
	}
	/**
	 * return log file name
	 * @return string 
	 */
	public function getLogFile (){
		return date('Ymd').'-'.self::LOG_FILE_NAME.'.log';
	}
	
	/**
	 * run save catalog 
	 * @param  void 
	 * @return void 
	 */
	public function runSaveCatalog()
	{
		//ini_set ( 'memory_limit', '2048M' );
		$data = $this->_getProductData();
	   	$this->saveCsvFileLocal ( $data );
	}	
	
	/**
	 *  save csv file in local server
	 *  @param array : product data 
	 *  @return void
	 */
	public function saveCsvFileLocal( $datas,$FileHandle ){
		
		$i = 1;
		$content = "";
		$total = count($datas[0]);
	
		foreach($datas as $k=>$v)
		{
			$content .= implode("\t",$v);
			if($i != $total)
				$content .= "\n";
			$i++;
			//echo $content ;
		}
		
		/*if(fwrite($FileHandle, $content));
		{
			echo "row inserted into file..!!!<br />";
		}*/
		fwrite($FileHandle, $content);
		//$this->_row++;
		
    }

	protected function createDir( $dir ) {
		if (! is_dir ( $dir )) {
			if (! mkdir ( $dir, 0755, true )) {
				Mage::log ( "Fehler beim Erstellen des Verzeichnisses Lagerung \"$dir\"", Zend_Log::ERR );
				return false;
			}
		}
		return true;
	}    
	
	public function createCSV($limit,$part,$code)
    {
		// get Products
        $product = Mage::getModel('catalog/product');

        $attributesToSelect = array('media_gallery',
					'sku',
					'category_ids',
					'description',
					'short_description',
					'price',
					'name',
					'image',
					'small_image',
					'thumbnail',
					'url_path',
					'weight',
					'meta_keyword',);
        /*@var $products Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
		$selected_attributes = $this->getFilterableAttribute();
		$attributes = array_merge($attributesToSelect,$selected_attributes);

		$storeid = Mage::app()->getStore()->getStoreId();
        if(Mage::getStoreConfig("findologic/export/export",$storeid) == 1)
        {
             $products = $product->getCollection()
            ->addStoreFilter($this->_store($code)->getId())
			->addAttributeToFilter('status','1','inner')
			//->addAttributeToFilter('sku', array('eq' =>'Zol'))
			->setOrder ('entity_id', 'ASC')
            ->addAttributeToSelect($attributes)
             ->joinField(
                'qty',

                'cataloginventory/stock_item',
                'qty',

                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
                );
            Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($products);
        }
        else
        {
          $products = $product->getCollection()
            ->addStoreFilter($this->_store($code)->getId())
			->addAttributeToFilter('status','1','inner')
			->setOrder ('entity_id', 'ASC')
            ->addAttributeToSelect($attributes);

        }
		
            //->addAttributeToFilter('is_in_stock', array('eq' => 1));
          //  echo $products->load(1);



        /* print_r($products);
         exit;
*/

		if($part){
        	$products->getSelect()->limitPage($part, $limit);
        }


		$root = Mage::getBaseDir('base');
		$root .= "/export/";

		if( ! $this->createDir( $root ) )
		{
			return "Not create" ;
		}

		$content = "";
		$filename = self::FILE_NAME.'_'.$code.'.csv';
		//$total = count($datas);
		$pathFile = $root.$filename;
		if($part == 1)
		{
			$FileHandle = fopen($pathFile, 'w+') or die("can't open file");
			$data[] = Mage::helper('findologic')->firstLine();
			$this->saveCsvFileLocal($data,$FileHandle);
		}
		else
		{
			$FileHandle = fopen($pathFile, 'a+') or die("can't open file");
		}

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($products);
        $products->addAttributeToFilter('visibility', array('in' => array(Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH, Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH) ));
        //Mage::getSingleton('catalog/product_visibility')->addVisibleInSearchFilterToCollection($products);

		$this->product_count = $products->getSize();




		//$products->printlogquery(true);
		//echo "<pre>";var_dump($products->getSize());echo "</pre>";exit;

        /*
        using resource iterator to load the data one by one
        instead of loading all at the same time. loading all data at the same time can cause the big memory allocation.
        */
        Mage::getSingleton('core/resource_iterator')
            ->walk($products->getSelect(), array(array($this, '_getProductData')), array('product'=>$product,'filehandle'=>$FileHandle,'storecode'=>$code));

		fclose($FileHandle);

		return $this->product_count;
		/*$redirect = $this->nextRedirection($this->product_count,$part,$limit,$code);
		if($redirect != "false")
		{
			header("Location: ".$redirect);
			echo "<br />SUCCESS: Unfinished";
		}
		else
		{
			echo "<br />SUCCESS: Finished";
		}*/
    }
	
	public function getFilterableAttribute()
	{
		$return_array = array();
		$collection = Mage::getResourceModel('catalog/product_attribute_collection')
                    ->addVisibleFilter()
                    ->addFieldToFilter('is_filterable', array('eq' =>1));
		foreach($collection as $v)
		{
			if($v->getAttributeCode() != 'price')
			{
				$return_array[] = $v['attribute_code'];
				$this->select_attributes[] = $v->getAttributeCode();
			}
		}
		return $return_array;
	}
	
	#########################
	#####   BUILD DATA  #####
	#########################

	public function _getProductData($args)
	{
		//echo $this->_row++;
		//$product = $args['product'];
		$product = Mage::getModel('catalog/product');
        $this->setData('product', $product);
        
        // reset time limit
        //set_time_limit(30);		
       
        /*@var $product Mage_Catalog_Model_Product */
        $product->setData($args['row']);
		$product->setStoreId($this->_store($args['storecode'])->getId())->load($product->getId());
        //$product->load($product->getId());
		
		/*$lastPageNumber = $this->getProductCollection()->getLastPageNumber () ;
		
		$data = array();
		
		$data[] = Mage::helper('findologic')->firstLine();
		for($pageNumber = 1; $pageNumber <= $lastPageNumber; $pageNumber ++) {
			
			$productCollection = $this->getProductCollection(true,$pageNumber );
			
			foreach (  $productCollection as $product ){
				try{
				  //<b></b>echo $this->_store()->getId();
                    $product = Mage::getModel('catalog/product')
			            ->setStoreId(Mage::app()->getStore()->getStoreId())
			            ->load($product->getId());*/
						
			 	try
			 	{		
                    $line = array();
					$line[] = $this->addenclosure($product->getId());
					$line[] = $this->addenclosure($product->getSku());
					$prodname = str_replace(array("\""), " ", strip_tags($product->getName()));
					$line[] = $this->addenclosure(str_replace(array("\r\n\s", "\s", "\n", "\r", "\t"), " ", strip_tags($prodname)));
				   // $line[] = $this->addenclosure(str_replace(array("\r\n\s", "\s", "\n", "\r", "\t"), " ", strip_tags($product->getName())));
					$shortdesc = str_replace(array("\""), " ", strip_tags($product->getShortDescription()));
					$line[] = $this->addenclosure(str_replace(array("\r\n\s", "\s", "\n", "\r", "\t"), " ", strip_tags($shortdesc)));
					//$line[] = $this->addenclosure(str_replace(array("\r\n\s", "\s", "\n", "\r", "\t"), " ", strip_tags($product->getShortDescription())));
					$completedesc = ($product->getDescription() == "" ? $product->getShortDescription() : $product->getDescription()) . ";";
					$completedesc = str_replace(array("\""), " ", strip_tags($completedesc));
					$completedesc = str_replace(array("\r\n\s", "\s", "\n", "\r", "\t"), " ", strip_tags($completedesc));
					$line[] = $this->addenclosure($completedesc);
					
					$price = $this->getAllPrice($product,$args['storecode']);
					
					$groups = end($price);
					array_pop($price);
					$line = array_merge($line,$price);
					
					$line[] = $this->addenclosure($product->getProductUrl());
					$images = $this->getImageGallery( $product );
					$line[] = $this->addenclosure($images[0]);
					$line[] = $this->addenclosure($this->getFormatedAttributes( $product,$args['storecode'] ));
					$meta = str_replace(array("\""), " ", strip_tags($product->getMetaKeyword()));
					$meta = str_replace(array("\r\n\s","\s", "\n", "\r", "\t")," ",$meta); 
					$line[] = $this->addenclosure($meta);
					$line[] = $groups;
					$line[] = $this->addenclosure("");
					$totalSoldProduct = $this->getQuantityOrderedBySku($product->getSku());
					$soldQuantity = intval($totalSoldProduct);
					$line[] = $this->addenclosure((isset($soldQuantity) && $soldQuantity != '')?$soldQuantity:0);
					$line[] = $this->addenclosure($this->format_date($product->getCreatedAt()));
					$position = $this->getProductPosition($product);
					$line[] = $this->addenclosure((isset($position) && $position != '')?$position:0);

					$data[] = $line;
					//echo "<pre>";print_r($product->getId());echo "</pre>";
				}catch(Exception $e){
					Mage::log ( "ERROR > Produkt-ID={$product->getId()} : " . $e->getMessage(), Zend_log::INFO, $this->getLogFile() );
					Mage::logException($e);
					unset( $line  );
				}
			//}

		//}
		//exit;
		//return $data;
		//echo "<pre>";var_dump($data);echo "</pre>";
		$this->saveCsvFileLocal( $data, $args['filehandle'] );
		//flush();

	}

	public function addenclosure($value)
	{
		$return = '"'.str_replace('"', '\"', $value).'"';
		return $return;
	}

	public function getFormatedAttributes( $product,$code='' )
	{
		$cats = array();
		$temp_cats = array();
		$final_cat = array();
		$categoryIds = $product->getCategoryIds();
		
		$storeId = $this->_store($code)->getId();
		$categoryid = Mage::app()->getStore($storeId)->getRootCategoryId();
		$category_model = Mage::getModel('catalog/category');
		$_category = $category_model->load($categoryid);
		$all_child_categories = $category_model->getResource()->getAllChildren($_category);
		$collection = Mage::getModel('catalog/category')->getCollection()
		->setStoreId($storeId)
		//->addAttributeToFilter('parent_id',array('eq' => $categoryid))
		->addAttributeToFilter('is_active',array('eq' => 1))
		->addAttributeToSelect(array('is_active')); 
		$all_child_categories =$collection->getAllIds();
		foreach($categoryIds as $k=>$v)
		{
			if(in_array($v,$all_child_categories))
			{
				$category = Mage::getModel('catalog/category')->load($v);
					
				$pid = (Mage::app()->getStore($this->_store($code)->getId())->getRootCategoryId() == $v)?$v:$category->getParentId();
				if(in_array($pid,$all_child_categories))
				{
					$temp_cats[] = $category->getName();
					while($pid != Mage::app()->getStore($this->_store($code)->getId())->getRootCategoryId())
					{
						$category = Mage::getModel('catalog/category')->load($pid);
							$temp_cats[] = $category->getName();
						$pid = $category->getParentId();
					}
					$cats[] = $temp_cats;
					$temp_cats = array();
				}
			}
		}
		
		foreach($cats as $ck=>$cv)
		{
			if("cat=".urlencode(implode("_",array_reverse($cv))) != "cat=")
				$final_cat[] = "cat=".urlencode(implode("_",array_reverse($cv)));
		}
		
		$all = implode("&", $final_cat);
		$protype = $product->getTypeId();
		if($protype=="configurable"){  
			$product=Mage::getModel('catalog/product')->load($product->getId());
			$attrs  = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
			foreach($attrs as $attr) {  
					if($all != "")
						$all .= "&";
						
					$all .= urlencode($attr['store_label']);
					$all .= "=";
					$cnt = count($attr['values']);
					$tmpcnt = 1;
					$tmp ="";
				   foreach($attr['values'] as $key)
				   {
						if($tmpcnt==1)
						{
							$all .=urlencode($key["store_label"]);
						}
						else
						{
							$all .= "&";
							$all .= urlencode($attr['store_label']);
							$all .= "=";
							$all .= urlencode($key["store_label"]);
						}
						$tmpcnt++;
				   }
			}
		} 
		foreach($this->select_attributes as $ka=>$va)
		{
			if($product->getData($va) != "")
			{
				if($all != "")
					$all .= "&";
				
				$attr1 = $product->getResource()->getAttribute($va);

				if($attr1->getFrontendLabel() == "Manufacturer")
					$all .= urlencode('vendor');
				else
					$all .= urlencode($attr1->getStoreLabel());

					$all .= "=";

				$dropdown_option_label ='';
				// if input type for this attribute is dropdown or multi-select
				if ($attr1->usesSource()) 
				{
				    // get the option label
				    if(is_array($attr1->getSource()->getOptionText($product->getData($va))))
				    {	
						$tmpcnt = 1;
						$attri_array = $attr1->getSource()->getOptionText($product->getData($va));
						foreach($attri_array as $key=>$val)
						 {
							if($tmpcnt==1)
							{
								$dropdown_option_label .= urlencode($val);
							}
							else
							{
								$dropdown_option_label .= "&";
								$dropdown_option_label .= urlencode(Mage::helper('catalog')->__( $attr1->getFrontendLabel()));
								$dropdown_option_label .= "=";
								$dropdown_option_label .= urlencode($val);
							}
							$tmpcnt++;
						}
						$all .= $dropdown_option_label;
						//$dropdown_option_label = implode("_",$attr1->getSource()->getOptionText($product->getData($va)));
					}
					else
					{
						$dropdown_option_label = $attr1->getSource()->getOptionText($product->getData($va));
						$all .= urlencode($dropdown_option_label);
					}
				}
				else
				{
					$dropdown_option_label = $product->getData($va);
					$all .= urlencode($dropdown_option_label);
				}	
			}
		}
		
		/*$attrubtesCollection = Mage::getModel('eav/entity_attribute')
							   ->getCollection()
							   ->setEntityTypeFilter( $this->getEntityTypeId() )
							   ->addFieldToFilter( 'attribute_code',array('in'=>Mage::helper('findologic')->SelectedAttributes()))
							   ->addFieldToFilter( 'is_visible', 1 );

		foreach( $attrubtesCollection as $att )
		{
			if($product->getAttributeText($att->getAttributeCode()) != "")
			{
				if($all != "")
					$all .= "&";
				$all .= urlencode(Mage::helper('catalog')->__( $att->getFrontendLabel()));
				$all .= "=";
				$all .= urlencode($product->getAttributeText($att->getAttributeCode()));	
			}
			elseif($att->getAttributeCode() == "vendor" && $product->getVendor() != "")
			{
				if($all != "")
					$all .= "&";
				$all .= urlencode(Mage::helper('catalog')->__( $att->getFrontendLabel()));
				$all .= "=";
				$all .= urlencode($product->getVendor());	
			}
			
		}*/
		//echo $all."<br />";
		return $all;
	}
	
	
	public function getImageGallery( $product ){
		Mage::getSingleton('catalog/product_attribute_backend_media')->setAttribute( $this->getAttributeImage() )->afterLoad( $product );
		$photo = array();
		$gallery = $product->getMediaGalleryImages()->getItems();
		foreach( $gallery as $image  ){
			//$photo[] =	basename($image->getUrl());
			$photo[] =	$image->getUrl();
		}
		$counVoidFiled = 5 - count($gallery);
		for( $i=1; $i<=$counVoidFiled ; $i++ ){
			$photo[] = '';
		}
		return $photo ; 	
	}
	
	
	public function getEntityTypeId() {
		if( ! $this->_entityIypeId ){
			$this->_entityIypeId = Mage::getModel('eav/config')->getEntityType( 'catalog_product' )->getEntityTypeId();
		}
		return $this->_entityIypeId ;
	}
	
	public function getAttributeImage (  ){
		if( ! $this->_attributeImage ){
			$this->_attributeImage = Mage::getModel('eav/config')->getAttribute( $this->getEntityTypeId() , 'media_gallery');
		}
		return $this->_attributeImage ;	
	}
	
	

	public function getAllPrice($product,$code){
		
		$promo = array();
		$cust_group = array();
		$customerGroupId = 0;
		$catalogRuleProducts = Mage::getModel('catalogrule/rule_product_price')
								->getCollection()
								->addFieldToFilter('main_table.website_id',$this->_store($code)->getWebsiteId())
								->addFieldToFilter('main_table.customer_group_id',$customerGroupId)
								;
		$catalogRuleProducts->getSelect()->where('main_table.product_id = ?', $product->getId());
		
		$tableName = Mage::getModel('catalogrule/rule_product_price')->getResource()->getTable('catalogrule/rule_product');
		$catalogRuleProducts->getSelect()
			->from(array('rule_product' => $tableName), 'rule_id')
			->where ('rule_product.product_id = main_table.product_id ')
			->where('rule_product.customer_group_id = ?',$customerGroupId)
			->where('rule_product.website_id = ?',$this->_store($code)->getWebsiteId());
			
		$oldPrice = Mage::app()->getHelper('tax')->getPrice($product, $product->getPrice(), true, null, null, null, $this->_store($code)->getId() , null) ;
		$product = Mage::getModel('catalog/product')->setStoreId( $this->_store($code)->getId() )->load($product->getId());
		if(!$catalogRuleProducts->getSize()){

			if($product->tier_price)
			{
				foreach($product->tier_price as $k=>$v)
				{
					$cust_group[] = $v['cust_group'];
					$price_qty[] = $v['price_qty'];
					$price1[] = $v['price'];
				}
				$pq = explode(".",end($price_qty));
				$p = end($price1);
				
				//$promo[] = $this->addenclosure($p." for ".$pq[0]." pairs");
			}
			
			if($product->getSpecialPrice())
			{
				$promo['price'] = $this->addenclosure(Mage::app()->getHelper('tax')->getPrice($product, $product->getSpecialPrice(), true, null, null, null, $this->_store($code)->getId() , null));
				$promo['instead'] = $this->addenclosure($oldPrice);
			}
			else
			{
				$promo['price'] = $this->addenclosure($oldPrice);
				$promo['instead'] = $this->addenclosure("0.0");
			}
				
		}else{
			//$catalogRule = $catalogRuleProducts->getFirstItem();
			$newPrice = array();
			/*foreach($catalogRuleProducts as $catalogRule){
				$rule = Mage::getModel('catalogrule/rule')->load($catalogRule->getRuleId());
				$newPrice[] = Mage::app()->getHelper('tax')->getPrice($product, $catalogRule->getRulePrice(), true, null, null, null, $this->_store()->getStoreId() , null) ;
			}*/
			/*if($product->getTypeId() != "configurable")
			{*/
				//$newPrice[] = $product->getFinalPrice();
				$product = Mage::getModel('catalog/product')->setStoreId( $this->_store($code)->getId() )->load($product->getId());
				$newPrice[] = $product->getPriceModel()->getFinalPrice(null,$product);
			
				if(($product->getSpecialPrice() < min($newPrice)) && $product->getSpecialPrice() != NULL)
				{
					$promo['price'] = $this->addenclosure(Mage::app()->getHelper('tax')->getPrice($product, $product->getSpecialPrice(), true, null, null, null, $this->_store($code)->getId() , null));
					$promo['instead'] = $this->addenclosure($oldPrice);
				}
				else
				{
					$promo['price'] = $this->addenclosure(min($newPrice));
					if($oldPrice == min($newPrice))
						$promo['instead'] = $this->addenclosure("0.0");
					else
						$promo['instead'] = $this->addenclosure($oldPrice);
				}
			//}	
			
		}	
		if($product->getTypeId() == "configurable")
		{

			$max = array();
			$pr = substr(substr($promo['price'], 1), 0, -1);
			//echo "<pre>";var_dump(get_class(Mage::getSingleton('catalog/product_type')->factory($product)));echo "</pre>";
			
			$attrs  = Mage::getSingleton('catalog/product_type')->factory($product)->getConfigurableAttributesAsArray($product);
			if($attrs)
			{
				foreach($attrs as $attr) {
			        $options    = $attr['values'];
			        foreach($options as $option) 
					{
				    	if($option['pricing_value'] != "")
						{
							if($option['is_percent'])
							{
								$round = round(($pr * $option['pricing_value'])/100);
								$max[] = $pr + $round;
							}
							else
							{
								$max[] = $pr + $option['pricing_value'];
							}
						}
						else
						{
							$max[] = "0.0";
						}
				    }
				}
			}
			$promo['maxprice'] = $this->addenclosure(max($max));
		}
		else if($product->getTypeId() == "bundle")
		{
			if($product->getPrice() > 0)
			{
				$promo['price'] = $this->addenclosure($product->getPrice());
				$promo['maxprice'] = $this->addenclosure($product->getPrice());
			}
			else
			{
				list($_minimalPrice, $_maximalPrice) = $product->getPriceModel()->getPrices($product);
				$promo['price'] = $this->addenclosure($_minimalPrice);
				$promo['maxprice'] = $this->addenclosure($_maximalPrice);
			}
		}
		else
		{
			$promo['maxprice'] = $this->addenclosure("0.0");
		}
		$promo[] = $this->addenclosure(implode(",",$cust_group));
		return $promo;
	}


	public function format_date($date)
	{
		return strtotime($date);
	}

	//////RETURN THE POSTION OF PRODUCT IN CATEGORY

	public function getProductPosition($product)
	{
		$categoryIds = $product->getCategoryIds();
		$category = null;
		foreach($categoryIds as $categoryId) {
	    	$category = Mage::getModel('catalog/category')->load($categoryId);
			if(is_object($category))
				break;
		}
		if(is_object($category))
		{
			if(Mage::getStoreConfig('catalog/frontend/flat_catalog_category') == "1")
			{
				return 0;
			}
			else
			{
			$positions = $category->getProductsPosition();
	        $productId = $product->getId();
	        return $positions[$productId];
			}
		}
		else
			return 0;
	}

	////// GET THE QUANTITY OF SOLD PRODUCT BY PRODUCT SKU
	public function getQuantityOrderedBySku($sku)
	{
	    try {
	        $_product = Mage::getResourceModel('reports/product_collection')
	            ->addOrderedQty()
	            ->addAttributeToFilter('sku', $sku)
	            ->setOrder('ordered_qty', 'desc')
	            ->getFirstItem();

	        if (!$_product) {
	            throw new Exception('No product matches the given SKU');
	        }

	        return $_product->getOrderedQty();
	    }
	    catch (Exception $e) {
	        return 0;
	    }
	}

}