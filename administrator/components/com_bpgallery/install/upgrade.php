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
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
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
     *
     * @param InstallerAdapter $parent Manifest file instance
     */
    function install(InstallerAdapter $parent)
    {
//        Factory::getApplication()->enqueueMessage('Post install');
        $parent->getParent()->setRedirectURL('index.php?option=com_bpgallery');
    }

    /**
     * Method to uninstall the component
     *
     * @param InstallerAdapter $parent Manifest file instance
     *
     * @throws Exception
     */
    function uninstall(InstallerAdapter $parent)
    {
//        Factory::getApplication()->enqueueMessage('Post uninstall');

        $params = ComponentHelper::getParams('com_bpgallery');
        $path = JPATH_SITE . $params->get('images_path', '/images/gallery');

        // Remove images directory
        if (is_dir($path)) {
            $count = $this->rmdir($path);
            Factory::getApplication()->enqueueMessage(sprintf('Path: %s, count: %s', $path, $count));

            // Inform user about removed images
            if ($count > 0) {
                Factory::getApplication()->enqueueMessage(Text::sprintf('COM_BPGALLERY_MSG_UNINSTALL_IMAGES_S', $count));
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
        $count = 1;
        foreach (new DirectoryIterator($path) as $f) {
            if ($f->isDot()) continue;
            if ($f->isFile()) {
                unlink($f->getPathname());
                $count++;
            } else if ($f->isDir()) {
                $count += $this->rmdir($f->getPathname());
            }
        }

        rmdir($path);

        return $count;
    }

    /**
     * Method to update the component
     *
     * @param InstallerAdapter $parent Manifest file instance
     */
    function update(InstallerAdapter $parent)
    {
//        Factory::getApplication()->enqueueMessage('Update');
    }

    /**
     * Method to run before an install/update/uninstall method
     *
     * @param String $type Name of actions (update,install,uninstall,discover_install)
     * @param InstallerAdapter $parent Manifest file instance
     *
     * @throws Exception
     */
    function preflight($type, InstallerAdapter $parent)
    {
//        Factory::getApplication()->enqueueMessage('Preflight: '.$type);
    }

    /**
     * Method to run after an install/update/uninstall method
     *
     * @param String $type Name of actions (update,install,uninstall,discover_install)
     * @param InstallerAdapter $parent Manifest file instance
     */
    function postflight($type, InstallerAdapter $parent)
    {
//        Factory::getApplication()->enqueueMessage('Postflight: '.$type);
    }
}