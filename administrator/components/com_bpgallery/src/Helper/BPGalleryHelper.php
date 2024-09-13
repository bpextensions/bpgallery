<?php

/**
 * @author      ${author.name} (${author.email})
 * @website     ${author.url}
 * @copyright   ${copyrights}
 * @license     ${license.url} ${license.name}
 * @package     ${package}.Component
 * @subpackage  BPGallery
 */

namespace BPExtensions\Component\BPGallery\Administrator\Helper;

use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Image\Image;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use Joomla\Filesystem\File;
use Joomla\Registry\Registry;
use stdClass;

defined('_JEXEC') or die;

/**
 * BP Gallery component helper.
 */
abstract class BPGalleryHelper extends ContentHelper
{

    /**
     * Fit the image inside given dimentions.
     */
    public const METHOD_FIT = 1;

    /**
     * Fit the image inside width of given dimentions.
     */
    public const METHOD_FIT_WIDTH = 2;

    /**
     * Fit the image inside height of dimentions.
     */
    public const METHOD_FIT_HEIGHT = 3;

    /**
     * Crop the image to provided dimentions.
     */
    public const METHOD_CROP = 4;

    /**
     * Fill provided dimentions with image.
     */
    public const METHOD_FILL = 5;

    /**
     * Thumbnail generation methods used to translate component settings
     * to helper constants.
     *
     * @var array
     */
    public static array $generationMethods = [
        BPGalleryHelper::METHOD_FIT        => 'fit',
        BPGalleryHelper::METHOD_FIT_WIDTH  => 'fit_width',
        BPGalleryHelper::METHOD_FIT_HEIGHT => 'fit_height',
        BPGalleryHelper::METHOD_CROP       => 'crop',
        BPGalleryHelper::METHOD_FILL       => 'fill',
    ];

    /**
     * Default image if file is lost/missing.
     *
     * @var string
     */
    protected static string $defaultImage = '/administrator/components/com_bpgallery/assets/images/default.svg';

    /**
     * Component params.
     *
     * @var Registry|null
     */
    private static ?Registry $params = null;

    /**
     * Adds Count Items for Category Manager.
     *
     * @param   stdClass[]  &$items  The banner category objects
     *
     * @return  stdClass[]
     */
    public static function countItems(array &$items): array
    {
        /**
         * @var DatabaseDriver $db
         */
        $db = Factory::getContainer()->get(DatabaseDriver::class);

        /* TODO: Performance test */

        foreach ($items as $item) {
            $item->count_trashed     = 0;
            $item->count_archived    = 0;
            $item->count_unpublished = 0;
            $item->count_published   = 0;
            $query                   = $db->getQuery(true);
            $query->select('state, count(*) AS count')
                ->from($db->qn('#__bpgallery_images'))
                ->where('catid = ' . (int)$item->id)
                ->group('state');
            $db->setQuery($query);
            $images = $db->loadObjectList();

            foreach ($images as $image) {
                if ($image->state === 1) {
                    $item->count_published = $image->count;
                }

                if ($image->state === 0) {
                    $item->count_unpublished = $image->count;
                }

                if ($image->state === 2) {
                    $item->count_archived = $image->count;
                }

                if ($image->state === -2) {
                    $item->count_trashed = $image->count;
                }
            }
        }

        return $items;
    }

    /**
     * Check if state can be deleted
     *
     * @param   int  $id  Id of state to delete
     *
     * @return  boolean
     */
    public static function canDeleteState(int $id): bool
    {
        $db    = Factory::getContainer()->get(DatabaseDriver::class);
        $query = $db->getQuery(true);

        $query->select('id')
            ->from($db->quoteName('#__content'))
            ->where($db->quoteName('state') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query);
        $states = $db->loadResult();

        return empty($states);
    }

