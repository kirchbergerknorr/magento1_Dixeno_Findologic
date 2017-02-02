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
* @version $Id: Layer.php 500 2010-08-16 16:43:19Z weller $
* @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
*/

class Dixeno_Findologic_Block_Layer extends Mage_CatalogSearch_Block_Layer
{

	protected $_filter = null;
	
    /**
     * Retrieve path to template used for generating block's output.
     *
     * @return string
     */
    public function getTemplate()
    {
    	if(Mage::helper('findologic')->isAlive()){
        	return 'findologic/layer.phtml';
    	}
    	return $this->_template;
    }

    /**
     * Prepare child blocks
     *
     * @return Mage_Catalog_Block_Layer_View
     */
    protected function _prepareLayout()
    {
    	if(!Mage::helper('findologic')->isAlive()){
    		return parent::_prepareLayout();
    	}
    	
        $stateBlock = $this->getLayout()->createBlock('catalog/layer_state')
            ->setLayer($this->getLayer());
            
        #$this->getLayout()->getBlock('search_result_list')
        $this->getLayout()->createBlock('catalog/product_list', 'search_result_list')
            ->setAvailableOrders(array('relevance' => $this->__('Relevance')))
            ->setDefaultDirection('desc')
            ->setSortBy('relevance');   
                	            
            
      
        $this->setChild('layer_state', $stateBlock);    	
    }    
    
   
    
    /**
     * get Findologic Search Result
     * 
     * @return DOMDocument
     */
    protected function _getFindologicSearchResult(){
    	return $this->getLayer()->getProductCollection()->getFindologicSearchResult();
    }
        
	
    protected function _getBlockNameByFilterName($filterName) {

    	$block = 'findologic/layer_filter_'.$filterName;
    	
		if (strpos ( $block, '/' ) !== false) {
			if (! $block = Mage::getConfig ()->getBlockClassName ( $block )) {
				$block ='findologic/layer_filter_attribute';
			}
		}
		if (!class_exists ( $block, false ) && !mageFindClassFile ( $block )) {
			$block ='findologic/layer_filter_attribute';
		}
   	
    	return $block;
    }
    
    
    public function getFilters(){
    	
        if(!Mage::helper('findologic')->isAlive()){
    		return parent::getFilters();
    	}    	
    	
    	if($this->_filter == null){
    	
    		$this->_filter = array();
	    	$filters = $this->_getFindologicSearchResult()->getElementsByTagName ('filter');
	    	foreach($filters as $filter){

	    		//$code = $filter->getAttribute('name');
				$codes = $filter->getElementsByTagName( "name" );
				$selects = $filter->getElementsByTagName( "select" );
			  	$code = $codes->item(0)->nodeValue;
				
	    		$block = $this->_getBlockNameByFilterName($code);
	    		
	    		$blockObject = $this->getLayout()->createBlock($block);
	    		
	    		$blockObject->addData(array(
	    			'code' => $code,
	    			'request_var' => $code,
	    			'type' => $selects->item(0)->nodeValue,
	    			'name' => $this->__($code),
	    			'label' => $this->__($code)
	    		));
	    		
                $blockObject
                    ->setLayer($this->getLayer())
                    ->init();	    		
	    		
	    		foreach($filter->getElementsByTagName('item') as $item){

	    			$itemObject = new Varien_Object();		
					$name = $item->getElementsByTagName('name');
					$frequency = $item->getElementsByTagName('frequency');

					//var_dump($name->item(0)->nodeValue."-".$weight->item(0)->nodeValue);
	    			//foreach($item->attributes as $attribute){
		    		$itemObject->setData("name", $name->item(0)->nodeValue);
					if($frequency->length > 0)
					{
						$itemObject->setData("count", $frequency->item(0)->nodeValue);
					}
	    			//}
					
					/*foreach($item->attributes as $attribute){
		    			 $itemObject->setData($attribute->name, $attribute->value);
	    			}*/
					
					/*if($code = "cat")
					{
	    				$category_id = Mage::getModel('catalog/category')
									->getCollection()
									->addAttributeToSelect('*')
									->addFieldToFilter( 'name',array('eq'=>$itemObject->getName()))
									->getData();
					}
					
					if($category_id[0]['entity_id'])
					{
		    			$query = array(
	            			$blockObject->getRequestVar()=>$category_id[0]['entity_id'],
	            			Mage::getBlockSingleton('page/html_pager')->getPageVarName() => null // exclude current page from urls
	        			);
					}
					else
					{*/
						if($blockObject->getRequestVar() == 'price')
						{
							$query = array(
		            			$blockObject->getRequestVar()=>$itemObject->getName(),
		            			Mage::getBlockSingleton('page/html_pager')->getPageVarName() => null // exclude current page from urls
		        			);
						}
						else
						{
							$query = array(
		            			$blockObject->getRequestVar().'[]'=>$itemObject->getName(),
		            			Mage::getBlockSingleton('page/html_pager')->getPageVarName() => null // exclude current page from urls
		        			);
						}
					//}
					$seturl = preg_replace('/%5B[0-9]%5D/','%5B%5D',Mage::getUrl('*/*/*', array('_current'=>true, '_use_rewrite'=>true, '_query'=>$query)));
        			$itemObject->setUrl($seturl);
   			
        			if($blockObject->isActive()){

        				$blockObject->setLabel($this->htmlEscape($blockObject->getCurrentValue()));
        			}
        			//echo "<pre>";var_dump($itemObject->getData());echo "</pre>";exit;
    				$blockObject->addItem($itemObject);
	    		}
     
    	        if($blockObject->isActive()){
    	        	$this->getLayer()->getState()->addFilter($blockObject);
        		}                    
	            //echo "<pre>";var_dump($blockObject->getData());echo "</pre>";exit;
	        	$this->_filter[] = $blockObject;            
	    	}
    	}
    	
    	return $this->_filter;
    }
	
