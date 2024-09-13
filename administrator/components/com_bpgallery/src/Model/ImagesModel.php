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

use BPExtensions\Component\BPGallery\Administrator\Extension\BPGalleryComponent;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Table\Table;
use Joomla\Component\Categories\Administrator\Table\CategoryTable;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;
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
                'modified',
                'a.modified',
                'publish_up',
                'a.publish_up',
                'publish_down',
                'a.publish_down',
                'state',
                'a.state',
                'author_id',
                'category_id',
                'a.category_id',
                'published',
                'a.published',
                'level',
                'c.level',
                'tag',
            ];

            if (Associations::isEnabled()) {
                $config['filter_fields'][] = 'association';
            }
        }

        parent::__construct($config);
    }

    /**
     * Get the filter form
     *
     * @param   array    $data      data
     * @param   boolean  $loadData  load current data
     *
     * @return  Form|null  The Form object or null if the form can't be found
     */
    public function getFilterForm($data = [], $loadData = true): ?Form
    {
        return parent::getFilterForm($data, $loadData);
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
        $app   = Factory::getApplication();
        $input = $app->getInput();

        $forcedLanguage = $input->get('forcedLanguage', '', 'cmd');

        // Adjust the context to support modal layouts.
        if ($layout = $input->get('layout')) {
            $this->context .= '.' . $layout;
        }

        // Adjust the context to support forced languages.
        if ($forcedLanguage) {
            $this->context .= '.' . $forcedLanguage;
        }

        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $featured = $this->getUserStateFromRequest($this->context . '.filter.featured', 'filter_featured', '');
        $this->setState('filter.featured', $featured);

        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);

        $level = $this->getUserStateFromRequest($this->context . '.filter.level', 'filter_level');
        $this->setState('filter.level', $level);

        $language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
        $this->setState('filter.language', $language);

        $formSubmitted = $input->post->get('form_submitted');

        // Gets the value of a user state variable and sets it in the session
        $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');
        $this->getUserStateFromRequest($this->context . '.filter.author_id', 'filter_author_id');
        $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id');
        $this->getUserStateFromRequest($this->context . '.filter.tag', 'filter_tag', '');

        if ($formSubmitted) {
            $access = $input->post->get('access');
            $this->setState('filter.access', $access);

            $authorId = $input->post->get('author_id');
            $this->setState('filter.author_id', $authorId);

            $categoryId = $input->post->get('category_id');
            $this->setState('filter.category_id', $categoryId);

            $tag = $input->post->get('tag');
            $this->setState('filter.tag', $tag);
        }

        // List state information.
        parent::populateState($ordering, $direction);

        // Force a language
        if (!empty($forcedLanguage)) {
            $this->setState('filter.language', $forcedLanguage);
            $this->setState('filter.forcedLanguage', $forcedLanguage);
        }
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
        $id .= ':' . serialize($this->getState('filter.access'));
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . serialize($this->getState('filter.category_id'));
        $id .= ':' . serialize($this->getState('filter.author_id'));
        $id .= ':' . $this->getState('filter.language');
        $id .= ':' . serialize($this->getState('filter.tag'));

        return parent::getStoreId($id);
    }


    /**
     * Build an SQL query to load the list data.
     *
     * @return  DatabaseQuery
     */
    protected function getListQuery(): DatabaseQuery
    {
        // Create a new query object.
        $db   = $this->getDatabase();
        $query = $db->getQuery(true);
        $user = $this->getCurrentUser();

        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                [
                    $db->qn('a.id'),
                    $db->qn('a.title'),
                    $db->qn('a.alias'),
                    $db->qn('a.filename'),
                    $db->qn('a.checked_out'),
                    $db->qn('a.checked_out_time'),
                    $db->qn('a.catid'),
                    $db->qn('a.state'),
                    $db->qn('a.access'),
                    $db->qn('a.created'),
                    $db->qn('a.created_by'),
                    $db->qn('a.created_by_alias'),
                    $db->qn('a.modified'),
                    $db->qn('a.ordering'),
                    $db->qn('a.language'),
                    $db->qn('a.publish_up'),
                    $db->qn('a.publish_down'),
                    $db->qn('a.intro'),
                    $db->qn('a.description'),
                    $db->qn('a.metadata'),
                ]
            )
        )
            ->select(
                [
                    $db->qn('l.title', 'language_title'),
                    $db->qn('l.image', 'language_image'),
                    $db->qn('uc.name', 'editor'),
                    $db->qn('ag.title', 'access_level'),
                    $db->qn('c.title', 'category_title'),
                    $db->qn('c.created_user_id', 'category_uid'),
                    $db->qn('c.level', 'category_level'),
                    $db->qn('c.published', 'category_published'),
                    $db->qn('parent.title', 'parent_category_title'),
                    $db->qn('parent.id', 'parent_category_id'),
                    $db->qn('parent.created_user_id', 'parent_category_uid'),
                    $db->qn('parent.level', 'parent_category_level'),
                    $db->qn('ua.name', 'author_name'),
                ]
            )
            ->from($db->qn('#__bpgallery_images', 'a'))
            ->join('LEFT', $db->qn('#__languages', 'l'), $db->qn('l.lang_code') . ' = ' . $db->qn('a.language'))
            ->join('LEFT', $db->qn('#__users', 'uc'), $db->qn('uc.id') . ' = ' . $db->qn('a.checked_out'))
            ->join('LEFT', $db->qn('#__viewlevels', 'ag'), $db->qn('ag.id') . ' = ' . $db->qn('a.access'))
            ->join('LEFT', $db->qn('#__categories', 'c'), $db->qn('c.id') . ' = ' . $db->qn('a.catid'))
            ->join('LEFT', $db->qn('#__categories', 'parent'), $db->qn('parent.id') . ' = ' . $db->qn('c.parent_id'))
            ->join('LEFT', $db->qn('#__users', 'ua'), $db->qn('ua.id') . ' = ' . $db->qn('a.created_by'));

        // Join over the associations.
        if (Associations::isEnabled()) {
            $subQuery = $db->getQuery(true)
                ->select('COUNT(' . $db->qn('asso1.id') . ') > 1')
                ->from($db->qn('#__associations', 'asso1'))
                ->join(
                    'INNER',
                    $db->qn('#__associations', 'asso2'),
                    $db->qn('asso1.key') . ' = ' . $db->qn('asso2.key')
                )
                ->where(
                    [
                        $db->qn('asso1.id') . ' = ' . $db->qn('a.id'),
                        $db->qn('asso1.context') . ' = ' . $db->quote('com_bpgallery.item'),
                    ]
                );

            $query->select('(' . $subQuery . ') AS ' . $db->qn('association'));
        }

        // Filter by access level.
        $access = $this->getState('filter.access');

        if (is_numeric($access)) {
            $access = (int)$access;
            $query->where($db->qn('a.access') . ' = :access')
                ->bind(':access', $access, ParameterType::INTEGER);
        } elseif (\is_array($access)) {
            $access = ArrayHelper::toInteger($access);
            $query->whereIn($db->qn('a.access'), $access);
        }

        // Filter by access level on categories.
        if (!$user->authorise('core.admin')) {
            $groups = $user->getAuthorisedViewLevels();
            $query->whereIn($db->qn('a.access'), $groups);
            $query->whereIn($db->qn('c.access'), $groups);
        }

        $published = (string)$this->getState('filter.published');

        if ($published !== '*') {
            if (is_numeric($published)) {
                $state = (int)$published;
                $query->where($db->qn('a.state') . ' = :state')
                    ->bind(':state', $state, ParameterType::INTEGER);
            } else {
                $query->whereIn(
                    $db->qn('a.state'),
                    [
                        BPGalleryComponent::CONDITION_PUBLISHED,
                        BPGalleryComponent::CONDITION_UNPUBLISHED,
                    ]
                );
            }
        }


        // Filter by categories and by level
        $categoryId = $this->getState('filter.category_id', []);
        $level     = (int)$this->getState('filter.level');

        if (!\is_array($categoryId)) {
            $categoryId = $categoryId ? [$categoryId] : [];
        }

        // Case: Using both categories filter and by level filter
        if (\count($categoryId)) {
            $categoryId    = ArrayHelper::toInteger($categoryId);
            $categoryTable = new CategoryTable($db);
            $subCatItemsWhere = [];

            foreach ($categoryId as $key => $filter_catid) {
                $categoryTable->load($filter_catid);

                // Because values to $query->bind() are passed by reference, using $query->bindArray() here instead to prevent overwriting.
                $valuesToBind = [$categoryTable->lft, $categoryTable->rgt];

                if ($level) {
                    $valuesToBind[] = $level + $categoryTable->level - 1;
                }

                // Bind values and get parameter names.
                $bounded = $query->bindArray($valuesToBind);

                $categoryWhere = $db->quoteName('c.lft') . ' >= ' . $bounded[0] . ' AND ' . $db->quoteName(
                        'c.rgt'
                    ) . ' <= ' . $bounded[1];

                if ($level) {
                    $categoryWhere .= ' AND ' . $db->quoteName('c.level') . ' <= ' . $bounded[2];
                }

                $subCatItemsWhere[] = '(' . $categoryWhere . ')';
            }

            $query->where('(' . implode(' OR ', $subCatItemsWhere) . ')');
        } elseif ($level = (int)$level) {
            // Case: Using only the by level filter
            $query->where($db->quoteName('c.level') . ' <= :level')
                ->bind(':level', $level, ParameterType::INTEGER);
        }

        // Filter by author
        $authorId = $this->getState('filter.author_id');

        if (is_numeric($authorId)) {
            $authorId = (int)$authorId;
            $type     = $this->getState('filter.author_id.include', true) ? ' = ' : ' <> ';
            $query->where($db->quoteName('a.created_by') . $type . ':authorId')
                ->bind(':authorId', $authorId, ParameterType::INTEGER);
        } elseif (\is_array($authorId)) {
            // Check to see if by_me is in the array
            if (\in_array('by_me', $authorId)) {
                // Replace by_me with the current user id in the array
                $authorId['by_me'] = $user->id;
            }

            $authorId = ArrayHelper::toInteger($authorId);
            $query->whereIn($db->quoteName('a.created_by'), $authorId);
        }


        // Filter by search in title.
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $search = (int)substr($search, 3);
                $query->where($db->quoteName('a.id') . ' = :search')
                    ->bind(':search', $search, ParameterType::INTEGER);
            } elseif (stripos($search, 'author:') === 0) {
                $search = '%' . substr($search, 7) . '%';
                $query->where(
                    '(' . $db->quoteName('ua.name') . ' LIKE :search1 OR ' . $db->quoteName(
                        'ua.username'
                    ) . ' LIKE :search2)'
                )
                    ->bind([':search1', ':search2'], $search);
            } elseif (stripos($search, 'content:') === 0) {
                $search = '%' . substr($search, 8) . '%';
                $query->where(
                    '(' . $db->quoteName('a.introtext') . ' LIKE :search1 OR ' . $db->quoteName(
                        'a.fulltext'
                    ) . ' LIKE :search2)'
                )
                    ->bind([':search1', ':search2'], $search);
            } else {
                $search = '%' . str_replace(' ', '%', trim($search)) . '%';
                $query->where(
                    '(' . $db->quoteName('a.title') . ' LIKE :search1 OR ' . $db->quoteName(
                        'a.alias'
                    ) . ' LIKE :search2'
                    . ' OR ' . $db->quoteName('a.intro') . ' LIKE :search3)'
                )
                    ->bind([':search1', ':search2'], $search);
            }
        }

        // Filter on the language.
        if ($language = $this->getState('filter.language')) {
            $query->where($db->quoteName('a.language') . ' = :language')
                ->bind(':language', $language);
        }

        // Filter by a single or group of tags.
        $tag = $this->getState('filter.tag');

        // Run simplified query when filtering by one tag.
        if (\is_array($tag) && \count($tag) === 1) {
            $tag = $tag[0];
        }

        if ($tag && \is_array($tag)) {
            $tag = ArrayHelper::toInteger($tag);

            $subQuery = $db->getQuery(true)
                ->select('DISTINCT ' . $db->quoteName('content_item_id'))
                ->from($db->quoteName('#__contentitem_tag_map'))
                ->where(
                    [
                        $db->quoteName('tag_id') . ' IN (' . implode(',', $query->bindArray($tag)) . ')',
                        $db->quoteName('type_alias') . ' = ' . $db->quote('com_bpgallery.image'),
                    ]
                );

            $query->join(
                'INNER',
                '(' . $subQuery . ') AS ' . $db->quoteName('tagmap'),
                $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id')
            );
        } elseif ($tag = (int)$tag) {
            $query->join(
                'INNER',
                $db->quoteName('#__contentitem_tag_map', 'tagmap'),
                $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id')
            )
                ->where(
                    [
                        $db->quoteName('tagmap.tag_id') . ' = :tag',
                        $db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_bpgallery.image'),
                    ]
                )
                ->bind(':tag', $tag, ParameterType::INTEGER);
        }

        // Add the list ordering clause.
        $orderCol  = $this->state->get('list.ordering', 'a.id');
        $orderDirn = $this->state->get('list.direction', 'DESC');

        if ($orderCol === 'a.ordering' || $orderCol === 'category_title') {
            $ordering = [
                $db->quoteName('c.title') . ' ' . $db->escape($orderDirn),
                $db->quoteName('a.ordering') . ' ' . $db->escape($orderDirn),
            ];
        } else {
            $ordering = $db->escape($orderCol) . ' ' . $db->escape($orderDirn);
        }

        $query->order($ordering);

        return $query;
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
                ->select('MAX(ordering) as ' . $db->qn('max') . ', catid')
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

}
