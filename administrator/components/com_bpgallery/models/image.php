<?php
/**
 * @author		Artur Stępień (artur.stepien@bestproject.pl)
 * @website		www.bestproject.pl
 * @copyright	Copyright (C) 2017 Best Project, Inc. All rights reserved.
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die;

/**
 * Image model.
 */
class BPGalleryModelImage extends JModelAdmin
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     */
    public $typeAlias = 'com_bpgallery.image';

    public function __construct($config = array())
    {

        // Base object construction
        parent::__construct($config);

        // Store basic params into model for laster use
        $this->params      = JComponentHelper::getParams('com_bpgallery');
        $this->images_base = $this->params->get('images_path', '/images/gallery');
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

            $user = JFactory::getUser();

            if (!empty($record->catid)) {
                return $user->authorise('core.delete',
                        'com_bpgallery.category.'.(int) $record->catid);
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
        $user = JFactory::getUser();

        if (!empty($record->catid)) {
            return $user->authorise('core.edit.state',
                    'com_bpgallery.category.'.(int) $record->catid);
        }

        return $user->authorise('core.edit.state', 'com_bpgallery');
    }

    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param   string  $type    The table type to instantiate
     * @param   string  $prefix  A prefix for the table class name. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  JTable	A JTable object
     *
     * @since   1.6
     */
    public function getTable($type = 'Image', $prefix = 'BPGalleryTable',
                             $config = array())
    {
        return JTable::getInstance($type, $prefix, $config);
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
    public function getForm($data = array(), $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_bpgallery.image', 'image',
            array('control' => 'jform', 'load_data' => $loadData));

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   1.6
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $data = JFactory::getApplication()->getUserState('com_bpgallery.edit.image.data',
            array());

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_bpgallery.image', $data);

        return $data;
    }

    /**
     * Prepare and sanitise the table prior to saving.
     *
     * @param   JTable  $table  A JTable object.
     *
     * @return  void
     *
     * @since   1.6
     */
    protected function prepareTable($table)
    {
        $table->title = htmlspecialchars_decode($table->get('title'), ENT_QUOTES);
    }

    /**
     * Saves data.
     * 
     * @param   Array   $data   Data to save.
     * 
     * @return  boolean
     */
    public function save($data)
    {

        // If there is a file to upload and upload failed
        if (isset($data['upload_image']) AND ! $this->uploadFile($data)) {
            return false;
        }

        // Upload was success or there was nothing to upload, so just save the data
        // and return the result.
        return parent::save($data);
    }

    /**
     *
     * @param   Array   $data
     * 
     * @return  boolean
     */
    protected function uploadFile(&$data)
    {

        // Get upload file details
        $basename = pathinfo($data['upload_file_name'], PATHINFO_BASENAME);
        $ext      = pathinfo($data['upload_file_name'], PATHINFO_EXTENSION);

        // Fill the title if it is missing
        if (empty($data['title'])) {
            $data['title'] = $basename;
        }

        // Prepare path
        $filename = $this->getSafeFilename($basename, $ext);
        $path     = JPATH_ROOT.$this->images_path.'/original/'.$filename;

        // If uploading the file failed.
        if (!JFile::upload($data['upload_image'], $path)) {

            // Return failure.
            return false;

            // Uplod successed, so save filename to image data.
        } else {

            $data['image'] = $filename;
        }

        // Generate thumbnails
        // If data save opr generating thumbnails failes
        if (!$this->generateThumbnails($path) OR ! parent::save($data)) {

            // Remove thumbnails
            $this->removeThumbnails($path);

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
        $filename = $basename.'.'.strtolower($extension);
        $path     = JPATH_ROOT.$this->images_path.'/original/'.$filename;

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
            if (is_numeric($end)) {

                // Remove it
                array_pop($parts);

                // increase it and append again
                $parts[] = $end++;

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
     * Try to remove all thumbnails for the selected file.
     * 
     * @param   String  $path   Path to the original image file.
     *
     * @return  boolean
     */
    protected function removeThumbnails($path)
    {

        $filename = pathinfo($path, PATHINFO_FILENAME);

        // TODO: Remove thumbnails
        return true;
    }

    /**
     * Generate thumbnails for the provided file.
     *
     * @param   String  $path   Patht to the image.
     * 
     * @return  boolean
     */
    protected function generateThumbnails($path)
    {
        // TODO: Generate thumbnails.
        return true;
    }

    /**
     * Method to allow derived classes to preprocess the data.
     *
     * @param   string  $context  The context identifier.
     * @param   mixed   &$data    The data to be processed. It gets altered directly.
     * @param   string  $group    The name of the plugin group to import (defaults to "content").
     *
     * @return  void
     */
    protected function preprocessData($context, &$data, $group = 'bpgallery')
    {
        parent::preprocessData($context, $data, $group);
    }
}