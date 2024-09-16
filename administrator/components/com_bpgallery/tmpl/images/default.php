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

use BPExtensions\Component\BPGallery\Administrator\Helper\BPGalleryHelper;
use BPExtensions\Component\BPGallery\Administrator\View\Images\HtmlView;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Button\PublishedButton;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\WebAsset\WebAssetManager;


/**
 * @var HtmlView        $this
 * @var CMSApplication  $app
 * @var WebAssetManager $wa
 * @var HtmlView        $this
 */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect')
    ->usePreset('component');

$app       = Factory::getApplication();
$user      = $this->getCurrentUser();
$userId    = $user->id;
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder === 'a.ordering';

if (str_contains($listOrder, 'publish_up')) {
    $orderingColumn = 'publish_up';
} elseif (str_contains($listOrder, 'publish_down')) {
    $orderingColumn = 'publish_down';
} elseif (str_contains($listOrder, 'modified')) {
    $orderingColumn = 'modified';
} else {
    $orderingColumn = 'created';
}

if ($saveOrder && !empty($this->items)) {
    $saveOrderingUrl = 'index.php?option=com_bpgallery&task=images.saveOrderAjax&tmpl=component&' . Session::getFormToken(
        ) . '=1';
    HTMLHelper::_('draggablelist.draggable');
}

