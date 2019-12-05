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
 * BP Gallery component helper.
 */
class BPGalleryHelper extends JHelperContent
{
    /**
     * Fit the image inside given dimentions.
     */
    const METHOD_FIT = 1;

    /**
     * Fit the image inside width of given dimentions.
     */
    const METHOD_FIT_WIDTH = 2;

    /**
     * Fit the image inside height of dimentions.
     */
    const METHOD_FIT_HEIGHT = 3;

    /**
     * Crop the image to provided dimentions.
     */
    const METHOD_CROP = 4;

    /**
     * Fill provided dimentions with image.
     */
    const METHOD_FILL = 5;

    /**
     * Component params.
     * 
     * @var Joomla\Registry\Registry
     */
    private static $params;

    /**
     * Default image if file is lost/missing.
     * 
     * @var string
     */
    protected static $defaultImage = '/administrator/components/com_bpgallery/assets/images/default.svg';

    /**
     * Configure the Linkbar.
     *
     * @param   string  $vName  The name of the active view.
     *
     * @return  void
     */
    public static function addSubmenu($vName)
    {
        JHtmlSidebar::addEntry(
            JText::_('COM_BPGALLERY_SUBMENU_IMAGES'),
            'index.php?option=com_bpgallery&view=images', $vName == 'images'
        );

        JHtmlSidebar::addEntry(
            JText::_('COM_BPGALLERY_SUBMENU_CATEGORIES'),
            'index.php?option=com_categories&extension=com_bpgallery',
            $vName == 'categories'
        );
    }

    /**
     * Adds Count Items for Category Manager.
     *
     * @param   stdClass[]  &$items  The banner category objects
     *
     * @return  stdClass[]
     *
     * @since   3.5
     */
    public static function countItems(&$items)
    {
        $db = JFactory::getDbo();

        /* TODO: Performance test */

        foreach ($items as $item) {
            $item->count_trashed     = 0;
            $item->count_archived    = 0;
            $item->count_unpublished = 0;
            $item->count_published   = 0;
            $query                   = $db->getQuery(true);
            $query->select('state, count(*) AS count')
                ->from($db->qn('#__bpgallery_images'))
                ->where('catid = '.(int) $item->id)
                ->group('state');
            $db->setQuery($query);
            $images                  = $db->loadObjectList();

            foreach ($images as $image) {
                if ($image->state == 1) {
                    $item->count_published = $image->count;
                }

                if ($image->state == 0) {
                    $item->count_unpublished = $image->count;
                }

                if ($image->state == 2) {
                    $item->count_archived = $image->count;
                }

                if ($image->state == -2) {
                    $item->count_trashed = $image->count;
                }
            }
        }

        return $items;
    }

    /**
     * Get component param.
     * 
     * @param   string  $param      Name of a parameter
     * @param   mixed   $default
     *
     * @return  mixed
     */
    public static function getParam($param, $default = '')
    {

        // If there are no params yet provided, get them and store in static
        if (is_null(self::$params)) {
            self::$params = JComponentHelper::getParams('com_bpgallery');
        }

        return self::$params->get($param, $default);
    }

