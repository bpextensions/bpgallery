<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

namespace BPExtensions\Component\BPGallery\Site\Dispatcher;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcher;
use Joomla\CMS\Language\Text;

/**
 * ComponentDispatcher class for com_bpgallery
 */
class Dispatcher extends ComponentDispatcher
{
    /**
     * Dispatch a controller task. Redirecting the user if appropriate.
     *
     * @return  void
     */
    public function dispatch(): void
    {
        $checkCreateEdit =
            ($this->input->get('view') === 'category' && $this->input->get('layout') === 'modal') ||
            ($this->input->get('view') === 'image' && $this->input->get('layout') === 'modal');

        if ($checkCreateEdit) {
            // Can create in any category (component permission) or at least in one category
            $canCreateRecords = $this->app->getIdentity()->authorise('core.create', 'com_bpgallery') || count(
                    $this->app->getIdentity()->getAuthorisedCategories('com_bpgallery', 'core.create')
                ) > 0;

            // Instead of checking edit on all records, we can use **same** check as the form editing view
            $values           = (array)$this->app->getUserState('com_bpgallery.edit.image.id');
            $isEditingRecords = count($values);
            $hasAccess        = $canCreateRecords || $isEditingRecords;

            if (!$hasAccess) {
                $this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');

                return;
            }
        }

        parent::dispatch();
    }
}
