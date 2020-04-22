<?php

use Joomla\CMS\Factory;

/**
 * Helper trait allowing to include webpack.encore generated assets using manifest.json
 *
 * Note: Requires self::$assets_root declared on a class using this trait (Example value components/com_bpgallery/assets)
 */
Trait AssetsTrait
{

    /**
     * Assets manifest cache.
     *
     * @var null|array
     */
    protected static $manifestCache;

    /**
     * Get asset url using manifest.json build by webpack in /templates/THEME_NAME/assets/build.
     *
     * @param string $url Internal URL (eg. templates/test/assets/build/theme.css)
     *
     * @return string
     *
     * @throws Exception
     *
     * @since 1.0.0
     */
    public static function getAssetUrl(string $url): string
    {
        $public_url = $url;
        $manifest = static::getManifest();
        $relativeUrl = ltrim($url, '/');
        if (array_key_exists($relativeUrl, $manifest)) {
            $public_url = $manifest[$relativeUrl];
        }

        return $public_url;
    }

    /**
     * Return manifest array.
     *
     * @return array
     *
     * @throws Exception
     *
     * @since 1.0.0
     */
    public static function getManifest(): array
    {
        if (is_null(static::$manifestCache)) {
            $manifest_path = JPATH_SITE . '/' . self::$assets_root . '/manifest.json';

            static::$manifestCache = [];
            if (file_exists($manifest_path)) {
                static::$manifestCache = json_decode(file_get_contents($manifest_path), true);
            }
        }

        return static::$manifestCache;
    }

    /**
     * Include entry point assets.
     *
     * @param string $name Name of the entry point.
     *
     * @throws Exception
     */
    public static function includeEntryPointAssets(string $name)
    {
        $manifest = static::getManifest();

        // Assets files
        $cssFilePath = self::$assets_root . '/' . $name . '.css';
        $jsFilePath = self::$assets_root . '/' . $name . '.js';

        // If css asset exists
        if (array_key_exists($cssFilePath, $manifest)) {
            Factory::getDocument()->addStyleSheet($manifest[$cssFilePath], ['version' => 'auto'], ['id' => 'entry-css-' . $name]);
        }

        // If js asset exists
        if (array_key_exists($jsFilePath, $manifest)) {
            Factory::getDocument()->addScript($manifest[$jsFilePath], ['version' => 'auto'], ['id' => 'entry-css-' . $name]);
        }
    }

}