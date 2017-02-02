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

abstract class Dixeno_Findologic_Block_Layer_Filter_Abstract extends Mage_Core_Block_Template
{
	protected $_items = array();
	
    /**
     * Initialize filter template
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('findologic/layer/filter.phtml');
    }	
	
    /**
     * get Items
     * 
     * @return array
     */
    public function getItems(){
    	return $this->_items;
    }
    
    /**
     * add Item
     * 
     * @param array $item
     * @return Dixeno_Findologic_Block_Layer_Filter_Abstract
     */
	public function addItem($item) {
		
		//$this->_items[] = new Varien_Object($item);
		$this->_items[] = $item;
		return $this;
	}
	
	/**
	 * Filter Init 
	 */
	public function init() {}
	
    /**
     * Get url for remove item from filter
     *
     * @return string
     */
    public function getRemoveUrl()
    {
        $query = array($this->getFilter()->getRequestVar()=>$this->getFilter()->getResetValue());
        $params['_current']     = true;
        $params['_use_rewrite'] = true;
        $params['_query']       = $query;
        $params['_escape']      = true;
        return Mage::getUrl('*/*/*', $params);
    }
	
	
    /**
     * Retrieve filter value for Clear All Items filter state
     *
     * @return mixed
     */
    public function getCleanValue()
    {
        return null;
    }	
	
    /**
     * get Filter
     * 
     * @return Dixeno_Findologic_Block_Layer_Filter_Abstract
     */
	public function getFilter(){
		return $this;
	}
	
	/**
	 * has Items
	 * 
	 * @return boolean
	 */
	public function hasItems(){
		return count($this->_items) ? true : false;
	}
	
	/**
	 * is Filter active
	 * 
	 * @return boolean
	 */
	public function isActive(){
		return $this->getRequest()->getParam($this->getRequestVar()) ? true : false;
	}
	
	public function getCurrentValue(){
		return $this->getRequest()->getParam($this->getRequestVar());
	}
	
    /**
     * Retrieve block html
     *
     * @return string
     */
    public function getHtml()
    {
        parent::_toHtml();
    }	
	
}