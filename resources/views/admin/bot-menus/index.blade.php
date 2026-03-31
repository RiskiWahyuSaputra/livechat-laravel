@extends('layouts.admin_template')

@section('title', 'Alur Chat Bot')

@section('content')
<style>
/* CSS Dasar untuk Flowchart / Tree Diagram */
.tree-wrapper {
    width: 100%;
    overflow-x: auto;
    padding: 30px 20px;
    background: #fdfdfd;
    border-radius: 12px;
    box-shadow: inset 0 0 10px rgba(0,0,0,0.02);
    cursor: grab; /* optional: to indicate horizontal draggable scroll if you implement it */
}

.tf-tree {
    text-align: center;
    white-space: nowrap;
    margin: 0 auto;
}

.tf-tree ul {
    padding-top: 20px;
    position: relative;
    transition: all 0.5s;
    display: inline-flex;
    justify-content: center;
    padding-left: 0;
}

.tf-tree li {
    float: left;
    text-align: center;
    list-style-type: none;
    position: relative;
    padding: 20px 10px 0 10px;
    transition: all 0.5s;
}

/* Connector Lines */
.tf-tree li::before, .tf-tree li::after {
    content: '';
    position: absolute; top: 0; right: 50%;
    border-top: 2px solid #ced4da;
    width: 50%; height: 20px;
    z-index: 1;
}
.tf-tree li::after {
    right: auto; left: 50%;
    border-left: 2px solid #ced4da;
}

/* Cleanup for first/last children */
.tf-tree li:only-child::after, .tf-tree li:only-child::before {
    display: none;
}
.tf-tree li:only-child { padding-top: 0; }
.tf-tree li:first-child::before, .tf-tree li:last-child::after {
    border: 0 none;
}
.tf-tree li:last-child::before {
    border-right: 2px solid #ced4da;
    border-radius: 0 5px 0 0;
}
.tf-tree li:first-child::after {
    border-radius: 5px 0 0 0;
}

/* Downward line from parents */
.tf-tree ul::before {
    content: '';
    position: absolute; top: 0; left: 50%;
    border-left: 2px solid #ced4da;
    width: 0; height: 20px;
}

/* Kotak Modul (Nodes) */
.tf-nc {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    padding: 16px;
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    min-width: 250px;
    max-width: 280px;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
    position: relative;
    z-index: 2;
    white-space: normal;
    transition: all 0.2s ease-in-out;
}

.tf-nc:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
    border-color: #cbd5e1;
}

/* Node Elements */
.node-header {
    width: 100%;
    display: flex;
    justify-content: flex-end;
    margin-bottom: 5px;
}

.node-title {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
    line-height: 1.3;
}

.node-badge {
    font-size: 10px;
    padding: 3px 8px;
    border-radius: 20px;
    font-weight: 600;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}
