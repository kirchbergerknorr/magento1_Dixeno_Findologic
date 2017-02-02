<?php

class Dixeno_Findologic_Block_Layer_Filter_Prices extends Dixeno_Findologic_Block_Layer_Filter_Abstract {
	
	public function init() {
		$this->setRequestVar('fl_prices');
	}		
	
	public function addItem($item) {
		
		if(isset($item['name'])){		
			$item['name'] = $this->_renderItemLabel($item['name']);
		}
		$this->_items[] = new Varien_Object($item);
		
		return $this;
	}

	
    /**
     * Prepare text of item label
     *
     * @param   int $range
     * @param   float $value
     * @return  string
     */
    protected function _renderItemLabel($value)
    {
        $store      = Mage::app()->getStore();
        $value = explode('-', $value);
        $fromPrice  = $store->formatPrice($value[0]);
        $toPrice    = $store->formatPrice($value[1]);
        return Mage::helper('catalog')->__('%s - %s', $fromPrice, $toPrice);
    }
    
    /**
     * Returns the filter's name
     * 
     * @return string
     */
    public function getName()
    {
        return 'Price';
    }
}