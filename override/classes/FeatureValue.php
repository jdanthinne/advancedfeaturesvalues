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

class FeatureValue extends FeatureValueCore
{
	public $position;

	public static $definition = array(
		'table' => 'feature_value',
		'primary' => 'id_feature_value',
		'multilang' => true,
		'fields' => array(
			'id_feature' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
			'position' => 	array('type' => self::TYPE_INT, 'validate' => 'isInt'),
			'custom' => 	array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
			'value' => 		array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 255),
		),
	);

	public static function getFeatureValuesWithLang($id_lang, $id_feature, $custom = false)
	{
		return Db::getInstance()->executeS('
			SELECT *
			FROM `'._DB_PREFIX_.'feature_value` v
			LEFT JOIN `'._DB_PREFIX_.'feature_value_lang` vl
				ON (v.`id_feature_value` = vl.`id_feature_value` AND vl.`id_lang` = '.(int)$id_lang.')
			WHERE v.`id_feature` = '.(int)$id_feature.'
				'.(!$custom ? 'AND (v.`custom` IS NULL OR v.`custom` = 0)' : '').'
			ORDER BY v.`position` ASC
		');
	}

	public function add($autodate = true, $null_values = false)
	{
		if ($this->position <= 0)
			$this->position = FeatureValue::getHigherPosition() + 1;

		$return = parent::add($autodate, $null_values);
		if ($return)
			Hook::exec('actionFeatureValueSave', array('id_feature_value' => $this->id));
		return $return;
	}

	public function delete()
	{
		/* Also delete related products */
		Db::getInstance()->execute('
			DELETE FROM `'._DB_PREFIX_.'feature_product`
			WHERE `id_feature_value` = '.(int)$this->id
		);
		$return = parent::delete();

		if ($return)
			Hook::exec('actionFeatureValueDelete', array('id_feature_value' => $this->id));

		/* Reinitializing position */
		$this->cleanPositions();

		return $return;
	}

	/**
	 * Move a feature value
	 * @param boolean $way Up (1)  or Down (0)
	 * @param integer $position
	 * @return boolean Update result
	 */
	public function updatePosition($way, $position, $id_feature_value = null)
	{
		if (!$res = Db::getInstance()->executeS('
			SELECT `position`, `id_feature_value`
			FROM `'._DB_PREFIX_.'feature_value`
			WHERE `id_feature_value` = '.((int)$id_feature_value ? $id_feature_value : $this->id).'
			ORDER BY `position` ASC'
		))
			return false;

		foreach ($res as $feature)
			if ((int)$feature['id_feature_value'] == (int)$this->id)
				$moved_feature = $feature;

		if (!isset($moved_feature) || !isset($position))
			return false;

		// < and > statements rather than BETWEEN operator
		// since BETWEEN is treated differently according to databases
		return (Db::getInstance()->execute('
			UPDATE `'._DB_PREFIX_.'feature_value`
			SET `position`= `position` '.($way ? '- 1' : '+ 1').'
			WHERE `position`
			'.($way
				? '> '.(int)$moved_feature['position'].' AND `position` <= '.(int)$position
				: '< '.(int)$moved_feature['position'].' AND `position` >= '.(int)$position))
		&& Db::getInstance()->execute('
			UPDATE `'._DB_PREFIX_.'feature_value`
			SET `position` = '.(int)$position.'
			WHERE `id_feature_value`='.(int)$moved_feature['id_feature_value']));
	}

	/**
	 * Reorder feature position
	 * Call it after deleting a feature.
	 *
	 * @return bool $return
	 */
	public static function cleanPositions()
	{
		//Reordering positions to remove "holes" in them (after delete for instance)
		$sql = 'SELECT id_feature_value, position FROM '._DB_PREFIX_.'feature_value ORDER BY position';
		$db = Db::getInstance();
		$r = $db->executeS($sql, false);
		$shift_table = array(); //List of update queries (one query is necessary for each "hole" in the table)
		$current_delta = 0;
		$min_id = 0;
		$max_id = 0;
		$future_position = 0;
		while ($line = $db->nextRow($r))
		{
			$delta = $future_position - $line['position']; //Difference between current position and future position
			if ($delta != $current_delta)
			{
				$shift_table[] = array('minId' => $min_id, 'maxId' => $max_id, 'delta' => $current_delta);
				$current_delta = $delta;
				$min_id = $line['id_feature_value'];
			}
			$future_position++;
		}

		$shift_table[] = array('minId' => $min_id, 'delta' => $current_delta);

		//Executing generated queries
		foreach ($shift_table as $line)
		{
			$delta = $line['delta'];
			if ($delta == 0)
				continue;
			$delta = $delta > 0 ? '+'.(int)$delta : (int)$delta;
			$min_id = $line['minId'];
			$sql = 'UPDATE '._DB_PREFIX_.'feature_value SET position = '.(int)$delta.' WHERE id_feature_value = '.(int)$min_id;
			Db::getInstance()->execute($sql);
		}
	}

	/**
	 * getHigherPosition
	 *
	 * Get the higher feature position
	 *
	 * @return integer $position
	 */
	public static function getHigherPosition()
	{
		$sql = 'SELECT MAX(`position`)
				FROM `'._DB_PREFIX_.'feature_value`';
		$position = DB::getInstance()->getValue($sql);
		if (is_numeric($position))
			$higher_position = $position;
		else
			$higher_position = -1;
		return $higher_position;
	}
}
