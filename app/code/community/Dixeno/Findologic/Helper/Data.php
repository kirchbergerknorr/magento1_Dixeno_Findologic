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
* @version $Id: Data.php 500 2010-08-16 16:43:19Z weller $
* @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
*/

// Doku: https://secure.findologic.com/dokuwiki/doku.php?id=fl:search_request_2_0

class Dixeno_Findologic_Helper_Data extends Mage_Core_Helper_Abstract
{
	const XML_URL_PATH 				= 'findologic/config/url';
	const XML_URL_SEARCH_PATH 		= 'findologic/config/url_search';
	const XML_URL_AUTOCOMPLETE_PATH = 'findologic/config/url_autocomplete';
	const XML_URL_ALIVECHECK_PATH 	= 'findologic/config/url_alivetest';
    const XML_SHOP_KEY_PATH			= 'findologic/config/shopkey';	
    const XML_LOGOS_PATH 			= 'system/findologic/logos';		
    const XML_LOGO_PATH 			= 'findologic/config/logo';
    const XML_TIMEOUT_PATH 			= 'findologic/config/timeout';
	
    const URL_TYPE_MAIN				= null;
    const URL_TYPE_AUTOCOMPLETE		= 'autocomplete';
    const URL_TYPE_LIVECHECK		= 'livecheck';
    const URL_TYPE_SEARCH			= 'search';
    
    protected $_isAlive = null;
    protected $_aliveMessage = null;
    
    /**
     * get URL
     * 
     * @param string $type
     * @return string
     */
    public function getUrl($type = null){
    	
    	$url = null;
		$storeid = Mage::app()->getStore()->getStoreId();
    	$baseUrl = 'http://'.Mage::getStoreConfig(self::XML_URL_PATH,$storeid);
    	
    	switch($type) {
    		case self::URL_TYPE_AUTOCOMPLETE:
    			$url = $baseUrl.Mage::getStoreConfig(self::XML_URL_AUTOCOMPLETE_PATH);
    			break;
    			
    		case self::URL_TYPE_LIVECHECK:
    			$url = $baseUrl.Mage::getStoreConfig(self::XML_URL_ALIVECHECK_PATH);
    			break;
    			
    		case self::URL_TYPE_SEARCH:
    			$url = $baseUrl.Mage::getStoreConfig(self::XML_URL_SEARCH_PATH);
    			break;    			
    			
    		case self::URL_TYPE_MAIN:
    		default:
    			$url = $baseUrl;
    			break;
    	}
    	return $url;
    }
    
    /**
     * is findologic service alive?
     * 
     * @return boolean
     */
    public function isAlive($localeCheck = false)
    {
    	if (is_null($this->_isAlive)) {
    		$this->_isAlive = false;
			$storeId = Mage::app()->getStore()->getStoreId();
			if(Mage::app()->getStore()->getCode()=='admin')
			{
				$storeId = Mage::app()->getRequest()->getParam('store', 0);
			}
	    	if((substr(Mage::getSingleton('core/locale')->getDefaultLocale(), 0,2) == 'de'
	    		or $localeCheck === false
	    		)
	    		&& !Mage::getStoreConfigFlag('advanced/modules_disable_output/Dixeno_Findologic')
	    		&& Mage::getStoreConfig(self::XML_SHOP_KEY_PATH,$storeId)
	    		&& $this->getUrl(self::URL_TYPE_MAIN)) {
    	
		    	try {
			    	$client = new Zend_Http_Client(
	    				$this->getUrl(self::URL_TYPE_LIVECHECK),
	    				array(
							'timeout' => Mage::getStoreConfig(self::XML_TIMEOUT_PATH)
						)
	    			);
					$client->setParameterGet('shopkey', Mage::getStoreConfig(self::XML_SHOP_KEY_PATH,$storeId));
			    	$response = $client->request(Zend_Http_Client::GET);
			    	
			    	$result = $response->getBody();
			    	if(trim($result) == 'alive'){
			    		$this->_isAlive = true;
			    	}

			    	if(strstr($result, 'message:')){
			    		$this->_setAliveMessage($result);
			    	}
		    	}
		    	catch(Zend_Http_Exception $e) {
		    		Mage::logException($e);
		    	}
	    	}
    	}

    	return $this->_isAlive;
    }
    
	/**
	 * get findologic logo URL
	 * 
	 * @return string
	 */
    public function getLogoUrl(){
    	
    	$logoKey = Mage::getStoreConfig(self::XML_LOGO_PATH,Mage::app()->getStore()->getStoreId());
    	$logos = Mage::getStoreConfig(self::XML_LOGOS_PATH,Mage::app()->getStore()->getStoreId());
    	
    	if(!$logoKey or !isset($logos[$logoKey])){
    		
    		foreach($logos as $logoKey => $logo){
    			if(isset($logo['default']) && $logo['default']){
    				break;
    			}
    		}
    	}
    	
    	return $logos[$logoKey]['url'];
    }
    
