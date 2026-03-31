@extends('layouts.admin_template')

@section('title', 'Alur Chat Bot')

@section('content')
<style>
/* Latar Belakang Kotak Titik-titik (Dot Grid) ala Visual Builder */
.tree-wrapper {
    width: 100%;
    min-height: 70vh;
    overflow: auto;
    padding: 40px 20px;
    background-color: #f8fafc;
    background-image: radial-gradient(#cbd5e1 1.5px, transparent 1.5px);
    background-size: 25px 25px;
    border-radius: 12px;
    box-shadow: inset 0 0 10px rgba(0,0,0,0.02);
    cursor: grab;
}

.tree-wrapper:active {
    cursor: grabbing;
}

/* Base Tree Rules */
.tf-tree {
    text-align: center;
    white-space: nowrap;
    margin: 0 auto;
}

.tf-tree ul {
    padding-top: 30px; 
    position: relative;
    display: inline-flex;
    justify-content: center;
    padding-left: 0;
}

.tf-tree li {
    float: left;
    text-align: center;
    list-style-type: none;
    position: relative;
    padding: 30px 10px 0 10px;
}

/* Connector Lines yang Tegas & Lurus (Warna Abu-Abu) */
.tf-tree li::before, .tf-tree li::after {
    content: '';
    position: absolute; top: 0; right: 50%;
    border-top: 1px solid #94a3b8;
    width: 50%; height: 30px;
    z-index: 1;
}
.tf-tree li::after {
    right: auto; left: 50%;
    border-left: 1px solid #94a3b8;
}
.tf-tree li:only-child::after, .tf-tree li:only-child::before {
    display: none;
}
.tf-tree li:only-child { padding-top: 0; }
.tf-tree li:first-child::before, .tf-tree li:last-child::after {
    border: 0 none;
}
.tf-tree li:last-child::before {
    border-right: 1px solid #94a3b8;
    border-radius: 0;
}
.tf-tree li:first-child::after {
    border-radius: 0;
}
.tf-tree ul::before {
    content: '';
    position: absolute; top: 0; left: 50%;
    border-left: 1px solid #94a3b8;
    width: 0; height: 30px;
}

/* KOTAK USER INPUT (Kotak Kecil di atas) */
.user-input-box {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 6px 10px;
    min-width: 140px;
    max-width: 200px;
    margin: 0 auto 15px auto;
    text-align: left;
    display: flex;
    flex-direction: column;
    position: relative;
    z-index: 2;
}

/* Garis penghubung kecil dari User Input ke Bot Node */
.user-input-box::after {
    content: '';
    position: absolute;
    bottom: -16px;
    left: 50%;
    border-left: 1px solid #94a3b8;
    height: 15px;
}

.input-header {
    font-size: 10px;
    color: #64748b;
    font-weight: 600;
    margin-bottom: 2px;
}

.input-value {
    font-size: 12px;
    color: #334155;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* KOTAK BOT RESPON (Kotak Besar di bawah) */
.bot-node-box {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    width: 240px;
    margin: 0 auto;
    text-align: left;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
    position: relative;
    z-index: 2;
}

.node-header-band {
    background: #eff6ff;
    padding: 8px 12px;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 8px 8px 0 0;
    font-size: 12px;
    font-weight: 700;
    color: #1e40af;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Pewarnaan Header Berdasarkan Tipe */
.node-header-band.type-link { background: #fef2f2; color: #b91c1c; }
.node-header-band.type-cs { background: #f0fdf4; color: #15803d; }
.node-header-root { background: #f1f5f9; color: #334155; }

.node-body {
    padding: 12px;
    font-size: 12px;
    color: #475569;
    line-height: 1.5;
    white-space: normal;
    min-height: 40px;
}

/* Action Area (Inline Button) */
.node-actions-hover {
    padding: 8px 12px;
    background: #f8fafc;
    border-top: 1px dashed #cbd5e1;
    border-radius: 0 0 8px 8px;
    display: flex;
    justify-content: flex-end;
    gap: 5px;
}

.btn-mini {
    padding: 4px 8px;
    font-size: 11px;
    border-radius: 4px;
    border: 1px solid #e2e8f0;
    background: white;
    color: #64748b;
    text-decoration: none;
    cursor: pointer;
}
.btn-mini:hover { background: #f1f5f9; }
.btn-mini.add { color: #10b981; }
.btn-mini.edit { color: #3b82f6; }
.btn-mini.delete { color: #ef4444; }

/* Root Styles (Start Point) */
.root-point {
    background: #475569;
    color: white;
    font-size: 10px;
    padding: 4px 14px;
    border-radius: 12px;
    display: inline-block;
    margin-bottom: 12px;
    font-weight: 600;
}

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
                <h3 class="page-title">Alur Percakapan (Bot Flow Builder)</h3>
            </div>
            <div class="col-auto">
                <button onclick="window.location.reload()" class="btn btn-white btn-sm"><i class="fas fa-sync-alt"></i> Refresh Canvas</button>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm"><i class="feather-check-circle me-1"></i> {{ session('success') }}</div>
            @endif

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="tree-wrapper" id="canvas-container">
                        <div class="tf-tree">
                            <ul>
                                <li>
                                    <!-- ROOT NODE (Start Point) -->
                                    <div class="root-point">Start point</div>
                                    <div class="bot-node-box shadow-sm">
                                        <div class="node-header-band node-header-root">
                                            <span>BEST-Greeting (Root)</span>
                                        </div>
                                        <div class="node-body">
                                            Halo! Saya BEST AI... Pilih menu di bawah ini:<br><br>
                                            <em class="text-muted" style="font-size: 10px;">(Pilihan bot default)</em>
                                        </div>
                                        <div class="node-actions-hover">
                                            <button @click="openCreate()" class="btn-mini add w-100 text-center fw-bold"><i class="fas fa-plus"></i> Tambah Menu Utama</button>
                                        </div>
                                    </div>

                                    @if(count($menus) > 0)
                                    <ul>
                                        @foreach($menus as $menu)
                                        <li>
                                            <!-- USER INPUT KOTAK KECIL -->
                                            <div class="user-input-box">
                                                <div class="input-header">User input</div>
                                                <div class="input-value">
                                                    @if($menu->action_type === 'submenu') <i class="fas fa-bars text-secondary"></i> @endif
                                                    @if($menu->action_type === 'link') <i class="fas fa-link text-danger"></i> @endif
                                                    @if($menu->action_type === 'connect_cs') <i class="fas fa-headset text-success"></i> @endif
                                                    {{ $menu->label }}
                                                </div>
                                            </div>

                                            <!-- BOT ACTION KOTAK BESAR -->
                                            <div class="bot-node-box">
                                                <div class="node-header-band 
                                                    {{ $menu->action_type === 'link' ? 'type-link' : '' }}
                                                    {{ $menu->action_type === 'connect_cs' ? 'type-cs' : '' }}">
                                                    <span>{{ strtoupper($menu->action_type) }}</span>
                                                </div>
                                                <div class="node-body">
                                                    @if($menu->message_response)
                                                        {{ Str::limit($menu->message_response, 80) }}
                                                    @endif

                                                    @if($menu->action_value)
                                                        <div class="mt-2 p-1 bg-light rounded" style="font-size: 10px; word-break: break-all;">
                                                            <strong>Aksi/Tujuan:</strong> {{ $menu->action_value }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="node-actions-hover">
                                                    @if($menu->action_type === 'submenu')
                                                        <button @click="openCreate({{ $menu->id }})" class="btn-mini add" title="Tambah Cabang"><i class="fas fa-plus"></i></button>
                                                    @endif
                                                    <button @click="openEdit({{ $menu->toJson() }})" class="btn-mini edit" title="Edit"><i class="fas fa-pen"></i></button>
                                                    
                                                    <form action="{{ route('admin.bot-menus.destroy', $menu->id) }}" method="POST" class="m-0 p-0" onsubmit="return confirm('Hapus seluruh cabang ini?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn-mini delete"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </div>
                                            </div>

                                            <!-- REKURSIF SUBMENU LEVEL BAWAH -->
                                            @if($menu->action_type === 'submenu' && count($menu->children) > 0)
                                            <ul>
                                                @foreach($menu->children as $child)
                                                <li>
                                                    <!-- USER INPUT KOTAK KECIL -->
                                                    <div class="user-input-box">
                                                        <div class="input-header">User input</div>
                                                        <div class="input-value">
                                                            @if($child->action_type === 'submenu') <i class="fas fa-bars text-secondary"></i> @endif
                                                            @if($child->action_type === 'link') <i class="fas fa-link text-danger"></i> @endif
                                                            @if($child->action_type === 'connect_cs') <i class="fas fa-headset text-success"></i> @endif
                                                            {{ $child->label }}
                                                        </div>
                                                    </div>

                                                    <!-- BOT ACTION KOTAK BESAR -->
                                                    <div class="bot-node-box">
                                                        <div class="node-header-band 
                                                            {{ $child->action_type === 'link' ? 'type-link' : '' }}
                                                            {{ $child->action_type === 'connect_cs' ? 'type-cs' : '' }}">
                                                            <span>{{ strtoupper($child->action_type) }}</span>
                                                        </div>
                                                        <div class="node-body">
                                                            @if($child->message_response)
                                                                {{ Str::limit($child->message_response, 80) }}
                                                            @endif

                                                            @if($child->action_value)
                                                                <div class="mt-2 p-1 bg-light rounded" style="font-size: 10px; word-break: break-all;">
                                                                    <strong>Aksi/Tujuan:</strong> {{ $child->action_value }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="node-actions-hover">
                                                            <button @click="openEdit({{ $child->toJson() }})" class="btn-mini edit" title="Edit"><i class="fas fa-pen"></i></button>
                                                            
                                                            <form action="{{ route('admin.bot-menus.destroy', $child->id) }}" method="POST" class="m-0 p-0" onsubmit="return confirm('Hapus menu ini?')">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="btn-mini delete"><i class="fas fa-trash"></i></button>
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
        </div>
    </div>

    <!-- Modal Form Alpine tetep utuh di bawah sini -->
    <div class="modal fade" :class="showModal ? 'show d-block' : ''" tabindex="-1" x-show="showModal" x-cloak>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0 rounded-3">
                <form :action="isEdit ? '/admin/bot-menus/' + form.id : '/admin/bot-menus'" method="POST">
                    @csrf
                    <template x-if="isEdit"><input type="hidden" name="_method" value="PUT"></template>
                    <input type="hidden" name="parent_id" x-model="form.parent_id">

                    <div class="modal-header bg-light">
                        <h5 class="modal-title font-weight-bold text-dark" x-text="isEdit ? 'Node Settings' : 'Create New Node'"></h5>
                        <button type="button" class="btn-close" @click="showModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Input Pelanggan (Label Menu)</label>
                            <input type="text" name="label" x-model="form.label" class="form-control bg-light" placeholder="Masukkan tulisan di tombol..." required>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Tipe Node (Aksi)</label>
                            <select name="action_type" x-model="form.action_type" class="form-select bg-light" required>
                                <option value="submenu">Flow Bercabang (Submenu)</option>
                                <option value="link">Aksi Buka URL Eksternal</option>
                                <option value="connect_cs">Aksi Transfer ke Agent CS</option>
                            </select>
                        </div>

                        <div class="form-group mb-3 position-relative">
                            <label class="form-label text-muted small fw-bold text-uppercase">Balasan AI (Text Response)</label>
                            <textarea name="message_response" x-model="form.message_response" class="form-control bg-light" rows="3" placeholder="Apa respon bot ketika ini dipencet?"></textarea>
                        </div>

                        <div class="form-group mb-3" x-show="form.action_type === 'link'">
                            <label class="form-label text-muted small fw-bold text-uppercase">Tujuan URL</label>
                            <input type="url" name="action_value" x-model="form.action_value" class="form-control bg-light" placeholder="https://...">
                        </div>

                        <div class="form-group mb-3" x-show="form.action_type === 'connect_cs'">
                            <label class="form-label text-muted small fw-bold text-uppercase">Routing Agent (Opsional)</label>
                            <input type="text" name="action_value" x-model="form.action_value" class="form-control bg-light" placeholder="Departemen Tujuan...">
                        </div>
                    </div>
                    <div class="modal-footer pb-3 border-top-0">
                        <button type="submit" class="btn btn-primary w-100" x-text="isEdit ? 'Save Node Configuration' : 'Create Flow Node'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade" :class="showModal ? 'show d-block' : ''" x-show="showModal" x-cloak></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const slider = document.getElementById('canvas-container');
        let isDown = false, startX, scrollLeft;
        if (slider) {
            slider.addEventListener('mousedown', (e) => {
                // Hindari grab saat mengklik tombol dalam kotak
                if(e.target.closest('.bot-node-box') || e.target.closest('.user-input-box')) return;
                isDown = true; slider.style.cursor = 'grabbing';
                startX = e.pageX - slider.offsetLeft; scrollLeft = slider.scrollLeft;
            });
            slider.addEventListener('mouseleave', () => { isDown = false; slider.style.cursor = 'grab'; });
            slider.addEventListener('mouseup', () => { isDown = false; slider.style.cursor = 'grab'; });
            slider.addEventListener('mousemove', (e) => {
                if (!isDown) return; e.preventDefault();
                const x = e.pageX - slider.offsetLeft; const walk = (x - startX) * 2;
                slider.scrollLeft = scrollLeft - walk;
            });
            
            // Auto scroll to center
            slider.scrollLeft = (slider.scrollWidth - slider.clientWidth) / 2;
        }
    });
</script>
@endsection
