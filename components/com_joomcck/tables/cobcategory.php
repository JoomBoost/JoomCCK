<?php
/**
 * Joomcck by JoomBoost
 * a component for Joomla! 1.7 - 2.5 CMS (http://www.joomla.org)
 * Author Website: https://www.joomBoost.com/
 * @copyright Copyright (C) 2012 JoomBoost (https://www.joomBoost.com). All rights reserved.
 * @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.database.tablenested');

class JoomcckTableCobCategory extends JTableNested
{
	public static $root_id = 0;

	public function __construct(&$_db)
	{
		parent::__construct('#__js_res_categories', 'id', $_db);
		
		$this->access = (int)JFactory::getConfig()->get('access');
		$this->section_id = JFactory::getApplication()->input->getInt('section_id');
		$this->_option = 'com_joomcck';
	}

	protected function _getAssetName()
	{
		$k = $this->_tbl_key;
		return $this->_option . '.category.' . (int)$this->$k;
	}

	protected function _getAssetTitle()
	{
		return $this->title;
	}

	protected function _getAssetParentId(JTable $table = null, $id = null)
	{
		// Initialise variables.
		$assetId = null;
		$db = $this->getDbo();
		
		// This is a category under a category.
		if($this->parent_id > 1)
		{
			// Build the query to get the asset id for the parent category.
			$query = $db->getQuery(true);
			$query->select('asset_id');
			$query->from('#__js_res_categories');
			$query->where('id = ' . (int)$this->parent_id);
			
			// Get the asset id from the database.
			$db->setQuery($query);
			if($result = $db->loadResult())
			{
				$assetId = (int)$result;
			}
		} // This is a category that needs to parent with the extension.
		elseif($assetId === null)
		{
			// Build the query to get the asset id for the parent category.
			$query = $db->getQuery(true);
			$query->select('id');
			$query->from('#__assets');
			$query->where('name = ' . $db->quote($this->section_id));
			
			// Get the asset id from the database.
			$db->setQuery($query);
			if($result = $db->loadResult())
			{
				$assetId = (int)$result;
			}
		}
		
		// Return the asset id.
		if($assetId)
		{
			return $assetId;
		}
		else
		{
			return parent::_getAssetParentId($table, $id);
		}
	}

	public function bind($array, $ignore = '')
	{
		if(isset($array['params']) && is_array($array['params']))
		{
			$registry = new JRegistry();
			$registry->loadArray($array['params']);
			$array['params'] = (string)$registry;
		}
		
		if(isset($array['metadata']) && is_array($array['metadata']))
		{
			$registry = new JRegistry();
			$registry->loadArray($array['metadata']);
			$array['metadata'] = (string)$registry;
		}
		
		// Bind the rules.
		if(isset($array['rules']) && is_array($array['rules']))
		{
			$rules = new JAccessRules($array['rules']);
		}
		else
		{
			$rules = new JAccessRules(array());
		}
		$this->setRules($rules);
		
		return parent::bind($array, $ignore);
	}

	public function check()
	{
		if(trim($this->title) == '')
		{
			$this->setError(JText::_('C_MSG_TITLENOTSET'));
			return false;
		}
		$this->alias = trim($this->alias);
		if(empty($this->alias))
		{
			$this->alias = $this->title;
		}
		
		$this->alias = \Joomla\CMS\Application\ApplicationHelper::stringURLSafe($this->alias);
		if(trim(str_replace('-', '', $this->alias)) == '')
		{
			$this->alias = JFactory::getDate()->format('Y-m-d-H-i-s');
		}
		//$this->relative_cats = null;
		if(is_array($this->relative_cats) && count($this->relative_cats))
		{
			$rel_cat = JTable::getInstance('CobCategory', 'JoomcckTable');
			$cats = array();
			foreach($this->relative_cats as $i => $cid)
			{
				$rel_cat->reset();
				$rel_cat->load($cid);
				$cats[$i]->id = $rel_cat->id;
				$cats[$i]->title = $rel_cat->title;
				$cats[$i]->path = $rel_cat->path;
			}
			$this->relative_cats = json_encode($cats);
		} elseif(is_array($this->relative_cats)) {
			$this->relative_cats = '';
		}
		return true;
	}

	public function store($updateNulls = false)
	{
		$date = JFactory::getDate();
		$user = JFactory::getUser();
		
		if($this->id)
		{
			// Existing category
			$this->modified_time = $date->toSql();
			$this->modified_user_id = $user->get('id');
		}
		else
		{
			// New category
			$this->created_time = $date->toSql();
			$this->created_user_id = $user->get('id');
		}
		// Verify that the alias is unique
		$table = JTable::getInstance('CobCategory', 'JoomcckTable');
		if($table->load(array(
			'alias' => $this->alias, 
			'parent_id' => $this->parent_id, 
			'section_id' => $this->section_id
		)) && ($table->id != $this->id || $this->id == 0))
		{
			
			$this->setError(JText::_('JLIB_DATABASE_ERROR_CATEGORY_UNIQUE_ALIAS'));
			return false;
		}
		//echo '<pre>';print_r($table); die;
		return parent::store($updateNulls);
	}
	
	
	/**
	 * Method to get an array of nodes from a given node to its root.
	 *
	 * @param   integer  $pk          Primary key of the node for which to get the path.
	 * @param   boolean  $diagnostic  Only select diagnostic data for the nested sets.
	 *
	 * @return  mixed    Boolean false on failure or array of node objects on success.
	 *
	 * @link    http://docs.joomla.org/JTableNested/getPath
	 * @since   11.1
	 */
	public function getPath($pk = null, $diagnostic = false)
	{
		// Initialise variables.
		$k = $this->_tbl_key;
		$pk = (is_null($pk)) ? $this->$k : $pk;

		// Get the path from the node to the root.
		$query = $this->_db->getQuery(true);
		$select = ($diagnostic) ? 'p.' . $k . ', p.parent_id, p.level, p.lft, p.rgt' : 'p.*';
		$query->select($select);
		$query->from($this->_tbl . ' AS n, ' . $this->_tbl . ' AS p');
		$query->where('n.lft BETWEEN p.lft AND p.rgt');
		$query->where('n.' . $k . ' = ' . (int) $pk);
		$query->where('p.section_id = ' . $this->section_id);
		$query->order('p.lft');

		$this->_db->setQuery($query);
		$path = $this->_db->loadObjectList();

		// Check for a database error.
		if ($this->_db->getErrorNum())
		{
			$e = new JException(JText::sprintf('JLIB_DATABASE_ERROR_GET_PATH_FAILED', get_class($this), $this->_db->getErrorMsg()));
			$this->setError($e);
			return false;
		}

		return $path;
	}
	
	
