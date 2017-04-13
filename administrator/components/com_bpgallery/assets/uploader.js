


(function($){
    
    $.fn.BPGalleryUpload = function(){
        
        this.setupDragAndDrop = function(){
            
            // Handle drop in the box
            var $container = $("#bpgallery_upload_container");
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
            $container.on('drop', function (e) 
            {

                 $container.addClass('hasFiles');
                 e.preventDefault();
                 var files = e.originalEvent.dataTransfer.files;
                 
                 // Display the list of files
                 $table = $('#bpgallery_upload_container table');
                 if( !$table ) {
                     $container.html('');
                     $table = $('<table class="table table-stripped"></table>');
                     $container.append($table);
                 }
                 
//                console.log(files);
                 files.forEach(function(file){
//                    console.log(file);
                     
                 });
                 file
                 for(var i; i<files.length;i++) {
                     $row = $('<tr><td>'+file.name+'</td><td>'+Math.round(file.size/1024)+' MB</td><td></td></tr>');
                 }

                 //We need to send dropped files to Server
//                 handleFileUpload(files,$container);
            });
            
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
//              $container.addClass('hover');
            });
            $(document).on('drop', function (e) 
            {
                e.stopPropagation();
                e.preventDefault();
            });
        };
        
        this.setupDragAndDrop();
        
        console.log('Uploader setup done.')
        
        return this;
    };
    
})(jQuery);