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

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.formvalidator');
JHtml::_('formbehavior.chosen', 'select');

$this->ignore_fieldsets = array('jmetadata', 'item_associations');

$app   = Factory::getApplication();
$input = $app->input;

// Create shortcut to parameters.
$params = clone $this->state->get('params');
//$params->merge(new Registry($this->item->attribs));

BPGalleryHelper::includeEntryPointAssets('component');
Factory::getDocument()->addScriptDeclaration('
	Joomla.submitbutton = function(task)
	{
		if (task == "image.cancel" || document.formvalidator.isValid(document.getElementById("image-form")))
		{
			Joomla.submitform(task, document.getElementById("image-form"));
		}
	};
');

// In case of modal
$isModal = $input->get('layout') == 'modal' ? true : false;
$layout = $isModal ? 'modal' : 'edit';
$tmpl = $isModal || $input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';

?>

<form action="<?php echo JRoute::_('index.php?option=com_bpgallery&layout=' . $layout . $tmpl . '&id=' . (int)$this->item->id); ?>"
      method="post" name="adminForm" id="image-form" class="form-validate">

    <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

    <div class="form-horizontal">
        <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>

        <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'general', empty($this->item->id) ? JText::_('COM_BPGALLERY_NEW_IMAGE') : JText::_('COM_BPGALLERY_EDIT_IMAGE')); ?>
        <div class="row-fluid">
            <div class="span9">
                <?php
                echo $this->form->renderField('filename');
                echo $this->form->renderField('alt');
                echo $this->form->renderField('intro');
                echo $this->form->renderField('description');
                echo $this->form->renderFieldset('extra');
                ?>
            </div>
            <div class="span3">
                <?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
            </div>
        </div>
        <?php echo JHtml::_('bootstrap.endTab'); ?>

        <?php $this->show_options = $params->get('show_article_options', 1); ?>
        <?php $this->ignore_fieldsets = ['details', 'jmetadata', 'item_associations'] ?>
        <?php echo JLayoutHelper::render('joomla.edit.params', $this); ?>

        <?php // Do not show the publishing options if the edit form is configured not to. ?>
        <?php //if ($params->get('show_publishing_options', 1) == 1) : ?>
        <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('JGLOBAL_FIELDSET_PUBLISHING')); ?>
        <div class="row-fluid form-horizontal-desktop">
            <div class="span6">
                <?php echo JLayoutHelper::render('joomla.edit.publishingdata', $this); ?>
            </div>
            <div class="span6">
                <?php echo JLayoutHelper::render('joomla.edit.metadata', $this); ?>
            </div>
        </div>
        <?php echo JHtml::_('bootstrap.endTab'); ?>
        <?php //endif; ?>


        <?php echo JHtml::_('bootstrap.endTabSet'); ?>
    </div>

    <input type="hidden" name="task" value=""/>
    <?php echo JHtml::_('form.token'); ?>
</form>
