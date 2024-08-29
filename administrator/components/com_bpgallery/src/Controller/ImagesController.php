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

use BPExtensions\Component\BPGallery\Administrator\Model\ImageModel;
use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Model\BaseModel;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

/**
 * Images list controller class.
 */
class ImagesController extends AdminController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     */
    protected $text_prefix = 'COM_BPGALLERY_IMAGES';

    /**
     * Recreate image thumbnails.
     *
     * @return  void
     * @throws Exception
     */
    public function recreate(): void
    {

        // Check for request forgeries
        $this->checkToken();

        // Get items to remove from the request.
        $user        = $this->app->getIdentity();
        $ids         = $this->input->get('cid', [], 'int');
        $redirectUrl = 'index.php?option=com_bpgallery&view=' . $this->view_list . $this->getRedirectToListAppend();

        if (!is_array($ids) || count($ids) < 1) {
            \JLog::add(Text::_('JERROR_NO_ITEMS_SELECTED'), \JLog::WARNING, 'jerror');
        } else {

            // Access checks.
            foreach ($ids as $i => $id) {
                // Remove zero value resulting from input filter
                if ($id === 0) {
                    unset($ids[$i]);

                    continue;
                }

                if (!$user->authorise('core.edit.state', 'com_bpgallery.image.' . (int)$id)) {
                    // Prune items that you can't change.
                    unset($ids[$i]);
                    $this->app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), 'notice');
                }
            }

            if (empty($ids)) {
                $this->app->enqueueMessage(Text::_('JERROR_NO_ITEMS_SELECTED'), 'error');

                $this->setRedirect(Route::_($redirectUrl, false));

                return;
            }

            /**
             * Get the model.
             *
             * @var ImageModel $model
             */
            $model = $this->getModel();

            // Remove the items.
            if ($model->recreateThumbnails($ids)) {
                $this->setMessage(Text::plural($this->text_prefix . '_N_ITEMS_RECREATED', count($ids)));
            } else {
                $this->setMessage($model->getError(), 'error');
            }
        }

        $this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
    }

    /**
     * Method to get a model object, loading it if required.
     *
     * @param   string  $name    The model name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  BaseModel  The model.
     */
    public function getModel(
        $name = 'Image',
        $prefix = 'Administrator',
        $config = ['ignore_request' => true]
    ): BaseModel {
        return parent::getModel($name, $prefix, $config);
    }
}
