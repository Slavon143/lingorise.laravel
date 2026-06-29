@extends('admin.layouts.app')

@section('title', 'Audit logs')
@section('eyebrow', 'Administrative trail')

@section('content')
    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <span class="admin-kicker">Traceability</span>
                <h2>Recent admin actions</h2>
            </div>
        </div>

        <form class="admin-filters" method="GET" action="{{ route('admin.audit-logs.index') }}">
            <label>
                <span>Action</span>
                <select name="action">
                    <option value="">All actions</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit">Filter</button>
            <a href="{{ route('admin.audit-logs.index') }}">Reset</a>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->created_at?->format('M d, Y H:i') }}</td>
                            <td>{{ $log->admin?->name ?? 'System' }}</td>
                            <td><span class="admin-badge is-admin">{{ $log->action }}</span></td>
                            <td>{{ class_basename($log->entity_type) }} #{{ $log->entity_id }}</td>
                            <td>{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="admin-empty-cell">No audit log entries yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-pagination">
            {{ $logs->links() }}
        </div>
    </section>
@endsection
