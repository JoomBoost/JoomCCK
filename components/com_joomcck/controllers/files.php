<?php

/**
 * Joomcck by JoomBoost
 * a component for Joomla! 1.7 - 2.5 CMS (http://www.joomla.org)
 * Author Website: https://www.joomBoost.com/
 *
 * @copyright Copyright (C) 2012 JoomBoost (https://www.joomBoost.com). All rights reserved.
 * @license   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\Filesystem\Folder;

defined('_JEXEC') or die();
define('DS', DIRECTORY_SEPARATOR);

jimport('mint.resizeimage');
jimport('mint.mvc.controller.admin');
class JoomcckControllerFiles extends MControllerAdmin
{

    public function __construct($config = array())
    {
        parent::__construct($config);

        if (!$this->input) {
            $this->input = JFactory::getApplication()->input;
        }
    }

    public function reindex()
    {
        $section_id = 1;
        $field_id   = 2;

        $db = JFactory::getDbo();

        $db->setQuery("SELECT id, fields FROM #__js_res_record WHERE section_id = " . $section_id);
        $records = $db->loadObjectList();

        foreach ($records as $record) {
            $data = json_decode($record->fields, TRUE);
            $db->setQuery("SELECT id, filename, realname, ext, size, title, description, width, height, fullpath, params
			FROM #__js_res_files
			WHERE record_id = {$record->id} AND field_id = {$field_id}");

            $data[$field_id] = $db->loadAssocList();
            $data            = json_encode($data);

            $db->setQuery("UPDATE #__js_res_record SET fields = '{$data}' WHERE id = " . $record->id);
            $db->query();
        }
    }

    public function download()
    {


        $return = Url::get_back('return');
        $params = JComponentHelper::getParams('com_joomcck');

        $files    = JTable::getInstance('Files', 'JoomcckTable');
        $field_id = $this->input->getInt('fid');
        if (!$field_id) {
            JError::raiseWarning(403, JText::_('CERRNOFILEID'));
            $this->setRedirect($return);

            return;
        }

        JTable::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . 'tables/field.php');
        $field_table = JTable::getInstance('Field', 'JoomcckTable');
        $field_table->load($field_id);
        if (!$field_table->id) {
            JError::raiseWarning(403, JText::_('CERRNOFILED'));
            $this->setRedirect($return);

            return;
        }

        $field_path = JPATH_ROOT . '/components/com_joomcck/fields' . DIRECTORY_SEPARATOR . $field_table->field_type . DIRECTORY_SEPARATOR . $field_table->field_type . '.php';
        if (!JFile::exists($field_path)) {
            JError::raiseWarning(403, JText::_('CERRNOFILEDFILE'));
            $this->setRedirect($return);

            return;
        }

        $record_id = $this->input->getInt('rid');
        if (!$record_id) {
            JError::raiseWarning(403, JText::_('CERRNORECORDID'));
            $this->setRedirect($return);

            return;
        }

        $record_model = MModelBase::getInstance('Record', 'JoomcckModel');
        $record       = $record_model->getItem($record_id);
        if (!$record->id) {
            JError::raiseWarning(403, JText::_('CERRNORECORD'));
            $this->setRedirect($return);

            return;
        }
        $values  = json_decode($record->fields, TRUE);
        $default = @$values[$field_id];

        require_once $field_path;

        $classname = 'JFormFieldC' . ucfirst($field_table->field_type);

        if (!class_exists($classname)) {
            JError::raiseWarning(403, JText::_('CCLASSNOTFOUND') . ': ' . $classname);
            $this->setRedirect($return);

            return;
        }

        $fieldclass = new $classname($field_table, $default);

        if (!method_exists($fieldclass, 'onBeforeDownload')) {
            JError::raiseWarning(403, JText::_('CERRCANNOTPROCESSFILE'));
            $this->setRedirect($return);

            return;
        }

        $fieldclass->onBeforeDownload($record, $this->input->get('fidx', 0), 0);

        if ($fieldclass->getErrors()) {
            JError::raiseWarning(403, implode("</li><li>", $fieldclass->getErrors()));
            $this->setRedirect($return);

            return;
        }

        if ($this->input->get('id', 0) || $this->input->get('file')) {
            $id = $this->input->get('id', 0);
            if ($id) {
                $files->load($id);
            } else {
                $files->load(
                    array(
                        'filename' => $this->input->get('file')
                    )
                );
            }

            $subfolder = $fieldclass->params->get('params.subfolder', $files->ext);
            if (!JFile::exists(JPATH_ROOT . "/" . $params->get('general_upload') . "/{$subfolder}/{$files->fullpath}")) {
                JError::raiseWarning(403, JText::_('CERRNOFILEHDD'));
                $this->setRedirect($return);

                return;
            }
            $download_file = JPATH_ROOT . DIRECTORY_SEPARATOR . $params->get('general_upload') . DIRECTORY_SEPARATOR . $subfolder . DIRECTORY_SEPARATOR . $files->fullpath;

            $files->hit();
        } else {
            $value     = $fieldclass->__get('value');
            $fileslist = (array) (isset($value['files']) ? $value['files'] : $value);
            if (!$fileslist) {
                JError::raiseWarning(403, JText::_('CERRNOFILEHDD'));
                $this->setRedirect($return);

                return;
            }

            $zip_filename = JPATH_CACHE . DIRECTORY_SEPARATOR . strtolower(sprintf('UNPACK_FIRST_%s.zip', $record->title));
            $zipper       = new ZipArchive();
            $zipper->open($zip_filename, ZIPARCHIVE::CREATE);

            foreach ($fileslist as $file) {
                $subfolder = $fieldclass->params->get('params.subfolder', $file['ext']);
                $add       = JPath::clean(JPATH_ROOT . DIRECTORY_SEPARATOR . $params->get('general_upload') . DIRECTORY_SEPARATOR . $subfolder . DIRECTORY_SEPARATOR . $file['fullpath']);
                $zipper->addFile($add, $file['realname']);
            }

            $zipper->close();

            $files->realname = basename($zip_filename);
            $files->size     = filesize($zip_filename);

            $download_file = $zip_filename;
        }

        $browser = @$_SERVER['HTTP_USER_AGENT'];

        if (@preg_match('/Opera(/| )([0-9].[0-9]{1,2})/iU', $browser)) {
            $browser = 'OPERA';
        } elseif (@preg_match('/MSIE ([0-9].[0-9]{1,2})/iU', $browser)) {
            $browser = 'IE';
        } elseif (@preg_match('/OmniWeb/([0-9].[0-9]{1,2})/iU', $browser)) {
            $browser = 'OMNIWEB';
        } elseif (@preg_match('/(Konqueror/)(.*)/iU', $browser)) {
            $browser = 'KONQUEROR';
        } elseif (@preg_match('/Mozilla/([0-9].[0-9]{1,2})/iU', $browser)) {
            $browser = 'MOZILLA';
        } else {
            $browser = 'OTHER';
        }

        header('Content-Type: ' . (($browser == 'IE' || $browser == 'OPERA') ? 'application/octetstream' : 'application/octet-stream'));
        header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . $files->size);
        header('Content-Encoding: none');
        header('Content-Disposition: attachment; filename="' . $files->realname . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        if ($browser == 'IE') {
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0', TRUE);
            header('Pragma: public', TRUE);
        }


        $this->_readfileChunked($download_file);

        exit();
    }

    function _readfileChunked($filename)
    {
        $chunksize = 1 * (1024 * 1024); // how many bytes per chunk
        $handle    = fopen($filename, 'rb');
        while (!feof($handle)) {
            $buffer = fread($handle, $chunksize);
            echo $buffer;
            ob_flush();
            flush();
        }

        return fclose($handle);
    }


    public function download_attach()
    {
        $params = JComponentHelper::getParams('com_joomcck');

        $files = JTable::getInstance('Files', 'JoomcckTable');
        if ($id = $this->input->get('id', 0)) {
            $files->load($id);
        }
        if (!$files->id) {
            JError::raiseWarning(403, JText::_('CERRNOFILEID'));

            return;
        }

        $download_file = JPATH_ROOT . DIRECTORY_SEPARATOR . $params->get('general_upload') . DS . 'comment'. DS . $files->fullpath;
        if (!JFile::exists($download_file)) {
            JError::raiseWarning(403, JText::_('CERRNOFILEHDD'));

            return;
        }

        $browser = $_SERVER['HTTP_USER_AGENT'];

        if (@preg_match('/Opera(/| )([0-9].[0-9]{1,2})/iU', $browser)) {
            $browser = 'OPERA';
        } elseif (@preg_match('/MSIE ([0-9].[0-9]{1,2})/iU', $browser)) {
            $browser = 'IE';
        } elseif (@preg_match('/OmniWeb/([0-9].[0-9]{1,2})/iU', $browser)) {
            $browser = 'OMNIWEB';
        } elseif (@preg_match('/(Konqueror/)(.*)/iU', $browser)) {
            $browser = 'KONQUEROR';
        } elseif (@preg_match('/Mozilla/([0-9].[0-9]{1,2})/iU', $browser)) {
            $browser = 'MOZILLA';
        } else {
            $browser = 'OTHER';
        }

        header('Content-Type: ' . (($browser == 'IE' || $browser == 'OPERA') ? 'application/octetstream' : 'application/octet-stream'));
        header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . $files->size);
        header('Content-Encoding: none');
        header('Content-Disposition: attachment; filename="' . $files->realname . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        if ($browser == 'IE') {
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0', TRUE);
            header('Pragma: public', TRUE);
        }

        $this->_readfileChunked($download_file);

        $files->hit();
    }
    public function upload()
    {
        require_once JPATH_ROOT . '/media/mint/vendors/flow/php/src/Flow/Autoloader.php';

        $loader = new Flow\Autoloader();
        $loader->autoload('Flow\ConfigInterface');
        $loader->autoload('Flow\Config');
        $loader->autoload('Flow\RequestInterface');
        $loader->autoload('Flow\Request');
        $loader->autoload('Flow\Basic');
        $loader->autoload('Flow\File');

        $config = new Flow\Config();
        $config->setTempDir(JPATH_ROOT . '/tmp/chunks');

        if (!JFolder::exists($config->getTempDir())) {
            JFolder::create($config->getTempDir());
        }

        if($this->input->getInt('iscomment')){
            $record = ItemsStore::getRecord($this->input->getInt('field_id'));
            $type = ItemsStore::getType($record->type_id);

            $field = new stdClass();
            $field->id = $this->input->getInt('field_id');
            $field->field_type = 'comment';
            $field->params = new JRegistry([
                "params" => [
                    "file_formats" => $type->params->get('comments.comments_allowed_formats'),
                    "max_size" => $type->params->get('comments.comments_attachment_max')
                ]
            ]);
        } else {
            $field = JTable::getInstance('Field', 'JoomcckTable');
            $field->load($this->input->getInt('field_id'));
            if (!$field->id) {
                echo json_encode(['error' => 1, 'msg' => 'Cannot load field: ' . $this->input->getInt('field_id')]);
                JFactory::getApplication()->close();
                return;
            }
            $field->params = new JRegistry($field->params);
        }


        $request = new Flow\Request();

        $max_upload =  $field->params->get('params.max_size', 2097152);
        $max_post     = $this->_convert_size(ini_get('post_max_size'));
        $memory_limit = $this->_convert_size(ini_get('memory_limit'));
        $limit        = min($max_upload, $max_post, $memory_limit);

        if ($limit < $request->getTotalSize()) {
            echo json_encode(['error' => 1, 'msg' => 'Size is bigger than: ' . $limit]);
            JFactory::getApplication()->close();
            return;
        }

        $filename =  $request->getFileName();
        $ext  = \Joomla\String\StringHelper::strtolower(JFile::getExt($filename));
        $exts    = explode(',', str_replace(' ', '', $field->params->get('params.file_formats', 'zip, jpg, png, jpeg, gif, txt, md, bmp')));

        if (!in_array($ext, $exts)) {
            echo json_encode(['error' => 1, 'msg' => 'Not allowed extension <b>' . $ext . '</b>: ' . implode(', ', $exts)]);
            JFactory::getApplication()->close();
            return;
        }


        $src = JPATH_ROOT . '/tmp/' . $request->getFileName();
        $time = time();
        $upload_name = $time . "_" . md5($request->getFileName() . $request->getTotalSize()) . "." . $ext;

        if (\Flow\Basic::save($src, $config, $request)) {
            $save = $this->savefile($request->getFileName(), $src, $upload_name, $field);
            if ($save['id']) {
                JFile::delete($src);
                if($this->input->get('record_id') && !$this->input->getInt('iscomment')) {
                    $record = JTable::getInstance('Record', 'JoomcckTable');
                    $record->load($this->input->get('record_id'));
                    if($record->id) {
                        $fields = json_decode($record->fields, TRUE);
                        $fields[$field->id][] = $save;
                        $record->fields = json_encode($fields);
                        $record->store();

                        $rv = JTable::getInstance('Record_values', 'JoomcckTable');
                        $rv->store_value($save['realname'], NULL, $record, $field);

                        $file_table = JTable::getInstance('Files', 'JoomcckTable');
                        $file_table->load($save['id']);
                        $file_table->saved = 1;
                        $file_table->store();
                    }
                }
            }
        } else {
            echo json_encode(['error' => 2, 'msg' => 'Couldnot save: ' . $src]);
            JFactory::getApplication()->close();
            return;
        }

        echo json_encode($save);

        JFactory::getApplication()->close();
    }

    public function mooupload()
    {

        require_once JPATH_ROOT . '/media/mint/js/mooupload/mooupload.php';

        $upload   = new Mooupload();
        $response = $upload->upload();
        if ($response['finish']) {
            $user = JFactory::getUser();

            $ext       = \Joomla\String\StringHelper::strtolower(JFile::getExt($response['upload_name']));
            $subfolder = $ext;
            if ($field_id = $this->input->getInt('field_id')) {
                $field = JTable::getInstance('Field', 'JoomcckTable');
                $field->load($field_id);
                $field->params = new JRegistry($field->params);
                $subfolder     = $field->params->get('params.subfolder', $field->field_type);
            }

            $src = JPATH_ROOT . '/tmp' . DIRECTORY_SEPARATOR . $response['upload_name'];
            $data = $this->savefile($response['name'], $src, $response['upload_name'], $field);
            if (!empty($data['id'])) {
                $response['row_id'] = $data['id'];
                JFile::delete($src);
            }
        }

        JResponse::setHeader('Cache-Control', 'no-cache, must-revalidate');
        JResponse::setHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT');
        JResponse::setHeader('Content-type', 'application/json');

        echo json_encode($response);

        JFactory::getApplication()->close();
    }

    public function savefile($realname, $src, $upload_name, $field)
    {
        $params = JComponentHelper::getParams('com_joomcck');

        $time = time();
        $date = date($params->get('folder_format', 'Y-m'), $time);
        $ext  = \Joomla\String\StringHelper::strtolower(JFile::getExt($realname));
        $subfolder     = $field->params->get('params.subfolder', $field->field_type);

        $dest  = JPATH_ROOT . DIRECTORY_SEPARATOR . $params->get('general_upload') . DIRECTORY_SEPARATOR . $subfolder . DIRECTORY_SEPARATOR;
        $index = '<html><body></body></html>';
        if (!JFolder::exists($dest)) {
            JFolder::create($dest, 0755);
            JFile::write($dest . DIRECTORY_SEPARATOR . 'index.html', $index);
        }

        $dest .= $date . DIRECTORY_SEPARATOR;
        if (!JFolder::exists($dest)) {
            JFolder::create($dest, 0755);
            JFile::write($dest . DIRECTORY_SEPARATOR . 'index.html', $index);
        }
        $dest .= $upload_name;

        if (!JFile::copy($src, $dest)) {
            return FALSE;
        }

        $data = array(
            'id'         => NULL,
            'filename'   => $upload_name,
            'realname'   => urldecode($realname),
            'section_id' => $this->input->getInt('section_id'),
            'record_id'  => $this->input->getInt('record_id'),
            'type_id'    => $this->input->getInt('type_id'),
            'field_id'   => $this->input->getInt('field_id'),
            'ext'        => $ext,
            'fullpath'   => JPath::clean($date . DIRECTORY_SEPARATOR . $upload_name, '/'),
            'size'       => filesize($src)
        );

        if (in_array(
            strtolower($ext),
            array(
                'jpg',
                'jpeg',
                'png',
                'gif',
                'bmp'
            )
        )) {
            $size = @getimagesize(JPath::clean($dest));

            if ($size && !empty($size)) {
                $data['width']  = $size[0];
                $data['height'] = $size[1];
            }

            $session = JFactory::getSession();
            $width   = (int) $session->get('width', FALSE, $this->input->get('key'));
            $height  = (int) $session->get('height', FALSE, $this->input->get('key'));


            if ($width && $height && ($width < (int) @$size[0] || $height < (int) @$size[1])) {
                $resizer          = new JS_Image_Resizer($dest);
                $resizer->quality = 100;
                $resizer->resize_limitwh($width, $height, $dest);
                $resizer->close();

                @chmod($dest, 0644);
            }


            if (
                function_exists('exif_read_data') && in_array(
                    strtolower($ext),
                    array(
                        'jpg',
                        'jpeg',
                        'ttf'
                    )
                )
            ) {
                $metadata       = @exif_read_data(JPath::clean($src));
                $data['params'] = json_encode($metadata);
            }
        }

        if (in_array(
            strtolower($ext),
            array(
                'mp3'
            )
        )) {
            $data['params'] = $this->_getID3(JPath::clean($src));
        }

        $table = JTable::getInstance('Files', 'JoomcckTable');
        $table->load(array('filename' => $upload_name));
        if (!$table->id) {
            $table->save($data);
        }

        $data['id'] = $table->id;
        return $data;
    }

    public function uploadremove()
    {
        $filename = $this->input->get('filename');

        $files_table  = JTable::getInstance('Files', 'JoomcckTable');
        $field_table  = JTable::getInstance('Field', 'JoomcckTable');
        $record_table = JTable::getInstance('Record', 'JoomcckTable');

        $files_table->load(
            array(
                'filename' => $filename
            )
        );


        $field_table->load($files_table->field_id);
        $field_params = new JRegistry($field_table->params);
        $record_table->load($files_table->record_id);

        $type = ItemsStore::getType($files_table->type_id);

        $params = JComponentHelper::getParams('com_joomcck');
        // 		$time = substr($filename, 0, strpos($filename, '_'));
        // 		$date = date($params->get('folder_format'), $time);
        $ext = JFile::getExt($filename);
        $id  = str_replace('.' . $ext, '', $filename);

        $subfolder      = $field_params->get('params.subfolder', $field_table->field_type);
        $full_file_path = JPATH_ROOT . DIRECTORY_SEPARATOR . $params->get('general_upload') . DIRECTORY_SEPARATOR . $subfolder . DIRECTORY_SEPARATOR . $files_table->fullpath;


        if (JFile::exists($full_file_path)) {
            $out = array(
                'success' => 1
            );

            if (!$files_table->record_id || !$files_table->saved || !($type->params->get('audit.audit_log') && $type->params->get('audit.al27.on'))) {
                JFile::delete($full_file_path);
                $files_table->delete();
            } else {
                $files_table->saved = 2;
                $files_table->store();
                $data             = $record_table->id ? $record_table->getProperties() : array();
                $data['file']     = $files_table->getProperties();
                $data['field']    = $field_table->label;
                $data['field_id'] = $field_table->id;
                ATlog::log($data, ATlog::REC_FILE_DELETED, 0, $files_table->field_id);
            }
        } else {
            $files_table->delete();
            $out = array(
                'success' => 2
            );
        }
        $out['id'] = $id;
        if ($out['success'] > 0 && $record_table->id && $files_table->saved) {
            $fields = json_decode($record_table->fields, TRUE);

            if (isset($fields[$field_table->id])) {
                $files = &$fields[$field_table->id];
                if (isset($fields[$field_table->id]['files'])) {
                    $files = &$fields[$field_table->id]['files'];
                }

                if (isset($fields[$field_table->id]['uploads'])) {
                    $files = &$fields[$field_table->id]['uploads'];
                }

                foreach ($files as $key => &$file) {
                    if ($file['id'] == $files_table->id) {
                        unset($files[$key]);
                        break;
                    }
                }

                $record_table->fields = json_encode($fields);
                $record_table->store();
            }
        }

        echo json_encode($out);

        JFactory::getApplication()->close();
    }

    public function show()
    {
        $files   = JTable::getInstance('Files', 'JoomcckTable');
        $id      = $this->input->getInt('id', 0);
        $folder  = $this->input->getString('fldr', '');
        $user_id = $this->input->getInt('user_id', 0);
        if ($id) {
            $files->load($id);
        }
        if (!$files->id || !$folder || !$user_id) {
            $thumb = JPATH_COMPONENT . DIRECTORY_SEPARATOR . 'images/notfound.jpg';
        } else {
            $files->views += 1;
            $files->store();

            $data       = explode('_', $files->filename);
            $params     = JComponentHelper::getParams('com_joomcck');
            $thumb_name = $this->input->get('file_key');
            $path       = JPATH_ROOT . DIRECTORY_SEPARATOR . 'images/joomcck_thumbs' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;
            $path .= (int) $user_id . DIRECTORY_SEPARATOR;
            $thumb = $path . $thumb_name . '.' . $files->ext;

            //$thumb      = JPATH_ROOT . DIRECTORY_SEPARATOR . JComponentHelper::getParams('com_joomcck')->get('general_upload') . DIRECTORY_SEPARATOR . 'thumbs_cache' . DIRECTORY_SEPARATOR . date($params->get('folder_format', 'Y-m'), $data[0]) . DIRECTORY_SEPARATOR . $thumb_name . '.' . $files->ext;

            if (!JFile::exists($thumb)) {
                $thumb = JPATH_COMPONENT . DIRECTORY_SEPARATOR . 'images/notfound.jpg';
            }
        }

        if (strtolower($files->ext) == 'jpg') {
            $files->ext = 'jpeg';
        }
        JResponse::clearHeaders();
        header("Content-Type: image/" . strtolower($files->ext));
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 200000) . ' GMT');
        header("Pragma: public"); // required
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

        echo readfile($thumb);
        exit();
    }

    private function _getID3($mp3)
    {
        if (function_exists('id3_get_tag')) {
            $version = id3_get_version($mp3);
            $genres  = id3_get_genre_list();
            $tags    = id3_get_tag($mp3);

            $genre = preg_replace("/^[^0-9]*$/", "", @$tags['genre']);
            $genre = !empty($genres[$genre]) ? $genres[$genre] : @$tags['genre'];

            $data = array(
                "song"   => @$tags['title'],
                "artist" => @$tags['artist'],
                "album"  => @$tags['album'],
                "year"   => @$tags['year'],
                "track"   => @$tags['track'],
                "genre"  => $genre
            );

            return json_encode($data);
        }

        $genre_arr = array(
            "Blues",
            "Classic Rock",
            "Country",
            "Dance",
            "Disco",
            "Funk",
            "Grunge",
            "Hip-Hop",
            "Jazz",
            "Metal",
            "New Age",
            "Oldies",
            "Other",
            "Pop",
            "R&B",
            "Rap",
            "Reggae",
            "Rock",
            "Techno",
            "Industrial",
            "Alternative",
            "Ska",
            "Death Metal",
            "Pranks",
            "Soundtrack",
            "Euro-Techno",
            "Ambient",
            "Trip-Hop",
            "Vocal",
            "Jazz+Funk",
            "Fusion",
            "Trance",
            "Classical",
            "Instrumental",
            "Acid",
            "House",
            "Game",
            "Sound Clip",
            "Gospel",
            "Noise",
            "AlternRock",
            "Bass",
            "Soul",
            "Punk",
            "Space",
            "Meditative",
            "Instrumental Pop",
            "Instrumental Rock",
            "Ethnic",
            "Gothic",
            "Darkwave",
            "Techno-Industrial",
            "Electronic",
            "Pop-Folk",
            "Eurodance",
            "Dream",
            "Southern Rock",
            "Comedy",
            "Cult",
            "Gangsta",
            "Top 40",
            "Christian Rap",
            "Pop/Funk",
            "Jungle",
            "Native American",
            "Cabaret",
            "New Wave",
            "Psychadelic",
            "Rave",
            "Showtunes",
            "Trailer",
            "Lo-Fi",
            "Tribal",
            "Acid Punk",
            "Acid Jazz",
            "Polka",
            "Retro",
            "Musical",
            "Rock & Roll",
            "Hard Rock",
            "Folk",
            "Folk-Rock",
            "National Folk",
            "Swing",
            "Fast Fusion",
            "Bebob",
            "Latin",
            "Revival",
            "Celtic",
            "Bluegrass",
            "Avantgarde",
            "Gothic Rock",
            "Progressive Rock",
            "Psychedelic Rock",
            "Symphonic Rock",
            "Slow Rock",
            "Big Band",
            "Chorus",
            "Easy Listening",
            "Acoustic",
            "Humour",
            "Speech",
            "Chanson",
            "Opera",
            "Chamber Music",
            "Sonata",
            "Symphony",
            "Booty Bass",
            "Primus",
            "Porn Groove",
            "Satire",
            "Slow Jam",
            "Club",
            "Tango",
            "Samba",
            "Folklore",
            "Ballad",
            "Power Ballad",
            "Rhythmic Soul",
            "Freestyle",
            "Duet",
            "Punk Rock",
            "Drum Solo",
            "Acapella",
            "Euro-House",
            "Dance Hall"
        );

        $filesize = filesize($mp3);
        $file     = fopen($mp3, "r");
        fseek($file, -128, SEEK_END);

        $tag = fread($file, 3);

        $data = array();
        if ($tag == "TAG") {
            $data["song"]    = trim(fread($file, 30));
            $data["artist"]  = trim(fread($file, 30));
            $data["album"]   = trim(fread($file, 30));
            $data["year"]    = trim(fread($file, 4));
            $data["comment"] = trim(fread($file, 30));
            $id              = ord(trim(fread($file, 1)));
            $data["genre"]   = '';
            if (isset($genre_arr[$id])) {
                $data["genre"] = $genre_arr[$id];
            }
        }
        fclose($file);

        return json_encode($data);
    }
    public function _convert_size($val)
    {
        $val  = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        settype($val, 'int');
        switch ($last) {
            case 'g':
                $val *= 1024 * 1024 * 1024;

            case 'm':
                $val *= 1024 * 1024;

            case 'k':
                 $val *= 1024;
        }

        return (int) @$val;
    }
}
