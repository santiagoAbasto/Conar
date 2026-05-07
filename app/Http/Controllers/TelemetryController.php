<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TelemetryController extends Controller
{
    public function ingest(Request $request): JsonResponse
    {
        $payload = $request->json()->all() ?: json_decode($request->getContent(), true) ?: [];
        $validator = Validator::make($payload, [
            'visitor_id' => ['required', 'uuid'],
            'session_id' => ['required', 'uuid'],
            'event_type' => ['required', 'in:page_view,heartbeat,session_end'],
            'url' => ['nullable', 'string', 'max:2048'],
            'path' => ['required', 'string', 'max:512'],
            'title' => ['nullable', 'string', 'max:255'],
            'referrer' => ['nullable', 'string', 'max:2048'],
            'metadata' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false], 422);
        }

        $bot = $request->attributes->get('analytics_bot', ['is_bot' => false]);
        if (($bot['type'] ?? null) === 'benign') {
            return response()->json(['ok' => true, 'ignored' => true], 202);
        }

        $data = $validator->validated();
        $now = now();
        $geo = $request->attributes->get('analytics_geo', []);
        $agent = $this->parseAgent((string) $request->userAgent());
        $ipHash = $request->ip() ? hash('sha256', $request->ip().'|'.config('app.key')) : null;

        DB::table('analytics_events')->insert([
            'visitor_id' => $data['visitor_id'],
            'session_id' => $data['session_id'],
            'event_type' => $data['event_type'],
            'path' => Str::limit($data['path'], 512, ''),
            'url' => isset($data['url']) ? Str::limit($data['url'], 2048, '') : null,
            'referrer' => isset($data['referrer']) ? Str::limit($data['referrer'], 2048, '') : null,
            'title' => isset($data['title']) ? Str::limit($data['title'], 255, '') : null,
            'ip_hash' => $ipHash,
            'country_code' => $geo['country_code'] ?? null,
            'country' => $geo['country'] ?? null,
            'region' => $geo['region'] ?? null,
            'city' => $geo['city'] ?? null,
            'latitude' => $geo['latitude'] ?? null,
            'longitude' => $geo['longitude'] ?? null,
            'device_type' => $agent['device_type'],
            'browser' => $agent['browser'],
            'os' => $agent['os'],
            'is_bot' => (bool) ($bot['is_bot'] ?? false),
            'bot_type' => $bot['type'] ?? null,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'occurred_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->touchSession($data, $geo, $agent, $ipHash, $bot, $now);

        return response()->json(['ok' => true]);
    }

    private function touchSession(array $data, array $geo, array $agent, ?string $ipHash, array $bot, $now): void
    {
        $existing = DB::table('analytics_sessions')
            ->where('session_id', $data['session_id'])
            ->first(['started_at', 'page_views', 'heartbeat_count']);

        if (!$existing) {
            DB::table('analytics_sessions')->insert([
                'session_id' => $data['session_id'],
                'visitor_id' => $data['visitor_id'],
                'landing_path' => $data['path'],
                'last_path' => $data['path'],
                'referrer' => isset($data['referrer']) ? Str::limit($data['referrer'], 2048, '') : null,
                'ip_hash' => $ipHash,
                'country_code' => $geo['country_code'] ?? null,
                'country' => $geo['country'] ?? null,
                'region' => $geo['region'] ?? null,
                'city' => $geo['city'] ?? null,
                'latitude' => $geo['latitude'] ?? null,
                'longitude' => $geo['longitude'] ?? null,
                'device_type' => $agent['device_type'],
                'browser' => $agent['browser'],
                'os' => $agent['os'],
                'is_bot' => (bool) ($bot['is_bot'] ?? false),
                'bot_type' => $bot['type'] ?? null,
                'page_views' => $data['event_type'] === 'page_view' ? 1 : 0,
                'heartbeat_count' => $data['event_type'] === 'heartbeat' ? 1 : 0,
                'duration_seconds' => 0,
                'started_at' => $now,
                'last_seen_at' => $now,
                'ended_at' => $data['event_type'] === 'session_end' ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return;
        }

        $startedAt = \Carbon\Carbon::parse($existing->started_at);

        DB::table('analytics_sessions')
            ->where('session_id', $data['session_id'])
            ->update([
                'last_path' => $data['path'],
                'page_views' => DB::raw('page_views + '.($data['event_type'] === 'page_view' ? 1 : 0)),
                'heartbeat_count' => DB::raw('heartbeat_count + '.($data['event_type'] === 'heartbeat' ? 1 : 0)),
                'duration_seconds' => max(0, $startedAt->diffInSeconds($now)),
                'last_seen_at' => $now,
                'ended_at' => $data['event_type'] === 'session_end' ? $now : null,
                'updated_at' => $now,
            ]);
    }

    private function parseAgent(string $ua): array
    {
        $lower = strtolower($ua);

        return [
            'device_type' => str_contains($lower, 'mobile') ? 'mobile' : (str_contains($lower, 'tablet') ? 'tablet' : 'desktop'),
            'browser' => match (true) {
                str_contains($lower, 'edg/') => 'Edge',
                str_contains($lower, 'chrome/') => 'Chrome',
                str_contains($lower, 'safari/') && !str_contains($lower, 'chrome/') => 'Safari',
                str_contains($lower, 'firefox/') => 'Firefox',
                default => 'Other',
            },
            'os' => match (true) {
                str_contains($lower, 'windows') => 'Windows',
                str_contains($lower, 'mac os') => 'macOS',
                str_contains($lower, 'iphone') || str_contains($lower, 'ipad') => 'iOS',
                str_contains($lower, 'android') => 'Android',
                str_contains($lower, 'linux') => 'Linux',
                default => 'Other',
            },
        ];
    }
}
