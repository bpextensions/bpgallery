<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_bpgallery
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$app = JFactory::getApplication();

if ($app->isClient('site')) {
    JSession::checkToken('get') or die(JText::_('JINVALID_TOKEN'));
}

JLoader::register('BPGalleryHelperRoute', JPATH_ADMINISTRATOR . '/components/com_bpgallery/helpers/route.php');

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('behavior.core');
JHtml::_('bootstrap.tooltip', '.hasTooltip', array('placement' => 'bottom'));
JHtml::_('bootstrap.popover', '.hasPopover', array('placement' => 'right'));
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.polyfill', array('event'), 'lt IE 9');
JHtml::_('script', 'com_bpgallery/modal_image.js', array('version' => 'auto', 'relative' => true));

// Special case for the search field tooltip.
$searchFilterDesc = $this->filterForm->getFieldAttribute('search', 'description', null, 'filter');
JHtml::_('bootstrap.tooltip', '#filter_search', array('title' => JText::_($searchFilterDesc), 'placement' => 'bottom'));

$function = $app->input->getCmd('function', 'jSelectBPGalleryImage');
$editor = $app->input->getCmd('editor', '');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
$onclick = $this->escape($function);

if (!empty($editor)) {
    // This view is used also in com_menus. Load the xtd script only if the editor is set!
//	JFactory::getDocument()->addScriptOptions('xtd-bpgalleryimage', array('editor' => $editor));
    $onclick = "jSelectBPGalleryImage";
}
$this->document->addStyleSheetVersion('/administrator/components/com_bpgallery/assets/component.css', ['version' => 'auto']);
?>
<div class="container-popup">

    <form
        action="<?php echo JRoute::_('index.php?option=com_bpgallery&view=images&layout=modal&tmpl=component&editor=' . $editor . '&function=' . $function . '&' . JSession::getFormToken() . '=1'); ?>"
        method="post" name="adminForm" id="adminForm" class="form-inline">

        <?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

        <?php if (empty($this->items)) : ?>
            <div class="alert alert-no-items">
                <?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
            </div>
        <?php else : ?>
            <table class="table table-striped table-condensed">
                <thead>
                <tr>
                    <th width="1%" class="nowrap center">
                        <?php echo JHtml::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
                    </th>
                    <th class="nowrap hidden-phone">
                        <?php echo JHtml::_('searchtools.sort', 'COM_BPGALLERY_HEADING_TITLE', 'a.title', $listDirn, $listOrder); ?>
                    </th>
                    <th width="10%" class="nowrap hidden-phone">
                        <?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'access_level', $listDirn, $listOrder); ?>
                    </th>
                    <th width="10%" class="nowrap hidden-phone">
                        <?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'a.language', $listDirn, $listOrder); ?>
                    </th>
                    <th width="1%" class="nowrap hidden-phone">
                        <?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                    </th>
                </tr>
                </thead>
                <tfoot>
                <tr>
                    <td colspan="6">
                        <?php echo $this->pagination->getListFooter(); ?>
                    </td>
                </tr>
                </tfoot>
                <tbody>
                <?php
                $iconStates = array(
                    -2 => 'icon-trash',
                    0 => 'icon-unpublish',
                    1 => 'icon-publish',
                    2 => 'icon-archive',
                );
                ?>
                <?php foreach ($this->items as $i => $item) :
                    $ordering = ($listOrder == 'ordering');
                    $item->cat_link = JRoute::_('index.php?option=com_categories&extension=com_bpgallery&task=edit&type=other&cid[]=' . $item->catid);
                    $item->item_link = JRoute::_('index.php?option=com_bpgallery&task=image.edit&id=' . (int)$item->id);
                    $item->thumbnail = BPGalleryHelper::getThumbnail($item, 64, 64, BPGalleryHelper::METHOD_CROP);
                    $item->thumbnail_preview = BPGalleryHelper::getThumbnail($item, 320, 320, BPGalleryHelper::METHOD_FIT);
                    ?>
                    <?php if ($item->language && JLanguageMultilang::isEnabled()) {
                    $tag = strlen($item->language);
                    if ($tag == 5) {
                        $lang = substr($item->language, 0, 2);
                    } elseif ($tag == 6) {
                        $lang = substr($item->language, 0, 3);
                    } else {
                        $lang = '';
                    }
                } elseif (!JLanguageMultilang::isEnabled()) {
                    $lang = '';
                }
                    ?>
                    <tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->catid; ?>">
                        <td class="center" style="vertical-align: middle">
                            <span class="<?php echo $iconStates[$this->escape($item->state)]; ?>"
                                  aria-hidden="true"></span>
                        </td>
                        <td class="nowrap has-context" style="vertical-align: middle">
                            <div class="pull-left">
                                <a class="select-link thumbnail hasPopover" href="javascript:void(0)"
                                   data-function="<?php echo $this->escape($onclick); ?>"
                                   data-id="<?php echo $item->id; ?>"
                                   data-title="<?php echo $this->escape($item->title); ?>"
                                   data-uri="<?php echo $this->escape(BPGalleryHelperRoute::getImageRoute($item->id, $item->language)); ?>"
                                   data-language="<?php echo $this->escape($lang); ?>" data-placement="right"
                                   data-content="<img src='<?php echo $item->thumbnail_preview ?>' />"
                                   data-original-title="<?php echo $this->escape($item->title) ?>">
                                    <img src="<?php echo $item->thumbnail ?>"
                                         alt="<?php echo $this->escape($item->title) ?>"/>
                                </a>
                            </div>
                            <div class="pull-left">
                                <a class="select-link" href="javascript:void(0)"
                                   data-function="<?php echo $this->escape($onclick); ?>"
                                   data-id="<?php echo $item->id; ?>"
                                   data-title="<?php echo $this->escape($item->title); ?>"
                                   data-uri="<?php echo $this->escape(BPGalleryHelperRoute::getImageRoute($item->id, $item->language)); ?>"
                                   data-language="<?php echo $this->escape($lang); ?>">
                                    <?php echo $this->escape($item->title); ?>
                                </a>
                                <span class="small">
                                    <?php echo JText::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
                                </span>
                                <div class="small">
                                    <?php echo JText::_('JCATEGORY') . ': ' . $this->escape($item->category_title); ?>
                                </div>
                            </div>
                        </td>
                        <td class="small hidden-phone" style="vertical-align: middle">
                            <?php echo $item->access_level; ?>
                        </td>
                        <td class="small nowrap hidden-phone" style="vertical-align: middle">
                            <?php if ($item->language == '*'): ?>
                                <?php echo JText::alt('JALL', 'language'); ?>
                            <?php else: ?>
                                <?php echo $item->language_title ? JHtml::_('image', 'mod_languages/' . $item->language_image . '.gif', $item->language_title, array('title' => $item->language_title), true) . '&nbsp;' . $this->escape($item->language_title) : JText::_('JUNDEFINED'); ?>
                            <?php endif; ?>
                        </td>
                        <td class="hidden-phone" style="vertical-align: middle">
                            <?php echo $item->id; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="forcedLanguage"
               value="<?php echo $app->input->get('forcedLanguage', '', 'CMD'); ?>"/>
        <?php echo JHtml::_('form.token'); ?>

    </form>
</div>
