<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
class Feature extends FeatureCore
{
 	/** @var string Name */
	public $parent_id_feature;

	/**
	 * @see ObjectModel::$definition
	 */
	public static $definition = array(
		'table' => 'feature',
		'primary' => 'id_feature',
		'multilang' => true,
		'fields' => array(
			'position' => 	array('type' => self::TYPE_INT, 'validate' => 'isInt'),
			// Parent Feature ID
			'parent_id_feature' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false),

			// Lang fields
			'name' => 		array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128),
		)
	);

	/**
	 * Get a parent feature id for a given id_feature
	 *
	 * @param integer $id_feature Feature id
	 * @return integer ID of parent feature
	 * @static
	 */
	public static function getParentFeatureID($id_feature)
	{
		return Db::getInstance()->getValue('
			SELECT parent_id_feature
			FROM `'._DB_PREFIX_.'feature` f
			WHERE f.`id_feature` = '.(int)$id_feature
		);
	}

	/**
	 * Get all features for a given language except for given id
	 *
	 * @param integer $id_lang Language id
	 * @param integer $id_feature Feature id to exclude
	 * @return array Multiple arrays with feature's data
	 * @static
	 */
	public static function getFeaturesExcept($id_lang, $id_feature, $with_shop = true)
	{
		return Db::getInstance()->executeS('
		SELECT DISTINCT f.id_feature, f.*, fl.*
		FROM `'._DB_PREFIX_.'feature` f
		'.($with_shop ? Shop::addSqlAssociation('feature', 'f') : '').'
		LEFT JOIN `'._DB_PREFIX_.'feature_lang` fl ON (f.`id_feature` = fl.`id_feature` AND fl.`id_lang` = '.(int)$id_lang.')
		WHERE f.id_feature != '.(int)$id_feature.'
		ORDER BY f.`position` ASC');
	}
}