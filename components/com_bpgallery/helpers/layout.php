<?php

JLoader::register('AssetsTrait', __DIR__ . '/trait/assetstrait.php');

abstract class BPGalleryHelperLayout
{

    use AssetsTrait;

    /**
     * Root url for assets directory relative to website root URL.
     *
     * @var string
     */
    protected static $assets_root = 'components/com_bpgallery/assets';


}