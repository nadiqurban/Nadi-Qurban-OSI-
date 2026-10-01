<?php

namespace Database\Seeders;

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\Order;
use App\Support\DocumentRegistry;
use Illuminate\Database\Seeder;

/** Dokumen: register existing system files + one uploaded participant list (CSV). */
class DemoDocumentSeeder extends Seeder
{
    public function run(): void
    {
        app(DocumentRegistry::class)->sync();

        if (Document::query()->where('name', 'Senarai Peserta Musim 2027.csv')->exists()) {
            return;
        }

        $csv = "No. Tempahan,Pelanggan,Servis,Haiwan,Kuantiti\n";
        Order::query()->with('customer')->limit(200)->get()->each(function (Order $o) use (&$csv) {
            $csv .= implode(',', [$o->order_no, '"'.str_replace('"', '', $o->customer->name).'"', $o->service->label(), $o->animal->label(), $o->quantity])."\n";
        });

        $doc = Document::query()->create([
            'name' => 'Senarai Peserta Musim 2027.csv',
            'category' => DocumentCategory::Report,
            'service' => 'Lain',
            'source' => 'upload',
            'extension' => 'csv',
            'size' => strlen($csv),
        ]);
        $media = $doc->addMediaFromString($csv)->usingFileName('senarai-peserta-2027.csv')->toMediaCollection('file');
        $doc->update(['media_id' => $media->id]);
    }
}