/**
	 * Method to rebuild the node's path field from the alias values of the
	 * nodes from the current node to the root node of the tree.
	 *
	 * @param   integer  $pk  Primary key of the node for which to get the path.
	 *
	 * @return  boolean  True on success.
	 *
	 * @link    http://docs.joomla.org/JTableNested/rebuildPath
	 * @since   11.1
	 */
	public function rebuildPath($pk = null)
	{
		// If there is no alias or path field, just return true.
		if (!property_exists($this, 'alias') || !property_exists($this, 'path'))
		{
			return true;
		}

		// Initialise variables.
		$k = $this->_tbl_key;
		$pk = (is_null($pk)) ? $this->$k : $pk;

		// Get the aliases for the path from the node to the root node.
		$query = $this->_db->getQuery(true);
		$query->select('p.alias');
		$query->from($this->_tbl . ' AS n, ' . $this->_tbl . ' AS p');
		$query->where('n.lft BETWEEN p.lft AND p.rgt');
		$query->where('n.' . $this->_tbl_key . ' = ' . (int) $pk);
		$query->where('p.section_id = ' . $this->section_id);
		$query->order('p.lft');
		$this->_db->setQuery($query);

		$segments = $this->_db->loadColumn();

		// Make sure to remove the root path if it exists in the list.
		if ($segments[0] == 'root')
		{
			array_shift($segments);
		}

		// Build the path.
		$path = trim(implode('/', $segments), ' /\\');

		// Update the path field for the node.
		$query = $this->_db->getQuery(true);
		$query->update($this->_tbl);
		$query->set('path = ' . $this->_db->quote($path));
		$query->where($this->_tbl_key . ' = ' . (int) $pk);
		$this->_db->setQuery($query);

		// Check for a database error.
		if (!$this->_db->query())
		{
			$e = new JException(JText::sprintf('JLIB_DATABASE_ERROR_REBUILDPATH_FAILED', get_class($this), $this->_db->getErrorMsg()));
			$this->setError($e);
			return false;
		}

		// Update the current record's path to the new one:
		$this->path = $path;

		return true;
	}
	
	public function delete($pk = null, $children = true)
	{
		$res = parent::delete($pk);
		if($res)
		{
			$db = $this->getDbo();
			$query = 'SELECT id,record_id FROM #__js_res_record_category WHERE catid = '.$pk;
			$db->setQuery($query);
			$result = $db->loadObjectList('id');
			
			$rids = array();
			if(!empty($result))
			{
				foreach ($result as $key => $value)
				{
					$rids[] = $value->record_id;
				}
				$query = 'DELETE FROM #__js_res_record_category WHERE id IN ('.implode(',', array_keys($result)).')';
				$db->setQuery($query);
				$db->execute();
				
				$query = 'SELECT id,categories FROM #__js_res_record WHERE id IN ('.implode(',', $rids).')';
				$db->setQuery($query);
				$records = $db->loadObjectList('id');
				if(!empty($records))
				{
					foreach ($records as $key => $record)
					{
						$cats = json_decode($record->categories, true);
						unset($cats[$pk]);
						$str = json_encode($cats);
						$query = 'UPDATE #__js_res_record SET categories = "'.$db->escape($str).'" WHERE id = ' . $key;
		 				$db->setQuery($query);
		 				$db->execute();
					}
				}
			}
		}
		
		return $res;
	}
}
?>
