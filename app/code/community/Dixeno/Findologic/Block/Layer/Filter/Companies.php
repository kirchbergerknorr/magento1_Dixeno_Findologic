<?php

class Dixeno_Findologic_Block_Layer_Filter_Companies extends Dixeno_Findologic_Block_Layer_Filter_Abstract {
	
	public function init() {
		$this->setRequestVar('fl_company');
	}		
	
	/**
	 * Returns the filter's name
	 * 
	 * @return string
	 */
	public function getName() {
		return 'Brand';
	}
}