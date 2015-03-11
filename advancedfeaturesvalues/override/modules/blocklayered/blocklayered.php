<?php

if (!defined('_CAN_LOAD_FILES_'))
  exit;

class BlockLayeredOverride extends BlockLayered
{
  
  private $page = 1;

  public function getFilterBlock($selected_filters = array())
  {
    global $cookie;
		static $cache = null;

		$id_lang = Context::getContext()->language->id;
		$currency = Context::getContext()->currency;
		$id_shop = (int) Context::getContext()->shop->id;
		$alias = 'product_shop';

		if (is_array($cache))
			return $cache;

		$home_category = Configuration::get('PS_HOME_CATEGORY');
		$id_parent = (int)Tools::getValue('id_category', Tools::getValue('id_category_layered', $home_category));
		if ($id_parent == $home_category)
			return;

		$parent = new Category((int)$id_parent, $id_lang);

		/* Get the filters for the current category */
		$filters = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT * FROM '._DB_PREFIX_.'layered_category
			WHERE id_category = '.(int)$id_parent.'
				AND id_shop = '.$id_shop.'
			GROUP BY `type`, id_value ORDER BY position ASC'
		);
		// Remove all empty selected filters
		foreach ($selected_filters as $key => $value)
			switch ($key)
			{
				case 'price':
				case 'weight':
					if ($value[0] === '' && $value[1] === '')
						unset($selected_filters[$key]);
					break;
				default:
					if ($value == '')
						unset($selected_filters[$key]);
					break;
			}

