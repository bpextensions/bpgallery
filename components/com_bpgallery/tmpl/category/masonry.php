<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

use BPExtensions\Component\BPGallery\Site\View\Category\HtmlView;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\FileLayout;
use Joomla\Language\Text;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * @var Registry $params
 * @var HtmlView $this
 */
$params = $this->params;

// Prepare layouts
$this->layoutThumbnail = new FileLayout('bpgallery.image.thumbnail', JPATH_ROOT);
$this->layoutCategory  = new FileLayout('bpgallery.category.masonry', JPATH_ROOT);

$category = $this->get('category');
$canEdit  = $params->get('access-edit');

$afterDisplayTitle    = implode(',', $category->event->afterDisplayTitle);
$beforeDisplayContent = implode(',', $category->event->beforeDisplayContent);
$afterDisplayContent  = implode(',', $category->event->afterDisplayContent);
?>
<section class="bpgallery-category bpgallery-category-masonry<?php echo $this->pageclass_sfx ?>">

    <div class="page-header">
        <?php if ($params->get('show_page_heading')) : ?>
            <h1>
                <?php echo $this->escape($params->get('page_heading')); ?>
            </h1>
        <?php endif; ?>

        <?php
        if (!$params->get('show_page_heading') && $params->get('show_category_title', 1)) : ?>
            <h1>
                <?php
                echo HTMLHelper::_('content.prepare', $category->title, '', 'com_bpgallery.category.title'); ?>
            </h1>
        <?php
        elseif ($params->get('show_category_title', 1)): ?>
            <h2>
                <?php echo HTMLHelper::_('content.prepare', $category->title, '', 'com_bpgallery.category.title'); ?>
            </h2>
        <?php endif; ?>
    </div>

    <?php echo $afterDisplayTitle; ?>

    <?php if ($beforeDisplayContent || $afterDisplayContent || $params->get('show_description',
            1) || $params->def('show_description_image', 1)) : ?>
        <div class="category-desc">
            <?php if ($params->get('show_description_image') && $category->getParams()->get('image')) : ?>
                <img src="<?php echo $category->getParams()->get('image'); ?>"
                     alt="<?php echo htmlspecialchars($category->getParams()->get('image_alt'), ENT_COMPAT,
                         'UTF-8'); ?>"/>
            <?php endif; ?>
            <?php echo $beforeDisplayContent; ?>
            <?php if ($params->get('show_description') && $category->description) : ?>
                <?php echo HTMLHelper::_('content.prepare', $category->description, '',
                    'com_bpgallery.category.description'); ?>
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
                    <?php echo Text::_('JGLOBAL_SUBCATEGORIES'); ?>
                </h3>
            <?php endif; ?>
            <?php echo $this->loadTemplate('children'); ?>
        </div>
    <?php endif; ?>

</section>
