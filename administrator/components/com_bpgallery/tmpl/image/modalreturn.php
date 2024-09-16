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

use BPExtensions\Component\BPGallery\Site\Helper\RouteHelper;

/** @var \Joomla\Component\Content\Administrator\View\Article\HtmlView $this */

$icon    = 'icon-check';
$title   = $this->item ? $this->item->title : '';
$content = $this->item ? $this->item->alias : '';
$data    = ['contentType' => 'com_bpgallery.image'];

if ($this->item) {
    $data['id']    = $this->item->id;
    $data['title'] = $this->item->title;
    $data['catId'] = $this->item->catid;
    $data['uri']   = RouteHelper::getImageRoute($this->item->id, $this->item->catid, $this->item->language);
}

// Add Content select script
/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('modal-content-select');

// The data for Content select script
$this->getDocument()->addScriptOptions('content-select-on-load', $data, false);

?>

<div class="px-4 py-5 my-5 text-center">
    <span class="fa-8x mb-4 <?php
    echo $icon; ?>" aria-hidden="true"></span>
    <h1 class="display-5 fw-bold"><?php
        echo $title; ?></h1>
    <div class="col-lg-6 mx-auto">
        <p class="lead mb-4">
            <?php
            echo $content; ?>
        </p>
    </div>
</div>
