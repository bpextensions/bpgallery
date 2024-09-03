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
?>
<a class="btn" type="button"
   onclick="document.getElementById('batch-category-id').value='';document.getElementById('batch-language-id').value=''"
   data-dismiss="modal">
    <?php echo JText::_('JCANCEL'); ?>
</a>
<button class="btn btn-success" type="submit" onclick="Joomla.submitbutton('image.batch');">
    <?php echo JText::_('JGLOBAL_BATCH_PROCESS'); ?>
</button>
