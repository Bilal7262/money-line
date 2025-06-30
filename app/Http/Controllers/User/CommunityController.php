<?php

namespace App\Http\Controllers\User;

use App\Models\Post;
use App\Models\Comment;
use App\Models\Report;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CommunityController extends Controller
{
    public function createPost(Request $request)
    {
        $validated = $request->validate([
            'description' => 'nullable|string|max:5000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $post = new Post();
        $post->user_id = $request->user()->id;
        $post->description = $validated['description'] ?? null;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('posts');
            $post->image = Storage::url($path);
        }

        $post->save();

        return response()->json([
            'message' => 'Post created successfully',
            'post' => $post->load('user'),
        ], 201);
    }

    public function showPosts(Request $request)
    {
        $posts = Post::with(['user', 'comments.user', 'likes', 'bookmarks'])
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Posts retrieved successfully',
            'posts' => $posts,
        ]);
    }

    public function showPost(Request $request, Post $post)
    {
        return response()->json([
            'message' => 'Post retrieved successfully',
            'post' => $post->load(['user', 'comments.user', 'likes', 'bookmarks']),
        ]);
    }

    public function likePost(Request $request, Post $post)
    {
        $user = $request->user();
        if ($user->likedPosts()->where('post_id', $post->id)->exists()) {
            $user->likedPosts()->detach($post->id);
            $message = 'Post unliked successfully';
        } else {
            $user->likedPosts()->attach($post->id);
            $message = 'Post liked successfully';
        }

        return response()->json([
            'message' => $message,
            'post' => $post->load('likes'),
        ]);
    }

    public function bookmarkPost(Request $request, Post $post)
    {
        $user = $request->user();
        if ($user->bookmarkedPosts()->where('post_id', $post->id)->exists()) {
            $user->bookmarkedPosts()->detach($post->id);
            $message = 'Post unbookmarked successfully';
        } else {
            $user->bookmarkedPosts()->attach($post->id);
            $message = 'Post bookmarked successfully';
        }

        return response()->json([
            'message' => $message,
            'post' => $post->load('bookmarks'),
        ]);
    }

    public function reportPost(Request $request, Post $post)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        if ($user->reports()->where('post_id', $post->id)->exists()) {
            return response()->json(['message' => 'Post already reported'], 422);
        }

        $report = Report::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'reason' => $validated['reason'] ?? null,
        ]);

        return response()->json([
            'message' => 'Post reported successfully',
            'report' => $report,
        ], 201);
    }

    public function createComment(Request $request, Post $post)
    {
        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $request->user()->id,
            'comment' => $validated['comment'],
        ]);

        return response()->json([
            'message' => 'Comment created successfully',
            'comment' => $comment->load('user'),
        ], 201);
    }

    public function likeComment(Request $request, Comment $comment)
    {
        $user = $request->user();
        if ($user->likedComments()->where('comment_id', $comment->id)->exists()) {
            $user->likedComments()->detach($comment->id);
            $message = 'Comment unliked successfully';
        } else {
            $user->likedComments()->attach($comment->id);
            $message = 'Comment liked successfully';
        }

        return response()->json([
            'message' => $message,
            'comment' => $comment->load('likes'),
        ]);
    }
}
