@extends('layouts.admin_template')

@section('title', 'Manajemen Tag')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h3 class="page-title">Manajemen Tag</h3>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Tag</li>
            </ul>
        </div>
        <div class="col-auto">
            <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tagModal" onclick="resetForm()">
                <i class="fas fa-plus"></i> Tambah Tag
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card card-table">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-center table-hover" id="tagTable">
                        <thead class="thead-light">
                            <tr>
                                <th>Nama Tag</th>
                                <th>Warna</th>
                                <th>Preview</th>
                                <th>Dibuat Pada</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Data will be loaded via AJAX or Blade loop --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Tag Modal --}}
<div class="modal fade" id="tagModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Tag Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="tagForm">
                <input type="hidden" id="tag_id">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label>Nama Tag <span class="text-danger">*</span></label>
                        <input type="text" id="tag_name" class="form-control" placeholder="Contoh: Urgent, Refund, Spam" required>
                    </div>
                    <div class="form-group">
                        <label>Warna Tag</label>
                        <div class="d-flex align-items-center">
                            <input type="color" id="tag_color" class="form-control form-control-color me-3" value="#6c757d" title="Pilih warna tag">
                            <input type="text" id="tag_color_hex" class="form-control" value="#6c757d" placeholder="#HEXCOLOR">
                        </div>
                        <small class="text-muted">Pilih warna untuk membedakan kategori tag di dashboard.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let tagTable;

    $(document).ready(function() {
        // Initialize DataTable
        tagTable = $('#tagTable').DataTable({
            destroy: true,
            processing: true,
            ajax: {
                url: "{{ route('admin.tags.index') }}",
                dataSrc: ""
            },
            columns: [
                { data: 'name' },
                { data: 'color' },
                { 
                    data: null,
                    render: function(data) {
                        return `<span class="badge" style="background-color: ${data.color || '#6c757d'}; color: white;">${data.name}</span>`;
                    }
                },
                { 
                    data: 'created_at',
                    render: function(data) {
                        return moment(data).format('DD MMM YYYY, HH:mm');
                    }
                },
                {
                    data: null,
                    className: 'text-end',
                    render: function(data) {
                        return `
                            <a href="javascript:void(0);" class="btn btn-sm btn-white text-success me-2" onclick="editTag(${JSON.stringify(data).replace(/"/g, '&quot;')})">
                                <i class="far fa-edit me-1"></i> Edit
                            </a>
                            <a href="javascript:void(0);" class="btn btn-sm btn-white text-danger" onclick="deleteTag(${data.id})">
                                <i class="far fa-trash-alt me-1"></i> Hapus
                            </a>
                        `;
                    }
                }
            ]
        });

        // Sync color picker with hex input
        $('#tag_color').on('input', function() {
            $('#tag_color_hex').val($(this).val());
        });
        $('#tag_color_hex').on('input', function() {
            let val = $(this).val();
            if(/^#[0-9A-F]{6}$/i.test(val)) {
                $('#tag_color').val(val);
            }
        });

        // Handle Form Submission
        $('#tagForm').on('submit', function(e) {
            e.preventDefault();
            
            const id = $('#tag_id').val();
            const name = $('#tag_name').val();
            const color = $('#tag_color_hex').val();
            
            const url = id ? `{{ url('admin/tags') }}/${id}` : "{{ route('admin.tags.store') }}";
            const method = id ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: method,
                data: {
                    _token: "{{ csrf_token() }}",
                    name: name,
                    color: color
                },
                success: function(response) {
                    $('#tagModal').modal('hide');
                    tagTable.ajax.reload();
                    Toast.fire({
                        icon: 'success',
                        title: response.message
                    });
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Terjadi kesalahan';
                    Swal.fire('Error', message, 'error');
                }
            });
        });
    });

    function resetForm() {
        $('#modalTitle').text('Tambah Tag Baru');
        $('#tag_id').val('');
        $('#tag_name').val('');
        $('#tag_color').val('#6c757d');
        $('#tag_color_hex').val('#6c757d');
    }

    function editTag(tag) {
        $('#modalTitle').text('Edit Tag');
        $('#tag_id').val(tag.id);
        $('#tag_name').val(tag.name);
        $('#tag_color').val(tag.color || '#6c757d');
        $('#tag_color_hex').val(tag.color || '#6c757d');
        $('#tagModal').modal('show');
    }

    function deleteTag(id) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Tag akan dihapus secara permanen dan dilepas dari semua percakapan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/tags') }}/${id}`,
                    method: 'DELETE',
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        tagTable.ajax.reload();
                        Toast.fire({
                            icon: 'success',
                            title: response.message
                        });
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Gagal menghapus tag', 'error');
                    }
                });
            }
        });
    }
</script>
@endpush
