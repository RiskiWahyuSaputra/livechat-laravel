@extends('layouts.admin_template')

@section('title', 'Manajemen Agent')

@section('content')

<div x-data="{
    search: '',
    showModal: false,
    isEdit: false,
    form: { id: '', name: '', description: '', supervisor_id: '', agents: [] },
    openCreate() {
        this.isEdit = false;
        this.form = { id: '', name: '', description: '', supervisor_id: '', agents: [] };
        this.showModal = true;
    },
    openEdit(div) {
        this.isEdit = true;
        this.form = { id: div.id, name: div.name, description: div.description || '', supervisor_id: div.supervisor_id || '', agents: div.agent_ids || [] };
        this.showModal = true;
    }
}">

{{-- Page title --}}
<div class="row mb-3">
    <div class="col-12">
        <h4 class="page-title mb-0">Manajemen Agent</h4>
    </div>
</div>

{{-- Toolbar: search + button — bebas, tidak dalam card --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div class="position-relative" style="width:260px;">
        <i class="fe fe-search position-absolute" style="left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;pointer-events:none;"></i>
        <input type="text" x-model="search" placeholder="Search division..."
            class="form-control form-control-sm"
            style="padding-left:32px;border-radius:8px;font-size:13px;border-color:#e5e7eb;">
    </div>
    <button @click="openCreate()" class="btn btn-primary btn-sm fw-bold px-4" style="border-radius:8px;font-size:13px;">
        Create division
    </button>
</div>

{{-- Table card --}}
<div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
    <div class="table-responsive">
        <table class="table mb-0" style="font-size:13px;margin:0;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e5e7eb;">
                    <th style="padding:11px 16px 11px 20px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Division</th>
                    <th style="padding:11px 16px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Supervisor</th>
                    <th style="padding:11px 16px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Agents</th>
                    <th style="padding:11px 20px 11px 16px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;width:100px;text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($divisions as $div)
                <tr x-show="search === '' || '{{ strtolower($div->name) }}'.includes(search.toLowerCase())"
                    style="border-bottom:1px solid #f1f5f9;"
                    onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                    <td style="padding:16px 16px 16px 20px;font-weight:600;color:#1f2937;">
                        {{ $div->name }}
                    </td>
                    <td style="padding:16px 16px;color:#6b7280;">
                        {{ $div->supervisor?->username ?? '—' }}
                    </td>
                    <td style="padding:16px 16px;color:#6b7280;">
                        @php $names = $div->agents->pluck('username')->toArray(); @endphp
                        @if(count($names))
                            {{ implode(', ', array_slice($names, 0, 5)) }}{{ count($names) > 5 ? ', +' . (count($names) - 5) . ' lainnya' : '' }}
                        @else
                            <span style="color:#cbd5e1;">No agents</span>
                        @endif
                    </td>
                    <td style="padding:16px 20px 16px 16px;text-align:right;">
                        <div class="d-flex align-items-center gap-2 justify-content-end">
                            <button @click="openEdit({{ json_encode(['id' => $div->id, 'name' => $div->name, 'description' => $div->description, 'supervisor_id' => $div->supervisor_id, 'agent_ids' => $div->agents->pluck('id')->toArray()]) }})"
                                class="btn btn-sm btn-white border" style="border-radius:6px;padding:5px 10px;">
                                <i class="fe fe-edit-2" style="font-size:12px;color:#4f46e5;"></i>
                            </button>
                            <form action="{{ route('admin.divisions.destroy', $div->id) }}" method="POST"
                                onsubmit="return confirm('Hapus divisi {{ $div->name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-white border" style="border-radius:6px;padding:5px 10px;">
                                    <i class="fe fe-trash-2" style="font-size:12px;color:#ef4444;"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-5" style="color:#94a3b8;font-size:13px;">
                        Belum ada divisi.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Showing --}}
<div class="mt-2" style="font-size:12px;color:#6b7280;">
    Showing {{ $divisions->count() }} of {{ $divisions->count() }} divisions
</div>

{{-- ── MODAL ── --}}
<div class="modal fade" :class="showModal ? 'show d-block' : ''" tabindex="-1" x-show="showModal" x-cloak>
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px;overflow:hidden;">
            <form :action="isEdit ? '{{ url('admin/divisions') }}/' + form.id : '{{ route('admin.divisions.store') }}'" method="POST">
                @csrf
                <template x-if="isEdit"><input type="hidden" name="_method" value="PUT"></template>

                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold" style="font-size:15px;" x-text="isEdit ? 'Edit Division' : 'Create Division'"></h5>
                    <button type="button" class="btn-close" @click="showModal = false"></button>
                </div>

                <div class="modal-body px-4 py-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">Nama Divisi <span class="text-danger">*</span></label>
                            <input type="text" name="name" x-model="form.name" class="form-control" style="font-size:13px;border-radius:8px;" placeholder="Contoh: Cyber" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">Supervisor</label>
                            <select name="supervisor_id" x-model="form.supervisor_id" class="form-select" style="font-size:13px;border-radius:8px;">
                                <option value="">— Pilih Supervisor —</option>
                                @foreach($agents as $agent)
                                <option value="{{ $agent->id }}">{{ $agent->username }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px;">Deskripsi <span class="text-muted fw-normal">(opsional)</span></label>
                            <input type="text" name="description" x-model="form.description" class="form-control" style="font-size:13px;border-radius:8px;" placeholder="Deskripsi singkat">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px;">Agents</label>
                            <div class="border rounded-3 p-3" style="max-height:200px;overflow-y:auto;background:#f8fafc;">
                                @foreach($agents as $agent)
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox"
                                        name="agent_ids[]"
                                        value="{{ $agent->id }}"
                                        :checked="form.agents.includes({{ $agent->id }})"
                                        @change="form.agents.includes({{ $agent->id }}) ? form.agents.splice(form.agents.indexOf({{ $agent->id }}), 1) : form.agents.push({{ $agent->id }})"
                                        id="agent_{{ $agent->id }}">
                                    <label class="form-check-label" for="agent_{{ $agent->id }}" style="font-size:13px;">
                                        {{ $agent->username }}
                                        <span style="font-size:11px;color:#94a3b8;">· {{ $agent->email }}</span>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light rounded-3 px-4" @click="showModal = false" style="font-size:13px;">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-5 fw-bold" style="font-size:13px;" x-text="isEdit ? 'Simpan' : 'Buat Divisi'"></button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal-backdrop fade" :class="showModal ? 'show d-block' : ''" x-show="showModal" x-cloak></div>

</div>
@endsection
