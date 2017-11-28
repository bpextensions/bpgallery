<?php

/**
 * @author		${author.name} (${author.email})
 * @website		${author.url}
 * @copyright	${copyrights}
 * @license		${license.url} ${license.name}
 */

defined('_JEXEC') or die;

JLoader::register('JFormFieldModal_Category', JPATH_ADMINISTRATOR.'/components/com_categories/models/fields/modal/category.php');

$this->document->addScriptVersion(
    '/administrator/components/com_bpgallery/assets/uploader.js',
    filemtime(JPATH_COMPONENT.'/assets/uploader.js'
));
$this->document->addScriptDeclaration('
	jQuery(document).ready(function($){
		$("#toolbar-new button").attr("onclick","jQuery(\"#bpgallery_upload_form\").modal(\"show\")");
		$(document).BPGalleryUpload({
            text_intro:"'.JText::_('COM_BPGALLERY_IMAGES_UPLOAD_TIP').'",
            text_browse:"'.JText::_('COM_BPGALLERY_IMAGES_BROWSE_BUTTON').'",
            upload_url:"'.JRoute::_('index.php?option=com_bpgallery&task=image.upload&format=json').'"
        });
	});
');
$this->document->addStyleSheetVersion('/administrator/components/com_bpgallery/assets/component.css',
    filemtime(JPATH_COMPONENT.'/assets/component.css'));
?>
<!-- Modal -->
<div id="bpgallery_upload_form" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="bpgallery_upload_form" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="uploadFormLabel"><?php echo JText::_('COM_BPGALLERY_IMAGES_UPLOAD_HEADER') ?><small></small></h3>
    </div>
    <div class="modal-body">
        <div id="bpgallery_upload_container">
            <i class="icon-upload"></i>
            <p><?php echo JText::_('COM_BPGALLERY_IMAGES_UPLOAD_TIP') ?></p>
            <p><button class="btn btn-success" id="bpgallery_upload_field_button"><i class="icon-search"></i> <?php echo JText::_('COM_BPGALLERY_IMAGES_BROWSE_BUTTON') ?></button></p>
        </div>
    </div>
    <div class="modal-footer">
        <?php
        $field = new JFormFieldModal_Category();
        $xml   = new SimpleXMLElement('<field name="category" type="modal_category" extension="com_bpgallery" new="true" select="true" />');
        $field->setup($xml, '');
        $field->setValue($this->state->get('filter.category_id',''));
        ?>
        <div class="pull-left category-selection">
            <?php echo str_ireplace('type="hidden"','type="text" class="hidden"',$field->renderField(array('hiddenLabel' => true))) ?>
        </div>
        <input name="bpgallery_upload_field_input" type="file" id="bpgallery_upload_field_input" multiple class="hidden"/>
        <div class="btn-group">
            <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo JText::_('JCANCEL') ?></button>
            <button class="btn btn-primary" disabled><i class="icon-upload"></i> <?php echo JText::_('COM_BPGALLERY_IMAGES_UPLOAD_BUTTON') ?></button>
        </div>
    </div>
</div>