<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

defined('_JEXEC') or die;

/**
 * BP Gallery master display controller.
 */
class BPGalleryController extends JControllerLegacy
{
    /**
     * Method to display a view.
     *
     * @param boolean $cachable If true, the view output will be cached
     * @param array $urlparams An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     *
     * @return  BPGalleryController  This object to support chaining.
     *
     */
    public function display($cachable = false, $urlparams = array())
    {

        $view = $this->input->get('view', 'images');
        $this->input->set('view', $view);

        $layout = $this->input->get('layout', 'default');
        $id = $this->input->getInt('id');

        // Check for edit form.
        if ($view == 'image' && $layout == 'edit' && !$this->checkEditId('com_bpgallery.edit.image', $id)) {
            // Somehow the person just went to the form - we don't allow that.
            $this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
            $this->setMessage($this->getError(), 'error');
            $this->setRedirect(JRoute::_('index.php?option=com_bpgallery&view=images', false));

            return false;
        }

        return parent::display();
    }
}
