<?php

/**
 * @author		Artur Stępień (artur.stepien@bestproject.pl)
 * @website		www.bestproject.pl
 * @copyright	Copyright (C) 2017 Best Project, Inc. All rights reserved.
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
return;
defined('_JEXEC') or die;
?>
<a class="btn" type="button" onclick="document.getElementById('batch-category-id').value='';document.getElementById('batch-language-id').value=''" data-dismiss="modal">
	<?php echo JText::_('JCANCEL'); ?>
</a>
<button class="btn btn-success" type="submit" onclick="Joomla.submitbutton('image.batch');">
	<?php echo JText::_('JGLOBAL_BATCH_PROCESS'); ?>
</button>
