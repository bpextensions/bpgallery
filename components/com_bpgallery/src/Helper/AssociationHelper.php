<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

namespace BPExtensions\Component\BPGallery\Site\Helper;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Multilanguage;
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
     * @throws Exception
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


    /**
     * Method to display in frontend the associations for a given article
     *
     * @param   integer  $id  Id of the article
     *
     * @return  array  An array containing the association URL and the related language object
     * @throws Exception
     */
    public static function displayAssociations(int $id): array
    {
        $return = [];

        if ($associations = self::getAssociations($id, 'article')) {
            $levels    = Factory::getApplication()->getIdentity()->getAuthorisedViewLevels();
            $languages = LanguageHelper::getLanguages();

            foreach ($languages as $language) {
                // Do not display language when no association
                if (empty($associations[$language->lang_code])) {
                    continue;
                }

                // Do not display language without frontend UI
                if (!array_key_exists($language->lang_code, LanguageHelper::getInstalledLanguages(0))) {
                    continue;
                }

                // Do not display language without specific home menu
                if (!array_key_exists($language->lang_code, Multilanguage::getSiteHomePages())) {
                    continue;
                }

                // Do not display language without authorized access level
                if (isset($language->access) && $language->access && !in_array($language->access, $levels, true)) {
                    continue;
                }

                $return[$language->lang_code] = ['item'     => $associations[$language->lang_code],
                                                 'language' => $language
                ];
            }
        }

        return $return;
    }
}
