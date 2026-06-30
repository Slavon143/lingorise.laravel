@extends('admin.layouts.app')

@section('title', 'Categories')
@section('eyebrow', 'Book categories')

@section('content')
    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <span class="admin-kicker">Taxonomy</span>
                <h2>Categories</h2>
            </div>
        </div>

        @if(session('status'))
            <div class="admin-flash" style="margin-bottom:16px;">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="admin-errors" style="margin-bottom:16px;">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Position</th>
                        <th>Active</th>
                        <th>Books</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>#{{ $category->id }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.categories.update', $category) }}" style="display:flex;gap:6px;align-items:center;">
                                    @csrf
                                    @method('PATCH')
                                    <input type="text" name="name" value="{{ $category->name }}" required style="width:160px;min-height:32px;padding:0 8px;border:1px solid rgba(28,39,66,.1);border-radius:6px;font:inherit;font-size:13px;">
                                    <input type="hidden" name="slug" value="{{ $category->slug }}">
                                    <input type="hidden" name="position" value="{{ $category->position }}">
                                    <input type="hidden" name="is_active" value="{{ $category->is_active ? '1' : '0' }}">
                                    <button type="submit" style="background:none;border:none;color:#3556d8;cursor:pointer;font:inherit;font-weight:700;font-size:12px;">Save</button>
                                </form>
                            </td>
                            <td>{{ $category->slug }}</td>
                            <td>{{ $category->position }}</td>
                            <td>
                                <span class="admin-badge {{ $category->is_active ? 'is-public' : '' }}">
                                    {{ $category->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ number_format($category->books_count) }}</td>
                            <td>
                                <div class="admin-row-actions">
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background:none;border:none;color:#e74c3c;cursor:pointer;font:inherit;font-weight:700;padding:0;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="admin-empty-cell">No categories yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-pagination">
            {{ $categories->links() }}
        </div>

        <div class="admin-panel" style="margin-top:24px;">
            <div class="admin-panel-head">
                <span class="admin-kicker">Create</span>
                <h2>Add a new category</h2>
            </div>

            <form class="admin-form" method="POST" action="{{ route('admin.categories.store') }}">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr 80px;gap:12px;align-items:end;">
                    <label>
                        <span>Name</span>
                        <input type="text" name="name" value="{{ old('name') }}" required maxlength="255">
                    </label>
                    <label>
                        <span>Description</span>
                        <input type="text" name="description" value="{{ old('description') }}" maxlength="1000">
                    </label>
                    <label>
                        <span>Position</span>
                        <input type="number" name="position" value="{{ old('position', 0) }}" min="0">
                    </label>
                </div>
                <input type="hidden" name="is_active" value="1">
                <div class="admin-form-actions" style="margin-top:12px;">
                    <button class="admin-primary-button" type="submit">Add category</button>
                </div>
            </form>
        </div>
    </section>
@endsection
