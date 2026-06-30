@extends('layouts.app')

@section('title', 'Add a book')

@section('content')
    <section class="book-form-heading">
        <a href="{{ route('library.index') }}">← Back to library</a>
        <span class="dashboard-date">New material</span>
        <h1>Add something worth reading.</h1>
        <p>Upload a file or paste text. Your original file is processed locally and is not stored publicly.</p>
    </section>

    <form class="book-create-form" method="POST" action="{{ route('library.store') }}" enctype="multipart/form-data" data-book-create-form data-book-metadata-url="{{ route('library.metadata') }}">
        @csrf
        <div class="book-form-main">
            <section class="book-form-card">
                <div class="form-section-title"><span>01</span><div><h2>Book details</h2><p>Help us organise your library.</p></div></div>
                <div class="book-fields-grid">
                    <label class="form-field"><span>Title</span><input name="title" value="{{ old('title') }}" required placeholder="The Secret Garden" data-autofill-field="title">@error('title')<small class="field-error">{{ $message }}</small>@enderror</label>
                    <label class="form-field"><span>Author <small>optional</small></span><input name="author" value="{{ old('author') }}" placeholder="Frances Hodgson Burnett" data-autofill-field="author"></label>
                    <label class="form-field"><span>Category</span><input name="category" value="{{ old('category') }}" placeholder="Fiction, travel, business…" data-autofill-field="category"></label>
                    <label class="form-field"><span>Language</span><select name="language_locale" data-autofill-field="language_locale"><option value="en" @selected(old('language_locale', 'en') === 'en')>English</option><option value="de" @selected(old('language_locale') === 'de')>German</option><option value="es" @selected(old('language_locale') === 'es')>Spanish</option><option value="fr" @selected(old('language_locale') === 'fr')>French</option><option value="sv" @selected(old('language_locale') === 'sv')>Swedish</option></select></label>
                    <label class="form-field"><span>Level</span><select name="level" data-autofill-field="level">@foreach(['A1','A2','B1','B2','C1','C2'] as $level)<option value="{{ $level }}" @selected(old('level','A2')===$level)>{{ $level }}</option>@endforeach</select></label>
                    <label class="form-field"><span>Visibility</span><select name="visibility" data-autofill-field="visibility"><option value="private" @selected(old('visibility', 'private') === 'private')>Private</option><option value="public" @selected(old('visibility') === 'public')>Public library</option></select></label>
                    <label class="form-field book-cover-field">
                        <span>Custom cover <small>optional</small></span>
                        <input type="file" name="cover_file" accept=".jpg,.jpeg,.png,.webp">
                        <small>EPUB covers are detected automatically.</small>
                        @error('cover_file')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                </div>
            </section>

            <section class="book-form-card">
                <div class="form-section-title"><span>02</span><div><h2>Add the content</h2><p>Choose one of the two options.</p></div></div>
                <label class="file-drop-zone">
                    <input type="file" name="book_file" accept=".txt,.epub">
                    <span class="file-drop-icon">↑</span>
                    <strong>Drop a TXT or EPUB here</strong>
                    <small>or click to choose a file · up to 10 MB</small>
                    <span class="file-choose-button">Choose file</span>
                    <em data-file-name>No file selected</em>
                    <em class="file-autofill-status" data-file-autofill-status hidden></em>
                </label>
                @error('book_file')<small class="field-error">{{ $message }}</small>@enderror
                <div class="content-divider"><span>or paste text</span></div>
                <label class="form-field"><span>Book text</span><textarea name="content" rows="12" placeholder="Paste your story, article, or lesson here…">{{ old('content') }}</textarea>@error('content')<small class="field-error">{{ $message }}</small>@enderror</label>

                <button class="book-main-submit" type="submit">
                    Upload and prepare book <span>→</span>
                </button>
            </section>
        </div>
        <aside class="book-form-summary">
            <span class="section-kicker">Ready when you are</span>
            <h2>Your book stays yours.</h2>
            <ul><li>✓ Private by default</li><li>✓ Text prepared automatically</li><li>✓ Reading progress saved</li><li>✓ Words stay linked to context</li></ul>
            <button type="submit">Prepare my book <span>→</span></button>
            <small>Processing usually takes a few seconds.</small>
        </aside>
    </form>
@endsection
