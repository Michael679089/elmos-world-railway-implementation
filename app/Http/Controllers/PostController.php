<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Media;
use App\Models\User;
use App\Models\Tag;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Exists;

use function Laravel\Prompts\alert;

class PostController extends Controller
{

    private $trustedHosts = ['images.unsplash.com', 'plus.unsplash.com'];

    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        $category = $request->query('category');

        $posts = Post::query();

        if ($category) {
            $posts->whereHas('categories', function ($query) use ($category) {
                $query->where('slug', $category);
            });
        }

        $posts = $posts->whereNotNull('publication_date')
            ->with(['users', 'media', 'categories'])
            ->where('status', 'P')
            ->orderBy('publication_date', 'desc')
            ->paginate(10);

        $categories = Category::all();

        return view('posts.index', [
            'categories' => $categories,
            'posts' => $posts
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Log::info("Post.create - Showing post creation form.");

        $categories = Category::all();

        if (url()->previous() === route('dashboard')) {
            session(['post_return_url' => route('dashboard')]);
        } else {
            session(['post_return_url' => route('landing')]);
        }

        Log::info("Post.create - END");
        return view('posts.create', ['categories' => $categories]);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info("Post.store - poststore Function START");
        Log::info("Post.store - \$request values", $request->all());

        // Step 1: Validate inputs
        $validated_request_items = $request->validate(rules: [
            'post_title' => 'required|string|max:255',
            'post_content' => 'required|string',
            'post_image' => 'nullable|string',
            'post_categories' => 'nullable|array',
            'post_categories.*' => 'exists:categories,id',
            'post_tags' => 'nullable|array',
        ]);

        // Step 2: Slug (must be unique) Generator - then create the unique slug
        $tempSlug = Str::slug($validated_request_items['post_title']);
        $newSlug = $this->make_slug_unique_from_db_post($tempSlug);

        // Step 3: check if there are any categories & tags input from user.
        // 3 : Categories
        $has_any_categories_flag = false;
        if (empty($validated_request_items['post_categories'])) {
            Log::info("Post.store - user didn't put post categories");
        } else {
            $has_any_categories_flag = true;
            Log::info("Post.store - (optional) post category attached", [
                'category' => $validated_request_items['post_categories']
            ]);
        };
        // 3 : Tags
        $has_tags_flag = false;
        if (empty($validated_request_items['post_tags'])) {
            Log::info("Post.store - (optional) user didn't put post tags");
        } else {
            $has_tags_flag = true;
            Log::info(message: "Post.store - post tags attached", context: [
                'tag' => $validated_request_items['post_tags']
            ]);
        };

        // Step 4: Create the Post
        $user_id = Auth::id();

        $post = new Post();
        $post->title = $validated_request_items['post_title'];
        $post->slug = $newSlug;
        $post->status = 'D'; // D - Draft
        $post->featured_image_url = "https://picsum.photos/200/300";
        $post->content = $validated_request_items['post_content'];
        $post->last_modified_date = now();
        $post->user_id = $user_id;

        try {
            $is_post_successful = $post->save();

            if ($is_post_successful) {

                Log::info("Post.store - Post successfully published and saved to DB");

                // Step 5: After post save -> SYNC the categories and tags of the created post if user added tags and categories.
                if ($has_any_categories_flag) {
                    $post_categories_pivot_table = $post->categories();
                    $post_categories_pivot_table->sync($request->post_categories ?? []); // remove any rows related to this post and add these new ones instead.
                }
                if ($has_tags_flag) {

                    $tagIds = [];

                    foreach ($validated_request_items['post_tags'] as $value) {
                        $tag =  Tag::firstOrCreate(
                            ['tag_name' => $value],
                            ['slug' => Str::slug($value)]
                        );
                        $tagIds[] = $tag->id;
                    }

                    $post->tags()->sync($tagIds);
                }

                $media = new Media([
                    'url'         => $validated_request_items['post_image'] ?? null,
                    'file_name'   => fake()->title(),
                    'file_type'   => '.jpg',
                    'upload_date' => now(),
                    'description' => fake()->paragraph(),
                    'post_id'     => $post->id,
                ]);

                $media->save();
            } else {
                Log::warning("Post.store - Post.save() returned false");
                return redirect()->back()->with('success', 'Post stored successfully!');
            }

            // Step 6: Redirect back;
            Log::info("Post.store - Post Store Function END");
            return redirect()->back()->with('success', 'Post stored successfully!');
        } catch (\Exception $e) {
            Log::error("Post.store - Exception occurred: " . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        Log::info("Post.show - user is in show page post.$id");

        // Step 1: Retrieve the post or fail
        $post = Post::with('categories')->findOrFail($id);

        // Step 2: Increment view count
        $post->increment('views_count');

        // Step 3: Get other data
        $others = Post::with('categories')->inRandomOrder()->take(3)->get();
        $comments = $post->comments()->with('users')->get();
        $user_id = Auth::id();
        $user_object = User::find($user_id); // returns the User model or null

        $comments = $comments->where("post_id", $id);

        // Step 4: Return view
        return view('posts.show', [
            'post' => $post,
            'comments' => $comments,
            'others' => $others,
            'user_object_of_the_one_looking_at_this_page' => $user_object,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        Log::info("Post.edit - show edit Page Form START");

        // Step 1: Query the targeted post.
        $post_object = Post::findOrFail(id: $id);

        // Step 2: Check if post belongs to (AuthUser)
        $user_object_of_the_one_looking_at_this_page = Auth::user();
        $admin = Auth::user()->roles->contains('id', 1);

        $user_id_of_the_user_object = $user_object_of_the_one_looking_at_this_page->id;
        $post_object_user_id = $post_object->user_id;
        $isOwnerOfPost = $post_object_user_id === $user_id_of_the_user_object;

        // Step 3: If Fail Redirect user with error. If Not Continue editing post.
        if ($isOwnerOfPost || $admin) {
            // Step 4: User considered authorized. But we need user's role. Query the user's role object.
            $user_roles_of_user_object = $user_object_of_the_one_looking_at_this_page->roles;
            $user_role_object = $user_roles_of_user_object->first();

            // Step 5 : Send also all the available categories.
            $all_available_categories = Category::all();

            $post_tags = $post_object->tags;
            Log::info("Post.edit: $post_tags");

            // Step 6: Reflect the unchanged values to web page.
            Log::info("Post.edit: Edit function END");
            return view(view: "components.dashboard.posts.edit", data: [
                "post" => $post_object,
                "user_role" => $user_role_object,
                "categories" => $all_available_categories,
                "tags" => $post_object->tags,
            ]);
        } else {
            abort(403, 'Unauthorized action.');
            return;
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Log::info("Post.update - Update function START");
        Log::info("Post.update - \$request values", $request->all());
        Log::info("\n");

        // 1: Cleaning and Decode the JSON String post_tags;
        $tags = ($request->post_tags);

        // Step 2: Add any new tags if there are new words from the tag.
        Log::info("Post.update: Operation - adding new tag_names string to tags table");
        foreach ($tags as $tag_name) {
            $doesTagExist = Tag::where("tag_name", $tag_name)->exists();
            if ($doesTagExist == false) {
                Log::info("Post.update: adding new tag_name: $tag_name to tags table");
                $unique_sluggified_tag_name = $this->make_slug_unique_from_db_tag(Str::slug($tag_name));
                Tag::create([
                    "tag_name" => $tag_name,
                    "slug" => $unique_sluggified_tag_name,
                ]);
            } else {
                Log::info("Post.update: $tag_name already exists in tags table");
            }
        };
        // Step 3: Check if previous post tags are changed or not.
        $post = Post::find($id);

        // Step 3: Now that we have proper tags_id, validate request.
        $validated_request_items = null;
        try {
            $validated_request_items = $request->validate(rules: [
                'post_title' => 'required|string|max:255',
                'post_content' => 'nullable|string',
                'post_image' => 'nullable|string',
                'post_categories' => 'nullable|array',
                'post_categories.*' => 'exists:categories,id',
                'post_tags' => 'nullable|array',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Step 3: Handle validation errors
            Log::error("Post.update - Validation failed", [
                'errors' => $e->validator->errors()->all()
            ]);
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            // Step 4: Handle any other unexpected errors
            Log::error("Post.update - Unexpected error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Unexpected error occurred.');
        }

        // Step 4: stop operation if no validated.
        if ($validated_request_items == null) return;

        Log::info(message: "Post.update: Request validated");

        // Step 5: Check if (AuthUser) is owner of post.
        Log::info("Post.update: target post id $id");
        $user_id = Auth::id();
        $admin = Auth::user()->roles->contains('id', 1);

        $isOwnerOfPost = $post->user_id === $user_id;

        Log::info("Post.update: Is it owner of post?", [$isOwnerOfPost]);

        // Step 6: Checking if the properties were changed
        $is_post_title_changed = false;
        $is_post_content_changed = false;
        $is_post_featured_image_url_changed = false;
        $is_post_categories_changed = false;
        $is_post_tags_changed = false;


        if ($isOwnerOfPost || $admin) {

            try {
                $is_post_title_changed = $post->title !== $validated_request_items["post_title"];
                Log::info("Post.update: Is post_title changed", ['changed' => $is_post_title_changed]);
                $is_post_content_changed = $post->content !== $validated_request_items["post_content"];
                Log::info("Post.update: Is post_content changed", ['changed' => $is_post_content_changed]);
                $is_new_image_url_valid_link = $this->isValidImageLink($validated_request_items["post_image"]);
                Log::info("Post.update: Is valid image url link", [$is_new_image_url_valid_link]);


                $new_image_url_link = $post->media()->first();

                if ($new_image_url_link) {
                    $new_image_url_link->update([
                        'url'         => $validated_request_items['post_image'] ?? null,
                        'file_name'   => fake()->title(),
                        'file_type'   => '.jpg',
                        'upload_date' => now(),
                        'description' => fake()->paragraph(),
                    ]);
                }

                Log::info("Post.update: old_image_url_link: ", [$post->featured_image_url]);
                Log::info("Post.update: new_image_url_link: ", [$new_image_url_link]);
                $is_post_featured_image_url_changed = ($post->featured_image_url ?? "") !== ($validated_request_items["post_image"] ?? "");
                Log::info("Post.update: Is post_image_url changed", ['changed' => $is_post_featured_image_url_changed]);
                $is_post_tags_changed = $post->post_tags !== $validated_request_items["post_tags"];

                $old_post_category_ids = $post->categories->pluck('id')->toArray() ?? [];
                $new_post_category_ids = $validated_request_items["post_categories"] ?? [];
                $parsed_new_post_category_ids = array_map('intval', $new_post_category_ids);

                $old_sorted = collect($old_post_category_ids)->sort()->values()->toArray();
                $new_sorted = collect($parsed_new_post_category_ids)->sort()->values()->toArray();

                $is_post_categories_changed = $old_sorted !== $new_sorted;
                Log::info("Post.update: old-categories", ['category_ids' => $old_sorted]);
                Log::info("Post.update: new-categories", ['category_ids' => $new_sorted]);
                Log::info("Post.update: Is the post categories changed?", ['changed' => $is_post_categories_changed]);

                Log::info("Post.update: were post_tags changed?", ['changed' => $is_post_tags_changed]);
            } catch (\Exception $e) {
                Log::error("Post.update: Error checking the data: $e");
            }

            // Step 7: No errors found while checking, now update each property one by one.
            $is_there_any_changes = false;
            if ($is_post_title_changed) {
                $post->title = $validated_request_items["post_title"];
                Log::info("Post.update: updated post_title");
                $is_there_any_changes = true;
            };
            if ($is_post_content_changed) {
                $post->content = $validated_request_items["post_content"];
                Log::info("Post.update: updated post_content");
                $is_there_any_changes = true;
            };

            if ($is_post_featured_image_url_changed) {
                $new_image_url_link = $validated_request_items["post_image"] ?? "";
                $is_new_image_url_link_valid = $this->isValidImageLink($new_image_url_link);
                Log::info("Post.update: Url Link changed, but is image url link valid?", [$is_new_image_url_link_valid]);
                if ($is_new_image_url_link_valid) {
                    $post->featured_image_url = $validated_request_items["post_image"] ?? "";
                    Log::info("Post.update: URL Link is Valid = updated post_image");
                    $is_there_any_changes = true;
                } else {
                    Log::error("Post.update: ERROR: Image_url is broken, please changed it or keep it blank");
                    $is_there_any_changes = false;
                }
            };

            if ($is_post_categories_changed) {
                Log::info("Post.update: syncing the new post_categories");
                $post_categories_pivot_table = $post->categories();
                $post_categories_pivot_table->sync($validated_request_items["post_categories"]);
                $is_there_any_changes = true;
            };

            if ($is_post_tags_changed) {
                Log::info("Post.update: syncing the new post_tags");
                $tagIds = [];

                foreach ($validated_request_items['post_tags'] as $value) {
                    $tag =  Tag::firstOrCreate(
                        ['tag_name' => $value],
                        ['slug' => Str::slug($value)]
                    );
                    $tagIds[] = $tag->id;
                }

                $post->tags()->sync($tagIds);
                $is_there_any_changes = true;
            };

            if ($is_there_any_changes) {
                Log::info("Post.update: some properties of th post was changed");
                Log::info("Post.update: proceeding to save changed to post");
                $post->save();
            } else {
                Log::info("Post.update: NOTHING was changed");
            };

            return redirect()->route('dashboard.posts.show', $post->id)->with('success', 'successfully saved post.');
        } else {
            abort(403, 'Unauthorized action.');
            return;
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        Log::info("Post.delete - delete function START");
        Log::info("Post.delete - targeted id: $id");

        // Step 1: Find the post
        $post = Post::find($id);

        if (!$post) {
            Log::warning("Post.delete - Post with id $id not found.");
            return redirect()->route('landing')->with('error', 'Post not found.');
        }

        // Step 2: Make sure it's user's posts it's deleting
        $user = Auth::user();
        $admin = $user->roles->contains('id', 1);
        $user_id = $user ? $user->id : null;

        if ($post->user_id == $user_id || $admin) {
            // Step 3: Detach relationships (optional, depends on your DB setup)
            $post->comments()->delete();
            $post->media()->delete();
            $post->tags()->detach();
            $post->categories()->detach();

            // Step 4: Delete the post
            $post->delete();

            Log::info("Post.delete - Post with id $id deleted successfully.");
            Log::info("Post.delete - delete function END");

            // Step 4: Redirect to dashboard post list with success message
            return redirect()->route('dashboard.posts')->with('success', 'Post deleted successfully.');
        } else {
            abort(403, 'Unauthorized action.');
            return;
        }
    }



    ////// My other FUNCTIONS (not part of CRUD):



    protected function isValidImageLink(?string $url): bool
    {
        if (!$url) return true; // It's nullable
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';
        $path = $parsedUrl['path'] ?? '';

        // Step 2: Check if image ends with valid extensions
        foreach ($validExtensions as $ext) {
            if (str_ends_with(strtolower($path), ".$ext")) {
                return true;
            }
        }

        // Step 3: If failed, check which host it come from. - unsplash most trusted
        $trustedHosts = ['images.unsplash.com', 'plus.unsplash.com', ''];
        if (in_array($host, $trustedHosts)) {
            return true;
        }

        return false;
    }

    protected function make_slug_unique_from_db_post(String $newSlug): string
    {
        $counter = 1;
        $original = $newSlug;
        while (Post::where('slug', $newSlug)->exists()) {
            $newSlug = "{$original}-{$counter}";
            $counter++;
        }
        return $newSlug;
    }

    protected function make_slug_unique_from_db_tag(String $newSlug): string
    {
        $counter = 1;
        $original = $newSlug;
        while (Tag::where('slug', $newSlug)->exists()) {
            $newSlug = "{$original}-{$counter}";
            $counter++;
        }
        return $newSlug;
    }
}
