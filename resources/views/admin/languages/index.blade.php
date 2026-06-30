@extends('admin.layouts.app')

@section('title', 'Languages')
@section('eyebrow', 'Supported languages')

@section('content')
    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <span class="admin-kicker">Reference</span>
                <h2>Languages</h2>
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
                        <th>Code</th>
                        <th>Name</th>
                        <th>Native name</th>
                        <th>Active</th>
                        <th>Translation</th>
                        <th>TTS</th>
                        <th>Books</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($languages as $language)
                        <tr>
                            <td>#{{ $language->id }}</td>
                            <td><strong>{{ $language->code }}</strong></td>
                            <td>
                                <form method="POST" action="{{ route('admin.languages.update', $language) }}" style="display:flex;gap:6px;align-items:center;">
                                    @csrf
                                    @method('PATCH')
                                    <input type="text" name="name" value="{{ $language->name }}" required style="width:140px;min-height:32px;padding:0 8px;border:1px solid rgba(28,39,66,.1);border-radius:6px;font:inherit;font-size:13px;">
                                    <input type="hidden" name="code" value="{{ $language->code }}">
                                    <input type="hidden" name="native_name" value="{{ $language->native_name }}">
                                    <input type="hidden" name="supports_translation" value="{{ $language->supports_translation ? '1' : '0' }}">
                                    <input type="hidden" name="supports_tts" value="{{ $language->supports_tts ? '1' : '0' }}">
                                    <input type="hidden" name="is_active" value="{{ $language->is_active ? '1' : '0' }}">
                                    <button type="submit" style="background:none;border:none;color:#3556d8;cursor:pointer;font:inherit;font-weight:700;font-size:12px;">Save</button>
                                </form>
                            </td>
                            <td>{{ $language->native_name ?? '—' }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.languages.toggle-active', $language) }}">
                                    @csrf
                                    <button type="submit" class="admin-badge {{ $language->is_active ? 'is-public' : '' }}" style="border:none;cursor:pointer;font-size:11px;">
                                        {{ $language->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td>{{ $language->supports_translation ? 'Yes' : 'No' }}</td>
                            <td>{{ $language->supports_tts ? 'Yes' : 'No' }}</td>
                            <td>{{ number_format($language->books_count) }}</td>
                            <td>
                                <div class="admin-row-actions">
                                    <form method="POST" action="{{ route('admin.languages.destroy', $language) }}" onsubmit="return confirm('Delete this language?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background:none;border:none;color:#e74c3c;cursor:pointer;font:inherit;font-weight:700;padding:0;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="admin-empty-cell">No languages yet. Run the seeder first.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-pagination">
            {{ $languages->links() }}
        </div>

        <div class="admin-panel" style="margin-top:24px;">
            <div class="admin-panel-head">
                <span class="admin-kicker">Create</span>
                <h2>Add a new language</h2>
            </div>

            <form class="admin-form" method="POST" action="{{ route('admin.languages.store') }}">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;align-items:end;">
                    <label>
                        <span>Code <strong style="color:#e74c3c;">*</strong></span>
                        <input type="text" name="code" value="{{ old('code') }}" required maxlength="10" placeholder="e.g. it">
                    </label>
                    <label>
                        <span>Name <strong style="color:#e74c3c;">*</strong></span>
                        <input type="text" name="name" value="{{ old('name') }}" required maxlength="100" placeholder="e.g. Italian">
                    </label>
                    <label>
                        <span>Native name</span>
                        <input type="text" name="native_name" value="{{ old('native_name') }}" maxlength="100" placeholder="e.g. Italiano">
                    </label>
                </div>
                <div style="display:flex;gap:20px;margin-top:12px;">
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#24304a;text-transform:none;letter-spacing:0;">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" checked>
                        Active
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#24304a;text-transform:none;letter-spacing:0;">
                        <input type="hidden" name="supports_translation" value="0">
                        <input type="checkbox" name="supports_translation" value="1">
                        Supports translation
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#24304a;text-transform:none;letter-spacing:0;">
                        <input type="hidden" name="supports_tts" value="0">
                        <input type="checkbox" name="supports_tts" value="1">
                        Supports TTS
                    </label>
                </div>
                <div class="admin-form-actions" style="margin-top:12px;">
                    <button class="admin-primary-button" type="submit">Add language</button>
                </div>
            </form>
        </div>
    </section>
@endsection
