<?php

namespace App\Livewire\Documents;

use App\Enums\DocumentCategory;
use App\Enums\Module;
use App\Models\Document;
use App\Models\User;
use App\Support\Audit;
use App\Support\Settings;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Repositori Dokumen (Dokumen.dc.html): grid/list, stats, category rail with
 * counts, storage meter, search / service filter / sort, multi-file upload.
 *
 * @property-read LengthAwarePaginator<int, Document> $documents
 * @property-read array<string, int> $counts
 * @property-read array{used: int, quota: int, pct: int} $storage
 */
#[Layout('layouts::app')]
#[Title('Dokumen')]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    public const MIMES = 'pdf,doc,docx,xls,xlsx,csv,jpg,jpeg,png,webp,mp4,mov';

    #[Url(as: 'paparan', except: 'grid')]
    public string $view = 'grid';

    #[Url(as: 'kategori', except: '')]
    public string $category = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'servis', except: '')]
    public string $service = '';

    #[Url(as: 'susun', except: 'terkini')]
    public string $sort = 'terkini';

    public bool $showUpload = false;

    /** @var array<int, UploadedFile> */
    public array $files = [];

    public string $uploadCategory = 'perjanjian';

    public string $uploadService = 'Lain';

    public function updated(string $property): void
    {
        if (in_array($property, ['category', 'search', 'service', 'sort'], true)) {
            $this->resetPage();
        }
    }

    /** @return LengthAwarePaginator<int, Document> */
    #[Computed]
    public function documents(): LengthAwarePaginator
    {
        $term = trim($this->search);

        return Document::query()->visibleTo($this->user())
            ->when(DocumentCategory::tryFrom($this->category), fn (Builder $q, DocumentCategory $c) => $q->where('category', $c))
            ->when(in_array($this->service, Document::SERVICES, true), fn (Builder $q) => $q->where('service', $this->service))
            ->when($term !== '', fn (Builder $q) => $q->where('name', 'like', "%{$term}%"))
            ->when($this->sort === 'lama', fn (Builder $q) => $q->oldest()->oldest('id'))
            ->when($this->sort === 'nama', fn (Builder $q) => $q->orderBy('name'))
            ->when($this->sort === 'saiz', fn (Builder $q) => $q->orderByDesc('size'))
            ->when($this->sort === 'terkini', fn (Builder $q) => $q->latest()->latest('id'))
            ->paginate($this->view === 'list' ? 20 : 24);
    }

    /** @return array<string, int> category value => count ('' = all) */
    #[Computed]
    public function counts(): array
    {
        $counts = Document::query()->visibleTo($this->user())->select('category', DB::raw('COUNT(*) as n'))->groupBy('category')->pluck('n', 'category')->map(fn ($n) => (int) $n)->all();

        return ['' => array_sum($counts)] + $counts;
    }

    /** @return array{used: int, quota: int, pct: int} bytes */
    #[Computed]
    public function storage(): array
    {
        $used = (int) DB::table('media')->sum('size');
        $quota = (int) round((float) app(Settings::class)->get('documents.quota_gb', 50) * 1024 ** 3);

        return ['used' => $used, 'quota' => $quota, 'pct' => $quota > 0 ? (int) min(100, round($used / $quota * 100)) : 0];
    }

    /** @return list<array{icon: string, tone: string, value: string, label: string}> */
    public function stats(): array
    {
        $c = $this->counts;

        return [
            ['icon' => 'files', 'tone' => 'primary', 'value' => number_format($c['']), 'label' => 'Jumlah Fail'],
            ['icon' => 'certificate', 'tone' => 'gold', 'value' => number_format($c[DocumentCategory::Certificate->value] ?? 0), 'label' => 'Sijil'],
            ['icon' => 'file-text', 'tone' => 'info', 'value' => number_format($c[DocumentCategory::Report->value] ?? 0), 'label' => 'Laporan'],
            ['icon' => 'video', 'tone' => 'purple', 'value' => number_format(Document::query()->visibleTo($this->user())->whereIn('extension', ['mp4', 'mov'])->count()), 'label' => 'Video Pelaksanaan'],
        ];
    }

    public static function bytes(int $bytes): string
    {
        return $bytes >= 1024 ** 3 ? round($bytes / 1024 ** 3, 1).' GB' : ($bytes >= 1024 ** 2 ? round($bytes / 1024 ** 2, 1).' MB' : round($bytes / 1024).' KB');
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }

    public function openUpload(): void
    {
        $this->authorize(Module::Documents->managePermission());
        $this->resetErrorBag();
        $this->files = [];
        $this->uploadCategory = (DocumentCategory::tryFrom($this->category) ?? DocumentCategory::Agreement)->value;
        $this->uploadService = 'Lain';
        $this->showUpload = true;
    }

    public function removeFile(int $index): void
    {
        unset($this->files[$index]);
        $this->files = array_values($this->files);
    }

    public function upload(): void
    {
        $this->authorize(Module::Documents->managePermission());
        $this->validate([
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['file', 'mimes:'.self::MIMES, 'max:102400'],
            'uploadCategory' => ['required', Rule::enum(DocumentCategory::class)],
            'uploadService' => ['required', Rule::in(Document::SERVICES)],
        ], [], ['files' => 'Fail', 'files.*' => 'Fail']);

        /** @var User $actor */
        $actor = auth()->user();
        $count = 0;

        foreach ($this->files as $file) {
            DB::transaction(function () use ($file, $actor) {
                $name = $file->getClientOriginalName();
                $doc = Document::query()->create([
                    'name' => $name,
                    'category' => $this->uploadCategory,
                    'service' => $this->uploadService === 'Lain' ? Document::serviceFromName($name) : $this->uploadService,
                    'source' => 'upload',
                    'extension' => strtolower($file->getClientOriginalExtension() ?: $file->extension()),
                    'size' => $file->getSize(),
                    'uploaded_by' => $actor->id,
                ]);
                $media = $doc->addMedia($file)->usingFileName(uniqid('doc-').'.'.$file->extension())->toMediaCollection('file');
                $doc->update(['media_id' => $media->id]);
            });
            $count++;
        }

        Audit::log('document.uploaded', "{$count} fail dimuat naik ke Dokumen (".DocumentCategory::from($this->uploadCategory)->label().')', causer: $actor, logName: 'documents');

        $this->files = [];
        $this->showUpload = false;
        unset($this->documents, $this->counts, $this->storage);
        $this->dispatch('toast', message: "{$count} fail dimuat naik.");
    }

    public function delete(int $documentId): void
    {
        $this->authorize(Module::Documents->managePermission());
        $doc = Document::query()->visibleTo($this->user())->findOrFail($documentId);
        abort_if($doc->source !== 'upload', 403);

        /** @var User $actor */
        $actor = auth()->user();
        $doc->clearMediaCollection('file');
        $doc->delete();
        Audit::log('document.deleted', "Fail {$doc->name} dipadam dari Dokumen", $doc, causer: $actor, logName: 'documents');

        unset($this->documents, $this->counts, $this->storage);
        $this->dispatch('toast', message: "{$doc->name} dipadam.", tone: 'info');
    }

    public function render(): mixed
    {
        return view('livewire.documents.index', [
            'canManage' => auth()->user()?->can(Module::Documents->managePermission()) ?? false,
        ]);
    }
}