		$filter_blocks = array();
		foreach ($filters as $filter)
		{
			$sql_query = array('select' => '', 'from' => '', 'join' => '', 'where' => '', 'group' => '', 'order' => '', 'second_query' => '');
			switch ($filter['type'])
			{
				// conditions + quantities + weight + price
				case 'price':
				case 'weight':
				case 'condition':
				case 'quantity':

					$sql_query['select'] = 'SELECT p.`id_product`, product_shop.`condition`, p.`id_manufacturer`, sa.`quantity`, p.`weight` ';

					$sql_query['from'] = '
					FROM '._DB_PREFIX_.'product p ';
					$sql_query['join'] = '
					INNER JOIN '._DB_PREFIX_.'category_product cp ON (cp.id_product = p.id_product)
					INNER JOIN '._DB_PREFIX_.'category c ON (c.id_category = cp.id_category AND
					'.(Configuration::get('PS_LAYERED_FULL_TREE') ? 'c.nleft >= '.(int)$parent->nleft.'
					AND c.nright <= '.(int)$parent->nright : 'c.id_category = '.(int)$id_parent).'
					AND c.active = 1) ';

					$sql_query['join'] .= 'LEFT JOIN `'._DB_PREFIX_.'stock_available` sa
						ON (sa.id_product = p.id_product AND sa.id_shop = '.(int)$this->context->shop->id.') ';
					$sql_query['where'] = 'WHERE product_shop.`active` = 1 AND product_shop.`visibility` IN ("both", "catalog") ';

					$sql_query['group'] = ' GROUP BY p.id_product ';
					break;

				case 'manufacturer':
					$sql_query['select'] = 'SELECT m.name, COUNT(DISTINCT p.id_product) nbr, m.id_manufacturer ';
					$sql_query['from'] = '
					FROM `'._DB_PREFIX_.'category_product` cp
					INNER JOIN  `'._DB_PREFIX_.'category` c ON (c.id_category = cp.id_category)
					INNER JOIN '._DB_PREFIX_.'product p ON (p.id_product = cp.id_product)
					INNER JOIN '._DB_PREFIX_.'manufacturer m ON (m.id_manufacturer = p.id_manufacturer) ';
					$sql_query['where'] = 'WHERE
					'.(Configuration::get('PS_LAYERED_FULL_TREE') ? 'c.nleft >= '.(int)$parent->nleft.'
					AND c.nright <= '.(int)$parent->nright : 'c.id_category = '.(int)$id_parent).'
					AND c.active = 1
					AND '.$alias.'.active = 1 AND '.$alias.'.`visibility` IN ("both", "catalog")';
					$sql_query['group'] = ' GROUP BY p.id_manufacturer ORDER BY m.name';

					if (!Configuration::get('PS_LAYERED_HIDE_0_VALUES'))
					{
						$sql_query['second_query'] = '
							SELECT m.name, 0 nbr, m.id_manufacturer

							FROM `'._DB_PREFIX_.'category_product` cp'.
							Shop::addSqlAssociation('product', 'cp').'
							INNER JOIN  `'._DB_PREFIX_.'category` c ON (c.id_category = cp.id_category)
							INNER JOIN '._DB_PREFIX_.'product p ON (p.id_product = cp.id_product)
							INNER JOIN '._DB_PREFIX_.'manufacturer m ON (m.id_manufacturer = p.id_manufacturer)

							WHERE '.(Configuration::get('PS_LAYERED_FULL_TREE') ? 'c.nleft >= '.(int)$parent->nleft.'
							AND c.nright <= '.(int)$parent->nright : 'c.id_category = '.(int)$id_parent).'
							AND c.active = 1
							AND '.$alias.'.active = 1 AND '.$alias.'.`visibility` IN ("both", "catalog")
							GROUP BY p.id_manufacturer ORDER BY m.name';
					}

					break;
				case 'id_attribute_group':// attribute group
					$sql_query['select'] = '
					SELECT COUNT(DISTINCT p.id_product) nbr, lpa.id_attribute_group,
					a.color, al.name attribute_name, agl.public_name attribute_group_name , lpa.id_attribute, ag.is_color_group,
					liagl.url_name name_url_name, liagl.meta_title name_meta_title, lial.url_name value_url_name, lial.meta_title value_meta_title';
					$sql_query['from'] = '
					FROM '._DB_PREFIX_.'layered_product_attribute lpa
					INNER JOIN '._DB_PREFIX_.'attribute a
					ON a.id_attribute = lpa.id_attribute
					INNER JOIN '._DB_PREFIX_.'attribute_lang al
					ON al.id_attribute = a.id_attribute
					AND al.id_lang = '.(int)$id_lang.'
					INNER JOIN '._DB_PREFIX_.'product as p
					ON p.id_product = lpa.id_product
					INNER JOIN '._DB_PREFIX_.'attribute_group ag
					ON ag.id_attribute_group = lpa.id_attribute_group
					INNER JOIN '._DB_PREFIX_.'attribute_group_lang agl
					ON agl.id_attribute_group = lpa.id_attribute_group
					AND agl.id_lang = '.(int)$id_lang.'
					LEFT JOIN '._DB_PREFIX_.'layered_indexable_attribute_group_lang_value liagl
					ON (liagl.id_attribute_group = lpa.id_attribute_group AND liagl.id_lang = '.(int)$id_lang.')
					LEFT JOIN '._DB_PREFIX_.'layered_indexable_attribute_lang_value lial
					ON (lial.id_attribute = lpa.id_attribute AND lial.id_lang = '.(int)$id_lang.') ';

					$sql_query['where'] = 'WHERE a.id_attribute_group = '.(int)$filter['id_value'];
					$sql_query['where'] .= ' AND lpa.`id_shop` = '.(int)Context::getContext()->shop->id;
					$sql_query['where'] .= ' AND '.$alias.'.active = 1 AND '.$alias.'.`visibility` IN ("both", "catalog")
					AND p.id_product IN (
						SELECT id_product
						FROM '._DB_PREFIX_.'category_product cp
						INNER JOIN '._DB_PREFIX_.'category c ON (c.id_category = cp.id_category AND
						'.(Configuration::get('PS_LAYERED_FULL_TREE') ? 'c.nleft >= '.(int)$parent->nleft.'
						AND c.nright <= '.(int)$parent->nright : 'c.id_category = '.(int)$id_parent).'
						AND c.active = 1)
					) ';
					$sql_query['group'] = '
					GROUP BY lpa.id_attribute
					ORDER BY ag.`position` ASC, a.`position` ASC';

					if (!Configuration::get('PS_LAYERED_HIDE_0_VALUES'))
					{
						$sql_query['second_query'] = '
							SELECT 0 nbr, lpa.id_attribute_group,
								a.color, al.name attribute_name, agl.public_name attribute_group_name , lpa.id_attribute, ag.is_color_group,
								liagl.url_name name_url_name, liagl.meta_title name_meta_title, lial.url_name value_url_name, lial.meta_title value_meta_title
							FROM '._DB_PREFIX_.'layered_product_attribute lpa'.
							Shop::addSqlAssociation('product', 'lpa').'
							INNER JOIN '._DB_PREFIX_.'attribute a
								ON a.id_attribute = lpa.id_attribute
							INNER JOIN '._DB_PREFIX_.'attribute_lang al
								ON al.id_attribute = a.id_attribute AND al.id_lang = '.(int)$id_lang.'
							INNER JOIN '._DB_PREFIX_.'product as p
								ON p.id_product = lpa.id_product
							INNER JOIN '._DB_PREFIX_.'attribute_group ag
								ON ag.id_attribute_group = lpa.id_attribute_group
							INNER JOIN '._DB_PREFIX_.'attribute_group_lang agl
								ON agl.id_attribute_group = lpa.id_attribute_group
							AND agl.id_lang = '.(int)$id_lang.'
							LEFT JOIN '._DB_PREFIX_.'layered_indexable_attribute_group_lang_value liagl
								ON (liagl.id_attribute_group = lpa.id_attribute_group AND liagl.id_lang = '.(int)$id_lang.')
							LEFT JOIN '._DB_PREFIX_.'layered_indexable_attribute_lang_value lial
								ON (lial.id_attribute = lpa.id_attribute AND lial.id_lang = '.(int)$id_lang.')
							WHERE '.$alias.'.active = 1 AND '.$alias.'.`visibility` IN ("both", "catalog")
							AND a.id_attribute_group = '.(int)$filter['id_value'].'
							AND lpa.`id_shop` = '.(int)Context::getContext()->shop->id.'
							GROUP BY lpa.id_attribute
							ORDER BY id_attribute_group, id_attribute';
					}
					break;

				case 'id_feature':
					$sql_query['select'] = 'SELECT fl.name feature_name, fp.id_feature, fv.id_feature_value, fvl.value,
					COUNT(DISTINCT p.id_product) nbr,
					lifl.url_name name_url_name, lifl.meta_title name_meta_title, lifvl.url_name value_url_name, lifvl.meta_title value_meta_title ';
					$sql_query['from'] = '
					FROM '._DB_PREFIX_.'feature_product fp
					INNER JOIN '._DB_PREFIX_.'product p ON (p.id_product = fp.id_product)
					LEFT JOIN '._DB_PREFIX_.'feature_lang fl ON (fl.id_feature = fp.id_feature AND fl.id_lang = '.$id_lang.')
					INNER JOIN '._DB_PREFIX_.'feature_value fv ON (fv.id_feature_value = fp.id_feature_value AND (fv.custom IS NULL OR fv.custom = 0))
					LEFT JOIN '._DB_PREFIX_.'feature_value_lang fvl ON (fvl.id_feature_value = fp.id_feature_value AND fvl.id_lang = '.$id_lang.')
					LEFT JOIN '._DB_PREFIX_.'layered_indexable_feature_lang_value lifl
					ON (lifl.id_feature = fp.id_feature AND lifl.id_lang = '.$id_lang.')
					LEFT JOIN '._DB_PREFIX_.'layered_indexable_feature_value_lang_value lifvl
					ON (lifvl.id_feature_value = fp.id_feature_value AND lifvl.id_lang = '.$id_lang.') ';
					$sql_query['where'] = 'WHERE '.$alias.'.`active` = 1 AND '.$alias.'.`visibility` IN ("both", "catalog")
					AND fp.id_feature = '.(int)$filter['id_value'].'
					AND p.id_product IN (
					SELECT id_product
					FROM '._DB_PREFIX_.'category_product cp
					INNER JOIN '._DB_PREFIX_.'category c ON (c.id_category = cp.id_category AND
					'.(Configuration::get('PS_LAYERED_FULL_TREE') ? 'c.nleft >= '.(int)$parent->nleft.'
					AND c.nright <= '.(int)$parent->nright : 'c.id_category = '.(int)$id_parent).'
					AND c.active = 1)) ';
					$sql_query['group'] = 'GROUP BY fv.id_feature_value ';
					$sql_query['order'] = 'ORDER BY fv.position ';

					if (!Configuration::get('PS_LAYERED_HIDE_0_VALUES'))
					{
						$sql_query['second_query'] = '
							SELECT fl.name feature_name, fp.id_feature, fv.id_feature_value, fvl.value,
							0 nbr,
							lifl.url_name name_url_name, lifl.meta_title name_meta_title, lifvl.url_name value_url_name, lifvl.meta_title value_meta_title

							FROM '._DB_PREFIX_.'feature_product fp'.
							Shop::addSqlAssociation('product', 'fp').'
							INNER JOIN '._DB_PREFIX_.'product p ON (p.id_product = fp.id_product)
							LEFT JOIN '._DB_PREFIX_.'feature_lang fl ON (fl.id_feature = fp.id_feature AND fl.id_lang = '.(int)$id_lang.')
							INNER JOIN '._DB_PREFIX_.'feature_value fv ON (fv.id_feature_value = fp.id_feature_value AND (fv.custom IS NULL OR fv.custom = 0))
							LEFT JOIN '._DB_PREFIX_.'feature_value_lang fvl ON (fvl.id_feature_value = fp.id_feature_value AND fvl.id_lang = '.(int)$id_lang.')
							LEFT JOIN '._DB_PREFIX_.'layered_indexable_feature_lang_value lifl
								ON (lifl.id_feature = fp.id_feature AND lifl.id_lang = '.(int)$id_lang.')
							LEFT JOIN '._DB_PREFIX_.'layered_indexable_feature_value_lang_value lifvl
								ON (lifvl.id_feature_value = fp.id_feature_value AND lifvl.id_lang = '.(int)$id_lang.')
							WHERE '.$alias.'.`active` = 1 AND '.$alias.'.`visibility` IN ("both", "catalog")
							AND fp.id_feature = '.(int)$filter['id_value'].'
							GROUP BY fv.id_feature_value';
					}

					break;

				case 'category':
					if (Group::isFeatureActive())
						$this->user_groups =  ($this->context->customer->isLogged() ? $this->context->customer->getGroups() : array(Configuration::get('PS_UNIDENTIFIED_GROUP')));

					$depth = Configuration::get('PS_LAYERED_FILTER_CATEGORY_DEPTH');
					if ($depth === false)
						$depth = 1;

					$sql_query['select'] = '
					SELECT c.id_category, c.id_parent, cl.name, (SELECT count(DISTINCT p.id_product) # ';
					$sql_query['from'] = '
					FROM '._DB_PREFIX_.'category_product cp
					LEFT JOIN '._DB_PREFIX_.'product p ON (p.id_product = cp.id_product) ';
					$sql_query['where'] = '
					WHERE cp.id_category = c.id_category
					AND '.$alias.'.active = 1 AND '.$alias.'.`visibility` IN ("both", "catalog")';
					$sql_query['group'] = ') count_products
					FROM '._DB_PREFIX_.'category c
					LEFT JOIN '._DB_PREFIX_.'category_lang cl ON (cl.id_category = c.id_category AND cl.`id_shop` = '.(int)Context::getContext()->shop->id.' and cl.id_lang = '.(int)$id_lang.') ';

					if (Group::isFeatureActive())
						$sql_query['group'] .= 'RIGHT JOIN '._DB_PREFIX_.'category_group cg ON (cg.id_category = c.id_category AND cg.`id_group` IN ('.implode(', ', $this->user_groups).')) ';

					$sql_query['group'] .= 'WHERE c.nleft > '.(int)$parent->nleft.'
					AND c.nright < '.(int)$parent->nright.'
					'.($depth ? 'AND c.level_depth <= '.($parent->level_depth+(int)$depth) : '').'
					AND c.active = 1
					GROUP BY c.id_category ORDER BY c.nleft, c.position';
			}
			foreach ($filters as $filter_tmp)
			{
				$method_name = 'get'.ucfirst($filter_tmp['type']).'FilterSubQuery';
				if (method_exists('BlockLayered', $method_name) &&
				(!in_array($filter['type'], array('price', 'weight')) && $filter['type'] != $filter_tmp['type'] || $filter['type'] == $filter_tmp['type']))
				{
					if ($filter['type'] == $filter_tmp['type'] && $filter['id_value'] == $filter_tmp['id_value'])
						$sub_query_filter = self::$method_name(array(), true);
					else
					{
						if (!is_null($filter_tmp['id_value']))
							$selected_filters_cleaned = $this->cleanFilterByIdValue(@$selected_filters[$filter_tmp['type']], $filter_tmp['id_value']);
						else
							$selected_filters_cleaned = @$selected_filters[$filter_tmp['type']];
						$sub_query_filter = self::$method_name($selected_filters_cleaned, $filter['type'] == $filter_tmp['type']);
					}
					foreach ($sub_query_filter as $key => $value)
						$sql_query[$key] .= $value;
				}
			}

			$products = false;
			if (!empty($sql_query['from']))
			{
				$sql_query['from'] .= Shop::addSqlAssociation('product', 'p');
				$products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_query['select']."\n".$sql_query['from']."\n".$sql_query['join']."\n".$sql_query['where']."\n".$sql_query['group']."\n".$sql_query['order']);
			}

			foreach ($filters as $filter_tmp)
			{
				$method_name = 'filterProductsBy'.ucfirst($filter_tmp['type']);
				if (method_exists('BlockLayered', $method_name) &&
				(!in_array($filter['type'], array('price', 'weight')) && $filter['type'] != $filter_tmp['type'] || $filter['type'] == $filter_tmp['type']))
					if ($filter['type'] == $filter_tmp['type'])
						$products = self::$method_name(array(), $products);
					else
						$products = self::$method_name(@$selected_filters[$filter_tmp['type']], $products);
			}

			if (!empty($sql_query['second_query']))
			{
				$res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_query['second_query']);
				if ($res)
					$products = array_merge($products, $res);
			}

