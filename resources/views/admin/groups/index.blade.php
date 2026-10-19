<x-layouts.app title="Kelola Customer dan Group">
    <div class="[&_h1]:text-black!">
        <x-page-heading title="Kelola Customer dan Group" description="Label customer yang tersedia pada registrasi. Group tidak menentukan hak akses akun.">
            <x-slot:action><a class="admin-primary-action" href="{{ route('admin.customer-groups.create') }}">+ Tambah group</a></x-slot:action>
        </x-page-heading>
    </div>
    <div class="order-table-card before:bg-none!">
        @include('admin.members.partials.filters')
        @include('admin.members.partials.navigation')
        <div class="status-folder-content">
            <section class="status-folder-panel">
                <div class="status-folder-heading">
                    <div>
                        <h2>Label Group Customer</h2>
                        <p>Kelola label group dan lihat jumlah customer yang tergabung.</p>
                    </div>
                    <span class="bg-[#eef4ef]! text-[#275a3a]!">{{ $groups->total() }} group</span>
                </div>
                <div class="status-table-frame">
                    @include('admin.groups.partials.table')
                </div>
                <div class="order-table-footer"><span>Menampilkan {{ $groups->firstItem() ?? 0 }}–{{ $groups->lastItem() ?? 0 }} dari {{ $groups->total() }} group</span><div>{{ $groups->links() }}</div></div>
            </section>
        </div>
    </div>
</x-layouts.app>
