<?php

class Product extends ProductCore
{
	public function addFeaturesToDB($id_feature, $id_value, $cust = 0)
	{
		if ($cust)
		{
			$row = array('id_feature' => (int)$id_feature, 'custom' => 1);
			Db::getInstance()->insert('feature_value', $row);
			$id_value = Db::getInstance()->Insert_ID();
		}
		$row = array('id_feature' => (int)$id_feature, 'id_product' => (int)$this->id, 'id_feature_value' => (int)$id_value);
		Db::getInstance()->insert('feature_product', $row);
		SpecificPriceRule::applyAllRules(array((int)$this->id));
		if ($id_value)
			return ($id_value);
	}

	public static function getFrontFeaturesStatic($id_lang, $id_product)
	{
		if (!Feature::isFeatureActive())
			return array();
		if (!array_key_exists($id_product.'-'.$id_lang, self::$_frontFeaturesCache))
		{
			self::$_frontFeaturesCache[$id_product.'-'.$id_lang] = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
				SELECT name, GROUP_CONCAT(value ORDER BY fv.position SEPARATOR ", ") AS value, pf.id_feature
				FROM '._DB_PREFIX_.'feature_product pf
				LEFT JOIN '._DB_PREFIX_.'feature_lang fl ON (fl.id_feature = pf.id_feature AND fl.id_lang = '.(int)$id_lang.')
				LEFT JOIN '._DB_PREFIX_.'feature_value_lang fvl ON (fvl.id_feature_value = pf.id_feature_value AND fvl.id_lang = '.(int)$id_lang.')
				LEFT JOIN '._DB_PREFIX_.'feature f ON f.id_feature = pf.id_feature
				LEFT JOIN '._DB_PREFIX_.'feature_value fv ON fv.id_feature_value = pf.id_feature_value
				'.Shop::addSqlAssociation('feature', 'f').'
				WHERE pf.id_product = '.(int)$id_product.'
				GROUP BY name, pf.id_feature
				ORDER BY f.position ASC'
			);
		}
		return self::$_frontFeaturesCache[$id_product.'-'.$id_lang];
	}

}
