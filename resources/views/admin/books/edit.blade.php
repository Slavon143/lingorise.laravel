@extends('admin.layouts.app')

@section('title', 'Edit book #'.$managedBook->id)
@section('eyebrow', 'Metadata update')

@section('content')
    <section class="admin-panel admin-form-panel">
        <div class="admin-panel-head">
            <div>
                <span class="admin-kicker">Allowed fields</span>
                <h2>{{ $managedBook->title }}</h2>
            </div>
            <a class="admin-link-button" href="{{ route('admin.books.show', $managedBook) }}">View book</a>
        </div>

        <form class="admin-form" method="POST" action="{{ route('admin.books.update', $managedBook) }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <label>
                <span>Title <strong style="color:#e74c3c;">*</strong></span>
                <input type="text" name="title" value="{{ old('title', $managedBook->title) }}" required maxlength="255">
                @error('title') <small>{{ $message }}</small> @enderror
            </label>

            <label>
                <span>Slug</span>
                <input type="text" name="slug" value="{{ old('slug', $managedBook->slug) }}" maxlength="255">
                @error('slug') <small>{{ $message }}</small> @enderror
            </label>

            <label>
                <span>Subtitle</span>
                <input type="text" name="subtitle" value="{{ old('subtitle', $managedBook->subtitle) }}" maxlength="255">
                @error('subtitle') <small>{{ $message }}</small> @enderror
            </label>

            <label>
                <span>Short description</span>
                <textarea name="short_description" rows="2" maxlength="500">{{ old('short_description', $managedBook->short_description) }}</textarea>
                @error('short_description') <small>{{ $message }}</small> @enderror
            </label>

            <label>
                <span>Description</span>
                <textarea name="description" rows="5">{{ old('description', $managedBook->description) }}</textarea>
                @error('description') <small>{{ $message }}</small> @enderror
            </label>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <label>
                    <span>Author</span>
                    <select name="author_id">
                        <option value="">— None —</option>
                        @foreach($authors as $author)
                            <option value="{{ $author->id }}" @selected((int) old('author_id', $managedBook->author_id) === $author->id)>{{ $author->name }}</option>
                        @endforeach
                    </select>
                    @error('author_id') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Category</span>
                    <select name="category_id">
                        <option value="">— None —</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected((int) old('category_id', $managedBook->category_id) === $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Language</span>
                    <select name="language_id">
                        <option value="">— None —</option>
                        @foreach($languages as $lang)
                            <option value="{{ $lang->id }}" @selected((int) old('language_id', $managedBook->language_id) === $lang->id)>{{ $lang->name }} ({{ $lang->code }})</option>
                        @endforeach
                    </select>
                    @error('language_id') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Difficulty</span>
                    <select name="difficulty">
                        <option value="">— None —</option>
                        @foreach(['beginner', 'elementary', 'intermediate', 'upper_intermediate', 'advanced'] as $level)
                            <option value="{{ $level }}" @selected(old('difficulty', $managedBook->difficulty) === $level)>{{ ucfirst(str_replace('_', ' ', $level)) }}</option>
                        @endforeach
                    </select>
                    @error('difficulty') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Access type</span>
                    <select name="access_type">
                        <option value="public" @selected(old('access_type', $managedBook->access_type ?? 'public') === 'public')>Public</option>
                        <option value="premium" @selected(old('access_type') === 'premium')>Premium</option>
                        <option value="private" @selected(old('access_type') === 'private')>Private</option>
                    </select>
                    @error('access_type') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Status</span>
                    <select name="status">
                        <option value="draft" @selected(old('status', $managedBook->processing_status) === 'draft')>Draft</option>
                        <option value="ready" @selected(old('status', $managedBook->processing_status) === 'ready')>Ready</option>
                        <option value="published" @selected(old('status', $managedBook->processing_status) === 'published')>Published</option>
                        <option value="archived" @selected(old('status', $managedBook->processing_status) === 'archived')>Archived</option>
                    </select>
                    @error('status') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Owner (user ID)</span>
                    <input type="number" name="owner_id" value="{{ old('owner_id', $managedBook->owner_id) }}" placeholder="User ID">
                    @error('owner_id') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Published at</span>
                    <input type="datetime-local" name="published_at" value="{{ old('published_at', $managedBook->published_at?->format('Y-m-d\TH:i')) }}">
                    @error('published_at') <small>{{ $message }}</small> @enderror
                </label>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <label>
                    <span>Cover image</span>
                    <input type="file" name="cover" accept="image/jpeg,image/png,image/webp">
                    @error('cover') <small>{{ $message }}</small> @enderror
                    <small style="color:#999;font-weight:400;text-transform:none;letter-spacing:0;">JPG, PNG, WebP up to 5 MB. Leave empty to keep current.</small>
                    @if($managedBook->cover_path)
                        <div style="margin-top:6px;">
                            <img src="{{ Storage::url($managedBook->cover_path) }}" alt="" style="max-width:80px;border-radius:4px;">
                        </div>
                    @endif
                </label>

                <label style="margin-top:22px;">
                    <span style="display:flex;align-items:center;gap:8px;">
                        <input type="hidden" name="is_featured" value="0">
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $managedBook->is_featured))>
                        Featured book
                    </span>
                </label>
            </div>

            <div class="admin-form-actions">
                <button class="admin-primary-button" type="submit">Save changes</button>
                <a href="{{ route('admin.books.show', $managedBook) }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
