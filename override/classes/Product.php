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
            if (Module::isInstalled('blocklayered') && Module::isEnabled('blocklayered')) {
                Db::getInstance(_PS_USE_SQL_SLAVE_)->execute('SET @@group_concat_max_len = 4096');
                self::$_frontFeaturesCache[$id_product.'-'.$id_lang] = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
                    SELECT `name`, GROUP_CONCAT(value ORDER BY `fv`.`position` SEPARATOR ", ") AS `value`, `pf`.`id_feature`, `liflv`.`url_name`, `liflv`.`meta_title`
                    FROM `'._DB_PREFIX_.'feature_product` `pf`
                    LEFT JOIN `'._DB_PREFIX_.'feature_lang` `fl` ON (`fl`.`id_feature` = `pf`.`id_feature` AND `fl`.`id_lang` = '.(int)$id_lang.')
                    LEFT JOIN `'._DB_PREFIX_.'feature_value_lang` `fvl` ON (`fvl`.`id_feature_value` = `pf`.`id_feature_value` AND `fvl`.`id_lang` = '.(int)$id_lang.')
                    LEFT JOIN `'._DB_PREFIX_.'feature` `f` ON (`f`.`id_feature` = `pf`.`id_feature` AND `fl`.`id_lang` = '.(int)$id_lang.')
                    LEFT JOIN '._DB_PREFIX_.'feature_value `fv` ON `fv`.`id_feature_value` = `pf`.`id_feature_value`
                    LEFT JOIN `'._DB_PREFIX_.'layered_indexable_feature_lang_value` `liflv` ON (`f`.`id_feature` = `liflv`.`id_feature` AND `liflv`.`id_lang` = '.(int)$id_lang.')
                    '.Shop::addSqlAssociation('feature', 'f').'
                    WHERE pf.`id_product` = '.(int)$id_product.'
                    GROUP BY `name`, pf.`id_feature`
                    ORDER BY f.`position` ASC'
                );
            } else {
                Db::getInstance(_PS_USE_SQL_SLAVE_)->execute('SET @@group_concat_max_len = 4096');
                self::$_frontFeaturesCache[$id_product.'-'.$id_lang] = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
                    SELECT `name`, GROUP_CONCAT(value ORDER BY `fv`.`position` SEPARATOR ", ") AS `value`, `pf`.`id_feature`
                    FROM '._DB_PREFIX_.'feature_product `pf`
                    LEFT JOIN '._DB_PREFIX_.'feature_lang `fl` ON (`fl`.`id_feature` = `pf`.`id_feature` AND `fl`.`id_lang` = '.(int)$id_lang.')
                    LEFT JOIN '._DB_PREFIX_.'feature_value_lang `fvl` ON (`fvl`.`id_feature_value` = `pf`.`id_feature_value` AND `fvl`.`id_lang` = '.(int)$id_lang.')
                    LEFT JOIN '._DB_PREFIX_.'feature `f` ON `f`.`id_feature` = `pf`.`id_feature`
                    LEFT JOIN '._DB_PREFIX_.'feature_value `fv` ON `fv`.`id_feature_value` = `pf`.`id_feature_value`
                    '.Shop::addSqlAssociation('feature', 'f').'
                    WHERE pf.id_product = '.(int)$id_product.'
                    GROUP BY `name`, pf.`id_feature`
                    ORDER BY f.`position` ASC'
                );
            }
        }
        return self::$_frontFeaturesCache[$id_product.'-'.$id_lang];
    }

}
