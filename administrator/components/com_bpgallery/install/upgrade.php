<?php

/**
 * @author		${author.name} (${author.email})
 * @website		${author.url}
 * @copyright	${copyrights}
 * @license		${license.url} ${license.name}
 */

defined('_JEXEC') or die;

class com_bpgalleryInstallerScript
{
   
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
//		JFactory::getApplication()->enqueueMessage('Running uninstall');
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
     * @param   String   $type   Name of actions (update,install,uninstall,discover_install)
     * @param   Object   $parent Manifest file instance
     */
    function preflight($type, $parent)
    {

    }

    /**
     * Method to run after an install/update/uninstall method
     *
     * @param   String   $type   Name of actions (update,install,uninstall,discover_install)
     * @param   Object   $parent Manifest file instance
     */
    function postflight($type, $parent)
    {
 
    }
}