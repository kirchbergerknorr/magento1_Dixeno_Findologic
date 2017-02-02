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

class Dixeno_Findologic_IndexController extends Mage_Core_Controller_Front_Action {
	
    const XML_STATUS_PATH			= 'findologic/export/enabled';		
    const XML_AUTH_USERNAME_PATH	= 'findologic/export/username';		
    const XML_AUTH_PASSWORD_PATH	= 'findologic/export/password';		
	
    /**
     * Password Protection Exporter
     */
    public function preDispatch()
    {
    	$storeid = Mage::app()->getStore()->getStoreId();
		// check if export is enabled
    	if(!Mage::getStoreConfig(self::XML_STATUS_PATH,$storeid)){	
    		$this->setFlag('', self::FLAG_NO_DISPATCH, true);	
    	}
    	
    	//$username = Mage::getStoreConfig(self::XML_AUTH_USERNAME_PATH);
    	
    	$password = Mage::getStoreConfig(self::XML_AUTH_PASSWORD_PATH,$storeid);
    	
    	if(!empty($password) 
    		&& $password != $this->getRequest()->getParam('pass')){
    			
    		$this->setFlag('', self::FLAG_NO_DISPATCH, true);
    	}
    	
    	// Authentication
    	/* 
    	if(!empty($username)
    		&& !empty($password)
    		&& ($this->getRequest()->getServer('PHP_AUTH_USER') != $username
    		or $this->getRequest()->getServer('PHP_AUTH_PW') != $password)){
    	
	    	$this->getResponse()->setHeader('status', 'Unauthorized', true);
	    	$this->getResponse()->setHeader('WWW-authenticate', 'basic realm="FINDOLOGIC Interface"', true);
	    	$this->getResponse()->sendHeaders();
	    	$this->setFlag('', self::FLAG_NO_DISPATCH, true);
    	}
    	*/
    	
        return parent::preDispatch();
    }	
	
	public function indexAction(){
		
		$this->_forward('productlist');
	}
	
	
	public function productlistAction(){
		$this->getResponse()->setHeader('Content-type', 'text/plain; charset=UTF-8');
		//$this->getResponse()->setHeader('Content-type', 'text/csv; charset=UTF-8');
        $this->loadLayout(false);
        $this->renderLayout();			
	}
	
	
}

