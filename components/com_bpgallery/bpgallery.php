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

JLoader::register('BPGalleryHelperRoute', JPATH_COMPONENT . '/helpers/route.php');
JLoader::register('BPGalleryHelperLayout', JPATH_COMPONENT . '/helpers/layout.php');

$input = JFactory::getApplication()->input;

if ($input->get('view') === 'images' && $input->get('layout') === 'modal')
{
	if (!JFactory::getUser()->authorise('core.create', 'com_bpgallery'))
	{
		JFactory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'warning');
		return;
	}

	JFactory::getLanguage()->load('com_bpgallery', JPATH_ADMINISTRATOR);
}

$controller = JControllerLegacy::getInstance('BPGallery');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
