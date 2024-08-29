<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

use BPExtensions\Component\BPGallery\Site\View\Image\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Registry\Registry;

/**
 * @var HtmlView        $this
 * @var WebAssetManager $wa
 **/

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.formvalidator');
JHtml::_('formbehavior.chosen', 'select');

$this->ignore_fieldsets = ['jmetadata', 'item_associations'];

$app   = Factory::getApplication();
$doc   = $app->getDocument();
$input = $app->input;
$wa    = $doc->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('com_contenthistory');
$wa->usePreset('component');
$wa->useScript('keepalive')
    ->useScript('form.validate');

// Create shortcut to parameters.
$this->ignore_fieldsets = array_merge(['jmetadata', 'item_associations']);
$this->useCoreUI        = true;

$params = clone $this->state->get('params');
$params->merge(new Registry($this->item->attribs));

$input = $app->input;

$assoc              = Associations::isEnabled();
$showArticleOptions = $params->get('show_image_options', 1);

if (!$assoc || !$showArticleOptions) {
    $this->ignore_fieldsets[] = 'frontendassociations';
}

if (!$showArticleOptions) {
    // Ignore fieldsets inside Options tab
    $this->ignore_fieldsets = array_merge($this->ignore_fieldsets,
        ['attribs', 'basic', 'category', 'author', 'date', 'other']);
}

// In case of modal
$isModal = $input->get('layout') === 'modal';
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = $isModal || $input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';

?>

<form action="<?php echo JRoute::_('index.php?option=com_bpgallery&layout=' . $layout . $tmpl . '&id=' . (int)$this->item->id); ?>"
      method="post" name="adminForm" id="item-form"
      aria-label="<?php echo Text::_('COM_BPGALLERY_FORM_TITLE_' . ((int)$this->item->id === 0 ? 'NEW' : 'EDIT'),
          true); ?>" class="form-validate">

    <?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>

    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab',
            ['active' => 'general', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'general', Text::_('COM_BPGALLERY_IMAGE_CONTENT')); ?>
        <div class="row">
            <div class="col-lg-9">
                <?php
                echo $this->form->renderField('filename');
                echo $this->form->renderField('alt');
                echo $this->form->renderField('intro');
                echo $this->form->renderField('description');
                echo $this->form->renderFieldset('extra');
                ?>
            </div>
            <div class="col-lg-3">
                <?php echo LayoutHelper::render('joomla.edit.global', $this); ?>
            </div>
        </div>

        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php $this->show_options = $params->get('show_image_options', 1); ?>
        <?php $this->ignore_fieldsets = ['details', 'jmetadata', 'item_associations'] ?>

        <?php echo JLayoutHelper::render('joomla.edit.params', $this); ?>

        <?php // Do not show the publishing options if the edit form is configured not to. ?>
        <?php if ($params->get('show_publishing_options', 1) == 1) : ?>
            <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing',
                Text::_('COM_CONTENT_FIELDSET_PUBLISHING')); ?>
            <div class="row">
                <div class="col-12 col-lg-6">
                    <fieldset id="fieldset-publishingdata" class="options-form">
                        <legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend>
                        <div>
                            <?php echo LayoutHelper::render('joomla.edit.publishingdata', $this); ?>
                        </div>
                    </fieldset>
                </div>
                <div class="col-12 col-lg-6">
                    <fieldset id="fieldset-metadata" class="options-form">
                        <legend><?php echo Text::_('JGLOBAL_FIELDSET_METADATA_OPTIONS'); ?></legend>
                        <div>
                            <?php echo LayoutHelper::render('joomla.edit.metadata', $this); ?>
                        </div>
                    </fieldset>
                </div>
            </div>
            <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php endif; ?>


        <?php if (!$isModal && $assoc && $params->get('show_associations_edit', 1) == 1) : ?>
            <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'associations',
                Text::_('JGLOBAL_FIELDSET_ASSOCIATIONS')); ?>
            <fieldset id="fieldset-associations" class="options-form">
                <legend><?php echo Text::_('JGLOBAL_FIELDSET_ASSOCIATIONS'); ?></legend>
                <div>
                    <?php echo LayoutHelper::render('joomla.edit.associations', $this); ?>
                </div>
            </fieldset>
            <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php elseif ($isModal && $assoc) : ?>
            <div class="hidden"><?php echo LayoutHelper::render('joomla.edit.associations', $this); ?></div>
        <?php endif; ?>

        <?php echo JHtml::_('bootstrap.endTabSet'); ?>

        <input type="hidden" name="task" value="">
        <input type="hidden" name="return" value="<?php echo $input->getBase64('return'); ?>">
        <input type="hidden" name="forcedLanguage" value="<?php echo $input->get('forcedLanguage', '', 'cmd'); ?>">
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>
