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
use Joomla\CMS\Table\Table;

defined('_JEXEC') or die;

/**
 * BP Gallery installation tasks class.
 */
final class com_bpgalleryInstallerScript
{

    /**
     * Method to install the component
     *
     * @param   InstallerAdapter  $parent  Manifest file instance
     */
    public function install(InstallerAdapter $parent): void
    {
        $parent->getParent()->setRedirectURL('index.php?option=com_bpgallery');
    }

    /**
     * Method to uninstall the component
     *
     * @param InstallerAdapter $parent Manifest file instance
     *
     * @throws Exception
     */
    public function uninstall(InstallerAdapter $parent): void
    {
        $params = ComponentHelper::getParams('com_bpgallery');
        $path   = JPATH_SITE . $params->get('images_path', '/images/gallery');

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
    public function update(InstallerAdapter $parent): void
    {
    }

    /**
     * Method to run before an install/update/uninstall method
     *
     * @param String $type Name of actions (update,install,uninstall,discover_install)
     * @param InstallerAdapter $parent Manifest file instance
     *
     * @throws Exception
     */
    public function preflight($type, InstallerAdapter $parent): void
    {
        if (PHP_VERSION_ID < 70200) {
            throw new RuntimeException(Text::_('COM_BPGALLERY_UNSUPPORTED_PHP_VERION'), 500);
        }
    }

    /**
     * Method to run after an install/update/uninstall method
     *
     * @param   String            $type    Name of actions (update,install,uninstall,discover_install)
     * @param   InstallerAdapter  $parent  Manifest file instance
     *
     * @throws Exception
     */
    public function postflight($type, InstallerAdapter $parent): void
    {

        // Post installation tasks
        if (in_array($type, ['install', 'discover_install'])) {

            $app = Factory::getApplication();

            // Load default component parameters
            $extension = Table::getInstance('Extension');
            if ($extension->load(['element' => 'com_bpgallery'])) {
                $buff = file_get_contents(__DIR__ . '/defaults.json');
                if (!$extension->bind(['params' => $buff]) || !$extension->check() || !$extension->store()) {
                    $app->enqueueMessage('Failed to store default component parameters.', 'error');
                }
            }

            // Create default category
            JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/models', 'CategoriesModel');
            JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/tables');
            $model = JModelLegacy::getInstance('Category', 'CategoriesModel');
            $data  = [
                'title'       => Text::_('COM_BPGALLERY_UNCATEGORISED'),
                'extension'   => 'com_bpgallery',
                'published'   => 1,
                'language'    => '*',
                'description' => '',
                'params'      => '{}'
            ];
            if (!$model->save($data)) {
                $app->enqueueMessage('Failed to create default gallery category.', 'error');
            }
        }

    }
}