    /**
     * Check availability display layer options
     *
     * @return bool
     */
    public function canShowOptions()
    {
    	if(!Mage::helper('findologic')->isAlive()){
    		return parent::canShowOptions();
    	}
    	
        return true;
    }    
	
	public function getActiveFilters()
	{
		$active = array();
		$cur_url = explode("?",$this->helper('core/url')->getCurrentUrl());
		$cur_url_base = $cur_url[0];
		$i=0;
		
		foreach(Mage::app()->getRequest()->getParams() as $key=>$val)
		{
			if($key != "q" && $key != "mode" && $key != "p" && $key != "limit" && $key != "dir" && $key != "order" && $key != "___store" && $key != "___from_store")
			{
				$key = str_replace("_","+",$key);
				if($key == "price")
				{
					$active['data'][$key][] = $val;
					$val = str_replace(" ","+",$val);
					
					$replace = $key."=".$val."&";
					if(strpos($cur_url[1],$replace) !== false)
						$temp_url1 = str_replace($replace,"",$cur_url[1]);
					else
						$temp_url1 = str_replace($key."=".$val,"",$cur_url[1]);
						
					$active['url'][$key."_url"][] = $cur_url_base."?".$temp_url1;
				}
				else
				{
					if(is_array($val))
					{
						foreach($val as $k_val=>$v_val)
						{
							$active['data'][urldecode($key)][] = $v_val;

							if(strpos($cur_url[1],"[") !== false)
							{
								$replace = $key."[]=".urlencode($v_val)."&";
								if(strpos($cur_url[1],$replace) !== false)
									$temp_url1 = str_replace($replace,"",$cur_url[1]);
								else
									$temp_url1 = str_replace($key."[]=".urlencode($v_val),"",$cur_url[1]);
							}
							elseif(strpos($cur_url[1],"%5B") !== false)
							{
								$replace = $key."%5B%5D=".urlencode($v_val)."&";
								if(strpos($cur_url[1],$replace) !== false)
									$temp_url1 = str_replace($replace,"",$cur_url[1]);
								else
									$temp_url1 = str_replace($key."%5B%5D=".urlencode($v_val),"",$cur_url[1]);
							}

							$active['url'][$key."_url"][] = $cur_url_base."?".$temp_url1;
						}
					}
				}
				$i++;
			}
			else
			{
				if($i)
					$active['url']["clean_url"] = $cur_url_base."?".$key."=".$val;
			}
		}
		//echo "<pre>";var_dump($active);echo "</pre>";
		return $active;
	}
    


}
