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
* @version $Id: List.php 500 2010-08-16 16:43:19Z weller $
* @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
*/

class Dixeno_Findologic_Block_Result_List extends Mage_Catalog_Block_Product_List
{


	 /**
     * Need use as _prepareLayout - but problem in declaring collection from
     * another block (was problem with search result)
     */
    protected function _beforeToHtml()
    {
        if(Mage::helper('findologic')->isAlive()){
    	
	        $availableOrders = array('relevance' => $this->__('Relevance'));
	
	        $this->setAvailableOrders($availableOrders)
	            ->setDefaultDirection('desc')
	            ->setSortBy('relevance');
    	} 
        return parent::_beforeToHtml();
    }
       

}
