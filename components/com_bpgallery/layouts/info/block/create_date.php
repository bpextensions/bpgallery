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
<dd class="create">
    <span class="icon-calendar" aria-hidden="true"></span>
    <time datetime="<?php echo JHtml::_('date', $displayData['item']->created, 'c'); ?>" itemprop="dateCreated">
        <?php echo JText::sprintf('COM_BPGALLERY_CREATED_DATE_ON', JHtml::_('date', $displayData['item']->created, JText::_('DATE_FORMAT_LC3'))); ?>
    </time>
</dd>
