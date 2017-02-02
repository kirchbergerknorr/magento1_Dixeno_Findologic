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
* @version $Id: Status.php 493 2010-08-13 15:41:52Z fuhr $
* @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
*/

class Dixeno_Findologic_Block_System_Config_Form_Field_Status extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Returns the elements HTML code
     * 
     * This field renders a green "enabled" if everything went fine and a red
     * "disabled" if an error occured. The error message is printed as note
     * below the status.
     * 
     * @param Varien_Data_Form_Element_Abstract $element The element is not used internally
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
    	$_helper = Mage::helper('findologic');
    	$output = '';
    	
    	if($_helper->isAlive(false)) {
    		$output = '<strong style="color: green">' . $this->__('enabled') . '</strong>';
    	}
    	else {
			$output = '<strong style="color: red">' . $this->__('disabled') . '</strong>';
    	    if($_helper->getAliveMessage()) {
    			$output .= ' <p class="note"><span>' . $_helper->getAliveMessage() . '</span></p>';
    		}
    	}

    	return $output;
	}
}
