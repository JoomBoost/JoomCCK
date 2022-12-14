<?php
/**
 * Joomcck by JoomBoost
 * a component for Joomla! 1.7 - 2.5 CMS (http://www.joomla.org)
 * Author Website: https://www.joomBoost.com/
 * @copyright Copyright (C) 2012 JoomBoost (https://www.joomBoost.com). All rights reserved.
 * @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
defined('_JEXEC') or die();
$default = array_shift($this->default);

$list = array();
foreach($this->values as $k => $value)
{
	if (!$value->field_value)
		continue;
	$c = explode('^', $value->field_value);
	ArrayHelper::clean_r($c);
	$label = JText::_(strip_tags($c[0]));

	$list[$k] = new stdClass();
	$list[$k]->text = $label;
	if ($this->params->get('params.filter_show_number', 1))
	{
		$list[$k]->text .= " ({$value->num})";
	}
	$list[$k]->value = $value->field_value;
}

array_unshift($list, JHtml::_('select.option', '', JText::sprintf('CCSELETFIELD', $this->label)));

echo JHtml::_('select.genericlist', $list, "filters[{$this->key}][value]", null, 'value', 'text', $default);
?>