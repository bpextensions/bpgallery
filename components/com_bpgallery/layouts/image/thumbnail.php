<?php
/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 * @author      ${author.name}
 */

use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

defined('JPATH_BASE') or die;

/**
 * @var array    $displayData      Layout data.
 * @var JObject  $item             Image object.
 * @var int      $thumbnail_width  Thumbnail width.
 * @var int      $thumbnail_height Thumbnail height.
 * @var int      $thumbnail_method Thumbnail generation method.
 * @var bool     $image_lightbox   Use lightbox for the image?
 * @var Registry $params           Parameters to use on this layout.
 */
extract($displayData, EXTR_SKIP);

$url_thumbnail = BPGalleryHelper::getThumbnail($item, $thumbnail_width, $thumbnail_height, $thumbnail_method);
$url_full      = BPGalleryHelper::getThumbnail($item, 1920, 1080, BPGalleryHelper::METHOD_FIT);
$url           = Route::_(BPGalleryHelperRoute::getImageRoute($item->slug, $item->catid, $item->language));
$alt           = empty($item->alt) ? $item->title : $item->alt;
?>
<a href="<?php echo $image_lightbox ? $url_full : $url ?>"
   <?php if ($image_lightbox) :
   ?>target="_blank"<?php
endif ?> class="image-link"
   title="<?php echo $item->title ?>">
    <span class="inner">
        <span class="overlay"></span>
        <img src="<?php echo $url_thumbnail ?>" alt="<?php echo $alt ?>" class="image">
    </span>
</a>
