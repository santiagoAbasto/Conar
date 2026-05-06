<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HeroSlideController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Hero/Index', [
            'slides' => HeroSlide::orderBy('order')->get()->map(fn($s) => [
                'id'         => $s->id,
                'title'      => $s->title,
                'subtitle'   => $s->subtitle,
                'media_type' => $s->media_type,
                'media_url'  => $s->media_url,
                'video_url'  => $s->video_url,
                'is_active'  => $s->is_active,
                'order'      => $s->order,
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Hero/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'nullable|string|max:255',
            'subtitle'    => 'nullable|string|max:500',
            'content'     => 'nullable|string',
            'media_type'  => 'required|in:image,video',
            'media'       => 'nullable|file|max:20480',
            'video_url'   => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:100',
            'button_link' => 'nullable|string|max:500',
            'order'       => 'nullable|string|max:10',
            'is_active'   => 'boolean',
        ]);

        if ($request->hasFile('media')) {
            $data['media'] = $request->file('media')->store('hero', 'public');
        }

        $data['is_active'] = $request->boolean('is_active', true);
        HeroSlide::create($data);

        return redirect('/admin/hero')
            ->with('success', 'Slide creado correctamente.');
    }

    public function edit(HeroSlide $hero)
    {
        return Inertia::render('Admin/Hero/Edit', [
            'slide' => [
                'id'          => $hero->id,
                'title'       => $hero->title,
                'subtitle'    => $hero->subtitle,
                'content'     => $hero->content,
                'media_type'  => $hero->media_type,
                'media_url'   => $hero->media_url,
                'video_url'   => $hero->video_url,
                'button_text' => $hero->button_text,
                'button_link' => $hero->button_link,
                'order'       => $hero->order,
                'is_active'   => $hero->is_active,
            ],
        ]);
    }

    public function update(Request $request, HeroSlide $hero)
    {
        $data = $request->validate([
            'title'       => 'nullable|string|max:255',
            'subtitle'    => 'nullable|string|max:500',
            'content'     => 'nullable|string',
            'media_type'  => 'required|in:image,video',
            'media'       => 'nullable|file|max:20480',
            'video_url'   => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:100',
            'button_link' => 'nullable|string|max:500',
            'order'       => 'nullable|string|max:10',
            'is_active'   => 'boolean',
        ]);

        if ($request->hasFile('media')) {
            $data['media'] = $request->file('media')->store('hero', 'public');
        } else {
            unset($data['media']);
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $hero->update($data);

        return redirect('/admin/hero')
            ->with('success', 'Slide actualizado correctamente.');
    }

    public function destroy(HeroSlide $hero)
    {
        $hero->delete();
        return redirect('/admin/hero')
            ->with('success', 'Slide eliminado.');
    }
}
