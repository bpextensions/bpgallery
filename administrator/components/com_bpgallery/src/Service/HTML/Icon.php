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

use BPExtensions\Component\BPGallery\Administrator\Extension\BPGalleryComponent;
use BPExtensions\Component\BPGallery\Site\Helper\RouteHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Workflow\Workflow;
use Joomla\Registry\Registry;

/**
 * BPGallery Component HTML Helper
 */
class Icon
{
    /**
     * Method to generate a link to the create item page for the given category
     *
     * @param   object    $category  The category information
     * @param   Registry  $params    The item parameters
     * @param   array     $attribs   Optional attributes for the link
     * @param   boolean   $legacy    True to use legacy images, false to use icomoon based graphic
     *
     * @return  string  The HTML markup for the create item link
     */
    public function create($category, $params, $attribs = [], $legacy = false): string
    {
        $uri = Uri::getInstance();

        $url = 'index.php?option=com_bpgallery&task=image.add&return=' . base64_encode($uri) . '&a_id=0&catid=' . $category->id;

        $text = '';

        if ($params->get('show_icons')) {
            $text .= '<span class="icon-plus icon-fw" aria-hidden="true"></span>';
        }

        $text .= Text::_('COM_BPGALLERY_NEW_IMAGE');

        // Add the button classes to the attribs array
        if (isset($attribs['class'])) {
            $attribs['class'] .= ' btn btn-primary';
        } else {
            $attribs['class'] = 'btn btn-primary';
        }

        return HTMLHelper::_('link', Route::_($url), $text, $attribs);
    }

    /**
     * Display an edit icon for the image.
     *
     * This icon will not display in a popup window, nor if the image is trashed.
     * Edit access checks must be performed in the calling code.
     *
     * @param   object    $image    The image information
     * @param   Registry  $params   The item parameters
     * @param   array     $attribs  Optional attributes for the link
     * @param   boolean   $legacy   True to use legacy images, false to use icomoon based graphic
     *
     * @return  string    The HTML for the image edit icon.
     */
    public function edit($image, $params, $attribs = [], $legacy = false): string
    {
        $user = Factory::getApplication()->getIdentity();
        $uri  = Uri::getInstance();

        // Ignore if in a popup window.
        if ($params && $params->get('popup')) {
            return '';
        }

        // Ignore if the state is negative (trashed).
        if (!in_array($image->state, [Workflow::CONDITION_UNPUBLISHED, Workflow::CONDITION_PUBLISHED])) {
            return '';
        }

        // Show checked_out icon if the image is checked out by a different user
        if (property_exists($image, 'checked_out')
            && property_exists($image, 'checked_out_time')
            && !is_null($image->checked_out)
            && $image->checked_out != $user->get('id')) {
            $checkoutUser = Factory::getApplication()->getIdentity();
            $checkoutUser->load($image->checked_out);
            $date    = HTMLHelper::_('date', $image->checked_out_time);
            $tooltip = Text::sprintf('COM_BPGALLERY_CHECKED_OUT_BY', $checkoutUser->name) . ' <br> ' . $date;

            $text = LayoutHelper::render('joomla.content.icons.edit_lock',
                ['image' => $image, 'tooltip' => $tooltip, 'legacy' => $legacy]);

            $attribs['aria-describedby'] = 'editimage-' . (int)$image->id;

            return HTMLHelper::_('link', '#', $text, $attribs);
        }

        $contentUrl = RouteHelper::getImageRoute($image->slug, $image->catid, $image->language);
        $url        = $contentUrl . '&task=image.edit&a_id=' . $image->id . '&return=' . base64_encode($uri);

        if ($image->state == BPGalleryComponent::CONDITION_UNPUBLISHED) {
            $tooltip = Text::_('COM_BPGALLERY_EDIT_UNPUBLISHED_IMAGE');
        } else {
            $tooltip = Text::_('COM_BPGALLERY_EDIT_PUBLISHED_IMAGE');
        }

        $text = LayoutHelper::render('joomla.content.icons.edit',
            ['image' => $image, 'tooltip' => $tooltip, 'legacy' => $legacy]);

        $attribs['aria-describedby'] = 'editimage-' . (int)$image->id;

        return HTMLHelper::_('link', Route::_($url), $text, $attribs);
    }

    /**
     * Method to generate a link to print an image
     *
     * @param   Registry  $params  The item parameters
     * @param   boolean   $legacy  True to use legacy images, false to use icomoon based graphic
     *
     * @return  string  The HTML markup for the popup link
     */
    public function print_screen($params, $legacy = false): string
    {
        $text = LayoutHelper::render('joomla.content.icons.print_screen', ['params' => $params, 'legacy' => $legacy]);

        return '<button type="button" onclick="window.print();return false;">' . $text . '</button>';
    }
}
