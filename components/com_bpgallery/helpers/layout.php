<?php

use Joomla\Registry\Registry;

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

}