    /**
     * Get url/path of a thumbnail.
     *
     * @param   object|string  $image     Image object.
     * @param   int            $width     Required image width.
     * @param   int            $height    Required image height.
     * @param   int            $method    Thumbnail generation method (default: self::METHOD_CROP)
     * @param   bool           $url       Should method return URL (true) or PATH (false)
     * @param   bool           $relative  Should method return relative or absolute url/path
     *
     * @return  string
     *
     * @throws Exception
     */
    public static function getThumbnail(
        object|string $image,
        int $width,
        int $height,
        int $method = self::METHOD_CROP,
        bool $url = true,
        bool $relative = true
    ): bool|string {
        /**
         * @var string $output_relative Relative thumbnail URL.
         * @var string $output_absolute Absolute thumbnail URL.
         * @var int    $mtime           Thumbnail file modify time.
         */

        $details = static::getThumbnailDetails(...func_get_args());
        extract($details, EXTR_SKIP);


        if ($url) {
            // Prepare relative base
            $uri_base = trim(Uri::root(true), '/');
            $uri_base = empty($uri_base) ? '/' : '/' . $uri_base . '/';

            return ($relative ? $uri_base . ltrim(
                    $output_relative,
                    '/'
                ) . '?' . $mtime : Uri::root() . ltrim($output_relative, '/') . '?' . $mtime);
        }

        // App requests PATH
        return ($relative ? $output_relative : $output_absolute);
    }

    /**
     * Get thumbnail details for a provided image object (and generate thumbnail if required).
     *
     * @param   object|string  $image     Image object.
     * @param   int            $width     Required image width.
     * @param   int            $height    Required image height.
     * @param   int            $method    Thumbnail generation method (default: self::METHOD_CROP)
     * @param   bool           $url       Should method return URL (true) or PATH (false)
     * @param   bool           $relative  Should method return relative or absolute url/path
     *
     * @return  string
     *
     * @throws Exception
     */
    protected static function getThumbnailDetails(
        object|string $image,
        int $width,
        int $height,
        int $method = self::METHOD_CROP,
        bool $url = true,
        bool $relative = true
    ): array|string {
        $filename           = (is_object($image) ? basename($image->filename) : basename($image));
        $relative_base_path = rtrim(self::getParam('images_path', '/images/gallery'), '/');
        $options['quality'] = self::getParam('quality', '85');
        $absolute_base_path = JPATH_ROOT . $relative_base_path;
        $original_relative  = $relative_base_path . '/original/' . $filename;
        $original_path      = $absolute_base_path . '/original/' . $filename;

        $directory = (int)$width . 'x' . (int)$height . '-' . $method;

        $output_base     = $relative_base_path . '/thumbs/' . $directory;
        $output_relative = rtrim($output_base, '/') . '/' . $filename;
        $output_absolute = JPATH_ROOT . $output_relative;

        // If thumbnail doesn't exists, create it
        if (!file_exists($output_absolute)) {
            $app = Factory::getApplication();

            // For the administrator and application debug add error message
            $showMessage = $app->isClient('administrator') || $app->get('debug');

            // If original file was not found
            if (!file_exists($original_path) or !is_file($original_path)) {
                // For the administrator and application debug add error message
                if ($showMessage) {
                    $message = Text::sprintf(
                        'COM_BPGALLERY_ERROR_MISSING_ORIGINAL_FILE_S',
                        $original_relative
                    );
                    $app->enqueueMessage($message, 'error');
                }

                // Failed, return default message
                return self::$defaultImage;
            }

            // If output file exists, remove it
            if (file_exists($output_absolute)) {
                File::delete($output_absolute);
            }

            // Image handle
            $output_image = new Image($original_path);

            // Create a proper image:
            // Crop the image/fill the dimensions
            if (in_array($method, [self::METHOD_CROP, self::METHOD_FILL])) {
                $output_image->resize(
                    $width,
                    $height,
                    false,
                    Image::SCALE_OUTSIDE
                );
                $output_image = $output_image->crop(
                    $width,
                    $height,
                    null,
                    null,
                    true
                );

                // Fit the image inside box
            } elseif ($method === self::METHOD_FIT) {
                $output_image->resize(
                    $width,
                    $height,
                    null,
                    Image::SCALE_INSIDE
                );

                // Fit image inside box width
            } elseif ($method === self::METHOD_FIT_WIDTH) {
                $height = 1;
                $output_image->resize(
                    $width,
                    $height,
                    false,
                    Image::SCALE_OUTSIDE
                );

                // Fit image inside box height
            } elseif ($method === self::METHOD_FIT_HEIGHT) {
                $width = 1;
                $output_image->resize(
                    $width,
                    $height,
                    false,
                    Image::SCALE_OUTSIDE
                );
            }

            // Get image type
            $image_type = self::getImageType($original_path);

            // If image type is not in supported types, return terror
            if ($image_type === 0) {

                // Show error message
                if ($showMessage) {
                    $message = Text::sprintf('COM_BPGALLERY_ERROR_UNSUPPORTED_FILE_S', $original_relative);
                    $app->enqueueMessage($message, 'error');
                }

                // Failed, return default message
                return self::$defaultImage;
            }

            if ($image_type === IMAGETYPE_JPEG) {
                $options['quality'] = (int)$options['quality'];
            } elseif ($image_type === IMAGETYPE_PNG) {
                $options['quality'] = round((int)$options['quality'] / 10);
            }

            // Ensure target directory exists
            if (
                !file_exists(JPATH_ROOT . $output_base) &&
                !mkdir($concurrentDirectory = JPATH_ROOT . $output_base, 0755, true) &&
                !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }

            // If we failed to save the output image
            $result = $output_image->toFile($output_absolute, $image_type, $options);
            if (!$result) {
                // Show error message
                if ($showMessage) {
                    $message = Text::sprintf(
                        'COM_BPGALLERY_ERROR_MISSING_THUMBNAIL_FILE_S',
                        $output_relative
                    );
                    $app->enqueueMessage($message, 'error');
                }

                // Failed, return default message
                return self::$defaultImage;
            }

            // Destroy image instance
            if (is_object($output_image)) {
                unset($output_image);
            }
        }

        // App requests URL
        $mtime = filemtime($output_absolute);

        return [
            'url'             => $url,
            'relative'        => $relative,
            'output_absolute' => $output_absolute,
            'output_relative' => $output_relative,
            'mtime'           => $mtime
        ];
    }

