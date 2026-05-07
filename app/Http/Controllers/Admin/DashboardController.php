<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Industry;
use App\Models\Post;
use App\Models\QuoteRequest;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Throwable;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard/Index', [
            'stats' => [
                'products'     => Product::count(),
                'categories'   => ProductCategory::count(),
                'industries'   => Industry::count(),
                'posts'        => Post::count(),
                'quotes'       => QuoteRequest::count(),
                'unread_quotes'=> QuoteRequest::where('is_read', 0)->count(),
                'messages'     => ContactMessage::count(),
                'unread_messages' => ContactMessage::where('is_read', 0)->count(),
                'subscribers'  => NewsletterSubscriber::count(),
            ],
            'recent_quotes'   => QuoteRequest::latest()->take(5)->get(),
            'recent_messages' => ContactMessage::latest()->take(5)->get(),
            'analytics' => $this->safeAnalyticsPayload(),
        ]);
    }

    private function safeAnalyticsPayload(): array
    {
        try {
            return $this->analyticsPayload();
        } catch (Throwable $exception) {
            Log::warning('Dashboard analytics unavailable.', [
                'message' => $exception->getMessage(),
            ]);

            return $this->emptyAnalyticsPayload();
        }
    }

    private function analyticsPayload(): array
    {
        if (
            !Schema::hasTable('analytics_sessions') ||
            !Schema::hasTable('analytics_hourly_summaries') ||
            !Schema::hasTable('analytics_blocked_bot_events')
        ) {
            return $this->emptyAnalyticsPayload();
        }

        $now = CarbonImmutable::now();
        $last24h = $now->subDay();
        $last7d = $now->subDays(7);
        $last30d = $now->subDays(30);

        $activeSessions = DB::table('analytics_sessions')
            ->where('is_bot', false)
            ->where('last_seen_at', '>=', $now->subMinutes(5))
            ->count();

        $avgDuration = (int) DB::table('analytics_sessions')
            ->where('is_bot', false)
            ->where('started_at', '>=', $last7d)
            ->where('duration_seconds', '>', 0)
            ->avg('duration_seconds');

        $blockedBots = (int) DB::table('analytics_blocked_bot_events')
            ->where('created_at', '>=', $last7d)
            ->count();

        $summary = DB::table('analytics_hourly_summaries')
            ->where('bucket_at', '>=', $last7d)
            ->selectRaw('SUM(page_views) as page_views, SUM(sessions) as sessions, SUM(unique_visitors) as visitors, SUM(engaged_seconds) as engaged_seconds')
            ->first();

        $timeSeries = DB::table('analytics_hourly_summaries')
            ->where('bucket_at', '>=', $last24h)
            ->selectRaw('bucket_at, SUM(page_views) as page_views, SUM(sessions) as sessions, SUM(blocked_bot_events) as blocked_bots')
            ->groupBy('bucket_at')
            ->orderBy('bucket_at')
            ->get()
            ->map(fn ($row) => [
                'label' => CarbonImmutable::parse($row->bucket_at)->format('H:i'),
                'page_views' => (int) $row->page_views,
                'sessions' => (int) $row->sessions,
                'blocked_bots' => (int) $row->blocked_bots,
            ]);

        $geo = DB::table('analytics_hourly_summaries')
            ->where('bucket_at', '>=', $last30d)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw('country, city, AVG(latitude) as latitude, AVG(longitude) as longitude, SUM(page_views) as page_views, SUM(sessions) as sessions')
            ->groupBy('country', 'city')
            ->orderByDesc(DB::raw('SUM(page_views)'))
            ->limit(80)
            ->get()
            ->map(fn ($row) => [
                'country' => $row->country,
                'city' => $row->city,
                'latitude' => (float) $row->latitude,
                'longitude' => (float) $row->longitude,
                'page_views' => (int) $row->page_views,
                'sessions' => (int) $row->sessions,
            ]);

        $heatmap = DB::table('analytics_hourly_summaries')
            ->where('bucket_at', '>=', $last30d)
            ->selectRaw('weekday, hour, SUM(page_views) as page_views')
            ->groupBy('weekday', 'hour')
            ->get()
            ->map(fn ($row) => [
                'weekday' => (int) $row->weekday,
                'hour' => (int) $row->hour,
                'value' => (int) $row->page_views,
            ]);

        $topPages = DB::table('analytics_hourly_summaries')
            ->where('bucket_at', '>=', $last7d)
            ->where('path', '!=', 'blocked-bots')
            ->selectRaw('path, SUM(page_views) as page_views, SUM(sessions) as sessions')
            ->groupBy('path')
            ->orderByDesc(DB::raw('SUM(page_views)'))
            ->limit(8)
            ->get();

        $sessions = DB::table('analytics_sessions')
            ->where('is_bot', false)
            ->where('last_seen_at', '>=', $now->subMinutes(30))
            ->orderByDesc('last_seen_at')
            ->limit(8)
            ->get([
                'session_id',
                'last_path',
                'city',
                'country',
                'device_type',
                'browser',
                'duration_seconds',
                'page_views',
                'last_seen_at',
            ])
            ->map(fn ($session) => [
                'session_id' => substr($session->session_id, 0, 8),
                'last_path' => $session->last_path,
                'location' => trim(collect([$session->city, $session->country])->filter()->join(', ')) ?: 'Sin geolocalizar',
                'device' => trim(collect([$session->device_type, $session->browser])->filter()->join(' / ')) ?: 'Desconocido',
                'duration_seconds' => (int) $session->duration_seconds,
                'page_views' => (int) $session->page_views,
                'last_seen_at' => CarbonImmutable::parse($session->last_seen_at)
                    ->timezone(config('app.timezone'))
                    ->locale('es')
                    ->diffForHumans(),
            ]);

        return [
            'kpis' => [
                'active_users' => $activeSessions,
                'avg_session_seconds' => $avgDuration,
                'blocked_bots' => $blockedBots,
                'page_views_7d' => (int) ($summary->page_views ?? 0),
                'sessions_7d' => (int) ($summary->sessions ?? 0),
                'visitors_7d' => (int) ($summary->visitors ?? 0),
                'engaged_seconds_7d' => (int) ($summary->engaged_seconds ?? 0),
            ],
            'time_series' => $timeSeries,
            'geo' => $geo,
            'heatmap' => $heatmap,
            'top_pages' => $topPages,
            'active_sessions' => $sessions,
        ];
    }

    private function emptyAnalyticsPayload(): array
    {
        return [
            'kpis' => [
                'active_users' => 0,
                'avg_session_seconds' => 0,
                'blocked_bots' => 0,
                'page_views_7d' => 0,
                'sessions_7d' => 0,
                'visitors_7d' => 0,
                'engaged_seconds_7d' => 0,
            ],
            'time_series' => [],
            'geo' => [],
            'heatmap' => [],
            'top_pages' => [],
            'active_sessions' => [],
        ];
    }
}
