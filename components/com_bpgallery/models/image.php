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

use Joomla\Registry\Registry;

/**
 * Single image model for a BP Galllery.
 *
 * @package     ${package}
 * @subpackage  com_bpgallery
 * @since       1.0
 */
class BPGalleryModelImage extends JModelForm
{
    /**
     * The name of the view for a single item
     *
     * @since   1.0
     */
    protected $view_item = 'image';

    /**
     * A loaded item
     *
     * @since   1.0
     */
    protected $_item = null;

    /**
     * Model context string.
     *
     * @var        string
     */
    protected $_context = 'com_bpgallery-image';

    public function getForm($data = array(), $loadData = true)
    {
    }

    /**
     * Gets a image
     *
     * @param integer $pk Id for the image
     *
     * @return  mixed Object or null
     *
     * @since   1.0.0
     */
    public function &getItem($pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int)$this->getState('image.id');

        if ($this->_item === null) {
            $this->_item = array();
        }

        if (!isset($this->_item[$pk])) {
            try {
                $db = $this->getDbo();
                $query = $db->getQuery(true);

                // Changes for sqlsrv
                $case_when = ' CASE WHEN ';
                $case_when .= $query->charLength('a.alias', '!=', '0');
                $case_when .= ' THEN ';
                $a_id = $query->castAsChar('a.id');
                $case_when .= $query->concatenate(array($a_id, 'a.alias'), ':');
                $case_when .= ' ELSE ';
                $case_when .= $a_id . ' END as slug';

                $case_when1 = ' CASE WHEN ';
                $case_when1 .= $query->charLength('c.alias', '!=', '0');
                $case_when1 .= ' THEN ';
                $c_id = $query->castAsChar('c.id');
                $case_when1 .= $query->concatenate(array($c_id, 'c.alias'), ':');
                $case_when1 .= ' ELSE ';
                $case_when1 .= $c_id . ' END as catslug';

                $query->select($this->getState('item.select', 'a.*') . ',' . $case_when . ',' . $case_when1)
                    ->from('#__bpgallery_images AS a')

                    // Join on category table.
                    ->select('c.title AS category_title, c.alias AS category_alias, c.access AS category_access')
                    ->join('LEFT', '#__categories AS c on c.id = a.catid')

                    // Join over the categories to get parent category titles
                    ->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias')
                    ->join('LEFT', '#__categories as parent ON parent.id = c.parent_id')
                    ->where('a.id = ' . (int)$pk);

                // Filter by start and end dates.
                $nullDate = $db->quote($db->getNullDate());
                $nowDate = $db->quote(JFactory::getDate()->toSql());

                // Filter by published state.
                $published = $this->getState('filter.published');
                $archived = $this->getState('filter.archived');

                if (is_numeric($published)) {
                    $query->where('(a.state = ' . (int)$published . ' OR a.state =' . (int)$archived . ')')
                        ->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')')
                        ->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');
                }

                $db->setQuery($query);
                $data = $db->loadObject();

                if (empty($data)) {
                    JError::raiseError(404, JText::_('COM_BPGALLERY_ERROR_IMAGE_NOT_FOUND'));
                }

                // Check for published state if filter set.
                if ((is_numeric($published) || is_numeric($archived)) && (($data->state != $published) && ($data->state != $archived))) {
                    JError::raiseError(404, JText::_('COM_BPGALLERY_ERROR_IMAGE_NOT_FOUND'));
                }

                /**
                 * In case some entity params have been set to "use global", those are
                 * represented as an empty string and must be "overridden" by merging
                 * the component and / or menu params here.
                 */
                $registry = new Registry($data->params);

                $data->params = clone $this->getState('params');
                $data->params->merge($registry);

                $registry = new Registry($data->metadata);
                $data->metadata = $registry;
                $data->metakey = $data->metadata->get('metakey');
                $data->metadesc = $data->metadata->get('metadesc');
                $data->author = $data->metadata->get('author');

                // Some contexts may not use tags data at all, so we allow callers to disable loading tag data
                if ($this->getState('load_tags', true)) {
                    $data->tags = new JHelperTags;
                    $data->tags->getItemTags('com_bpgallery.image', $data->id);
                }

                // Compute access permissions.
                if (($access = $this->getState('filter.access'))) {
                    // If the access filter has been set, we already know this user can view.
                    $data->params->set('access-view', true);
                } else {
                    // If no access filter is set, the layout takes some responsibility for display of limited information.
                    $user = JFactory::getUser();
                    $groups = $user->getAuthorisedViewLevels();

                    if ($data->catid == 0 || $data->category_access === null) {
                        $data->params->set('access-view', in_array($data->access, $groups));
                    } else {
                        $data->params->set('access-view', in_array($data->access, $groups) && in_array($data->category_access, $groups));
                    }
                }

                $this->_item[$pk] = $data;
            } catch (Exception $e) {
                $this->setError($e);
                $this->_item[$pk] = false;
            }
        }

        return $this->_item[$pk];
    }

    /**
     * Increment the hit counter for the image.
     *
     * @param integer $pk Optional primary key of the image to increment.
     *
     * @return  boolean  True if successful; false otherwise and internal error set.
     *
     * @throws Exception
     * @since   3.0
     *
     */
    public function hit($pk = 0)
    {
        $input = JFactory::getApplication()->input;
        $hitcount = $input->getInt('hitcount', 1);

        if ($hitcount) {
            $pk = (!empty($pk)) ? $pk : (int)$this->getState('image.id');

            $table = JTable::getInstance('Image', 'BPGalleryTable');
            $table->load($pk);
            $table->hit($pk);
        }

        return true;
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @return  void
     *
     * @throws Exception
     *
     * @since   1.6
     */
    protected function populateState()
    {
        $app = JFactory::getApplication();
        $params = JComponentHelper::getParams('com_bpgallery');

        // Prepare parameters
        $menuParams = new Registry;
        if ($menu = $app->getMenu()->getActive()) {
            $menuParams->loadString($menu->params);
        }

        $mergedParams = clone $params;
        $mergedParams->merge($menuParams);

        $this->setState('image.id', $app->input->getInt('id'));
        $this->setState('params', $mergedParams);

        $user = JFactory::getUser();

        if ((!$user->authorise('core.edit.state', 'com_bpgallery')) && (!$user->authorise('core.edit', 'com_bpgallery'))) {
            $this->setState('filter.published', 1);
            $this->setState('filter.archived', 2);
        }
    }
}
