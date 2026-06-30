@extends('admin.layouts.app')

@section('title', 'AI Structured Cache')
@section('eyebrow', 'Learning intelligence')

@section('content')
    <form method="GET" class="admin-filters" style="margin-bottom:16px">
        <select name="operation_type">
            <option value="">All types</option>
            @foreach($operationTypes as $t)
                <option value="{{ $t }}" @selected(request('operation_type') === $t)>{{ $t }}</option>
            @endforeach
        </select>
        <button type="submit">Filter</button>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Operation</th>
                <th>Source</th>
                <th>Language</th>
                <th>Hits</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $e)
                <tr>
                    <td>{{ $e->id }}</td>
                    <td>{{ $e->operation_type }}</td>
                    <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis">{{ $e->source_text }}</td>
                    <td>{{ $e->source_language }}→{{ $e->target_language ?? '—' }}</td>
                    <td>{{ $e->hits }}</td>
                    <td>{{ $e->created_at->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No cache entries yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $entries->links() }}
@endsection
