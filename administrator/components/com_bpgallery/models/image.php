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
        $table->name = htmlspecialchars_decode($table->name, ENT_QUOTES);
    }
}