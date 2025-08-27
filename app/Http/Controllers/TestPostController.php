<?php

namespace App\Http\Controllers;

use App\Models\TestPost;
use Illuminate\Http\Request;

class TestPostController extends Controller
{
    // List all posts
    public function index()
    {
        $posts = TestPost::latest()->get();
        return response()->json([
            'success' => true,
            'data' => $posts
        ]);
    }

    // Store new post
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $post = TestPost::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully',
            'data' => $post
        ], 201);
    }

    // Show single post
    public function show($id)
    {
        $post = TestPost::find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $post
        ]);
    }

    // Update post
    public function update(Request $request, $id)
    {
        $post = TestPost::find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $post->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully',
            'data' => $post
        ]);
    }

    // Delete post
    public function destroy($id)
    {
        $post = TestPost::find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully'
        ]);
    }
}
