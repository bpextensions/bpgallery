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

JHtml::_('behavior.core');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$image_lightbox           = $this->params->def('images_lightbox', 1);
$category_masonry_columns = $this->params->def('category_masonry_columns', 4);

$gap = (bool)$this->params->get('category_masonry_gap', 1);
list($thumbnail_width, $thumbnail_height, $thumbnail_method) = BPGalleryHelperLayout::getThumbnailSettingsFromParams($this->params,
    'thumbnails_size_category_masonry');

$layoutOptions = [
    'items'                    => $this->items,
    'params'                   => $this->params,
    'thumbnail_width'          => $thumbnail_width,
    'thumbnail_height'         => $thumbnail_height,
    'thumbnail_method'         => $thumbnail_method,
    'gap'                      => $gap,
    'image_lightbox'           => $this->params->get('images_lightbox', 1),
    'layoutThumbnail'          => $this->layoutThumbnail,
    'category'                 => $this->get('category'),
    'category_masonry_columns' => $category_masonry_columns,
];

?>
<?php if (empty($this->items)) : ?>
    <p><?php echo JText::_('COM_BPGALLERY_NO_IMAGES'); ?></p>
<?php else : ?>

    <?php // Render category items using default category layout ?>
    <?php echo $this->layoutCategory->render($layoutOptions) ?>

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
