<?php

/**
 * @author      ${author.name} (${author.email})
 * @website     ${author.url}
 * @copyright   ${copyrights}
 * @license     ${license.url} ${license.name}
 * @package     ${package}.Module
 * @subpackage  ModBPGallery
 */

defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Cache\Controller\OutputController;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

/**
 * Helper for mod_bpgallery
 *
 * @since  1.0
 */
final class ModBPGalleryHelper
{
    const CACHE_GROUP = 'mod_bpgallery';
    /**
     * Module params.
     *
     * @var Registry
     */
    protected $params;

    /**
     * Module instance.
     *
     * @var stdClass
     */
    protected $module;

    /**
     * Cache key.
     *
     * @var string
     */
    protected $cache_key;

    /**
     * ModBPGallery constructor.
     *
     * @param   stdClass  $module  Module instance to work on.
     */
    public function __construct($module, Registry $params)
    {
        $this->module    = $module;
        $this->params    = $params;
        $this->cache_key = 'mod_bpgallery_' . crc32($module->params . '_' . $module->id);
    }

    /**
     * Get a list of images from a specific category
     *
     * @param   \Joomla\Registry\Registry  &$params  object holding the models parameters
     *
     * @return  mixed
     * @throws Exception
     *
     * @since  1.0
     */
    public function getList()
    {
        /**
         * @var OutputController $cache
         */
        $cache = Cache::getInstance('output');
        $data  = $cache->get($this->cache_key, static::CACHE_GROUP);
        if ($data === false) {
            $data = $this->getItems();
            $cache->store($data, $this->cache_key, static::CACHE_GROUP);
        }

        return $data;
    }

    /**
     * Get module images list.
     *
     * @return array
     *
     * @throws Exception
     */
    protected function getItems(): array
    {

        // Get an instance of the generic articles model
        $model = ListModel::getInstance('Category', 'BPGalleryModel', array('ignore_request' => true));

        // Set application parameters in model
        $app       = Factory::getApplication();
        $appParams = $app->getParams();
        $user      = Factory::getUser();
        $model->setState('params', $appParams);

        $model->setState('list.start', 0);
        if ((!$user->authorise('core.edit.state', 'com_bpgallery')) && (!$user->authorise(
                'core.edit',
                'com_bpgallery'
            ))) {
            $model->setState('filter.published', 1);
        }

        // Set the filters based on the module params
        $model->setState('list.limit', (int)$this->params->get('count', 0));

        // Access filter
        $access     = !ComponentHelper::getParams('com_bpgallery')->get('show_noauth');
        $authorised = Access::getAuthorisedViewLevels(JFactory::getUser()->get('id'));
        $model->setState('filter.access', $access);

        // Filter category
        $catids = $this->params->get('catid');
        $model->setState('filter.category_id.include', (bool)$this->params->get('category_filtering_type', 1));

        // Category filter
        if ($catids) {
            $catids = $this->getSubcategories($catids, $access);
            $model->setState('filter.category_id', $catids);
        }

        // Ordering
        $ordering = $this->params->get('image_ordering', 'a.ordering');

        switch ($ordering) {
            case 'random':
                $model->setState('list.ordering', JFactory::getDbo()->getQuery(true)->Rand());
                break;

            default:
                $model->setState('list.ordering', $ordering);
                $model->setState('list.direction', $this->params->get('image_ordering_direction', 'ASC'));
                break;
        }
        $excluded_images = $this->params->get('excluded_images', '');

        if ($excluded_images) {
            $excluded_images = explode("\r\n", $excluded_images);
            $model->setState('filter.image_id', $excluded_images);

            // Exclude
            $model->setState('filter.image_id.include', false);
        }

        $date_filtering = $this->params->get('date_filtering', 'off');

        if ($date_filtering !== 'off') {
            $model->setState('filter.date_filtering', $date_filtering);
            $model->setState('filter.date_field', $this->params->get('date_field', 'a.created'));
            $model->setState('filter.start_date_range', $this->params->get('start_date_range', '1000-01-01 00:00:00'));
            $model->setState('filter.end_date_range', $this->params->get('end_date_range', '9999-12-31 23:59:59'));
            $model->setState('filter.relative_date', $this->params->get('relative_date', 30));
        }

        // Filter by language
        $model->setState('filter.language', $app->getLanguageFilter());

        $items = $model->getItems();

        // Display options
        $show_date        = $this->params->get('show_date', 0);
        $show_date_field  = $this->params->get('show_date_field', 'created');
        $show_date_format = $this->params->get('show_date_format', 'Y-m-d H:i:s');
        $show_category    = $this->params->get('show_category', 0);
        $show_author      = $this->params->get('show_author', 0);

        // Prepare data for display using display options
        foreach ($items as &$item) {
            $item->slug    = $item->id . ':' . $item->alias;
            $item->catslug = $item->catid . ':' . $item->catslug;


            if ($access || in_array($item->access, $authorised)) {
                // We know that user has the privilege to view the article
                $item->link = Route::_(BPGalleryHelperRoute::getImageRoute($item->slug, $item->catid, $item->language));
            } else {
                $menu      = $app->getMenu();
                $menuitems = $menu->getItems('link', 'index.php?option=com_users&view=login');

                if (isset($menuitems[0])) {
                    $Itemid = $menuitems[0]->id;
                } elseif ($app->input->getInt('Itemid') > 0) {
                    // Use Itemid from requesting page only if there is no existing menu
                    $Itemid = $app->input->getInt('Itemid');
                }

                $item->link = Route::_('index.php?option=com_users&view=login&Itemid=' . $Itemid);
            }

            // Used for styling the active article

            if ($show_date) {
                $item->displayDate = JHtml::_('date', $item->$show_date_field, $show_date_format);
            }

            if ($item->catid) {
                $item->displayCategoryLink  = Route::_(BPGalleryHelperRoute::getCategoryRoute($item->catid));
                $item->displayCategoryTitle = $show_category ? '<a href="' . $item->displayCategoryLink . '">' . $item->category_title . '</a>' : '';
            } else {
                $item->displayCategoryTitle = $show_category ? $item->category_title : '';
            }

            $item->displayAuthorName = $show_author ? $item->author : '';

            $item->displayDescription = $item->description;
        }

        return $items;
    }


    /**
     * Get all subcategories for the selected categories.
     *
     * @param   array  $selected_categories  Selected categories IDs
     * @param   int    $access               Categories access filter.
     *
     * @return array
     */
    protected function getSubcategories(array $selected_categories, int $access): array
    {
        $ids = $selected_categories;

        if ($this->params->get('show_child_category_articles', 0) && (int)$this->params->get('levels', 0) > 0) {
            // Get an instance of the generic categories model
            $categories = ListModel::getInstance('Categories', 'BPGalleryModel', array('ignore_request' => true));
            $categories->setState('params', $this->params);
            $levels = $this->params->get('levels', 1) ?: 9999;
            $categories->setState('filter.get_children', $levels);
            $categories->setState('filter.published', 1);
            $categories->setState('filter.access', $access);
            $additional_catids = array();

            foreach ($ids as $catid) {
                $categories->setState('filter.parentId', $catid);
                $items = $categories->getItems(true);

                if ($items) {
                    foreach ($items as $category) {
                        $condition = (($category->level - $categories->getParent()->level) <= $levels);

                        if ($condition) {
                            $additional_catids[] = $category->id;
                        }
                    }
                }
            }

            $ids = array_unique(array_merge($ids, $additional_catids));
        }

        return $ids;
    }
}
