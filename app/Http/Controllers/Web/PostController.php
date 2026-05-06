<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::where('is_active', true)
            ->orderByDesc('published_at')
            ->paginate(9);

        return view('web.posts.index', [
            'posts' => $posts
        ]);
    }

    public function show($slug)
    {
        $post = Post::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $related = Post::where('id', '!=', $post->id)
            ->where('is_active', true)
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        return view('web.posts.show', [
            'post' => $post,
            'related' => $related
        ]);
    }
}