<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

namespace BPExtensions\Component\BPGallery\Administrator\View;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarFactoryInterface;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Content\Administrator\Helper\ContentHelper;
use SimpleXMLElement;

/**
 * View class for a list of images.
 */
class HtmlView extends BaseHtmlView
{
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
        $canDo = ContentHelper::getActions('com_bpgallery', 'category', $this->state->get('filter.category_id'));
        $user  = Factory::getApplication()->getIdentity();

        ToolbarHelper::title(Text::_('COM_BPGALLERY_MANAGER_IMAGES'), 'image images');

        /**
         * @var Toolbar $toolbar
         */
        $toolbar = Factory::getContainer()->get(ToolbarFactoryInterface::class)->createToolbar();

        if (count($user->getAuthorisedCategories('com_bpgallery', 'core.create')) > 0) {
            $toolbar->addNew('image.add');
        }

        if (($canDo->get('core.edit'))) {
            $toolbar->editList('image.edit');
            $toolbar->custom(
                'images.recreate',
                'loop.png',
                'loop_f2.png',
                'COM_BPGALLERY_TOOLBAR_RECREATE',
                true
            );
        }

        if ($canDo->get('core.edit.state')) {
            if ($this->state->get('filter.published') != 2) {
                $toolbar->publish('images.publish', 'JTOOLBAR_PUBLISH', true);
                $toolbar->unpublish('images.unpublish', 'JTOOLBAR_UNPUBLISH', true);
            }

            if ($this->state->get('filter.published') != -1) {
                if ($this->state->get('filter.published') != 2) {
                    $toolbar->archiveList('images.archive');
                } elseif ($this->state->get('filter.published') == 2) {
                    $toolbar->unarchiveList('images.publish');
                }
            }
        }

        if ($canDo->get('core.edit.state')) {
            $toolbar->checkin('images.checkin');
        }

        // Add a batch button
        if ($user->authorise('core.create', 'com_bpgallery')
            && $user->authorise('core.edit', 'com_bpgallery')
            && $user->authorise('core.edit.state', 'com_bpgallery')) {
            $title = Text::_('JTOOLBAR_BATCH');

            // Instantiate a new JLayoutFile instance and render the batch button
            $layout = new FileLayout('joomla.toolbar.batch');

            $dhtml = $layout->render(['title' => $title]);
            $toolbar->customButton('Custom', $dhtml, 'batch');
        }

        if ($this->state->get('filter.published') === -2 && $canDo->get('core.delete')) {
            $toolbar->delete('images.delete')
                ->text('JTOOLBAR_EMPTY_TRASH')
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
