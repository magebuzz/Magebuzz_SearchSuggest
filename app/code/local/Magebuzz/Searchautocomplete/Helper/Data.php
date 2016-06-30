<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Searchautocomplete_Helper_Data extends Mage_Core_Helper_Abstract {
  public function isActive() {
    if(Mage::getStoreConfig('searchautocomplete/general/is_active')){
      return true;
    }
    return false;
  }

  public function isShowThumbnailImage() {
    if(Mage::getStoreConfig('searchautocomplete/display_setting/is_show_thumbnail')){
      return true;
    }
    return false;
  }

  public function getThumbnailImageSize() {
    return (int)Mage::getStoreConfig('searchautocomplete/display_setting/thumbnail_product_image_size');
  }

  public function isShowProductDesc(){
    if(Mage::getStoreConfig('searchautocomplete/display_setting/is_show_description')){
      return true;
    }
    return false;
  }

  public function setLimitChar($str,$numberChar) {
    $short_text = substr($str, 0, $numberChar);
    if(substr($short_text, 0, strrpos($short_text, ' '))!=''){ 
      $short_text = substr($short_text, 0, strrpos($short_text, ' '));
      $short_text = $short_text.'...';
    }
    return $short_text;
  }

  public function getLimitCharProductDesc() {
    return (int)Mage::getStoreConfig('searchautocomplete/display_setting/limit_character_product_desc');
  }

  public function getSearchResultHeading() {
    return Mage::getStoreConfig('searchautocomplete/display_setting/search_result_heading');
  }

  public function isSearchByTag() {
    if(Mage::getStoreConfig('searchautocomplete/general/search_by_tag')){
      return true;
    }
    return false;
  }

  public function getSearchResults($keyword) {
    $result = array();
    $products = array();
    $resource = Mage::getSingleton('core/resource');
    $db = $resource->getConnection('core_read');
    $limit = Mage::getStoreConfig('searchautocomplete/display_setting/number_results');
    /* Search By Attributes */
    $attributes = Mage::getStoreConfig('searchautocomplete/general/searchable_attributes');
    $arrAttributes = explode(",", $attributes);
    foreach($arrAttributes as $index => $_attributeId){
      $_attribute_code = Mage::getModel('eav/entity_attribute')->load($_attributeId)->getAttributeCode();
      $products = $this->searchProductByAttribute($keyword,$_attribute_code);
      $result = array_merge($products, $result);
    }
    /* Search By Tags */
    if($this->isSearchByTag()){
      $resultByTag = $this->searchProductByTag($keyword);
      $result = array_merge($resultByTag, $result);
    }
    if(!(Mage::getStoreConfig('searchautocomplete/display_setting/show_out_of_stock'))&& !empty($result)){
      $select = $db->select();

      $select
      ->from($resource->getTableName('cataloginventory/stock_status'), 'product_id')
      ->where('product_id IN ('.implode(',',$result).') AND stock_status = 1');

      $result = $db->fetchCol($select);
    }
    $result = array_unique($result);
    $result = array_slice($result, 0, $limit);
    return $result;
  }

  public function searchByAttribute($keyword,$attribute) {
    $result = array();
    $storeId    = Mage::app()->getStore()->getId();
    $products = Mage::getModel('catalog/product')->getCollection()
    ->addAttributeToSelect('*')		
    ->setStoreId($storeId)
    ->addStoreFilter($storeId)
    ->addFieldToFilter("status",1)	
    ->addFieldToFilter($attribute,array('like'=>'%'. $keyword.'%'))	
    ->setCurPage(1)
    ->setOrder('name','ASC');

    Mage::getSingleton('catalog/product_status')->addSaleableFilterToCollection($products);
    Mage::getSingleton('catalog/product_visibility')->addVisibleInSiteFilterToCollection($products);
    $products->load();
    if(count($products))
    {
      foreach($products as $product)
      {
        $result[] = $product->getId();
      }
    }
    return $result;
  }

  /*
  * Get Manufacturer Ids by keyword
  */
  public function getManufacturerIds($keyword) {
    $attributeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product','manufacturer');
    $manufacturerIds = array();
    $read = Mage::getSingleton('core/resource')->getConnection('core_read');
    /* Create search query from attribute option table by keyword */
    /* Normal query
    $searchQuery = "SELECT DISTINCT eao.option_id";
    $searchQuery .= " FROM ".Mage::getSingleton('core/resource')->getTableName('eav_attribute_option_value')." AS eaov";
    $searchQuery .= " JOIN ".Mage::getSingleton('core/resource')->getTableName('eav_attribute_option')." AS eao ON eaov.option_id = eao.option_id";
    $searchQuery .= " WHERE eaov.value LIKE '%".$keyword."%' AND eao.attribute_id = '".$attributeId."'";
    */
    /* Query with specific character */
    $searchQuery = $read->quoteInto("SELECT DISTINCT eao.option_id FROM ".Mage::getSingleton('core/resource')->getTableName('eav_attribute_option_value')." AS eaov JOIN ".Mage::getSingleton('core/resource')->getTableName('eav_attribute_option')." AS eao ON eaov.option_id = eao.option_id WHERE eao.attribute_id = '".$attributeId."' AND eaov.value LIKE ?", '%'.$keyword.'%');

    /* Read results */
    $result = $read->fetchAll($searchQuery);

    foreach($result as $item) {
      array_push($manufacturerIds, $item['option_id']);
    }
    return $manufacturerIds;
  }

  public function searchProductByManufacturer($keyword) {
    $attributeId = Mage::getResourceModel('eav/entity_attribute')
    ->getIdByCode('catalog_product','manufacturer');
    //get manufacturer id by keyword 
    $manufacturerIds = $this->getManufacturerIds($keyword);
    $attribute = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
    $attributeOptions = $attribute ->getSource()->getAllOptions();
    $result = array();
    $storeId    = Mage::app()->getStore()->getId();

    $products = Mage::getModel('catalog/product')->getCollection()
    ->addAttributeToSelect('*')		
    ->setStoreId($storeId)
    ->addStoreFilter($storeId)
    ->addFieldToFilter("status", '1')	
    ->addFieldToFilter('manufacturer', array('in' => $manufacturerIds))	
    ->setCurPage(1)
    ->setOrder('name','ASC');

    Mage::getSingleton('catalog/product_status')->addSaleableFilterToCollection($products);
    Mage::getSingleton('catalog/product_visibility')->addVisibleInSiteFilterToCollection($products);
    $products->load();

    if(count($products))
    {
      foreach($products as $product)
      {
        $result[] = $product->getId();
      }
    }
    return $result;
  }

  public function searchProductByAttribute($keyword,$attributeCode) {
    $keyword = $this->jschars($keyword);
    $result = array();
    $storeId    = Mage::app()->getStore()->getId();
    $manufacturerIds = $this->getManufacturerIds($keyword);
    $productCollection = Mage::getModel('catalog/product')->getCollection()
    ->addAttributeToSelect('*')		
    ->setStoreId($storeId)
    ->addStoreFilter($storeId)
    ->addFieldToFilter("status", '1');
    if($attributeCode == 'manufacturer') {
      $attributeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product',$attributeCode);
      $manufacturerIds = $this->getManufacturerIds($keyword);
      $manufacturer = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
      $attributeOptions = $manufacturer ->getSource()->getAllOptions();
      $productCollection->addFieldToFilter('manufacturer', array('in' => $manufacturerIds));
    }else{
      $productCollection->addFieldToFilter($attributeCode,array('like'=>'%'.$keyword.'%'));
    }
    $productCollection->setCurPage(1);
    $productCollection->setOrder('name','ASC');

    Mage::getSingleton('catalog/product_status')->addSaleableFilterToCollection($productCollection);
    Mage::getSingleton('catalog/product_visibility')->addVisibleInSiteFilterToCollection($productCollection);
    $productCollection->load();
    if(count($productCollection))
    {
      foreach($productCollection as $product)
      {
        $result[] = $product->getId();
      }
    }
    return $result;
  }

  public function jschars($str) {
    $str = mb_ereg_replace("\\\\", "\\\\", $str);
    $str = mb_ereg_replace("\"", "\\\"", $str);
    $str = mb_ereg_replace("'", "\\'", $str);
    $str = mb_ereg_replace("\r\n", "\\n", $str);
    $str = mb_ereg_replace("\r", "\\n", $str);
    $str = mb_ereg_replace("\n", "\\n", $str);
    $str = mb_ereg_replace("\t", "\\t", $str);
    $str = mb_ereg_replace("<", "\\x3C", $str);
    $str = mb_ereg_replace(">", "\\x3E", $str);
    return $str;
  }

  public function searchProductByTag($keyword) {
    $result = array();
    if(Mage::getStoreConfig('searchautocomplete/general/search_by_tag')) {
      $model = Mage::getModel('tag/tag');
      $tag_collections = $model->getResourceCollection()
      ->addPopularity()
      ->addStatusFilter($model->getApprovedStatus())
      ->addFieldToFilter("name",array('like'=>'%'. $keyword.'%'))	
      ->addStoreFilter(Mage::app()->getStore()->getId())
      ->setActiveFilter()
      ->load();
      if(count($tag_collections)) {
        foreach($tag_collections as $tag) {
          $products = $this->getProductListByTagId($tag->getId());
          if(count($products))
          {
            foreach($products as $product)
            {
              $result[] = $product->getId();
            }
          }				
        }
      }
    }	
    return $result;	
  }

  public function getProductListByTagId($tagId) {
    $tagModel = Mage::getModel('tag/tag');
    $collections = $tagModel->getEntityCollection()
    ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
    ->addTagFilter($tagId)
    ->addStoreFilter()
    ->addMinimalPrice()
    ->addUrlRewrite()
    ->setActiveFilter();
    Mage::getSingleton('catalog/product_status')->addSaleableFilterToCollection($collections);
    Mage::getSingleton('catalog/product_visibility')->addVisibleInSiteFilterToCollection($collections);

    return $collections;		
  }
}