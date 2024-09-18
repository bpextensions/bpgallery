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

use BPExtensions\Component\BPGallery\Administrator\Extension\BPGalleryComponent;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * Single category model for the images' category.
 */
class CategoryModel extends ListModel
{
    use CurrentUserTrait;

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
            $orderCol = 'a.ordering';
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

        $user = $this->getCurrentUser();

        if ((!$user->authorise('core.edit.state', 'com_bpgallery')) && (!$user->authorise(
                'core.edit',
                'com_bpgallery'
            ))) {
            // Filter on published for those who do not have edit or edit.state rights.
            $this->setState('filter.published', BPGalleryComponent::CONDITION_PUBLISHED);
        }

        $this->setState('filter.language', Multilanguage::isEnabled());


        // Process show_noauth parameter
        if ((!$params->get('show_noauth')) || (!ComponentHelper::getParams('com_bpgallery')->get('show_noauth'))) {
            $this->setState('filter.access', true);
        } else {
            $this->setState('filter.access', false);
        }

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
     * @return  object|array  The category object
     *
     * @throws Exception
     */
    public function getCategory(): object|array
    {
        if (!is_object($this->_item)) {
            /**
             * @var Categories     $categories
             */
            $user = $this->getCurrentUser();

            $options = [];

            if ($params = $this->state->get('params')) {
                $options['countItems'] = $params->get(
                        'show_cat_num_images',
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
     * TODO: Verify if this method is required at all.
     *
     * Method to build an SQL query to load the list data.
     *
     * @return DatabaseQuery|QueryInterface
     * @throws Exception
     */
    protected function getListQuery(): QueryInterface|DatabaseQuery
    {
        $user = Factory::getApplication()->getIdentity();
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $nowDate = Factory::getDate()->toSql();

        $conditionArchived    = BPGalleryComponent::CONDITION_ARCHIVED;
        $conditionUnpublished = BPGalleryComponent::CONDITION_UNPUBLISHED;

        // Select required fields from the categories.
        $query->select(
            $this->getState(
                'list.select',
                [
                    $db->qn('a.id'),
                    $db->qn('a.title'),
                    $db->qn('a.alias'),
                    $db->qn('a.intro'),
                    $db->qn('a.description'),
                    $db->qn('a.filename'),
                    $db->qn('a.alt'),
                    $db->qn('a.checked_out'),
                    $db->qn('a.checked_out_time'),
                    $db->qn('a.catid'),
                    $db->qn('a.created'),
                    $db->qn('a.created_by'),
                    $db->qn('a.created_by_alias'),
                    $db->qn('a.modified'),
                    $db->qn('a.modified_by'),
                    // Use created if publish_up is null
                    'CASE WHEN ' . $db->qn('a.publish_up') . ' IS NULL THEN ' . $db->qn('a.created')
                    . ' ELSE ' . $db->qn('a.publish_up') . ' END AS ' . $db->qn('publish_up'),
                    $db->qn('a.publish_down'),
                    $db->qn('a.params'),
                    $db->qn('a.metadata'),
                    $db->qn('a.access'),
                    $db->qn('a.language'),
                    $db->qn('a.ordering'),
                ]
            )
        )
            ->select(
                [
                    // Published/archived image in archived category is treated as archived image. If category is not published then force 0.
                    'CASE WHEN ' . $db->qn('c.published') . ' = 2 AND ' . $db->qn(
                        'a.state'
                    ) . ' > 0 THEN ' . $conditionArchived
                    . ' WHEN ' . $db->qn('c.published') . ' != 1 THEN ' . $conditionUnpublished
                    . ' ELSE ' . $db->qn('a.state') . ' END AS ' . $db->qn('state'),
                    $db->qn('c.title', 'category_title'),
                    $db->qn('c.path', 'category_route'),
                    $db->qn('c.access', 'category_access'),
                    $db->qn('c.alias', 'category_alias'),
                    $db->qn('c.language', 'category_language'),
                    $db->qn('c.published'),
                    $db->qn('c.published', 'parents_published'),
                    $db->qn('c.lft'),
                    'CASE WHEN ' . $db->qn('a.created_by_alias') . ' > ' . $db->quote(' ') . ' THEN ' . $db->qn(
                        'a.created_by_alias'
                    )
                    . ' ELSE ' . $db->qn('ua.name') . ' END AS ' . $db->qn('author'),
                    $db->qn('ua.email', 'author_email'),
                    $db->qn('uam.name', 'modified_by_name'),
                    $db->qn('parent.title', 'parent_title'),
                    $db->qn('parent.id', 'parent_id'),
                    $db->qn('parent.path', 'parent_route'),
                    $db->qn('parent.alias', 'parent_alias'),
                    $db->qn('parent.language', 'parent_language'),
                ]
            )
            ->from($db->qn('#__bpgallery_images') . ' AS a')
            ->join('LEFT', $db->qn('#__categories', 'c'), $db->qn('c.id') . ' = ' . $db->qn('a.catid'))
            ->join('LEFT', $db->qn('#__users', 'ua'), $db->qn('ua.id') . ' = ' . $db->qn('a.created_by'))
            ->join('LEFT', $db->qn('#__users', 'uam'), $db->qn('uam.id') . ' = ' . $db->qn('a.modified_by'))
            ->join('LEFT', $db->qn('#__categories', 'parent'), $db->qn('parent.id') . ' = ' . $db->qn('c.parent_id'));

        // Filter by access level.
        if ($this->getState('filter.access', true)) {
            $groups = $this->getState('filter.viewlevels', $user->getAuthorisedViewLevels());
            $query->whereIn($db->qn('a.access'), $groups)
                ->whereIn($db->qn('c.access'), $groups);
        }

        // Filter by published state
        $condition = $this->getState('filter.published');

        if (is_numeric($condition) && $condition == 2) {
            /**
             * If category is archived then image has to be published or archived.
             * Or category is published then image has to be archived.
             */
            $query->where(
                '((' . $db->qn('c.published') . ' = 2 AND ' . $db->qn('a.state') . ' > :conditionUnpublished)'
                . ' OR (' . $db->qn('c.published') . ' = 1 AND ' . $db->qn('a.state') . ' = :conditionArchived))'
            )
                ->bind(':conditionUnpublished', $conditionUnpublished, ParameterType::INTEGER)
                ->bind(':conditionArchived', $conditionArchived, ParameterType::INTEGER);
        } elseif (is_numeric($condition)) {
            $condition = (int)$condition;

            // Category has to be published
            $query->where($db->qn('c.published') . ' = 1 AND ' . $db->qn('a.state') . ' = :condition')
                ->bind(':condition', $condition, ParameterType::INTEGER);
        } elseif (\is_array($condition)) {
            // Category has to be published
            $query->where(
                $db->qn('c.published') . ' = 1 AND ' . $db->qn('a.state')
                . ' IN (' . implode(',', $query->bindArray($condition)) . ')'
            );
        }

        // Filter by a single or group of images.
        $imageId = $this->getState('filter.image_id');

        if (is_numeric($imageId)) {
            $imageId = (int)$imageId;
            $type    = $this->getState('filter.image_id.include', true) ? ' = ' : ' <> ';
            $query->where($db->qn('a.id') . $type . ':imageId')
                ->bind(':imageId', $imageId, ParameterType::INTEGER);
        } elseif (\is_array($imageId)) {
            $imageId = ArrayHelper::toInteger($imageId);

            if ($this->getState('filter.image_id.include', true)) {
                $query->whereIn($db->qn('a.id'), $imageId);
            } else {
                $query->whereNotIn($db->qn('a.id'), $imageId);
            }
        }

        // Filter by a single or group of categories
        $categoryId = $this->getState('filter.category_id');

        if (is_numeric($categoryId)) {
            $type = $this->getState('filter.category_id.include', true) ? ' = ' : ' <> ';

            // Add subcategory check
            $includeSubcategories = $this->getState('filter.subcategories', false);

            if ($includeSubcategories) {
                $categoryId = (int)$categoryId;
                $levels     = (int)$this->getState('filter.max_category_levels', 1);

                // Create a subquery for the subcategory list
                $subQuery = $db->getQuery(true)
                    ->select($db->qn('sub.id'))
                    ->from($db->qn('#__categories', 'sub'))
                    ->join(
                        'INNER',
                        $db->qn('#__categories', 'this'),
                        $db->qn('sub.lft') . ' > ' . $db->qn('this.lft')
                        . ' AND ' . $db->qn('sub.rgt') . ' < ' . $db->qn('this.rgt')
                    )
                    ->where($db->qn('this.id') . ' = :subCategoryId');

                $query->bind(':subCategoryId', $categoryId, ParameterType::INTEGER);

                if ($levels >= 0) {
                    $subQuery->where($db->qn('sub.level') . ' <= ' . $db->qn('this.level') . ' + :levels');
                    $query->bind(':levels', $levels, ParameterType::INTEGER);
                }

                // Add the subquery to the main query
                $query->where(
                    '(' . $db->qn('a.catid') . $type . ':categoryId OR ' . $db->qn(
                        'a.catid'
                    ) . ' IN (' . $subQuery . '))'
                );
                $query->bind(':categoryId', $categoryId, ParameterType::INTEGER);
            } else {
                $query->where($db->qn('a.catid') . $type . ':categoryId');
                $query->bind(':categoryId', $categoryId, ParameterType::INTEGER);
            }
        } elseif (\is_array($categoryId) && (\count($categoryId) > 0)) {
            $categoryId = ArrayHelper::toInteger($categoryId);

            if (!empty($categoryId)) {
                if ($this->getState('filter.category_id.include', true)) {
                    $query->whereIn($db->qn('a.catid'), $categoryId);
                } else {
                    $query->whereNotIn($db->qn('a.catid'), $categoryId);
                }
            }
        }

        // Filter by author
        $authorId    = $this->getState('filter.author_id');
        $authorWhere = '';

        if (is_numeric($authorId)) {
            $authorId    = (int)$authorId;
            $type        = $this->getState('filter.author_id.include', true) ? ' = ' : ' <> ';
            $authorWhere = $db->qn('a.created_by') . $type . ':authorId';
            $query->bind(':authorId', $authorId, ParameterType::INTEGER);
        } elseif (\is_array($authorId)) {
            $authorId = array_values(array_filter($authorId, 'is_numeric'));

            if ($authorId) {
                $type        = $this->getState('filter.author_id.include', true) ? ' IN' : ' NOT IN';
                $authorWhere = $db->qn('a.created_by') . $type . ' (' . implode(
                        ',',
                        $query->bindArray($authorId)
                    ) . ')';
            }
        }

        // Filter by author alias
        $authorAlias      = $this->getState('filter.author_alias');
        $authorAliasWhere = '';

        if (\is_string($authorAlias)) {
            $type             = $this->getState('filter.author_alias.include', true) ? ' = ' : ' <> ';
            $authorAliasWhere = $db->qn('a.created_by_alias') . $type . ':authorAlias';
            $query->bind(':authorAlias', $authorAlias);
        } elseif (\is_array($authorAlias) && !empty($authorAlias)) {
            $type             = $this->getState('filter.author_alias.include', true) ? ' IN' : ' NOT IN';
            $authorAliasWhere = $db->qn('a.created_by_alias') . $type
                . ' (' . implode(',', $query->bindArray($authorAlias, ParameterType::STRING)) . ')';
        }

        if (!empty($authorWhere) && !empty($authorAliasWhere)) {
            $query->where('(' . $authorWhere . ' OR ' . $authorAliasWhere . ')');
        } elseif (!empty($authorWhere) || !empty($authorAliasWhere)) {
            // One of these is empty, the other is not so we just add both
            $query->where($authorWhere . $authorAliasWhere);
        }

        // Filter by start and end dates.
        if ((!$user->authorise('core.edit.state', 'com_bpgallery')) && (!$user->authorise(
                'core.edit',
                'com_bpgallery'
            ))) {
            $query->where(
                [
                    '(' . $db->qn('a.publish_up') . ' IS NULL OR ' . $db->qn('a.publish_up') . ' <= :publishUp)',
                    '(' . $db->qn('a.publish_down') . ' IS NULL OR ' . $db->qn('a.publish_down') . ' >= :publishDown)',
                ]
            )
                ->bind(':publishUp', $nowDate)
                ->bind(':publishDown', $nowDate);
        }

        // Filter by Date Range or Relative Date
        $dateFiltering = $this->getState('filter.date_filtering', 'off');
        $dateField        = $db->escape($this->getState('filter.date_field', 'a.created'));

        switch ($dateFiltering) {
            case 'range':
                $startDateRange = $this->getState('filter.start_date_range', '');
                $endDateRange   = $this->getState('filter.end_date_range', '');

                if ($startDateRange || $endDateRange) {
                    $query->where($db->qn($dateField) . ' IS NOT NULL');

                    if ($startDateRange) {
                        $query->where($db->qn($dateField) . ' >= :startDateRange')
                            ->bind(':startDateRange', $startDateRange);
                    }

                    if ($endDateRange) {
                        $query->where($db->qn($dateField) . ' <= :endDateRange')
                            ->bind(':endDateRange', $endDateRange);
                    }
                }

                break;

            case 'relative':
                $relativeDate = (int)$this->getState('filter.relative_date', 0);
                $query->where(
                    $db->qn($dateField) . ' IS NOT NULL AND '
                    . $db->qn($dateField) . ' >= ' . $query->dateAdd($db->quote($nowDate), -1 * $relativeDate, 'DAY')
                );
                break;

            case 'off':
            default:
                break;
        }



        // Filter by language
        if ($this->getState('filter.language')) {
            $query->whereIn(
                $db->qn('a.language'),
                [Factory::getApplication()->getLanguage()->getTag(), '*'],
                ParameterType::STRING
            );
        }

        // Filter by a single or group of tags.
        $tagId = $this->getState('filter.tag');

        if (\is_array($tagId) && \count($tagId) === 1) {
            $tagId = current($tagId);
        }

        if (\is_array($tagId)) {
            $tagId = ArrayHelper::toInteger($tagId);

            if ($tagId) {
                $subQuery = $db->getQuery(true)
                    ->select('DISTINCT ' . $db->qn('content_item_id'))
                    ->from($db->qn('#__contentitem_tag_map'))
                    ->where(
                        [
                            $db->qn('tag_id') . ' IN (' . implode(',', $query->bindArray($tagId)) . ')',
                            $db->qn('type_alias') . ' = ' . $db->quote('com_bpgallery.image'),
                        ]
                    );

                $query->join(
                    'INNER',
                    '(' . $subQuery . ') AS ' . $db->qn('tagmap'),
                    $db->qn('tagmap.content_item_id') . ' = ' . $db->qn('a.id')
                );
            }
        } elseif ($tagId = (int)$tagId) {
            $query->join(
                'INNER',
                $db->qn('#__contentitem_tag_map', 'tagmap'),
                $db->qn('tagmap.content_item_id') . ' = ' . $db->qn('a.id')
                . ' AND ' . $db->qn('tagmap.type_alias') . ' = ' . $db->quote('com_bpgallery.image')
            )
                ->where($db->qn('tagmap.tag_id') . ' = :tagId')
                ->bind(':tagId', $tagId, ParameterType::INTEGER);
        }

        // Add the list ordering clause.
        $query->order(
            $db->escape($this->getState('list.ordering', 'a.ordering')) . ' ' . $db->escape(
                $this->getState('list.direction', 'ASC')
            )
        );

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

}
