<?php

/**
 * @author        ${author.name} (${author.email})
 * @website        ${author.url}
 * @copyright    ${copyrights}
 * @license        ${license.url} ${license.name}
 * @package        ${package}
 * @subpackage        ${subpackage}
 */

defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

/**
 * Image controller class.
 */
class BPGalleryControllerImage extends JControllerForm
{
    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     */
    protected $text_prefix = 'COM_BPGALLERY_IMAGE';

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

        if ($categoryId) {
            // If the category has been passed in the URL check it.
            $allow = JFactory::getUser()->authorise('core.create',
                $this->option.'.category.'.$categoryId);
        }

        if ($allow !== null) {
            return $allow;
        }

        // In the absence of better information, revert to the component permissions.
        return false;
    }

    /**
     * Method override to check if you can edit an existing record.
     *
     * @param   array   $data  An array of input data.
     * @param   string  $key   The name of the key for the primary key.
     *
     * @return  boolean
     */
    protected function allowEdit($data = array(), $key = 'id')
    {
        $recordId   = (int) isset($data[$key]) ? $data[$key] : 0;
        $categoryId = 0;

        if ($recordId) {
            $categoryId = (int) $this->getModel()->getItem($recordId)->catid;
        }

        if ($categoryId) {
            // The category has been set. Check the category permissions.
            return JFactory::getUser()->authorise('core.edit',
                    $this->option.'.category.'.$categoryId);
        }

        // Since there is no asset tracking, revert to the component permissions.
        return false;
    }

    /**
	 * Method to run batch operations.
	 *
	 * @param   string  $model  The model
	 *
	 * @return  boolean  True on success.
	 */
	public function batch($model = null)
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Set the model
		$model = $this->getModel('Image', '', array());

		// Preset the redirect
		$this->setRedirect(JRoute::_('index.php?option=com_bpgallery&view=images' . $this->getRedirectToListAppend(), false));

		return parent::batch($model);
	}
}