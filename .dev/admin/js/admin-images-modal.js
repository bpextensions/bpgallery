/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 * @author      ${author.name}
 */
(() => {

    'use strict';

    // Use a JoomlaExpectingPostMessage flag to be able to distinct legacy methods
    if (window.parent.JoomlaExpectingPostMessage) {
        return;
    }

    /**
     * Javascript to insert the link
     * View element calls jSelectImage when an image is clicked
     * jSelectImage creates the link tag, sends it to the editor,
     * and closes the select frame.
     * */
    window.jSelectImage = (id, title, catid, object, link, lang) => {
        // eslint-disable-next-line no-console
        console.warn('Method jSelectImage() is deprecated. Use postMessage() instead.');
        if (!Joomla.getOptions('xtd-images')) {
            return;
        }
        const {
            editor
        } = Joomla.getOptions('xtd-images');
        const tag = `<a href="${link}"${lang !== '' ? ` hreflang="${lang}"` : ''}>${title}</a>`;
        window.parent.Joomla.editors.instances[editor].replaceSelection(tag);
        if (window.parent.Joomla.Modal && window.parent.Joomla.Modal.getCurrent()) {
            window.parent.Joomla.Modal.getCurrent().close();
        }
    };

    document.querySelectorAll('.select-link').forEach(element => {
        // Listen for click event
        element.addEventListener('click', event => {
            event.preventDefault();
            const {
                target
            } = event;
            const functionName = target.getAttribute('data-function');
            if (functionName === 'jSelectImage' && window[functionName]) {
                // Used in xtd_contacts
                window[functionName](target.getAttribute('data-id'), target.getAttribute('data-title'), target.getAttribute('data-cat-id'), null, target.getAttribute('data-uri'), target.getAttribute('data-language'));
            } else if (window.parent[functionName]) {
                // Used in com_menus
                window.parent[functionName](target.getAttribute('data-id'), target.getAttribute('data-title'), target.getAttribute('data-cat-id'), null, target.getAttribute('data-uri'), target.getAttribute('data-language'));
            }
            if (window.parent.Joomla.Modal && window.parent.Joomla.Modal.getCurrent()) {
                window.parent.Joomla.Modal.getCurrent().close();
            }
        });
    });

})();
