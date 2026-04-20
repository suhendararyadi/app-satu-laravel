<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\Page;
use App\Models\Post;
use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;

class PublicSchoolController extends Controller
{
    public function home(Team $team): Response
    {
        abort_if($team->is_personal, 404);

        $posts = $team->posts()->published()->latest('published_at')->limit(3)->get();
        $pages = $team->pages()->published()->orderBy('sort_order')->get();

        return Inertia::render('public/home', [
            'school' => $team,
            'recentPosts' => $posts,
            'pages' => $pages,
        ]);
    }

    public function page(Team $team, Page $page): Response
    {
        abort_if($team->is_personal, 404);
        abort_if(! $page->is_published, 404);
        abort_if($page->team_id !== $team->id, 403);

        return Inertia::render('public/page-detail', [
            'school' => $team,
            'page' => $page,
        ]);
    }

    public function news(Team $team): Response
    {
        abort_if($team->is_personal, 404);

        $posts = $team->posts()->published()->latest('published_at')->paginate(9);

        return Inertia::render('public/news/index', [
            'school' => $team,
            'posts' => $posts,
        ]);
    }

    public function post(Team $team, Post $post): Response
    {
        abort_if($team->is_personal, 404);
        abort_if(! $post->is_published, 404);
        abort_if($post->team_id !== $team->id, 403);

        $post->load('author');

        return Inertia::render('public/news/show', [
            'school' => $team,
            'post' => $post,
        ]);
    }

    public function gallery(Team $team): Response
    {
        abort_if($team->is_personal, 404);

        $galleries = $team->galleries()->published()->withCount('images')->latest()->get();

        return Inertia::render('public/gallery/index', [
            'school' => $team,
            'galleries' => $galleries,
        ]);
    }

    public function galleryDetail(Team $team, Gallery $gallery): Response
    {
        abort_if($team->is_personal, 404);
        abort_if(! $gallery->is_published, 404);
        abort_if($gallery->team_id !== $team->id, 403);

        $gallery->load('images');

        return Inertia::render('public/gallery/show', [
            'school' => $team,
            'gallery' => $gallery,
        ]);
    }

    public function contact(Team $team): Response
    {
        abort_if($team->is_personal, 404);

        return Inertia::render('public/contact', [
            'school' => $team,
        ]);
    }
}
