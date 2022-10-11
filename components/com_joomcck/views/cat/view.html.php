<?php
/**
 * Joomcck by JoomBoost
 * a component for Joomla! 1.7 - 2.5 CMS (http://www.joomla.org)
 * Author Website: https://www.joomBoost.com/
 * @copyright Copyright (C) 2012 JoomBoost (https://www.joomBoost.com). All rights reserved.
 * @license   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * HTML View class for the Categories component
 *
 * @static
 * @package        Joomla.Administrator
 * @subpackage     com_categories
 */
class JoomcckViewCat extends MViewBase
{

	/**
	 * Display the view
	 */
	public function display($tpl = NULL)
	{
		global $app;
		$this->section = Factory::getApplication()->input->getInt('section_id',0);
		$this->state   = $this->get('State');
		$this->item    = $this->get('Item');
		$this->form    = $this->get('Form');

		$this->canDo         = MECAccess::getActions('category', $this->state->get('category.id'));
		$this->params_groups = array(
			'params',
			'publishing-details'
		);

		if(!$this->section)
		{
			Factory::getApplication()->enqueueMessage(JText::_('C_MSG_SELECTSECTIO'),'warning');
			Factory::getApplication()->redirect('index.php?option=com_joomcck&view=sections');


		}

		// Check for errors.
		if(count($errors = $this->get('Errors')))
		{
			throw new Exception( implode("\n", $errors),500);


		}


		//$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since    1.6
	 */
	protected function addToolbar()
	{
		// Avoid nonsense situation.
		if(!$this->section)
		{
			return;
		}
		JFactory::getApplication()->input->set('hidemainmenu', TRUE);

		$user       = JFactory::getUser();
		$isNew      = ($this->item->id == 0);
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));

		JToolBarHelper::title(($isNew ? JText::_('CNEWCATEGORY') : JText::_('CEDITCATEGORY')), ($isNew ? 'category-add.png' : 'categories.png'));

		if(!$checkedOut)
		{
			JToolBarHelper::apply('category.apply', 'JTOOLBAR_APPLY');
			JToolBarHelper::save('category.save', 'JTOOLBAR_SAVE');
			JToolBarHelper::custom('category.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', FALSE);
			if(!$isNew)
			{
				JToolBarHelper::custom('category.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', FALSE);
			}
		}
		JToolBarHelper::cancel('category.cancel', 'JTOOLBAR_CANCEL');
		MRToolBar::helpW('http://help.JoomBoost.com/joomcck/index.html?category.htm', 1000, 500);
		JToolBarHelper::divider();

	}
}
