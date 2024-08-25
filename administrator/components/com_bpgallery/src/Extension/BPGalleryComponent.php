<?php

/**
 * @author      ${author.name} (${author.email})
 * @website     ${author.url}
 * @copyright   ${copyrights}
 * @license     ${license.url} ${license.name}
 * @package     ${package}.Component
 * @subpackage  BPGallery
 */

namespace BPExtensions\Component\BPGallery\Administrator\Extension;

\defined('JPATH_PLATFORM') or die;

use BPExtensions\Component\BPGallery\Administrator\Helper\BPGalleryHelper;
use BPExtensions\Component\BPGallery\Administrator\Service\HTML\AdministratorService;
use BPExtensions\Component\BPGallery\Administrator\Service\HTML\Icon;
use Exception;
use Joomla\CMS\Association\AssociationServiceInterface;
use Joomla\CMS\Association\AssociationServiceTrait;
use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Factory;
use Joomla\CMS\Fields\FieldsServiceInterface;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper as LibraryContentHelper;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Tag\TagServiceInterface;
use Joomla\CMS\Tag\TagServiceTrait;
use Joomla\CMS\Workflow\WorkflowServiceInterface;
use Joomla\CMS\Workflow\WorkflowServiceTrait;
use Psr\Container\ContainerInterface;

/**
 * Component class for com_bpgallery
 */
