/**
 * @author       ${author.name} (${author.email})
 * @website      ${author.url}
 * @copyright    ${copyrights}
 * @license      ${license.url} ${license.name}
 */

import jQuery from 'jquery';
import Joomla from 'joomla';

((Joomla, $) => {
    'use strict';

    Joomla.BPGalleryUpload = function (options) {

        console.log('BPGalleryUpload setup');

        const $container = $('joomla-dialog .bpgallery_upload_form');

        // Holds upload settings
        this.settings = $.extend({
            upload_url: 'index.php?option=com_bpgallery&task=image.upload&format=json',
        }, options);

        // Upload files list (queue)
        this.queue = [];

        /**
         * Initialize upload function
         */
        this.init = () => {

            // Bind files drag & drop actions
            this.setupDragAndDrop();

            // Bind browse button action
            this.setupBrowseButtonAction();

            // Bind upload button action
            this.setupUploadButtonAction();

            // Category selection action
            this.bindCategorySelectAction();

            // Bind category modals events (preventing closing both upload and category modal when one is closed)
            this.bindCategoryModalsEvents();

            $container.addClass('loaded');
        };

        /**
         * No more files in the queue, so draw a proper message
         */
        this.drawDropContainerIntro = () => {

            // Create contents
            let $contents = $(
                '<i class="icon-upload fa-4x text-muted mt-4 mt-xl-5 mb-3" aria-hidden="true"></i>' +
                '<p class="text-center mb-3 px-xl-5">' + Joomla.Text._('COM_BPGALLERY_IMAGES_UPLOAD_TIP') + '</p>' +
                '<p class="mb-4 mb-xl-5">' +
                '<button class="btn btn-success" id="bpgallery_upload_field_button"><i class="icon-search" aria-hidden="true"></i> ' + Joomla.Text._('COM_BPGALLERY_IMAGES_BROWSE_BUTTON') + '</button>' +
                '</p>'
            );

            // Get drop container element
            let $container = $('#bpgallery_upload_container');

            // Draw a drag&drop message
            $container.text('').append($contents);

            // Append browser button click action
            $container.find('#bpgallery_upload_field_button').click(function () {
                $('#bpgallery_upload_field_input').click();
            });

            // Remove class that markes files in container
            $container.removeClass('hasFiles');
        };

        // Takes care of creating table with upload queue and progress bar
        this.prepareFilesList = () => {

            // Get upload container
            let $container = $("#bpgallery_upload_container");
            $container.addClass('hasFiles');

            // Create the files table
            let $list = $('#bpgallery_upload_container ul');

            // If queue table doesnt exists
            if (!$list.length) {

                // Clear container and create files table
                $container.text('');
                $list = $('<ul class="queue row g-2 p-0 mb-0 w-100"></ul>');
                $container.append($list);
            }

            return $list;
        };

        /**
         * Check if selected file exists in files queue.
         *
         * @var {File}    file    File object to search for.
         * @returns {Boolean}
         */
        this.fileExistsInQueue = (file) => {

            // Check queue list for a selected file
            for (let i = 0, ic = this.queue.length; i < ic; i++) {

                // Check if file exists
                if (
                    this.queue[i].name === file.name &&
                    this.queue[i].size === file.size &&
                    this.queue[i].type === file.type &&
                    this.queue[i].lastModified === file.lastModified
                ) {
                    return i;
                }
            }

            return false;
        };

        /**
         * Add new files to the list
         *
         * @var   Array   files   A list of File objects to add into table
         *
         * @returns void
         */
        this.addFiles = (files) => {

            // Make sure we have the queue table
            // let $list = this.prepareFilesList();

            files = Array.from(files);

            let newFiles = [];

            // Check each file for adding
            for (let i = 0, ic = files.length, file = files[0]; i < ic; i++, file = files[i]) {

                // Check for this file position in queue
                let position = this.fileExistsInQueue(file);

                // If file exists in queue
                if (position !== false) {

                    // Remove this file from files to add and update index+counter
                    files.splice(position, 1);
                    i--;
                    ic--;

                    // File is new in the queue
                } else {

                    // Add file to the queue array
                    this.queue.push(file);
                    newFiles.push(file);
                }
            }

            // There are files in the queue
            if (newFiles.length > 0) {

                console.log('Render queue and canUpload()');

                // Render added images
                this.renderQueue(newFiles);

                // We have files so check if we can enable upload button
                this.canUpload();

                // Update files counter
                $('.joomla-dialog-header h3').text(Joomla.Text._('COM_BPGALLERY_IMAGES_UPLOAD_HEADER') + ' (' + this.queue.length + ')');
            }
        };

        /**
         * Render current list of files
         *
         * @param files List of files to add. If none provided we'll use the full queue.
         *
         * @returns Null
         */
        this.renderQueue = (files = []) => {

            // Make sure we have the queue table
            let $list = this.prepareFilesList();

            // Find the "Add" button in the queue
            let $uploadButton = $list.find('.upload-button');

            // Render each image
            for (let idx in files) {

                let $image = this.createImageElement(files[idx]);

                // Append the image
                if ($uploadButton.length) {
                    $image.insertBefore($uploadButton);
                } else {
                    $list.append($image);
                }
            }

            // Add the upload button
            if (!$uploadButton.length) {
                $list.append(this.createUploadButton());
            }
        };

        /**
         * Create "Add" button that will be added at the end of the queue.
         *
         * @returns {jQuery|HTMLElement}
         */
        this.createUploadButton = () => {

            // Create button
            let $btn = $('<li class="queue-entry upload-button">\n\
                    <button class="btn-upload btn btn-outline-primary">\n\
                        <span class="wrapper">\n\
                            <i class="icon-image fw-2x" aria-hidden="true"></i>\n\
                            <small>' + Joomla.Text._('COM_BPGALLERY_IMAGES_BTN_ADD_LABEL') + '</small>\n\
                        </span>\n\
                    </button>\n\
                </li>');

            // Bind browse event
            $btn.click($.proxy(this.showBrowseWindow, this));

            return $btn;
        };

        /**
         * Create queue image element.
         *
         * @param file
         *
         * @returns {jQuery|HTMLElement}
         */
        this.createImageElement = (file) => {

            // Create a row
            let $row =
                $('<li class="queue-entry col-6 col-md-4 col-lg-2">\n\
                    <span>\n\
                        <span class="image disabled"></span>\n\
                        <span class="name"><span>' + file.name + '</span></span>\n\
                        <span class="progress"><span></span></span>\n\
                        <button class="btn-remove btn btn-danger btn-sm"><i class="icon-trash" aria-hidden="true"></i><spall class="visually-hidden">' + Joomla.Text._('JREMOVE') + '</spall></button>\n\
                    </span>\n\
                </li>');

            // Prepare file remove action
            $row.find('.btn-remove').click($.proxy(this.removeFileAction, this));

            // Get preview element
            file.$preview = $row.find('.image');

            // Load preview image
            file.$preview.reader = new FileReader();
            $(file.$preview.reader).on('load', $.proxy(function (e) {
                this.css('background-image', 'url(' + e.target.result + ')');
                this.removeClass('disabled');
            }, file.$preview));
            file.$preview.reader.readAsDataURL(file);

            return $row;
        };

        /**
         * Remove single file from upload queue and queue table.
         *
         * @var Event e
         */
        this.removeFileAction = (e) => {

            // Get remove button element
            let $target = $(e.target);
            let $row = $target.closest('li');
            let index = $row.parent().children().index($row);

            // Remove file from queue list
            this.queue.splice(index, 1);

            // Remove file row in queue table
            $row.remove();

            // Update files counter
            $('.joomla-dialog-header h3').text(Joomla.Text._('COM_BPGALLERY_IMAGES_UPLOAD_HEADER') + ' (' + this.queue.length + ')');

            // If there are no more files in the queue
            if (this.queue.length < 1) {

                // Create intro text on drop container
                this.drawDropContainerIntro();

                // Remove images counter
                $('#uploadFormLabel small').text('');

                // We have no files so force script to check upload possibility
                this.canUpload();
            }
        };

        /**
         * Setups a Drag & Drop functionality.
         *
         * @returns Null
         */
        this.setupDragAndDrop = () => {

            // Get files upload container element
            let $container = $("#bpgallery_upload_container");

            // Handle drop in the box
            $container.on('dragenter', function (e) {
                e.stopPropagation();
                e.preventDefault();
                $container.addClass('hover');
            });
            $container.on('dragover', function (e) {
                e.stopPropagation();
                e.preventDefault();
            });
            $container.on('dragleave', function (e) {
                $container.removeClass('hover');
                $container.removeClass('active');
            });
            $container.on('drop', $.proxy(function (e) {
                e.stopPropagation();
                e.preventDefault();

                // Create FileList object
                let files = e.originalEvent.dataTransfer.files;

                // Add selected files to queue table
                this.addFiles(files);

            }, this));

            // handle drop outside of the box
            $(document).on('dragenter', function (e) {
                e.stopPropagation();
                e.preventDefault();
            });
            $(document).on('dragover', function (e) {
                e.stopPropagation();
                e.preventDefault();
            });
            $(document).on('drop', function (e) {
                e.stopPropagation();
                e.preventDefault();
            });
        };

        /**
         * Setups a Browse button functionality.
         *
         * @returns Null
         */
        this.setupBrowseButtonAction = () => {

            // Browse button action
            $('#bpgallery_upload_field_button').click($.proxy(function () {

                // Fire click event on files browse input
                // $('#bpgallery_upload_field_input').click();
                this.showBrowseWindow();

            }, this));

            // Files select action
            $('#bpgallery_upload_field_input').change($.proxy(function (e) {

                // Render and add selected files to the queue
                this.addFiles(e.target.files);

            }, this));
        };

        /**
         * Show files browse window.
         */
        this.showBrowseWindow = () => {

            // Fire click event on files browse input
            $('#bpgallery_upload_field_input').click();
        }

        /**
         * Binds upload button action.
         */
        this.setupUploadButtonAction = function () {
            $container.find('.process-upload').click($.proxy(this.uploadAction, this));
        };

        /**
         * Check if user can perform upload action (we have files and category selected).
         *
         * @returns {boolean}
         */
        this.canUpload = () => {

            let category_id = $('#category_id').val();
            let $btn = $container.find('.btn-toolbar .process-upload');
            let $warning = $('#bpgallery_upload_missing_params_warning');

            // If user did not selected files or category
            if (this.queue.length < 1 || (category_id !== '' && parseInt(category_id) < 1)) {

                // Disable upload button
                $btn.attr('disabled', '');
                $warning.show();

                return false;

                // If files and category are selected
            } else {

                // Enable upload button
                $btn.removeAttr('disabled');
                $warning.hide();

                return true;

            }
        };

        /**
         * Performs JavaScript upload.
         */
        this.uploadAction = () => {

            // If there is anything to upload
            if (this.canUpload()) {


                // First file in queue
                this.currentUploadFile = this.queue[0];

                // Selected category ID
                let category_id = $('#category_id').val();

                // Entry thumbnail
                this.currentUploadThumbnail = $('#bpgallery_upload_container .queue .queue-entry').first();

                // Entry progress bar
                this.currentUploadProgressBar = this.currentUploadThumbnail.find('.progress span');
                this.currentUploadProgressBar.parent().addClass('uploading');

                let fd = new FormData();
                fd.append('image', this.currentUploadFile);

                console.log('Performing upload of ', this.currentUploadFile.name);

                $.ajax({
                    // Your server script to process the upload
                    url: this.settings.upload_url + '&category_id=' + category_id,
                    type: 'POST',

                    // Form data
                    data: fd,

                    // Tell jQuery not to process data or worry about content-type
                    // You *must* include these options!
                    cache: false,
                    contentType: false,
                    processData: false,

                    // Custom XMLHttpRequest
                    xhr: $.proxy(function () {
                        let request = $.ajaxSettings.xhr();
                        if (request.upload) {

                            // For handling the progress of the upload
                            request.upload.addEventListener('progress', $.proxy(function (e) {

                                if (e.lengthComputable) {
                                    this.currentUploadProgressBar.css({
                                        'width': parseInt((e.loaded * 100) / e.total) + '%'
                                    });
                                }

                            }, this), false);
                        }
                        return request;
                    }, this),

                    success: $.proxy(function () {

                        console.log('Done.');

                        // Remove element
                        let e = {
                            target: this.currentUploadThumbnail[0]
                        };

                        this.removeFileAction(e);

                        // No more images, reload the window
                        if (this.queue.length === 0) {
                            window.location.href = window.location.href;

                            // There are still images, upload next
                        } else {
                            this.uploadAction();
                        }


                    }, this),

                    fail: $.proxy(function (r) {

                        console.log('Failed.', r);

                        // Remove element
                        let e = {
                            target: this.currentUploadThumbnail[0]
                        };

                        this.uploadAction();

                    }, this),
                });

            }
        };

        /**
         * Bind category select actions
         */
        this.bindCategorySelectAction = () => {

            // Get input element
            let $element = $('#category_id');

            // Dirty workaround for lack of onChange event on disabled/readonly/hidden fields
            let val_old, val_new = $element.val();

            // Check category id every 0.1sec
            setInterval(function () {
                // Get current field value
                val_new = $element.val();

                // if value changed
                if (val_old !== val_new) {

                    // Save new value for later
                    val_old = val_new;

                    // Fix field value
                    if (val_new === '') $element.val('0');

                    // Trigger change
                    $element.trigger('change');
                }
            }, 100);
            // End for workaround

            // Enable upload button if there is a category selected
            $element.change($.proxy(this.canUpload, this));
        };

        /**
         * Bind category modals events (to prevent closing both category modal and upload modal).
         */
        this.bindCategoryModalsEvents = () => {

            let $modalSelect = $('#ModalSelectCategory_category');
            let $modalNew = $('#ModalNewCategory_category');

            $modalSelect.find('button[data-dismiss="modal"]').click(function (e) {
                e.stopPropagation();
                $modalSelect.modal('hide');
            });

            $modalNew.find('button[data-dismiss="modal"]').click(function (e) {
                e.stopPropagation();
                $modalNew.modal('hide');
            });
        }

        // Initialize uploader
        this.init();

        return this;
    };


    $(() => {

        // Bind New button click event
        $('.com_bpgallery .emptystate-btnadd').click((e) => {
            e.preventDefault();
            e.stopPropagation();
            $('#toolbar-upload button').click();
        });

        // Setup uploader on dialog open event
        $(window).on('joomla-dialog:open', function (e, v) {
            if ($(e.target).find('.bpgallery_upload_form').length) {
                Joomla.BPGalleryUpload();
            }
        });

    });


})(Joomla, jQuery)