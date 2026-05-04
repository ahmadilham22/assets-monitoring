<script>
    // Helper untuk parse error dari FormRequest:
    // - validation error: {message, errors: {field: [msg, ...]}}
    // - other error: {message}
    function parseAjaxError(xhr, fallback) {
        var message = fallback || 'Terjadi kesalahan.';
        if (xhr && xhr.responseJSON) {
            if (xhr.responseJSON.errors) {
                message = Object.values(xhr.responseJSON.errors)
                    .map(function(arr) { return arr[0]; })
                    .join('\n');
            } else if (xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
        }
        return message;
    }

    $(document).ready(function() {
        $('#categories_id').select2({
            placeholder: 'Pilih...',
            dropdownParent: $('#AddSubCategoryModal'),
        });

        $('#edit_categories_id').select2({
            placeholder: 'Pilih...',
            dropdownParent: $('#EditSubCategoryModal'),
        });

        // Reset form setiap kali modal ditutup supaya state tidak bocor
        $('#AddSubCategoryModal').on('hidden.bs.modal', function() {
            $('#add_subcategoryForm').trigger('reset');
            $('#categories_id').val('').trigger('change');
            $('#add_subcategory').prop('disabled', false).html('Save changes');
        });

        $('#EditSubCategoryModal').on('hidden.bs.modal', function() {
            $('#edit_subcategoryForm').trigger('reset');
            $('#edit_subcategory_id').val('');
            $('#edit_categories_id').val('').trigger('change');
            $('#update_subcategory').prop('disabled', false).html('Save changes');
        });

        // ---- DELETE ----
        $(document).off('click', '#delete_subcategory').on('click', '#delete_subcategory', function(e) {
            e.preventDefault();

            var subcategoryId = $(this).val();
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data yang dihapus tidak dapat dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    type: 'DELETE',
                    data: {
                        id: subcategoryId
                    },
                    url: 'sub-category/delete/' + subcategoryId,
                    dataType: 'json',
                    success: function(res) {
                        $('#myTable').DataTable().ajax.reload(null, false);
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            timer: 2000,
                            icon: 'success',
                            title: 'Success',
                            text: res.message,
                            showConfirmButton: false,
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: parseAjaxError(xhr, 'Gagal menghapus data.'),
                        });
                    }
                });
            });
        });

        // ---- EDIT (load data ke modal) ----
        $(document).off('click', '#edit_subcategory').on('click', '#edit_subcategory', function(e) {
            e.preventDefault();

            var subcategoryId = $(this).val();

            $.ajax({
                type: 'GET',
                url: 'sub-category/edit/' + subcategoryId,
                dataType: 'json',
                success: function(response) {
                    if (!response.success) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Data tidak ditemukan',
                        });
                        return;
                    }

                    $('#edit_subcategory_id').val(response.data.id);
                    $('#edit_kode_sub_kategori').val(response.data.kode_sub_kategori);
                    $('#edit_nama_sub_kategori').val(response.data.nama_sub_kategori);
                    $('#edit_categories_id').val(response.data.categories_id).trigger('change');
                    $('#EditSubCategoryModal').modal('show');
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: parseAjaxError(xhr, 'Gagal memuat data.'),
                    });
                }
            });
        });

        // ---- UPDATE ----
        $(document).off('click', '#update_subcategory').on('click', '#update_subcategory', function(e) {
            e.preventDefault();

            var $btn = $(this);
            if ($btn.prop('disabled')) {
                return;
            }
            $btn.prop('disabled', true).html('Menyimpan...');

            var subcategoryId = $('#edit_subcategory_id').val();
            var data = {
                categories_id: $('#edit_categories_id').val(),
                kode_sub_kategori: $('#edit_kode_sub_kategori').val(),
                nama_sub_kategori: $('#edit_nama_sub_kategori').val(),
            };

            $.ajax({
                type: 'PUT',
                url: 'sub-category/update/' + subcategoryId,
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#EditSubCategoryModal').modal('hide');
                        $('#myTable').DataTable().ajax.reload(null, false);
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            timer: 2000,
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            showConfirmButton: false,
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: parseAjaxError(xhr, 'Gagal memperbarui data.'),
                    });
                },
                complete: function() {
                    $btn.prop('disabled', false).html('Save changes');
                }
            });
        });

        // ---- ADD ----
        $(document).off('click', '#add_subcategory').on('click', '#add_subcategory', function(e) {
            e.preventDefault();

            var $btn = $(this);
            if ($btn.prop('disabled')) {
                return;
            }
            $btn.prop('disabled', true).html('Menyimpan...');

            var data = {
                categories_id: $('#categories_id').val(),
                kode_sub_kategori: $('#kode_sub_kategori').val(),
                nama_sub_kategori: $('#nama_sub_kategori').val(),
            };

            $.ajax({
                type: 'POST',
                url: "{{ route('sub-category.store') }}",
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#AddSubCategoryModal').modal('hide');
                        $('#myTable').DataTable().ajax.reload(null, false);
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            timer: 2000,
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            showConfirmButton: false,
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: parseAjaxError(xhr, 'Gagal menambah data.'),
                    });
                },
                complete: function() {
                    $btn.prop('disabled', false).html('Save changes');
                }
            });
        });
    });
</script>
