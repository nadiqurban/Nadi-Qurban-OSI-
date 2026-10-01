<?php

namespace App\Actions\Pipeline;

use App\Enums\Module;
use App\Enums\NotificationType;
use App\Enums\Severity;
use App\Models\Certificate;
use App\Models\Order;
use App\Models\User;
use App\Support\Audit;
use App\Support\Notifier;
use App\Support\Sequence;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Issues one certificate per participant (bahagian), numbered NQ-SIJIL-{year}-{seq4}.
 * Idempotent: existing certificates keep their number (name is refreshed).
 */
class IssueCertificates
{
    /**
     * @param  Collection<int, Order>  $orders
     * @return Collection<int, Certificate>
     */
    public function handle(Collection $orders, ?User $actor): Collection
    {
        return DB::transaction(function () use ($orders, $actor) {
            $issued = collect();
            $new = 0;

            foreach ($orders as $order) {
                $order->loadMissing(['participants', 'customer', 'certificates']);

                foreach ($order->participantNames() as $i => $name) {
                    $position = $i + 1;
                    $certificate = $order->certificates->firstWhere('position', $position);

                    if (! $certificate) {
                        $seq = Sequence::next('certificate-'.$order->year, 1);
                        $certificate = new Certificate([
                            'order_id' => $order->id,
                            'position' => $position,
                            'certificate_no' => sprintf('NQ-SIJIL-%d-%04d', $order->year, $seq),
                            'generated_by' => $actor?->id,
                            'generated_at' => now(),
                        ]);
                        $new++;
                    }

                    $certificate->recipient_name = mb_strtoupper($name ?: $order->customer->name);
                    $certificate->save();
                    $issued->push($certificate->setRelation('order', $order));
                }
            }

            if ($new > 0) {
                Audit::log('certificate.issued', "{$new} sijil dijana", severity: Severity::Info,
                    properties: ['orders' => $orders->pluck('order_no')->all()], causer: $actor, logName: 'orders');

                DB::afterCommit(fn () => Notifier::send(NotificationType::CertificateReady, Module::Certificates->viewPermission(),
                    'Sijil siap dijana', "{$new} sijil ".$orders->first()?->service->label().' sedia untuk dipos kepada pelanggan.',
                    route('shipping.index'), except: $actor));
            }

            return $issued;
        });
    }
}
