


(function ($) {

    $.fn.BPGalleryUpload = function (settings) {

        // Holds upload settings
        this.settings = $.extend({
            'text_intro':'Drag &amp; drop files on this box or select them using <strong>Browse</strong> button below.',
            'text_browse':'Browse'
        }, settings);

        // Upload files list (queue)
        this.queue = [];

        /**
         * Initialize upload function
         */
        this.init = function () {

            // Bind files drag & drop actions
            this.setupDragAndDrop();

            // Bind browse button action
            this.setupBrowseButtonAction();
            
            // Bind upload button action
            this.setupUploadButtonAction();
        };

        /**
         * No more files in the queue, so draw a proper message
         */
        this.drawDropContainerIntro = function () {
            
            // Create contents
            var $contents = $(
                '<i class="icon-upload"></i><p>'+this.settings.text_intro+'</p>'+
                '<button class="btn" id="bpgallery_upload_field_button"><i class="icon-search"></i> '+this.settings.text_browse+'</button>'
            );
    
            
            // Get drop container element
            var $container = $('#bpgallery_upload_container');
            
            // Draw a drag&drop message
            $container.text('').append($contents);
            
            // Append browser button click action
            $container.find('#bpgallery_upload_field_button').click(function(){
                $('#bpgallery_upload_field_input').click();
            });
            
            // Remove class that markes files in container
            $container.removeClass('hasFiles');
        };

        // Takes care of creating table with upload queue and progress bar
        this.prepareFilesList = function () {

            // Get upload container
            var $container = $("#bpgallery_upload_container");
            $container.addClass('hasFiles');

            // Create the files table
            var $list = $('#bpgallery_upload_container ul');

            // If queue table doesnt exists
            if (!$list.length) {

                // Clear container and create files table
                $container.text('');
                $list = $('<ul class="images"></ul>');
                $container.append($list);
            }

            return $list;
        };

        /**
         * Add new files to the list
         * 
         * @param   Array   files   A list of File objects to add into table
         * @returns Null
         */
        this.addFiles = function (files) {
            
            // Enable upload button
            $('#bpgallery_upload_form .modal-footer .btn.btn-primary').removeAttr('disabled');
            
            // Add files to upload queue
            this.queue.push.apply(this.queue, Array.prototype.slice.call(files));
            
            // Update files counter
            $('#uploadFormLabel small').text('('+this.queue.length+')');

            // Make sure we have the queue table
            var $list = this.prepareFilesList();

            // Create and append row in queue table for each file
            for (var i = 0, file = files[0]; i < files.length; i++, file = files[i]) {

                // Create a row
                var $row = $('<li class="thumbnail pull-left">\n\
                    <span class="image"></span>\n\
                    <span class="name">'+file.name+'</span>\n\
                    <a href="#" class="btn-remove"><i class="icon-trash"></i></a>\n\
                </li>');

                // Prepare file remove action
                $row.find('.btn-remove').click($.proxy(this.removeFileAction, this));
                
                // Get preview element
                file.$preview = $row.find('.image');
                
                // Load preview image
                file.$preview.reader = new FileReader();
                $(file.$preview.reader).on('load',$.proxy(function (e) {
                    this.css('background-image', 'url('+e.target.result+')');
                }, file.$preview));
                file.$preview.reader.readAsDataURL(file);

                // Append a row
                $list.append($row);
            }

        };

        /**
         * Remove single file from upload queue and queue table.
         * 
         * @param Event e
         */
        this.removeFileAction = function (e) {

            // Get remove button element
            var $target = $(e.target);
            var $row = $target.closest('li');
            var index = $row.parent().children().index($row);
            
            // Remove file from queue list
            this.queue.splice(index,1);
            
            // Remove file row in queue table
            $row.remove();
            
             // Update files counter
            $('#uploadFormLabel small').text('('+this.queue.length+')');
            
            // If there are no more files in the queue
            if( this.queue.length<1 ) {
                
                // Create intro text on drop container
                this.drawDropContainerIntro();
                
                // Remove images counter
                $('#uploadFormLabel small').text('');
                
                // Disable upload button
                $('#bpgallery_upload_form .modal-footer .btn.btn-primary').attr('disabled','');
            }
        };

        /**
         * Setups a Drag & Drop functionality.
         * 
         * @returns Null
         */
        this.setupDragAndDrop = function () {

            // Get files upload container element
            var $container = $("#bpgallery_upload_container");

            // Handle drop in the box
            $container.on('dragenter', function (e)
            {
                e.stopPropagation();
                e.preventDefault();
                $container.addClass('hover');
            });
            $container.on('dragover', function (e)
            {
                e.stopPropagation();
                e.preventDefault();
            });
            $container.on('dragleave', function (e)
            {
                $container.removeClass('hover');
                $container.removeClass('active');
            });
            $container.on('drop', $.proxy(function (e)
            {
                e.stopPropagation();
                e.preventDefault();

                // Create FileList object
                var files = e.originalEvent.dataTransfer.files;

                // Add selected files to queue table
                this.addFiles(files);

            }, this));

            // handle drop outside of the box
            $(document).on('dragenter', function (e)
            {
                e.stopPropagation();
                e.preventDefault();
            });
            $(document).on('dragover', function (e)
            {
                e.stopPropagation();
                e.preventDefault();
            });
            $(document).on('drop', function (e)
            {
                e.stopPropagation();
                e.preventDefault();
            });
        };

        /**
         * Setups a Browse button functionality.
         * 
         * @returns Null
         */
        this.setupBrowseButtonAction = function () {

            // Browse button action
            $('#bpgallery_upload_field_button').click($.proxy(function () {


                // Fire click event on files browse input
                $('#bpgallery_upload_field_input').click();

            }, this));

            // Files select action
            $('#bpgallery_upload_field_input').change($.proxy(function (e) {

                // Render and add selected files to the queue
                this.addFiles(e.target.files);

            }, this));
        };
        
        /**
         * Binds upload button action.
         */
        this.setupUploadButtonAction = function() {
            $('#bpgallery_upload_form .modal-footer .btn.btn-primary').click($.proxy(this.uploadAction, this));
        };
        
        /**
         * Performs Java Script upload.
         */
        this.uploadAction = function() {
            
            console.log('SHOULD NOT Upload.');
            // If there is anything to upload
            if( this.queue.length>0 ) {
                console.log('Uploading.');
            }
        };

        // Initialize uploader
        this.init();

        return this;
    };

})(jQuery);