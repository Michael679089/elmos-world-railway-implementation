<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;

class LandingController extends Controller
{
    public function index()
    {
        // Set higher execution time limit for this request
        ini_set('max_execution_time', 300);

        $featuredPosts = Post::select(['id', 'title', 'content', 'publication_date', 'views_count', 'featured_post', 'user_id'])
            ->with(['users:id,name', 'categories:id,category_name'])
            ->where('featured_post', 1)
            ->where('status', 'P')
            ->orderBy('publication_date', 'desc')
            ->get();

        $latestPosts = Post::select(['id', 'title', 'content', 'publication_date', 'views_count', 'user_id'])
            ->with(['users:id,name', 'categories:id,category_name'])
            ->where('status', 'P')
            ->orderBy('publication_date', 'desc')
            ->paginate(10);

        $popularPosts = Post::select(['id', 'title', 'content', 'publication_date', 'views_count', 'user_id'])
            ->with(['users:id,name', 'categories:id,category_name'])
            ->where('status', 'P')
            ->orderBy('views_count', 'desc')
            ->take(10)
            ->get();

        $categories = cache()->remember('all_categories', 60 * 24, function () {
            return Category::all();
        });

        $is_user_logged_in = false;

        if (Auth::id()) {
            $is_user_logged_in = true;
        }


        return view(
            'landing',
            [
                'featuredPosts' => $featuredPosts,
                'latestPosts' => $latestPosts,
                'popularPosts' => $popularPosts,
                'categories' => $categories,

                // So I can check in landing page if user is logged in using Javascript
                'is_user_logged_in' => $is_user_logged_in,
            ]
        );
    }
}
