<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnnouncementRequest;
use App\Models\Announcement;
use App\Models\Product;
use App\Models\ProductModel;
use App\Models\Promotion;
use App\Models\UserLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with('linkable')
            ->orderByDesc('ends_at')
            ->get()
            // running first, then upcoming, then the rest
            ->sortBy(fn ($a) => array_search($a->status(), ['running', 'upcoming', 'ended', 'off']))
            ->values();

        return view('pages.admin.announcements.index', compact('announcements'));
    }

    public function create()
    {
        return view('pages.admin.announcements.form', $this->formData(new Announcement([
            'type' => Announcement::TYPE_DROP,
            'show_on' => Announcement::SHOW_ALL,
            'is_active' => true,
            'starts_at' => now()->startOfHour(),
            'ends_at' => now()->addWeek()->endOfDay(),
        ])));
    }

    public function store(AnnouncementRequest $request)
    {
        $announcement = new Announcement($this->fields($request));
        $announcement->setLink($request->validated('link'));
        $this->saveImage($announcement, $request);
        $announcement->save();

        UserLog::record('announcement.created', 'Created announcement "' . $announcement->title . '"', $announcement);

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement created.');
    }

    public function edit(Announcement $announcement)
    {
        return view('pages.admin.announcements.form', $this->formData($announcement));
    }

    public function update(AnnouncementRequest $request, Announcement $announcement)
    {
        $announcement->fill($this->fields($request));
        $announcement->setLink($request->validated('link'));
        $this->saveImage($announcement, $request);
        $announcement->save();

        UserLog::record('announcement.updated', 'Updated announcement "' . $announcement->title . '"', $announcement);

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement saved.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        UserLog::record('announcement.deleted', 'Deleted announcement "' . $announcement->title . '"', $announcement);

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement deleted.');
    }

    private function fields(AnnouncementRequest $request): array
    {
        return $request->safe()->only(['type', 'title', 'body', 'show_on', 'starts_at', 'ends_at', 'is_active']);
    }

    private function formData(Announcement $announcement): array
    {
        return [
            'announcement' => $announcement,
            // One select with groups; option values are "product:12", "model:3", "promotion:5".
            'linkGroups' => [
                'Promotions' => Promotion::orderByDesc('ends_at')->get()->mapWithKeys(fn ($p) => ['promotion:' . $p->id => $p->name]),
                'Models' => ProductModel::with('brand')->orderBy('name')->get()->mapWithKeys(fn ($m) => ['model:' . $m->id => $m->brand->name . ' ' . $m->name]),
                'Products' => Product::orderBy('name')->get()->mapWithKeys(fn ($p) => ['product:' . $p->id => $p->name]),
            ],
        ];
    }

    // Stores a new thumbnail (or removes it) and deletes the old uploaded file (seeded URLs are left alone).
    private function saveImage(Announcement $announcement, AnnouncementRequest $request): void
    {
        $replace = $request->hasFile('image');

        if (!$replace && !$request->validated('remove_image')) {
            return;
        }

        if ($announcement->image && !Str::startsWith($announcement->image, ['http://', 'https://'])) {
            Storage::disk('public')->delete($announcement->image);
        }

        // Saved in storage/app/public/announcements (visible after "php artisan storage:link").
        $announcement->image = $replace ? $this->storeImage($request->file('image')) : null;
    }

    // Normally a random file name. When the uploaded file's name contains "background" (e.g. "black-friday-background.jpg"),
    // the saved name starts with "background-", so the bar shows it as the slide background (Announcement::imageIsBackground()).
    // The rule lives in the file name, so no extra database column is needed.
    private function storeImage(UploadedFile $file): string
    {
        if (!Str::contains(Str::lower($file->getClientOriginalName()), Announcement::BACKGROUND_MARK)) {
            return $file->store('announcements', 'public');
        }

        $name = Announcement::BACKGROUND_MARK . '-' . Str::random(32) . '.' . $file->extension();

        return $file->storeAs('announcements', $name, 'public');
    }
}