    /**
     * get Findologic Search result
     * 
     * @param string $queryText
     * @param int $limit
     * @return DOMdocument
     */
    public function search($queryText, $params = array())
    {
    	$client = new Zend_Http_Client($this->getUrl(self::URL_TYPE_SEARCH));
    	//echo "<pre>";var_dump(Mage::app()->getStore()->getCode());echo "</pre>";exit;
    	$params['shopkey'] = Mage::getStoreConfig(self::XML_SHOP_KEY_PATH,Mage::app()->getStore()->getStoreId());
    	$params['query'] = $queryText;
        
    	// check if customer want so see all products
        if ($params['count'] === 'all') {
            if (Mage::getStoreConfig('catalog/frontend/list_allow_all')) {
                // we're setting this to a very high value because findologic
                // has no dedicated value for "all"
                $params['count'] = 9999999999;
            }
            else {
                $listPerPageValues = explode(',', Mage::getStoreConfig('catalog/frontend/list_per_page_values'));
                $params['count'] = 9; // fallback to a default value
                if ($listPerPageValues) {
                    // extract the maximum value
                    $params['count'] = array_pop($listPerPageValues);
                }
            }
        }
    	//echo "<pre>";var_dump($params);echo "</pre>";exit;
		$params['userip'] = $_SERVER['REMOTE_ADDR'];
    	$client->setParameterGet($params);
    	
    	$xmlDom = null;
    	try{
    		Varien_Profiler::start('findologic/do_request');
			$response = $client->request(Zend_Http_Client::GET);
			$result = $response->getBody();

			Varien_Profiler::stop('findologic/do_request');
			
    	    if($result === null or empty($result)){
    			Mage::throwException('There is no XML Result');
    		}
			
			Varien_Profiler::start('findologic/parse_request');
    		$xmlDom = new DOMdocument;
	    	$xmlDom->preserveWhiteSpace = false;
			$xmlDom->loadXML($result);
			
			
			/*$filters = $xmlDom->getElementsByTagName( "filter" );
			  foreach( $filters as $filter )
			  {
			  	foreach($filter->getElementsByTagName('item') as $item){
			  			$items = $item->getElementsByTagName( "name" );
			  			$i = $items->item(0)->nodeValue;
			  		}
			  $authors = $filter->getElementsByTagName( "name" );
			  $author = $authors->item(0)->nodeValue;
			  
			  echo "123 $i - 123<br>";
			  }*/
			Varien_Profiler::stop('findologic/parse_request');
    		
    	}catch (Exception $e){
    		if(Mage::getIsDeveloperMode()){
    			throw $e;
    		}
    		Mage::logException($e);
    		Mage::log(__CLASS__ . '::' . __METHOD__ . 'params: ' . $params);
    		Mage::log(__CLASS__ . '::' . __METHOD__ . 'result: ' . $result);
    	}
    	
    	return $xmlDom;
    }
	
	/**
	 * @param string $queryText
	 * @return mixed
	 */
	public function autocomplete($queryText){
	
		$result = null;
		
    	$client = new Zend_Http_Client($this->getUrl(self::URL_TYPE_AUTOCOMPLETE));

    	$client->setParameterGet('shop', Mage::getStoreConfig(self::XML_SHOP_KEY_PATH,Mage::app()->getStore()->getStoreId()));
    	$client->setParameterGet('autoq', $queryText);

	    try{
			$response = $client->request(Zend_Http_Client::GET);
			$result = $response->getBody();
			
    	    if($result === null){
    			Mage::throwException('There is no XML Result');
    		}			

    		$result = json_decode($result);
    		
    	}catch (Exception $e){
    		if(Mage::getIsDeveloperMode()){
    			throw $e;
    		}
    	}

    	return $result;
	}
    
	protected function _setAliveMessage($msg){
		$this->_aliveMessage = $msg;
		return $this;
	}
    
	public function getAliveMessage(){
		return $this->_aliveMessage;
	}    
	
	public function firstLine(){
		return array(
					'id',
					'ordernumber',	
					'name',
					'summary',
					'description',
					'price',
					'instead',
					'maxprice',
					'url',
					'image',
					'attributes',
					'keywords',
					'groups',
					'bonus',
					'sales_frequency',
					'date_added',
					'sort'
					);	
	}
	
	public function SelectedAttributes(){
		return array (
						'color',
						'gender',
						'manufacturer',
						'vendor',
					);
	
	}
}
