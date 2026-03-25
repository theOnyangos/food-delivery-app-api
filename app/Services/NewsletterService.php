<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendNewsletterJob;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class NewsletterService
{
    /**
     * Subscribe an email (or resubscribe if previously unsubscribed).
     */
    public function subscribe(string $email, ?string $name = null, ?string $source = null): NewsletterSubscriber
    {
        $subscriber = NewsletterSubscriber::query()->where('email', $email)->first();

        if ($subscriber !== null) {
            $subscriber->update([
                'name' => $name ?? $subscriber->name,
                'source' => $source ?? $subscriber->source,
                'unsubscribed_at' => null,
                'subscribed_at' => $subscriber->unsubscribed_at !== null ? now() : $subscriber->subscribed_at,
            ]);

            return $subscriber->fresh();
        }

        return NewsletterSubscriber::query()->create([
            'email' => $email,
            'name' => $name,
            'source' => $source,
            'subscribed_at' => now(),
        ]);
    }

    /**
     * List subscribers for admin (Yajra DataTables format). Optional filter: status (subscribed | unsubscribed).
     */
    public function getDataTables(Request $request): mixed
    {
        $query = NewsletterSubscriber::query()->orderByDesc('subscribed_at');

        $status = $request->query('status');
        if ($status === 'subscribed') {
            $query->subscribed();
        } elseif ($status === 'unsubscribed') {
            $query->whereNotNull('unsubscribed_at');
        }

        return DataTables::eloquent($query)
            ->addColumn('status_label', fn (NewsletterSubscriber $s) => $s->unsubscribed_at ? 'unsubscribed' : 'subscribed')
            ->addColumn('subscribed_at_formatted', fn (NewsletterSubscriber $s) => $s->subscribed_at?->format('Y-m-d H:i'))
            ->toJson();
    }

    /**
     * Mark subscriber as unsubscribed.
     */
    public function unsubscribe(NewsletterSubscriber $subscriber): void
    {
        $subscriber->update(['unsubscribed_at' => now()]);
    }

    /**
     * Queue sending the newsletter to all subscribed emails.
     */
    public function sendToAll(string $subject, string $bodyHtml): void
    {
        SendNewsletterJob::dispatch($subject, $bodyHtml);
    }
}
