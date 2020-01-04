<?php

/**
 * @author        ${author.name} (${author.email})
 * @website        ${author.url}
 * @copyright    ${copyrights}
 * @license        ${license.url} ${license.name}
 * @package        ${package}
 * @subpackage        ${subpackage}
 */

defined('_JEXEC') or die;
if ($this->maxLevel != 0 && count($this->children[$this->category->id]) > 0) :
    ?>
    <ul class="categories-list">
        <?php foreach ($this->children[$this->category->id] as $id => $child) : ?>
            <?php
            $image = $child->getParams()->get('image');
            if ($this->params->get('show_empty_categories') || $child->numitems || count($child->getChildren())) :
                ?>
                <li class="category-item">
                    <h4>
                        <a href="<?php echo JRoute::_(BPGalleryHelperRoute::getCategoryRoute($child->id)); ?>"
                           class="category-anchor">
                            <?php if (!empty($image)): ?>
                                <span class="image-wrapper">
                        <span class="overlay"></span>
                        <img src="<?php echo $image ?>" alt="<?php echo $this->escape($child->title) ?>">
                    </span>
                            <?php endif ?>
                            <span class="category-title">
				        <?php echo $this->escape($child->title); ?>

                                <?php if ($this->params->get('show_cat_num_images') == 1) : ?>
                                    <span class="badge badge-info pull-right" aria-hidden="true"
                                          title="<?php echo JText::_('COM_BPGALLERY_CAT_NUM'); ?>"><?php echo $child->numitems; ?></span>
                                <?php endif; ?>
                    </span>
                        </a>
                    </h4>

                    <?php if ($this->params->get('show_subcat_desc') == 1) : ?>
                        <?php if ($child->description) : ?>
                            <div class="category-desc">
                                <?php echo JHtml::_('content.prepare', $child->description, '', 'com_bpgallery.category'); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (count($child->getChildren()) > 0) :
                        $this->children[$child->id] = $child->getChildren();
                        $this->category = $child;
                        $this->maxLevel--;
                        echo $this->loadTemplate('children');
                        $this->category = $child->getParent();
                        $this->maxLevel++;
                    endif; ?>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
