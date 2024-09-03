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

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$user      = JFactory::getUser();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$canOrder  = $user->authorise('core.edit.state', 'com_bpgallery.category');
$saveOrder = $listOrder == 'a.ordering';

BPGalleryHelper::includeEntryPointAssets('component');

if ($saveOrder) {
    $saveOrderingUrl = 'index.php?option=com_bpgallery&task=images.saveOrderAjax&tmpl=component';
    JHtml::_('sortablelist.sortable', 'imageList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
?>
    <form action="<?php echo JRoute::_('index.php?option=com_bpgallery&view=images'); ?>" method="post" name="adminForm"
          id="adminForm">
        <div id="j-sidebar-container" class="span2">
            <?php echo $this->sidebar; ?>
        </div>
        <div id="j-main-container" class="span10">
            <?php
            // Search tools bar
            echo JLayoutHelper::render('joomla.searchtools.default', ['view' => $this]);
            ?>
            <?php if (empty($this->items)) : ?>
                <div class="alert alert-no-items">
                    <?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                </div>
            <?php else : ?>
                <table class="table table-striped" id="imageList">
                    <thead>
                    <tr>
                        <th width="1%" class="nowrap center hidden-phone">
                            <?php echo JHtml::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null,
                                'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
                        </th>
                        <th width="1%" class="center">
                            <?php echo JHtml::_('grid.checkall'); ?>
                        </th>
                        <th width="1%" class="nowrap center">
                            <?php echo JHtml::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
                        </th>
                        <th class="nowrap hidden-phone">
                            <?php echo JHtml::_('searchtools.sort', 'COM_BPGALLERY_HEADING_TITLE', 'a.title', $listDirn,
                                $listOrder); ?>
                        </th>
                        <th width="10%" class="nowrap hidden-phone">
                            <?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'access_level', $listDirn,
                                $listOrder); ?>
                        </th>
                        <th width="10%" class="nowrap hidden-phone">
                            <?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'a.language', $listDirn,
                                $listOrder); ?>
                        </th>
                        <th width="1%" class="nowrap hidden-phone">
                            <?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn,
                                $listOrder); ?>
                        </th>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr>
                        <td colspan="13">
                            <?php echo $this->pagination->getListFooter(); ?>
                        </td>
                    </tr>
                    </tfoot>
                    <tbody>
                    <?php foreach ($this->items as $i => $item) :
                        $ordering = ($listOrder == 'ordering');
                        $item->cat_link = JRoute::_('index.php?option=com_categories&extension=com_bpgallery&task=edit&type=other&cid[]=' . $item->catid);
                        $item->item_link = JRoute::_('index.php?option=com_bpgallery&task=image.edit&id=' . (int)$item->id);
                        $item->thumbnail = BPGalleryHelper::getThumbnail($item, 64, 64, BPGalleryHelper::METHOD_CROP);
                        $item->thumbnail_preview = BPGalleryHelper::getThumbnail($item, 320, 320,
                            BPGalleryHelper::METHOD_FIT);
                        $canCreate = $user->authorise('core.create', 'com_bpgallery.category.' . $item->catid);
                        $canEdit = $user->authorise('core.edit', 'com_bpgallery.category.' . $item->catid);
                        $canCheckin = $user->authorise('core.manage',
                                'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
                        $canChange = $user->authorise('core.edit.state',
                                'com_bpgallery.category.' . $item->catid) && $canCheckin;
                        $title = JHtmlString::truncate($item->title, 55, false);
                        $alias = JHtmlString::truncate($item->alias, 55, false);
                        ?>
                        <tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->catid; ?>">
                            <td class="order nowrap center hidden-phone">
                                <?php
                                $iconClass = '';

                                if (!$canChange) {
                                    $iconClass = ' inactive';
                                } elseif (!$saveOrder) {
                                    $iconClass = ' inactive tip-top hasTooltip" title="' . JHtml::tooltipText('JORDERINGDISABLED');
                                }
                                ?>
                                <span class="sortable-handler <?php echo $iconClass ?>">
									<span class="icon-menu"></span>
								</span>
                                <?php if ($canChange && $saveOrder) : ?>
                                    <input type="text" style="display:none" name="order[]" size="5"
                                           value="<?php echo $item->ordering; ?>" class="width-20 text-area-order "/>
                                <?php endif; ?>
                            </td>
                            <td class="center">
                                <?php echo JHtml::_('grid.id', $i, $item->id); ?>
                            </td>
                            <td class="center">
                                <div class="btn-group">
                                    <?php echo JHtml::_('jgrid.published', $item->state, $i, 'images.', $canChange,
                                        'cb', $item->publish_up, $item->publish_down); ?>
                                    <?php // Create dropdown items and render the dropdown list.
                                    if ($canChange) {
                                        JHtml::_('actionsdropdown.' . ((int)$item->state === 2 ? 'un' : '') . 'archive',
                                            'cb' . $i, 'images');
                                        JHtml::_('actionsdropdown.' . ((int)$item->state === -2 ? 'un' : '') . 'trash',
                                            'cb' . $i, 'images');
                                        echo JHtml::_('actionsdropdown.render', $this->escape($item->title));
                                    }
                                    ?>
                                </div>
                            </td>
                            <td class="nowrap has-context">
                                <div class="pull-left">
                                    <?php if ($canEdit): ?>
                                        <a href="<?php echo $item->item_link ?>" class="thumbnail hasPopover"
                                           data-placement="right"
                                           data-content="<img src='<?php echo $item->thumbnail_preview ?>' />"
                                           data-original-title="<?php echo $this->escape($title) ?>">
                                            <img src="<?php echo $item->thumbnail ?>"
                                                 alt="<?php echo $this->escape($title) ?>"/>
                                        </a>
                                    <?php else: ?>
                                        <span class="thumbnail hasPopover" data-placement="right"
                                              data-content="<img src='<?php echo $item->thumbnail_preview ?>' />"
                                              data-original-title="<?php echo $this->escape($title) ?>">
											<img src="<?php echo $item->thumbnail ?>"
                                                 alt="<?php echo $this->escape($title) ?>"/>
										</span>
                                    <?php endif ?>
                                </div>
                                <div class="pull-left">
                                    <?php if ($item->checked_out) : ?>
                                        <?php echo JHtml::_('jgrid.checkedout', $i, $item->editor,
                                            $item->checked_out_time, 'images.', $canCheckin); ?>
                                    <?php endif; ?>
                                    <?php if ($canEdit) : ?>
                                        <a href="<?php echo $item->item_link ?>">
                                            <?php echo $this->escape($title); ?></a>
                                    <?php else : ?>
                                        <?php echo $this->escape($title); ?>
                                    <?php endif; ?>
                                    <span class="small">
										<?php echo JText::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($alias)); ?>
									</span>
                                    <div class="small">
                                        <?php echo JText::_('JCATEGORY') . ': ' . $this->escape($item->category_title); ?>
                                    </div>
                                </div>
                            </td>
                            <td class="small hidden-phone">
                                <?php echo $item->access_level; ?>
                            </td>
                            <td class="small nowrap hidden-phone">
                                <?php if ($item->language == '*'): ?>
                                    <?php echo JText::alt('JALL', 'language'); ?>
                                <?php else: ?>
                                    <?php echo $item->language_title ? JHtml::_('image',
                                            'mod_languages/' . $item->language_image . '.gif', $item->language_title,
                                            ['title' => $item->language_title],
                                            true) . '&nbsp;' . $this->escape($item->language_title) : JText::_('JUNDEFINED'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="hidden-phone">
                                <?php echo $item->id; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php // Load the batch processing form. ?>
                <?php if ($user->authorise('core.create', 'com_bpgallery')
                    && $user->authorise('core.edit', 'com_bpgallery')
                    && $user->authorise('core.edit.state', 'com_bpgallery')) : ?>
                    <?php echo JHtml::_(
                        'bootstrap.renderModal',
                        'collapseModal',
                        [
                            'title'  => JText::_('COM_BPGALLERY_BATCH_OPTIONS'),
                            'footer' => $this->loadTemplate('batch_footer')
                        ],
                        $this->loadTemplate('batch_body')
                    ); ?>
                <?php endif; ?>
            <?php endif; ?>

            <input type="hidden" name="task" value=""/>
            <input type="hidden" name="boxchecked" value="0"/>
            <?php echo JHtml::_('form.token'); ?>
        </div>
    </form>
<?php echo $this->loadTemplate('upload');
