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
<?php if (!empty($displayData['item']->associations)) : ?>
    <?php $associations = $displayData['item']->associations; ?>

    <dd class="association">
        <?php echo JText::_('JASSOCIATIONS'); ?>
        <?php foreach ($associations as $association) : ?>
            <?php if ($displayData['item']->params->get('flags', 1) && $association['language']->image) : ?>
                <?php $flag = JHtml::_('image', 'mod_languages/' . $association['language']->image . '.gif', $association['language']->title_native, array('title' => $association['language']->title_native), true); ?>
                &nbsp;<a href="<?php echo JRoute::_($association['item']); ?>"><?php echo $flag; ?></a>&nbsp;
            <?php else : ?>
                <?php $class = 'label label-association label-' . $association['language']->sef; ?>
                &nbsp;<a class="<?php echo $class; ?>"
                         href="<?php echo JRoute::_($association['item']); ?>"><?php echo strtoupper($association['language']->sef); ?></a>&nbsp;
            <?php endif; ?>
        <?php endforeach; ?>
    </dd>
<?php endif; ?>
