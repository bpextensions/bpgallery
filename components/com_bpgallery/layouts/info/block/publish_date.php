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
<dd class="published">
    <span class="icon-calendar" aria-hidden="true"></span>
    <time datetime="<?php echo JHtml::_('date', $displayData['item']->publish_up, 'c'); ?>" itemprop="datePublished">
        <?php echo JText::sprintf('COM_BPGALLERY_PUBLISHED_DATE_ON', JHtml::_('date', $displayData['item']->publish_up, JText::_('DATE_FORMAT_LC3'))); ?>
    </time>
</dd>