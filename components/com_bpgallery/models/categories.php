<?php

/**
 * @author      ${author.name} (${author.email})
 * @website     ${author.url}
 * @copyright   ${copyrights}
 * @license     ${license.url} ${license.name}
 * @package     ${package}.Component
 * @subpackage  BPGallery
 */

defined('_JEXEC') or die;

use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Registry\Registry;

/**
 * This models supports retrieving lists of image categories.
 *
 * @since  1.0
 */
final class BPGalleryModelCategories extends ListModel
{
    /**
     * Model context string.
     *
     * @var        string
     */
    public $_context = 'com_bpgallery.categories';

    /**
     * The category context (allows other extensions to derived from this model).
     *
     * @var        string
     */
    protected $_extension = 'com_bpgallery';

    private $_parent = null;

    /**
     * Get the parent.
     *
     * @return  object  An array of data items on success, false on failure.
     *
     * @throws Exception
     *
     * @since   1.0
     */
    public function getParent()
    {
        if (!is_object($this->_parent)) {
            $this->getItems();
        }

        return $this->_parent;
    }

    /**
     * Redefine the function an add some properties to make the styling more easy
     *
     * @param bool $recursive True if you want to return children recursively.
     *
     * @return  mixed  An array of data items on success, false on failure.
     * @throws Exception
     *
     * @since   1.0
     */
    public function getItems($recursive = false)
    {
        $store = $this->getStoreId();

        if (!isset($this->cache[$store])) {
            $app = JFactory::getApplication();
            $menu = $app->getMenu();
            $active = $menu->getActive();
            $params = new Registry;

            if ($active) {
                $params->loadString($active->params);
            }

            $options = array();
            $options['countItems'] = $params->get('show_cat_num_articles_cat', 1) || !$params->get('show_empty_categories_cat', 0);
            $categories = Categories::getInstance('BPGallery', $options);
            $this->_parent = $categories->get($this->getState('filter.parentId', 'root'));

            if (is_object($this->_parent)) {
                $this->cache[$store] = $this->_parent->getChildren($recursive);
            } else {
                $this->cache[$store] = false;
            }
        }

        return $this->cache[$store];
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param string $id A prefix for the store id.
     *
     * @return  string  A store id.
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.extension');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.access');
        $id .= ':' . $this->getState('filter.parentId');

        return parent::getStoreId($id);
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @param string $ordering The field to order on.
     * @param string $direction The direction to order on.
     *
     * @return  void
     * @throws Exception
     *
     * @since   1.0
     */
    protected function populateState($ordering = null, $direction = null)
    {
        $app = Factory::getApplication();
        $this->setState('filter.extension', $this->_extension);

        // Get the parent id if defined.
        $parentId = $app->input->getInt('id');
        $this->setState('filter.parentId', $parentId);

        $params = $app->getParams();
        $this->setState('params', $params);

        $this->setState('filter.published', 1);
        $this->setState('filter.access', true);
    }
}
