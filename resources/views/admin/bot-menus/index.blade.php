@extends('layouts.admin_template')

@section('title', 'Alur Chat Bot')

@section('content')
<!-- TAMBAHKAN PANZOOM LIBRARY -->
<script src="https://unpkg.com/@panzoom/panzoom@4.5.1/dist/panzoom.min.js"></script>

<style>
/* Latar Belakang Kotak Titik-titik (Dot Grid) ala Visual Builder */
.tree-wrapper-container {
    width: 100%;
    min-height: 75vh;
    overflow: hidden; /* Sembunyikan scrollbar OS karena kita pakai Panzoom */
    background-color: #f8fafc;
    background-image: radial-gradient(#cbd5e1 1.5px, transparent 1.5px);
    background-size: 25px 25px;
    border-radius: 12px;
    box-shadow: inset 0 0 10px rgba(0,0,0,0.02);
    position: relative;
    cursor: grab;
    z-index: 10;
}

.tree-wrapper-container:active {
    cursor: grabbing;
}

/* Base Tree Rules */
.tf-tree {
    text-align: center;
    white-space: nowrap;
    margin: 0 auto;
    /* Memberi ruang nafas luas agar saat digeser tidak terpotong */
    padding: 100px 300px;
    transform-origin: 0 0;
}

.tf-tree ul {
    padding-top: 30px; 
    position: relative;
    display: inline-flex;
    justify-content: center;
    padding-left: 0;
    margin-bottom: 0;
}

.tf-tree li {
    float: left;
    text-align: center;
    list-style-type: none;
    position: relative;
    padding: 30px 15px 0 15px; /* Jarak antar ranting horisontal */
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
    padding: 6px 12px;
    min-width: 150px;
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
    text-transform: uppercase;
}

.input-value {
    font-size: 13px;
    color: #334155;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* KOTAK BOT RESPON (Kotak Besar di bawah) */
.bot-node-box {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    width: 250px;
    margin: 0 auto;
    text-align: left;
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
    font-size: 11px;
    font-weight: 800;
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

/* Action Area (Tampil Permanen, Tidak Pakai Hover) */
.node-actions-hover {
    padding: 8px 12px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    border-radius: 0 0 8px 8px;
    display: flex;
    justify-content: flex-end; /* Jika Mau Rata Kanan */
    gap: 6px;
    opacity: 1 !important; /* Mencegah disembunyikan CSS lain */
    visibility: visible !important;
}

.btn-mini {
    padding: 5px 8px;
    font-size: 11px;
    border-radius: 4px;
    border: 1px solid #cbd5e1;
    background: white;
    color: #475569;
    text-decoration: none;
    cursor: pointer;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.btn-mini:hover { background: #f1f5f9; filter: brightness(0.95); }
.btn-mini.add { color: #059669; border-color: #a7f3d0; background: #f0fdf4;}
.btn-mini.edit { color: #2563eb; border-color: #bfdbfe; background: #eff6ff;}
.btn-mini.delete { color: #dc2626; border-color: #fecaca; background: #fef2f2;}

/* Root Styles (Start Point) */
.root-point {
    background: #334155;
    color: white;
    font-size: 11px;
    padding: 6px 16px;
    border-radius: 20px;
    display: inline-block;
    margin-bottom: 15px;
    font-weight: 700;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Modals di depan kanvas panzoom dll */
.modal-backdrop, .modal { z-index: 1050 !important; }

/* Control Panel Zoom (Melayang Kritis) */
.zoom-controls {
    position: absolute;
    bottom: 20px;
    right: 20px;
    z-index: 99;
    display: flex;
    flex-direction: column;
    gap: 5px;
    background: white;
    padding: 5px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}
.zoom-btn {
    width: 36px; height: 36px;
    border: none; background: transparent;
    border-radius: 5px; cursor: pointer;
    color: #475569; font-size: 16px;
}
.zoom-btn:hover { background: #f1f5f9; color: #0f172a; }

</style>

<div x-data="{
    showModal: false,
    showGreetingModal: false,
    isEdit: false,
    form: { id: '', parent_id: '', label: '', message_response: '', action_type: 'submenu', action_value: '' },
    greetingForm: { message: `{!! addslashes(\App\Models\Setting::get('bot_greeting_message', 'Selamat datang di layanan pelanggan BRILLIAN.BIS! Ada yang bisa kami bantu?')) !!}` },
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
                <h3 class="page-title">Alur Percakapan Cerdas (Bot Canvas Builder)</h3>
            </div>
            <div class="col-auto">
                <p class="text-xs text-muted mb-0"><i class="fas fa-mouse me-1"></i> <i>Scroll Wheel</i> untuk Zoom In/Out | Klik Tahan untuk Geser</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm"><i class="feather-check-circle me-1"></i> {{ session('success') }}</div>
            @endif

            <div class="card shadow rounded-3 border-0 overflow-hidden">
                <div class="card-body p-0 position-relative">
                    
                    {{-- TOMBOL KONTROL ZOOM MENGAPUNG --}}
                    <div class="zoom-controls">
                        <button class="zoom-btn" id="zoomInBtn" title="Zoom In"><i class="fas fa-plus"></i></button>
                        <button class="zoom-btn" id="zoomOutBtn" title="Zoom Out"><i class="fas fa-minus"></i></button>
                        <button class="zoom-btn" id="resetBtn" title="Tengahkan Canvas"><i class="fas fa-expand"></i></button>
                    </div>

                    {{-- RUANG KANVAS PEMBUNGKUS TREE --}}
                    <div class="tree-wrapper-container" id="panzoom-container">
                        <div class="tf-tree" id="panzoom-element">
                            <!-- STRUKTUR UTAMA POHON -->
                            <ul>
                                <li>
                                    <!-- ROOT NODE (Start Point) -->
                                    <div class="root-point">Start point</div>
                                    <div class="bot-node-box shadow-lg border-0">
                                        <div class="node-header-band node-header-root">
                                            <span>BEST-Greeting (Root)</span>
                                        </div>
                                        <div class="node-body">
                                            {{ \App\Models\Setting::get('bot_greeting_message', 'Selamat datang di layanan pelanggan BRILLIAN.BIS! Ada yang bisa kami bantu?') }}<br><br>
                                            <em class="text-muted" style="font-size: 10px;">(Pesan Pembuka Dinamis)</em>
                                        </div>
                                        <div class="node-actions-hover bg-light d-flex border-top" style="padding: 6px; gap: 6px;">
                                            <button type="button" @click="showGreetingModal = true" class="btn-mini edit flex-fill justify-content-center" style="padding: 8px;"><i class="fas fa-pen me-1"></i> Edit Sapaan</button>
                                            <button type="button" @click="openCreate()" class="btn-mini add flex-fill justify-content-center" style="padding: 8px;"><i class="fas fa-plus me-1"></i> Tambah Menu</button>
                                        </div>
                                    </div>

                                    @if(count($menus) > 0)
                                    <!-- ANAK CABANG KE Menu Utama Berulang Rekursif -->
                                    <ul>
                                        @foreach($menus as $menu)
                                            <!-- Kita pisah view komponen partial -->
                                            @include('admin.bot-menus.partials.tree_node', ['menu' => $menu])
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
            <div class="modal-content shadow-lg border-0 rounded-4">
                <form :action="isEdit ? '/admin/bot-menus/' + form.id : '/admin/bot-menus'" method="POST">
                    @csrf
                    <template x-if="isEdit"><input type="hidden" name="_method" value="PUT"></template>
                    <input type="hidden" name="parent_id" x-model="form.parent_id">

                    <div class="modal-header bg-white border-bottom-0 pb-0 pt-4 px-4">
                        <h5 class="modal-title fw-bolder text-dark" x-text="isEdit ? 'Pengaturan Cabang Node' : 'Ciptakan Jalur Baru'"></h5>
                        <button type="button" class="btn-close" @click="showModal = false"></button>
                    </div>
                    <div class="modal-body px-4 pt-4">
                        <div class="form-group mb-4">
                            <label class="form-label text-muted small fw-bold text-uppercase">Nama Tombol Menu <span class="text-danger">*</span></label>
                            <input type="text" name="label" x-model="form.label" class="form-control form-control-lg bg-light" placeholder="Misal: 'Hubungi CS', 'Layanan Bantuan'" required>
                        </div>

                        <div class="form-group mb-4">
                            <label class="form-label text-muted small fw-bold text-uppercase">Tipe Node (Aksi Lanjutan) <span class="text-danger">*</span></label>
                            <select name="action_type" x-model="form.action_type" class="form-select bg-light" required>
                                <option value="submenu">Buka Submenu Lanjutan (Tambah Pilihan Baru)</option>
                                <option value="link">Kirim URL Eksternal (User Keluar Web)</option>
                                <option value="connect_cs">Minta Dialihkan ke Agent Manusia</option>
                            </select>
                        </div>

                        <div class="form-group mb-4 position-relative">
                            <label class="form-label text-muted small fw-bold text-uppercase">Balasan Spesifik AI (Opsional)</label>
                            <textarea name="message_response" x-model="form.message_response" class="form-control bg-light" rows="3" placeholder="Apa respon gelembung bot ketika menu ini dipencet pelanggan?"></textarea>
                        </div>

                        <div class="form-group mb-4" x-show="form.action_type === 'link'">
                            <label class="form-label text-muted small fw-bold text-uppercase">Destinasi URL Valid</label>
                            <input type="url" name="action_value" x-model="form.action_value" class="form-control bg-light" placeholder="https://youtube.com/...">
                        </div>

                        <div class="form-group mb-4" x-show="form.action_type === 'connect_cs'">
                            <label class="form-label text-muted small fw-bold text-uppercase">Kategori Departemen Tujuan (Opsional)</label>
                            <input type="text" name="action_value" x-model="form.action_value" class="form-control bg-light" placeholder="Contoh: Tim Sales / Tim Teknisi...">
                        </div>
                    </div>
                    <div class="modal-footer px-4 pb-4 border-top-0 bg-white rounded-bottom-4">
                        <button type="button" class="btn btn-light rounded-pill px-4" @click="showModal = false">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm fw-bold" x-text="isEdit ? 'Simpan Node' : 'Tumbuhkan Node'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade" :class="showModal ? 'show d-block' : ''" x-show="showModal" x-cloak></div>
    <div class="modal fade" :class="showGreetingModal ? 'show d-block' : ''" tabindex="-1" x-show="showGreetingModal" x-cloak>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <form action="{{ route('admin.bot-menus.greeting') }}" method="POST">
                    @csrf
                    <div class="modal-header bg-white border-bottom-0 pb-0 pt-4 px-4">
                        <h5 class="modal-title fw-bolder text-dark">Pengaturan Sapaan Awal (Root Node)</h5>
                        <button type="button" class="btn-close" @click="showGreetingModal = false"></button>
                    </div>
                    <div class="modal-body px-4 pt-4">
                        <div class="form-group mb-4">
                            <label class="form-label text-muted small fw-bold text-uppercase">Teks Gelembung Pembuka <span class="text-danger">*</span></label>
                            <textarea name="bot_greeting_message" x-model="greetingForm.message" class="form-control bg-light" rows="4" required placeholder="Tuliskan respon pertama bot di sini..."></textarea>
                            <small class="text-muted mt-2 d-block">Pesan ini menimpa sapaan bawaan pada Livechat user dan akan langsung tersinkronisasi realtime.</small>
                        </div>
                    </div>
                    <div class="modal-footer px-4 pb-4 border-top-0 bg-white rounded-bottom-4">
                        <button type="button" class="btn btn-light rounded-pill px-4" @click="showGreetingModal = false">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm fw-bold">Simpan Sapaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade" :class="showGreetingModal ? 'show d-block' : ''" x-show="showGreetingModal" x-cloak></div>

</div>

<script>
    // Inisialisasi PANZOOM (Penyihir Canvas Masa Kini)
    document.addEventListener('DOMContentLoaded', function() {
        // Ambil elemen kanvas utamanya
        const elem = document.getElementById('panzoom-element');
        const container = document.getElementById('panzoom-container');

        // Nyalakan plugin Panzoom (Cukup 1 baris kode sakti ini)
        const panzoom = Panzoom(elem, {
            maxScale: 3,
            minScale: 0.2,
            step: 0.1,
            cursor: 'grab',
            animate: true
        });

        // 1. Dukungan Zoom In / Out memakai Scroll/Roda Mouse!
        elem.parentElement.addEventListener('wheel', panzoom.zoomWithWheel);

        // 2. Tombol Aksi Kanan Bawah
        document.getElementById('zoomInBtn').addEventListener('click', panzoom.zoomIn);
        document.getElementById('zoomOutBtn').addEventListener('click', panzoom.zoomOut);
        document.getElementById('resetBtn').addEventListener('click', panzoom.reset);

        // 3. Modifikasi Intervensi Input/Klik di dalam Panzoom Element
        // Mencegah Panzoom mendeteksi / mengambil alih event ketika user menekan Tombol atau mengetik Form Modal (Krusial Bug Fix)
        elem.addEventListener('panzoomstart', (event) => {
            if (event.target.tagName.toLowerCase() === 'button' || 
                event.target.closest('button') || 
                event.target.closest('.modal')) {
                event.preventDefault(); // Matikan grab ketika lagi mijit tombol Alpine
            }
        });
        
        // Geser Diagram sedikit ke tengah layar pada saat awal Load
        setTimeout(() => { panzoom.pan(0, 50); }, 500);
    });
</script>
@endsection
