<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

namespace BPExtensions\Component\BPGallery\Administrator\Model;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Table\Table;
use Joomla\Component\Categories\Administrator\Table\CategoryTable;
use Joomla\Database\DatabaseQuery;
use Joomla\Utilities\ArrayHelper;


/**
 * Methods supporting a list of images records.
 */
class ImagesModel extends ListModel
{
    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @throws Exception
     * @see     ListModel
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id',
                'a.id',
                'title',
                'a.title',
                'alias',
                'a.alias',
                'filename',
                'a.filename',
                'state',
                'a.state',
                'ordering',
                'a.ordering',
                'language',
                'a.language',
                'catid',
                'a.catid',
                'category_title',
                'checked_out',
                'a.checked_out',
                'checked_out_time',
                'a.checked_out_time',
                'created',
                'a.created',
                'publish_up',
                'a.publish_up',
                'publish_down',
                'a.publish_down',
                'category_id',
                'published',
                'level',
                'c.level',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to get the maximum ordering value for each category.
     *
     * @return  array
     */
    public function &getCategoryOrders(): array
    {
        if (!isset($this->cache['categoryorders'])) {
            $db    = $this->getDbo();
            $query = $db->getQuery(true)
                ->select('MAX(ordering) as ' . $db->quoteName('max') . ', catid')
                ->select('catid')
                ->from('#__bpgallery_images')
                ->group('catid');
            $db->setQuery($query);
            $this->cache['categoryorders'] = $db->loadAssocList('catid', 0);
        }

        return $this->cache['categoryorders'];
    }

    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param   string  $name     The table type to instantiate
     * @param   string  $prefix   A prefix for the table class name. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return  Table  A Table object
     * @throws Exception
     */
    public function getTable($name = 'Image', $prefix = 'Administrator', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  DatabaseQuery
     */
    protected function getListQuery(): DatabaseQuery
    {
        $db    = $this->getDbo();
        $query = $db->getQuery(true);

        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                'a.id AS id,'
                . 'a.title AS title,'
                . 'a.alias AS alias,'
                . 'a.filename AS filename,'
                . 'a.checked_out AS checked_out,'
                . 'a.checked_out_time AS checked_out_time,'
                . 'a.catid AS catid,'
                . 'a.state AS state,'
                . 'a.ordering AS ordering,'
                . 'a.language,'
                . 'a.publish_up,'
                . 'a.publish_down'
            )
        );
        $query->from($db->quoteName('#__bpgallery_images', 'a'));

        // Join over the language
        $query->select('l.title AS language_title, l.image AS language_image')
            ->join('LEFT', $db->quoteName('#__languages', 'l') . ' ON l.lang_code = a.language');

        // Join over the users for the checked out user.
        $query->select($db->quoteName('uc.name', 'editor'))
            ->join('LEFT', $db->quoteName('#__users', 'uc') . ' ON uc.id = a.checked_out');

        // Join over the asset groups.
        $query->select($db->quoteName('ag.title', 'access_level'))
            ->join(
                'LEFT',
                $db->quoteName('#__viewlevels', 'ag') . ' ON ' . $db->quoteName('ag.id') . ' = ' . $db->quoteName('a.access')
            );

        // Join over the categories.
        $query->select($db->quoteName('c.title', 'category_title'))
            ->join('LEFT', $db->quoteName('#__categories', 'c') . ' ON c.id = a.catid');

        // Filter by published state
        $published = $this->getState('filter.published');

        if (is_numeric($published)) {
            $query->where($db->quoteName('a.state') . ' = ' . (int)$published);
        } elseif ($published === '') {
            $query->where($db->quoteName('a.state') . ' IN (0, 1)');
        }

        // Filter by categories and by level
        $categoryId = $this->getState('filter.category_id', []);
        $level = $this->getState('filter.level');

        if (!is_array($categoryId)) {
            $categoryId = $categoryId ? array($categoryId) : [];
        }

        // Case: Using both categories filter and by level filter
        if (count($categoryId)) {
            $categoryId = ArrayHelper::toInteger($categoryId);
            $categoryTable = new CategoryTable($this->getDbo(), $this->getDispatcher());
            $subCatItemsWhere = [];

            foreach ($categoryId as $filter_catid) {
                $categoryTable->load($filter_catid);
                $subCatItemsWhere[] = '(' .
                    ($level ? 'c.level <= ' . ((int)$level + (int)$categoryTable->level - 1) . ' AND ' : '') .
                    'c.lft >= ' . (int)$categoryTable->lft . ' AND ' .
                    'c.rgt <= ' . (int)$categoryTable->rgt . ')';
            }

            $query->where('(' . implode(' OR ', $subCatItemsWhere) . ')');
        } // Case: Using only the by level filter
        elseif ($level) {
            $query->where('c.level <= ' . (int)$level);
        }

        // Filter by search in title
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where($db->quoteName('a.id') . ' = ' . (int)substr($search, 3));
            } else {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
                $query->where('(a.title LIKE ' . $search . ' OR a.alias LIKE ' . $search . ')');
            }
        }

        // Filter on the language.
        if ($language = $this->getState('filter.language')) {
            $query->where($db->quoteName('a.language') . ' = ' . $db->quote($language));
        }

        // Add the list ordering clause.
        $orderCol  = $this->state->get('list.ordering', 'a.id');
        $orderDirn = $this->state->get('list.direction', 'DESC');

        if ($orderCol === 'a.ordering' || $orderCol === 'category_title') {
            $orderCol = 'c.title ' . $orderDirn . ', a.ordering';
        }

        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string  A store id.
     */
    protected function getStoreId($id = ''): string
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.category_id');
        $id .= ':' . $this->getState('filter.language');
        $id .= ':' . $this->getState('filter.level');

        return parent::getStoreId($id);
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     */
    protected function populateState($ordering = 'a.id', $direction = 'desc'): void
    {
        // Load the filter state.
        $this->setState(
            'filter.search',
            $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string')
        );
        $this->setState(
            'filter.published',
            $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string')
        );
        $this->setState(
            'filter.category_id',
            $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id', '', 'cmd')
        );
        $this->setState(
            'filter.language',
            $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '', 'string')
        );
        $this->setState(
            'filter.level',
            $this->getUserStateFromRequest($this->context . '.filter.level', 'filter_level', '', 'cmd')
        );

        // Load the parameters.
        $this->setState('params', ComponentHelper::getParams('com_bpgallery'));

        // List state information.
        parent::populateState($ordering, $direction);
    }
}
