<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

namespace BPExtensions\Component\BPGallery\Site\View\Image;

defined('_JEXEC') or die;

use BPExtensions\Component\BPGallery\Site\Helper\AssociationHelper;
use BPExtensions\Component\BPGallery\Site\Helper\LayoutHelper;
use BPExtensions\Component\BPGallery\Site\Helper\RouteHelper;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\CMS\User\User;
use Joomla\Registry\Registry;
use stdClass;


/**
 * HTML Image View class for the BP Gallery component
 */
class HtmlView extends BaseHtmlView
{
    use CurrentUserTrait;

    protected ?stdClass $item;

    protected ?Registry $params;

    protected bool $print = false;

    protected stdClass $state;

    protected ?User $user;

    /**
     * The page class suffix
     */
    protected string $pageclass_sfx = '';

    /**
     * The flag to mark if the active menu item is linked to the being displayed image
     */
    protected bool $menuItemMatchImage = false;

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *                        *
     *
     * @throws Exception
     * @throws Exception
     */
    public function display($tpl = null): void
    {

        $app = Factory::getApplication();
        $user = $this->getCurrentUser();
        
        $this->item = $this->get('Item');
        $this->print = $app->input->getBool('print', false);
        $this->state = $this->get('State');
        $this->user = $user;

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }


        // Add router helpers.
        $item = $this->item;
        $item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;

        // No link for ROOT category
        if ($item->parent_alias === 'root') {
            $item->parent_id = null;
        }

        $item->readmore_link = Route::_(RouteHelper::getImageRoute($item->slug, $item->catid, $item->language));

        // Merge image params. If this is single-image view, menu params override image params
        // Otherwise, image params override menu item params
        $this->params = $this->state->get('params');
        $active       = $app->getMenu()->getActive();
        $temp         = clone $this->params;

        // Check to see which parameters should take priority. If the active menu item link to the current image, then
        // the menu item params take priority
        if ($active
            && $active->component === 'com_bpgallery'
            && isset($active->query['view'], $active->query['id'])
            && $active->query['view'] === 'image'
            && (int)$active->query['id'] === $item->id) {
            $this->menuItemMatchImage = true;

            // Load layout from active query (in case it is an alternative menu item)
            if (isset($active->query['layout'])) {
                $this->setLayout($active->query['layout']);
            } elseif ($layout = $item->params->get('image_layout')) {
                // Check for alternative layout of image
                $this->setLayout($layout);
            }

            // $item->params are the image params, $temp are the menu item params
            // Merge so that the menu item params take priority
            $item->params->merge($temp);
        } else {
            // The active menu item is not linked to this image, so the image params take priority here
            // Merge the menu item params with the image params so that the image params take priority
            $temp->merge($item->params);
            $item->params = $temp;

            // Check for alternative layouts (since we are not in a single-image menu item)
            // Single-image menu item layout takes priority over alt layout for an image
            if ($layout = $item->params->get('image_layout')) {
                $this->setLayout($layout);
            }
        }

        $offset = $this->state->get('list.offset');

        // Check the view access to the image (the model has already computed the values).
        if ($item->params->get('access-view') == false && ($item->params->get('show_noauth', '0') == '0')) {
            $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $app->setHeader('status', 403, true);

            return;
        }

        /**
         * Check for no 'access-view' and empty fulltext,
         * - Redirect guest users to login
         * - Deny access to logged users with 403 code
         * NOTE: we do not recheck for no access-view + show_noauth disabled ... since it was checked above
         */
        if ((bool)$item->params->get('access-view') === false && $item->fulltext == '') {
            if ($this->user->get('guest')) {
                $return                = base64_encode(Uri::getInstance());
                $login_url_with_return = Route::_('index.php?option=com_users&view=login&return=' . $return);
                $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
                $app->redirect($login_url_with_return, 403);
            } else {
                $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
                $app->setHeader('status', 403, true);

                return;
            }
        };

        if (Associations::isEnabled() && $item->params->get('show_associations')) {
            $item->associations = AssociationHelper::displayAssociations($item->id);
        }

        $dispatcher = $this->getDispatcher();

        // Process the content plugins.
        PluginHelper::importPlugin('bpgallery', null, true, $dispatcher);

        $eventArguments = [
            'context' => 'com_bpgallery.image',
            'subject' => $item,
            'params'  => $item->params,
        ];

        LayoutHelper::processImageEvents($item, $dispatcher, $eventArguments);

        // Escape strings for HTML output
        $this->pageclass_sfx = htmlspecialchars($this->item->params->get('pageclass_sfx', ''));

        $this->_prepareDocument();

        parent::display($tpl);
    }

    /**
     * Prepares the document.
     *
     * @return  void
     *
     * @throws Exception
     */
    protected function _prepareDocument()
    {
        /**
         * @var CMSApplication $app
         * @var HtmlDocument   $doc
         */
        $app     = Factory::getApplication();
        $pathway = $app->getPathway();
        $doc     = $app->getDocument();

        /**
         * Because the application sets a default page title,
         * we need to get it from the menu item itself
         */
        $menu = $app->getMenu()->getActive();

        if ($menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
        } else {
            $this->params->def('page_heading', $this->item->title);
        }

        $title = $this->params->get('page_title', '');

        // If the menu item is not linked to this article
        if (!$this->menuItemMatchImage) {
            // If a browser page title is defined, use that, then fall back to the article title if set, then fall back to the page_title option
            $title = $this->item->params->get('article_page_title', $this->item->title ?: $title);

            // Get ID of the category from active menu item
            if ($menu && $menu->component === 'com_govarticle' && isset($menu->query['view'])
                && in_array($menu->query['view'], ['categories', 'category'])) {
                $id = $menu->query['id'];
            } else {
                $id = 0;
            }

            $path     = [['title' => $this->item->title, 'link' => '']];
            $category = Factory::getApplication()->bootComponent('BPGallery')->getCategory()->get($this->item->catid);

            while ($category !== null && $category->id != $id && $category->id !== 'root') {
                $path[] = [
                    'title' => $category->title,
                    'link'  => RouteHelper::getCategoryRoute($category->id, $category->language)];
                $category = $category->getParent();
            }

            $path = array_reverse($path);

            foreach ($path as $item) {
                $pathway->addItem($item['title'], $item['link']);
            }
        }

        if (empty($title)) {
            $title = $this->item->title;
        }

        $this->setDocumentTitle($title);

        if ($this->item->metadesc) {
            $doc->setDescription($this->item->metadesc);
        } elseif ($this->params->get('menu-meta_description')) {
            $doc->setDescription($this->params->get('menu-meta_description'));
        }

        if ($this->params->get('robots')) {
            $doc->setMetaData('robots', $this->params->get('robots'));
        }

        if ($app->get('MetaAuthor') == '1') {
            $author = $this->item->created_by_alias ?: $this->item->author;
            $doc->setMetaData('author', $author);
        }

        $mdata = $this->item->metadata->toArray();

        foreach ($mdata as $k => $v) {
            if ($v) {
                $doc->setMetaData($k, $v);
            }
        }

        // If there is a pagebreak heading or title, add it to the page title
        if (!empty($this->item->page_title)) {
            $this->item->title = $this->item->title . ' - ' . $this->item->page_title;
            $this->setDocumentTitle(
                $this->item->page_title . ' - ' . Text::sprintf('PLG_CONTENT_PAGEBREAK_PAGE_NUM',
                    $this->state->get('list.offset') + 1)
            );
        }

        if ($this->print) {
            $doc->setMetaData('robots', 'noindex, nofollow');
        }

    }
}
