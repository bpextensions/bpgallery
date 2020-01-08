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

use Joomla\CMS\Language\LanguageHelper;

/**
 * Supports a modal image picker.
 *
 * @since  1.0
 */
class JFormFieldModal_BPGalleryImage extends JFormField
{
    /**
     * The form field type.
     *
     * @var     string
     * @since   1.0
     */
    protected $type = 'Modal_BPGalleryImage';

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   1.0
     */
    protected function getInput()
    {
        $allowNew = ((string)$this->element['new'] == 'true');
        $allowEdit = ((string)$this->element['edit'] == 'true');
        $allowClear = ((string)$this->element['clear'] != 'false');
        $allowSelect = ((string)$this->element['select'] != 'false');
        $allowPropagate = ((string)$this->element['propagate'] == 'true');

        $languages = LanguageHelper::getContentLanguages(array(0, 1));

        // Load language
        JFactory::getLanguage()->load('com_bpgallery', JPATH_ADMINISTRATOR);

        // The active image id field.
        $value = (int)$this->value > 0 ? (int)$this->value : '';

        // Create the modal id.
        $modalId = 'BPGalleryImage_' . $this->id;

        // Add the modal field script to the document head.
        JHtml::_('jquery.framework');
        JHtml::_('script', 'system/modal-fields.js', array('version' => 'auto', 'relative' => true));

        // Script to proxy the select modal function to the modal-fields.js file.
        if ($allowSelect) {
            static $scriptSelect = null;

            if (is_null($scriptSelect)) {
                $scriptSelect = array();
            }

            if (!isset($scriptSelect[$this->id])) {
                JFactory::getDocument()->addScriptDeclaration("
				function jSelectBPGalleryImage_" . $this->id . "(id, title, object) {
					window.processModalSelect('BPGalleryImage', '" . $this->id . "', id, title, '', object);
				}
				");

                JText::script('JGLOBAL_ASSOCIATIONS_PROPAGATE_FAILED');

                $scriptSelect[$this->id] = true;
            }
        }

        // Setup variables for display.
        $linkImages = 'index.php?option=com_bpgallery&amp;view=images&amp;layout=modal&amp;tmpl=component&amp;' . JSession::getFormToken() . '=1';
        $linkImage = 'index.php?option=com_bpgallery&amp;view=image&amp;layout=modal&amp;tmpl=component&amp;' . JSession::getFormToken() . '=1';
        $modalTitle = JText::_('COM_BPGALLERY_CHANGE_IMAGE');

        if (isset($this->element['language'])) {
            $linkImages .= '&amp;forcedLanguage=' . $this->element['language'];
            $linkImage .= '&amp;forcedLanguage=' . $this->element['language'];
            $modalTitle .= ' &#8212; ' . $this->element['label'];
        }

        $urlSelect = $linkImages . '&amp;function=jSelectBPGalleryImage_' . $this->id;
        $urlEdit = $linkImage . '&amp;task=image.edit&amp;id=\' + document.getElementById("' . $this->id . '_id").value + \'';
        $urlNew = $linkImage . '&amp;task=image.add';

        if ($value) {
            $db = JFactory::getDbo();
            $query = $db->getQuery(true)
                ->select($db->quoteName('title'))
                ->from($db->quoteName('#__bpgallery_images'))
                ->where($db->quoteName('id') . ' = ' . (int)$value);
            $db->setQuery($query);

            try {
                $title = $db->loadResult();
            } catch (RuntimeException $e) {
                JError::raiseWarning(500, $e->getMessage());
            }
        }

        $title = empty($title) ? JText::_('COM_BPGALLERY_SELECT_A_IMAGE') : htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

        // The current contact display field.
        $html = '<span class="input-append">';
        $html .= '<input class="input-medium" id="' . $this->id . '_name" type="text" value="' . $title . '" disabled="disabled" size="35" />';

        // Select contact button
        if ($allowSelect) {
            $html .= '<button'
                . ' type="button"'
                . ' class="btn hasTooltip' . ($value ? ' hidden' : '') . '"'
                . ' id="' . $this->id . '_select"'
                . ' data-toggle="modal"'
                . ' data-target="#ModalSelect' . $modalId . '"'
                . ' title="' . JHtml::tooltipText('COM_BPGALLERY_CHANGE_IMAGE') . '">'
                . '<span class="icon-file" aria-hidden="true"></span> ' . JText::_('JSELECT')
                . '</button>';
        }

        // New contact button
        if ($allowNew) {
            $html .= '<button'
                . ' type="button"'
                . ' class="btn hasTooltip' . ($value ? ' hidden' : '') . '"'
                . ' id="' . $this->id . '_new"'
                . ' data-toggle="modal"'
                . ' data-target="#ModalNew' . $modalId . '"'
                . ' title="' . JHtml::tooltipText('COM_BPGALLERY_NEW_IMAGE') . '">'
                . '<span class="icon-new" aria-hidden="true"></span> ' . JText::_('JACTION_CREATE')
                . '</button>';
        }

        // Edit contact button
        if ($allowEdit) {
            $html .= '<button'
                . ' type="button"'
                . ' class="btn hasTooltip' . ($value ? '' : ' hidden') . '"'
                . ' id="' . $this->id . '_edit"'
                . ' data-toggle="modal"'
                . ' data-target="#ModalEdit' . $modalId . '"'
                . ' title="' . JHtml::tooltipText('COM_BPGALLERY_EDIT_IMAGE') . '">'
                . '<span class="icon-edit" aria-hidden="true"></span> ' . JText::_('JACTION_EDIT')
                . '</button>';
        }

        // Clear contact button
        if ($allowClear) {
            $html .= '<button'
                . ' type="button"'
                . ' class="btn' . ($value ? '' : ' hidden') . '"'
                . ' id="' . $this->id . '_clear"'
                . ' onclick="window.processModalParent(\'' . $this->id . '\'); return false;">'
                . '<span class="icon-remove" aria-hidden="true"></span>' . JText::_('JCLEAR')
                . '</button>';
        }

        // Propagate contact button
        if ($allowPropagate && count($languages) > 2) {
            // Strip off language tag at the end
            $tagLength = (int)strlen($this->element['language']);
            $callbackFunctionStem = substr("jSelectBPGalleryImage_" . $this->id, 0, -$tagLength);

            $html .= '<a'
                . ' class="btn hasTooltip' . ($value ? '' : ' hidden') . '"'
                . ' id="' . $this->id . '_propagate"'
                . ' href="#"'
                . ' title="' . JHtml::tooltipText('JGLOBAL_ASSOCIATIONS_PROPAGATE_TIP') . '"'
                . ' onclick="Joomla.propagateAssociation(\'' . $this->id . '\', \'' . $callbackFunctionStem . '\');">'
                . '<span class="icon-refresh" aria-hidden="true"></span>' . JText::_('JGLOBAL_ASSOCIATIONS_PROPAGATE_BUTTON')
                . '</a>';
        }

        $html .= '</span>';

        // Select contact modal
        if ($allowSelect) {
            $html .= JHtml::_(
                'bootstrap.renderModal',
                'ModalSelect' . $modalId,
                array(
                    'title' => $modalTitle,
                    'url' => $urlSelect,
                    'height' => '400px',
                    'width' => '800px',
                    'bodyHeight' => '70',
                    'modalWidth' => '80',
                    'footer' => '<button type="button" class="btn" data-dismiss="modal">' . JText::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>',
                )
            );
        }

        // New contact modal
        if ($allowNew) {
            $html .= JHtml::_(
                'bootstrap.renderModal',
                'ModalNew' . $modalId,
                array(
                    'title' => JText::_('COM_BPGALLERY_NEW_IMAGE'),
                    'backdrop' => 'static',
                    'keyboard' => false,
                    'closeButton' => false,
                    'url' => $urlNew,
                    'height' => '400px',
                    'width' => '800px',
                    'bodyHeight' => '70',
                    'modalWidth' => '80',
                    'footer' => '<button type="button" class="btn"'
                        . ' onclick="window.processModalEdit(this, \''
                        . $this->id . '\', \'add\', \'image\', \'cancel\', \'image-form\', \'jform_id\', \'jform_title\'); return false;">'
                        . JText::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>'
                        . '<button type="button" class="btn btn-primary"'
                        . ' onclick="window.processModalEdit(this, \''
                        . $this->id . '\', \'add\', \'image\', \'save\', \'image-form\', \'jform_id\', \'jform_title\'); return false;">'
                        . JText::_('JSAVE') . '</button>'
                        . '<button type="button" class="btn btn-success"'
                        . ' onclick="window.processModalEdit(this, \''
                        . $this->id . '\', \'add\', \'image\', \'apply\', \'image-form\', \'jform_id\', \'jform_title\'); return false;">'
                        . JText::_('JAPPLY') . '</button>',
                )
            );
        }

        // Edit contact modal.
        if ($allowEdit) {
            $html .= JHtml::_(
                'bootstrap.renderModal',
                'ModalEdit' . $modalId,
                array(
                    'title' => JText::_('COM_BPGALLERY_EDIT_IMAGE'),
                    'backdrop' => 'static',
                    'keyboard' => false,
                    'closeButton' => false,
                    'url' => $urlEdit,
                    'height' => '400px',
                    'width' => '800px',
                    'bodyHeight' => '70',
                    'modalWidth' => '80',
                    'footer' => '<button type="button" class="btn"'
                        . ' onclick="window.processModalEdit(this, \'' . $this->id
                        . '\', \'edit\', \'image\', \'cancel\', \'image-form\', \'jform_id\', \'jform_title\'); return false;">'
                        . JText::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>'
                        . '<button type="button" class="btn btn-primary"'
                        . ' onclick="window.processModalEdit(this, \''
                        . $this->id . '\', \'edit\', \'image\', \'save\', \'image-form\', \'jform_id\', \'jform_title\'); return false;">'
                        . JText::_('JSAVE') . '</button>'
                        . '<button type="button" class="btn btn-success"'
                        . ' onclick="window.processModalEdit(this, \''
                        . $this->id . '\', \'edit\', \'image\', \'apply\', \'image-form\', \'jform_id\', \'jform_title\'); return false;">'
                        . JText::_('JAPPLY') . '</button>',
                )
            );
        }

        // Note: class='required' for client side validation.
        $class = $this->required ? ' class="required modal-value"' : '';

        $html .= '<input type="hidden" id="' . $this->id . '_id"' . $class . ' data-required="' . (int)$this->required . '" name="' . $this->name
            . '" data-text="' . htmlspecialchars(JText::_('COM_BPGALLERY_SELECT_A_IMAGE', true), ENT_COMPAT, 'UTF-8') . '" value="' . $value . '" />';

        return $html;
    }

    /**
     * Method to get the field label markup.
     *
     * @return  string  The field label markup.
     *
     * @since   1.0
     */
    protected function getLabel()
    {
        return str_replace($this->id, $this->id . '_id', parent::getLabel());
    }
}
