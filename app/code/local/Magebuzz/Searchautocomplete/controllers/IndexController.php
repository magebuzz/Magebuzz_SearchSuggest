<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Searchautocomplete_IndexController extends Mage_Core_Controller_Front_Action {
	public function indexAction(){	
		$this->loadLayout();  
		$this->renderLayout();
	}
	
	public function ajaxsearchAction() {
		$html = "";
		$keyword = urldecode($this->getRequest()->getParam('keyword'));
		$_coreHelper = Mage::helper('core');
		$limitCharDesc = Mage::helper("searchautocomplete")->getLimitCharProductDesc();
		/* Get all ids */
		$productIds = Mage::helper("searchautocomplete")->getSearchResults($keyword);
		if(count($productIds) > 0) {
				$html .= "<ul class='list-products'>";
				foreach($productIds as $productId)
				{
					$product = Mage::getModel("catalog/product")->load($productId);
					$html .= "<li class='item product' onclick=\"location.href='".$product->getProductUrl()."'\">";
					$html .= "<div class='product-info'>";
					$price = $_coreHelper->currency($product->getPrice(),true,false);
					$productUrl = $product->getProductUrl();
					$productName = $product->getName();
					$productDesc = $product->getShortDescription();
					if(Mage::helper('searchautocomplete')->isShowThumbnailImage()){
						$img = Mage::helper('catalog/image')->init($product, 'image')->resize(Mage::helper('searchautocomplete')->getThumbnailImageSize());
						$img = $img->__toString();
						$html .= "<a class='product-img' href='".$productUrl."' title='".$productName."' alt='".$productName."' target='_blank'><img src='". $img ."' title='".$productName."'></a>";
					}	
					$html .= "<div class='product-desc'>";
					$html .= "<h3 class='product-name'><a href='".$productUrl."' title='".$productName."' target='_blank'>".$productName."</a></h3>";
					if(Mage::helper('searchautocomplete')->isShowProductDesc()){
						$html .= "<p class='desc'>".Mage::helper('searchautocomplete')->setLimitChar($productDesc,$limitCharDesc)."</p>";
					}
					$productBlock = new Mage_Catalog_Block_Product;
          $priceHtml = $this->getLayout()->createBlock('core/template')->setTemplate('searchautocomplete/product/price.phtml')->setProduct($product)->toHtml();
					$html .= "<div class='product-price'>".$priceHtml."</div>";
					unset($productBlock);
					$html .= "</div>";
					$html .= "</div>";
					$html .= "</li>";
				}
				$html .= "</ul>";
		}
		$responseHtml = array('html' => $html);
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($responseHtml));	
	}
}