<?php

/**
 * @author        ${author.name} (${author.email})
 * @website        ${author.url}
 * @copyright    ${copyrights}
 * @license        ${license.url} ${license.name}
 * @package        ${package}
 * @subpackage        ${subpackage}
 */

use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * BP Gallery image table
 */
class BPGalleryTableImage extends JTable
{
    /**
     * Constructor
	 *
	 * @param   JDatabaseDriver  &$db  Database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__bpgallery_images', 'id', $db);

		JTableObserverContenthistory::createObserver($this, array('typeAlias' => 'com_bpgallery.image'));

		$this->created = JFactory::getDate()->toSql();
		$this->setColumnAlias('published', 'state');
	}

	/**
	 * Overloaded check function
	 *
	 * @return  boolean
	 *
	 * @see     JTable::check
	 * @since   1.5
	 */
	public function check()
	{
		// Set name
		$this->title = htmlspecialchars_decode($this->title, ENT_QUOTES);

		// Set alias
		if (trim($this->alias) == '')
		{
			$this->alias = $this->title;
		}

		$this->alias = JApplicationHelper::stringURLSafe($this->alias, $this->language);

		if (trim(str_replace('-', '', $this->alias)) == '')
		{
			$this->alias = JFactory::getDate()->format('Y-m-d-H-i-s');
		}

		// Check the publish down date is not earlier than publish up.
		if ($this->publish_down > $this->_db->getNullDate() && $this->publish_down < $this->publish_up)
		{
			$this->setError(JText::_('JGLOBAL_START_PUBLISH_AFTER_FINISH'));

			return false;
		}

		// Set ordering
		if ($this->state < 0)
		{
			// Set ordering to 0 if state is archived or trashed
			$this->ordering = 0;
		}
		elseif (empty($this->ordering))
		{
			// Set ordering to last if ordering was 0
			$this->ordering = self::getNextOrder($this->_db->quoteName('catid') . '=' . $this->_db->quote($this->catid) . ' AND state>=0');
		}

		if (empty($this->publish_up))
		{
            $this->publish_up = $this->getDbo()->getNullDate();
        }

        if (empty($this->publish_down)) {
            $this->publish_down = $this->getDbo()->getNullDate();
        }

        if (empty($this->modified)) {
            $this->modified = $this->getDbo()->getNullDate();
        }

        if (empty($this->language)) {
            $this->language = '*';
        }

        return true;
    }

    /**
     * Overloaded bind function
     *
     * @param array $array Named array
     * @param mixed $ignore An optional array or space separated list of properties
     *                          to ignore while binding.
     *
     * @return  mixed  Null if operation was satisfactory, otherwise returns an error string
     *
     * @see     Table::bind()
     * @since   1.0
     */
    public function bind($array, $ignore = '')
    {
        if (isset($array['metadata']) && is_array($array['metadata'])) {
            $registry = new Registry($array['metadata']);
            $array['metadata'] = (string)$registry;
        }

        return parent::bind($array, $ignore);
    }

}
