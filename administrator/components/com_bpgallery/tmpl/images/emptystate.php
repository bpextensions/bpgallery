<?php

/**
 * @author            ${author.name} (${author.email})
 * @website           ${author.url}
 * @copyright         ${copyrights}
 * @license           ${license.url} ${license.name}
 * @package           ${package}
 * @subpackage        ${subpackage}
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

/** @var \BPExtensions\Component\BPGallery\Administrator\View\Images\HtmlView $this */

$displayData = [
    'textPrefix' => 'COM_BPGAPPERY',
    'formURL'    => 'index.php?option=com_bpgallery&view=images',
    'icon'       => 'icon-image image',
];

$user = $this->getCurrentUser();

if ($user->authorise('core.create', 'com_bpgallery') || count(
        $user->getAuthorisedCategories('com_bpgallery', 'core.create')
    ) > 0) {
    $displayData['createURL'] = 'index.php?option=com_bpgallery&task=image.add';
}

echo LayoutHelper::render('joomla.content.emptystate', $displayData);

echo $this->loadTemplate('upload');