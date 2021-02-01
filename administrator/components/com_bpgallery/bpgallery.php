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
JHtml::_('behavior.tabstate');

if (!JFactory::getUser()->authorise('core.manage', 'com_bpgallery')) {
    throw new JAccessExceptionNotallowed(JText::_('JERROR_ALERTNOAUTHOR'), 403);
}

// Register extension classes
JLoader::register('BPGalleryHelper', JPATH_ADMINISTRATOR . '/components/com_bpgallery/helpers/bpgallery.php');
JLoader::register('BPGalleryHelperDonate', JPATH_ADMINISTRATOR . '/components/com_bpgallery/helpers/donate.php');

// Show donation message
BPGalleryHelperDonate::showMessage();

// Execute the task.
$controller = JControllerLegacy::getInstance('BPGallery');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
