<?php

/**
 * @author        ${author.name} (${author.email})
 * @website        ${author.url}
 * @copyright    ${copyrights}
 * @license        ${license.url} ${license.name}
 * @package        ${package}
 * @subpackage        ${subpackage}
 */

use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

JHtml::_('behavior.core');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));

$images_align = $this->params->def('images_align', 'center');
$image_lightbox = $this->params->get('images_lightbox', 1);
?>
<?php if (empty($this->items)) : ?>
    <p> <?php echo JText::_('COM_BPGALLERY_NO_IMAGES'); ?>     </p>
<?php else : ?>

    <ul class="items <?php echo 'images-align-' . $images_align ?>">
        <?php foreach ($this->items as $i => $item) :
            $url_thumbnail = BPGalleryHelper::getThumbnail($item, 0, 100, BPGalleryHelper::METHOD_FIT_HEIGHT);
            $url_medium = BPGalleryHelper::getThumbnail($item, 600, 0, BPGalleryHelper::METHOD_FIT_WIDTH);
            $url_full = BPGalleryHelper::getThumbnail($item, 1920, 1080, BPGalleryHelper::METHOD_FIT);
            $url = Route::_(BPGalleryHelperRoute::getImageRoute($item->slug, $item->catid, $item->language));
            $alt = empty($item->alt) ? $item->title : $item->alt;
            ?>
            <a href="<?php echo $image_lightbox ? $url_full : $url ?>"
               <?php if ($image_lightbox): ?>target="_blank"<?php endif ?> class="image-link"
               title="<?php echo $item->title ?>">
                <span class="overlay"></span>
                <img
                        src="<?php echo $url_thumbnail ?>" alt="<?php echo $alt ?>" class="image"
                        srcset="<?php echo $url_thumbnail ?> 100w, <?php echo $url_medium ?> 400w, <?php echo $url_full ?> 1080w"
                        sizes="100%"
                >
            </a>
        <?php endforeach; ?>
    </ul>

    <?php if ($this->params->get('show_pagination', 2)) : ?>
        <div class="pagination">
            <?php if ($this->params->def('show_pagination_results', 1)) : ?>
                <p class="counter">
                    <?php echo $this->pagination->getPagesCounter(); ?>
                </p>
            <?php endif; ?>
            <?php echo $this->pagination->getPagesLinks(); ?>
        </div>
    <?php endif; ?>

<?php endif; ?>
