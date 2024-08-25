<?php

/**
 * @author      ${author.name} (${author.email})
 * @website     ${author.url}
 * @copyright   ${copyrights}
 * @license     ${license.url} ${license.name}
 * @package     ${package}.Component
 * @subpackage  BPGallery
 */

namespace BPExtensions\Component\BPGallery\Administrator\Service\HTML;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;

/**
 * BPGallery HTML helper
 */
class AdministratorService
{

    /**
     * Render the list of associated items
     *
     * @param   integer  $imageId  The article item id
     *
     * @return  string  The language HTML
     *
     * @throws  \Exception
     */
    public function association(int $imageId): string
    {
        // Defaults
        $html = '';

        // Get the associations
        if ($associations = Associations::getAssociations(
            'com_bpgallery',
            '#__bpgallery_images',
            'com_bpgallery.item',
            $imageId,
            'id',
            'alias',
            'catid',
        )) {
            foreach ($associations as $tag => $associated) {
                $associations[$tag] = (int)$associated->id;
            }

            // Get the associated menu items
            $db    = Factory::getContainer()->get(DatabaseDriver::class);
            $query = $db->getQuery(true)
                ->select(
                    [
                        'c.*',
                        $db->quoteName('l.sef', 'lang_sef'),
                        $db->quoteName('l.lang_code'),
                        $db->quoteName('cat.title', 'category_title'),
                        $db->quoteName('l.image'),
                        $db->quoteName('l.title', 'language_title'),
                    ]
                )
                ->from($db->quoteName('#__bpgallery_images', 'c'))
                ->join('LEFT', $db->quoteName('#__categories', 'cat'),
                    $db->quoteName('cat.id') . ' = ' . $db->quoteName('c.catid'))
                ->join('LEFT', $db->quoteName('#__languages', 'l'),
                    $db->quoteName('c.language') . ' = ' . $db->quoteName('l.lang_code'))
                ->whereIn($db->quoteName('c.id'), array_values($associations))
                ->where($db->quoteName('c.id') . ' != :imageId')
                ->bind(':imageId', $imageId, ParameterType::INTEGER);

            $db->setQuery($query);

            try {
                $items = $db->loadObjectList('id');
            } catch (\RuntimeException $e) {
                throw new \Exception($e->getMessage(), 500, $e);
            }

            if ($items) {
                $languages         = LanguageHelper::getContentLanguages([0, 1]);
                $content_languages = array_column($languages, 'lang_code');

                foreach ($items as &$item) {
                    if (in_array($item->lang_code, $content_languages, true)) {
                        $text    = $item->lang_code;
                        $url     = Route::_('index.php?option=com_bpgallery&task=image.edit&id=' . (int)$item->id);
                        $tooltip = '<strong>' . htmlspecialchars($item->language_title, ENT_QUOTES,
                                'UTF-8') . '</strong><br>'
                            . htmlspecialchars($item->title, ENT_QUOTES,
                                'UTF-8') . '<br>' . Text::sprintf('JCATEGORY_SPRINTF', $item->category_title);
                        $classes = 'badge bg-secondary';

                        $item->link = '<a href="' . $url . '" class="' . $classes . '">' . $text . '</a>'
                            . '<div role="tooltip" id="tip-' . (int)$imageId . '-' . (int)$item->id . '">' . $tooltip . '</div>';
                    } else {
                        // Display warning if Content Language is trashed or deleted
                        Factory::getApplication()->enqueueMessage(Text::sprintf('JGLOBAL_ASSOCIATIONS_CONTENTLANGUAGE_WARNING',
                            $item->lang_code), 'warning');
                    }
                }
            }

            $html = LayoutHelper::render('joomla.content.associations', $items);
        }

        return $html;
    }
}
