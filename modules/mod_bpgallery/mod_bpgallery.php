<?php

/**
 * @author      ${author.name} (${author.email})
 * @website     ${author.url}
 * @copyright   ${copyrights}
 * @license     ${license.url} ${license.name}
 * @package     ${package}.Module
 * @subpackage  ModBPGallery
 */

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;
defined('PATH_COM_BPGALLERY') or define('PATH_COM_BPGALLERY', JPATH_SITE . '/components/com_bpgallery/');
defined('PATH_COM_BPGALLERY_ADMIN') or define('PATH_COM_BPGALLERY_ADMIN', JPATH_ADMINISTRATOR . '/components/com_bpgallery/');

// Include the helper functions only once
JLoader::register('ModBPGalleryHelper', __DIR__ . '/helper.php');
JLoader::register('BPGalleryHelperRoute', PATH_COM_BPGALLERY . 'helpers/route.php');
JLoader::register('BPGalleryModelCategory', PATH_COM_BPGALLERY . 'models/category.php');
JLoader::register('BPGalleryModelCategories', PATH_COM_BPGALLERY . 'models/categories.php');
JLoader::register('BPGalleryHelperLayout', PATH_COM_BPGALLERY . 'helpers/layout.php');
JLoader::register('BPGalleryHelper', PATH_COM_BPGALLERY_ADMIN . 'helpers/bpgallery.php');

/**
 * Merge component params and module params.
 *
 * @var Registry $params
 * @var Registry $cparams
 */
$module_params = ComponentHelper::getParams('com_bpgallery');
$module_params->merge($params);
$params = $module_params;

// Get items
$helper = new ModBPGalleryHelper($module, $params);
$list = $helper->getList();

if (!empty($list)) {
    $doc = Factory::getDocument();
    $layout = $params->get('layout', 'default');
    $moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8');
    $images_align = $params->def('images_align', 'center');
    $image_lightbox = $params->get('images_lightbox', 1);
    $square_row_length = $params->def('category_square_row_length', 4);
    $category_masonry_columns = $params->def('category_masonry_columns', 4);
    $category_masonry_gap = (bool)$params->get('category_masonry_gap', 1);
    $module_id = 'mod_bpgallery_' . $module->id;

    require ModuleHelper::getLayoutPath('mod_bpgallery', $layout);
}
