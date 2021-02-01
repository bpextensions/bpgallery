<?php
/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 * @author      ${author.name}
 */

defined('_JEXEC') or die;

/**
 * BP Gallery Component Route Helper
 *
 * @since       1.0
 */
abstract class BPGalleryHelperRoute
{

    /**
     * Get the URL route for a image item from a item ID and language
     *
     * @param integer $id The id of the map item
     * @param mixed $language The id of the language being used.
     *
     * @return  string  The link to the map item edit
     *
     * @since   1.0
     */
    public static function getImageRoute($id, $language = 0): string
    {
        // Create the link
        $link = 'index.php?option=com_bpgallery&view=image&id=' . $id;

        if ($language && $language !== '*' && JLanguageMultilang::isEnabled()) {
            $link .= '&lang=' . $language;
        }

        return $link;
    }

}
