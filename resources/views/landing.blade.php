
@extends('layouts.master')
@section('title', 'landing')
    
@section('content')

@foreach ($featuredPosts as $post)
<div class="relative bg-cover bg-center fill p-10 rounded-2xl text-gray-50 min-h-100 flex flex-col-reverse" style="background-image: url('{{ $post->media->first()?->url }}')">
    <div class="relative z-10">
            <div class="text-md mb-2">Featured</div>
            <div class="text-5xl mb-1">{{$post->title}}</div>
            <div class="flex gap-5 items-center justify-center">
                <span class="flex-11/12">{{Str::limit($post->content,300)}}</span>
                <a class="flex-1/12 cursor-pointer" href="{{ route('posts.show', $post->id) }}">
                    <svg class="w-10 h-10 text-white hover:text-green-600 duration-200 transition ease-in-out hover:translate-x-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            
        </div>
    <div class="rounded-2xl absolute  inset-0 bg-gradient-to-t from-black/70 to-transparent pointer-events-none"></div>
    
</div>
@endforeach

<div class="flex gap-10 mt-5">
    <div class="w-[70%]">
        <div class="text-2xl font-bold">Latest Posts</div>
        <div>
            <div class="grid grid-cols-2 gap-7 mt-5">
                @foreach ($latestPosts as $post)
                <div class="linkToPost | cursor-pointer shadow-md h-full rounded-3xl p-5 mb-2 transition ease-in-out hover:scale-102">
                        <a href="{{ route('posts.show', $post->id) }}">
                            <div class="bg-cover bg-center h-[200px] rounded-2xl mb-2" style="background-image: url('{{ $post->media->first()?->url }}')"></div>
    
                               <div class="my-2 mr-3 text-xs flex flex-wrap gap-1">
                                    @foreach ($post->categories as $category)
                                        <span class="px-2 py-1 rounded-full text-white
                                            @switch($category->category_name)
                                                @case('Budgeting & Savings') bg-green-600 @break
                                                @case('Investing') bg-blue-600 @break
                                                @case('Debt & Credit') bg-red-600 @break
                                                @case('Financial Planning') bg-purple-600 @break
                                                @case('Career & Income') bg-yellow-500 text-black @break
                                                @default bg-gray-500 @break
                                            @endswitch
                                        ">
                                            {{ $category->category_name }}
                                        </span>
                                    @endforeach
                                </div>
                                                
                            <div class="text-2xl font-semibold">{{$post->title}}</div>
                            <div class="flex gap-3 text-gray-600 my-2 text-xs">
                                <span class="flex items-center"><x-css-profile class="h-4"/>{{ $post->users->name}}</span>
                                <span class="flex gap-1 items-center">
                                    <svg class="h-3 w-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ date('M d, Y', strtotime($post->publication_date)) }}
                                </span>   
                                <span class="flex gap-1 items-center">
                                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    {{ $post->views_count }}
                                </span>                    
                            </div>                    
                            <div class="text-black mb-4">{{Str::limit($post->content,150)}}</div>
                            <div class="w-full mt-10 mb-2">
                                <a class="text-white bg-green-700 py-3 px-5 rounded-xl cursor-pointer transition ease-in-out hover:bg-green-900" href="{{ route('posts.show', $post->id) }}">Read more</a>
                            </div>
                        </a>
                        
                    </div>
                @endforeach
                
            </div>
            <div class="mt-8">
                {{ $latestPosts->links() }}
            </div>
        </div>
    </div>
    <div class="w-[30%]">
        <div class="text-2xl font-bold">Popular</div>
        <div class="grid grid-cols-1 gap-2 mt-5">

            @foreach ($popularPosts as $post)
                <a class="linkToPost | relative bg-cover bg-center h-[200px] rounded-2xl mb-2 p-5 cursor-pointer text-white flex flex-col-reverse transition ease-in-out hover:scale-102" style="background-image: url('{{ $post->media->first()?->url }}')" href="{{ route('posts.show', $post->id) }}">
                    @foreach ($post->categories as $category)
                        <div class="text-md z-1">{{$category->category_name}}</div>
                    @endforeach
                    <div class="text-2xl mb-1 z-1">{{$post->title}}</div>
                    <div class="rounded-2xl absolute  inset-0 bg-gradient-to-t from-black/70 to-transparent pointer-events-none"></div>
                </a>
            @endforeach
 
        </div>
    </div>

    {{-- Inline Script For Popping Out Pop Up --}}

    <script>
        // Step 1: Create the div popup
        const popupOverlay = document.createElement('div');
        popupOverlay.id = 'loginPopup';
        popupOverlay.className = 'fixed inset-0 flex items-center justify-center bg-black/50 hidden z-50';

        const popupContent = document.createElement('div');
        popupContent.className = 'bg-white p-6 rounded-lg shadow-lg max-w-sm w-full text-center border border-2 border-green-700';

        popupContent.innerHTML = `
            <h2 class="text-xl font-bold mb-4">Please Log In</h2>
            <p class="mb-4">You need to be logged in to view this post.</p>
            <p class="mb-4 text-center">Redirecting user to the Login Page...</p>
            <div class="flex justify-center">
                <div class="w-8 h-8 border-4 border-green-600 border-t-transparent rounded-full animate-spin"></div>
            </div>
        `;

        popupOverlay.appendChild(popupContent);
        document.body.appendChild(popupOverlay);
        
        // Step 2: Get all elements with the class 'linkToPost' + Loop through and attach click event
        var elements = document.getElementsByClassName('linkToPost');
        const isUserLoggedIn = @json($is_user_logged_in);

        for (var i = 0; i < elements.length; i++) {
            elements[i].addEventListener('click', function(event) {
                if (!isUserLoggedIn) {
                    event.preventDefault();
                    popupOverlay.classList.remove('hidden');

                    // Optional: redirect after a delay
                    setTimeout(() => {
                        window.location.href = "{{ route('login') }}";
                    }, 2000);
                }
            });
        }
    </script>


</div>

@endsection

