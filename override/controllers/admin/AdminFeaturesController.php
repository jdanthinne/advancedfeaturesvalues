<?php
/**
* 2007-2014 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2014 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class AdminFeaturesController extends AdminFeaturesControllerCore
{
	public function __construct()
	{
		$this->table = 'feature';
		$this->className = 'Feature';
		$this->list_id = 'feature';
		$this->identifier = 'id_feature';
		$this->lang = true;

		$this->fields_list = array(
			'id_feature' => array(
				'title' => $this->l('ID'),
				'align' => 'center',
				'class' => 'fixed-width-xs'
			),
			'name' => array(
				'title' => $this->l('Name'),
				'width' => 'auto',
				'filter_key' => 'b!name'
			),
			'value' => array(
				'title' => $this->l('Values'),
				'orderby' => false,
				'search' => false,
				'align' => 'center',
				'class' => 'fixed-width-xs'
			),
			'parent_id_feature' => array(
				'title' => $this->l('ParentID'),
				'align' => 'center',
				'class' => 'fixed-width-xs'
			),
			'position' => array(
				'title' => $this->l('Position'),
				'filter_key' => 'a!position',
				'align' => 'center',
				'class' => 'fixed-width-xs',
				'position' => 'position'
			)
		);

		$this->bulk_actions = array(
			'delete' => array(
				'text' => $this->l('Delete selected'),
				'icon' => 'icon-trash',
				'confirm' => $this->l('Delete selected items?')
			)
		);
		AdminController::__construct();
	}

	/**
	 * AdminController::renderForm() override
	 * @see AdminController::renderForm()
	 */
	public function renderForm()
	{
		$this->toolbar_title = $this->l('Add a new feature');
		$this->fields_form = array(
			'legend' => array(
				'title' => $this->l('Feature with Parent'),
				'icon' => 'icon-info-sign'
			),
			'input' => array(
				array(
					'type' => 'text',
					'label' => $this->l('Name'),
					'name' => 'name',
					'lang' => true,
					'size' => 33,
					'hint' => $this->l('Invalid characters:').' <>;=#{}',
					'required' => true
				),
				array(
					'type' => 'select',
					'label' => $this->l('Parent Feature'),
					'name' => 'parent_id_feature',
					'options' => array(
						'query' => Feature::getFeaturesExcept($this->context->language->id, Tools::getValue('id_feature')),
						'id' => 'id_feature',
						'name' => 'name'
					),
					'required' => true
				)
			)
		);

		if (Shop::isFeatureActive())
		{
			$this->fields_form['input'][] = array(
				'type' => 'shop',
				'label' => $this->l('Shop association'),
				'name' => 'checkBoxShopAsso',
			);
		}

		$this->fields_form['submit'] = array(
			'title' => $this->l('Save'),
		);

		return AdminController::renderForm();
	}

	public function renderView()
	{
		if (($id = Tools::getValue('id_feature')))
		{
			$this->setTypeValue();
			$this->list_id = 'feature_value';
			$this->position_identifier = 'id_feature_value';
			$this->position_group_identifier = 'id_feature';
			$this->lang = true;

			// Action for list
			$this->addRowAction('edit');
			$this->addRowAction('delete');

			if (!Validate::isLoadedObject($obj = new Feature((int)$id)))
			{
				$this->errors[] = Tools::displayError('An error occurred while updating the status for an object.').'
					<b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
				return;
			}

			$this->feature_name = $obj->name;
			$this->toolbar_title = $this->feature_name[$this->context->employee->id_lang];
			$this->fields_list = array(
				'id_feature_value' => array(
					'title' => $this->l('ID'),
					'align' => 'center',
					'class' => 'fixed-width-xs'
				),
				'value' => array(
					'title' => $this->l('Value')
				),
				'parent_id_feature_value' => array(
					'title' => $this->l('ParentID'),
					'align' => 'center',
					'class' => 'fixed-width-xs'
				),
				'position' => array(
					'title' => $this->l('Position'),
					'filter_key' => 'a!position',
					'align' => 'center',
					'class' => 'fixed-width-xs',
					'position' => 'position'
				)
			);

			$this->_where = sprintf('AND `id_feature` = %d', (int)$id);
			$this->_orderBy = 'position';
			self::$currentIndex = self::$currentIndex.'&id_feature='.(int)$id.'&viewfeature';
			$this->processFilter();
			return AdminController::renderList();
		}
	}

	/**
	 * AdminController::renderForm() override
	 * @see AdminController::renderForm()
	 */
	public function initFormFeatureValue()
	{
		$this->setTypeValue();

		$parent_id = Feature::getParentFeatureID((int)Tools::getValue('id_feature'));

		$this->fields_form[0]['form'] = array(
			'legend' => array(
				'title' => $this->l('Feature value'),
				'icon' => 'icon-info-sign'
			),
			'input' => array(
				array(
					'type' => 'select',
					'label' => $this->l('Feature'),
					'name' => 'id_feature',
					'options' => array(
						'query' => Feature::getFeatures($this->context->language->id),
						'id' => 'id_feature',
						'name' => 'name'
					),
					'required' => true
				),
				array(
					'type' => 'text',
					'label' => $this->l('Value'),
					'name' => 'value',
					'lang' => true,
					'size' => 33,
					'hint' => $this->l('Invalid characters:').' <>;=#{}',
					'required' => true
				),
				array(
					'type' => 'select',
					'label' => $this->l('Parent Feature Value'),
					'name' => 'parent_id_feature_value',
					'options' => array(
						'query' => FeatureValue::getFeatureValuesWithLang($this->context->language->id, $parent_id),
						'id' => 'id_feature_value',
						'name' => 'value'
					),
					'required' => true
				),
			),
			'submit' => array(
				'title' => $this->l('Save'),
			),
			'buttons' => array(
				'save-and-stay' => array(
					'title' => $this->l('Save then add another value'),
					'name' => 'submitAdd'.$this->table.'AndStay',
					'type' => 'submit',
					'class' => 'btn btn-default pull-right',
					'icon' => 'process-icon-save'
				)
			)
		);

		$this->fields_value['id_feature'] = (int)Tools::getValue('id_feature');

		// Create Object FeatureValue
		$feature_value = new FeatureValue(Tools::getValue('id_feature_value'));

		$this->tpl_vars = array(
			'feature_value' => $feature_value,
		);

		$this->getlanguages();
		$helper = new HelperForm();
		$helper->show_cancel_button = true;

		$back = Tools::safeOutput(Tools::getValue('back', ''));
		if (empty($back))
			$back = self::$currentIndex.'&token='.$this->token;
		if (!Validate::isCleanHtml($back))
			die(Tools::displayError());

		$helper->back_url = $back;
		$helper->currentIndex = self::$currentIndex;
		$helper->token = $this->token;
		$helper->table = $this->table;
		$helper->identifier = $this->identifier;
		$helper->override_folder = 'feature_value/';
		$helper->id = $feature_value->id;
		$helper->toolbar_scroll = false;
		$helper->tpl_vars = $this->tpl_vars;
		$helper->languages = $this->_languages;
		$helper->default_form_language = $this->default_form_language;
		$helper->allow_employee_form_lang = $this->allow_employee_form_lang;
		$helper->fields_value = $this->getFieldsValue($feature_value);
		$helper->toolbar_btn = $this->toolbar_btn;
		$helper->title = $this->l('Add a new feature value');
		$this->content .= $helper->generateForm($this->fields_form);
	}

	public function ajaxProcessUpdatePositions()
	{
		if ($this->tabAccess['edit'] === '1')
		{
			$way = (int)Tools::getValue('way');
			$id = (int)Tools::getValue('id');
			$table = 'feature';
			$positions = Tools::getValue($table);
			if (empty($positions))
			{
				$table = 'feature_value';
				$positions = Tools::getValue($table);
			}

			$new_positions = array();
			foreach ($positions as $v)
				if (!empty($v))
					$new_positions[] = $v;

			foreach ($new_positions as $position => $value)
			{
				$pos = explode('_', $value);

				if (isset($pos[2]) && (int)$pos[2] === $id)
				{
					if ($table == 'feature')
					{
						if ($feature = new Feature((int)$pos[2]))
							if (isset($position) && $feature->updatePosition($way, $position, $id))
								echo 'ok position '.(int)$position.' for feature '.(int)$pos[1].'\r\n';
							else
								echo '{"hasError" : true, "errors" : "Can not update feature '.(int)$id.' to position '.(int)$position.' "}';
						else
							echo '{"hasError" : true, "errors" : "This feature ('.(int)$id.') can t be loaded"}';

						break;
					}
					elseif ($table == 'feature_value')
					{
						if ($feature_value = new FeatureValue((int)$pos[2]))
							if (isset($position) && $feature_value->updatePosition($way, $position, $id))
								echo 'ok position '.(int)$position.' for feature value '.(int)$pos[2].'\r\n';
							else
								echo '{"hasError" : true, "errors" : "Can not update feature value '.(int)$id.' to position '.(int)$position.' "}';
						else
							echo '{"hasError" : true, "errors" : "This feature value ('.(int)$id.') can t be loaded"}';

						break;
					}
				}
			}
		}
	}
}
