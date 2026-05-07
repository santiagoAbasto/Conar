<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const COLORS = [
        'caudal' => '#28597D',
        'nivel' => '#FD7F23',
        'presion' => '#075433',
        'temperatura' => '#960D14',
        'analisis' => '#B6AE27',
        'controladores de procesos' => '#188495',
        'indicacion y registro de datos' => '#431F7F',
        'productos especiales y servicios' => '#3D586D',
    ];

    private const PALETTE = [
        '#28597D',
        '#FD7F23',
        '#075433',
        '#960D14',
        '#B6AE27',
        '#188495',
        '#431F7F',
        '#3D586D',
    ];

    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->string('color', 7)->nullable()->after('description');
        });

        DB::table('product_categories')
            ->orderBy('order')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->values()
            ->each(function ($category, int $index) {
                $normalized = Str::of($category->name)->ascii()->lower()->squish()->toString();

                DB::table('product_categories')
                    ->where('id', $category->id)
                    ->update([
                        'color' => self::COLORS[$normalized] ?? self::PALETTE[$index % count(self::PALETTE)],
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
