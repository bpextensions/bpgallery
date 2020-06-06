<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

defined('_JEXEC') or die;

JHtml::_('behavior.core');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$image_lightbox             = $this->params->def('images_lightbox', 1);
$category_square_row_length = $this->params->def('category_square_row_length', 4);

$gap = (bool)$this->params->get('category_squares_gap', 1);
list($thumbnail_width, $thumbnail_height, $thumbnail_method) = BPGalleryHelperLayout::getThumbnailSettingsFromParams($this->params,
    'thumbnails_size_category_squares');

// Group items if required
if ($this->params->get('group_images')) {
    $groups = BPGalleryHelperLayout::groupItemsByCategory($this->items);
}

$layoutOptions = [
    'items'                      => $this->items,
    'params'                     => $this->params,
    'thumbnail_width'            => $thumbnail_width,
    'thumbnail_height'           => $thumbnail_height,
    'thumbnail_method'           => $thumbnail_method,
    'gap'                        => $gap,
    'image_lightbox'             => $this->params->get('images_lightbox', 1),
    'layoutThumbnail'            => $this->layoutThumbnail,
    'category_id'                => $this->get('category')->id,
    'category_square_row_length' => $category_square_row_length,
];

?>
<?php if (empty($this->items)) : ?>
    <p> <?php echo JText::_('COM_BPGALLERY_NO_IMAGES'); ?>     </p>
<?php else : ?>

    <?php // Render category items using default category layout ?>
    <?php if (!$this->params->get('group_images')): ?>
        <?php echo $this->layoutCategory->render($layoutOptions) ?>
    <?php else: ?>
        <?php foreach ($groups as $group):
            $groupOptions = $layoutOptions;
            $groupOptions['items'] = $group->items;
            $groupOptions['category_id'] = $group->id;
            ?>
            <h2 class="category-name">
                <?php echo $group->title ?>
            </h2>
            <?php echo $this->layoutCategory->render($groupOptions) ?>
        <?php endforeach ?>
    <?php endif; ?>

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
