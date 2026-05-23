@extends('layouts.admin_inventory.app')

@section('title', 'Kategori Produk')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Kategori Produk</h1>
            <p class="text-sm text-slate-500 mt-1">Kelola pengelompokan produk dan barang jadi.</p>
        </div>
        <div>
            <button onclick="openCreateModal()" class="px-4 py-2 bg-[#696cff] text-white rounded-lg hover:bg-[#5f61e6] transition-colors shadow-sm shadow-[#696cff]/30 text-sm font-medium flex items-center">
                <i class="bi bi-plus-lg mr-2"></i> Tambah Kategori
            </button>
        </div>
    </div>

    <!-- Alert Messages -->


    @if(session('error'))
        <div class="bg-[#ffe0db] text-[#ff3e1d] px-4 py-3 rounded-lg sneat-shadow flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="bi bi-exclamation-triangle-fill text-xl"></i>
                <span class="font-medium text-sm">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-[#ffe0db] text-[#ff3e1d] px-4 py-3 rounded-lg sneat-shadow">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Card Container -->
    <div class="sneat-card">
        <!-- Card Header -->
        <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <form action="{{ route('admin.inventory.master-categories.index') }}" method="GET" class="w-full md:w-1/3 flex gap-2">
                <div class="relative w-full">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <i class="bi bi-search"></i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama kategori..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-[#696cff] focus:border-[#696cff] outline-none transition-all bg-slate-50 focus:bg-white text-slate-600">
                </div>
                <button type="submit" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-colors text-sm font-medium">Cari</button>
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 border-collapse">
                <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-500 uppercase tracking-wider text-xs">
                    <tr>
                        <th class="px-6 py-4 font-semibold w-16 text-center">No</th>
                        <th class="px-6 py-4 font-semibold">Nama Kategori</th>
                        <th class="px-6 py-4 font-semibold text-center w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($categories as $index => $cat)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="px-6 py-4 text-center text-slate-500">{{ $categories->firstItem() + $index }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-[#e7e7ff] text-[#696cff] flex items-center justify-center">
                                        <i class="bi bi-tags"></i>
                                    </div>
                                    <span class="font-semibold text-slate-700">{{ $cat->name_category }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button onclick="openEditModal({{ $cat->category_id }}, '{{ $cat->name_category }}')" class="w-8 h-8 rounded bg-[#e7e7ff] text-[#696cff] hover:bg-[#696cff] hover:text-white flex items-center justify-center transition-colors" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form action="{{ route('admin.inventory.master-categories.destroy', $cat->category_id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded bg-[#ffe0db] text-[#ff3e1d] hover:bg-[#ff3e1d] hover:text-white flex items-center justify-center transition-colors" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="bi bi-inbox text-4xl mb-3 text-slate-300"></i>
                                    <p class="text-sm">Belum ada data kategori.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($categories->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $categories->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Tambah Kategori -->
<div id="createModal" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 transition-opacity duration-300 opacity-0 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 transform scale-95 transition-transform duration-300">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-slate-800">Tambah Kategori Baru</h3>
            <button onclick="closeCreateModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="bi bi-x-lg text-xl"></i>
            </button>
        </div>
        <form action="{{ route('admin.inventory.master-categories.store') }}" method="POST">
            @csrf
            <div class="p-6">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Kategori <span class="text-[#ff3e1d]">*</span></label>
                    <input type="text" name="name_category" required placeholder="Contoh: Essential Oil, Supplement" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#696cff]/50 focus:border-[#696cff] outline-none text-slate-600 text-sm">
                </div>
            </div>
            <div class="p-6 border-t border-slate-100 flex justify-end gap-3 bg-slate-50 rounded-b-xl">
                <button type="button" onclick="closeCreateModal()" class="px-4 py-2 text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 text-sm font-medium">Batal</button>
                <button type="submit" class="px-4 py-2 bg-[#696cff] text-white rounded-lg hover:bg-[#5f61e6] text-sm font-medium shadow-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div id="editModal" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 transition-opacity duration-300 opacity-0 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 transform scale-95 transition-transform duration-300">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-slate-800">Edit Kategori</h3>
            <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="bi bi-x-lg text-xl"></i>
            </button>
        </div>
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            <div class="p-6">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Kategori <span class="text-[#ff3e1d]">*</span></label>
                    <input type="text" id="edit_name_category" name="name_category" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#696cff]/50 focus:border-[#696cff] outline-none text-slate-600 text-sm">
                </div>
            </div>
            <div class="p-6 border-t border-slate-100 flex justify-end gap-3 bg-slate-50 rounded-b-xl">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 text-sm font-medium">Batal</button>
                <button type="submit" class="px-4 py-2 bg-[#696cff] text-white rounded-lg hover:bg-[#5f61e6] text-sm font-medium shadow-sm">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Animation for Modals
    function animateModalOpen(modalId) {
        const modal = document.getElementById(modalId);
        const card = modal.querySelector('div');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Trigger reflow
        void modal.offsetWidth;
        
        modal.classList.remove('opacity-0');
        card.classList.remove('scale-95');
        card.classList.add('scale-100');
    }

    function animateModalClose(modalId) {
        const modal = document.getElementById(modalId);
        const card = modal.querySelector('div');
        
        modal.classList.add('opacity-0');
        card.classList.remove('scale-100');
        card.classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }

    function openCreateModal() {
        animateModalOpen('createModal');
    }

    function closeCreateModal() {
        animateModalClose('createModal');
    }

    function openEditModal(id, name) {
        document.getElementById('edit_name_category').value = name;
        document.getElementById('editForm').action = "/admin/inventory/master-categories/" + id;
        animateModalOpen('editModal');
    }

    function closeEditModal() {
        animateModalClose('editModal');
    }
</script>
@endsection