			switch ($filter['type'])
			{
				case 'price':
				if ($this->showPriceFilter()) {
					$price_array = array(
						'type_lite' => 'price',
						'type' => 'price',
						'id_key' => 0,
						'name' => $this->l('Price'),
						'slider' => true,
						'max' => '0',
						'min' => null,
						'values' => array ('1' => 0),
						'unit' => $currency->sign,
						'format' => $currency->format,
						'filter_show_limit' => $filter['filter_show_limit'],
						'filter_type' => $filter['filter_type']
					);
					if (isset($products) && $products)
						foreach ($products as $product)
						{
							if (is_null($price_array['min']))
							{
								$price_array['min'] = $product['price_min'];
								$price_array['values'][0] = $product['price_min'];
							}
							else if ($price_array['min'] > $product['price_min'])
							{
								$price_array['min'] = $product['price_min'];
								$price_array['values'][0] = $product['price_min'];
							}

							if ($price_array['max'] < $product['price_max'])
							{
								$price_array['max'] = $product['price_max'];
								$price_array['values'][1] = $product['price_max'];
							}
						}

					if ($price_array['max'] != $price_array['min'] && $price_array['min'] != null)
					{
						if ($filter['filter_type'] == 2)
						{
							$price_array['list_of_values'] = array();
							$nbr_of_value = $filter['filter_show_limit'];
							if ($nbr_of_value < 2)
								$nbr_of_value = 4;
							$delta = ($price_array['max'] - $price_array['min']) / $nbr_of_value;
							$current_step = $price_array['min'];
							for ($i = 0; $i < $nbr_of_value; $i++)
								$price_array['list_of_values'][] = array(
									(int)($price_array['min'] + $i * $delta),
									(int)($price_array['min'] + ($i + 1) * $delta)
								);
						}
						if (isset($selected_filters['price']) && isset($selected_filters['price'][0])
						&& isset($selected_filters['price'][1]))
						{
							$price_array['values'][0] = $selected_filters['price'][0];
							$price_array['values'][1] = $selected_filters['price'][1];
						}
						$filter_blocks[] = $price_array;
					}
				}
				break;

				case 'weight':
					$weight_array = array(
						'type_lite' => 'weight',
						'type' => 'weight',
						'id_key' => 0,
						'name' => $this->l('Weight'),
						'slider' => true,
						'max' => '0',
						'min' => null,
						'values' => array ('1' => 0),
						'unit' => Configuration::get('PS_WEIGHT_UNIT'),
						'format' => 5, // Ex: xxxxx kg
						'filter_show_limit' => $filter['filter_show_limit'],
						'filter_type' => $filter['filter_type']
					);
					if (isset($products) && $products)
						foreach ($products as $product)
						{
							if (is_null($weight_array['min']))
							{
								$weight_array['min'] = $product['weight'];
								$weight_array['values'][0] = $product['weight'];
							}
							else if ($weight_array['min'] > $product['weight'])
							{
								$weight_array['min'] = $product['weight'];
								$weight_array['values'][0] = $product['weight'];
							}

							if ($weight_array['max'] < $product['weight'])
							{
								$weight_array['max'] = $product['weight'];
								$weight_array['values'][1] = $product['weight'];
							}
						}
					if ($weight_array['max'] != $weight_array['min'] && $weight_array['min'] != null)
					{
						if (isset($selected_filters['weight']) && isset($selected_filters['weight'][0])
						&& isset($selected_filters['weight'][1]))
						{
							$weight_array['values'][0] = $selected_filters['weight'][0];
							$weight_array['values'][1] = $selected_filters['weight'][1];
						}
						$filter_blocks[] = $weight_array;
					}
					break;

				case 'condition':
					$condition_array = array(
						'new' => array('name' => $this->l('New'),'nbr' => 0),
						'used' => array('name' => $this->l('Used'), 'nbr' => 0),
						'refurbished' => array('name' => $this->l('Refurbished'),
						'nbr' => 0)
					);
					if (isset($products) && $products)
						foreach ($products as $product)
							if (isset($selected_filters['condition']) && in_array($product['condition'], $selected_filters['condition']))
								$condition_array[$product['condition']]['checked'] = true;
					foreach ($condition_array as $key => $condition)
						if (isset($selected_filters['condition']) && in_array($key, $selected_filters['condition']))
							$condition_array[$key]['checked'] = true;
					if (isset($products) && $products)
						foreach ($products as $product)
							if (isset($condition_array[$product['condition']]))
								$condition_array[$product['condition']]['nbr']++;
					$filter_blocks[] = array(
						'type_lite' => 'condition',
						'type' => 'condition',
						'id_key' => 0,
						'name' => $this->l('Condition'),
						'values' => $condition_array,
						'filter_show_limit' => $filter['filter_show_limit'],
						'filter_type' => $filter['filter_type']
					);
					break;

				case 'quantity':
					$quantity_array = array (
						0 => array('name' => $this->l('Not available'), 'nbr' => 0),
						1 => array('name' => $this->l('In stock'), 'nbr' => 0)
					);
					foreach ($quantity_array as $key => $quantity)
						if (isset($selected_filters['quantity']) && in_array($key, $selected_filters['quantity']))
							$quantity_array[$key]['checked'] = true;
					if (isset($products) && $products)
						foreach ($products as $product)
						{
							//If oosp move all not available quantity to available quantity
							if ((int)$product['quantity'] > 0 || Product::isAvailableWhenOutOfStock(StockAvailable::outOfStock($product['id_product'])))
								$quantity_array[1]['nbr']++;
							else
								$quantity_array[0]['nbr']++;
						}

					$filter_blocks[] = array(
						'type_lite' => 'quantity',
						'type' => 'quantity',
						'id_key' => 0,
						'name' => $this->l('Availability'),
						'values' => $quantity_array,
						'filter_show_limit' => $filter['filter_show_limit'],
						'filter_type' => $filter['filter_type']
					);

					break;

				case 'manufacturer':
					if (isset($products) && $products)
					{
						$manufaturers_array = array();
							foreach ($products as $manufacturer)
							{
								if (!isset($manufaturers_array[$manufacturer['id_manufacturer']]))
									$manufaturers_array[$manufacturer['id_manufacturer']] = array('name' => $manufacturer['name'], 'nbr' => $manufacturer['nbr']);
								if (isset($selected_filters['manufacturer']) && in_array((int)$manufacturer['id_manufacturer'], $selected_filters['manufacturer']))
									$manufaturers_array[$manufacturer['id_manufacturer']]['checked'] = true;
							}
						$filter_blocks[] = array(
							'type_lite' => 'manufacturer',
							'type' => 'manufacturer',
							'id_key' => 0,
							'name' => $this->l('Manufacturer'),
							'values' => $manufaturers_array,
							'filter_show_limit' => $filter['filter_show_limit'],
							'filter_type' => $filter['filter_type']
						);
					}
					break;

				case 'id_attribute_group':
					$attributes_array = array();
					if (isset($products) && $products)
					{
						foreach ($products as $attributes)
						{
							if (!isset($attributes_array[$attributes['id_attribute_group']]))
								$attributes_array[$attributes['id_attribute_group']] = array (
									'type_lite' => 'id_attribute_group',
									'type' => 'id_attribute_group',
									'id_key' => (int)$attributes['id_attribute_group'],
									'name' =>  $attributes['attribute_group_name'],
									'is_color_group' => (bool)$attributes['is_color_group'],
									'values' => array(),
									'url_name' => $attributes['name_url_name'],
									'meta_title' => $attributes['name_meta_title'],
									'filter_show_limit' => $filter['filter_show_limit'],
									'filter_type' => $filter['filter_type']
								);

							if (!isset($attributes_array[$attributes['id_attribute_group']]['values'][$attributes['id_attribute']]))
								$attributes_array[$attributes['id_attribute_group']]['values'][$attributes['id_attribute']] = array(
									'color' => $attributes['color'],
									'name' => $attributes['attribute_name'],
									'nbr' => (int)$attributes['nbr'],
									'url_name' => $attributes['value_url_name'],
									'meta_title' => $attributes['value_meta_title']
								);

							if (isset($selected_filters['id_attribute_group'][$attributes['id_attribute']]))
								$attributes_array[$attributes['id_attribute_group']]['values'][$attributes['id_attribute']]['checked'] = true;
						}

						$filter_blocks = array_merge($filter_blocks, $attributes_array);
					}
					break;
				case 'id_feature':
					$feature_array = array();
					if (isset($products) && $products)
					{
						foreach ($products as $feature)
						{
							if (!isset($feature_array[$feature['id_feature']]))
								$feature_array[$feature['id_feature']] = array(
									'type_lite' => 'id_feature',
									'type' => 'id_feature',
									'id_key' => (int)$feature['id_feature'],
									'values' => array(),
									'name' => $feature['feature_name'],
									'url_name' => $feature['name_url_name'],
									'meta_title' => $feature['name_meta_title'],
									'filter_show_limit' => $filter['filter_show_limit'],
									'filter_type' => $filter['filter_type']
								);

							if (!isset($feature_array[$feature['id_feature']]['values'][$feature['id_feature_value']]))
								$feature_array[$feature['id_feature']]['values'][$feature['id_feature_value']] = array(
									'nbr' => (int)$feature['nbr'],
									'name' => $feature['value'],
									'url_name' => $feature['value_url_name'],
									'meta_title' => $feature['value_meta_title']
								);

							if (isset($selected_filters['id_feature'][$feature['id_feature_value']]))
								$feature_array[$feature['id_feature']]['values'][$feature['id_feature_value']]['checked'] = true;
						}

						$filter_blocks = array_merge($filter_blocks, $feature_array);
					}
					break;

				case 'category':
					$tmp_array = array();
					if (isset($products) && $products)
					{
						$categories_with_products_count = 0;
						foreach ($products as $category)
						{
							$tmp_array[$category['id_category']] = array(
								'name' => $category['name'],
								'nbr' => (int)$category['count_products']
							);

							if ((int)$category['count_products'])
								$categories_with_products_count++;

							if (isset($selected_filters['category']) && in_array($category['id_category'], $selected_filters['category']))
								$tmp_array[$category['id_category']]['checked'] = true;
						}
						if ($categories_with_products_count || !Configuration::get('PS_LAYERED_HIDE_0_VALUES'))
							$filter_blocks[] = array (
								'type_lite' => 'category',
								'type' => 'category',
								'id_key' => 0, 'name' => $this->l('Categories'),
								'values' => $tmp_array,
								'filter_show_limit' => $filter['filter_show_limit'],
								'filter_type' => $filter['filter_type']
							);
					}
					break;
			}
		}

		// All non indexable attribute and feature
		$non_indexable = array();

		// Get all non indexable attribute groups
		foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
		SELECT public_name
		FROM `'._DB_PREFIX_.'attribute_group_lang` agl
		LEFT JOIN `'._DB_PREFIX_.'layered_indexable_attribute_group` liag
		ON liag.id_attribute_group = agl.id_attribute_group
		WHERE indexable IS NULL OR indexable = 0
		AND id_lang = '.(int)$id_lang) as $attribute)
			$non_indexable[] = Tools::link_rewrite($attribute['public_name']);

		// Get all non indexable features
		foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
		SELECT name
		FROM `'._DB_PREFIX_.'feature_lang` fl
		LEFT JOIN  `'._DB_PREFIX_.'layered_indexable_feature` lif
		ON lif.id_feature = fl.id_feature
		WHERE indexable IS NULL OR indexable = 0
		AND id_lang = '.(int)$id_lang) as $attribute)
			$non_indexable[] = Tools::link_rewrite($attribute['name']);

		//generate SEO link
		$param_selected = '';
		$param_product_url = '';
		$option_checked_array = array();
		$param_group_selected_array = array();
		$title_values = array();
		$meta_values = array();

		//get filters checked by group

		foreach ($filter_blocks as $type_filter)
		{
			$filter_name = (!empty($type_filter['url_name']) ? $type_filter['url_name'] : $type_filter['name']);
			$filter_meta = (!empty($type_filter['meta_title']) ? $type_filter['meta_title'] : $type_filter['name']);
			$attr_key = $type_filter['type'].'_'.$type_filter['id_key'];

			$param_group_selected = '';

			if (in_array(strtolower($type_filter['type']), array('price', 'weight'))
				&& (float)$type_filter['values'][0] > (float)$type_filter['min']
				&& (float)$type_filter['values'][1] > (float)$type_filter['max'])
			{
				$param_group_selected .= $this->getAnchor().str_replace($this->getAnchor(), '_', $type_filter['values'][0])
					.$this->getAnchor().str_replace($this->getAnchor(), '_', $type_filter['values'][1]);
				$param_group_selected_array[Tools::link_rewrite($filter_name)][] = Tools::link_rewrite($filter_name);

				if (!isset($title_values[$filter_meta]))
					$title_values[$filter_meta] = array();
				$title_values[$filter_meta][] = $filter_meta;
				if (!isset($meta_values[$attr_key]))
					$meta_values[$attr_key] = array('title' => $filter_meta, 'values' => array());
				$meta_values[$attr_key]['values'][] = $filter_meta;
			}
			else
			{
				foreach ($type_filter['values'] as $key => $value)
				{
					if (is_array($value) && array_key_exists('checked', $value ))
					{
						$value_name = !empty($value['url_name']) ? $value['url_name'] : $value['name'];
						$value_meta = !empty($value['meta_title']) ? $value['meta_title'] : $value['name'];
						$param_group_selected .= $this->getAnchor().str_replace($this->getAnchor(), '_', Tools::link_rewrite($value_name));
						$param_group_selected_array[Tools::link_rewrite($filter_name)][] = Tools::link_rewrite($value_name);

						if (!isset($title_values[$filter_meta]))
							$title_values[$filter_meta] = array();
						$title_values[$filter_meta][] = $value_name;
						if (!isset($meta_values[$attr_key]))
							$meta_values[$attr_key] = array('title' => $filter_meta, 'values' => array());
						$meta_values[$attr_key]['values'][] = $value_meta;
					}
					else
						$param_group_selected_array[Tools::link_rewrite($filter_name)][] = array();
				}
			}

			if (!empty($param_group_selected))
			{
				$param_selected .= '/'.str_replace($this->getAnchor(), '_', Tools::link_rewrite($filter_name)).$param_group_selected;
				$option_checked_array[Tools::link_rewrite($filter_name)] = $param_group_selected;
			}
			// select only attribute and group attribute to display an unique product combination link
			if (!empty($param_group_selected) && $type_filter['type'] == 'id_attribute_group')
				$param_product_url .= '/'.str_replace($this->getAnchor(), '_', Tools::link_rewrite($filter_name)).$param_group_selected;

		}

		if ($this->page > 1)
			$param_selected .= '/page-'.$this->page;

		$blacklist = array('weight', 'price');

		if (!Configuration::get('PS_LAYERED_FILTER_INDEX_CDT'))
			$blacklist[] = 'condition';
		if (!Configuration::get('PS_LAYERED_FILTER_INDEX_QTY'))
			$blacklist[] = 'quantity';
		if (!Configuration::get('PS_LAYERED_FILTER_INDEX_MNF'))
			$blacklist[] = 'manufacturer';
		if (!Configuration::get('PS_LAYERED_FILTER_INDEX_CAT'))
			$blacklist[] = 'category';

		$global_nofollow = false;

		foreach ($filter_blocks as &$type_filter)
		{
			$filter_name = (!empty($type_filter['url_name']) ? $type_filter['url_name'] : $type_filter['name']);

			if (count($type_filter) > 0 && !isset($type_filter['slider']))
			{
				foreach ($type_filter['values'] as $key => $values)
				{
					$nofollow = false;
					if (!empty($values['checked']) && in_array($type_filter['type'], $blacklist))
						$global_nofollow = true;

					$option_checked_clone_array = $option_checked_array;

					// If not filters checked, add parameter
					$value_name = !empty($values['url_name']) ? $values['url_name'] : $values['name'];

					if (!in_array(Tools::link_rewrite($value_name), $param_group_selected_array[Tools::link_rewrite($filter_name)]))
					{
						// Update parameter filter checked before
						if (array_key_exists(Tools::link_rewrite($filter_name), $option_checked_array))
						{
							$option_checked_clone_array[Tools::link_rewrite($filter_name)] = $option_checked_clone_array[Tools::link_rewrite($filter_name)].$this->getAnchor().str_replace($this->getAnchor(), '_', Tools::link_rewrite($value_name));

							if (in_array($type_filter['type'], $blacklist))
								$nofollow = true;
						}
						else
							$option_checked_clone_array[Tools::link_rewrite($filter_name)] = $this->getAnchor().str_replace($this->getAnchor(), '_', Tools::link_rewrite($value_name));
					}
					else
					{
						// Remove selected parameters
						$option_checked_clone_array[Tools::link_rewrite($filter_name)] = str_replace($this->getAnchor().str_replace($this->getAnchor(), '_', Tools::link_rewrite($value_name)), '', $option_checked_clone_array[Tools::link_rewrite($filter_name)]);
						if (empty($option_checked_clone_array[Tools::link_rewrite($filter_name)]))
							unset($option_checked_clone_array[Tools::link_rewrite($filter_name)]);
					}
					$parameters = '';
					ksort($option_checked_clone_array); // Order parameters
					foreach ($option_checked_clone_array as $key_group => $value_group)
						$parameters .= '/'.str_replace($this->getAnchor(), '_', $key_group).$value_group;

					// Add nofollow if any blacklisted filters ins in parameters
					foreach ($filter_blocks as $filter)
					{
						$name = Tools::link_rewrite((!empty($filter['url_name']) ? $filter['url_name'] : $filter['name']));
						if (in_array($filter['type'], $blacklist) && strpos($parameters, $name.'-') !== false)
							$nofollow = true;
					}

					// Check if there is an non indexable attribute or feature in the url
					foreach ($non_indexable as $value)
						if (strpos($parameters, '/'.$value) !== false)
							$nofollow = true;

					$type_filter['values'][$key]['link'] = Context::getContext()->link->getCategoryLink($parent, null, null).'#'.ltrim($parameters, '/');
					$type_filter['values'][$key]['rel'] = ($nofollow) ? 'nofollow' : '';
				}
			}
		}

		$n_filters = 0;
		if (isset($selected_filters['price']))
			if ($price_array['min'] == $selected_filters['price'][0] && $price_array['max'] == $selected_filters['price'][1])
				unset($selected_filters['price']);
		if (isset($selected_filters['weight']))
			if ($weight_array['min'] == $selected_filters['weight'][0] && $weight_array['max'] == $selected_filters['weight'][1])
				unset($selected_filters['weight']);

		foreach ($selected_filters as $filters)
			$n_filters += count($filters);

		$cache = array(
			'layered_show_qties' => (int)Configuration::get('PS_LAYERED_SHOW_QTIES'),
			'id_category_layered' => (int)$id_parent,
			'selected_filters' => $selected_filters,
			'n_filters' => (int)$n_filters,
			'nbr_filterBlocks' => count($filter_blocks),
			'filters' => $filter_blocks,
			'title_values' => $title_values,
			'meta_values' => $meta_values,
			'current_friendly_url' => $param_selected,
			'param_product_url' => $param_product_url,
			'no_follow' => (!empty($param_selected) || $global_nofollow)
		);

		return $cache;
  }
  
  private static function getCategoryFilterSubQuery($filter_value, $ignore_join)
	{
		if (empty($filter_value))
			return array();
		$query_filters_join = '';
		$query_filters_where = ' AND p.id_product IN (SELECT id_product FROM '._DB_PREFIX_.'category_product cp WHERE ';
		foreach ($filter_value as $id_category)
			$query_filters_where .= 'cp.`id_category` = '.(int)$id_category.' OR ';
		$query_filters_where = rtrim($query_filters_where, 'OR ').') ';

		return array('where' => $query_filters_where, 'join' => $query_filters_join);
	}
	
	private static function getId_featureFilterSubQuery($filter_value, $ignore_join)
	{
		if (empty($filter_value))
			return array();
		$query_filters = ' AND p.id_product IN (SELECT id_product FROM '._DB_PREFIX_.'feature_product fp WHERE ';
		foreach ($filter_value as $filter_val)
			$query_filters .= 'fp.`id_feature_value` = '.(int)$filter_val.' OR ';
		$query_filters = rtrim($query_filters, 'OR ').') ';

		return array('where' => $query_filters);
	}
	
	private static function getPriceFilterSubQuery($filter_value)
	{
		$id_currency = (int)Context::getContext()->currency->id;

		if (isset($filter_value) && $filter_value)
		{
			$price_filter_query = '
			INNER JOIN `'._DB_PREFIX_.'layered_price_index` psi ON (psi.id_product = p.id_product AND psi.id_currency = '.(int)$id_currency.'
			AND psi.price_min <= '.(int)$filter_value[1].' AND psi.price_max >= '.(int)$filter_value[0].' AND psi.id_shop='.(int)Context::getContext()->shop->id.') ';
		}
		else
		{
			$price_filter_query = '
			INNER JOIN `'._DB_PREFIX_.'layered_price_index` psi
			ON (psi.id_product = p.id_product AND psi.id_currency = '.(int)$id_currency.' AND psi.id_shop='.(int)Context::getContext()->shop->id.') ';
		}

		return array('join' => $price_filter_query, 'select' => ', psi.price_min, psi.price_max');
	}
	
	private static function getQuantityFilterSubQuery($filter_value, $ignore_join)
	{
		if (count($filter_value) == 2 || empty($filter_value))
			return array();

		$query_filters_join = '';

		$query_filters = ' AND sav.quantity '.(!$filter_value[0] ? '<=' : '>').' 0 ';
		$query_filters_join = 'LEFT JOIN `'._DB_PREFIX_.'stock_available` sav ON (sav.id_product = p.id_product AND sav.id_shop = '.(int)Context::getContext()->shop->id.') ';

		return array('where' => $query_filters, 'join' => $query_filters_join);
	}

	private static function getManufacturerFilterSubQuery($filter_value, $ignore_join)
	{
		if (empty($filter_value))
			$query_filters = '';
		else
		{
			array_walk($filter_value, create_function('&$id_manufacturer', '$id_manufacturer = (int)$id_manufacturer;'));
			$query_filters = ' AND p.id_manufacturer IN ('.implode($filter_value, ',').')';
		}
			if ($ignore_join)
				return array('where' => $query_filters, 'select' => ', m.name');
			else
				return array('where' => $query_filters, 'select' => ', m.name', 'join' => 'LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (m.id_manufacturer = p.id_manufacturer) ');
	}

	private static function getConditionFilterSubQuery($filter_value, $ignore_join)
	{
		if (count($filter_value) == 3 || empty($filter_value))
			return array();

		$query_filters = ' AND product_shop.condition IN (';

		foreach ($filter_value as $cond)
			$query_filters .= '\''.$cond.'\',';
		$query_filters = rtrim($query_filters, ',').') ';

		return array('where' => $query_filters);
	}
	
	private static function filterProductsByPrice($filter_value, $product_collection)
	{
		if (empty($filter_value))
			return $product_collection;
		foreach ($product_collection as $key => $product)
		{
			if (isset($filter_value) && $filter_value && isset($product['price_min']) && isset($product['id_product'])
			&& ((int)$filter_value[0] > $product['price_min'] || (int)$filter_value[1] < $product['price_max']))
			{
				$price = Product::getPriceStatic($product['id_product'], Configuration::get('PS_LAYERED_FILTER_PRICE_USETAX'));
				if ($price < $filter_value[0] || $price > $filter_value[1])
					continue;
				unset($product_collection[$key]);
			}
		}
		return $product_collection;
	}

}
