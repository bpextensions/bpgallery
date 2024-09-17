<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 * @author      ${author.name}
 */

namespace BPExtensions\Component\BPGallery\Administrator\Field\Modal;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ModalSelectField;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;

defined('JPATH_BASE') or die;

/**
 * Supports a modal image picker.
 */
class ImageField extends ModalSelectField
{
    /**
     * The form field type.
     *
     * @var     string
     */
    protected $type = 'Modal_Image';

    /**
     * Method to attach a Form object to the field.
     *
     * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
     * @param   mixed              $value    The form field value to validate.
     * @param   string             $group    The field name group control value.
     *
     * @return  boolean  True on success.
     *
     * @throws Exception
     * @see     FormField::setup()
     */
    public function setup(\SimpleXMLElement $element, $value, $group = null): bool
    {
        // Check if the value consist with id:alias, extract the id only
        if ($value && str_contains($value, ':')) {
            [$id] = explode(':', $value, 2);
            $value = (int)$id;
        }

        $result   = parent::setup($element, $value, $group);

        if (!$result) {
            return $result;
        }

        Factory::getApplication()->getLanguage()->load('com_bpgallery', JPATH_ADMINISTRATOR);

        $languages = LanguageHelper::getContentLanguages([0, 1], false);
        $language = (string)$this->element['language'];

        // Prepare enabled actions
        $this->canDo['propagate'] = ((string)$this->element['propagate'] === 'true') && \count($languages) > 2;

        // Prepare Urls
        $linkImages = (new Uri())->setPath(Uri::base(true) . '/index.php');
        $linkImages->setQuery([
            'option'                => 'com_bpgallery',
            'view'                  => 'images',
            'layout'                => 'modal',
            'tmpl'                  => 'component',
            Session::getFormToken() => 1,
        ]);
        $linkImage = clone $linkImages;
        $linkImage->setVar('view', 'image');
        $linkCheckin = (new Uri())->setPath(Uri::base(true) . '/index.php');
        $linkCheckin->setQuery([
            'option'                => 'com_bpgallery',
            'task'                  => 'images.checkin',
            'format'                => 'json',
            Session::getFormToken() => 1,
        ]);

        if ($language) {
            $linkImages->setVar('forcedLanguage', $language);
            $linkImage->setVar('forcedLanguage', $language);

            $modalTitle = Text::_('COM_BPGALLERY_SELECT_AN_IMAGE') . ' &#8212; ' . $this->getTitle();

            $this->dataAttributes['data-language'] = $language;
        } else {
            $modalTitle = Text::_('COM_BPGALLERY_SELECT_AN_IMAGE');
        }

        $urlSelect = $linkImages;
        $urlEdit   = clone $linkImage;
        $urlEdit->setVar('task', 'image.edit');
        $urlNew = clone $linkImage;
        $urlNew->setVar('task', 'image.add');

        $this->urls['select']  = (string)$urlSelect;
        $this->urls['new']     = (string)$urlNew;
        $this->urls['edit']    = (string)$urlEdit;
        $this->urls['checkin'] = (string)$linkCheckin;

        // Prepare titles
        $this->modalTitles['select'] = $modalTitle;
        $this->modalTitles['new']    = Text::_('COM_BPGALLERY_NEW_IMAGE');
        $this->modalTitles['edit']   = Text::_('COM_BPGALLERY_EDIT_IMAGE');

        $this->hint = $this->hint ?: Text::_('COM_BPGALLERY_SELECT_AN_IMAGE');

        return $result;
    }

    /**
     * Method to retrieve the title of selected item.
     *
     * @return string
     * @throws Exception
     */
    protected function getValueTitle(): string
    {
        $value = (int)$this->value ?: '';
        $title = '';

        if ($value) {
            try {
                $db    = $this->getDatabase();
                $query = $db->getQuery(true)
                    ->select($db->qn('title'))
                    ->from($db->qn('#__bpgallery_images'))
                    ->where($db->qn('id') . ' = :value')
                    ->bind(':value', $value, ParameterType::INTEGER);
                $db->setQuery($query);

                $title = $db->loadResult();
            } catch (\Throwable $e) {
                Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            }
        }

        return $title ?: $value;
    }

    /**
     * Method to get the data to be passed to the layout for rendering.
     *
     * @return  array
     */
    protected function getLayoutData(): array
    {
        $data             = parent::getLayoutData();
        $data['language'] = (string)$this->element['language'];

        return $data;
    }

    /**
     * Get the renderer
     *
     * @param   string  $layoutId  Id to load
     *
     * @return  FileLayout
     */
    protected function getRenderer($layoutId = 'default'): FileLayout
    {
        $layout = parent::getRenderer($layoutId);
        $layout->setComponent('com_bpgallery');
        $layout->setClient(1);

        return $layout;
    }
}
