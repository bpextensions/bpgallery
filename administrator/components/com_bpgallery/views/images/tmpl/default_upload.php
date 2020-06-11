<?php

/**
 * @author        ${author.name} (${author.email})
 * @website        ${author.url}
 * @copyright    ${copyrights}
 * @license        ${license.url} ${license.name}
 * @package        ${package}
 * @subpackage        ${subpackage}
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

$doc = Factory::getDocument();

JLoader::register('JFormFieldModal_Category',
    JPATH_ADMINISTRATOR . '/components/com_categories/models/fields/modal/category.php');

Text::script('COM_BPGALLERY_IMAGES_UPLOAD_TIP');
Text::script('COM_BPGALLERY_IMAGES_BROWSE_BUTTON');
Text::script('COM_BPGALLERY_IMAGES_BTN_ADD_LABEL');

BPGalleryHelper::includeEntryPointAssets('uploader');
$options = [
    'url' => Route::_('index.php?option=com_bpgallery&task=image.upload&format=json'),
];
$options = json_encode($options);
$doc->addScriptDeclaration("
	jQuery(document).ready(function($){
	
	    // Show upload window
	    var showUploadWindow = function(e){
	        e.stopPropagation();
	        $('#bpgallery_upload_form').modal('show');
	    };
	
	    // Bind New button click event
		$('#toolbar-new button').removeAttr('onclick').click(showUploadWindow);
		
		$(document).BPGalleryUpload($options);
	});
");
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
        $field->setValue($this->state->get('filter.category_id', ''));
        ?>
        <div class="pull-left category-selection">
            <?php echo str_ireplace('type="hidden"', 'type="text" class="hidden"',
                $field->renderField(array('hiddenLabel' => true))) ?>
        </div>
        <input name="bpgallery_upload_field_input" type="file" id="bpgallery_upload_field_input" multiple
               class="hidden"/>
        <div class="pull-right d-flex align-items-center">
            <div class="mr-2" id="bpgallery_upload_missing_params_warning">
                <p class="m-0"><i class="icon-warning mr-2"
                                  aria-hidden="true"></i> <?php echo Text::_('COM_BPGALLERY_UPLOAD_MISSING_PARAMS_WARNING') ?>
                </p>
            </div>
            <div class="btn-group">
                <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo JText::_('JCANCEL') ?></button>
                <button class="btn btn-primary" disabled><i
                            class="icon-upload"></i> <?php echo JText::_('COM_BPGALLERY_IMAGES_UPLOAD_BUTTON') ?>
                </button>
            </div>
        </div>
    </div>
</div>