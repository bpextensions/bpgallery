<?php
/**
 * @author		Artur Stępień (artur.stepien@bestproject.pl)
 * @website		www.bestproject.pl
 * @copyright	Copyright (C) 2017 Best Project, Inc. All rights reserved.
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die;

/**
 * BP Gallery component helper.
 */
class BPGalleryHelper extends JHelperContent
{

	/**
	 * Configure the Linkbar.
	 *
	 * @param   string  $vName  The name of the active view.
	 *
	 * @return  void
	 */
	public static function addSubmenu($vName)
	{
		JHtmlSidebar::addEntry(
			JText::_('COM_BPGALLERY_SUBMENU_IMAGES'),
			'index.php?option=com_bpgallery&view=images', $vName == 'images'
		);

		JHtmlSidebar::addEntry(
			JText::_('COM_BPGALLERY_SUBMENU_CATEGORIES'),
			'index.php?option=com_categories&extension=com_bpgallery',
			$vName == 'categories'
		);
	}

	/**
	 * Adds Count Items for Category Manager.
	 *
	 * @param   stdClass[]  &$items  The banner category objects
	 *
	 * @return  stdClass[]
	 *
	 * @since   3.5
	 */
	public static function countItems(&$items)
	{
		$db = JFactory::getDbo();

		/* TODO: Performance test */

		foreach ($items as $item) {
			$item->count_trashed	 = 0;
			$item->count_archived	 = 0;
			$item->count_unpublished = 0;
			$item->count_published	 = 0;
			$query					 = $db->getQuery(true);
			$query->select('state, count(*) AS count')
				->from($db->qn('#__bpgallery_images'))
				->where('catid = '.(int) $item->id)
				->group('state');
			$db->setQuery($query);
			$images					 = $db->loadObjectList();

			foreach ($images as $image) {
				if ($image->state == 1) {
					$item->count_published = $image->count;
				}

				if ($image->state == 0) {
					$item->count_unpublished = $image->count;
				}

				if ($image->state == 2) {
					$item->count_archived = $image->count;
				}

				if ($image->state == -2) {
					$item->count_trashed = $image->count;
				}
			}
		}

		return $items;
	}

	/**
	 * Returns a URL of a medium image thumbnail.
	 *
	 * @param	Object	$item		Image object
	 * @param	Boolean	$local		Should this function return a server path to the file?
	 * @param	Boolean	$absolute	Should this function return an absolute path/url to the file?
	 *
	 * @return	String
	 */
	public static function getSmallThumbnail($item, $local = false, $absolute = false)
	{

		if ($local) {
			$url = JPATH_ROOT.'/images/bpgallery/small/'.$item->filename;
			if ($absolute) {
				list($tmp, $url) = explode('images/bpgallery/small/', $url);
			}
		} else {
			$url = JURI::root(!$absolute).'/images/bpgallery/small/'.$item->filename;
		}

		return $url;
	}

	/**
	 * Returns a URL of a medium image thumbnail.
	 *
	 * @param	Object	$item		Image object
	 * @param	Boolean	$local		Should this function return a server path to the file?
	 * @param	Boolean	$absolute	Should this function return an absolute path/url to the file?
	 *
	 * @return	String
	 */
	public static function getMediumThumbnail($item, $local = false, $absolute = false)
	{

		if ($local) {
			$url = JPATH_ROOT.'/images/bpgallery/medium/'.$item->filename;
			if ($absolute) {
				list($tmp, $url) = explode('images/bpgallery/medium/', $url);
			}
		} else {
			$url = JURI::root(!$absolute).'/images/bpgallery/medium/'.$item->filename;
		}

		return $url;
	}


}