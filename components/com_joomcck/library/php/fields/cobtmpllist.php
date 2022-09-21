<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') || die;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.path');

JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of files
 *
 * @since  1.7.0
 */
class JFormFieldCobTmplList extends JFormFieldList
{
    /**
     * The form field type.
     *
     * @var string
     * @since  1.7.0
     */
    protected $type = 'CobTmplList';

    /**
     * The filter.
     *
     * @var string
     * @since  3.2
     */
    protected $filter;

    /**
     * The exclude.
     *
     * @var string
     * @since  3.2
     */
    protected $exclude;

    /**
     * The hideNone.
     *
     * @var boolean
     * @since  3.2
     */
    protected $hideNone = false;

    /**
     * The hideDefault.
     *
     * @var boolean
     * @since  3.2
     */
    protected $hideDefault = false;

    /**
     * The stripExt.
     *
     * @var boolean
     * @since  3.2
     */
    protected $stripExt = false;

    /**
     * The directory.
     *
     * @var string
     * @since  3.2
     */
    protected $directory;

    /**
     * Method to get certain otherwise inaccessible properties from the form field object.
     *
     * @since   3.2
     *
     * @param  string $name The property name for which to get the value.
     * @return mixed  The property value or null.
     */

    protected function getInput()
    {
        $html = [];
        $attr = '';

        // Initialize some field attributes.
        $attr .= !empty($this->class) ? ' class="' . $this->class . '"' : '';
        $attr .= !empty($this->size) ? ' size="' . $this->size . '"' : '';
        $attr .= $this->multiple ? ' multiple' : '';
        $attr .= $this->required ? ' required aria-required="true"' : '';
        $attr .= $this->autofocus ? ' autofocus' : '';

        // To avoid user's confusion, readonly="true" should imply disabled="true".
        if ((string) $this->readonly == '1' || (string) $this->readonly == 'true' || (string) $this->disabled == '1' || (string) $this->disabled == 'true') {
            $attr .= ' disabled="disabled"';
        }

        // Initialize JavaScript field attributes.
        $attr .= $this->onchange ? ' onchange="' . $this->onchange . '"' : '';

        // Get the field options.
        $options = (array) $this->getOptions();

        // Create a read-only list (no name) with hidden input(s) to store the value(s).
        if ((string) $this->readonly == '1' || (string) $this->readonly == 'true') {
            $html[] = JHtml::_('select.genericlist', $options, '', trim($attr), 'value', 'text', $this->value, $this->id);

            // E.g. form field type tag sends $this->value as array
            if ($this->multiple && is_array($this->value)) {
                if (!count($this->value)) {
                    $this->value[] = '';
                }

                foreach ($this->value as $value) {
                    $html[] = '<input type="hidden" name="' . $this->name . '" value="' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '"/>';
                }
            } else {
                $html[] = '<input type="hidden" name="' . $this->name . '" value="' . htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '"/>';
            }
        } else
        // Create a regular list passing the arguments in an array.
        {
            $listoptions                   = [];
            $listoptions['option.key']     = 'value';
            $listoptions['option.text']    = 'text';
            $listoptions['list.select']    = $this->value;
            $listoptions['id']             = $this->id;
            $listoptions['list.translate'] = false;
            $listoptions['option.attr']    = 'optionattr';
            $listoptions['list.attr']      = trim($attr);

            $html[] = JHtml::_('select.genericlist', $options, $this->name, $listoptions);
		}
		
		//JHtmlSelect::genericlist()
		$path = $this->directory;

        if (!is_dir($path)) {
            $path = JPATH_ROOT . '/' . $path;
        }

		$path = JPath::clean($path);
		$xml = $path.DIRECTORY_SEPARATOR.str_replace('.php', '.xml', $this->value ?: $this->default);
		if(JFile::exists($xml)) {
			$icon = HTMLFormatHelper::icon('gear.png');
			$parts = explode('/', $this->directory);
			$title = JText::sprintf('COB_FIEL_PARAMS', ucfirst($parts[6]));

			$form = MFormHelper::getFieldParams($xml, JFactory::getApplication()->input->get('id'), $this->value ?: $this->default);

			$html[] = <<<EOT
			<a href="#config_$this->id" role="button" class="btn btn-mini" data-toggle="modal">$icon</a>
			<div id="config_$this->id" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="mtitle_$this->id" aria-hidden="true">
			<div class="modal-header">
			  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			  <h3 id="mtitle_$this->id">$title</h3>
			</div>
			<div class="modal-body" style=" overflow-Y: scroll;">
			  $form
			</div>
			<div class="modal-footer">
			  <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
			</div>
		  </div>
EOT;
		}

        return '<div style="white-space: nowrap; display: inline-block;">'.str_replace('<select ', '<select style="width: auto;" ', implode($html)).'</div>';
    }

