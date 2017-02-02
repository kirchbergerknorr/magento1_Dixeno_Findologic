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
* @version $Id: Collection.php 500 2010-08-16 16:43:19Z weller $
* @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
*/


class Dixeno_Findologic_Model_Mysql4_Fulltext_Collection
    extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
{
	
	protected $_findologicSearchResult = null;
	
    /**
     * Retrieve query model object
     *
     * @return Mage_CatalogSearch_Model_Query
     */
    protected function _getQuery()
    {
        return Mage::helper('catalogsearch')->getQuery();
    }
    
    /**
     * Get collection size
     *
     * @return int
     */
    public function getSize()
    {
        $result = $this->getFindologicSearchResult();
		$xpath = new DOMXpath($result);
		$size = intval($xpath->query("/searchResult/results/count")->item(0)->nodeValue);
		return $size;
    }
    
    /**
     * Returns the count of the first item returned
     * 
     * This returns the offset used for pagination. The first page always has
     * no offset. Following pages have an offset of the page size.
     * 
     * If the offset is larger then the result count we are on a page not
     * availible and want to run the request again.
     * 
     * @retun int
     */
    public function getFirst()
    {
        $result = $this->getFindologicSearchResult();
        $xpath = new DOMXpath($result);
        $first = intval($xpath->query("/searchResult/query/limit")->item(0)->getAttribute('first'));
        return $first;
    }
    
    /**
     * Retrieve is flat enabled flag
     * Return alvays false if magento run admin
     *
     * @return bool
     */
    public function isEnabledFlat()
    {
    	if(Mage::helper('findologic')->isAlive()){
    		return false;
    	}
    	
        return parent::isEnabledFlat();
    }    
    
    /**
     * get Toolbar Block
     * 
     * @return Mage_Catalog_Block_Product_List_Toolbar
     */
    protected function _getToolbarBlock(){
    	
    	$mainBlock = Mage::app()->getLayout()->getBlock('search.result');
    	if($mainBlock instanceof Mage_CatalogSearch_Block_Result){
    		$toolbarBlock = $mainBlock->getListBlock()->getToolbarBlock();
    	}else{
    		$toolbarBlock = Mage::app()->getLayout()->createBlock('catalog/product_list_toolbar');
    	}
  	
    	return $toolbarBlock;
    }
 
    
    public function getFindologicSearchResult()
    {
    	if (is_null($this->_findologicSearchResult)) {
	        $params = array();
			$order = "";
			$dir = "";
			$flag = 0;
	        $params['first'] = $this->_getToolbarBlock()->getLimit() * ($this->_getToolbarBlock()->getCurrentPage() - 1);
	        $params['count'] = $this->_getToolbarBlock()->getLimit();
	        
			//echo "<pre>";var_dump(Mage::app()->getRequest()->getParams());echo "</pre>";
			
	        foreach (Mage::app()->getRequest()->getParams() as $key => $value) {
	        	/*if(substr($key, 0, 3) != 'fl_'){
	        		continue;
	        	}*/
	        	if($key != "q" && $key != "mode" && $key != "p" && $key != "limit" && $key != "dir" && $key != "order" && $key != "___store" && $key != "___from_store")
				{
		        	switch($key) {
		        		case 'price':
		        			$value = explode(' - ', $value);
		        			$params["attrib[$key][min]"] = $value[0];
		        			$params["attrib[$key][max]"] = $value[1];
		        			break;
							
		        		default:
							$key = str_replace("_","+",$key);
							if(is_array($value))
							{
								foreach($value as $kv=>$vv)
								{
									$params["attrib[$key][]"] = $vv;
								}
							}
		        			break;
		        	}    	
				}
				else
				{
					if($key == "order")
					{
						if($value == 'relevance')
							$order = 'rank';
						elseif($value == 'price')
							$order = 'price';
						elseif($value == 'name')
							$order = 'label';
						elseif($value == 'dateadded')
							$order = 'dateadded';
						elseif($value == 'salesfrequency')
							$order = 'salesfrequency';
						$flag = 1;
					}
					elseif($key == "dir")
					{
						$dir = strtoupper($value);
						$flag = 1;
					}
				}
	        }
			
			if($flag)
				$params["order"] = $order." ".$dir;
     		
	        $this->_findologicSearchResult = Mage::helper('findologic')->search($this->_getQuery()->getQueryText(), $params);
	        
	        if ($this->getFirst() > $this->getSize()) {
	            // we requested a page offset and no result was returned, so
	            // we're on a non-existing page. to fix this we run the request
	            // again - this time without an offset.
	            $params['first'] = 0;
                $this->_findologicSearchResult = Mage::helper('findologic')->search($this->_getQuery()->getQueryText(), $params);
	        }
    	}
    	return $this->_findologicSearchResult;
    }
    
    
    /**
     * Load entities records into items
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    /**
     * Load entities records into items
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function _loadEntities($printQuery = false, $logQuery = false)
    {
    	
    	$result = $this->getFindologicSearchResult();
		$xpath = new DOMXpath($result);
		$products = $xpath->query("/searchResult/products/product");    	
    	
    	$productIds = array();
    	
        foreach ($products as $product){
        	$productIds[] = $product->getAttribute('id');
        }

        if (!empty($productIds)) {
	
	        $this->addIdFilter($productIds);
	        $this->_pageSize = null;
	        
	        $entity = $this->getEntity();
	
	        $this->printLogQuery($printQuery, $logQuery);
	
	        try {
	            $rows = $this->_fetchAll($this->getSelect());
	        } catch (Exception $e) {
	            Mage::printException($e, $this->getSelect());
	            $this->printLogQuery(true, true, $this->getSelect());
	            throw $e;
	        }
	
	        $items = array();
	        foreach ($rows as $v) {        	
				$items[$v[$this->getEntity()->getIdFieldName()]] = $v;
	        }

	        foreach ($productIds as $productId){
	        	
	        	if(empty($items[$productId])){
	        		continue;
	        	}
	        	$v = $items[$productId];
	            $object = $this->getNewEmptyItem()
	                ->setData($v);
  
	            $this->addItem($object);
	            if (isset($this->_itemsById[$object->getId()])) {
	                $this->_itemsById[$object->getId()][] = $object;
	            }
	            else {
	                $this->_itemsById[$object->getId()] = array($object);
	            }        	
	        }
	        
        }else{
            // the app tries to load all products if no filter is set
            // this should never happen
            Mage::log(__METHOD__ . '::' . __CLASS__ . ' No product ids defined for filter. The app is likely to run out of memory if we proceed here.');
            // inject an invalid product id to fake an empty result
        	
        }
        return $this;
    }      

    /**
     * Add search query filter
     *
     * @param   Mage_CatalogSearch_Model_Query $query
     * @return  Mage_CatalogSearch_Model_Mysql4_Search_Collection
     */
    public function addSearchFilter($query)
    {
        return $this;
    }

    /**
     * Set Order field
     *
     * @param string $attribute
     * @param string $dir
     * @return Mage_CatalogSearch_Model_Mysql4_Fulltext_Collection
     */
    public function setOrder($attribute, $dir='desc')
    {
        return $this;
    }
}
