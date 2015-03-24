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
