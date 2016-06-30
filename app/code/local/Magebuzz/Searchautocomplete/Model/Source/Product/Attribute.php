<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/ 
class Magebuzz_Searchautocomplete_Model_Source_Product_Attribute {
	public static $_entityTypeId;
	public static $_productAttributes;
	public static $_productAttributeOptions;

	public static function getProductAttributeList() {
		if(is_array(self::$_productAttributes))
				return self::$_productAttributes;

		$resource = Mage::getSingleton('core/resource');
		$db = $resource->getConnection('core_read');

		$select = $db->select()
				->from($resource->getTableName('eav/entity_type'), 'entity_type_id')
				->where('entity_type_code=?', 'catalog_product')
				->limit(1);

		self::$_entityTypeId = $db->fetchOne($select);

		$select = $db->select()
				->from($resource->getTableName('eav/attribute'), array(
								'title' => 'frontend_label',          // for admin part
								'id'    => 'attribute_id',             // for applying filter to collection
								'code'  => 'attribute_code',    // as a tip for constructing {attribute_name}
								'type'  => 'backend_type',    // for table name
						))
				->where('entity_type_id=?', self::$_entityTypeId)
				->where('frontend_label<>""')
				->orWhere('attribute_code=?', "manufacturer")  //add new attribute in backend
				->where('find_in_set(backend_type, "text,varchar,static")')
				->order('frontend_label');
		foreach($db->fetchAll($select) as $v)
				self::$_productAttributes[$v['id']] = array(
								'title' => $v['title'],
								'code'  => $v['code'],
								'type'  => $v['type'],
				);

		return self::$_productAttributes;
	}

	public static function getProductIds2($query, $storeId) {
			$resource = Mage::getSingleton('core/resource');
			$db = $resource->getConnection('core_read');
			$select = $db->select();
			$fullTextTable = $resource->getTableName('catalogsearch/fulltext');
			$select->from($fullTextTable)
					->where('store_id = ?',$storeId)
					->where('data_index LIKE "%'.$query.'%"');
			$result = $db->fetchCol($select);
			return $result;
	}

	public static function getProductIds($attributes, $attrValues, $storeId) {
			$ids = array();
			if(empty($attributes)) return $ids;
			$resource = Mage::getSingleton('core/resource');
			$db = $resource->getConnection('core_read');

			// list of tables used for selecting
			$usedTables = array();
			foreach($attributes as $attrId => $attrType)
					$usedTables[$attrType] = true;

			foreach(array_keys($usedTables) as $tableName)
			{
				if ($tableName != 'static') {
					$select = $db->select();
					if ($tableName == 'int') {
						$eaov = $resource->getTableName('eav/attribute_option_value');
						$cpei = $resource->getTableName('catalog/product') . '_' . $tableName;
						//SQL
							// SELECT cpei.entity_id from catalog_product_entity_int AS cpei
							// JOIN eav_attribute_option_value AS eaov
							// ON cpei.`value` = eaov.option_id
							// WHERE eaov.`value` Like '%str%'
								$select->from(array('cpei' => $cpei), array('cpei.entity_id'))
												->join(array('eaov' => $eaov), 'cpei.`value` = eaov.option_id', array())
												->where('cpei.entity_type_id=?', self::$_entityTypeId)
												->where('cpei.store_id=0 OR cpei.store_id=?', $storeId)
												->where('cpei.attribute_id IN (' . implode(',', array_keys($attributes)) . ')');
							foreach ($attrValues as $value)
								$select ->where('eaov.`value` LIKE "%' . addslashes($value) . '%"');
							 
						}else{
							$select->distinct()
										->from($resource->getTableName('catalog/product') . '_' . $tableName, 'entity_id')
										->where('entity_type_id=?', self::$_entityTypeId)
										->where('store_id=0 OR store_id=?', $storeId)
										->where('attribute_id IN (' . implode(',', array_keys($attributes)) . ')');
							foreach ($attrValues as $value)
								$select->where('`value` LIKE "%' . addslashes($value) . '%"');
						}
						$ids = array_merge($ids, $db->fetchCol($select));
				}
				if ($tableName == 'static') {
						$select = $db->select();
						$select->distinct()
									->from($resource->getTableName('catalog/product'), 'entity_id')
									->where('entity_type_id=?', self::$_entityTypeId);
						foreach ($attrValues as $value)
							$select->where('`sku` LIKE "%' . addslashes($value) . '%"');
							
						$ids = array_merge($ids, $db->fetchCol($select));
				}
			}
			if(!(Mage::getStoreConfig('searchautocomplete/display_setting/show_out_of_stock'))&& !empty($ids)){
					$select = $db->select();

					$select
							->from($resource->getTableName('cataloginventory/stock_status'), 'product_id')
							->where('product_id IN ('.implode(',',$ids).') AND stock_status = 1');

					$ids = $db->fetchCol($select);
			}
			return array_unique($ids);
	}

	public static function toOptionArray()
	{
		if(is_array(self::$_productAttributeOptions)) return self::$_productAttributeOptions;

		self::$_productAttributeOptions = array();

		foreach(self::getProductAttributeList() as $id => $data)
			self::$_productAttributeOptions[] = array(
					'value' => $id,
					'label' => $data['title'].' ('.$data['code'].')'
			);
		return self::$_productAttributeOptions;
	}
}