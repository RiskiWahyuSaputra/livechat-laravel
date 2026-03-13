@extends('layouts.admin_template')

@section('title', 'Edit Flow – ' . $flow->name)

@push('styles')
<style>
    #graph-editor { font-family: monospace; font-size: 13px; min-height: 400px; }
</style>
@endpush

@section('content')
<div x-data="flowEditor()" x-init="init()">
    <div class="row">
        <div class="col-sm-12">
            {{-- Header --}}
            <div class="card mb-3">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <a href="{{ route('admin.flows.index') }}" class="text-muted small"><i class="fe fe-arrow-left me-1"></i>Kembali</a>
                            <h4 class="mb-0 mt-1">{{ $flow->name }} <code class="small text-muted">{{ $flow->code }}</code></h4>
                            @if($flow->description)<p class="text-muted mb-0 small">{{ $flow->description }}</p>@endif
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            @if($flow->status === 'published')
                            <span class="badge bg-success">Published</span>
                            <form action="{{ route('admin.flows.publish', $flow) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="status" value="draft">
                                <button class="btn btn-outline-warning btn-sm"><i class="fe fe-eye-off me-1"></i>Ubah ke Draft</button>
                            </form>
                            @else
                            <span class="badge bg-secondary">Draft</span>
                            <form action="{{ route('admin.flows.publish', $flow) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="status" value="published">
                                <button class="btn btn-success btn-sm"><i class="fe fe-eye me-1"></i>Publish</button>
                            </form>
                            @endif
                            <button class="btn btn-primary btn-sm" @click="saveGraph()">
                                <i class="fe fe-save me-1"></i>Simpan Graph
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            {{-- Node/Edge JSON Editor --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Graph JSON Editor</h5>
                    <p class="text-muted small mb-0 mt-1">
                        Edit nodes dan edges langsung sebagai JSON. Node types: <code>START, MESSAGE, MENU, INPUT, SWITCH_FLOW, FALLBACK, END</code>.
                        Edge condition types: <code>always, user_choice, within_schedule, outside_schedule</code>.
                    </p>
                </div>
                <div class="card-body">
                    <div x-show="saveMsg" x-cloak class="alert alert-success mb-3" x-text="saveMsg"></div>
                    <div x-show="saveErr" x-cloak class="alert alert-danger mb-3" x-text="saveErr"></div>
                    <textarea id="graph-editor" class="form-control" rows="30" x-model="graphJson"></textarea>
                    <div class="mt-2 d-flex gap-2">
                        <button class="btn btn-primary btn-sm" @click="saveGraph()"><i class="fe fe-save me-1"></i>Simpan</button>
                        <button class="btn btn-outline-secondary btn-sm" @click="formatJson()"><i class="fe fe-code me-1"></i>Format JSON</button>
                    </div>
                </div>
            </div>

            {{-- Node/Edge Reference Tables --}}
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><h5 class="card-title mb-0">Nodes ({{ $flow->nodes->count() }})</h5></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead class="thead-light"><tr><th>ID</th><th>Code</th><th>Type</th></tr></thead>
                                    <tbody>
                                        @foreach($flow->nodes as $node)
                                        <tr>
                                            <td>{{ $node->id }}</td>
                                            <td><code>{{ $node->code }}</code></td>
                                            <td><span class="badge bg-light text-dark">{{ $node->type }}</span></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><h5 class="card-title mb-0">Edges ({{ $flow->edges->count() }})</h5></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead class="thead-light"><tr><th>From</th><th>To</th><th>Condition</th><th>Priority</th></tr></thead>
                                    <tbody>
                                        @foreach($flow->edges as $edge)
                                        <tr>
                                            <td>{{ $edge->fromNode->code ?? $edge->from_node_id }}</td>
                                            <td>{{ $edge->toNode->code ?? $edge->to_node_id }}</td>
                                            <td>
                                                <code>{{ $edge->condition_type }}</code>
                                                @if($edge->condition_value)
                                                <small class="text-muted">{{ json_encode($edge->condition_value) }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $edge->priority }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function flowEditor() {
    return {
        graphJson: '',
        saveMsg: '',
        saveErr: '',

        init() {
            this.graphJson = JSON.stringify(@json($graph), null, 2);
        },

        formatJson() {
            try {
                this.graphJson = JSON.stringify(JSON.parse(this.graphJson), null, 2);
            } catch(e) {
                this.saveErr = 'JSON tidak valid: ' + e.message;
            }
        },

        async saveGraph() {
            this.saveMsg = '';
            this.saveErr = '';

            try {
                JSON.parse(this.graphJson); // validate
            } catch(e) {
                this.saveErr = 'JSON tidak valid: ' + e.message;
                return;
            }

            const res = await fetch('{{ route('admin.flows.update', $flow) }}', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ graph: this.graphJson }),
            });

            const data = await res.json();
            if (data.success) {
                this.saveMsg = 'Graph berhasil disimpan!';
                setTimeout(() => location.reload(), 800);
            } else {
                this.saveErr = data.message ?? 'Gagal menyimpan.';
            }
        },
    };
}
</script>
@endpush
