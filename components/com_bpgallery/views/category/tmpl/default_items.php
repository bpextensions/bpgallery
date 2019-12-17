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
$listDirn = $this->escape($this->state->get('list.direction'));

$images_align = $this->params->def('images_align', 'center');

?>
<?php if (empty($this->items)) : ?>
    <p> <?php echo JText::_('COM_BPGALLERY_NO_IMAGES'); ?>     </p>
<?php else : ?>

    <form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="adminForm"
          id="adminForm">

        <ul class="items <?php echo 'images-align-' . $images_align ?>">
            <?php foreach ($this->items as $i => $item) :
                $url_thumbnail = BPGalleryHelper::getThumbnail($item, 0, 100, BPGalleryHelper::METHOD_FIT_HEIGHT);
                $url_medium = BPGalleryHelper::getThumbnail($item, 600, 0, BPGalleryHelper::METHOD_FIT_WIDTH);
                $url_full = BPGalleryHelper::getThumbnail($item, 1920, 1080, BPGalleryHelper::METHOD_FIT);
                ?>
                <a href="<?php echo $url_full ?>" target="_blank" class="image-link" title="<?php echo $item->title ?>">
                    <img
                            src="<?php echo $url_thumbnail ?>" alt="<?php echo $item->title ?>" class="image"
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
		<div>
			<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
			<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		</div>
</form>
<?php endif; ?>
