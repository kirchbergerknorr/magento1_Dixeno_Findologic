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
* @version $Id: Autocomplete.php 336 2010-05-12 16:15:49Z weller $
* @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
*/

class Dixeno_Findologic_Model_Layer extends Mage_CatalogSearch_Model_Layer
{
    const XML_PATH_DISPLAY_LAYER_COUNT    = 'catalog/search/use_layered_navigation_count';

    /**
     * Get current layer product collection
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    public function getProductCollection()
    {
        if(Mage::helper('findologic')->isAlive()){
        	
            if(!isset($this->_productCollections['findologic'])){
				$collection = Mage::getResourceModel('findologic/fulltext_collection');  
	        	$this->prepareProductCollection($collection);
	        	$this->_productCollections['findologic'] = $collection;
            }

            return $this->_productCollections['findologic'];
        }

        return parent::getProductCollection();
    }
    
    /**
     * Initialize product collection
     *
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $collection
     * @return Mage_Catalog_Model_Layer
     */
    public function prepareProductCollection($collection)
    {
    	if(!Mage::helper('findologic')->isAlive()){
    		return parent::prepareProductCollection($collection);
    	}
    	
        $attributes = Mage::getSingleton('catalog/config')
            ->getProductAttributes();
        $collection->addAttributeToSelect($attributes)
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            //->addStoreFilter()
            ;
        
        return $this;
    }    

    /**
     * Adding product count to categories collection
     *
     * @param   Mage_Eav_Model_Entity_Collection_Abstract $categoryCollection
     * @return  Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function addCountToCategories($categoryCollection)
    {
    	if(Mage::helper('findologic')->isAlive()){
        	return $this;
    	}
    	return parent::addCountToCategories($categoryCollection);
    }    


}
