<?php

namespace App\Http\Controllers;

use App\Enums\Module;
use App\Models\ExecutionReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams execution evidence (photos/videos) from the private disk. Route is
 * signed + authenticated; vendor PICs only reach their own vendor's reports.
 */
class ExecutionMediaController
{
    public function __invoke(Request $request, Media $media): StreamedResponse
    {
        Gate::authorize(Module::Execution->viewPermission());

        abort_unless($media->model_type === (new ExecutionReport)->getMorphClass(), 404);

        /** @var User $user */
        $user = $request->user();
        $report = ExecutionReport::query()->findOrFail($media->model_id);

        abort_if($user->isVendorPic() && $report->vendor_id !== $user->vendor_id, 403);

        return response()->stream(function () use ($media) {
            $stream = $media->stream();
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $media->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($media->file_name).'"',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
