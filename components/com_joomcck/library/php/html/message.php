<?php
/**
 * Joomcck by JoomBoost
 * a component for Joomla! 1.7 - 2.5 CMS (http://www.joomla.org)
 * Author Website: https://www.joomBoost.com/
 * @copyright Copyright (C) 2012 JoomBoost (https://www.joomBoost.com). All rights reserved.
 * @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die('Restricted access');

class JHTMLMessage
{
	public static function box($msg, $type = 'normal')
	{
		$html = sprintf('<span class="me_msg_%s"><span><span><span> %s  </span></span></span></span>', $type, $msg);
		return $html;
	}
}
?>