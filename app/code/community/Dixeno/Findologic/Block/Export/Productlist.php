<?php
/*                                                                       *
* This script is part of the findologic search project			         *
*                                                                        *
* findologic is free software; you can redistribute it and/or modify  *
* it under the terms of the GNU General Public License version 2 as      *
* published by the Free Software Foundation.                             *
*                                                                        *
* This script is distributed in the hope that it will be useful, but     *
* WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
* TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
* Public License for more details.                                       *
*                                                                        *
* @version $Id: Productlist.php 500 2010-08-16 16:43:19Z weller $
* @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
*/


class Dixeno_Findologic_Block_Export_Productlist extends Mage_Core_Block_Template
{

	protected $_attributeCollection = null;
	
	/*@var Dixeno_Log_Model_Log */
	protected $_log = null;
	
	/**
	 * Class Constuctor
	 * Cache Settings
	 */
	public function __construct(){
		
		// set Cache to one Hour
		$this->setCacheLifetime(3600);   
        $this->setCacheKey(
        	$this->getNameInLayout().
        	Mage::app()->getStore()->getId().
        	$this->getRequest()->getParam('part', 1).
        	$this->getRequest()->getParam('limit', 1000)
        );     
		parent::__construct();
	}
	


    /**
     * generates the Output
     *
     * @return string
     */
    protected function _toHtml()
    { 
   	
		// get Products
        $product = Mage::getModel('catalog/product');
        
        /*@var $products Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
        $products = $product->getCollection()
            ->addStoreFilter()
            ->addAttributeToSort('news_from_date','desc')
            ->addAttributeToSelect(array('name', 'short_description', 'price', 'image'), 'inner');
            
        // split Export in Parts 
        if($this->getRequest()->getParam('part')){    
        	$products->getSelect()->limitPage($this->getRequest()->getParam('part', 1), $this->getRequest()->getParam('limit', 1000));
        }
        
        // generate Headline
        $headline = array('id', 'ordernumber', 'name', 'summary', 'description', 'price', 'url', 'image', 'company', 'categories', 'keywords', 'attributes');

		echo implode('|', $headline)."\n";
        
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($products);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInSearchFilterToCollection($products);

        /*
        using resource iterator to load the data one by one
        instead of loading all at the same time. loading all data at the same time can cause the big memory allocation.
        */
        Mage::getSingleton('core/resource_iterator')
            ->walk($products->getSelect(), array(array($this, 'addNewItemCsvCallback')), array('product'=>$product));
               
    }


    
    /**
     * Product iterator callback function
     * add detailinformations to products
     *
     * @param array $args
     */
    public function addNewItemCsvCallback($args)
    {
    	$product = $args['product'];
        $this->setData('product', $product);
        
        // reset time limit
        //set_time_limit(30);		
       
        /*@var $product Mage_Catalog_Model_Product */
        $product->setData($args['row']);
        $product->load($product->getId());

        // add Line
        $data = array(
        	$product->getId(),
        	$product->getSku(),
        	$product->getName(),
        	$product->getShortDescription(),
        	$product->getDescription(),
        	$this->getProductPrice($product),
        	$product->getProductUrl(), 
        	(string) $this->helper('catalog/image')->init($product, 'image'),
        	$product->getAttributeText('manufacturer'),
        	$this->_getCategoryNames($product),
        	$this->_getTags(),
			http_build_query($this->getProductAttributes())
		);
		
		
		$output = $this->_addCsvRow($data);
		
		echo $output;
		flush();

    }
    
    /**
     * get Tags from the current product
     * 
     * @return string
     */
    protected function _getTags(){
    	   	
    	$tagCollection = Mage::getResourceModel('tag/tag_collection')
    						->addProductFilter($this->getProduct()->getId())
			                ->addStoreFilter(Mage::app()->getStore()->getId())
			                ->setActiveFilter()    	
			                ->joinRel()				
    						//->getSelect()->__toString();
    						->load();
    	$tags = array();
    	foreach($tagCollection as $tag){
    		$tags[] = $tag->getName();
    	}	
    	
    	return implode(' ', $tags);
    }
    
    /**
     * add CSV Row
     * 
     * @param array $data
     */
    protected function _addCsvRow($data){
    	
    	foreach($data as &$item){
    		$item = str_replace(array("\r", "\n", "|"), ' ', addcslashes(strip_tags($item), '"'));
    	}

    	return '"'.implode('"|"', $data).'"'."\n"; 
    }
    
    /**
     * get Category Names
     * 
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    protected function _getCategoryNames($product){
    	
    	$data = array();
    	$categories = $product->getCategoryCollection()->addAttributeToSelect('name')->load();
    	foreach($categories as $category){
    		
    		$parentCategories = $category->getParentCategories();
    		foreach($parentCategories as $parentCategory){
    			$data[] = $parentCategory->getName();
    		}
    		
    		$data[] = $category->getName();
    	}
    	return implode('_', $data);
    }
    
    
    /**
     * get current Product
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
    	return $this->getData('product');
    }   
    

    /**
     * get current Category
     *
     * @return unknown
     */
    public function getCategory()
    {
    	return $this->getData('category');
    }     
      

	
	/**
	 * get final Product Price
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @return float
	 */
	protected function getProductPrice(Mage_Catalog_Model_Product $product){
		

		return round($product->getPrice(),2);
	}
	
	
	/**
	 * get Product Attributes
	 * 
	 * @return array
	 */
    protected function getProductAttributes(){
		
		$_attributes = array();
    	
		if($_additional = $this->getAdditionalData()){

			foreach ($_additional as $_data){
				
				$_attributes[$this->__($_data['code'])] = $this->helper('catalog/output')->productAttribute($this->getProduct(), $_data['value'], $_data['code']);
			}
		}   	
    	return $_attributes;
    }
    
    /**
     * $excludeAttr is optional array of attribute codes to
     * exclude them from additional data array
     *
     * @param array $excludeAttr
     * @return array
     */
    public function getAdditionalData(array $excludeAttr = array())
    {
        $data = array();
        $product = $this->getProduct();
        $attributes = $product->getAttributes();
        foreach ($attributes as $attribute) {
//            if ($attribute->getIsVisibleOnFront() && $attribute->getIsUserDefined() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
            if ($attribute->getIsVisibleOnFront() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {

                $value = $attribute->getFrontend()->getValue($product);

                // TODO this is temporary skipping eco taxes
                if (is_string($value)) {
                    if (strlen($value) && $product->hasData($attribute->getAttributeCode())) {
                        if ($attribute->getFrontendInput() == 'price') {
                            $value = Mage::app()->getStore()->convertPrice($value,true);
                        } elseif (!$attribute->getIsHtmlAllowedOnFront()) {
                            $value = $this->htmlEscape($value);
                        }
                        if(in_array($attribute->getAttributeCode(), array('in_depth'))){
                        	continue;
                        }
                        
                        $data[$attribute->getAttributeCode()] = array(
                           // 'label' => $attribute->getFrontend()->getLabel(),
                           'value' => $value,
                           'code'  => $attribute->getAttributeCode()
                        );
                    }
                }
            }
        }
        return $data;
    }    

	
	
    
}