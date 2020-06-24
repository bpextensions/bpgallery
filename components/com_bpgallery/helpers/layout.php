<?php
/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 * @author      ${author.name}
 */

use Joomla\Registry\Registry;

defined('_JEXEC') or die;

JLoader::register('AssetsTrait', JPATH_ADMINISTRATOR . '/components/com_bpgallery/helpers/trait/AssetsTrait.php');

abstract class BPGalleryHelperLayout
{

    use AssetsTrait;

    /**
     * Root url for assets directory relative to website root URL.
     *
     * @var string
     */
    protected static $assets_root = 'components/com_bpgallery/assets';

    /**
     * Get thumbnail settings from parameters to use on the helper.
     *
     * @param Registry $params Params to be used in extraction
     * @param string $name Name of the parameter holding thumbnail settings.
     *
     * @return array
     */
    public static function getThumbnailSettingsFromParams(Registry $params, string $name): array
    {
        return [
            $params->get($name . '.width'),
            $params->get($name . '.height'),
            array_search($params->get($name . '.method'), BPGalleryHelper::$generationMethods, true)
        ];
    }

    /**
     * Group provided items by a category.
     *
     * @param   array  $items
     *
     * @return array
     */
    public static function groupItemsByCategory(array $items): array
    {
        $groups = [];

        foreach ($items as $item) {

            // If there is no info about this category, create it
            if (!array_key_exists($item->catlft, $groups)) {
                $category              = [
                    'title' => $item->catname,
                    'id'    => (int)$item->catid,
                    'slug'  => $item->catslug,
                    'items' => []
                ];
                $groups[$item->catlft] = (object)json_decode(json_encode($category));
            }

            // Add item to the category group
            $groups[$item->catlft]->items[] = $item;
        }

        return $groups;
    }

}