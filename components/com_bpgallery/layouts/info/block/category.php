<?php

/**
 * @author        ${author.name} (${author.email})
 * @website        ${author.url}
 * @copyright    ${copyrights}
 * @license        ${license.url} ${license.name}
 * @package        ${package}
 * @subpackage        ${subpackage}
 */

defined('JPATH_BASE') or die;

?>
<dd class="category-name">
    <?php $title = $this->escape($displayData['item']->category_title); ?>
    <?php if ($displayData['params']->get('link_category') && $displayData['item']->catslug) : ?>
        <?php $url = '<a href="' . JRoute::_(BPGalleryHelperRoute::getCategoryRoute($displayData['item']->catslug, $displayData['item']->language)) . '" itemprop="genre">' . $title . '</a>'; ?>
        <?php echo JText::sprintf('COM_BPGALLERY_CATEGORY', $url); ?>
    <?php else : ?>
        <?php echo JText::sprintf('COM_BPGALLERY_CATEGORY', '<span itemprop="genre">' . $title . '</span>'); ?>
    <?php endif; ?>
</dd>
