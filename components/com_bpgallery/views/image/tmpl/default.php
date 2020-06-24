<?php

/**
 * @author        ${author.name} (${author.email})
 * @website        ${author.url}
 * @copyright    ${copyrights}
 * @license        ${license.url} ${license.name}
 * @package        ${package}
 * @subpackage        ${subpackage}
 */

//use Joomla\CMS\Language\Text;
//use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\FileLayout;

defined('_JEXEC') or die;

// Include assets generated by Webpack
if ($this->params->def('include_component_assets', 1)) {
    BPGalleryHelperLayout::includeEntryPointAssets('component');
}
if ($this->params->def('include_theme_assets', 1)) {
    BPGalleryHelperLayout::includeEntryPointAssets('image-default');
}
if ($this->params->def('images_lightbox', 1)) {
    $lightboxLayoutData = [
        'params'         => $this->params,
        'lightbox_query' => '.bpgallery-image-page'
    ];
    (new FileLayout('components.com_bpgallery.layouts.lightbox', JPATH_ROOT))->render($lightboxLayoutData);
}

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers');

// Create shortcuts to some parameters.
$params = $this->params;
$canEdit = $params->get('access-edit');
$user = JFactory::getUser();

// Check if associations are implemented. If they are, define the parameter.
$assocParam = (JLanguageAssociations::isEnabled() && $params->get('show_associations'));
JHtml::_('behavior.caption');

$image_lightbox = $this->params->get('images_lightbox', 1);

$url_full = BPGalleryHelper::getThumbnail($this->item, 1920, 0, BPGalleryHelper::METHOD_FIT_WIDTH);

?>
<article class="bpgallery-image-page<?php echo $this->pageclass_sfx; ?>" itemscope itemtype="https://schema.org/Thing">
    <meta itemprop="inLanguage"
          content="<?php echo ($this->item->language === '*') ? JFactory::getConfig()->get('language') : $this->item->language; ?>"/>
    <?php if ($this->params->get('show_page_heading')) : ?>
        <div class="page-header">
            <h1> <?php echo $this->escape($this->params->get('page_heading')); ?> </h1>
        </div>
    <?php endif ?>

    <?php if ($params->get('show_title')) : ?>
        <div class="page-header">
            <h2 itemprop="headline">
                <?php echo $this->escape($this->item->title); ?>
            </h2>
            <?php if ($this->item->state == 0) : ?>
                <span class="label label-warning"><?php echo JText::_('JUNPUBLISHED'); ?></span>
            <?php endif; ?>
            <?php if (strtotime($this->item->publish_up) > strtotime(JFactory::getDate())) : ?>
                <span class="label label-warning"><?php echo JText::_('JNOTPUBLISHEDYET'); ?></span>
            <?php endif; ?>
            <?php if ((strtotime($this->item->publish_down) < strtotime(JFactory::getDate())) && $this->item->publish_down != JFactory::getDbo()->getNullDate()) : ?>
                <span class="label label-warning"><?php echo JText::_('JEXPIRED'); ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php // Content is generated by content plugin event "onContentAfterTitle" ?>
    <?php echo $this->item->event->afterDisplayTitle; ?>

    <?php // Todo: for Joomla4 joomla.content.info_block.block can be changed to joomla.content.info_block ?>
    <?php echo JLayoutHelper::render('info.block',
        array('item' => $this->item, 'params' => $params, 'position' => 'above')); ?>

    <?php // Content is generated by content plugin event "onContentBeforeDisplay" ?>
    <?php echo $this->item->event->beforeDisplayContent; ?>

    <?php if ($image_lightbox): ?>
        <a href="<?php echo $url_full ?>" target="_blank" class="image-link" title="<?php echo $this->item->title ?>">
            <img src="<?php echo $url_full ?>" alt="<?php echo $this->item->title ?>" class="image"/>
        </a>
    <?php else: ?>
        <img src="<?php echo $url_full ?>" alt="<?php echo $this->item->title ?>" class="image"/>
    <?php endif ?>

    <div itemprop="descripion">
        <?php echo $this->item->text; ?>
    </div>

    <?php // Content is generated by content plugin event "onContentAfterDisplay" ?>
    <?php echo $this->item->event->afterDisplayContent; ?>
</article>
