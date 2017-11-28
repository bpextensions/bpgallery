<?php

/**
 * @author		${author.name} (${author.email})
 * @website		${author.url}
 * @copyright	${copyrights}
 * @license		${license.url} ${license.name}
 */

defined('_JEXEC') or die;
JHtml::_('behavior.tabstate');

if (!JFactory::getUser()->authorise('core.manage', 'com_bpgallery'))
{
	throw new JAccessExceptionNotallowed(JText::_('JERROR_ALERTNOAUTHOR'), 403);
}

JLoader::register('BPGalleryHelper', JPATH_ADMINISTRATOR . '/components/com_bpgallery/helpers/bpgallery.php');

// Execute the task.
$controller = JControllerLegacy::getInstance('BPGallery');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