    /**
     * Get url/path of a thumbnail.
     * 
     * @param   JObject $image      Image object.
     * @param   int     $width      Required image width.
     * @param   int     $height     Required image height.
     * @param   int     $method     Thumbnail generation method (default: self::METHOD_CROP)
     * @param   bool    $url        Should method return URL (true) or PATH (false)
     * @param   bool    $relative   Should method return relative or absolute url/path
     *
     * @return  string
     */
    public static function getThumbnail($image, $width, $height,
                                        $method = self::METHOD_CROP,
                                        $url = true, $relative = true)
    {
        $filename           = (is_object($image) ? basename($image->filename) : basename($image));
        $relative_base_path = self::getParam('images_path', '/images/gallery');
        $absolute_base_path = JPATH_ROOT.$relative_base_path;

        $original_relative = $relative_base_path.'/original/'.$filename;
        $original_path     = $absolute_base_path.'/original/'.$filename;

        $directory = 'thumbs_'.(int) $width.'x'.(int) $height.'-'.$method;

        $output_base     = $relative_base_path.'/'.$directory;
        $output_relative = $output_base.'/'.$filename;
        $output_absolute = JPATH_ROOT.$output_relative;

        // If thumbnail doesn't exists, create it
        if (!file_exists($output_absolute)) {

            $app = JFactory::getApplication();

            // For the administrator and application debug add error message
            $showMessage = $app->isClient('administrator') OR $app->get('debug');

            // If original file was not found
            if (!file_exists($original_path) OR !is_file($original_path)) {

                // For the administrator and application debug add error message
                if ($showMessage) {
                    $message = JText::sprintf('COM_BPGALLERY_ERROR_MISSING_ORIGINAL_FILE_S',
                            $original_relative);
                    $app->enqueueMessage($message, 'error');
                }

                // Failed, return default message
                return self::$defaultImage;
            } else {

                // If output file exists, remove it
                if (file_exists($output_absolute)) {
                    JFile::delete($output_absolute);
                }

                // Image handle
                $output_image = new JImage($original_path);

                // Create a proper image:
                // Crop the image/fill the dimensions
                if (in_array($method, [self::METHOD_CROP, self::METHOD_FILL])) {
                    $output_image->resize($width, $height, false,
                        JImage::SCALE_OUTSIDE);
                    $output_image = $output_image->crop($width, $height, null,
                        null, true);

                    // Fit the image inside box
                } elseif ($method === self::METHOD_FIT) {
                    $output_image->resize($width, $height, null,
                        JImage::SCALE_INSIDE);

                    // Fit image inside box width
                } elseif ($method === self::METHOD_FIT_WIDTH) {
                    $height = 1;
                    $output_image->resize($width, $height, false,
                        JImage::SCALE_OUTSIDE);

                    // Fit image inside box height
                } elseif ($method === self::METHOD_FIT_HEIGHT) {
                    $width = 1;
                    $output_image->resize($width, $height, false,
                        JImage::SCALE_OUTSIDE);
                }

                // Get image type
                $image_type = self::getImageType($original_path);

                // If image type is not in supported types, return terror
                if ($image_type == 0) {

                    // Show error message
                    if ($showMessage) {
                        $message = JText::sprintf('COM_BPGALLERY_ERROR_UNSUPPORTED_FILE_S',
                                $original_relative);
                        $app->enqueueMessage($message, 'error');
                    }

                    // Failed, return default message
                    return self::$defaultImage;
                }

                // Ensure target directory exists
                if (!file_exists(JPATH_ROOT.$output_base)) {
                    mkdir(JPATH_ROOT.$output_base, 0755, true);
                }

                // If we failed to save the output image
                $result = $output_image->toFile($output_absolute, $image_type);
                if (!$result) {

                    // Show error message
                    if ($showMessage) {
                        $message = JText::sprintf('COM_BPGALLERY_ERROR_MISSING_THUMBNAIL_FILE_S',
                                $output_relative);
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
        }

        // App requests URL
        $mtime = filemtime($output_absolute);
        if ($url) {
            return $relative ? $output_relative.'?'.$mtime : JURI::base().$output_relative.'?'.$mtime;

        // App requests PATH
        } else {
            return $relative ? $output_relative : $output_absolute;
        }
    }

    /**
     * Returns IMAGETYPE constant for provided image (used in thumbnails output).
     * 
     * @param   string  $file_path  Original file path.
     * 
     * @return int
     */
    public static function getImageType($file_path)
    {
        $properties = JImage::getImageFileProperties($file_path);

        switch ($properties->mime) {
            case 'image/jpeg': $type = IMAGETYPE_JPEG;
                break;
            case 'image/png': $type = IMAGETYPE_PNG;
                break;
            case 'image/gif': $type = IMAGETYPE_GIF;
                break;
            default: $type = 0;
        }

        return $type;
    }
}