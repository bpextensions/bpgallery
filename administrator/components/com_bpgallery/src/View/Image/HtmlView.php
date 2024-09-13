<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

namespace BPExtensions\Component\BPGallery\Administrator\View\Image;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarFactoryInterface;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View to edit an image.
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The JForm object
     *
     * @var  Form
     */
    protected $form;

    /**
     * The active item
     *
     * @var  object
     */
    protected $item;

    /**
     * The model state
     *
     * @var  object
     */
    protected $state;

    /**
     * Object containing permissions for the item
     *
     * @var  object
     */
    protected $canDo;

    /**
     * Display the view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void  A string if successful, otherwise an Error object.
     * @throws Exception
     */
    public function display($tpl = null): void
    {

        $this->form  = $this->get('Form');
        $this->item  = $this->get('Item');
        $this->state = $this->get('State');
        $this->canDo = ContentHelper::getActions('com_bpgallery');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        // If we are forcing a language in modal (used for associations).
        if ($this->getLayout() === 'modal' && $forcedLanguage = Factory::getApplication()->input->get('forcedLanguage',
                '', 'cmd')) {
            // Set the language field to the forcedLanguage and disable changing it.
            $this->form->setValue('language', null, $forcedLanguage);
            $this->form->setFieldAttribute('language', 'readonly', 'true');

            // Only allow to select categories with All language or with the forced language.
            $this->form->setFieldAttribute('catid', 'language', '*,' . $forcedLanguage);

            // Only allow to select tags with All language or with the forced language.
            $this->form->setFieldAttribute('tags', 'language', '*,' . $forcedLanguage);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     * @throws Exception
     */
    protected function addToolbar(): void
    {
        Factory::getApplication()->input->set('hidemainmenu', true);

        $user       = Factory::getApplication()->getIdentity();
        $userId     = $user->id;
        $isNew      = ($this->item->id === 0);
        $checkedOut = !(is_null($this->item->checked_out) || $this->item->checked_out === $userId);
        $canDo      = $this->canDo;

        ToolbarHelper::title(
            $isNew ? Text::_('COM_BPGALLERY_MANAGER_IMAGE_NEW') : Text::_('COM_BPGALLERY_MANAGER_IMAGE_EDIT'),
            'image images'
        );

        /**
         * @var Toolbar $toolbar
         */
        $toolbar = Factory::getContainer()->get(ToolbarFactoryInterface::class)->createToolbar();

        // If not checked out, can save the item.
        if (!$checkedOut && ($canDo->get('core.edit') || $canDo->get('core.create'))) {
            $toolbar->apply('image.apply');
            $toolbar->save('image.save');
        }

        if (!$checkedOut && $canDo->get('core.create')) {
            $toolbar->save2new('image.save2new');
        }

        // If an existing item, can save to a copy.
        if (!$isNew && $canDo->get('core.create')) {
            $toolbar->save2copy('image.save2copy');
        }

        if (!empty($this->item->id) && ComponentHelper::isEnabled('com_contenthistory') &&
            $this->state->params->get('save_history', 0) && $canDo->get('core.edit')) {
            $toolbar->versions('com_bpgallery.image', $this->item->id);
        }

        $toolbar->cancel('image.cancel');
    }
}
