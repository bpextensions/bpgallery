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
 * HTML View class for the BPGallery component
 */
class BPGalleryViewCategory extends JViewCategoryfeed
{
    /**
     * @var    string  The name of the view to link individual items to
     */
    protected $viewName = 'image';

    /**
     * Method to reconcile non standard names from components to usage in this class.
     * Typically overriden in the component feed view class.
     *
     * @param   object  $item  The item for a feed, an element of the $items array.
     *
     * @return  void
     */
    protected function reconcileNames($item)
    {
        parent::reconcileNames($item);

        $item->description = $item->address;
    }
}
