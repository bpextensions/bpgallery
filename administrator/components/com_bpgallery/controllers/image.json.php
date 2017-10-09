<?php
/**
 * @author		Artur Stępień (artur.stepien@bestproject.pl)
 * @website		www.bestproject.pl
 * @copyright	Copyright (C) 2017 Best Project, Inc. All rights reserved.
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

/**
 * Image controller class for JSON format.
 */
class BPGalleryControllerImage extends JControllerAdmin
{

    /**
     * Method to get a model object, loading it if required.
     *
     * @param   string  $name    The model name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  JModelLegacy  The model.
     *
     * @since   1.6
     */
    public function getModel($name = 'Image', $prefix = 'BPGalleryModel',
                             $config = array('ignore_request' => true))
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Image upload action.
     * 
     * @return  string
     */
    public function upload()
    {
        // Remove the script time limit.
        @set_time_limit(0);

        // Do not cache the response to this, its a redirect, and mod_expires and google chrome browser bugs cache it forever!
        $app = JFactory::getApplication();
        $app->allowCache(false);

        // Set mime
        $app->mimeType = 'application/json';

        // Set content type
        $app->setHeader('Content-Type',
            $app->mimeType.'; charset='.$app->charSet);

        // Get uploaded file info
        $file = $this->input->files->get('image');

        // Check content type
        $permitted_types = array(
            'image/jpeg', 'image/png', 'image/gif'
        );
        if( !in_array($file['type'], $permitted_types) ) {
            $this->close(415);
        }

        // Prepare data
        $data = array('category_id' => $this->input->getInt('category_id'));
        $data['upload_image'] = $file['tmp_name'];
        $data['upload_file_name'] = $file['name'];

        // Access check.
        if (!$this->allowAdd($data)) {
            $app->close(550);
        }

        // Get application model
        /* @var $model BPGalleryModelImage */
        $model = $this->getModel();

        // Response holder
        $response = array();

        // If save process succesed
        if ($model->save($data)) {

            // Send proper status code
            $app->setHeader('status', 200);

            // save process failed
        } else {

            // Send proper status code
            $app->setHeader('status', 500);
        }
        // Set peak memory usage
        if ($app->get('debug')) {
            $response['peak_memory_usage'] = round(memory_get_peak_usage(true) / 1024
                / 1024, 2);
        }

        // Send response status code
        $app->sendHeaders();

        // Send response
        echo json_encode($response);

        // Close application
        $app->close();
    }

    /**
     * Method override to check if you can add a new record.
     *
     * @param   array  $data  An array of input data.
     *
     * @return  boolean
     */
    protected function allowAdd($data = array())
    {
        $categoryId = ArrayHelper::getValue($data, 'category_id', 0, 'int');
        $allow      = null;

        // Check permissions for this category
        if ($categoryId) {
            // If the category has been passed in the URL check it.
            $allow = JFactory::getUser()->authorise('core.create',
                $this->option.'.category.'.$categoryId);
        }

        // We how the answer, return it
        if ($allow !== null) {
            return $allow;
        }

        // In the absence of better information, revert to the component permissions.
        return false;
    }
}