    /**
     * Get component param.
     *
     * @param   string        $param  Name of a parameter
     * @param   mixed|string  $default
     *
     * @return  mixed
     */
    public static function getParam(string $param, mixed $default = ''): mixed
    {

        // If there are no params yet provided, get them and store in static
        if (is_null(self::$params)) {
            self::$params = ComponentHelper::getParams('com_bpgallery');
        }

        return self::$params->get($param, $default);
    }

    /**
     * Returns IMAGETYPE constant for provided image (used in thumbnails output).
     *
     * @param   string  $file_path  Original file path.
     *
     * @return int
     */
    public static function getImageType(string $file_path): int
    {
        $properties = Image::getImageFileProperties($file_path);

        return match ($properties->mime) {
            'image/jpeg' => IMAGETYPE_JPEG,
            'image/png' => IMAGETYPE_PNG,
            'image/gif' => IMAGETYPE_GIF,
            default => 0,
        };
    }

    /**
     * Get thumbnail url and srcset attributes.
     *
     * @param   object|string  $image     Image object.
     * @param   int            $width     Required image width.
     * @param   int            $height    Required image height.
     * @param   int            $method    Thumbnail generation method (default: self::METHOD_CROP)
     * @param   bool           $url       Should method return URL (true) or PATH (false)
     * @param   bool           $relative  Should method return relative or absolute url/path
     *
     * @return  array
     *
     * @throws Exception
     */
    public static function getThumbnailWithSrcSet(
        object|string $image,
        int $width,
        int $height,
        int $method = self::METHOD_CROP,
        bool $url = true,
        bool $relative = true
    ): array {
        /**
         * @var bool $output_relative Relative thumbnail URL.
         * @var bool $output_absolute Absolute thumbnail URL.
         * @var int  $mtime           Thumbnail file modify time.
         */

        $details = static::getThumbnailDetails(...func_get_args());
        extract($details, EXTR_SKIP);

        if ($url) {
            $src = $relative ? $output_relative . '?' . $mtime : Uri::base() . $output_relative . '?' . $mtime;

            // App requests PATH
        } else {
            $src = $relative ? $output_relative : $output_absolute;
        }

        return [
            'src'    => $src,
            'srcset' => $src . ' ' . Image::getImageFileProperties($output_absolute)->width . 'w'
        ];
    }

    /**
     * Returns valid contexts
     *
     * @return  array
     * @throws Exception
     */
    public static function getContexts(): array
    {
        Factory::getApplication()->getLanguage()->load('com_bpgallery', JPATH_ADMINISTRATOR);

        return [
            'com_bpgallery.image' => Text::_('COM_BPGALLERY_IMAGE'),
        ];
    }

    /**
     * Prepares a form
     *
     * @param   Form          $form  The form to change
     * @param   object|array  $data  The form data
     *
     * @return void
     * @throws Exception
     */
    public static function onPrepareForm(Form $form, object|array $data): void
    {
        if ($form->getName() !== 'com_categories.categorycom_bpgallery') {
            return;
        }
    }
}
