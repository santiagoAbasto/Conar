<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class DetectBotMiddleware
{
    private const BENIGN_BOTS = [
        'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
        'yandexbot', 'facebookexternalhit', 'linkedinbot', 'twitterbot',
        'whatsapp', 'telegrambot',
    ];

    private const MALICIOUS_SIGNATURES = [
        'sqlmap', 'nikto', 'acunetix', 'nessus', 'masscan', 'nmap',
        'python-requests', 'curl/', 'wget/', 'go-http-client', 'java/',
        'libwww-perl', 'scrapy', 'httpclient', 'headlesschrome',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $classification = $this->classify($request);
        $mustResolveGeo = $request->is('api/telemetry') || ($classification['type'] ?? null) === 'malicious';
        $geo = $mustResolveGeo ? $this->resolveGeo($request->ip()) : [];

        $request->attributes->set('analytics_bot', $classification);
        $request->attributes->set('analytics_geo', $geo);

        if (($classification['type'] ?? null) === 'malicious') {
            $this->recordBlockedBot($request, $classification, $geo);
            abort(403, 'Forbidden');
        }

        return $next($request);
    }

    private function classify(Request $request): array
    {
        $ua = strtolower((string) $request->userAgent());
        $path = strtolower('/'.ltrim($request->path(), '/'));
        $query = strtolower((string) $request->getQueryString());

        foreach (self::BENIGN_BOTS as $bot) {
            if ($ua !== '' && str_contains($ua, $bot)) {
                return ['is_bot' => true, 'type' => 'benign', 'name' => $bot];
            }
        }

        foreach (self::MALICIOUS_SIGNATURES as $signature) {
            if ($ua !== '' && str_contains($ua, $signature)) {
                return ['is_bot' => true, 'type' => 'malicious', 'name' => $signature, 'reason' => 'user_agent'];
            }
        }

        $attackPattern = preg_match('/(\.\.\/|wp-admin|wp-login|phpmyadmin|\/vendor\/|\/\.env|union\+select|select.+from|<script|base64_decode)/i', $path.' '.$query);

        if ($attackPattern) {
            return ['is_bot' => true, 'type' => 'malicious', 'name' => 'attack-pattern', 'reason' => 'path_or_query'];
        }

        return ['is_bot' => false, 'type' => null, 'name' => null];
    }

    private function resolveGeo(?string $ip): array
    {
        if (!$ip || in_array($ip, ['127.0.0.1', '::1'], true)) {
            return [];
        }

        return Cache::remember('analytics_geo:'.sha1($ip), now()->addDay(), function () use ($ip) {
            if ($token = config('services.ipinfo.token')) {
                $response = Http::timeout(1.5)->acceptJson()->get("https://ipinfo.io/{$ip}/json", [
                    'token' => $token,
                ]);

                if ($response->ok()) {
                    $data = $response->json();
                    [$lat, $lng] = array_pad(explode(',', (string) ($data['loc'] ?? '')), 2, null);

                    return [
                        'country_code' => $data['country'] ?? null,
                        'country' => $data['country'] ?? null,
                        'region' => $data['region'] ?? null,
                        'city' => $data['city'] ?? null,
                        'latitude' => $lat !== null ? (float) $lat : null,
                        'longitude' => $lng !== null ? (float) $lng : null,
                    ];
                }
            }

            if ($key = config('services.ipstack.key')) {
                $response = Http::timeout(1.5)->acceptJson()->get("http://api.ipstack.com/{$ip}", [
                    'access_key' => $key,
                ]);

                if ($response->ok()) {
                    $data = $response->json();

                    return [
                        'country_code' => $data['country_code'] ?? null,
                        'country' => $data['country_name'] ?? null,
                        'region' => $data['region_name'] ?? null,
                        'city' => $data['city'] ?? null,
                        'latitude' => isset($data['latitude']) ? (float) $data['latitude'] : null,
                        'longitude' => isset($data['longitude']) ? (float) $data['longitude'] : null,
                    ];
                }
            }

            return [];
        });
    }

    private function recordBlockedBot(Request $request, array $classification, array $geo): void
    {
        try {
            if (!Schema::hasTable('analytics_blocked_bot_events')) {
                return;
            }

            DB::table('analytics_blocked_bot_events')->insert([
                'ip_hash' => $request->ip() ? hash('sha256', $request->ip().'|'.config('app.key')) : null,
                'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 512),
                'method' => $request->method(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
                'reason' => $classification['reason'] ?? $classification['name'] ?? 'bot_detected',
                'country_code' => $geo['country_code'] ?? null,
                'country' => $geo['country'] ?? null,
                'city' => $geo['city'] ?? null,
                'latitude' => $geo['latitude'] ?? null,
                'longitude' => $geo['longitude'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
            // Bot blocking must never make the public website unavailable.
        }
    }
}
