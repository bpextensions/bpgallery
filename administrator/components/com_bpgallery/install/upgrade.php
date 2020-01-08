<?php

/**
 * @author        ${author.name} (${author.email})
 * @website        ${author.url}
 * @copyright    ${copyrights}
 * @license        ${license.url} ${license.name}
 * @package        ${package}
 * @subpackage        ${subpackage}
 */

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/**
 * BP Gallery installation tasks class.
 */
class com_bpgalleryInstallerScript
{

    /**
     * Images parent directory.
     *
     * @var string|null
     */
    protected $images_path;

    /**
     * Images removed message.
     *
     * @var string|null
     */
    protected $msg_uninstall_images;

    /**
     * Method to install the component
     */
    function install($parent)
    {
//		JFactory::getApplication()->enqueueMessage('Running install');
    }

    /**
     * Method to uninstall the component
     */
    function uninstall($parent)
    {

        // Remove images directory
        if (!is_null($this->images_path) and is_dir($this->images_path)) {
            $count = $this->rmdir($this->images_path);

            // Inform user about removed images
            if ($count > 0) {
                Factory::getApplication()->enqueueMessage(sprintf($this->msg_uninstall_images, $count));
            }

        }
    }

    /**
     * Remove directory recursively.
     *
     * @param string $path
     *
     * @return int Number of files removed.
     */
    protected function rmdir(string $path): int
    {
        $count = 0;
        foreach (new DirectoryIterator($path) as $f) {
            if ($f->isDot()) continue;
            if ($f->isFile()) {
                unlink($f->getPathname());
                $count++;
            } else if ($f->isDir()) {
                $count += $this->rmdir($path);
            }
        }

        return $count;
    }

    /**
     * Method to update the component
     */
    function update($parent)
    {
//		JFactory::getApplication()->enqueueMessage('Running update');
    }

    /**
     * Method to run before an install/update/uninstall method
     *
     * @param String $type Name of actions (update,install,uninstall,discover_install)
     * @param Object $parent Manifest file instance
     */
    function preflight($type, $parent)
    {
        if ($type === 'uninstall') {
            $params = ComponentHelper::getParams('com_bpgallery');
            $path = $params->get('images_path', '/images/gallery');
            $this->images_path = JPATH_SITE . $path;

            $this->msg_uninstall_images = Text::_('COM_BPGALLERY_MSG_UNINSTALL_IMAGES_S');
        }
    }

    /**
     * Method to run after an install/update/uninstall method
     *
     * @param String $type Name of actions (update,install,uninstall,discover_install)
     * @param Object $parent Manifest file instance
     */
    function postflight($type, $parent)
    {

    }
}