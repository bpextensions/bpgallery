<?php
/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 * @author      ${author.name}
 */

defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip', '.hasTooltip', array('placement' => 'bottom'));

// @deprecated 4.0 the function parameter, the inline js and the buttons are not needed since 3.7.0.
$function = JFactory::getApplication()->input->getCmd('function', 'jEditBPGalleryImage_' . (int)$this->item->id);

// Function to update input title when changed
JFactory::getDocument()->addScriptDeclaration('
	function jEditBPGalleryImageModal() {
		if (window.parent && document.formvalidator.isValid(document.getElementById("image-form"))) {
			return window.parent.' . $this->escape($function) . '(document.getElementById("jform_title").value);
		}
	}
');
?>
<button id="applyBtn" type="button" class="hidden"
        onclick="Joomla.submitbutton('image.apply'); jEditBPGalleryImageModal();"></button>
<button id="saveBtn" type="button" class="hidden"
        onclick="Joomla.submitbutton('image.save'); jEditBPGalleryImageModal();"></button>
<button id="closeBtn" type="button" class="hidden" onclick="Joomla.submitbutton('image.cancel');"></button>

<div class="container-popup">
    <?php $this->setLayout('edit'); ?>
    <?php echo $this->loadTemplate(); ?>
</div>
