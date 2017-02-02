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
* @version $Id: IndexController.php 369 2010-05-29 15:29:22Z weller $
* @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
*/

class Dixeno_Findologic_CsvController extends Mage_Core_Controller_Front_Action {

    const XML_STATUS_PATH			= 'findologic/export/enabled';		
    const XML_AUTH_USERNAME_PATH	= 'findologic/export/username';		
    const XML_AUTH_PASSWORD_PATH	= 'findologic/export/password';		
	
	public function createcsvAction(){
		$this->getResponse()->setHeader('Content-type', 'text/plain; charset=UTF-8');
		//$this->getResponse()->setHeader('Content-type', 'text/csv; charset=UTF-8');
        $this->loadLayout(false);
        $this->renderLayout();			
	}
	
	public function createAction()
	{
		$part = $this->getRequest()->getParam('part',1);
		$limit = $this->getRequest()->getParam('limit',1000);
		$code = $this->getRequest()->getParam('store_code');

		if(Mage::getModel('core/store')->load($code)->getId())
		{
			$product_count = Mage::getModel('findologic/exportCatalog')->createCSV($limit,$part,$code);  
		
			$redirect = $this->nextRedirection($product_count,$part,$limit,$code);
			if($redirect != "false")
			{
				//header("Location: ".$redirect);
				//$this->_redirect($redirect);
				Mage::app()->getFrontController()->getResponse()->setRedirect($redirect);
				return;
				echo "<br />SUCCESS: Unfinished";
			}
			else
			{
				echo "<br />SUCCESS: Finished";
			}
		}
		else
		{
			die("Store Code(<b>".$code."</b>) that you have mentioned in the URL was not found.");
		}
	}
	
	public function nextRedirection($count,$part,$limit,$code)
	{
		if(!$part)
			$exported = 1*$limit;
		else
			$exported = $part*$limit;
			
		if($exported < $count)
		{
			return Mage::getBaseUrl()."findologic/csv/create/part/".($part+1)."/limit/".$limit."/store_code/".$code."/";
		}
		else
		{
			return "false";
		}
	}
	
	
}

