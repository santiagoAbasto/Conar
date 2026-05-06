<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

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
                'title'        => is_array($p->title) ? ($p->title['es'] ?? $p->title[array_key_first($p->title)] ?? '') : $p->title,
                'slug'         => $p->slug,
                'category'     => $p->category,
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
            'title'        => 'required|string|max:300',
            'excerpt'      => 'nullable|string|max:500',
            'content'      => 'nullable|string',
            'category'     => 'nullable|string|max:120',
            'order'        => 'nullable|string|max:10',
            'is_active'    => 'boolean',
            'is_featured'  => 'boolean',
            'published_at' => 'nullable|date',
            'image'        => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('posts', 'public');
        }

        $data['title']       = ['es' => $data['title']];
        $data['excerpt']     = isset($data['excerpt']) ? ['es' => $data['excerpt']] : null;
        $data['content']     = isset($data['content']) ? ['es' => $data['content']] : null;
        $data['slug']        = Str::slug($request->title);
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
                'title'        => is_array($post->title) ? ($post->title['es'] ?? '') : $post->title,
                'excerpt'      => is_array($post->excerpt) ? ($post->excerpt['es'] ?? '') : $post->excerpt,
                'content'      => is_array($post->content) ? ($post->content['es'] ?? '') : $post->content,
                'slug'         => $post->slug,
                'category'     => $post->category,
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
            'title'        => 'required|string|max:300',
            'excerpt'      => 'nullable|string|max:500',
            'content'      => 'nullable|string',
            'category'     => 'nullable|string|max:120',
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

        $data['title']       = ['es' => $data['title']];
        $data['excerpt']     = isset($data['excerpt']) ? ['es' => $data['excerpt']] : null;
        $data['content']     = isset($data['content']) ? ['es' => $data['content']] : null;
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
}
