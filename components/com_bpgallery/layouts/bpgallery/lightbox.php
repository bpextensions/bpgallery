<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Registry\Registry;

defined('JPATH_BASE') or die;

/**
 * @var array    $displayData    Layout data.
 * @var Registry $params         Parameters to use on this layout.
 * @var string   $lightbox_query Lightbox container element query string.
 * @var WebAssetManager $wa
 */

extract($displayData, EXTR_SKIP);

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();

$wa->useScript('jquery');
$wa->usePreset('com_bpgallery.lightbox');

$lightbox_options = [
    'type'           => 'image',
    'image'          => (object)[],
    'gallery'        => [
        'enabled'  => true,
        'tCounter' => '<span class="mfp-counter">' . Text::_('COM_BPGALLERY_LIGHTBOX_N_OF_X') . '</span>',
        'tPrev'    => Text::_('COM_BPGALLERY_LIGHTBOX_PREV'),
        'tNext'    => Text::_('COM_BPGALLERY_LIGHTBOX_NEXT'),
    ],
    'tClose'         => Text::_('COM_BPGALLERY_LIGHTBOX_CLOSE'),
    'closeBtnInside' => true,
    'zoom'           => [
        'enabled'  => true,
        'duration' => 300,
        'easing'   => 'ease-in-out'
    ]
];
try {
    $lightbox_options = json_encode($lightbox_options, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    $lightbox_options = [];
}

// Enable or disable image title in a lightbox
$titleSrc = '"title"';
if (!$params->get('images_lightbox_title', 1)) {
    $titleSrc = "function(){return ''}";
}

// Disable lightbox below given screen width
$disableOn               = '';
$images_lightbox_min_res = $params->get('images_lightbox_min_res', 0);
if ($images_lightbox_min_res) {
    $disableOn = "lightbox_options.disableOn = function(){
                if( $(window).width() < $images_lightbox_min_res ) {
                    return false;
                }
                return true;
            }";
}

// Create lightbox instance
$wa->addInlineScript(
    "

    // Run lightbox for BP Gallery
    jQuery(function($){
        var lightbox_options = $lightbox_options;
        $disableOn
        lightbox_options.image.titleSrc = $titleSrc;
        lightbox_options.zoom.opener = function(openerElement) {
            return openerElement.is('img') ? openerElement : openerElement.find('img');
        }
        $('$lightbox_query .image-link').magnificPopup(lightbox_options);
    });
", [], [],
    ['jquery']
);
