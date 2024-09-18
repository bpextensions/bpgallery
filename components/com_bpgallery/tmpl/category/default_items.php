<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

use BPExtensions\Component\BPGallery\Site\Helper\LayoutHelper;
use BPExtensions\Component\BPGallery\Site\View\Category\HtmlView;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

[$thumbnail_width, $thumbnail_height, $thumbnail_method] = LayoutHelper::getThumbnailSettingsFromParams($this->params,
    'thumbnails_size_category_default');

// Group items if required
if ($this->params->get('group_images')) {
    try {
        $groups = LayoutHelper::groupItemsByCategory($this->items);
    } catch (JsonException $e) {
        $groups = [];
    }
}

$layoutOptions = [
    'items'            => $this->items,
    'params'           => $this->params,
    'thumbnail_width'  => $thumbnail_width,
    'thumbnail_height' => $thumbnail_height,
    'thumbnail_method' => $thumbnail_method,
    'images_align'     => $this->params->def('images_align', 'center'),
    'image_lightbox'   => $this->params->get('images_lightbox', 1),
    'layoutThumbnail'  => $this->layoutThumbnail,
    'category_id'      => $this->get('category')->id,
];

/**
 * @var HtmlView $this
 */

?>
<?php if (empty($this->items)) : ?>
    <p> <?php echo Text::_('COM_BPGALLERY_NO_IMAGES'); ?>     </p>
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
