jQuery(document).ready(function($) {
    var selectedImages = [];
    var mediaUploader;

    $('.neoalbum-upload-btn').on('click', function(e) {
        e.preventDefault();

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: neoalbum_admin.strings.select_images,
            button: {
                text: neoalbum_admin.strings.use_these_images
            },
            multiple: true
        });

        mediaUploader.on('select', function() {
            selectedImages = mediaUploader.state().get('selection').toJSON();
            renderImagePreview();
        });

        mediaUploader.open();
    });

    function renderImagePreview() {
        var $preview = $('.neoalbum-images-preview');
        $preview.empty();

        selectedImages.forEach(function(image, index) {
            var $item = $('<div class="preview-item"></div>');
            var $img = $('<img src="' + image.sizes.thumbnail.url + '" alt="" />');
            $item.append($img);
            $preview.append($item);
        });
    }

    $('.neoalbum-save-btn').on('click', function() {
        var name = $('#neoalbum-name').val();
        var password = $('#neoalbum-password').val();
        var lockImages = $('#neoalbum-lock-images').is(':checked');

        if (!name) {
            alert('لطفاً نام آلبوم را وارد کنید');
            return;
        }

        if (selectedImages.length === 0) {
            alert('لطفاً حداقل یک تصویر انتخاب کنید');
            return;
        }

        var imageUrls = selectedImages.map(function(img) {
            return img.url;
        });

        $.ajax({
            url: neoalbum_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'neoalbum_save_album',
                nonce: neoalbum_admin.nonce,
                name: name,
                images: imageUrls,
                password: password,
                lock_images: lockImages
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#neoalbum-name').val('');
                    $('#neoalbum-password').val('');
                    $('#neoalbum-lock-images').prop('checked', false);
                    selectedImages = [];
                    $('.neoalbum-images-preview').empty();
                    loadAlbums();
                }
            }
        });
    });

    function loadAlbums() {
        $.ajax({
            url: neoalbum_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'neoalbum_get_albums',
                nonce: neoalbum_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderAlbumsTable(response.data.albums);
                }
            }
        });
    }

    function renderAlbumsTable(albums) {
        var $tbody = $('#neoalbum-albums-tbody');
        $tbody.empty();

        if (albums.length === 0) {
            $tbody.append('<tr><td colspan="4">' + 'هنوز آلبومی وجود ندارد' + '</td></tr>');
            return;
        }

        albums.forEach(function(album) {
            var $row = $('<tr></tr>');
            $row.append('<td>' + album.name + '</td>');
            $row.append('<td>' + album.image_count + '</td>');
            $row.append('<td><code>' + album.shortcode + '</code></td>');
            $row.append('<td><button class="button neoalbum-delete-btn" data-album-id="' + album.id + '">حذف</button></td>');
            $tbody.append($row);
        });
    }

    $(document).on('click', '.neoalbum-delete-btn', function() {
        if (!confirm(neoalbum_admin.strings.delete_confirm)) {
            return;
        }

        var albumId = $(this).data('album-id');
        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: neoalbum_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'neoalbum_delete_album',
                nonce: neoalbum_admin.nonce,
                album_id: albumId
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    loadAlbums();
                }
            }
        });
    });

    loadAlbums();
});
