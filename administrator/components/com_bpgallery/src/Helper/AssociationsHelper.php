<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

namespace BPExtensions\Component\BPGallery\Administrator\Helper;

use BPExtensions\Component\BPGallery\Administrator\Table\ImageTable;
use BPExtensions\Component\BPGallery\Site\Helper\AssociationHelper;
use Exception;
use Joomla\CMS\Association\AssociationExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Table\Table;
use Joomla\Component\Categories\Administrator\Table\CategoryTable;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * BP Gallery associations helper.
 */
class AssociationsHelper extends AssociationExtensionHelper
{
    /**
     * The extension name
     *
     * @var     array $extension
     */
    protected $extension = 'com_content';

    /**
     * Array of item types
     *
     * @var     array $itemTypes
     */
    protected $itemTypes = ['article', 'category'];

    /**
     * Has the extension association support
     *
     * @var     boolean $associationsSupport
     */
    protected $associationsSupport = true;

    /**
     * Method to get the associations for a given item.
     *
     * @param   integer  $id    Id of the item
     * @param   string   $view  Name of the view
     *
     * @return  array   Array of associations for the item
     */
    public function getAssociationsForItem($id = 0, $view = null): array
    {
        return AssociationHelper::getAssociations($id, $view);
    }

    /**
     * Get the associated items for an item
     *
     * @param   string  $typeName  The item type
     * @param   int     $id        The id of item for which we need the associated items
     *
     * @return  array
     */
    public function getAssociations(string $typeName, int $id): array
    {
        $type = $this->getType($typeName);

        $context    = $this->extension . '.item';
        $catidField = 'catid';

        if ($typeName === 'category') {
            $context    = 'com_categories.item';
            $catidField = '';
        }

        // Get the associations.
        return Associations::getAssociations(
            $this->extension,
            $type['tables']['a'],
            $context,
            $id,
            'id',
            'alias',
            $catidField
        );
    }

    /**
     * Get information about the type
     *
     * @param   string  $typeName  The item type
     *
     * @return  array  Array of item types
     */
    public function getType($typeName = ''): array
    {
        $fields  = $this->getFieldsTemplate();
        $tables  = [];
        $joins   = [];
        $support = $this->getSupportTemplate();
        $title   = '';

        if (\in_array($typeName, $this->itemTypes)) {
            switch ($typeName) {
                case 'article':
                    $support['state']     = true;
                    $support['acl']       = true;
                    $support['checkout']  = true;
                    $support['category']  = true;
                    $support['save2copy'] = true;

                    $tables = [
                        'a' => '#__bpgallery_images',
                    ];

                    $title = 'article';
                    break;

                case 'category':
                    $fields['created_user_id'] = 'a.created_user_id';
                    $fields['ordering']        = 'a.lft';
                    $fields['level']           = 'a.level';
                    $fields['catid']           = '';
                    $fields['state']           = 'a.published';

                    $support['state']    = true;
                    $support['acl']      = true;
                    $support['checkout'] = true;
                    $support['level']    = true;

                    $tables = [
                        'a' => '#__categories',
                    ];

                    $title = 'category';
                    break;
            }
        }

        return [
            'fields'  => $fields,
            'support' => $support,
            'tables'  => $tables,
            'joins'   => $joins,
            'title'   => $title,
        ];
    }

    /**
     * Get item information
     *
     * @param   string  $typeName  The item type
     * @param   int     $id        The id of item for which we need the associated items
     *
     * @return  Table|null
     * @throws Exception
     */
    public function getItem(string $typeName, int $id): ?Table
    {
        if (empty($id)) {
            return null;
        }

        $table = null;

        $mvc = Factory::getApplication()->bootComponent('com_bpgallery')->getMVCFactory();

        /**
         * @var ImageTable|CategoryTable|null $table
         */

        switch ($typeName) {
            case 'image':
                $table = $mvc->createTable('Image');
                break;

            case 'category':
                $table = $mvc->createTable('Category');
                break;
        }

        if (\is_null($table)) {
            return null;
        }

        $table->load($id);

        return $table;
    }
}
