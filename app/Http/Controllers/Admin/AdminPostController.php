<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Support\Translation;

class AdminPostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::orderBy('order')->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.\"es\"')) LIKE ?", ['%' . $request->search . '%']);
            });
        }

        return Inertia::render('Admin/Posts/Index', [
            'posts'   => $query->paginate(20)->through(fn($p) => [
                'id'           => $p->id,
                'title'        => Translation::payload($p->getRawOriginal('title')),
                'slug'         => $p->slug,
                'category'     => Translation::payload($p->getRawOriginal('category')),
                'is_active'    => $p->is_active,
                'is_featured'  => $p->is_featured,
                'published_at' => $p->published_at?->format('d/m/Y'),
                'image_url'    => $p->image_url,
                'order'        => $p->order ?? 'A',
            ]),
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Posts/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required',
            'excerpt'      => 'nullable',
            'content'      => 'nullable',
            'category'     => 'nullable',
            'order'        => 'nullable|string|max:10',
            'is_active'    => 'boolean',
            'is_featured'  => 'boolean',
            'published_at' => 'nullable|date',
            'image'        => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('posts', 'public');
        }

        $data['title']       = Translation::input($request->input('title'));
        $data['excerpt']     = Translation::input($request->input('excerpt'));
        $data['content']     = Translation::input($request->input('content'));
        $data['category']    = Translation::input($request->input('category'));
        $data['slug']        = $this->uniqueSlug($data['title']['es']);
        $data['is_active']   = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured', false);
        $data['order']       = strtoupper(trim($data['order'] ?? 'A')) ?: 'A';

        Post::create($data);

        return redirect('/admin/posts')
            ->with('success', 'Novedad creada correctamente.');
    }

    public function edit(Post $post)
    {
        return Inertia::render('Admin/Posts/Edit', [
            'post' => [
                'id'           => $post->id,
                'title'        => Translation::payload($post->getRawOriginal('title')),
                'excerpt'      => Translation::payload($post->getRawOriginal('excerpt')),
                'content'      => Translation::payload($post->getRawOriginal('content')),
                'slug'         => $post->slug,
                'category'     => Translation::payload($post->getRawOriginal('category')),
                'image_url'    => $post->image_url,
                'is_active'    => $post->is_active,
                'is_featured'  => $post->is_featured,
                'published_at' => $post->published_at?->format('Y-m-d'),
                'order'        => $post->order ?? 'A',
            ],
        ]);
    }

    public function update(Request $request, Post $post)
    {
        $data = $request->validate([
            'title'        => 'required',
            'excerpt'      => 'nullable',
            'content'      => 'nullable',
            'category'     => 'nullable',
            'order'        => 'nullable|string|max:10',
            'is_active'    => 'boolean',
            'is_featured'  => 'boolean',
            'published_at' => 'nullable|date',
            'image'        => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('posts', 'public');
        } else {
            unset($data['image']);
        }

        $data['title']       = Translation::input($request->input('title'));
        $data['excerpt']     = Translation::input($request->input('excerpt'));
        $data['content']     = Translation::input($request->input('content'));
        $data['category']    = Translation::input($request->input('category'));
        $data['is_active']   = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured', false);
        $data['order']       = strtoupper(trim($data['order'] ?? 'A')) ?: 'A';

        $post->update($data);

        return redirect('/admin/posts')
            ->with('success', 'Novedad actualizada correctamente.');
    }

    public function destroy(Post $post)
    {
        $post->delete();
        return redirect('/admin/posts')
            ->with('success', 'Novedad eliminada.');
    }

    /* ---- Toggle mostrar en Home ---- */

    public function toggleHome(Post $post)
    {
        $post->update(['is_featured' => !$post->is_featured]);

        $msg = $post->is_featured
            ? 'Novedad agregada al home.'
            : 'Novedad quitada del home.';

        return back()->with('success', $msg);
    }

    /* ---- Actualizar orden inline (desde el Index) ---- */

    public function updateOrder(Request $request, Post $post)
    {
        $data = $request->validate(['order' => 'required|string|max:10']);
        $post->update(['order' => strtoupper(trim($data['order'])) ?: 'A']);
        return back()->with('success', 'Orden actualizado.');
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'novedad';
        $slug = $base;
        $counter = 2;

        while (Post::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
