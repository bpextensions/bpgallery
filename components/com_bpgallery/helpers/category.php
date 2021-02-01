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

/**
 * BPGallery Component Category Tree
 */
class BPGalleryCategories extends JCategories
{
    /**
     * Class constructor
     *
     * @param   array  $options  Array of options
     */
    public function __construct($options = [])
    {
        $options['table']      = '#__bpgallery_images';
        $options['extension']  = 'com_bpgallery';
        $options['statefield'] = 'state';

        parent::__construct($options);
    }
}
