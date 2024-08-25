<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

namespace BPExtensions\Component\BPGallery\Site\Model;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Component\Categories\Administrator\Extension\CategoriesComponent;
use Joomla\Component\Categories\Administrator\Table\CategoryTable;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * Single category model for the images' category.
 */
class CategoryModel extends ListModel
{
    /**
     * Category items data
     *
     * @var array
     */
    protected $_item = null;

    protected $_images = null;

    protected $_siblings = null;

    protected $_children = null;

    protected $_parent = null;

    /**
     * The category that applies.
     *
     * @access    protected
     * @var        object
     */
    protected $_category = null;

    /**
     * The list of other gallery categories.
     *
     * @access    protected
     * @var       array
     */
    protected $_categories = null;

    /**
     * Constructor.
     *
     * @param array $config An optional associative array of configuration settings.
     *
     */
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'name', 'a.name',
                'state', 'a.state',
                'ordering', 'a.ordering'
            );
        }

        parent::__construct($config);
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
     *
     * @throws Exception
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        /**
         * @var CMSApplication $app
         */
        $app    = Factory::getApplication();
        $params = ComponentHelper::getParams('com_bpgallery');

        // Optional filter text
        $itemid = $app->input->get('Itemid', 0, 'int');
        $search = $app->getUserStateFromRequest('com_bpgallery.category.list.' . $itemid . '.filter-search',
            'filter-search', '', 'string');
        $this->setState('list.filter', $search);

        // Prepare parameters
        $menuParams = new Registry;
        if ($menu = $app->getMenu()->getActive()) {
            $menuParams->loadString($menu->getParams());
        }

        $mergedParams = clone $params;
        $mergedParams->merge($menuParams);

        // Fix Joomla! issue with use global on subform fields
        $this->mergeThumbnailsParams($params, $menuParams, $mergedParams);

        // List state information
        $format = $app->input->getWord('format');
        if ($format === 'feed') {
            $limit = $app->get('feed_limit');
        } else {
            $limit = $mergedParams->get('images_limit', $app->get('list_limit'));
        }
        $this->setState('list.limit', $limit);
        $limitstart = $app->input->get('limitstart', 0, 'uint');
        $this->setState('list.start', $limitstart);

        $orderCol = $app->input->get('filter_order', $mergedParams->get('initial_sort', 'ordering'));
        if (!in_array($orderCol, $this->filter_fields)) {
            $orderCol = 'ordering';
        }
        $this->setState('list.ordering', $orderCol);

        $listOrder = $app->input->get('filter_order_Dir', 'ASC');

        if (!in_array(strtoupper($listOrder), ['ASC', 'DESC', ''])) {
            $listOrder = 'ASC';
        }

        $this->setState('list.direction', $listOrder);

        // Category filter
        $id = $app->input->get('id', 0, 'int');
        $this->setState('filter.category_id', $id);

        // Group images by category (add lft data)
        $this->setState('list.group', $mergedParams->get('group_images', 0));

        // Category level filter
        $this->setState('filter.max_category_levels', $mergedParams->get('maxLevel', 1));
        $this->setState('filter.subcategories', true);

        $user = $app->getIdentity();

        if ((!$user->authorise('core.edit.state', 'com_bpgallery')) && (!$user->authorise(
                'core.edit',
                'com_bpgallery'
            ))) {
            // Limit to published for people who can't edit or edit.state.
            $this->setState('filter.published', 1);

            // Filter by start and end dates.
            $this->setState('filter.publish_date', true);
        }

        $this->setState('filter.language', Multilanguage::isEnabled());

        // Load the parameters.
        $this->setState('params', $mergedParams);
    }

    /**
     * Method to get a list of items.
     *
     * @return  mixed  An array of objects on success, false on failure.
     */
    public function getItems(): mixed
    {
        // Invoke the parent getItems method to get the main list
        $items = parent::getItems();

        // Convert the params field into an object, saving original in _params
        foreach ($items as $item) {
            if (is_string($item->params)) {
                $item->params = new Registry($item->params);
            }
        }

        return $items;
    }

    /**
     * Get the parent category.
     *
     * @return  mixed  An array of categories or false if an error occurs.
     *
     * @throws Exception
     */
    public function getParent(): mixed
    {
        if (!is_object($this->_item)) {
            $this->getCategory();
        }

        return $this->_parent;
    }

    /**
     * Method to get category data for the current category
     *
     * @return  object  The category object
     *
     * @throws Exception
     */
    public function getCategory()
    {
        if (!is_object($this->_item)) {
            /**
             * @var CMSApplication $app
             * @var Categories     $categories
             */
            $app = Factory::getApplication();
            $user = $app->getIdentity();

            $options = [];

            if ($params = $this->state->get('params')) {
                $options['countItems'] = $params->get('show_cat_num_articles',
                        1) || !$params->get('show_empty_categories_cat', 0);
                $options['access']     = $params->get('check_access_rights', 1);
            } else {
                $options['countItems'] = 0;
            }

            // User witch change stat have access to both published and unpublished items
            if ($user->authorise('core.edit.state', $this->option)) {
                $options['published'] = [0, 1, 2];
            }

            $categories = Factory::getApplication()->bootComponent('BPGallery')->getCategory($options);
            $this->_item = $categories->get($this->getState('filter.category_id', 'root'));
            if (is_object($this->_item)) {

                $asset = 'com_bpgallery.category.' . $this->_item->id;

                // Check general create permission.
                if ($user->authorise('core.create', $asset)) {
                    $this->_item->getParams()->set('access-create', true);
                }

                $this->_children = $this->_item->getChildren();
                $this->_parent = false;

                if ($this->_item->getParent()) {
                    $this->_parent = $this->_item->getParent();
                }

                $this->_rightsibling = $this->_item->getSibling();
                $this->_leftsibling = $this->_item->getSibling(false);
            } else {
                $this->_children = false;
                $this->_parent = false;
            }
        }

        return $this->_item;
    }

    /**
     * Get the sibling (adjacent) categories.
     *
     * @return  mixed  An array of categories or false if an error occurs.
     *
     * @throws Exception
     */
    public function &getLeftSibling(): mixed
    {
        if (!is_object($this->_item)) {
            $this->getCategory();
        }

        return $this->_leftsibling;
    }

    /**
     * Get the sibling (adjacent) categories.
     *
     * @return  mixed  An array of categories or false if an error occurs.
     *
     * @throws Exception
     */
    public function &getRightSibling(): mixed
    {
        if (!is_object($this->_item)) {
            $this->getCategory();
        }

        return $this->_rightsibling;
    }

    /**
     * Get the child categories.
     *
     * @return  mixed  An array of categories or false if an error occurs.
     *
     * @throws Exception
     */
    public function &getChildren(): mixed
    {
        if (!is_object($this->_item)) {
            $this->getCategory();
        }

        return $this->_children;
    }

    /**
     * Increment the hit counter for the category.
     *
     * @param integer $pk Optional primary key of the category to increment.
     *
     * @return  boolean  True if successful; false otherwise and internal error set.
     *
     * @throws Exception
     */
    public function hit($pk = 0)
    {
        $input         = Factory::getApplication()->getInput();
        $hitcount = $input->getInt('hitcount', 1);

        if ($hitcount) {
            /**
             * @var CategoriesComponent $component
             * @var CategoryTable       $table
             */
            $pk = (!empty($pk)) ? $pk : (int)$this->getState('filter.category_id');
            $component = Factory::getApplication()->bootComponent('Categories');
            $table     = $component->getMVCFactory()->createTable('Category');
            $table->load($pk) && $table->hit($pk);
        }

        return true;
    }

    /**
     * TODO: Verify if this method is required at all.
     *
     * Method to build an SQL query to load the list data.
     *
     * @return DatabaseQuery|QueryInterface
     * @throws Exception
     */
    protected function getListQuery()
    {
        $user = Factory::getApplication()->getIdentity();
        $groups = implode(',', $user->getAuthorisedViewLevels());

        // Create a new query object.
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        // Select required fields from the categories.
        $case_when = ' CASE WHEN ';
        $case_when .= $query->charLength('a.alias', '!=', '0');
        $case_when .= ' THEN ';
        $a_id      = $query->castAsChar('a.id');
        $case_when .= $query->concatenate(array($a_id, 'a.alias'), ':');
        $case_when .= ' ELSE ';
        $case_when .= $a_id . ' END as slug';

        $case_when1 = ' CASE WHEN ';
        $case_when1 .= $query->charLength('c.alias', '!=', '0');
        $case_when1 .= ' THEN ';
        $c_id       = $query->castAsChar('c.id');
        $case_when1 .= $query->concatenate(array($c_id, 'c.alias'), ':');
        $case_when1 .= ' ELSE ';
        $case_when1 .= $c_id . ' END as catslug';
        $query->select($this->getState(
                'list.select',
                'a.*,c.title AS catname'
            ) . ',' . $case_when . ',' . $case_when1)
            /**
             * TODO: we actually should be doing it but it's wrong this way
             *    . ' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(\':\', a.id, a.alias) ELSE a.id END as slug, '
             *    . ' CASE WHEN CHAR_LENGTH(c.alias) THEN CONCAT_WS(\':\', c.id, c.alias) ELSE c.id END AS catslug ');
             */
            ->from($db->quoteName('#__bpgallery_images') . ' AS a')
            ->join('LEFT', '#__categories AS c ON c.id = a.catid')
            ->where('a.access IN (' . $groups . ')');

        // We need to group images to add lft data
        if ($this->getState('list.group', 0)) {
            $query->select('c.lft as catlft');
        }

        // Filter by category.
        $categoryId = $this->getState('filter.category_id');
        if (is_numeric($categoryId)) {
            $type = $this->getState('filter.category_id.include', true) ? '= ' : '<> ';

            // Add subcategory check
            $includeSubcategories = $this->getState('filter.subcategories', false);
            $categoryEquals       = 'a.catid ' . $type . (int)$categoryId;

            if ($includeSubcategories) {
                $levels = (int)$this->getState('filter.max_category_levels', '1');

                // Create a subquery for the subcategory list
                $subQuery = $db->getQuery(true)
                    ->select('sub.id')
                    ->from('#__categories as sub')
                    ->join('INNER', '#__categories as this ON sub.lft > this.lft AND sub.rgt < this.rgt')
                    ->where('this.id = ' . (int)$categoryId);

                // Show only published categories images
                if (!Factory::getUser()->authorise('core.edit.state', $this->option)) {
                    $subQuery->where('sub.published = 1');

                    // User can change state, so show all categories
                } else {
                    $subQuery->where('sub.published IN(0,1,2)');
                }

                if ($levels >= 0) {
                    $subQuery->where('sub.level <= this.level + ' . $levels);
                }

                // Add the subquery to the main query
                $query->where('(' . $categoryEquals . ' OR a.catid IN (' . (string)$subQuery . '))');
            } else {
                $query->where($categoryEquals);
            }
        } elseif (is_array($categoryId) && (count($categoryId) > 0)) {
            $categoryId = ArrayHelper::toInteger($categoryId);
            $categoryId = implode(',', $categoryId);

            if (!empty($categoryId)) {
                $type = $this->getState('filter.category_id.include', true) ? 'IN' : 'NOT IN';
                $query->where('a.catid ' . $type . ' (' . $categoryId . ')');
            }
        }

        // Join over the users for the author and modified_by names.
        $query->select("CASE WHEN a.created_by_alias > ' ' THEN a.created_by_alias ELSE ua.name END AS author")
            ->select('ua.email AS author_email')
            ->join('LEFT', '#__users AS ua ON ua.id = a.created_by')
            ->join('LEFT', '#__users AS uam ON uam.id = a.modified_by');

        // Filter by state
        $state = $this->getState('filter.published');

        if (is_numeric($state)) {
            $query->where('a.state = ' . (int)$state);
        } else {
            $query->where('(a.state IN (0,1,2))');
        }

        // Filter by start and end dates.
        $nullDate = $db->quote($db->getNullDate());
        $nowDate = $db->quote(Factory::getDate()->toSql());

        if ($this->getState('filter.publish_date')) {
            $query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')')
                ->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');
        }

        // Filter by Date Range or Relative Date
        $dateFiltering = $this->getState('filter.date_filtering', 'off');
        $dateField = $this->getState('filter.date_field', 'a.created');

        switch ($dateFiltering) {
            case 'range':
                $startDateRange = $db->quote($this->getState('filter.start_date_range', $nullDate));
                $endDateRange = $db->quote($this->getState('filter.end_date_range', $nullDate));
                $query->where(
                    '(' . $dateField . ' >= ' . $startDateRange . ' AND ' . $dateField .
                    ' <= ' . $endDateRange . ')'
                );
                break;

            case 'relative':
                $relativeDate = (int)$this->getState('filter.relative_date', 0);
                $query->where(
                    $dateField . ' >= ' . $query->dateAdd($nowDate, -1 * $relativeDate, 'DAY')
                );
                break;

            case 'off':
            default:
                break;
        }

        // Filter by search in title
        $search = $this->getState('list.filter');
        if (!empty($search)) {
            $search = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where('(a.name LIKE ' . $search . ')');
        }

        // Filter by language
        if ($this->getState('filter.language')) {
            $query->where('a.language in (' . $db->quote(Factory::getApplication()->getLanguage()->getTag()) . ',' . $db->quote('*') . ')');
        }

        // Filter images ids
        $filter_image_id = $this->getState('filter.image_id');
        $filter_image_id = (!is_array($filter_image_id) and $filter_image_id > 0) ? [$filter_image_id] : $filter_image_id;
        $filter_image_id_include = $this->getState('filter.image_id.include', false);

        if (!empty($filter_image_id) and $filter_image_id_include) {
            $query->where('a.id IN(' . implode(',', $filter_image_id) . ')');
        } elseif (!empty($filter_image_id) and !$filter_image_id_include) {
            $query->where('a.id NOT IN(' . implode(',', $filter_image_id) . ')');
        }

        // Set sortname ordering if selected
        if ($this->getState('list.ordering') === 'sortname') {
            $query->order($db->escape('a.sortname1') . ' ' . $db->escape($this->getState('list.direction', 'ASC')))
                ->order($db->escape('a.sortname2') . ' ' . $db->escape($this->getState('list.direction', 'ASC')))
                ->order($db->escape('a.sortname3') . ' ' . $db->escape($this->getState('list.direction', 'ASC')));
        } else {
            $query->order($db->escape($this->getState('list.ordering', 'a.ordering')) . ' ' . $db->escape($this->getState('list.direction', 'ASC')));
        }

        return $query;
    }


    /**
     * Merge view dependent thumbnails settings. (Note: Fixes Joomla! issue with useglobal on subform fields)
     *
     * @param Registry $componentsParams Component parameters (defaults)
     * @param Registry $menuParams Menu parameters.
     * @param Registry $params Merged parameters.
     */
    protected function mergeThumbnailsParams(Registry $componentsParams, Registry $menuParams, Registry $params): void
    {
        $groups = ['thumbnails_size_category_default', 'thumbnails_size_category_squares', 'thumbnails_size_category_masonry'];

        foreach ($groups as $name) {
            // Component param
            $cparam = (new Registry())->loadArray((array)$componentsParams->get($name));

            // Menu param
            $mparam = (new Registry())->loadArray((array)$menuParams->get($name));

            // Merge parameters
            $merged = $cparam->merge($mparam);
            $params->set($name, $merged->toObject());
        }
    }


    /**
     * Method to get a JPagination object for the data set.
     *
     * @return Pagination|null A JPagination object for the data set.
     */
    public function getPagination(): ?Pagination
    {
        if (empty($this->_pagination)) {
            return null;
        }

        return $this->_pagination;
    }
}
