<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

namespace BPExtensions\Component\BPGallery\Administrator\View\Images;

defined('_JEXEC') or die;

use BPExtensions\Component\BPGallery\Administrator\Extension\BPGalleryComponent;
use BPExtensions\Component\BPGallery\Administrator\Helper\BPGalleryHelper;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Button\DropdownButton;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\User\CurrentUserTrait;
use SimpleXMLElement;

/**
 * View class for a list of images.
 */
class HtmlView extends BaseHtmlView
{
    use CurrentUserTrait;

    /**
     * Form object for search filters
     *
     * @var  \Joomla\CMS\Form\Form
     */
    public $filterForm;
    /**
     * The active search filters
     *
     * @var  array
     */
    public $activeFilters;
    /**
     * An array of items
     *
     * @var  array
     */
    protected $items;
    /**
     * The pagination object
     *
     * @var  \Joomla\CMS\Pagination\Pagination
     */
    protected $pagination;
    /**
     * The model state
     *
     * @var   object
     */
    protected $state;

    /**
     * Are hits being recorded on the site?
     *
     * @var   boolean
     */
    protected $hits = false;

    /**
     * All transition, which can be executed of one if the items
     *
     * @var  array
     */
    protected array $transitions = [];

    /**
     * Is this view an Empty State
     *
     * @var   boolean
     * @since 4.0.0
     */
    private bool $isEmptyState = false;

    /**
     * Method to display the view.
     *
     * @param   string  $tpl  A template file to load. [optional]
     *
     * @throws Exception
     */
    public function display($tpl = null): void
    {

        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        $this->categories = $this->get('CategoryOrders');

        if (!\count($this->items) && $this->isEmptyState = $this->get('IsEmptyState')) {
            $this->setLayout('emptystate');
        }

        // Check for errors.
        if (\count($errors = $this->get('Errors')) || $this->transitions === false) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        // We don't need toolbar in the modal window.
        if ($this->getLayout() !== 'modal') {
            $this->addToolbar();

            // We do not need to filter by language when multilingual is disabled
            if (!Multilanguage::isEnabled()) {
                unset($this->activeFilters['language']);
                $this->filterForm->removeField('language', 'filter');
            }
        } elseif ($forcedLanguage = Factory::getApplication()->input->get('forcedLanguage', '', 'CMD')) {
            // If the language is forced we can't allow to select the language, so transform the language selector filter into a hidden field.
            $languageXml = new SimpleXMLElement('<field name="language" type="hidden" default="' . $forcedLanguage . '" />');
            $this->filterForm->setField($languageXml, 'filter', true);

            // Also, unset the active language filter so the search tools is not open by default with this filter.
            unset($this->activeFilters['language']);

            // One last changes needed is to change the category filter to just show categories with All language or with the forced language.
            $this->filterForm->setFieldAttribute('category_id', 'language', '*,' . $forcedLanguage, 'filter');
        }

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     */
    protected function addToolbar(): void
    {
        $canDo = BPGalleryHelper::getActions('com_bpgallery', 'category', $this->state->get('filter.category_id'));
        $user  = Factory::getApplication()->getIdentity();

        ToolbarHelper::title(Text::_('COM_BPGALLERY_MANAGER_IMAGES'), 'image images');

        /**
         * @var Toolbar $toolbar
         */
        $toolbar = $this->getDocument()->getToolbar();

        if ($canDo->get('core.create') || \count($user->getAuthorisedCategories('com_bpgallery', 'core.create')) > 0) {
            $toolbar->addNew('image.add');

            $toolbar->popupButton('upload', 'COM_BPGALLERY_BUTTON_UPLOAD_LABEL')
                ->popupType('inline')
                ->icon('icon-upload')
                ->textHeader(Text::_('COM_BPGALLERY_IMAGES_UPLOAD_HEADER'))
                ->url('#bpgallery_upload_form')
                ->modalWidth('800px')
                ->modalHeight('fit-content');
        }

        if (!$this->isEmptyState && $canDo->get('core.edit.state')) {
            /** @var  DropdownButton $dropdown */
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();


            if ($canDo->get('core.edit.state')) {
                $childBar->basicButton('recreate', 'COM_BPGALLERY_TOOLBAR_RECREATE', 'images.recreate')
                    ->listCheck(true)
                    ->icon('icon-refresh');

                $childBar->publish('images.publish')->listCheck(true);

                $childBar->unpublish('images.unpublish')->listCheck(true);

                $childBar->archive('images.archive')->listCheck(true);

                $childBar->checkin('images.checkin');

                if ($this->state->get('filter.published') !== BPGalleryComponent::CONDITION_TRASHED) {
                    $childBar->trash('images.trash')->listCheck(true);
                }
            }

            // Add a batch button
            if (
                $user->authorise('core.create', 'com_bpgallery')
                && $user->authorise('core.edit', 'com_bpgallery')
            ) {
                $childBar->popupButton('batch', 'JTOOLBAR_BATCH')
                    ->popupType('inline')
                    ->textHeader(Text::_('COM_BPGALLERY_BATCH_OPTIONS'))
                    ->url('#joomla-dialog-batch')
                    ->modalWidth('800px')
                    ->modalHeight('fit-content')
                    ->listCheck(true);
            }
        }

        if (
            !$this->isEmptyState &&
            $this->state->get('filter.published') === BPGalleryComponent::CONDITION_TRASHED &&
            $canDo->get('core.delete')
        ) {
            $toolbar->delete('images.delete', 'JTOOLBAR_DELETE_FROM_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        if ($user->authorise('core.admin', 'com_bpgallery') || $user->authorise('core.options', 'com_bpgallery')) {
            $toolbar->preferences('com_bpgallery');
        }
    }

    /**
     * Returns an array of fields the table can be sorted by
     *
     * @return  array  Array containing the field name to sort by as the key and display text as value
     */
    protected function getSortFields(): array
    {
        return [
            'ordering'   => Text::_('JGRID_HEADING_ORDERING'),
            'a.state'    => Text::_('JSTATUS'),
            'a.title'    => Text::_('COM_BPGALLERY_HEADING_TITLE'),
            'a.filename' => Text::_('COM_BPGALLERY_HEADING_FILENAME'),
            'a.language' => Text::_('JGRID_HEADING_LANGUAGE'),
            'a.id'       => Text::_('JGRID_HEADING_ID')
        ];
    }
}
