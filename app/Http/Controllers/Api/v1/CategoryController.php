<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->hasRole('admin')) {
            return Category::paginate(10);
        }
        elseif ($user->hasRole('client')) {
            return Category::where('user_id', $user->id)->paginate(10);
        }

        return response()->json([
            'message' => 'Unauthorized role'
        ], 403);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->hasRole('admin')) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'user_id' => 'required|exists:users,id'
            ]);

            $category = Category::create([
                'name' => $validated['name'],
                'user_id' => $validated['user_id'],
            ]);

            return response()->json($category, 201);
        }
        elseif ($user->hasRole('client')) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $category = Category::create([
                'name' => $validated['name'],
                'user_id' => $request->user()->id,
            ]);

            return response()->json($category, 201);
        }

        return response()->json([
            'message' => 'Unauthorized role'
        ], 403);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return response()->json($category);
        }
        elseif ($user->hasRole('client')) {
            if ($category->user_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Forbidden'
                ], 403);
            }

            return response()->json($category);
        }

        return response()->json([
            'message' => 'Unauthorized role'
        ], 403);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $user = $request->user();

        if ($user->hasRole('client') && $category->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $user = auth()->user();

        if ($user->hasRole('client') && $category->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $category->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
