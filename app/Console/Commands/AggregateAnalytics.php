<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AggregateAnalytics extends Command
{
    protected $signature = 'analytics:aggregate
        {--from= : Fecha inicial parseable por Carbon}
        {--to= : Fecha final parseable por Carbon}
        {--hours=24 : Ventana relativa cuando no se indica --from}';

    protected $description = 'Consolida visitas web en resúmenes por hora usando el horario de Argentina.';

    public function handle(): int
    {
        $to = $this->option('to')
            ? CarbonImmutable::parse($this->option('to'))->ceilHour()
            : CarbonImmutable::now()->ceilHour();

        $from = $this->option('from')
            ? CarbonImmutable::parse($this->option('from'))->floorHour()
            : $to->subHours((int) $this->option('hours'))->floorHour();

        if ($from->greaterThanOrEqualTo($to)) {
            $this->error('La fecha --from debe ser anterior a --to.');
            return self::FAILURE;
        }

        DB::transaction(function () use ($from, $to) {
            DB::table('analytics_hourly_summaries')
                ->whereBetween('bucket_at', [$from, $to])
                ->delete();

            $eventRows = DB::table('analytics_events')
                ->selectRaw("
                    TIMESTAMP(DATE_FORMAT(occurred_at, '%Y-%m-%d %H:00:00')) as bucket_at,
                    DATE(occurred_at) as summary_date,
                    WEEKDAY(occurred_at) as weekday,
                    HOUR(occurred_at) as hour,
                    COALESCE(NULLIF(path, ''), 'site') as path,
                    COALESCE(country_code, '--') as country_code,
                    COALESCE(country, 'Unknown') as country,
                    COALESCE(city, 'Unknown') as city,
                    AVG(latitude) as latitude,
                    AVG(longitude) as longitude,
                    SUM(event_type = 'page_view') as page_views,
                    COUNT(DISTINCT session_id) as sessions,
                    COUNT(DISTINCT visitor_id) as unique_visitors,
                    SUM(event_type = 'heartbeat') as heartbeats,
                    SUM(event_type = 'heartbeat') * 30 as engaged_seconds,
                    0 as blocked_bot_events
                ")
                ->where('is_bot', false)
                ->whereBetween('occurred_at', [$from, $to])
                ->groupByRaw("
                    TIMESTAMP(DATE_FORMAT(occurred_at, '%Y-%m-%d %H:00:00')),
                    DATE(occurred_at),
                    WEEKDAY(occurred_at),
                    HOUR(occurred_at),
                    COALESCE(NULLIF(path, ''), 'site'),
                    COALESCE(country_code, '--'),
                    COALESCE(country, 'Unknown'),
                    COALESCE(city, 'Unknown')
                ")
                ->get()
                ->map(fn ($row) => $this->normalizeRow((array) $row))
                ->all();

            $blockedRows = DB::table('analytics_blocked_bot_events')
                ->selectRaw("
                    TIMESTAMP(DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')) as bucket_at,
                    DATE(created_at) as summary_date,
                    WEEKDAY(created_at) as weekday,
                    HOUR(created_at) as hour,
                    'blocked-bots' as path,
                    COALESCE(country_code, '--') as country_code,
                    COALESCE(country, 'Unknown') as country,
                    COALESCE(city, 'Unknown') as city,
                    AVG(latitude) as latitude,
                    AVG(longitude) as longitude,
                    0 as page_views,
                    0 as sessions,
                    0 as unique_visitors,
                    0 as heartbeats,
                    0 as engaged_seconds,
                    COUNT(*) as blocked_bot_events
                ")
                ->whereBetween('created_at', [$from, $to])
                ->groupByRaw("
                    TIMESTAMP(DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')),
                    DATE(created_at),
                    WEEKDAY(created_at),
                    HOUR(created_at),
                    COALESCE(country_code, '--'),
                    COALESCE(country, 'Unknown'),
                    COALESCE(city, 'Unknown')
                ")
                ->get()
                ->map(fn ($row) => $this->normalizeRow((array) $row))
                ->all();

            $rows = array_merge($eventRows, $blockedRows);

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('analytics_hourly_summaries')->upsert(
                    $chunk,
                    ['bucket_at', 'path_hash', 'country_code', 'city'],
                    [
                        'summary_date', 'weekday', 'hour', 'path', 'country', 'latitude',
                        'longitude', 'page_views', 'sessions', 'unique_visitors',
                        'heartbeats', 'engaged_seconds', 'blocked_bot_events', 'updated_at',
                    ]
                );
            }
        });

        $this->info("Visitas agregadas desde {$from->toDateTimeString()} hasta {$to->toDateTimeString()} (horario Argentina).");

        return self::SUCCESS;
    }

    private function normalizeRow(array $row): array
    {
        $now = now();

        return [
            'bucket_at' => $row['bucket_at'],
            'summary_date' => $row['summary_date'],
            'weekday' => (int) $row['weekday'],
            'hour' => (int) $row['hour'],
            'path' => mb_substr((string) $row['path'], 0, 512),
            'path_hash' => sha1((string) $row['path']),
            'country_code' => (string) $row['country_code'],
            'country' => (string) $row['country'],
            'city' => (string) $row['city'],
            'latitude' => $row['latitude'] !== null ? (float) $row['latitude'] : null,
            'longitude' => $row['longitude'] !== null ? (float) $row['longitude'] : null,
            'page_views' => (int) $row['page_views'],
            'sessions' => (int) $row['sessions'],
            'unique_visitors' => (int) $row['unique_visitors'],
            'heartbeats' => (int) $row['heartbeats'],
            'engaged_seconds' => (int) $row['engaged_seconds'],
            'blocked_bot_events' => (int) $row['blocked_bot_events'],
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
