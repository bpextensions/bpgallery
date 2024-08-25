<?php

/**
 * @author        ${author.name} (${author.email})
 * @website        ${author.url}
 * @copyright    ${copyrights}
 * @license        ${license.url} ${license.name}
 * @package        ${package}
 * @subpackage        ${subpackage}
 */

namespace BPExtensions\Component\BPGallery\Site\Helper;

use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Language\Multilanguage;

defined('_JEXEC') or die;

/**
 * BPGallery Component Route Helper
 *
 * @static
 * @subpackage  com_bpgallery
 */
abstract class RouteHelper
{
    /**
     * Get the URL route for a image from a image ID, image category ID and language
     *
     * @param   integer  $id        The id of the image
     * @param   integer  $catid     The id of the image's category
     * @param   mixed    $language  The id of the language being used.
     *
     * @return  string  The link to the image
     */
    public static function getImageRoute($id, $catid, $language = 0, $layout = null): string
    {
        // Create the link
        $link = 'index.php?option=com_bpgallery&view=image&id=' . $id;

        if ($catid > 1) {
            $link .= '&catid=' . $catid;
        }

        if ($language && $language !== '*' && Multilanguage::isEnabled()) {
            $link .= '&lang=' . $language;
        }

        if ($layout) {
            $link .= '&layout=' . $layout;
        }

        return $link;
    }

    /**
     * Get the URL route for a image category from a image category ID and language
     *
     * @param   integer|CategoryNode  $catid     The id of the image's category either an integer id or an instance of JCategoryNode
     * @param   integer|string        $language  The id of the language being used.
     * @param   null|string           $layout    The layout value.
     *
     * @return  string  The link to the image
     */
    public static function getCategoryRoute($catid, $language = 0, ?string $layout = null): string
    {
        if ($catid instanceof CategoryNode) {
            $id = $catid->id;
        } else {
            $id = (int)$catid;
        }

        if ($id < 1) {
            return '';
        }

        $link = 'index.php?option=com_bpgallery&view=category&id=' . $id;

        if ($language && $language !== '*' && Multilanguage::isEnabled()) {
            $link .= '&lang=' . $language;
        }

        if ($layout) {
            $link .= '&layout=' . $layout;
        }

        return $link;
    }
}
