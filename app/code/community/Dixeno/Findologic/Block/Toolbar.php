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
* @version $Id: Autocomplete.php 500 2010-08-16 16:43:19Z weller $
* @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
*/

class Dixeno_Findologic_Block_Toolbar extends Mage_Catalog_Block_Product_List_Toolbar
{
    public function getCurrentOrder()
    {
        $order = $this->_getData('_current_grid_order');
        if ($order) {
            return $order;
        }

        $orders = $this->getAvailableOrders();
        $defaultOrder = $this->_orderField;

        if (!isset($orders[$defaultOrder])) {
            $keys = array_keys($orders);
            $defaultOrder = $keys[0];
        }

        $order = $this->getRequest()->getParam($this->getOrderVarName());
		
		if(!isset($orders[$order]))
		{
			if($order == "dateadded")
				$orders[$order] = "Date Added";
			elseif($order == "salesfrequency")
				$orders[$order] = "Sales";
		}
		
        if ($order && isset($orders[$order])) {
            if ($order == $defaultOrder) {
                Mage::getSingleton('catalog/session')->unsSortOrder();
            } else {
                $this->_memorizeParam('sort_order', $order);
            }
        } else {
            $order = Mage::getSingleton('catalog/session')->getSortOrder();
        }
        // validate session value
        if (!$order || !isset($orders[$order])) {
            $order = $defaultOrder;
        }
        $this->setData('_current_grid_order', $order);
        return $order;
    }
	/**
	* get the collection and set the page limit
	*
	*/
	public function getPageInfo()
	{
		$limit = (int)$this->getLimit();
        $this->_collection->setPageSize($limit);
	}
}
