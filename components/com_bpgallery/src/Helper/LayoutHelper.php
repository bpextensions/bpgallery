<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 * @author      ${author.name}
 */

namespace BPExtensions\Component\BPGallery\Site\Helper;

defined('_JEXEC') or die;

use BPExtensions\Component\BPGallery\Administrator\Helper\BPGalleryHelper;
use BPExtensions\Component\BPGallery\Administrator\Trait\AssetsTrait;
use Joomla\Registry\Registry;
use JsonException;

abstract class LayoutHelper
{

    use AssetsTrait;

    /**
     * Root url for assets directory relative to website root URL.
     *
     * @var string
     */
    protected static string $assets_root = 'components/com_bpgallery/assets';

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
     * @throws JsonException
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
                $groups[$item->catlft] = (object)json_decode(json_encode($category), false, 512, JSON_THROW_ON_ERROR);
            }

            // Add item to the category group
            $groups[$item->catlft]->items[] = $item;
        }

        return $groups;
    }
}