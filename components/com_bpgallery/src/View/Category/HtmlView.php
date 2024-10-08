<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

namespace BPExtensions\Component\BPGallery\Site\View\Category;

defined('_JEXEC') or die;

use BPExtensions\Component\BPGallery\Site\Helper\LayoutHelper;
use BPExtensions\Component\BPGallery\Site\Helper\RouteHelper;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Event\Content\AfterDisplayEvent;
use Joomla\CMS\Event\Content\AfterTitleEvent;
use Joomla\CMS\Event\Content\BeforeDisplayEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Menu\MenuItem;
use Joomla\CMS\MVC\View\CategoryView;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use stdClass;

/**
 * HTML View class for the BPGallery component
 */
class HtmlView extends CategoryView
{
    /**
     * An array of items
     *
     * @var  array
     */
    public $items;

    /**
     * @var    string  The name of the extension for the category
     */
    protected $extension = 'com_bpgallery';

    /**
     * @var    string  Default title to use for page title
     */
    protected $defaultPageTitle = 'COM_BPGALLERY_DEFAULT_PAGE_TITLE';

    /**
     * @var    string  The name of the view to link individual items to
     */
    protected $viewName = 'image';

    public ?Registry $params = null;

    protected ?MenuItem $menu = null;

    protected ?int $maxLevel = null;

    public ?FileLayout $layoutThumbnail = null;

    public ?FileLayout $layoutCategory = null;

    public $pagination = null;

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @throws Exception
     */
    public function display($tpl = null): void
    {
        $this->commonCategoryDisplay();

        // Flag indicates to not add limitstart=0 to URL
        $this->pagination->hideEmptyLimitstart = true;

        // Prepare the data
        // Get the metrics for the structural page layout.
        $params = $this->params;

        /**
         * @var CMSApplication $app
         */
        $app        = Factory::getApplication();
        $dispatcher = $app->getDispatcher();

        // Check for errors.
        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        // Process the content plugins.
        PluginHelper::importPlugin('bpgallery', null, true, $dispatcher);

        // Prepare the data.
        // Compute the image slug.
        foreach ($this->items as $item) {
            $item->slug   = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
            $temp         = $item->params;
            $item->params = clone $this->params;
            $item->params->merge($temp);

            $eventArguments = [
                'context' => 'com_bpgallery.category',
                'subject' => $item,
                'params'  => $item->params,
            ];

            LayoutHelper::processImageEvents($item, $dispatcher, $eventArguments);
        }


        // Because the application sets a default page title,
        // we need to get it from the menu item itself
        $active = $app->getMenu()->getActive();

        if ($this->menuItemMatchCategory) {
            $this->params->def('page_heading', $this->params->get('page_title', $active->title));
            $title = $this->params->get('page_title', $active->title);
        } else {
            $this->params->def('page_heading', $this->category->title);
            $title = $this->category->title;
            $this->params->set('page_title', $title);
        }

        if (empty($title)) {
            $title = $this->category->title;
        }

        $this->setDocumentTitle($title);
        /**
         * @var HtmlDocument $doc
         */
        $doc = $app->getDocument();

        if ($this->category->metadesc) {
            $doc->setDescription($this->category->metadesc);
        } elseif ($this->params->get('menu-meta_description')) {
            $doc->setDescription($this->params->get('menu-meta_description'));
        }

        if ($this->params->get('robots')) {
            $doc->setMetaData('robots', $this->params->get('robots'));
        }

        if (!is_object($this->category->metadata)) {
            $this->category->metadata = new Registry($this->category->metadata);
        }

        if (($app->get('MetaAuthor') == '1') && $this->category->get('author', '')) {
            $doc->setMetaData('author', $this->category->get('author', ''));
        }

        $mdata = $this->category->metadata->toArray();

        foreach ($mdata as $k => $v) {
            if ($v) {
                $doc->setMetaData($k, $v);
            }
        }

        // Bind category events
        $afterTitleEvent    = new AfterTitleEvent('ContentAfterTitle', [
            'context' => $this->category->extension . '.categories',
            'subject' => $this->category,
            'params'  => $this->params,
        ]);
        $beforeDisplayEvent = new BeforeDisplayEvent('ContentBeforeDisplay', [
            'context' => $this->category->extension . '.categories',
            'subject' => $this->category,
            'params'  => $this->params,
        ]);
        $afterDisplayEvent  = new AfterDisplayEvent('ContentAfterDisplay', [
            'context' => $this->category->extension . '.categories',
            'subject' => $this->category,
            'params'  => $this->params,
        ]);

        $this->category->event = new stdClass();

        $this->category->event->afterDisplayTitle    = $dispatcher->dispatch(
            $afterTitleEvent->getName(),
            $afterTitleEvent
        )->getArgument('result', []);
        $this->category->event->beforeDisplayContent = $dispatcher->dispatch(
            $beforeDisplayEvent->getName(),
            $beforeDisplayEvent
        )->getArgument('result', []);
        $this->category->event->afterDisplayContent  = $dispatcher->dispatch(
            $afterDisplayEvent->getName(),
            $afterDisplayEvent
        )->getArgument('result', []);

        parent::display($tpl);
    }

    public function commonCategoryDisplay(): void
    {
        parent::commonCategoryDisplay();

        $this->params->merge($this->getModel()->getState('params'));
    }

    /**
     * Prepares the document
     *
     * @return  void
     */
    protected function prepareDocument(): void
    {
        parent::prepareDocument();

        if ($this->menuItemMatchCategory) {
            // If the active menu item is linked directly to the category being displayed, no further process is needed
            return;
        }

        $menu = $this->menu;

        if ($menu && $menu->component === 'com_bpgallery' && isset($menu->query['view'])
            && in_array($menu->query['view'], ['categories', 'category'])) {
            $id = $menu->query['id'];
        } else {
            $id = 0;
        }

        $path     = [['title' => $this->category->title, 'link' => '']];
        $category = $this->category->getParent();

        while ($category !== null && $category->id !== 'root' && $category->id != $id) {
            $path[]   = [
                'title' => $category->title,
                'link'  => RouteHelper::getCategoryRoute($category->id, $category->language)
            ];
            $category = $category->getParent();
        }

        $path = array_reverse($path);

        foreach ($path as $item) {
            $this->pathway->addItem($item['title'], $item['link']);
        }
    }
}
