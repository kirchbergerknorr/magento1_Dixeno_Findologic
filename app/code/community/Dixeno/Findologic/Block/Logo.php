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
* @version $Id: Logo.php 224 2010-04-23 09:01:22Z weller $
* @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
*/
class Dixeno_Findologic_Block_Logo extends Mage_Core_Block_Template
{
    

	public function getLogoUrl(){
		
		return $this->helper('findologic')->getLogoUrl();
	}
	
	
}