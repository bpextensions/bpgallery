<?php
defined('_JEXEC') or die;

$this->document->addScript('/administrator/components/com_bpgallery/assets/uploader.js');
$this->document->addScriptDeclaration('
	jQuery(document).ready(function($){
		$("#toolbar-new button").attr("onclick","jQuery(\"#bpgallery_upload_form\").modal(\"show\")");
		$(document).BPGalleryUpload();
	});
');
$this->document->addStyleSheet('/administrator/components/com_bpgallery/assets/component.css');

?>
<!-- Modal -->
<div id="bpgallery_upload_form" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="bpgallery_upload_form" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="uploadFormLabel"><?php echo JText::_('COM_BPGALLERY_IMAGES_UPLOAD_HEADER') ?></h3>
  </div>
  <div class="modal-body">
	  <div id="bpgallery_upload_container">
		  <i class="icon-upload"></i>
		  <p>Drag & drop files on this box or <a href="#" class="btn btn-default btn-small">select them</a> from your computer.</p>
	  </div>
  </div>
  <div class="modal-footer">
	<?php
	$field = new JFormFieldCategory();
	$xml = new SimpleXMLElement('<field name="category" type="category" extension="com_bpgallery"><option value="">JOPTION_SELECT_CATEGORY</option></field>');
	$field->setup($xml, '');
	?>
	<div class="pull-left">
	  <?php echo $field->renderField(array('hiddenLabel'=>true)) ?>
	</div>
    <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo JText::_('JCANCEL') ?></button>
    <button class="btn btn-primary"><i class="icon-upload"></i> <?php echo JText::_('COM_BPGALLERY_IMAGES_UPLOAD_BUTTON') ?></button>
  </div>
</div>