$assoc = Associations::isEnabled();
?>
    <form action="<?php
    echo Route::_('index.php?option=com_bpgallery&view=images'); ?>" method="post" name="adminForm" id="adminForm">
        <div class="row">
            <div class="col-md-12">
                <div id="j-main-container" class="j-main-container">
                    <?php
                    // Search tools bar
                    echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]);
                    ?>
                    <?php
                    if (empty($this->items)) : ?>
                        <div class="alert alert-info">
                            <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php
                                echo Text::_('INFO'); ?></span>
                            <?php
                            echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                        </div>
                    <?php
                    else : ?>
                        <table class="table itemList" id="imageList">
                            <caption class="visually-hidden">
                                <?php
                                echo Text::_('COM_BPGALLERY_IMAGES_TABLE_CAPTION'); ?>,
                                <span id="orderedBy"><?php
                                    echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                                <span id="filteredBy"><?php
                                    echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                            </caption>
                            <thead>
                            <tr>
                                <td class="w-1 text-center">
                                    <?php
                                    echo HTMLHelper::_('grid.checkall'); ?>
                                </td>
                                <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                                    <?php
                                    echo HTMLHelper::_(
                                        'searchtools.sort',
                                        '',
                                        'a.ordering',
                                        $listDirn,
                                        $listOrder,
                                        null,
                                        'asc',
                                        'JGRID_HEADING_ORDERING',
                                        'icon-sort'
                                    ); ?>
                                </th>
                                <th scope="col" class="w-1 text-center">
                                    <?php
                                    echo HTMLHelper::_(
                                        'searchtools.sort',
                                        'JSTATUS',
                                        'a.state',
                                        $listDirn,
                                        $listOrder
                                    ); ?>
                                </th>
                                <th scope="col" class="w-1 text-center">
                                    <?php
                                    echo Text::_('COM_BPGALLERY_HEADING_THUMBNAIL') ?>
                                </th>
                                <th scope="col" style="min-width:100px">
                                    <?php
                                    echo HTMLHelper::_(
                                        'searchtools.sort',
                                        'JGLOBAL_TITLE',
                                        'a.title',
                                        $listDirn,
                                        $listOrder
                                    ); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-md-table-cell">
                                    <?php
                                    echo HTMLHelper::_(
                                        'searchtools.sort',
                                        'JGRID_HEADING_ACCESS',
                                        'a.access',
                                        $listDirn,
                                        $listOrder
                                    ); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-md-table-cell">
                                    <?php
                                    echo HTMLHelper::_(
                                        'searchtools.sort',
                                        'JAUTHOR',
                                        'a.created_by',
                                        $listDirn,
                                        $listOrder
                                    ); ?>
                                </th>
                                <?php
                                if ($assoc) : ?>
                                    <th scope="col" class="w-5 d-none d-md-table-cell">
                                        <?php
                                        echo HTMLHelper::_(
                                            'searchtools.sort',
                                            'COM_BPGALLERY_HEADING_ASSOCIATION',
                                            'association',
                                            $listDirn,
                                            $listOrder
                                        ); ?>
                                    </th>
                                <?php
                                endif; ?>
                                <?php
                                if (Multilanguage::isEnabled()) : ?>
                                    <th scope="col" class="w-10 d-none d-md-table-cell">
                                        <?php
                                        echo HTMLHelper::_(
                                            'searchtools.sort',
                                            'JGRID_HEADING_LANGUAGE',
                                            'language',
                                            $listDirn,
                                            $listOrder
                                        ); ?>
                                    </th>
                                <?php
                                endif; ?>
                                <th scope="col" class="w-10 d-none d-md-table-cell text-center">
                                    <?php
                                    echo HTMLHelper::_(
                                        'searchtools.sort',
                                        'COM_BPGALLERY_HEADING_DATE_' . strtoupper($orderingColumn),
                                        'a.' . $orderingColumn,
                                        $listDirn,
                                        $listOrder
                                    ); ?>
                                </th>
                                <th scope="col" class="w-3 d-none d-lg-table-cell">
                                    <?php
                                    echo HTMLHelper::_(
                                        'searchtools.sort',
                                        'JGRID_HEADING_ID',
                                        'a.id',
                                        $listDirn,
                                        $listOrder
                                    ); ?>
                                </th>
                            </tr>
                            </thead>
                            <tbody<?php
                            if ($saveOrder) :
                                ?> class="js-draggable" data-url="<?php
                            echo $saveOrderingUrl; ?>" data-direction="<?php
                            echo strtolower($listDirn); ?>" data-nested="true"<?php
                            endif; ?>>
                            <?php
                            foreach ($this->items as $i => $item) :
                                $item->max_ordering = 0;
                                $canEdit = $user->authorise('core.edit', 'com_bpgallery.image.' . $item->id);
                                $canCheckin = $user->authorise(
                                        'core.manage',
                                        'com_checkin'
                                    ) || $item->checked_out == $userId || is_null($item->checked_out);
                                $canEditOwn = $user->authorise(
                                        'core.edit.own',
                                        'com_bpgallery.image.' . $item->id
                                    ) && $item->created_by == $userId;
                                $canChange = $user->authorise(
                                        'core.edit.state',
                                        'com_bpgallery.image.' . $item->id
                                    ) && $canCheckin;
                                $canEditCat = $user->authorise('core.edit', 'com_bpgallery.category.' . $item->catid);
                                $canEditOwnCat = $user->authorise(
                                        'core.edit.own',
                                        'com_bpgallery.category.' . $item->catid
                                    ) && $item->category_uid == $userId;
                                $canEditParCat = $user->authorise(
                                    'core.edit',
                                    'com_bpgallery.category.' . $item->parent_category_id
                                );
                                $canEditOwnParCat = $user->authorise(
                                        'core.edit.own',
                                        'com_bpgallery.category.' . $item->parent_category_id
                                    ) && $item->parent_category_uid == $userId;

                                ?>
                                <tr class="row<?php
                                echo $i % 2; ?>" data-draggable-group="<?php
                                echo $item->catid; ?>">
                                    <td class="text-center">
                                        <?php
                                        echo HTMLHelper::_(
                                            'grid.id',
                                            $i,
                                            $item->id,
                                            false,
                                            'cid',
                                            'cb',
                                            $item->title
                                        ); ?>
                                    </td>
                                    <td class="text-center d-none d-md-table-cell">
                                        <?php
                                        $iconClass = '';
                                        if (!$canChange) {
                                            $iconClass = ' inactive';
                                        } elseif (!$saveOrder) {
                                            $iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
                                        }
                                        ?>
                                        <span class="sortable-handler<?php
                                        echo $iconClass ?>">
                                        <span class="icon-ellipsis-v" aria-hidden="true"></span>
                                    </span>
                                        <?php
                                        if ($canChange && $saveOrder) : ?>
                                            <input type="text" name="order[]" size="5" value="<?php
                                            echo $item->ordering; ?>" class="width-20 text-area-order hidden">
                                        <?php
                                        endif; ?>
                                    </td>
                                    <td class="image-status text-center">
                                        <?php
                                        $options = [
                                            'task_prefix'        => 'images.',
                                            'disabled'           => !$canChange,
                                            'id'                 => 'state-' . $item->id,
                                            'category_published' => $item->category_published
                                        ];

                                        echo (new PublishedButton())->render(
                                            (int)$item->state,
                                            $i,
                                            $options,
                                            $item->publish_up,
                                            $item->publish_down
                                        );
                                        ?>
                                    </td>
                                    <td>
                                        <img src="<?php
                                        echo BPGalleryHelper::getThumbnail($item, 80, 60) ?>" alt="<?php
                                        echo htmlspecialchars($item->title) ?>" class="img-fluid"/>
                                    </td>
                                    <th scope="row" class="has-context">
                                        <div class="break-word">
                                            <?php
                                            if ($item->checked_out) : ?>
                                                <?php
                                                echo HTMLHelper::_(
                                                    'jgrid.checkedout',
                                                    $i,
                                                    $item->editor,
                                                    $item->checked_out_time,
                                                    'images.',
                                                    $canCheckin
                                                ); ?>
                                            <?php
                                            endif; ?>
                                            <?php
                                            if ($canEdit || $canEditOwn) : ?>
                                                <a href="<?php
                                                echo Route::_(
                                                    'index.php?option=com_bpgallery&task=image.edit&id=' . $item->id
                                                ); ?>" title="<?php
                                                echo Text::_('JACTION_EDIT'); ?> <?php
                                                echo $this->escape($item->title); ?>">
                                                    <?php
                                                    echo $this->escape($item->title); ?></a>
                                            <?php
                                            else : ?>
                                                <span title="<?php
                                                echo Text::sprintf(
                                                    'JFIELD_ALIAS_LABEL',
                                                    $this->escape($item->alias)
                                                ); ?>"><?php
                                                    echo $this->escape($item->title); ?></span>
                                            <?php
                                            endif; ?>
                                            <div class="small break-word">
                                                <?php
                                                if (empty($item->note)) : ?>
                                                    <?php
                                                    echo Text::sprintf(
                                                        'JGLOBAL_LIST_ALIAS',
                                                        $this->escape($item->alias)
                                                    ); ?>
                                                <?php
                                                else : ?>
                                                    <?php
                                                    echo Text::sprintf(
                                                        'JGLOBAL_LIST_ALIAS_NOTE',
                                                        $this->escape($item->alias),
                                                        $this->escape($item->note)
                                                    ); ?>
                                                <?php
                                                endif; ?>
                                            </div>
                                            <div class="small">
                                                <?php
                                                $ParentCatUrl  = Route::_(
                                                    'index.php?option=com_categories&task=category.edit&id=' . $item->parent_category_id . '&extension=com_bpgallery'
                                                );
                                                $CurrentCatUrl = Route::_(
                                                    'index.php?option=com_categories&task=category.edit&id=' . $item->catid . '&extension=com_bpgallery'
                                                );
                                                $EditCatTxt    = Text::_('COM_BPGALLERY_EDIT_CATEGORY');
                                                echo Text::_('JCATEGORY') . ': ';
                                                if ($item->category_level != '1') :
                                                    if ($item->parent_category_level != '1') :
                                                        echo ' &#187; ';
                                                    endif;
                                                endif;
                                                if ($this->getLanguage()->isRtl()) {
                                                    if ($canEditCat || $canEditOwnCat) :
                                                        echo '<a href="' . $CurrentCatUrl . '" title="' . $EditCatTxt . '">';
                                                    endif;
                                                    echo $this->escape($item->category_title);
                                                    if ($canEditCat || $canEditOwnCat) :
                                                        echo '</a>';
                                                    endif;
                                                    if ($item->category_level != '1') :
                                                        echo ' &#171; ';
                                                        if ($canEditParCat || $canEditOwnParCat) :
                                                            echo '<a href="' . $ParentCatUrl . '" title="' . $EditCatTxt . '">';
                                                        endif;
                                                        echo $this->escape($item->parent_category_title);
                                                        if ($canEditParCat || $canEditOwnParCat) :
                                                            echo '</a>';
                                                        endif;
                                                    endif;
                                                } else {
                                                    if ($item->category_level != '1') :
                                                        if ($canEditParCat || $canEditOwnParCat) :
                                                            echo '<a href="' . $ParentCatUrl . '" title="' . $EditCatTxt . '">';
                                                        endif;
                                                        echo $this->escape($item->parent_category_title);
                                                        if ($canEditParCat || $canEditOwnParCat) :
                                                            echo '</a>';
                                                        endif;
                                                        echo ' &#187; ';
                                                    endif;
                                                    if ($canEditCat || $canEditOwnCat) :
                                                        echo '<a href="' . $CurrentCatUrl . '" title="' . $EditCatTxt . '">';
                                                    endif;
                                                    echo $this->escape($item->category_title);
                                                    if ($canEditCat || $canEditOwnCat) :
                                                        echo '</a>';
                                                    endif;
                                                }
                                                if ($item->category_published < '1') :
                                                    echo $item->category_published == '0' ? ' (' . Text::_(
                                                            'JUNPUBLISHED'
                                                        ) . ')' : ' (' . Text::_('JTRASHED') . ')';
                                                endif;
                                                ?>
                                            </div>
                                        </div>
                                    </th>
                                    <td class="small d-none d-md-table-cell">
                                        <?php
                                        echo $this->escape($item->access_level); ?>
                                    </td>
                                    <td class="small d-none d-md-table-cell">
                                        <?php
                                        if ((int)$item->created_by != 0) : ?>
                                            <a href="<?php
                                            echo Route::_(
                                                'index.php?option=com_users&task=user.edit&id=' . (int)$item->created_by
                                            ); ?>">
                                                <?php
                                                echo $this->escape($item->author_name); ?>
                                            </a>
                                        <?php
                                        else : ?>
                                            <?php
                                            echo Text::_('JNONE'); ?>
                                        <?php
                                        endif; ?>
                                        <?php
                                        if ($item->created_by_alias) : ?>
                                            <div class="smallsub"><?php
                                                echo Text::sprintf(
                                                    'JGLOBAL_LIST_ALIAS',
                                                    $this->escape($item->created_by_alias)
                                                ); ?></div>
                                        <?php
                                        endif; ?>
                                    </td>
                                    <?php
                                    if ($assoc) : ?>
                                        <td class="d-none d-md-table-cell">
                                            <?php
                                            if ($item->association) : ?>
                                                <?php
                                                echo HTMLHelper::_('contentadministrator.association', $item->id); ?>
                                            <?php
                                            endif; ?>
                                        </td>
                                    <?php
                                    endif; ?>
                                    <?php
                                    if (Multilanguage::isEnabled()) : ?>
                                        <td class="small d-none d-md-table-cell">
                                            <?php
                                            echo LayoutHelper::render('joomla.content.language', $item); ?>
                                        </td>
                                    <?php
                                    endif; ?>
                                    <td class="small d-none d-md-table-cell text-center">
                                        <?php
                                        $date = $item->{$orderingColumn};
                                        echo $date > 0 ? HTMLHelper::_('date', $date, Text::_('DATE_FORMAT_LC4')) : '-';
                                        ?>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <?php
                                        echo (int)$item->id; ?>
                                    </td>
                                </tr>
                            <?php
                            endforeach; ?>
                            </tbody>
                        </table>

                        <?php
                        // load the pagination. ?>
                        <?php
                        echo $this->pagination->getListFooter(); ?>

                        <?php
                        // Load the batch processing form. ?>
                        <?php
                        if (
                            $user->authorise('core.create', 'com_bpgallery')
                            && $user->authorise('core.edit', 'com_bpgallery')
                            && $user->authorise('core.edit.state', 'com_bpgallery')
                        ) : ?>
                            <template id="joomla-dialog-batch"><?php
                                echo $this->loadTemplate('batch_body'); ?></template>
                        <?php
                        endif; ?>
                    <?php
                    endif; ?>

                    <input type="hidden" name="task" value="">
                    <input type="hidden" name="boxchecked" value="0">
                    <?php
                    echo HTMLHelper::_('form.token'); ?>
                </div>
            </div>
        </div>
    </form>

<?php echo $this->loadTemplate('upload');
