<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

namespace BPExtensions\Component\BPGallery\Site;

defined('_JEXEC') or die;

use BPExtensions\Component\BPGallery\Site\Helper\RouteHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\Component\Categories\Administrator\Helper\CategoryAssociationHelper;

/**
 * BPGallery Component Association Helper
 */
abstract class AssociationHelper extends CategoryAssociationHelper
{
    /**
     * Method to get the associations for a given item
     *
     * @param   integer  $id    Id of the item
     * @param   string   $view  Name of the view
     *
     * @return  array   Array of associations for the item
     */
    public static function getAssociations($id = 0, $view = null, $layout = null): array
    {
        $jinput = Factory::getApplication()->getInput();
        $view   = $view === null ? $jinput->get('view') : $view;
        $id     = empty($id) ? $jinput->getInt('id') : $id;
        $return = [];

        if ($view === 'image') {
            if ($id) {
                $associations = Associations::getAssociations(
                    'com_bpgallery',
                    '#__bpgallery_images',
                    'com_bpgallery.item',
                    $id,
                    'id',
                    'alias',
                    'catid',
                );

                foreach ($associations as $tag => $item) {
                    $return[$tag] = RouteHelper::getImageRoute($item->id, (int)$item->catid, $item->language, $layout);
                }

                return $return;
            }
        }

        if ($view === 'category' || $view === 'categories') {
            $return = self::getCategoryAssociations($id, 'com_bpgallery', $layout);
        }

        return $return;
    }
}
