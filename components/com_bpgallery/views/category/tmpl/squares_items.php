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
$square_row_length = $this->params->def('category_square_row_length', 4);
$image_width = round(floor(100 / $square_row_length), 2);
$this->document->addStyleDeclaration("
    @media screen and (max-width: 360px) {
        .bpgallery-category-square .items .image-link {
            width: 100%
        }
    }
    @media screen and (min-width:361px) and (max-width: 800px) {
        .bpgallery-category-square .items .image-link {
            width: 50%
        }
    }
    @media screen and (min-width:361px) and (min-width: 801px) {
        .bpgallery-category-square .items .image-link {
            width: {$image_width}%
        }
    }
");

list($thumbnail_width, $thumbnail_height, $thumbnail_method) = BPGalleryHelperLayout::getThumbnailSettingsFromParams($this->params, 'thumbnails_size_category_squares');
?>
<?php if (empty($this->items)) : ?>
    <p> <?php echo JText::_('COM_BPGALLERY_NO_IMAGES'); ?>     </p>
<?php else : ?>

    <ul class="items">
        <?php foreach ($this->items as $i => $item) :
            $url_thumbnail = BPGalleryHelper::getThumbnail($item, $thumbnail_width, $thumbnail_height, $thumbnail_method);
            $url_full = BPGalleryHelper::getThumbnail($item, 1920, 1080, BPGalleryHelper::METHOD_FIT);
            $url = Route::_(BPGalleryHelperRoute::getImageRoute($item->slug, $item->catid, $item->language));
            $alt = empty($item->alt) ? $item->title : $item->alt;
            ?>
            <a href="<?php echo $image_lightbox ? $url_full : $url ?>"
               <?php if ($image_lightbox): ?>target="_blank"<?php endif ?> class="image-link"
               title="<?php echo $item->title ?>">
                <span class="inner">
                    <span class="overlay"></span>
                    <img src="<?php echo $url_thumbnail ?>" alt="<?php echo $alt ?>" class="image">
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
