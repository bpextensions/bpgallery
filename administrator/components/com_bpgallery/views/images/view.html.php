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

/**
 * View class for a list of images.
 */
class BPGalleryViewImages extends JViewLegacy
{
	/**
	 * Category data
	 *
	 * @var  array
	 */
	protected $categories;

	/**
	 * An array of items
	 *
	 * @var  array
	 */
	protected $items;

	/**
	 * The pagination object
	 *
	 * @var  JPagination
	 */
	protected $pagination;

	/**
	 * The model state
	 *
	 * @var  object
	 */
	protected $state;

	/**
	 * Method to display the view.
	 *
	 * @param   string  $tpl  A template file to load. [optional]
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @since   1.6
	 */
	public function display($tpl = null)
	{
        if ($this->getLayout() !== 'modal') {
            BPGalleryHelper::addSubmenu('images');
        }

        $this->categories = $this->get('CategoryOrders');
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode("\n", $errors));

            return false;
        }

        // Include the component HTML helpers.
        JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

        // We don't need toolbar in the modal window.
        if ($this->getLayout() !== 'modal') {
            $this->addToolbar();
            $this->sidebar = JHtmlSidebar::render();
        } else {
            // In article associations modal we need to remove language filter if forcing a language.
            // We also need to change the category filter to show show categories with All or the forced language.
            if ($forcedLanguage = JFactory::getApplication()->input->get('forcedLanguage', '', 'CMD')) {
                // If the language is forced we can't allow to select the language, so transform the language selector filter into a hidden field.
                $languageXml = new SimpleXMLElement('<field name="language" type="hidden" default="' . $forcedLanguage . '" />');
                $this->filterForm->setField($languageXml, 'filter', true);

                // Also, unset the active language filter so the search tools is not open by default with this filter.
                unset($this->activeFilters['language']);

                // One last changes needed is to change the category filter to just show categories with All language or with the forced language.
                $this->filterForm->setFieldAttribute('category_id', 'language', '*,' . $forcedLanguage, 'filter');
            }
        }

        return parent::display($tpl);
    }

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function addToolbar()
	{
		JLoader::register('BPGalleryHelper', JPATH_ADMINISTRATOR . '/components/com_bpgallery/helpers/bpgallery.php');

		$canDo = JHelperContent::getActions('com_bpgallery', 'category', $this->state->get('filter.category_id'));
		$user  = JFactory::getUser();

		JToolbarHelper::title(JText::_('COM_BPGALLERY_MANAGER_IMAGES'), 'image images');

		if (count($user->getAuthorisedCategories('com_bpgallery', 'core.create')) > 0)
		{
			JToolbarHelper::addNew('image.add');
		}

		if (($canDo->get('core.edit')))
		{
			JToolbarHelper::editList('image.edit');
			JToolBarHelper::custom('image.recreate', 'loop.png', 'loop_f2.png', 'COM_BPGALLERY_TOOLBAR_RECREATE', true);
		}

		if ($canDo->get('core.edit.state'))
		{
			if ($this->state->get('filter.published') != 2)
			{
				JToolbarHelper::publish('images.publish', 'JTOOLBAR_PUBLISH', true);
				JToolbarHelper::unpublish('images.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			}

			if ($this->state->get('filter.published') != -1)
			{
				if ($this->state->get('filter.published') != 2)
				{
					JToolbarHelper::archiveList('images.archive');
				}
				elseif ($this->state->get('filter.published') == 2)
				{
					JToolbarHelper::unarchiveList('images.publish');
				}
			}
		}

		if ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::checkin('images.checkin');
		}

		// Add a batch button
		if ($user->authorise('core.create', 'com_bpgallery')
			&& $user->authorise('core.edit', 'com_bpgallery')
			&& $user->authorise('core.edit.state', 'com_bpgallery'))
		{
			$title = JText::_('JTOOLBAR_BATCH');

			// Instantiate a new JLayoutFile instance and render the batch button
			$layout = new JLayoutFile('joomla.toolbar.batch');

			$dhtml = $layout->render(array('title' => $title));
			JToolbar::getInstance('toolbar')->appendButton('Custom', $dhtml, 'batch');
		}

		if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
		{
			JToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'images.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::trash('images.trash');
		}

		if ($user->authorise('core.admin', 'com_bpgallery') || $user->authorise('core.options', 'com_bpgallery'))
		{
			JToolbarHelper::preferences('com_bpgallery');
		}
	}

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 *
	 * @since   3.0
	 */
	protected function getSortFields()
	{
		return array(
			'ordering' => JText::_('JGRID_HEADING_ORDERING'),
			'a.state' => JText::_('JSTATUS'),
			'a.title' => JText::_('COM_BPGALLERY_HEADING_TITLE'),
			'a.filename' => JText::_('COM_BPGALLERY_HEADING_FILENAME'),
			'a.language' => JText::_('JGRID_HEADING_LANGUAGE'),
			'a.id' => JText::_('JGRID_HEADING_ID')
		);
	}
}
