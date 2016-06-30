<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Searchautocomplete_Block_Searchautocomplete extends Mage_Core_Block_Template {
	public function _prepareLayout() {
		return parent::_prepareLayout();
	}
	
	public function getSearchautocomplete() { 
		if (!$this->hasData('searchautocomplete')) {
				$this->setData('searchautocomplete', Mage::registry('searchautocomplete'));
		}
		return $this->getData('searchautocomplete');
	}
}