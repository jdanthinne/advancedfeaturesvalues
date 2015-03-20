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

if (!defined('_PS_VERSION_'))
	exit;

class AdvancedFeaturesValues extends Module
{
	public function __construct()
	{
		$this->name = 'advancedfeaturesvalues';
		$this->tab = 'administration';
		$this->version = '1.0.5';
		$this->author = 'Jérôme Danthinne';
		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.5.3', 'max' => _PS_VERSION_);
		$this->bootstrap = true;

		parent::__construct();

		$this->displayName = $this->l('Advanced Features Values');
		$this->description = $this->l('Allows multiple values selection per feature, and features values ordering.');
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
	}

	public function install()
	{
		if (!parent::install() || !$this->registerHook('header'))
			return false;
		$this->_clearCache('*');
		unlink(_PS_CACHE_DIR_.'class_index.php');

		// Alter DB PRIMARY KEY to allow multiple values
		if (!Db::getInstance()->execute('
			ALTER TABLE '._DB_PREFIX_.'feature_product DROP PRIMARY KEY;
			ALTER TABLE '._DB_PREFIX_.'feature_product ADD PRIMARY KEY (id_feature, id_product, id_feature_value);'))
			return false;
		// Add position field to feature_value
		if (!Db::getInstance()->execute('
			ALTER TABLE '._DB_PREFIX_.'feature_value ADD position INT UNSIGNED NOT NULL DEFAULT 0;'))
			return false;
		$features = Db::getInstance()->executeS('
			SELECT GROUP_CONCAT(id_feature_value ORDER BY id_feature_value) AS id_feature_value,id_feature
			FROM '._DB_PREFIX_.'feature_value GROUP BY id_feature;');
		foreach ($features as $feature)
		{
			$values = explode(',', $feature['id_feature_value']);
			foreach ($values as $position => $value)
			{
				if (!Db::getInstance()->execute('
					UPDATE '._DB_PREFIX_.'feature_value SET position = '.$position.' WHERE id_feature_value = '.$value.';'))
					return false;
			}
		}

		return true;
	}

	public function uninstall()
	{
		if (!parent::uninstall())
			return false;
		$this->_clearCache('*');
		unlink(_PS_CACHE_DIR_.'class_index.php');

		// Remove multiple values from DB and restore PRIMARY KEY
		if (!Db::getInstance()->execute('
			CREATE TABLE '._DB_PREFIX_.'feature_product_tmp as
			SELECT * FROM '._DB_PREFIX_.'feature_product WHERE 1 GROUP BY id_feature,id_product;
			TRUNCATE '._DB_PREFIX_.'feature_product;
			ALTER TABLE '._DB_PREFIX_.'feature_product DROP PRIMARY KEY;
			ALTER TABLE '._DB_PREFIX_.'feature_product ADD PRIMARY KEY (id_feature, id_product);
			INSERT INTO '._DB_PREFIX_.'feature_product
			SELECT * FROM '._DB_PREFIX_.'feature_product_tmp;
			DROP TABLE '._DB_PREFIX_.'feature_product_tmp'))
			return false;
		// Remove position field from feature_value
		if (!Db::getInstance()->execute('
			ALTER TABLE '._DB_PREFIX_.'feature_value DROP position;'))
			return false;

		return true;
	}

	public function hookHeader()
	{
		if ((isset($this->context->controller->display_column_left) && !$this->context->controller->display_column_left)
			&& (isset($this->context->controller->display_column_right) && !$this->context->controller->display_column_right))
			return false;

		if (Tools::getValue('id_category', Tools::getValue('id_category_layered', Configuration::get('PS_HOME_CATEGORY'))) == Configuration::get('PS_HOME_CATEGORY'))
			return;

		$this->context->controller->addJS(($this->_path).'views/js/blocklayered.js');
	}
}