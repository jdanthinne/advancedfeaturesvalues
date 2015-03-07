<?php

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

			// Lang fields
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

	public function add($autodate = true, $nullValues = false)
	{
		if ($this->position <= 0)
			$this->position = FeatureValue::getHigherPosition() + 1;

		$return = parent::add($autodate, $nullValues);
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
			WHERE `id_feature_value` = '.(int)($id_feature_value ? $id_feature_value : $this->id).'
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
		$sql = "SELECT id_feature_value, position FROM "._DB_PREFIX_."feature_value ORDER BY position";
		$db = Db::getInstance();
		$r = $db->executeS($sql, false);
		$shiftTable = array(); //List of update queries (one query is necessary for each "hole" in the table)
		$currentDelta = 0;
		$minId = 0;
		$maxId = 0;
		$futurePosition = 0;
		while ($line = $db->nextRow($r))
		{
			$delta = $futurePosition - $line['position']; //Difference between current position and future position
			if ($delta != $currentDelta)
			{
				$shiftTable[] = array('minId' => $minId, 'maxId' => $maxId, 'delta' => $currentDelta);
				$currentDelta = $delta;
				$minId = $line['id_feature_value'];
			}
			$futurePosition++;
		}

		$shiftTable[] = array('minId' => $minId, 'delta' => $currentDelta);
		
		//Executing generated queries
		foreach ($shiftTable as $line)
		{
			$delta = $line['delta'];
			if ($delta == 0)
				continue;
			$delta = $delta > 0 ? '+'.(int)$delta : (int)$delta;
			$minId = $line['minId'];
			$sql = 'UPDATE '._DB_PREFIX_.'feature_value SET position = '.(int)$delta.' WHERE id_feature_value = '.(int)$minId;
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
		return (is_numeric($position)) ? $position : -1;
	}
}
