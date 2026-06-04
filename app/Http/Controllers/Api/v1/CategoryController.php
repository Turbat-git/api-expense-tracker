<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Group;

#[Group("Category Controller", "APIs for Category")]
class CategoryController extends Controller
{
    /**
     * List categories
     *
     * Admins receive all categories paginated. Clients only receive their own.
     *
     *
     * @response scenario="Admin: all categories" {
     *   "current_page": 1,
     *   "data": [{"id": 1, "name": "Electronics", "user_id": 5}],
     *   "per_page": 10,
     *   "total": 50
     * }
     *
     * @response scenario="Client: own categories" {
     *   "current_page": 1,
     *   "data": [{"id": 1, "name": "Electronics", "user_id": 3}],
     *   "per_page": 10,
     *   "total": 5
     * }
     * @response 403 scenario="Unauthorized role" {"message": "Unauthorized role"}
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            return response()->json(
                [
                    'success' => true,
                    'message' => 'Fetching All Category Data',
                    'data' => [Category::paginate(10)],
                    'response_code' => 200
                ], 200
            );
        }
        elseif ($user->hasRole('client')) {
            return response()->json(
                [
                    'success' => true,
                    'message' => 'Fetching Your Category Data',
                    'data' => [Category::where('user_id', $user->id)->paginate(10)],
                    'response_code' => 200
                ], 200
            );
        }
    }

    /**
     * Create a category
     *
     * Admins can assign a category to any user. Clients create categories for themselves only.
     *
     * @bodyParam name string required The name of the category. Example: Electronics
     * @bodyParam user_id int The ID of the user to assign the category to (admin only). Example: 5
     *
     * @response 201 scenario="Success" {"id": 1, "name": "Electronics", "user_id": 5}
     * @response 403 scenario="Unauthorized role" {"message": "Unauthorized role"}
     * @response 422 scenario="Validation error" {"message": "The name field is required."}
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            $validated = $request->validate(
                [
                'name' => 'required|string|max:64',
                'user_id' => 'required|exists:users,id'
                ]
            );

            $category = Category::create([
                'name' => $validated['name'],
                'user_id' => $validated['user_id'],
            ]);

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Category created successfully.',
                    'data' =>
                        [
                            'category' => $category,
                        ],
                    'response_code' => 201,
                ],201);
        }
        elseif ($user->hasRole('client')) {
            $validated = $request->validate([
                'name' => 'required|string|max:64',
            ]);

            $category = Category::create([
                'name' => $validated['name'],
                'user_id' => $request->user()->id,
            ]);

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Category created successfully.',
                    'data' =>
                        [
                            'category' => $category->name,
                        ],
                    'response_code' => 201,
                ],201);
        }
    }

    /**
     * Get a category
     *
     * Admins can view any category. Clients can only view their own.
     *
     * @urlParam category int required The ID of the category. Example: 1
     *
     * @response scenario="Success" {"id": 1, "name": "Electronics", "user_id": 5}
     * @responseField id int The category ID.
     * @responseField name string The category name.
     * @responseField user_id int The ID of the owning user.
     *
     * @response 403 scenario="Client accessing another user's category" {"message": "Forbidden"}
     * @response 403 scenario="Unauthorized role" {"message": "Unauthorized role"}
     */
    public function show(Category $category)
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return response()->json(
                [
                    'success' => true,
                    'message' => 'Show category successful.',
                    'data' => [
                        'category' => $category,
                    ],
                    'response_code' => 200
                ], 200);
        }
        elseif ($user->hasRole('client')) {
            if ($category->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. This Category does not belong to you.',
                    'data' => null,
                    'response_code' => 403
                ], 403);
            }

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Show category successful.',
                    'data' => [
                        'category' => $category,
                    ],
                    'response_code' => 200
                ], 200);
        }
    }

    /**
     * Update a category
     *
     * Admins can update any category. Clients can only update their own.
     *
     * @urlParam category int required The ID of the category. Example: 1
     *
     * @bodyParam name string required The new name of the category. Example: Apparel
     *
     * @response scenario="Success" {"id": 1, "name": "Apparel", "user_id": 5}
     * @response 403 scenario="Client updating another user's category" {"message": "Forbidden"}
     * @response 422 scenario="Validation error" {"message": "The name field is required."}
     */
    public function update(Request $request, Category $category)
    {
        $user = $request->user();

        if ($user->hasPermissionTo('update-own-categories') && $category->user_id !== $user->id) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Forbidden. This Category does not belong to you',
                    'data' => null,
                    'response_code' => 403
                ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:64',
        ]);

        $category->update($validated);

        return response()->json(
            [
                'success' => true,
                'message' => 'Update category successful.',
                'data' => [
                    'category' => $category,
                ],
                'response_code' => 200
            ], 200);
    }

    /**
     * Delete a category
     *
     * Admins can delete any category. Clients can only delete their own.
     *
     *
     * @urlParam category int required The ID of the category. Example: 1
     *
     * @response scenario="Success" {"message": "Deleted"}
     * @response 403 scenario="Client deleting another user's category" {"message": "Forbidden"}
     */
    public function destroy(Category $category)
    {
        $user = auth()->user();

        if ($user->hasRole('client') && $category->user_id !== $user->id) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Forbidden. This Category does not belong to you',
                    'data' => null,
                    'response_code' => 403
                ], 403);
        }

        $category->delete();

        return response()->json([], 204);
    }
}
