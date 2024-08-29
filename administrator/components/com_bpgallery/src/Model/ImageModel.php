<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

namespace BPExtensions\Component\BPGallery\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Registry\Registry;

/**
 * Image model.
 */
class ImageModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     */
    public $typeAlias = 'com_bpgallery.image';

    public function __construct($config = [])
    {

        // Base object construction
        parent::__construct($config);

        // Store basic params into model for laster use
        $this->params = JComponentHelper::getParams('com_bpgallery');

        // Debugging errors
        $app = JFactory::getApplication();
        $this->debugMode = $app->isClient('administrator') or $app->get('debug');
    }

    /**
     * Method to get the record form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  JForm|boolean  A JForm object on success, false on failure
     *
     * @since   1.6
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm(
            'com_bpgallery.image',
            'image',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Saves data.
     *
     * @param   array  $data  Data to save.
     *
     * @return  boolean
     *
     * @throws Exception
     */
    public function save($data)
    {

        // If there is a file to upload and upload failed
        if (isset($data['upload_image']) and !$this->uploadFile($data)) {
            return false;
        }

        $input  = Factory::getApplication()->input;
        $filter = JFilterInput::getInstance();

        if (isset($data['metadata']) && isset($data['metadata']['author'])) {
            $data['metadata']['author'] = $filter->clean($data['metadata']['author'], 'TRIM');
        }

        if (isset($data['created_by_alias'])) {
            $data['created_by_alias'] = $filter->clean($data['created_by_alias'], 'TRIM');
        }

        JLoader::register(
            'CategoriesHelper',
            JPATH_ADMINISTRATOR . '/components/com_categories/helpers/categories.php'
        );

        // Create new category, if needed.
        $createCategory = true;

        // If category ID is provided, check if it's valid.
        if (is_numeric($data['catid']) && $data['catid']) {
            $createCategory = !CategoriesHelper::validateCategoryId($data['catid'], 'com_bpgallery');
        }

        // Set default image state
        $defaultImageState = (int)$this->params->get('default_image_state', 1);
        $data['state']     = $defaultImageState;

        // Save New Category
        if ($createCategory && $this->canCreateCategory()) {
            $table = [];

            // Remove #new# prefix, if exists.
            $table['title']     = strpos($data['catid'], '#new#') === 0 ? substr($data['catid'], 5) : $data['catid'];
            $table['parent_id'] = 1;
            $table['extension'] = 'com_bpgallery';
            $table['language']  = $data['language'];
            $table['published'] = 1;

            // Create new category and get catid back
            $data['catid'] = CategoriesHelper::createCategory($table);
        }

        // Alter the title for save as copy
        if ($input->get('task') == 'save2copy') {
            $origTable = clone $this->getTable();
            $origTable->load($input->getInt('id'));

            if ($data['title'] == $origTable->title) {
                [$title, $alias] = $this->generateNewTitle($data['catid'], $data['alias'], $data['title']);
                $data['title'] = $title;
                $data['alias'] = $alias;
            } else {
                if ($data['alias'] == $origTable->alias) {
                    $data['alias'] = '';
                }
            }

            $data['state'] = 0;
        }

        // Automatic handling of alias for empty fields
        if (in_array(
                $input->get('task'),
                ['apply', 'save', 'save2new']
            ) && (!isset($data['id']) || (int)$data['id'] == 0)) {
            if ($data['alias'] == null) {
                if (JFactory::getConfig()->get('unicodeslugs') == 1) {
                    $data['alias'] = JFilterOutput::stringURLUnicodeSlug($data['title']);
                } else {
                    $data['alias'] = JFilterOutput::stringURLSafe($data['title']);
                }

                $table = JTable::getInstance('Image', 'BPGalleryTable');

                if ($table->load(['alias' => $data['alias'], 'catid' => $data['catid']])) {
                    $msg = Text::_('COM_BPGALLERY_SAVE_WARNING');
                }

                [$title, $alias] = $this->generateNewTitle($data['catid'], $data['alias'], $data['title']);
                $data['alias'] = $alias;

                if (isset($msg)) {
                    Factory::getApplication()->enqueueMessage($msg, 'warning');
                }
            }
        }

        // Upload was success or there was nothing to upload, so just save the data
        // and return the result.
        return parent::save($data);
    }

    /**
     * Upload file.
     *
     * @param   array  $data  Form data.
     *
     * @return  boolean
     *
     * @throws Exception
     */
    protected function uploadFile(&$data)
    {

        // Get upload file details
        $basename = pathinfo($data['upload_file_name'], PATHINFO_FILENAME);
        $ext      = pathinfo($data['upload_file_name'], PATHINFO_EXTENSION);

        // Fill the title if it is missing
        if (empty($data['title'])) {
            $data['title'] = $basename;
        }

        // Prepare path
        $images_path          = $this->params->get('images_path', '/images/gallery');
        $images_path          = '/' . trim($images_path . '/');
        $filename             = $this->getSafeFilename($basename, $ext);
        $images_path_absolute = JPATH_ROOT . $images_path . '/original';
        $path                 = $images_path_absolute . '/' . $filename;

        // Ensure target directory exists
        if (!file_exists($images_path_absolute)) {
            mkdir($images_path_absolute, 0755, true);
        }

        // If uploading the file failed.
        if (!File::upload($data['upload_image'], $path)) {
            // If debug is enabled, provide useful message
            if ($this->debugMode) {
                echo json_encode([
                    'error' => Text::sprintf('COM_BPGALLERY_ERROR_IMAGE_UPLOAD_S', $data['upload_image'], $path)
                ]);
                JFactory::getApplication()->close(500);
            }

            // Return failure.
            return false;

            // Upload succeed, so save filename to image data.
        } else {
            $data['filename'] = ltrim($images_path . '/original/' . $filename, '/');
        }

        // If data save or thumbnails generation fails
        $result_thumbnails_generation = $this->generateThumbnails($path);
        $result_save = $result_thumbnails_generation and parent::save($data);
        if (!$result_thumbnails_generation or !$result_save) {
            // Remove thumbnails
            $this->removeThumbnails($path);

            // If debug is enabled, provide useful message
            if ($this->debugMode) {
                $errors = [];
                if (!$result_thumbnails_generation) {
                    $errors[] = Text::sprintf('COM_BPGALLERY_ERROR_CREATING_THUMBNAILS_S', $path);
                }
                if (!$result_save) {
                    $errors[] = Text::_('COM_BPGALLERY_ERROR_SAVING_IMAGE_DATA');
                }

                echo json_encode(['errors' => $errors, 'data' => $data]);
                Factory::getApplication()->close(500);
            }

            // Return failure
            return false;
        }

        // All fine, return success
        return true;
    }

    /**
     * Get a unique filename from a provided filename using recurency.
     *
     * @param   String  $basename   File basename.
     * @param   String  $extension  File extension.
     *
     * @return  String
     */
    protected function getSafeFilename($basename, $extension)
    {

        // Prepare path for this filename
        $images_path = \BPGalleryHelper::getParam(
            'images_path',
            '/images/gallery'
        );
        $filename    = $basename . '.' . strtolower($extension);
        $path        = JPATH_ROOT . $images_path . '/original/' . $filename;

        // If path is save (no overwriting)
        if (!file_exists($path)) {
            // Return this
            return $filename;
        } else {
            // Create new basename
            $parts = explode('-', $basename);

            // Get string after last -
            $end = end($parts);

            // If this is a number
            if (count($parts) > 1 and is_numeric($end)) {
                // Remove it
                array_pop($parts);

                // increase it and append again
                $parts[] = (int)$end + 1;

                // Not a number
            } else {
                // So add 2nd version number
                $parts [] = 2;
            }

            // Create a basename
            $basename = implode('-', $parts);

            // Check the new filename
            return $this->getSafeFilename($basename, $extension);
        }
    }

    /**
     * Generate thumbnails for the provided file.
     *
     * @param   string  $path  Patht to the image.
     *
     * @return  boolean
     *
     * @throws Exception
     */
    public function generateThumbnails(string $path): bool
    {

        // Get thumbnail sizes
        $sizes = \BPGalleryHelper::getParam('sizes', '');
        if (!empty($sizes)) {
            $sizes = (array)$sizes;
        } else {
            $sizes = [];
        }

        // Add default (component views) thumbnail sizes
        $defaultSizes = [
            ['width' => 64, 'height' => 64, 'method' => 'crop'],
            ['width' => 320, 'height' => 320, 'method' => 'fit']
        ];
        $defaultSizes = (array)json_decode(json_encode($defaultSizes));
        $sizes        = array_merge($sizes, $defaultSizes);

        // For each thumbnail size, create a thumbnail
        foreach ($sizes as $size) {
            $method = array_search($size->method, BPGalleryHelper::$generationMethods, true);
            \BPGalleryHelper::getThumbnail(
                $path,
                $size->width,
                $size->height,
                $method
            );
        }

        return true;
    }

    /**
     * Try to remove all thumbnails for the selected file.
     *
     * @param   string  $path  Path to the original image file.
     *
     * @return  boolean
     */
    public function removeThumbnails(string $path): bool
    {
        $result = true;

        $app         = Factory::getApplication();
        $filename    = pathinfo($path, PATHINFO_BASENAME);
        $images_root = dirname(dirname($path));
        $thumbs_root = $images_root . '/thumbs';

        $directories = Folder::folders($thumbs_root, null, null, true);

        foreach ($directories as $directory) {
            $path = $directory . '/' . $filename;
            if (File::exists($path) && !File::delete($path)) {
                $app->enqueueMessage(Text::sprintf('COM_BPGALLERY_IMAGES_UNABLE_TO_REMOVE_THUMBS_S', $path), 'error');
                $return = false;
            }
        }

        return $result;
    }

    /**
     * Is the user allowed to create an on the fly category?
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    private function canCreateCategory(): bool
    {
        return Factory::getUser()->authorise('core.create', 'com_bpgallery');
    }

    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param   string  $type    The table type to instantiate
     * @param   string  $prefix  A prefix for the table class name. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  JTable    A JTable object
     *
     * @since   1.6
     */
    public function getTable(
        $type = 'Image',
        $prefix = 'BPGalleryTable',
        $config = []
    ) {
        return JTable::getInstance($type, $prefix, $config);
    }

    /**
     * Recreate thumbnails for a given images.
     *
     * @param   array  $image_ids  Images ids.
     *
     * @return bool
     *
     * @throws Exception
     */
    public function recreateThumbnails(array $image_ids): bool
    {
        $result = true;

        // Prepare path
        $images_path          = $this->params->get('images_path', '/images/gallery');
        $images_path          = '/' . trim($images_path, '/');
        $images_path_absolute = JPATH_ROOT . $images_path . '/original';

        /**
         * @var BPGalleryTableImage $table
         */
        $table = $this->getTable();
        foreach ($image_ids as $image_id) {
            // Load image
            if (!$table->load($image_id)) {
                $result = false;
                break;
            }

            // Get filename
            $basename = pathinfo($table->filename, PATHINFO_FILENAME);
            $ext      = pathinfo($table->filename, PATHINFO_EXTENSION);
            $path     = $images_path_absolute . '/' . $basename . '.' . $ext;

            // Delete old thumbnails
            $this->removeThumbnails($path);

            // Recreate thumbnails
            if (!$this->generateThumbnails($path)) {
                $result = false;
                break;
            }

            $table->reset();
        }

        return $result;
    }

    /**
     * Method to test whether a record can be deleted.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
     */
    protected function canDelete($record)
    {
        if (!empty($record->id)) {
            if ($record->state != -2) {
                return;
            }

            $user = Factory::getUser();

            if (!empty($record->catid)) {
                return $user->authorise(
                    'core.delete',
                    'com_bpgallery.category.' . (int)$record->catid
                );
            }

            return $user->authorise('core.delete', 'com_bpgallery');
        }
    }

    /**
     * Method to test whether a record can be deleted.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to change the state of the record.
     *                   Defaults to the permission set in the component.
     */
    protected function canEditState($record)
    {
        $user = Factory::getUser();

        if (!empty($record->catid)) {
            return $user->authorise(
                'core.edit.state',
                'com_bpgallery.category.' . (int)$record->catid
            );
        }

        return $user->authorise('core.edit.state', 'com_bpgallery');
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @throws Exception
     * @since   1.0
     *
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $data = Factory::getApplication()->getUserState('com_bpgallery.edit.image.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_bpgallery.image', $data);

        return $data;
    }

    /**
     * Method to get a single record.
     *
     * @param   integer  $pk  The id of the primary key.
     *
     * @return  mixed  Object on success, false on failure.
     *
     * @throws Exception
     */
    public function getItem($pk = null): ?object
    {
        if ($item = parent::getItem($pk)) {
            // Convert the params field to an array.
            $registry     = new Registry($item->params);
            $item->params = $registry->toArray();

            // Convert the metadata field to an array.
            $registry       = new Registry($item->metadata);
            $item->metadata = $registry->toArray();

            // TODO: Add tags support
//            if (!empty($item->id))
//            {
//                $item->tags = new JHelperTags;
//                $item->tags->getTagIds($item->id, 'com_bpgallery.image');
//            }
        }

        // Load associated content items
        // TODO: Add associations support
//        $assoc = JLanguageAssociations::isEnabled();
//
//        if ($assoc)
//        {
//            $item->associations = array();
//
//            if ($item->id != null)
//            {
//                $associations = JLanguageAssociations::getAssociations('com_bpgallery', '#__bpgallery_images', 'com_bpgallery.image', $item->id);
//
//                foreach ($associations as $tag => $association)
//                {
//                    $item->associations[$tag] = $association->id;
//                }
//            }
//        }

        return $item;
    }

    /**
     * Method to allow derived classes to preprocess the data.
     *
     * @param   string   $context  The context identifier.
     * @param   mixed   &$data     The data to be processed. It gets altered directly.
     * @param   string   $group    The name of the plugin group to import (defaults to "content").
     *
     * @return  void
     */
    protected function preprocessData($context, &$data, $group = 'bpgallery')
    {
        parent::preprocessData($context, $data, $group);
    }

    /**
     * Prepare and sanitise the table prior to saving.
     *
     * @param   JTable  $table  A JTable object.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function prepareTable($table)
    {
        $table->title = htmlspecialchars_decode($table->get('title'), ENT_QUOTES);

        $date = Factory::getDate();
        $user = Factory::getUser();

        if (empty($table->id)) {
            // Set the values
            $table->created    = $date->toSql();
            $table->created_by = $user->id;

            // Set ordering to the last item if not set
            if (empty($table->ordering)) {
                $db    = $this->getDbo();
                $query = $db->getQuery(true)
                    ->select('MAX(ordering)')
                    ->from('#__bpgallery_images');

                $db->setQuery($query);
                $max = $db->loadResult();

                $table->ordering = $max + 1;
            }
        } else {
            // Set the values
            $table->modified    = $date->toSql();
            $table->modified_by = $user->id;
        }
    }

    /**
     * Function that can be overriden to do any data cleanup after batch copying data
     *
     * @param   JTableInterface  $table  The table object containing the newly created item
     * @param   integer          $newId  The id of the new item
     * @param   integer          $oldId  The original item id
     *
     * @return  void
     *
     * @since  3.8.12
     */
    protected function cleanupPostBatchCopy(\JTableInterface $table, $newId, $oldId)
    {

        // Register FieldsHelper
        JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');

        $oldItem = $this->getTable();
        $oldItem->load($oldId);
        $fields = FieldsHelper::getFields('com_bpgallery.image', $oldItem, true);

        $fieldsData = [];

        if (!empty($fields)) {
            $fieldsData['com_fields'] = [];

            foreach ($fields as $field) {
                $fieldsData['com_fields'][$field->name] = $field->rawvalue;
            }
        }

        JEventDispatcher::getInstance()->trigger(
            'onContentAfterSave',
            ['com_bpgallery.image', &$this->table, true, $fieldsData]
        );
    }
}
