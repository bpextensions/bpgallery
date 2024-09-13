<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

namespace BPExtensions\Component\BPGallery\Administrator\Table;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * BP Gallery image table
 */
class ImageTable extends Table implements CurrentUserInterface
{
    use CurrentUserTrait;

    /**
     * Constructor
     *
     * @param   DatabaseDriver  &     $db          Database connector object
     * @param   ?DispatcherInterface  $dispatcher  Event dispatcher for this table
     */
    public function __construct(DatabaseDriver $db, DispatcherInterface $dispatcher = null)
    {
        parent::__construct('#__bpgallery_images', 'id', $db, $dispatcher);

        $this->created = Factory::getDate()->toSql();
        $this->setColumnAlias('published', 'state');
    }

    /**
     * Overloaded check function
     *
     * @return  boolean
     *
     * @see     Table::check
     */
    public function check(): bool
    {
        if (empty($this->title)) {
            $this->title = pathinfo($this->upload_file_name, PATHINFO_FILENAME);
        }

        // Set name
        $this->title = htmlspecialchars_decode($this->title, ENT_QUOTES);

        // Set alias
        if (empty($this->alias)) {
            $this->alias = $this->title;
        }

        $this->alias = ApplicationHelper::stringURLSafe($this->alias, $this->language);

        if (trim(str_replace('-', '', $this->alias)) === '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        // Check the publish down date is not earlier than publish up.
        if (!$this->publish_down) {
            $this->publish_down = null;
        }

        // Make sure the entry have an intro
        if (empty($this->intro)) {
            $this->intro = '';
        }

        if (empty($this->params)) {
            $this->params = (new Registry())->toString();
        }

        if (empty($this->metadata)) {
            $this->metadata = (new Registry())->toString();
        }

        // Make sure the entry have a description
        if (empty($this->description)) {
            $this->description = '';
        }

        // Set ordering
        if ($this->state < 0) {
            // Set ordering to 0 if state is archived or trashed
            $this->ordering = 0;
        } elseif (empty($this->ordering)) {
            // Set ordering to last if ordering was 0
            $this->ordering = $this->getNextOrder(
                $this->_db->quoteName('catid') . '=' . $this->_db->quote($this->catid) . ' AND state>=0'
            );
        }

        if (empty($this->publish_up) || $this->publish_up === $this->getDbo()->getNullDate()) {
            $this->publish_up = Factory::getDate()->toSql();
        }

        if (empty($this->publish_down) || $this->publish_down === $this->getDbo()->getNullDate()) {
            $this->publish_down = null;
        }

        if (empty($this->checked_out_time) || $this->checked_out_time === $this->getDbo()->getNullDate()) {
            $this->checked_out_time = null;
        }

        if (empty($this->modified)) {
            $this->modified = Factory::getDate()->toSql();
        }

        if (empty($this->language)) {
            $this->language = '*';
        }

        return true;
    }

    /**
     * Overloaded bind function
     *
     * @param   array  $src     Named array
     * @param   mixed  $ignore  An optional array or space separated list of properties
     *                          to ignore while binding.
     *
     * @return  mixed  Null if operation was satisfactory, otherwise returns an error string
     *
     * @see     Table::bind()
     */
    public function bind($src, $ignore = ''): bool
    {
        if (isset($src['metadata']) && is_array($src['metadata'])) {
            $registry        = new Registry($src['metadata']);
            $src['metadata'] = (string)$registry;
        }

        return parent::bind($src, $ignore);
    }
}
