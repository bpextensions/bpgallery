<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

defined('JPATH_BASE') or die;

?>
<dl class="image-info muted">

    <dt class="image-info-term">
        <?php if ($displayData['params']->get('info_block_show_title', 1)) : ?>
            <?php echo JText::_('COM_GALLERY_IMAGE_INFO'); ?>
        <?php endif; ?>
    </dt>

    <?php if ($displayData['params']->get('show_author', 1) && !empty($displayData['item']->author)) : ?>
        <?php echo $this->sublayout('author', $displayData); ?>
    <?php endif; ?>

    <?php if ($displayData['params']->get('show_parent_category', 1) && !empty($displayData['item']->parent_slug)) : ?>
        <?php echo $this->sublayout('parent_category', $displayData); ?>
    <?php endif; ?>

    <?php if ($displayData['params']->get('show_category', 1)) : ?>
        <?php echo $this->sublayout('category', $displayData); ?>
    <?php endif; ?>

    <?php if ($displayData['params']->get('show_associations', 1)) : ?>
        <?php echo $this->sublayout('associations', $displayData); ?>
    <?php endif; ?>

    <?php if ($displayData['params']->get('show_publish_date', 1)) : ?>
        <?php echo $this->sublayout('publish_date', $displayData); ?>
    <?php endif; ?>

    <?php if ($displayData['params']->get('show_create_date', 1)) : ?>
        <?php echo $this->sublayout('create_date', $displayData); ?>
    <?php endif; ?>

    <?php if ($displayData['params']->get('show_modify_date', 1) and !empty($displayData['item']->modified)) : ?>
        <?php echo $this->sublayout('modify_date', $displayData); ?>
    <?php endif; ?>

</dl>
