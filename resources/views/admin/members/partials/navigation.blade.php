<nav class="mb-5 flex flex-wrap gap-3" aria-label="Kelola customer dan group">
    <a class="admin-form-secondary" href="{{ route('admin.members.index') }}">Customer / Buyer</a>
    <a class="admin-form-secondary" href="{{ route('admin.members.index', ['registration' => 'pending']) }}">Permintaan registrasi ({{ $pendingCount }})</a>
    <a class="admin-form-secondary" href="{{ route('admin.customer-groups.index') }}">Label Group</a>
</nav>
