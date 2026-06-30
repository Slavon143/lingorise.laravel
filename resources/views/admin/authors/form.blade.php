@extends('admin.layouts.app')

@section('title', $author ? 'Edit author' : 'Create author')
@section('eyebrow', $author ? 'Author details' : 'New author')

@section('content')
    <section class="admin-panel admin-form-panel">
        <div class="admin-panel-head">
            <div>
                <span class="admin-kicker">{{ $author ? 'Edit' : 'Create' }}</span>
                <h2>{{ $author ? $author->name : 'Add a new author' }}</h2>
            </div>
            <a class="admin-link-button" href="{{ route('admin.authors.index') }}">Back to authors</a>
        </div>

        <form class="admin-form" method="POST" action="{{ $author ? route('admin.authors.update', $author) : route('admin.authors.store') }}" enctype="multipart/form-data">
            @csrf
            @if($author) @method('PATCH') @endif

            <label>
                <span>Name <strong style="color:#e74c3c;">*</strong></span>
                <input type="text" name="name" value="{{ old('name', $author?->name) }}" required maxlength="255">
                @error('name') <small>{{ $message }}</small> @enderror
            </label>

            <label>
                <span>Slug <small style="color:#999;font-weight:400;">(auto-generated from name)</small></span>
                <input type="text" name="slug" value="{{ old('slug', $author?->slug) }}" maxlength="255">
                @error('slug') <small>{{ $message }}</small> @enderror
            </label>

            <label>
                <span>Bio</span>
                <textarea name="bio" rows="4" maxlength="2000">{{ old('bio', $author?->bio) }}</textarea>
                @error('bio') <small>{{ $message }}</small> @enderror
            </label>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                <label>
                    <span>Country</span>
                    <input type="text" name="country" value="{{ old('country', $author?->country) }}" maxlength="100">
                    @error('country') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Birth year</span>
                    <input type="number" name="birth_year" value="{{ old('birth_year', $author?->birth_year) }}" min="1800" max="{{ date('Y') }}">
                    @error('birth_year') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Death year</span>
                    <input type="number" name="death_year" value="{{ old('death_year', $author?->death_year) }}" min="1800" max="{{ date('Y') }}">
                    @error('death_year') <small>{{ $message }}</small> @enderror
                </label>
            </div>

            @if(!$author || !$author->photo_path)
                <label>
                    <span>Photo</span>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
                    @error('photo') <small>{{ $message }}</small> @enderror
                </label>
            @endif

            <div class="admin-form-actions">
                <button class="admin-primary-button" type="submit">{{ $author ? 'Save changes' : 'Create author' }}</button>
                <a href="{{ route('admin.authors.index') }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
