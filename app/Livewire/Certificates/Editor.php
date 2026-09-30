<?php

namespace App\Livewire\Certificates;

use App\Enums\Module;
use App\Support\Audit;
use App\Support\CertificateTemplate;
use Illuminate\Http\UploadedFile;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Editor Sijil (Tempahan & Pelanggan.dc.html, "Sijil"): edit the certificate
 * template (saved in settings) with a live A5 preview. Sample values
 * (name, ibadah, …) only drive the preview and the sample PDF.
 */
#[Layout('layouts::app')]
#[Title('Editor Sijil')]
class Editor extends Component
{
    use WithFileUploads;

    /** @var array<string, string> */
    public array $form = [];

    /** @var UploadedFile|null */
    public $background = null;

    public function mount(CertificateTemplate $template): void
    {
        $this->form = $template->template() + CertificateTemplate::SAMPLE;
    }

    private function canManage(): bool
    {
        return auth()->user()?->can(Module::Certificates->managePermission()) ?? false;
    }

    public function save(CertificateTemplate $template): void
    {
        $this->authorize(Module::Certificates->managePermission());

        $this->validate([
            'form.title' => ['required', 'string', 'max:40'],
            'form.intro' => ['required', 'string', 'max:80'],
            'form.close' => ['required', 'string', 'max:60'],
            'form.jazak' => ['required', 'string', 'max:40'],
            'form.qr_url' => ['required', 'url:https,http', 'max:200'],
            'form.color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], [], [
            'form.title' => 'tajuk sijil', 'form.intro' => 'ayat pembuka', 'form.close' => 'ayat penghubung',
            'form.jazak' => 'ayat penutup', 'form.qr_url' => 'URL daftar', 'form.color' => 'warna',
        ]);

        $template->save($this->form);
        Audit::log('certificate.template', 'Templat sijil dikemaskini', causer: auth()->user(), logName: 'settings');
        $this->dispatch('toast', message: 'Templat sijil disimpan.');
    }

    public function updatedBackground(CertificateTemplate $template): void
    {
        $this->authorize(Module::Certificates->managePermission());

        $this->validate(['background' => ['image', 'mimes:png,jpg,jpeg,webp', 'max:5120']], [], ['background' => 'template sijil']);

        $template->setBackground($this->background->store('certificates', 'local'));
        $this->background = null;
        $this->dispatch('toast', message: 'Template sijil dimuat naik.');
    }

    public function removeBackground(CertificateTemplate $template): void
    {
        $this->authorize(Module::Certificates->managePermission());

        $template->setBackground(null);
    }

    public function resetTemplate(CertificateTemplate $template): void
    {
        $this->authorize(Module::Certificates->managePermission());

        $template->reset();
        $this->form = $template->template() + CertificateTemplate::SAMPLE;
        $this->resetValidation();
        $this->dispatch('toast', message: 'Templat sijil diset semula.');
    }

    public function download(CertificateTemplate $template): StreamedResponse
    {
        $this->authorize(Module::Certificates->viewPermission());

        return $template->download([$template->render($this->form)], 'Sijil-'.($this->form['certificate_no'] ?: 'Nadi-Qurban').'.pdf');
    }

    public function render(CertificateTemplate $template): mixed
    {
        return view('livewire.certificates.editor', [
            'canManage' => $this->canManage(),
            'certificate' => $template->render($this->form),
            'hasBackground' => $template->backgroundPath() !== null,
        ]);
    }
}
