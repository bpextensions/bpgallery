<?php
/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 * @author      ${author.name}
 */

defined('JPATH_BASE') or die;

JLoader::register('BPGalleryHelper', JPATH_ADMINISTRATOR . '/components/com_bpgallery/helpers/bpgallery.php');

JFormHelper::loadFieldClass('list');

/**
 * Image selection field field.
 *
 * @since  1.0
 */
class JFormFieldBPGalleryImage extends JFormFieldList
{
    /**
     * The form field type.
     *
     * @var    string
     *
     * @since  1.0
     */
    protected $type = 'BPGalleryImage';

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     *
     * @throws Exception
     *
     * @since   1.0
     */
    public function getOptions()
    {
        $options = array_merge(parent::getOptions(), BPGalleryHelper::getImageOptions());

        return $options;
    }
}
