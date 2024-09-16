<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

use BPExtensions\Component\BPGallery\Administrator\View\Images\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Component\Categories\Administrator\Field\Modal;
use Joomla\Database\DatabaseDriver;

defined('_JEXEC') or die;

Text::script('COM_BPGALLERY_IMAGES_UPLOAD_TIP');
Text::script('COM_BPGALLERY_IMAGES_BROWSE_BUTTON');
Text::script('COM_BPGALLERY_IMAGES_BTN_ADD_LABEL');
Text::script('COM_BPGALLERY_IMAGES_UPLOAD_HEADER');
Text::script('JREMOVE');
/**
 * @var HtmlView        $this
 * @var WebAssetManager $wa
 */
$wa = $this->getDocument()->getWebAssetManager();
$options = [
    'url' => Route::_('index.php?option=com_bpgallery&task=image.upload&format=json'),
];
try {
    $options = json_encode($options, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    $options = [];
}
$wa->
useScript('core')->
useScript('joomla.dialog')->
useScript('joomla.dialog-autocreate')->
usePreset('com_bpgallery.uploader')
//    useScript('com_bpgallery.uploader')->
//    useStyle('com_bpgallery.uploader')
;

$title = Text::_('COM_BPGALLERY_IMAGES_UPLOAD_HEADER');

?>
<template id="bpgallery_upload_form">
    <div class="bpgallery_upload_form">
        <div id="bpgallery_upload_container"
             class="d-flex align-items-center justify-content-center flex-column mx-3 mt-3">

            <i class="icon-upload fa-4x text-muted mt-4 mt-xl-5 mb-3"></i>
            <p class="text-center mb-3 px-xl-5"><?php
                echo Text::_('COM_BPGALLERY_IMAGES_UPLOAD_TIP') ?></p>
            <p class="mb-4 mb-xl-5">
                <button class="btn btn-success btn-sm" id="bpgallery_upload_field_button">
                    <i class="icon-search"></i>
                    <?php
                    echo Text::_('COM_BPGALLERY_IMAGES_BROWSE_BUTTON') ?>
                </button>
            </p>
        </div>
        <?php
        $field = new Modal\CategoryField();
        $field->setDatabase(Factory::getContainer()->get(DatabaseDriver::class));
        $xml   = new SimpleXMLElement('<field name="category" type="modal_category" extension="com_bpgallery" new="true" select="true" />');
        $field->setup($xml, '');
        $field->setValue($this->state->get('filter.category_id', ''));
        ?>
        <input name="bpgallery_upload_field_input" type="file" id="bpgallery_upload_field_input" multiple
               class="visually-hidden" accept="image/png,image/jpeg"/>
        <div class="btn-toolbar d-flex justify-content-between w-100 p-3">
            <div class="category-selection">
                <?php
                echo $field->renderField(['hiddenLabel' => true]) ?>
            </div>
            <joomla-toolbar-button class="ms-auto">
                <button class="btn btn-success process-upload" disabled><i class="icon-upload"
                                                                           aria-hidden="true"></i> <?php
                    echo Text::_('COM_BPGALLERY_IMAGES_UPLOAD_BUTTON') ?></button>
            </joomla-toolbar-button>
        </div>
    </div>
</template>