    public function __get($name)
    {
        switch ($name) {
            case 'filter':
            case 'exclude':
            case 'hideNone':
            case 'hideDefault':
            case 'stripExt':
            case 'directory':
                return $this->$name;
        }

        return parent::__get($name);
    }

    /**
     * Method to set certain otherwise inaccessible properties of the form field object.
     *
     * @since   3.2
     *
     * @param  string $name  The property name for which to set the value.
     * @param  mixed  $value The value of the property.
     * @return void
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'filter':
            case 'directory':
            case 'exclude':
                $this->$name = (string) $value;
                break;

            case 'hideNone':
            case 'hideDefault':
            case 'stripExt':
                $value       = (string) $value;
                $this->$name = ($value === 'true' || $value === $name || $value === '1');
                break;

            default:
                parent::__set($name, $value);
        }
    }

    /**
     * Method to attach a JForm object to the field.
     *
     *                                      For example if the field has name="foo" and the group value is set to "bar" then the
     *                                      full field name would end up being "bar[foo]".
     * @see     JFormField::setup()
     * @since   3.2
     *
     * @param  SimpleXMLElement $element The SimpleXMLElement object representing the `<field>` tag for the form field object.
     * @param  mixed            $value   The form field value to validate.
     * @param  string           $group   The field name group control value. This acts as an array container for the field.
     * @return boolean          True on success.
     */
    public function setup(SimpleXMLElement $element, $value, $group = null)
    {
        $return = parent::setup($element, $value, $group);

        if ($return) {
            $this->filter  = (string) $this->element['filter'];
            $this->exclude = (string) $this->element['exclude'];

            $hideNone       = (string) $this->element['hide_none'];
            $this->hideNone = ($hideNone == 'true' || $hideNone == 'hideNone' || $hideNone == '1');

            $hideDefault       = (string) $this->element['hide_default'];
            $this->hideDefault = ($hideDefault == 'true' || $hideDefault == 'hideDefault' || $hideDefault == '1');

            $stripExt       = (string) $this->element['stripext'];
            $this->stripExt = ($stripExt == 'true' || $stripExt == 'stripExt' || $stripExt == '1');

            // Get the path in which to search for file options.
            $this->directory = (string) $this->element['directory'];
        }

        return $return;
    }

    /**
     * Method to get the list of files for the field options.
     * Specify the target directory with a directory attribute
     * Attributes allow an exclude mask and stripping of extensions from file name.
     * Default attribute may optionally be set to null (no file) or -1 (use a default).
     *
     * @since   1.7.0
     *
     * @return array The field option objects.
     */
    protected function getOptions()
    {
        $options = [];

        $path = $this->directory;

        if (!is_dir($path)) {
            $path = JPATH_ROOT . '/' . $path;
        }

        $path = JPath::clean($path);

        // Prepend some default options based on field attributes.
        if (!$this->hideNone) {
            $options[] = JHtml::_('select.option', '-1', JText::alt('JOPTION_DO_NOT_USE', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)));
        }

        if (!$this->hideDefault) {
            $options[] = JHtml::_('select.option', '', JText::alt('JOPTION_USE_DEFAULT', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)));
        }

        // Get a list of files in the search path with the given filter.
        $files = JFolder::files($path, $this->filter);

        // Build the options list from the list of files.
        if (is_array($files)) {
            foreach ($files as $file) {
                // Check to see if the file is in the exclude mask.
                if ($this->exclude) {
                    if (preg_match(chr(1) . $this->exclude . chr(1), $file)) {
                        continue;
                    }
                }

                // If the extension is to be stripped, do it.
                if ($this->stripExt) {
                    $file = JFile::stripExt($file);
                }

                $options[] = JHtml::_('select.option', $file, $file);
            }
        }

        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $options);

        return $options;
    }
}