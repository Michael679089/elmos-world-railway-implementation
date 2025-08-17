{{-- 
    Blade partial for rendering a single comment and its subcomments.
    Variables passed:
    - $current_comment
--}}

<div class="my-10">
    <div class="flex items-center justify-between w-full mb-2">
        <img src="https://i.pravatar.cc/40?u={{ $current_comment->user_id }}"
            alt="User avatar"
            class="w-10 h-10 rounded-full"
        />
        
    </div>
    <div class="flex justify-between items-center">
        <h3 class="font-semibold text-sm">{{ $current_comment->users->name }}</h3>
        <span class="flex gap-1 items-center text-sm">
            <svg name="clock-icon" class="h-2 w-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ date('M d, Y', strtotime($current_comment->created_at)) }}
        </span>
    </div>

    {{-- Comment Context --}}
    <div id="comment-{{ $current_comment->id }}">
        <div class="mt-1 text-gray-700 text-sm">
            <p>{{ $current_comment->comment_context }}</p>
        </div>
        
        {{-- Update Comment Form (Hidden First) --}}
        @if ($current_comment->user_id === $user_object_of_the_one_looking_at_this_page->id)
            <form action="{{ route('comments.update', $current_comment->id) }}" method="POST" class="hidden | columns-1">
                @csrf
                @method('PUT')
                <textarea
                    name="updated_comment_context"
                    placeholder="Write your comment here..."
                    class="w-full h-28 p-4 border border-gray-300 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                >{{$current_comment->comment_context}}</textarea>
                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-green-800 text-white rounded-2xl transition cursor-pointer">
                        Update Comment
                    </button>
                </div>
            </form>
        @endif
    </div>
    <div class="w-full flex items-center gap-3">
        <button onclick="toggleReplyForm({{ $current_comment->id }})" class="text-blue-500 text-sm flex gap-1 my-2 cursor-pointer">
            <x-heroicon-o-arrow-uturn-left class="w-6 h-6 text-gray-500" />
            Reply
        </button>

        {{-- ACTIONS --}}
        @if ($current_comment->user_id === $user_object_of_the_one_looking_at_this_page->id)
            {{-- Show this to only my users --}}
                <button type="submit" title="Edit Comment" id="initiate-edit-comment-{{ $current_comment->id }}">
                    <x-heroicon-c-pencil-square class="h-5 w-5 text-gray-400 cursor-pointer transition-colors hover:text-blue-500"/>
                </button>

                <form action="{{ route('comments.destroy', $current_comment->id) }}" method="POST" class="inline-block mt-1">
                    @csrf
                    @method('DELETE')
                    <button type="submit"><x-heroicon-s-trash class="h-5 w-5 text-gray-400 cursor-pointer transition-colors hover:text-red-500" /></button>
                </form>
        @endif
    </div>
                    
    {{-- Reply form (hidden) --}}
    <form id="reply-form-{{ $current_comment->id }}" action="{{ route('comments.reply') }}" method="POST" class="hidden mt-3">
        @csrf
        <div class="space-y-3">
            {{-- Get the user's content and leave --}}
            <input type="hidden" name="under_what_comment_id" value="{{ $current_comment->id }}">
            <input type="hidden" name="inside_what_post_id" value="{{ $post->id }}">
            <input type="hidden" name="post_id" value="{{ $post->id }}">
            <textarea
                name="comment_context"
                placeholder="Write your comment here..."
                class="w-full h-28 p-4 border border-gray-300 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
            ></textarea>
            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 bg-green-800 text-white rounded-2xl transition cursor-pointer">
                    Post Comment
                </button>
            </div>
        </div>
    </form>

    {{-- Recursive Comments section --}}
    {{-- Step 1: Get the list of comments using the current_comment --}}
    @php
        // Step 1: Get only the subcomments of current_comment.
        $listOfSubcomments = $comments->where("under_what_comment_id_is_this_comment", $current_comment->id);
    @endphp
    {{-- Step 2: Display the current list of comments using the current_comment --}}
    @foreach($listOfSubcomments as $subcomment)
        <div class="ml-5 pl-8 border-l-1 border-gray-400 ">
            <div class="w-full">
                @include('template.comment', [ "current_comment" => $subcomment])
            </div>
        </div>
    @endforeach
</div>
