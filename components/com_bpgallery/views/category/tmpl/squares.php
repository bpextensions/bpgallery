<?php

/**
 * @author        ${author.name} (${author.email})
 * @website        ${author.url}
 * @copyright    ${copyrights}
 * @license        ${license.url} ${license.name}
 * @package        ${package}
 * @subpackage        ${subpackage}
 */

use Joomla\CMS\Layout\FileLayout;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * @var Registry $params
 */
$params = $this->params;

// Prepare layouts
$this->layoutThumbnail = new FileLayout('components.com_bpgallery.layouts.image.thumbnail', JPATH_ROOT);
$this->layoutCategory  = new FileLayout('components.com_bpgallery.layouts.category.squares', JPATH_ROOT);

$category = $this->get('category');
$canEdit  = $params->get('access-edit');

$dispatcher = JEventDispatcher::getInstance();

$category->text = $category->description;
$dispatcher->trigger('onContentPrepare', array('com_bpgallery.categories', &$category, &$params, 0));
$category->description = $category->text;

$results = $dispatcher->trigger('onContentAfterTitle', array('com_bpgallery.categories', &$category, &$params, 0));
$afterDisplayTitle = trim(implode("\n", $results));

$results = $dispatcher->trigger('onContentBeforeDisplay', array('com_bpgallery.categories', &$category, &$params, 0));
$beforeDisplayContent = trim(implode("\n", $results));

$results = $dispatcher->trigger('onContentAfterDisplay', array('com_bpgallery.categories', &$category, &$params, 0));
$afterDisplayContent = trim(implode("\n", $results));
?>
<section class="bpgallery-category bpgallery-category-square<?php echo $this->pageclass_sfx ?>">

    <div class="page-header">
        <?php if ($params->get('show_page_heading')) : ?>
            <h1>
                <?php echo $this->escape($params->get('page_heading')); ?>
            </h1>
        <?php endif; ?>

        <?php if ($params->get('show_category_title', 1)) : ?>
            <h2>
                <?php echo JHtml::_('content.prepare', $category->title, '', 'com_bpgallery.category.title'); ?>
            </h2>
        <?php endif; ?>
    </div>

    <?php echo $afterDisplayTitle; ?>

    <?php if ($beforeDisplayContent || $afterDisplayContent || $params->get('show_description', 1) || $params->def('show_description_image', 1)) : ?>
        <div class="category-desc">
            <?php if ($params->get('show_description_image') && $category->getParams()->get('image')) : ?>
                <img src="<?php echo $category->getParams()->get('image'); ?>"
                     alt="<?php echo htmlspecialchars($category->getParams()->get('image_alt'), ENT_COMPAT, 'UTF-8'); ?>"/>
            <?php endif; ?>
            <?php echo $beforeDisplayContent; ?>
            <?php if ($params->get('show_description') && $category->description) : ?>
                <?php echo JHtml::_('content.prepare', $category->description, '', 'com_bpgallery.category.description'); ?>
            <?php endif; ?>
            <?php echo $afterDisplayContent; ?>
            <div class="clr"></div>
        </div>
    <?php endif; ?>

    <?php echo $this->loadTemplate('items') ?>

    <?php if ($this->maxLevel != 0 && $this->get('children')) : ?>
        <div class="cat-children">
            <?php if ($params->get('show_category_heading_title_text', 1) == 1) : ?>
                <h3>
                    <?php echo JText::_('JGLOBAL_SUBCATEGORIES'); ?>
                </h3>
            <?php endif; ?>
            <?php echo $this->loadTemplate('children'); ?>
        </div>
    <?php endif; ?>

</section>
