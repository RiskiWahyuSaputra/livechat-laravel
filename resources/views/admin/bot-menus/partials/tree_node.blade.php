<li class="position-relative">
    <!-- USER INPUT KOTAK KECIL -->
    <div class="user-input-box shadow-sm">
        <div class="input-header">User input</div>
        <div class="input-value">
            @if($menu->action_type === 'submenu') <i class="fas fa-bars text-secondary"></i> @endif
            @if($menu->action_type === 'link') <i class="fas fa-link text-danger"></i> @endif
            @if($menu->action_type === 'connect_cs') <i class="fas fa-headset text-success"></i> @endif
            {{ $menu->label }}
        </div>
    </div>

    <!-- BOT ACTION KOTAK BESAR -->
    <div class="bot-node-box shadow-sm border-0">
        <div class="node-header-band 
            {{ $menu->action_type === 'link' ? 'type-link' : '' }}
            {{ $menu->action_type === 'connect_cs' ? 'type-cs' : '' }}">
            <span>{{ strtoupper($menu->action_type) }}</span>
        </div>
        <div class="node-body">
            @if($menu->message_response)
                {{ Str::limit($menu->message_response, 100) }}
            @endif

            @if($menu->action_value)
                <div class="mt-2 p-1 bg-light rounded" style="font-size: 10px; word-break: break-all;">
                    <strong>Aksi/Tujuan:</strong> {{ $menu->action_value }}
                </div>
            @endif
        </div>
        <div class="node-actions-hover">
            @if($menu->action_type === 'submenu')
                <button type="button" @click="openCreate({{ $menu->id }})" class="btn-mini add border-success text-success fw-bold" title="Tambah Submenu Di Sini" style="padding: 4px 10px;">
                    <i class="fas fa-plus"></i> Submenu
                </button>
            @endif
            <button type="button" @click="openEdit({{ $menu->toJson() }})" class="btn-mini edit" title="Edit"><i class="fas fa-pen"></i></button>
            
            <form action="{{ route('admin.bot-menus.destroy', $menu->id) }}" method="POST" class="m-0 p-0" onsubmit="return confirm('Yakin hapus kotak ini beserta isi bawahnya?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn-mini delete" title="Hapus Permanen"><i class="fas fa-trash"></i></button>
            </form>
        </div>
    </div>

    <!-- REKURSIF ANAK CABANG -->
    @if($menu->action_type === 'submenu' && $menu->children && count($menu->children) > 0)
    <ul>
        @foreach($menu->children as $child)
            @include('admin.bot-menus.partials.tree_node', ['menu' => $child])
        @endforeach
    </ul>
    @endif
</li>
