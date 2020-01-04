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
<dd class="createdby" itemprop="author" itemscope itemtype="https://schema.org/Person">
    <?php $author = ($displayData['item']->created_by_alias ?: $displayData['item']->author); ?>
    <?php $author = '<span itemprop="name">' . $author . '</span>'; ?>
    <?php if (!empty($displayData['item']->contact_link) && $displayData['params']->get('link_author') == true) : ?>
        <?php echo JText::sprintf('COM_BPGALLERY_WRITTEN_BY', JHtml::_('link', $displayData['item']->contact_link, $author, array('itemprop' => 'url'))); ?>
    <?php else : ?>
        <?php echo JText::sprintf('COM_BPGALLERY_WRITTEN_BY', $author); ?>
    <?php endif; ?>
</dd>
