@extends('admin.layouts.app')

@section('title', 'Create book')
@section('eyebrow', 'New book')

@section('content')
    <section class="admin-panel admin-form-panel">
        <div class="admin-panel-head">
            <div>
                <span class="admin-kicker">Book details</span>
                <h2>Create a new book</h2>
            </div>
            <a class="admin-link-button" href="{{ route('admin.books.index') }}">Back to books</a>
        </div>

        <form class="admin-form" method="POST" action="{{ route('admin.books.store') }}" enctype="multipart/form-data">
            @csrf

            <label>
                <span>Title <strong style="color:#e74c3c;">*</strong></span>
                <input type="text" name="title" value="{{ old('title') }}" required maxlength="255">
                @error('title') <small>{{ $message }}</small> @enderror
            </label>

            <label>
                <span>Slug <small style="color:#999;font-weight:400;">(auto-generated from title)</small></span>
                <input type="text" name="slug" value="{{ old('slug') }}" maxlength="255" placeholder="leave empty to auto-generate">
                @error('slug') <small>{{ $message }}</small> @enderror
            </label>

            <label>
                <span>Subtitle</span>
                <input type="text" name="subtitle" value="{{ old('subtitle') }}" maxlength="255">
                @error('subtitle') <small>{{ $message }}</small> @enderror
            </label>

            <label>
                <span>Short description</span>
                <textarea name="short_description" rows="2" maxlength="500">{{ old('short_description') }}</textarea>
                @error('short_description') <small>{{ $message }}</small> @enderror
            </label>

            <label>
                <span>Description</span>
                <textarea name="description" rows="5">{{ old('description') }}</textarea>
                @error('description') <small>{{ $message }}</small> @enderror
            </label>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <label>
                    <span>Author</span>
                    <select name="author_id">
                        <option value="">— None —</option>
                        @foreach($authors as $author)
                            <option value="{{ $author->id }}" @selected((int) old('author_id') === $author->id)>{{ $author->name }}</option>
                        @endforeach
                    </select>
                    @error('author_id') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Category</span>
                    <select name="category_id">
                        <option value="">— None —</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected((int) old('category_id') === $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Language</span>
                    <select name="language_id">
                        <option value="">— None —</option>
                        @foreach($languages as $lang)
                            <option value="{{ $lang->id }}" @selected((int) old('language_id') === $lang->id)>{{ $lang->name }} ({{ $lang->code }})</option>
                        @endforeach
                    </select>
                    @error('language_id') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Difficulty</span>
                    <select name="difficulty">
                        <option value="">— None —</option>
                        @foreach(['beginner', 'elementary', 'intermediate', 'upper_intermediate', 'advanced'] as $level)
                            <option value="{{ $level }}" @selected(old('difficulty') === $level)>{{ ucfirst(str_replace('_', ' ', $level)) }}</option>
                        @endforeach
                    </select>
                    @error('difficulty') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Access type</span>
                    <select name="access_type">
                        <option value="public" @selected(old('access_type', 'public') === 'public')>Public</option>
                        <option value="premium" @selected(old('access_type') === 'premium')>Premium</option>
                        <option value="private" @selected(old('access_type') === 'private')>Private</option>
                    </select>
                    @error('access_type') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Status</span>
                    <select name="status">
                        <option value="draft" @selected(old('status', 'draft') === 'draft')>Draft</option>
                        <option value="ready" @selected(old('status') === 'ready')>Ready</option>
                        <option value="published" @selected(old('status') === 'published')>Published</option>
                        <option value="archived" @selected(old('status') === 'archived')>Archived</option>
                    </select>
                    @error('status') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Owner (user ID)</span>
                    <input type="number" name="owner_id" value="{{ old('owner_id') }}" placeholder="User ID">
                    @error('owner_id') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Published at</span>
                    <input type="datetime-local" name="published_at" value="{{ old('published_at') }}">
                    @error('published_at') <small>{{ $message }}</small> @enderror
                </label>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <label>
                    <span>Cover image</span>
                    <input type="file" name="cover" accept="image/jpeg,image/png,image/webp">
                    @error('cover') <small>{{ $message }}</small> @enderror
                    <small style="color:#999;font-weight:400;text-transform:none;letter-spacing:0;">JPG, PNG, WebP up to 5 MB</small>
                </label>

                <label style="margin-top:22px;">
                    <span style="display:flex;align-items:center;gap:8px;">
                        <input type="hidden" name="is_featured" value="0">
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured'))>
                        Featured book
                    </span>
                </label>
            </div>

            <div class="admin-form-actions">
                <button class="admin-primary-button" type="submit">Create book</button>
                <a href="{{ route('admin.books.index') }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