class BPGalleryComponent extends MVCComponent implements
    BootableExtensionInterface, CategoryServiceInterface, FieldsServiceInterface, AssociationServiceInterface,
    WorkflowServiceInterface, RouterServiceInterface, TagServiceInterface
{
    use AssociationServiceTrait;
    use RouterServiceTrait;
    use HTMLRegistryAwareTrait;
    use WorkflowServiceTrait;
    use CategoryServiceTrait, TagServiceTrait {
        CategoryServiceTrait::getTableNameForSection insteadof TagServiceTrait;
        CategoryServiceTrait::getStateColumnForSection insteadof TagServiceTrait;
    }

    /**
     * The trashed condition
     */
    const CONDITION_NAMES = [
        self::CONDITION_PUBLISHED   => 'JPUBLISHED',
        self::CONDITION_UNPUBLISHED => 'JUNPUBLISHED',
        self::CONDITION_ARCHIVED    => 'JARCHIVED',
        self::CONDITION_TRASHED     => 'JTRASHED',
    ];
    /**
     * The archived condition
     */
    public const CONDITION_ARCHIVED = 2;
    /**
     * The published condition
     */
    public const CONDITION_PUBLISHED = 1;
    /**
     * The unpublished condition
     */
    public const CONDITION_UNPUBLISHED = 0;
    /**
     * The trashed condition
     */
    public const CONDITION_TRASHED = -2;
    /** @var array Supported functionality */
    protected $supportedFunctionality = [
        'core.featured' => true,
        'core.state'    => true,
    ];

    /**
     * Booting the extension. This is the function to set up the environment of the extension like
     * registering new class loaders, etc.
     *
     * If required, some initial set up can be done from services of the container, eg.
     * registering HTML services.
     *
     * @param   ContainerInterface  $container  The container
     *
     * @return  void
     */
    public function boot(ContainerInterface $container): void
    {

        $this->getRegistry()->register('BPGalleryAdministrator', new AdministratorService);
        $this->getRegistry()->register('BPGalleryIcon', new Icon());
    }

    /**
     * Returns a valid section for the given section. If it is not valid then null
     * is returned.
     *
     * @param   string  $section  The section to get the mapping for
     * @param   object  $item     The item
     *
     * @return  string|null  The new section
     *
     * @throws \Exception
     */
    public function validateSection($section, $item = null): ?string
    {
        if (Factory::getApplication()->isClient('site')) {
            // On the front end we need to map some sections
            switch ($section) {
                // Editing an image
                case 'form':

                    // Category list view
                case 'category':
                    $section = 'image';
            }
        }

        if ($section !== 'image') {
            // We don't know other sections
            return null;
        }

        return $section;
    }

    /**
     * Returns valid contexts
     *
     * @return  array
     * @throws Exception
     */
    public function getContexts(): array
    {
        Factory::getApplication()->getLanguage()->load('com_bpgallery', JPATH_ADMINISTRATOR);

        return [
            'com_bpgallery.image'      => Text::_('COM_BPGALLERY'),
            'com_bpgallery.categories' => Text::_('JCATEGORY')
        ];
    }

    /**
     * Returns the workflow context based on the given category section
     *
     * @param   string  $section  The section
     *
     * @return  string|null
     * @throws Exception
     */
    public function getCategoryWorkflowContext(?string $section = null): string
    {
        $context = $this->getWorkflowContexts();

        // @codingStandardsIgnoreStart
        return array_key_first($context);
        // @codingStandardsIgnoreEnd
    }

    /**
     * Returns valid contexts
     *
     * @return  array
     * @throws Exception
     */
    public function getWorkflowContexts(): array
    {
        Factory::getApplication()->getLanguage()->load('com_bpgallery', JPATH_ADMINISTRATOR);

        return [
            'com_bpgallery.image' => Text::_('COM_BPGALLERY')
        ];
    }

    /**
     * Returns a table name for the state association
     *
     * @param   string  $section  An optional section to separate different areas in the component
     *
     * @return  string
     */
    public function getWorkflowTableBySection(?string $section = null): string
    {
        return '#__bpgallery_images';
    }

    /**
     * Returns the model name, based on the context
     *
     * @param   string  $context  The context of the workflow
     *
     * @return string
     * @throws Exception
     */
    public function getModelName($context): string
    {
        $parts = explode('.', $context);

        if (count($parts) < 2) {
            return '';
        }

        array_shift($parts);

        $modelname = array_shift($parts);

        if ($modelname === 'image' && Factory::getApplication()->isClient('site')) {
            return 'Form';
        }

        if ($modelname === 'featured' && Factory::getApplication()->isClient('administrator')) {
            return 'Image';
        }

        return ucfirst($modelname);
    }

    /**
     * Method to filter transitions by given id of state.
     *
     * @param   array  $transitions  The Transitions to filter
     * @param   int    $pk           Id of the state
     *
     * @return  array
     */
    public function filterTransitions(array $transitions, int $pk): array
    {
        return BPGalleryHelper::filterTransitions($transitions, $pk);
    }

    /**
     * Adds Count Items for Category Manager.
     *
     * @param   \stdClass[]  $items    The category objects
     * @param   string       $section  The section
     *
     * @return  void
     */
    public function countItems(array $items, string $section): void
    {
        $config = (object)[
            'related_tbl'         => 'bpgallery_images',
            'state_col'           => 'state',
            'group_col'           => 'catid',
            'relation_type'       => 'category_or_group',
            'uses_workflows'      => true,
            'workflows_component' => 'com_bpgallery'
        ];

        LibraryContentHelper::countRelations($items, $config);
    }

    /**
     * Adds Count Items for Tag Manager.
     *
     * @param   \stdClass[]  $items      The content objects
     * @param   string       $extension  The name of the active view.
     *
     * @return  void
     * @throws  \Exception
     */
    public function countTagItems(array $items, string $extension): void
    {
        $parts   = explode('.', $extension);
        $section = count($parts) > 1 ? $parts[1] : null;

        $config = (object)[
            'related_tbl'   => ($section === 'category' ? 'categories' : 'bpgallery_images'),
            'state_col'     => ($section === 'category' ? 'published' : 'state'),
            'group_col'     => 'tag_id',
            'extension'     => $extension,
            'relation_type' => 'tag_assigments',
        ];

        LibraryContentHelper::countRelations($items, $config);
    }

    /**
     * Prepares the category form
     *
     * @param   Form          $form  The form to prepare
     * @param   array|object  $data  The form data
     *
     * @return void
     * @throws Exception
     */
    public function prepareForm(Form $form, $data)
    {
        BPGalleryHelper::onPrepareForm($form, $data);
    }

    /**
     * Returns the table for the count items functions for the given section.
     *
     * @param   string  $section  The section
     *
     * @return  string|null
     */
    protected function getTableNameForSection(string $section = null): ?string
    {
        return '#__bpgallery_images';
    }
}
