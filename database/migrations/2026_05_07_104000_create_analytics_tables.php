<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->char('visitor_id', 36);
            $table->char('session_id', 36);
            $table->string('event_type', 32);
            $table->string('path', 512);
            $table->text('url')->nullable();
            $table->text('referrer')->nullable();
            $table->string('title', 255)->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('country', 96)->nullable();
            $table->string('region', 128)->nullable();
            $table->string('city', 128)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('device_type', 24)->nullable();
            $table->string('browser', 64)->nullable();
            $table->string('os', 64)->nullable();
            $table->boolean('is_bot')->default(false);
            $table->string('bot_type', 24)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            // Hot paths for ingestion-adjacent reads and retention jobs.
            $table->index('occurred_at', 'analytics_events_occurred_at_idx');
            $table->index(['session_id', 'occurred_at'], 'analytics_events_session_time_idx');
            $table->index(['visitor_id', 'occurred_at'], 'analytics_events_visitor_time_idx');
            $table->index(['event_type', 'occurred_at'], 'analytics_events_type_time_idx');
            $table->index(['is_bot', 'bot_type', 'occurred_at'], 'analytics_events_bot_time_idx');
            $table->index(['country_code', 'city'], 'analytics_events_geo_idx');
            $table->index('path', 'analytics_events_path_idx');
        });

        Schema::create('analytics_sessions', function (Blueprint $table) {
            $table->id();
            $table->char('session_id', 36)->unique();
            $table->char('visitor_id', 36);
            $table->string('landing_path', 512)->nullable();
            $table->string('last_path', 512)->nullable();
            $table->text('referrer')->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('country', 96)->nullable();
            $table->string('region', 128)->nullable();
            $table->string('city', 128)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('device_type', 24)->nullable();
            $table->string('browser', 64)->nullable();
            $table->string('os', 64)->nullable();
            $table->boolean('is_bot')->default(false);
            $table->string('bot_type', 24)->nullable();
            $table->unsignedInteger('page_views')->default(0);
            $table->unsignedInteger('heartbeat_count')->default(0);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['last_seen_at', 'is_bot'], 'analytics_sessions_active_idx');
            $table->index(['started_at', 'is_bot'], 'analytics_sessions_started_bot_idx');
            $table->index(['visitor_id', 'started_at'], 'analytics_sessions_visitor_started_idx');
            $table->index(['country_code', 'city'], 'analytics_sessions_geo_idx');
        });

        Schema::create('analytics_blocked_bot_events', function (Blueprint $table) {
            $table->id();
            $table->char('ip_hash', 64)->nullable();
            $table->string('path', 512)->nullable();
            $table->string('method', 12)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('reason', 96);
            $table->char('country_code', 2)->nullable();
            $table->string('country', 96)->nullable();
            $table->string('city', 128)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();

            $table->index('created_at', 'analytics_blocked_created_at_idx');
            $table->index(['ip_hash', 'created_at'], 'analytics_blocked_ip_time_idx');
        });

        Schema::create('analytics_hourly_summaries', function (Blueprint $table) {
            $table->id();
            $table->timestamp('bucket_at');
            $table->date('summary_date');
            $table->unsignedTinyInteger('weekday');
            $table->unsignedTinyInteger('hour');
            $table->string('path', 512)->default('site');
            $table->char('path_hash', 40)->default('site');
            $table->char('country_code', 2)->default('--');
            $table->string('country', 96)->default('Unknown');
            $table->string('city', 128)->default('Unknown');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('page_views')->default(0);
            $table->unsignedInteger('sessions')->default(0);
            $table->unsignedInteger('unique_visitors')->default(0);
            $table->unsignedInteger('heartbeats')->default(0);
            $table->unsignedInteger('engaged_seconds')->default(0);
            $table->unsignedInteger('blocked_bot_events')->default(0);
            $table->timestamps();

            $table->unique(
                ['bucket_at', 'path_hash', 'country_code', 'city'],
                'analytics_hourly_unique_dimension'
            );
            $table->index(['bucket_at', 'country_code'], 'analytics_hourly_bucket_country_idx');
            $table->index(['summary_date', 'hour'], 'analytics_hourly_date_hour_idx');
            $table->index(['weekday', 'hour'], 'analytics_hourly_weekday_hour_idx');
            $table->index('path_hash', 'analytics_hourly_path_hash_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_hourly_summaries');
        Schema::dropIfExists('analytics_blocked_bot_events');
        Schema::dropIfExists('analytics_sessions');
        Schema::dropIfExists('analytics_events');
    }
};
