<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

namespace BPextensions\Component\BPGallery\Administrator\Controller;

defined('_JEXEC') or die;

use BPExtensions\Component\BPGallery\Administrator\Model\ImageModel;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

/**
 * Image controller class.
 */
class ImageController extends FormController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     */
    protected $text_prefix = 'COM_BPGALLERY_IMAGE';

    /**
     * Method to run batch operations.
     *
     * @param   string  $model  The model
     *
     * @return  boolean  True on success.
     * @throws Exception
     */
    public function batch($model = null): bool
    {
        $this->checkToken();

        /**
         * @var ImageModel $model
         */
        $model = $this->getModel('Image', 'Administrator', []);

        // Preset the redirect
        $this->setRedirect(
            Route::_('index.php?option=com_bpgallery&view=images' . $this->getRedirectToListAppend(), false)
        );

        return parent::batch($model);
    }

    /**
     * Image upload action.
     *
     * @throws Exception
     */
    public function upload(): void
    {
        // Remove the script time limit.
        @set_time_limit(0);

        // Do not cache the response to this, its a redirect, and mod_expires and google chrome browser bugs cache it forever!
        /**
         * @var CMSApplication $app
         */
        $app = Factory::getApplication();
        $app->allowCache(false);
        $debug = $app->get('debug');

        // Set mime
        $app->mimeType = 'application/json';

        // Set content type
        $app->setHeader(
            'Content-Type',
            $app->mimeType . '; charset=' . $app->charSet
        );

        // Get uploaded file info
        $file = $this->input->files->get('image');

        // Check content type
        $permitted_types = [
            'image/jpeg',
            'image/png',
            'image/gif'
        ];

        if (!in_array($file['type'], $permitted_types, true)) {

            // Useful debug message
            if ($debug) {
                echo json_encode(['error' => Text::sprintf('COM_BPGALLERY_ERROR_UNSUPPORTED_FILE_S', $file['type'])],
                    JSON_THROW_ON_ERROR);
            }

            $app->close(415);
        }

        // Prepare data
        $data = [
            'catid'            => $this->input->getInt('category_id'),
            'upload_image'     => $file['tmp_name'],
            'upload_file_name' => $file['name'],
            'language' => $app->getUserStateFromRequest(
                $this->option . '.images.filter.language',
                'filter_language',
                '*'
            )
        ];

        // Access check.
        if (!$this->allowAdd($data)) {
            // Useful debug message
            if ($debug) {
                echo json_encode(['error' => Text::_('COM_BPGALLERY_ERROR_MISSING_ADD_PERMISSION')],
                    JSON_THROW_ON_ERROR);
            }

            $app->setHeader('status', 550);
            $app->close();
        }

        // Get application model
        /* @var $model ImageModel */
        $model = $this->getModel();

        // Response holder
        $response = [];

        // If save process succeed
        try {
            if ($model->save($data)) {
                // Useful debug message
                if ($debug) {
                    $response = array_merge($response, [
                        'result' => Text::_('COM_BPGALLERY_SUCCESS_ADDED'),
                    ]);
                }

                // Send proper status code
                $app->setHeader('status', 200);
            }
        } catch (Exception $e) {
            $app->setHeader('status', 500);
            if ($debug) {
                $response = array_merge($response, [
                    'error' => $e->getMessage(),
                ]);
            }
        } finally {
            if ($debug) {
                $response = array_merge($response, [
                    'data'  => $data
                ]);
            }
        }

        // Set peak memory usage
        if ($app->get('debug')) {
            $response['peak_memory_usage'] = round(memory_get_peak_usage(true) / 1024
                / 1024, 2);
        }

        // Send response status code
        $app->sendHeaders();

        // Send response
        echo json_encode($response, JSON_THROW_ON_ERROR);

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
    protected function allowAdd($data = []): bool
    {
        $categoryId = ArrayHelper::getValue($data, 'catid', $this->input->getInt('filter_category_id'), 'int');

        if ($categoryId) {
            // If the category has been passed in the data or URL check it.
            return $this->app->getIdentity()->authorise('core.create', 'com_bpgallery.category.' . $categoryId);
        }

        // In the absence of better information, revert to the component permissions.
        return parent::allowAdd();
    }

    /**
     * Method override to check if you can edit an existing record.
     *
     * @param   array   $data  An array of input data.
     * @param   string  $key   The name of the key for the primary key.
     *
     * @return  boolean
     */
    protected function allowEdit($data = [], $key = 'id'): bool
    {
        $recordId = (int)isset($data[$key]) ? $data[$key] : 0;
        $user     = $this->app->getIdentity();

        // Zero record (id:0), return component edit permission by calling parent controller method
        if (!$recordId) {
            return parent::allowEdit($data, $key);
        }

        // Check edit on the record asset (explicit or inherited)
        if ($user->authorise('core.edit', 'com_bpgallery.article.' . $recordId)) {
            return true;
        }

        // Check edit own on the record asset (explicit or inherited)
        if ($user->authorise('core.edit.own', 'com_bpgallery.article.' . $recordId)) {
            // Existing record already has an owner, get it
            $record = $this->getModel()->getItem($recordId);

            if (empty($record)) {
                return false;
            }

            // Grant if current user is owner of the record
            return (int)$user->id === (int)$record->created_by;
        }

        return false;
    }
}
