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

use BPExtensions\Component\BPGallery\Administrator\Event\ContentAfterSave;
use BPExtensions\Component\BPGallery\Administrator\Helper\BPGalleryHelper;
use BPExtensions\Component\BPGallery\Administrator\Table\ImageTable;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\Component\Categories\Administrator\Helper\CategoriesHelper;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Event\DispatcherAwareTrait;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Registry\Registry;
use RuntimeException;

/**
 * Image model.
 */
class ImageModel extends AdminModel
{
    use CurrentUserTrait;
    use DispatcherAwareTrait;
    
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
        $this->params = ComponentHelper::getParams('com_bpgallery');

        // Debugging errors
        $app             = Factory::getApplication();
        $this->debugMode = $app->isClient('administrator') || $app->get('debug');
    }

    /**
     * Method to get the record form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  Form|boolean  A JForm object on success, false on failure
     * @throws Exception
     */
    public function getForm($data = [], $loadData = true): bool|Form
    {
        // Get the form.
        $form = $this->loadForm(
            'com_bpgallery.image',
            'image',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        return $form ?? false;
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
    public function save($data): bool
    {

        // If there is a file to upload and upload failed
        if (isset($data['upload_image']) and !$this->uploadFile($data)) {
            return false;
        }

        $input  = Factory::getApplication()->input;
        $filter = InputFilter::getInstance();

        if (isset($data['metadata']) && isset($data['metadata']['author'])) {
            $data['metadata']['author'] = $filter->clean($data['metadata']['author'], 'TRIM');
        }

        if (isset($data['created_by_alias'])) {
            $data['created_by_alias'] = $filter->clean($data['created_by_alias'], 'TRIM');
        }

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
            $table['title'] = str_starts_with($data['catid'], '#new#') ? substr($data['catid'], 5) : $data['catid'];
            $table['parent_id'] = 1;
            $table['extension'] = 'com_bpgallery';
            $table['language']  = $data['language'];
            $table['published'] = 1;

            // Create new category and get catid back
            $data['catid'] = CategoriesHelper::createCategory($table);
        }

        // Alter the title for save as copy
        if ($input->get('task') === 'save2copy') {
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
            ) && (!isset($data['id']) || (int)$data['id'] == 0) && $data['alias'] === null) {
            if (Factory::getApplication()->getConfig()->get('unicodeslugs') === 1) {
                $data['alias'] = OutputFilter::stringURLUnicodeSlug($data['title']);
                } else {
                $data['alias'] = OutputFilter::stringURLSafe($data['title']);
                }

            $table = $this->getTable();

                if ($table->load(['alias' => $data['alias'], 'catid' => $data['catid']])) {
                    $msg = Text::_('COM_BPGALLERY_SAVE_WARNING');
                }

                [$title, $alias] = $this->generateNewTitle($data['catid'], $data['alias'], $data['title']);
                $data['alias'] = $alias;

                if (isset($msg)) {
                    Factory::getApplication()->enqueueMessage($msg, CMSApplicationInterface::MSG_WARNING);
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
    protected function uploadFile(array &$data): bool
    {

        // Get upload file details
        /**
         * @var CMSApplication $app
         */
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
        if (!file_exists($images_path_absolute) && !mkdir($images_path_absolute, 0755, true) && !is_dir(
                $images_path_absolute
            )) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $images_path_absolute));
        }

        // If uploading the file failed.
        if (!File::upload($data['upload_image'], $path)) {
            // If debug is enabled, provide useful message
            if ($this->debugMode) {
                throw new RuntimeException(
                    Text::sprintf('COM_BPGALLERY_ERROR_IMAGE_UPLOAD_S', $data['upload_image'], $path), 500
                );
            }

            // Return failure.
            return false;

            // Upload succeed, so save filename to image data.
        }

        $data['filename'] = ltrim($images_path . '/original/' . $filename, '/');

        // If data save or thumbnails generation fails
        $result_thumbnails_generation = $this->generateThumbnails($path);
        $result_save      = $result_thumbnails_generation && parent::save($data);
        if (!$result_thumbnails_generation || !$result_save) {
            // Remove thumbnails
            $this->removeThumbnails($path);

            // If debug is enabled, provide useful message
            if ($this->debugMode) {
                if (!$result_thumbnails_generation) {
                    throw new RuntimeException(Text::sprintf('COM_BPGALLERY_ERROR_CREATING_THUMBNAILS_S', $path), 500);
                }
                if (!$result_save) {
                    throw new RuntimeException($this->getError(), 500);
                }
            }

            // Return failure
            throw new RuntimeException(Text::_('COM_BPGALLERY_ERROR_SAVING_IMAGE_DATA'), 500);
        }

        // All fine, return success
        return true;
    }

    /**
     * Get a unique filename from a provided filename using recurency.
     *
     * @param   string  $basename   File basename.
     * @param   string  $extension  File extension.
     *
     * @return  string
     */
    protected function getSafeFilename(string $basename, string $extension): string
    {

        // Prepare path for this filename
        $images_path = BPGalleryHelper::getParam(
            'images_path',
            '/images/gallery'
        );
        $filename    = $basename . '.' . strtolower($extension);
        $path        = JPATH_ROOT . $images_path . '/original/' . $filename;

        // If path is save (no overwriting)
        if (!file_exists($path)) {
            // Return this
            return $filename;
        }

        // Create new basename
        $parts = explode('-', $basename);

        // Get string after last -
        $end = end($parts);

        // If this is a number
        if (count($parts) > 1 && is_numeric($end)) {
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

    /**
     * Generate thumbnails for the provided file.
     *
     * @param   string  $path  Path to the image.
     *
     * @return  boolean
     *
     * @throws Exception
     */
    public function generateThumbnails(string $path): bool
    {

        // Get thumbnail sizes
        $sizes        = BPGalleryHelper::getParam('sizes', '');
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
        $defaultSizes = (array)json_decode(
            json_encode($defaultSizes, JSON_THROW_ON_ERROR),
            false,
            512,
            JSON_THROW_ON_ERROR
        );
        $sizes        = array_merge($sizes, $defaultSizes);

        // For each thumbnail size, create a thumbnail
        foreach ($sizes as $size) {
            $method = array_search($size->method, BPGalleryHelper::$generationMethods, true);
            BPGalleryHelper::getThumbnail(
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
     * @throws Exception
     */
    public function removeThumbnails(string $path): bool
    {
        $app         = Factory::getApplication();
        $filename    = pathinfo($path, PATHINFO_BASENAME);
        $images_root = dirname($path, 2);
        $thumbs_root = $images_root . '/thumbs';

        $directories = Folder::folders($thumbs_root, null, null, true);

        foreach ($directories as $directory) {
            $path = $directory . '/' . $filename;
            if (is_file($path) && !File::delete($path)) {
                $app->enqueueMessage(Text::sprintf('COM_BPGALLERY_IMAGES_UNABLE_TO_REMOVE_THUMBS_S', $path), 'error');

                return false;
            }
        }

        return true;
    }

    /**
     * Is the user allowed to create an on the fly category?
     *
     * @return  boolean
     * @throws Exception
     */
    private function canCreateCategory(): bool
    {
        return $this->getCurrentUser()->authorise('core.create', 'com_bpgallery');
    }

    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param   string  $name     The table type to instantiate
     * @param   string  $prefix   A prefix for the table class name. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return  Table    A JTable object
     * @throws Exception
     */
    public function getTable($name = 'Image', $prefix = 'Administrator', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
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
         * @var ImageTable $table
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
    protected function canDelete($record): bool
    {
        if (!empty($record->id)) {
            if ($record->state !== -2) {
                return false;
            }

            $user = $this->getCurrentUser();

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
    protected function canEditState($record): bool
    {
        $user = $this->getCurrentUser();

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
     *
     */
    protected function loadFormData(): mixed
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
    protected function preprocessData($context, &$data, $group = 'bpgallery'): void
    {
        parent::preprocessData($context, $data, $group);
    }

    /**
     * Prepare and sanitise the table prior to saving.
     *
     * @param   ImageTable  $table  A JTable object.
     *
     * @return  void
     */
    protected function prepareTable($table): void
    {
        $table->title = htmlspecialchars_decode($table->get('title'), ENT_QUOTES);

        $date = Factory::getDate();
        $user = $this->getCurrentUser();

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
     * @param   ImageTable  $table  The table object containing the newly created item
     * @param   integer     $newId  The id of the new item
     * @param   integer     $oldId  The original item id
     *
     * @return  void
     * @throws Exception
     */
    protected function cleanupPostBatchCopy(\JTableInterface $table, $newId, $oldId): void
    {

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

        $this->getDispatcher()->dispatch(
            'onContentAfterSave',
            new ContentAfterSave('ContentAfterSave', ['com_bpgallery.image', &$this->table, true, $fieldsData])
        );
    }
}
