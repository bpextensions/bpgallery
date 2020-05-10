<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

defined('_JEXEC') or die;

/**
 * Images list controller class.
 */
class BPGalleryControllerImages extends JControllerAdmin
{
    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     */
    protected $text_prefix = 'COM_BPGALLERY_IMAGES';

    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @throws Exception
     * @see     JControllerLegacy
     */
    public function __construct($config = array())
    {
        parent::__construct($config);
    }

    /**
     * Recreate image thumbnails.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function recreate()
    {

        // Check for request forgeries
        $this->checkToken();

        // Get items to remove from the request.
        $cid = $this->input->get('cid', array(), 'array');
        if (!is_array($cid) || count($cid) < 1) {
            \JLog::add(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), \JLog::WARNING, 'jerror');
        } else {

            /**
             * Get the model.
             *
             * @var BPGalleryModelImage $model
             */
            $model = $this->getModel();

            // Make sure the item ids are integers
            $cid = ArrayHelper::toInteger($cid);

            // Remove the items.
            if ($model->recreateThumbnails($cid)) {
                $this->setMessage(Text::plural($this->text_prefix . '_N_ITEMS_RECREATED', count($cid)));
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
     * @return  JModelLegacy  The model.
     *
     * @since   1.6
     */
    public function getModel($name = 'Image', $prefix = 'BPGalleryModel', $config = array('ignore_request' => true))
    {
        return parent::getModel($name, $prefix, $config);
    }
}
