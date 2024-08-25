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
<dd class="parent-category-name">
    <?php $title = $this->escape($displayData['item']->parent_title); ?>
    <?php if ($displayData['params']->get('link_parent_category') && !empty($displayData['item']->parent_slug)) : ?>
        <?php $url = '<a href="' . JRoute::_(BPGalleryHelperRoute::getCategoryRoute($displayData['item']->parent_slug, $displayData['item']->parent_language)) . '" itemprop="genre">' . $title . '</a>'; ?>
        <?php echo JText::sprintf('COM_BPGALLERY_PARENT', $url); ?>
    <?php else : ?>
        <?php echo JText::sprintf('COM_BPGALLERY_PARENT', '<span itemprop="genre">' . $title . '</span>'); ?>
    <?php endif; ?>
</dd>
