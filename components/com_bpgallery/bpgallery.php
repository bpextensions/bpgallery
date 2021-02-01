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

defined('_JEXEC') or die;

JLoader::register('BPGalleryHelperRoute', JPATH_COMPONENT . '/helpers/route.php');
JLoader::register('BPGalleryHelperLayout', JPATH_COMPONENT . '/helpers/layout.php');
$app   = Factory::getApplication();
$input = $app->input;

if ($input->get('view') === 'images' && $input->get('layout') === 'modal') {
    if (!Factory::getUser()->authorise('core.create', 'com_bpgallery')) {
        $app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'warning');

        return;
    }

    Factory::getLanguage()->load('com_bpgallery', JPATH_ADMINISTRATOR);
}

$controller = JControllerLegacy::getInstance('BPGallery');
$controller->execute($app->input->get('task'));
$controller->redirect();
