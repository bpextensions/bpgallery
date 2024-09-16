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

use BPExtensions\Component\BPGallery\Site\Helper\RouteHelper;
use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Toolbar;
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
        $this->canDo = ContentHelper::getActions('com_bpgallery', 'image', $this->item->id);

        if ($this->getLayout() === 'modalreturn') {
            parent::display($tpl);

            return;
        }

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        // If we are forcing a language in modal (used for associations).
        if ($this->getLayout() === 'modal' && $forcedLanguage = Factory::getApplication()->input->get(
                'forcedLanguage',
                '',
                'cmd'
            )) {
            // Set the language field to the forcedLanguage and disable changing it.
            $this->form->setValue('language', null, $forcedLanguage);
            $this->form->setFieldAttribute('language', 'readonly', 'true');

            // Only allow to select categories with All language or with the forced language.
            $this->form->setFieldAttribute('catid', 'language', '*,' . $forcedLanguage);

            // Only allow to select tags with All language or with the forced language.
            $this->form->setFieldAttribute('tags', 'language', '*,' . $forcedLanguage);
        }

        if ($this->getLayout() !== 'modal') {
            $this->addToolbar();
        } else {
            $this->addModalToolbar();
        }

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
        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        $user       = $this->getCurrentUser();
        $userId     = $user->id;
        $isNew      = ($this->item->id == 0);
        $checkedOut = !(\is_null($this->item->checked_out) || $this->item->checked_out == $userId);
        /**
         * @var Toolbar $toolbar
         */
        $toolbar = $this->getDocument()->getToolbar();

        // Built the actions for new and existing records.
        $canDo      = $this->canDo;

        ToolbarHelper::title(
            Text::_('COM_BPGALLERY_PAGE_' . ($checkedOut ? 'VIEW_IMAGE' : ($isNew ? 'ADD_IMAGE' : 'EDIT_IMAGE'))),
            'image image-add'
        );

        // For new records, check the create permission.
        if ($isNew && (\count($user->getAuthorisedCategories('com_bpgallery', 'core.create')) > 0)) {
            $toolbar->apply('image.apply');

            $saveGroup = $toolbar->dropdownButton('save-group');

            $saveGroup->configure(
                function (Toolbar $childBar) use ($user) {
                    $childBar->save('image.save');

                    if ($user->authorise('core.create', 'com_menus.menu')) {
                        $childBar->save('image.save2menu', 'JTOOLBAR_SAVE_TO_MENU');
                    }

                    $childBar->save2new('image.save2new');
                }
            );

            $toolbar->cancel('image.cancel', 'JTOOLBAR_CANCEL');
        } else {
            // Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
            $itemEditable = $canDo->get('core.edit') || ($canDo->get(
                        'core.edit.own'
                    ) && $this->item->created_by == $userId);

            if (!$checkedOut && $itemEditable) {
                $toolbar->apply('image.apply');
            }

            $saveGroup = $toolbar->dropdownButton('save-group');

            $saveGroup->configure(
                function (Toolbar $childBar) use ($checkedOut, $itemEditable, $canDo, $user) {
                    // Can't save the record if it's checked out and editable
                    if (!$checkedOut && $itemEditable) {
                        $childBar->save('image.save');

                        // We can save this record, but check the create permission to see if we can return to make a new one.
                        if ($canDo->get('core.create')) {
                            $childBar->save2new('image.save2new');
                        }
                    }

                    // If checked out, we can still save2menu
                    if ($user->authorise('core.create', 'com_menus.menu')) {
                        $childBar->save('image.save2menu', 'JTOOLBAR_SAVE_TO_MENU');
                    }

                    // If checked out, we can still save
                    if ($canDo->get('core.create')) {
                        $childBar->save2copy('image.save2copy');
                    }
                }
            );

            $toolbar->cancel('image.cancel', 'JTOOLBAR_CLOSE');

            if (!$isNew) {
                if (ComponentHelper::isEnabled('com_contenthistory') && $this->state->params->get(
                        'save_history',
                        0
                    ) && $itemEditable) {
                    $toolbar->versions('com_bpgallery.image', $this->item->id);
                }

                $url = RouteHelper::getImageRoute(
                    $this->item->id . ':' . $this->item->alias,
                    $this->item->catid,
                    $this->item->language
                );

                $toolbar->preview(Route::link('site', $url, true), 'JGLOBAL_PREVIEW')
                    ->bodyHeight(80)
                    ->modalWidth(90);

                if (PluginHelper::isEnabled('system', 'jooa11y')) {
                    $toolbar->jooa11y(Route::link('site', $url . '&jooa11y=1', true), 'JGLOBAL_JOOA11Y')
                        ->bodyHeight(80)
                        ->modalWidth(90);
                }

                if (Associations::isEnabled() && ComponentHelper::isEnabled('com_associations')) {
                    $toolbar->standardButton('associations', 'JTOOLBAR_ASSOCIATIONS', 'image.editAssociations')
                        ->icon('icon-contract')
                        ->listCheck(false);
                }
            }
        }

        $toolbar->divider();
        $toolbar->inlinehelp();
    }

    /**
     * Add the modal toolbar.
     *
     * @return  void
     *
     * @throws  \Exception
     */
    protected function addModalToolbar(): void
    {
        $user       = $this->getCurrentUser();
        $userId     = $user->id;
        $isNew      = ($this->item->id == 0);
        $checkedOut = !(\is_null($this->item->checked_out) || $this->item->checked_out == $userId);
        $toolbar    = Toolbar::getInstance();

        // Build the actions for new and existing records.
        $canDo = $this->canDo;

        ToolbarHelper::title(
            Text::_('COM_BPGALLERY_PAGE_' . ($checkedOut ? 'VIEW_IMAGE' : ($isNew ? 'ADD_IMAGE' : 'EDIT_IMAGE'))),
            'image image-add'
        );

        $canCreate = $isNew && (\count($user->getAuthorisedCategories('com_bpgallery', 'core.create')) > 0);
        $canEdit   = $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId);

        // For new records, check the create permission.
        if ($canCreate || $canEdit) {
            $toolbar->apply('image.apply');
            $toolbar->save('image.save');
        }

        $toolbar->cancel('image.cancel');
    }
}
