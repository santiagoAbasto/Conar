<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Translation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminFloatingWhatsappController extends Controller
{
    public function edit()
    {
        $footer = json_decode(Setting::where('key', 'footer')->value('value') ?? '{}', true) ?: [];
        $settings = json_decode(Setting::where('key', 'floating_whatsapp')->value('value') ?? '{}', true) ?: [];

        $phone = Translation::text($settings['phone'] ?? ($footer['phone'] ?? '')) ?? '';
        $message = Translation::text($settings['message'] ?? 'Hola, quiero hacer una consulta desde la web de CONAR.') ?? '';
        $label = Translation::text($settings['label'] ?? 'WhatsApp') ?? 'WhatsApp';
        $isActive = array_key_exists('is_active', $settings) ? (bool) $settings['is_active'] : true;

        return Inertia::render('Admin/Extras/FloatingWhatsapp', [
            'whatsapp' => [
                'phone' => $phone,
                'message' => $message,
                'label' => $label,
                'is_active' => $isActive,
                'preview_url' => $this->buildWhatsappUrl($phone, $message),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:80',
            'message' => 'nullable|string|max:500',
            'label' => 'nullable|string|max:40',
            'is_active' => 'boolean',
        ]);

        $data['phone'] = trim($data['phone']);
        $data['message'] = trim($data['message'] ?? '');
        $data['label'] = trim($data['label'] ?? 'WhatsApp') ?: 'WhatsApp';
        $data['is_active'] = $request->boolean('is_active');

        Setting::updateOrCreate(
            ['key' => 'floating_whatsapp'],
            ['value' => json_encode($data)]
        );

        return back()->with('success', 'WhatsApp flotante actualizado correctamente.');
    }

    protected function buildWhatsappUrl(?string $phone, ?string $message): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        $url = 'https://wa.me/' . $digits;
        $message = trim((string) $message);

        return $message !== '' ? $url . '?text=' . rawurlencode($message) : $url;
    }
}
