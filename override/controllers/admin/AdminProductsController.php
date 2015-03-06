<?php

class AdminProductsController extends AdminProductsControllerCore
{
	public function initFormFeatures($obj)
	{
		if (!$this->default_form_language)
			$this->getLanguages();

		$tpl_path = _PS_MODULE_DIR_.'advancedfeaturesvalues/views/templates/admin/products/features.tpl';
		$data = $this->context->smarty->createTemplate($tpl_path, $this->context->smarty);

		$data->assign('default_form_language', $this->default_form_language);
		$data->assign('languages', $this->_languages);

		if (!Feature::isFeatureActive())
			$this->displayWarning($this->l('This feature has been disabled. ').' <a href="index.php?tab=AdminPerformance&token='.Tools::getAdminTokenLite('AdminPerformance').'#featuresDetachables">'.$this->l('Performances').'</a>');
		else
		{
			if ($obj->id)
			{
				if ($this->product_exists_in_shop)
				{
					$features = Feature::getFeatures($this->context->language->id, (Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP));

					foreach ($features as $k => $tab_features)
					{
						$features[$k]['current_item'] = array();
						$features[$k]['val'] = array();

						$custom = true;
						foreach ($obj->getFeatures() as $tab_products)
							if ($tab_products['id_feature'] == $tab_features['id_feature'])
								$features[$k]['current_item'][] = $tab_products['id_feature_value'];

						$features[$k]['featureValues'] = FeatureValue::getFeatureValuesWithLang($this->context->language->id, (int)$tab_features['id_feature']);
						if (count($features[$k]['featureValues']))
							foreach ($features[$k]['featureValues'] as $value)
								if (in_array($value['id_feature_value'], $features[$k]['current_item']))
									$custom = false;

						if ($custom && !empty($features[$k]['current_item']))
							$features[$k]['val'] = FeatureValue::getFeatureValueLang($features[$k]['current_item'][0]);
					}

					$data->assign('available_features', $features);
					$data->assign('product', $obj);
					$data->assign('link', $this->context->link);
					$data->assign('default_form_language', $this->default_form_language);
				}
				else
					$this->displayWarning($this->l('You must save the product in this shop before adding features.'));
			}
			else
				$this->displayWarning($this->l('You must save this product before adding features.'));
		}
		$this->tpl_form_vars['custom_form'] = $data->fetch();
	}
	
	public function processFeatures()
	{
		if (!Feature::isFeatureActive())
			return;

		if (Validate::isLoadedObject($product = new Product((int)Tools::getValue('id_product'))))
		{
			// delete all objects
			$product->deleteFeatures();

			// add new objects
			$languages = Language::getLanguages(false);
			foreach ($_POST as $key => $val)
			{
				if (preg_match('/^feature_([0-9]+)_value/i', $key, $match))
				{
					if (!empty($val)) {
						foreach ($val as $v) {
							$product->addFeaturesToDB($match[1], $v);
						}
					}
					else
					{
						if ($default_value = $this->checkFeatures($languages, $match[1]))
						{
							$id_value = $product->addFeaturesToDB($match[1], 0, 1);
							foreach ($languages as $language)
							{
								if ($cust = Tools::getValue('custom_'.$match[1].'_'.(int)$language['id_lang']))
									$product->addFeaturesCustomToDB($id_value, (int)$language['id_lang'], $cust);
								else
									$product->addFeaturesCustomToDB($id_value, (int)$language['id_lang'], $default_value);
							}
						}
					}
				}
			}
		}
		else
			$this->errors[] = Tools::displayError('A product must be created before adding features.');
	}
}