.badge-submenu { background-color: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe; }
.badge-link { background-color: #fef2f2; color: #ef4444; border: 1px solid #fecaca; }
.badge-cs { background-color: #f0fdf4; color: #22c55e; border: 1px solid #bbf7d0; }

.node-response {
    font-size: 11px;
    color: #64748b;
    background: #f8fafc;
    padding: 8px 10px;
    border-radius: 6px;
    width: 100%;
    text-align: left;
    margin-bottom: 15px;
    border-left: 3px solid #e2e8f0;
    max-height: 80px;
    overflow-y: auto;
}

.node-actions {
    display: flex;
    gap: 8px;
    justify-content: center;
    width: 100%;
    border-top: 1px solid #f1f5f9;
    padding-top: 10px;
}

.btn-node {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: #f8fafc;
    color: #64748b;
    transition: all 0.2s;
}

.btn-node:hover.edit { background: #eff6ff; color: #3b82f6; }
.btn-node:hover.delete { background: #fef2f2; color: #ef4444; }
.btn-node:hover.add { background: #f0fdf4; color: #22c55e; text-decoration: none;}

/* Menjadikan Root Node Spesial */
.tf-root {
    border: 2px solid #3b82f6;
    background: linear-gradient(to bottom, #ffffff, #eff6ff);
}
.tf-root .node-title { color: #1d4ed8; }

/* Horizontal Scrollbar Stylings */
.tree-wrapper::-webkit-scrollbar { height: 10px; }
.tree-wrapper::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 5px; }
.tree-wrapper::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 5px; }
.tree-wrapper::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>

<div x-data="{
    showModal: false,
    isEdit: false,
    form: { id: '', parent_id: '', label: '', message_response: '', action_type: 'submenu', action_value: '' },
    openCreate(parentId = null) {
        this.isEdit = false;
        this.form = { id: '', parent_id: parentId, label: '', message_response: '', action_type: 'submenu', action_value: '' };
        this.showModal = true;
    },
    openEdit(menu) {
        this.isEdit = true;
        this.form = { 
            id: menu.id, 
            parent_id: menu.parent_id, 
            label: menu.label, 
            message_response: menu.message_response, 
            action_type: menu.action_type, 
            action_value: menu.action_value 
        };
        this.showModal = true;
    }
}">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Alur Percakapan (Chat Flow Diagram)</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Alur Obrolan Bot</li>
                </ul>
            </div>
            <div class="col-auto">
                {{-- Tombol Refresh Tabel/Layar untuk kenyamanan --}}
                <button onclick="window.location.reload()" class="btn btn-white btn-sm me-2"><i class="fas fa-sync-alt"></i> Refresh Diagram</button>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="feather-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h5 class="card-title text-muted mb-0"><i class="fas fa-sitemap text-primary me-2"></i> Peta Hierarki Bot Menu</h5>
                    <p class="text-sm text-secondary mt-1">Anda bisa mengikuti garis rute untuk mengatur percakapan otomatis dengan mudah.</p>
                </div>
                <div class="card-body py-1">
                    
                    {{-- DIAGRAM POHON (TREE CHART) --}}
                    <div class="tree-wrapper mt-2">
                        <div class="tf-tree">
                            <ul>
                                <li>
                                    <!-- ROOT NODE: Titik Mula Chat -->
                                    <div class="tf-nc tf-root shadow-lg">
                                        <div class="node-badge badge-primary bg-primary text-white border-primary mb-2 shadow-sm">
                                            <i class="fas fa-robot me-1"></i> LAYAR AWAL GUEST
                                        </div>
                                        <div class="node-title">Pesan Sambutan Pertama</div>
                                        <div class="node-response">
                                            "Halo! Saya BEST AI... Pilih menu di bawah ini:"
                                        </div>
                                        <div class="node-actions mt-1 pt-0 border-0">
                                            <button @click="openCreate()" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm" title="Tambah Menu Utama">
                                                <i class="fas fa-plus me-1"></i> Tambah Menu Utama
                                            </button>
                                        </div>
                                    </div>
                                    
                                    {{-- Jika ada menu utama, maka buka cabang --}}
                                    @if(count($menus) > 0)
                                    <ul>
                                        @foreach($menus as $menu)
                                        <li>
                                            <!-- NODE LEVEL 1 (MENU UTAMA) -->
                                            <div class="tf-nc">
                                                <span class="node-badge 
                                                    {{ $menu->action_type === 'submenu' ? 'badge-submenu' : '' }} 
                                                    {{ $menu->action_type === 'link' ? 'badge-link' : '' }} 
                                                    {{ $menu->action_type === 'connect_cs' ? 'badge-cs' : '' }}">
                                                    {{ strtoupper($menu->action_type) }}
                                                </span>
                                                <div class="node-title">{{ $menu->label }}</div>
                                                
                                                @if($menu->message_response || $menu->action_value)
                                                <div class="node-response">
                                                    @if($menu->action_type === 'link')
                                                        <i class="fas fa-link me-1"></i> <a href="{{ $menu->action_value }}" target="_blank" class="text-primary text-truncate d-inline-block align-middle" style="max-width:180px">{{ $menu->action_value }}</a><br>
                                                    @endif
                                                    {{ Str::limit($menu->message_response ?: 'Menuju Layanan: '.$menu->action_value, 60) }}
                                                </div>
                                                @endif
                                                
                                                <div class="node-actions">
                                                    @if($menu->action_type === 'submenu')
                                                        <button @click="openCreate({{ $menu->id }})" class="btn-node add" title="Tambah Submenu di bawah ini"><i class="fas fa-plus"></i></button>
                                                    @endif
                                                    <button @click="openEdit({{ $menu->toJson() }})" class="btn-node edit" title="Edit Menu"><i class="fas fa-pen"></i></button>
                                                    
                                                    <form action="{{ route('admin.bot-menus.destroy', $menu->id) }}" method="POST" class="d-inline m-0 p-0" onsubmit="return confirm('Peringatan: Menghapus kotak ini akan ikut menghapus SEMUA ranting submenunya ke bawah. Yakin ingin menghapus?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn-node delete" title="Hapus Permanen"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </div>
                                            </div>

                                            {{-- Jika menu ini adalah submenu dan punya anak cabang --}}
                                            @if($menu->action_type === 'submenu' && count($menu->children) > 0)
                                            <ul>
                                                @foreach($menu->children as $child)
                                                <li>
                                                    <!-- NODE LEVEL 2 (SUBMENU) -->
                                                    <div class="tf-nc">
                                                        <span class="node-badge 
                                                            {{ $child->action_type === 'submenu' ? 'badge-submenu' : '' }} 
                                                            {{ $child->action_type === 'link' ? 'badge-link' : '' }} 
                                                            {{ $child->action_type === 'connect_cs' ? 'badge-cs' : '' }}">
                                                            {{ strtoupper($child->action_type) }}
                                                        </span>
                                                        <div class="node-title">{{ $child->label }}</div>
                                                        
                                                        @if($child->message_response || $child->action_value)
                                                        <div class="node-response">
                                                            @if($child->action_type === 'link')
                                                                <i class="fas fa-link me-1"></i> <a href="{{ $child->action_value }}" target="_blank" class="text-primary text-truncate d-inline-block align-middle" style="max-width:180px">{{ $child->action_value }}</a><br>
                                                            @endif
                                                            {{ Str::limit($child->message_response ?: 'Menuju Bidang: '.$child->action_value, 60) }}
                                                        </div>
                                                        @endif
                                                        
                                                        <div class="node-actions">
                                                            <button @click="openEdit({{ $child->toJson() }})" class="btn-node edit" title="Edit Menu"><i class="fas fa-pen"></i></button>
                                                            <form action="{{ route('admin.bot-menus.destroy', $child->id) }}" method="POST" class="d-inline m-0 p-0" onsubmit="return confirm('Hapus ranting submenu ini?')">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="btn-node delete" title="Hapus Submenu"><i class="fas fa-trash"></i></button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </li>
                                                @endforeach
                                            </ul>
                                            @endif
                                        </li>
                                        @endforeach
                                    </ul>
                                    @endif
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                </div>
            </div>
            
            <div class="alert alert-info border-info mt-4 rounded-3 shadow-sm bg-white">
                <div class="d-flex align-items-start">
                    <i class="fas fa-info-circle display-6 text-info me-3 mt-1"></i>
                    <div>
                        <h6 class="mb-1 fw-bold text-info-emphasis">Sistem Hirarki Pohon Interaktif</h6>
                        <p class="mb-0 text-secondary text-sm">Flowchart di atas mewakili bagaimana Bot AI memberi pilihan ganda ke User. Customer akan membaca <b>Pesan Balasan</b> di dalam kotak ketika mengklik tombol tersebut. Kemudian Bot akan memicu <i>Action Tipe</i> di logo lencana warna-warni atasnya. Klik Tahan dan Geser (Drag layar) untuk menavigasi ke sisi jika pohon terlalu rimbun/lebar!</p>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    <!-- Modal Form (Masih Sama & Powerfull) -->
    <div class="modal fade" :class="showModal ? 'show d-block' : ''" tabindex="-1" x-show="showModal" x-cloak>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <form :action="isEdit ? '/admin/bot-menus/' + form.id : '/admin/bot-menus'" method="POST">
                    @csrf
                    <template x-if="isEdit"><input type="hidden" name="_method" value="PUT"></template>
                    <input type="hidden" name="parent_id" x-model="form.parent_id">

                    <div class="modal-header border-bottom-0 bg-light rounded-top-4 pb-0 pt-4 px-4">
                        <h5 class="modal-title fw-bold text-primary" x-text="isEdit ? '✏️ Edit Kotak Menu' : '➕ Buat Kotak Cabang Baru'"></h5>
                        <button type="button" class="btn-close" @click="showModal = false"></button>
                    </div>
                    <div class="modal-body px-4 pt-4 pb-2">
                        <div class="form-group mb-4">
                            <label class="form-label font-weight-bold text-dark">Nama/Label Tombol <span class="text-danger">*</span></label>
                            <input type="text" name="label" x-model="form.label" class="form-control form-control-lg bg-light" placeholder="Contoh: 'Tanya Produk' / 'Beli Sekarang'" required>
                        </div>

                        <div class="form-group mb-4">
                            <label class="form-label font-weight-bold text-dark">Tipe Aksi Pemicu <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-bolt text-warning"></i></span>
                                <select name="action_type" x-model="form.action_type" class="form-select bg-light" required>
                                    <option value="submenu">Buka Submenu (Menampilkan cabang pilihan baru)</option>
                                    <option value="link">Arahkan Link Terluar (Website luar/Youtube)</option>
                                    <option value="connect_cs">Minta Dioper Secara Manusia Ke Agent Live</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group mb-4 position-relative">
                            <label class="form-label font-weight-bold text-dark">Tepian Jawaban Mesin (Pesan Balasan)</label>
                            <textarea name="message_response" x-model="form.message_response" class="form-control bg-light" rows="3" placeholder="Masukkan ketikan respons instan jika ini dipencet oleh customer..."></textarea>
                            <small class="text-muted d-block mt-2"><i class="fas fa-info-circle"></i> Merupakan <i>Bubble Text</i> pembuka yang di-<i>generate</i> bot sebelum menjalankan aksi utamanya.</small>
                        </div>

                        <div class="form-group mb-4" x-show="form.action_type === 'link'">
                            <label class="form-label font-weight-bold text-dark">URL Link <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-link text-danger"></i></span>
                                <input type="url" name="action_value" x-model="form.action_value" class="form-control bg-light" placeholder="https://youtube.com/...">
                            </div>
                        </div>

                        <div class="form-group mb-4" x-show="form.action_type === 'connect_cs'">
                            <label class="form-label font-weight-bold text-dark">Jalur Penugasan / Capping</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-headset text-success"></i></span>
                                <input type="text" name="action_value" x-model="form.action_value" class="form-control bg-light" placeholder="(Opsional) Tulis Kategori: General Support / Penjualan...">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 px-4 pb-4 bg-white rounded-bottom-4">
                        <button type="button" class="btn btn-light rounded-pill px-4" @click="showModal = false">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm" x-text="isEdit ? 'Update Sekarang' : 'Tumbuhkan Cabang Baru'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade" :class="showModal ? 'show d-block' : ''" x-show="showModal" x-cloak></div>
</div>

<script>
    // Dukungan geser horizontal / klik tahan layar untuk geser diagram jika terlalu lebar
    document.addEventListener('DOMContentLoaded', function() {
        const slider = document.querySelector('.tree-wrapper');
        let isDown = false;
        let startX;
        let scrollLeft;

        if (slider) {
            slider.addEventListener('mousedown', (e) => {
                isDown = true;
                slider.style.cursor = 'grabbing';
                startX = e.pageX - slider.offsetLeft;
                scrollLeft = slider.scrollLeft;
            });
            slider.addEventListener('mouseleave', () => {
                isDown = false;
                slider.style.cursor = 'grab';
            });
            slider.addEventListener('mouseup', () => {
                isDown = false;
                slider.style.cursor = 'grab';
            });
            slider.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - slider.offsetLeft;
                const walk = (x - startX) * 2; // scroll-fast
                slider.scrollLeft = scrollLeft - walk;
            });
        }
    });
</script>
@endsection
