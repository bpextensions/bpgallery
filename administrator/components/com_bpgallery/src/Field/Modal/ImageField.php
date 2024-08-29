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

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\WebAsset\WebAssetManagerInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;

/**
 * Supports a modal image picker.
 */
class ImageField extends FormField
{
    /**
     * The form field type.
     *
     * @var     string
     */
    protected $type = 'Modal_Image';

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     */
    protected function getInput(): string
    {
        $allowNew       = ((string)$this->element['new'] === 'true');
        $allowEdit      = ((string)$this->element['edit'] === 'true');
        $allowClear     = ((string)$this->element['clear'] !== 'false');
        $allowSelect    = ((string)$this->element['select'] !== 'false');
        $allowPropagate = ((string)$this->element['propagate'] === 'true');

        $languages = LanguageHelper::getContentLanguages([0, 1], false);
        $app       = Factory::getApplication();
        $container = Factory::getContainer();

        // Load language
        $app->getLanguage()->load('com_bpgallery', JPATH_ADMINISTRATOR);

        // The active image id field.
        $value = (int)$this->value > 0 ? (int)$this->value : '';

        // Create the modal id.
        $modalId = 'Image_' . $this->id;
        /**
         * @var WebAssetManagerInterface $wa
         */
        $wa = $container->get(WebAssetManagerInterface::class);

        // Add the modal field script to the document head.
        $wa->useScript('field.modal-fields');

        // Script to proxy the select modal function to the modal-fields.js file.
        if ($allowSelect) {
            static $scriptSelect = null;

            if (is_null($scriptSelect)) {
                $scriptSelect = [];
            }

            if (!isset($scriptSelect[$this->id])) {
                $wa->addInlineScript(
                    "
                window.jSelectImage_" . $this->id . " = function (id, title, catid, object, url, language) {
                    window.processModalSelect('Image', '" . $this->id . "', id, title, catid, object, url, language);
                }",
                    [],
                    ['type' => 'module']
                );

                Text::script('JGLOBAL_ASSOCIATIONS_PROPAGATE_FAILED');

                $scriptSelect[$this->id] = true;
            }
        }

        // Setup variables for display.
        $linkImages = 'index.php?option=com_bpgallery&amp;view=images&amp;layout=modal&amp;tmpl=component&amp;' . Session::getFormToken() . '=1';
        $linkImage  = 'index.php?option=com_bpgallery&amp;view=image&amp;layout=modal&amp;tmpl=component&amp;' . Session::getFormToken() . '=1';
        $modalTitle = Text::_('COM_BPGALLERY_CHANGE_IMAGE');

        if (isset($this->element['language'])) {
            $linkImages .= '&amp;forcedLanguage=' . $this->element['language'];
            $linkImage  .= '&amp;forcedLanguage=' . $this->element['language'];
            $modalTitle = Text::_('COM_BPGALLERY_SELECT_AN_IMAGE') . ' &#8212; ' . $this->element['label'];
        } else {
            $modalTitle = Text::_('COM_BPGALLERY_SELECT_AN_IMAGE');
        }

        $urlSelect = $linkImages . '&amp;function=jSelectImage_' . $this->id;
        $urlEdit   = $linkImage . '&amp;task=image.edit&amp;id=\' + document.getElementById("' . $this->id . '_id").value + \'';
        $urlNew    = $linkImage . '&amp;task=image.add';

        if ($value) {
            /**
             * @var DatabaseDriver $db
             */
            $db    = $container->get(DatabaseDriver::class);
            $query = $db->getQuery(true)
                ->select($db->qn('title'))
                ->from($db->qn('#__bpgallery_images'))
                ->where($db->qn('id') . ' = :val')
                ->bind(':val', $value, ParameterType::INTEGER);
            $db->setQuery($query);

            try {
                $title = $db->loadResult();
            } catch (RuntimeException $e) {
                $app->enqueueMessage($e->getMessage(), 'error');
            }
        }

        $title = empty($title) ? Text::_('COM_BPGALLERY_SELECT_AN_IMAGE') : htmlspecialchars($title, ENT_QUOTES,
            'UTF-8');

        // The current contact display field.
        $html = '';

        if ($allowSelect || $allowNew || $allowEdit || $allowClear) {
            $html .= '<span class="input-group">';
        }

        $html .= '<input class="form-control" id="' . $this->id . '_name" type="text" value="' . $title . '" readonly size="35">';

        // Select article button
        if ($allowSelect) {
            $html .= '<button'
                . ' class="btn btn-primary' . ($value ? ' hidden' : '') . '"'
                . ' id="' . $this->id . '_select"'
                . ' data-bs-toggle="modal"'
                . ' type="button"'
                . ' data-bs-target="#ModalSelect' . $modalId . '">'
                . '<span class="icon-file" aria-hidden="true"></span> ' . Text::_('JSELECT')
                . '</button>';
        }

        // New article button
        if ($allowNew) {
            $html .= '<button'
                . ' class="btn btn-secondary' . ($value ? ' hidden' : '') . '"'
                . ' id="' . $this->id . '_new"'
                . ' data-bs-toggle="modal"'
                . ' type="button"'
                . ' data-bs-target="#ModalNew' . $modalId . '">'
                . '<span class="icon-plus" aria-hidden="true"></span> ' . Text::_('JACTION_CREATE')
                . '</button>';
        }

        // Edit article button
        if ($allowEdit) {
            $html .= '<button'
                . ' class="btn btn-primary' . ($value ? '' : ' hidden') . '"'
                . ' id="' . $this->id . '_edit"'
                . ' data-bs-toggle="modal"'
                . ' type="button"'
                . ' data-bs-target="#ModalEdit' . $modalId . '">'
                . '<span class="icon-pen-square" aria-hidden="true"></span> ' . Text::_('JACTION_EDIT')
                . '</button>';
        }

        // Clear article button
        if ($allowClear) {
            $html .= '<button'
                . ' class="btn btn-secondary' . ($value ? '' : ' hidden') . '"'
                . ' id="' . $this->id . '_clear"'
                . ' type="button"'
                . ' onclick="window.processModalParent(\'' . $this->id . '\'); return false;">'
                . '<span class="icon-times" aria-hidden="true"></span> ' . Text::_('JCLEAR')
                . '</button>';
        }

        // Propagate article button
        if ($allowPropagate && count($languages) > 2) {
            // Strip off language tag at the end
            $tagLength            = (int)strlen($this->element['language']);
            $callbackFunctionStem = substr("jSelectImage_" . $this->id, 0, -$tagLength);

            $html .= '<button'
                . ' class="btn btn-primary' . ($value ? '' : ' hidden') . '"'
                . ' type="button"'
                . ' id="' . $this->id . '_propagate"'
                . ' title="' . Text::_('JGLOBAL_ASSOCIATIONS_PROPAGATE_TIP') . '"'
                . ' onclick="Joomla.propagateAssociation(\'' . $this->id . '\', \'' . $callbackFunctionStem . '\');">'
                . '<span class="icon-sync" aria-hidden="true"></span> ' . Text::_('JGLOBAL_ASSOCIATIONS_PROPAGATE_BUTTON')
                . '</button>';
        }

        if ($allowSelect || $allowNew || $allowEdit || $allowClear) {
            $html .= '</span>';
        }

        // Select article modal
        if ($allowSelect) {
            $html .= HTMLHelper::_(
                'bootstrap.renderModal',
                'ModalSelect' . $modalId,
                [
                    'title'      => $modalTitle,
                    'url'        => $urlSelect,
                    'height'     => '400px',
                    'width'      => '800px',
                    'bodyHeight' => 70,
                    'modalWidth' => 80,
                    'footer'     => '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'
                        . Text::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>',
                ]
            );
        }

        // New article modal
        if ($allowNew) {
            $html .= HTMLHelper::_(
                'bootstrap.renderModal',
                'ModalNew' . $modalId,
                [
                    'title'       => Text::_('COM_BPGALLERY_NEW_IMAGE'),
                    'backdrop'    => 'static',
                    'keyboard'    => false,
                    'closeButton' => false,
                    'url'         => $urlNew,
                    'height'      => '400px',
                    'width'       => '800px',
                    'bodyHeight'  => 70,
                    'modalWidth'  => 80,
                    'footer'      => '<button type="button" class="btn btn-secondary"'
                        . ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'add\', \'article\', \'cancel\', \'item-form\'); return false;">'
                        . Text::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>'
                        . '<button type="button" class="btn btn-primary"'
                        . ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'add\', \'article\', \'save\', \'item-form\'); return false;">'
                        . Text::_('JSAVE') . '</button>'
                        . '<button type="button" class="btn btn-success"'
                        . ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'add\', \'article\', \'apply\', \'item-form\'); return false;">'
                        . Text::_('JAPPLY') . '</button>',
                ]
            );
        }

        // Edit article modal
        if ($allowEdit) {
            $html .= HTMLHelper::_(
                'bootstrap.renderModal',
                'ModalEdit' . $modalId,
                [
                    'title'       => Text::_('COM_BPGALLERY_EDIT_IMAGE'),
                    'backdrop'    => 'static',
                    'keyboard'    => false,
                    'closeButton' => false,
                    'url'         => $urlEdit,
                    'height'      => '400px',
                    'width'       => '800px',
                    'bodyHeight'  => 70,
                    'modalWidth'  => 80,
                    'footer'      => '<button type="button" class="btn btn-secondary"'
                        . ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'edit\', \'article\', \'cancel\', \'item-form\'); return false;">'
                        . Text::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>'
                        . '<button type="button" class="btn btn-primary"'
                        . ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'edit\', \'article\', \'save\', \'item-form\'); return false;">'
                        . Text::_('JSAVE') . '</button>'
                        . '<button type="button" class="btn btn-success"'
                        . ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'edit\', \'article\', \'apply\', \'item-form\'); return false;">'
                        . Text::_('JAPPLY') . '</button>',
                ]
            );
        }

        // Note: class='required' for client side validation.
        $class = $this->required ? ' class="required modal-value"' : '';

        $html .= '<input type="hidden" id="' . $this->id . '_id" ' . $class . ' data-required="' . (int)$this->required . '" name="' . $this->name
            . '" data-text="' . htmlspecialchars(Text::_('COM_BPGALLERY_SELECT_AN_IMAGE'), ENT_COMPAT,
                'UTF-8') . '" value="' . $value . '">';

        return $html;
    }

    /**
     * Method to get the field label markup.
     *
     * @return  string  The field label markup.
     */
    protected function getLabel(): string
    {
        return str_replace($this->id, $this->id . '_name', parent::getLabel());
    }
}
