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

class Dixeno_Findologic_Block_Autocomplete extends Mage_CatalogSearch_Block_Autocomplete
{
    protected $_suggestData = null;

    /**
     * get Suggest Data as Array
     * 
     * @return array
     */
    public function getSuggestData()
    {
    	if(!$this->helper('findologic')->isAlive()){
    		return parent::getSuggestData();
    	}	
    	
        if (!$this->_suggestData) {
        	$this->_suggestData = array();
        	
			$query = $this->helper('catalogsearch')->getQueryText();
        	$result = $this->helper('findologic')->autocomplete($query);

        	if(isset($result->suggestions)) {
        	    $counter = 0;
        	    $data = array();
        	    
	            foreach ($result->suggestions as $suggestion) {
	            	$suggestion = explode('|', $suggestion);
	            	
	                $_data = array(
	                    'title' => $suggestion[0],
	                    'row_class' => ++$counter % 2 ? 'odd' : 'even',
	                    'num_of_results' => $suggestion[1]
	                );
	
	                if ($_data['title'] == $query) {
	                    array_unshift($data, $_data);
	                }
	                else {
	                    $data[] = $_data;
	                }
	            }
	            $this->_suggestData = $data;
        	}
        }

		return $this->_suggestData;
    }
}
