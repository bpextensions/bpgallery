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

$image_lightbox = $this->params->def('images_lightbox', 1);
$category_masonry_columns = $this->params->def('category_masonry_columns', 4);
$image_width = round(floor(100 / $category_masonry_columns), 2);
$this->document->addStyleDeclaration("
    @media screen and (max-width: 360px) {
        .bpgallery-category-masonry .items .image-link {
            width: 100%
        }
    }
    @media screen and (min-width:361px) and (max-width: 800px) {
        .bpgallery-category-masonry .items .image-link {
            width: 50%
        }
    }
    @media screen and (min-width:361px) and (min-width: 801px) {
        .bpgallery-category-masonry .items .image-link {
            width: {$image_width}%
        }
    }
");

$gap = (bool)$this->params->get('category_masonry_gap', 1);

?>
<?php if (empty($this->items)) : ?>
    <p><?php echo JText::_('COM_BPGALLERY_NO_IMAGES'); ?></p>
<?php else : ?>

    <ul class="items<?php echo($gap ? '' : ' nogap') ?>">
        <?php foreach ($this->items as $i => $item) :
            $url_thumbnail = BPGalleryHelper::getThumbnail($item, 320, 200, BPGalleryHelper::METHOD_FIT_WIDTH);
            $url_medium = BPGalleryHelper::getThumbnail($item, 600, 600, BPGalleryHelper::METHOD_FIT_WIDTH);
            $url_full = BPGalleryHelper::getThumbnail($item, 1920, 1080, BPGalleryHelper::METHOD_FIT_WIDTH);
            $url = Route::_(BPGalleryHelperRoute::getImageRoute($item->slug, $item->catid, $item->language));
            $alt = empty($item->alt) ? $item->title : $item->alt;
            ?>
            <a href="<?php echo $image_lightbox ? $url_full : $url ?>"
               <?php if ($image_lightbox): ?>target="_blank"<?php endif ?> class="image-link"
               title="<?php echo $item->title ?>">
                <span class="inner">
                    <span class="overlay"></span>
                    <img
                        src="<?php echo $url_thumbnail ?>" alt="<?php echo $alt ?>" class="image"
                        srcset="<?php echo $url_thumbnail ?> 200w, <?php echo $url_medium ?> 600w, <?php echo $url_full ?> 1920w"
                        sizes="(max-width: 800px) 600px, (min-width:801px) 200px, 1920px"
                    >
                </span>
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
