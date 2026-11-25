@extends('layouts.admin')

@section('pageTitle') {{ $announcement->exists ? 'Edit announcement' : 'New announcement' }} @endsection

@section('pageContent')
    <a href="{{ route('admin.announcements.index') }}" class="small text-decoration-none">&larr; Announcements</a>
    <h1 class="h3 mt-2 mb-4">{{ $announcement->exists ? 'Edit: ' . $announcement->title : 'New announcement' }}</h1>

    @include('partials.errors')

    <form method="POST" enctype="multipart/form-data" action="{{ $announcement->exists ? route('admin.announcements.update', $announcement) : route('admin.announcements.store') }}"
          data-validate novalidate>
        @csrf
        @if($announcement->exists) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-xl-7">
                <div class="card card-body border-0 shadow-sm mb-4">
                    <h2 class="h6 fw-bold mb-3">Message</h2>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="type">Type</label>
                            <select class="form-select" id="type" name="type">
                                @foreach(\App\Models\Announcement::TYPES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', $announcement->type) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="title">Title</label>
                            <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $announcement->title) }}" placeholder="e.g. Air Jordan 4 &quot;Bred&quot; drops Friday" data-rules="required|min:3|max:80">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="body">Short text (optional)</label>
                            <input type="text" class="form-control" id="body" name="body" value="{{ old('body', $announcement->body) }}" placeholder="e.g. Limited pairs, sizes 38-46" data-rules="max:160">
                            <div class="form-text">One line next to the title. Hidden on phones to keep the bar small.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="link">"Shop now" links to (optional)</label>
                            <select class="form-select" id="link" name="link">
                                <option value="">No link</option>
                                @foreach($linkGroups as $group => $options)
                                    @if($options->isNotEmpty())
                                        <optgroup label="{{ $group }}">
                                            @foreach($options as $value => $label)
                                                <option value="{{ $value }}" @selected(old('link', $announcement->linkKey()) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="image">Image (optional)</label>
                            @if($announcement->imageUrl())
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <img src="{{ $announcement->imageUrl() }}" alt="" class="rounded border admin-thumb">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                                        <label class="form-check-label" for="remove_image">Remove</label>
                                    </div>
                                </div>
                            @endif
                            <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png,.webp" data-rules="mimes:jpg,jpeg,png,webp|max_size:2048">
                            <div class="form-text">
                                Shown small and square next to the text. If the file name contains <strong>"background"</strong>
                                (e.g. <code>black-friday-background.jpg</code>), it fills the whole bar behind the text instead
                                (wide image, at least 1300 &times; 100 px). Max 2 MB.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card card-body border-0 shadow-sm mb-4">
                    <h2 class="h6 fw-bold mb-3">When and where</h2>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="starts_at">Show from</label>
                            <input type="datetime-local" class="form-control" id="starts_at" name="starts_at" value="{{ old('starts_at', $announcement->starts_at?->format('Y-m-d\TH:i')) }}" data-rules="required">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="ends_at">Show until</label>
                            <input type="datetime-local" class="form-control" id="ends_at" name="ends_at" value="{{ old('ends_at', $announcement->ends_at?->format('Y-m-d\TH:i')) }}" data-rules="required">
                        </div>
                        <div class="col-12 form-text mt-1">
                            The countdown runs to "Show until": "Drops in" for a drop, "Back in" for a restock, "Ends in" for a promo.
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="show_on">Show on</label>
                            <select class="form-select" id="show_on" name="show_on">
                                @foreach(\App\Models\Announcement::SHOW_ON as $value => $label)
                                    <option value="{{ $value }}" @selected(old('show_on', $announcement->show_on) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $announcement->is_active))>
                        <label class="form-check-label" for="is_active">Active (switch off to hide it without deleting)</label>
                    </div>
                </div>

                <button class="btn btn-dark w-100">{{ $announcement->exists ? 'Save' : 'Create' }}</button>
            </div>
        </div>
    </form>
@endsection
