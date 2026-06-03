<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Expense;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Group;

#[Group("Expense Controller", "APIs for Expense")]
class ExpenseController extends Controller
{
    /**
     *
     * List expenses
     *
     * Admins receive all expenses paginated. Clients only receive their own.
     *
     * @response scenario="Admin: all expenses" {
     *   "current_page": 1,
     *   "data": [{
     *     "id": 1,
     *     "amount": 15.50,
     *     "description": "Lunch",
     *     "category_id": 1,
     *     "user_id": 5
     *   }],
     *   "per_page": 10,
     *   "total": 50
     * }
     *
     * @response scenario="Client: own expenses" {
     *   "current_page": 1,
     *   "data": [{
     *     "id": 1,
     *     "amount": 15.50,
     *     "description": "Lunch",
     *     "category_id": 1,
     *     "user_id": 3
     *   }],
     *   "per_page": 10,
     *   "total": 5
     * }
     *
     * @response 403 scenario="Unauthorized role" {"message": "Unauthorized role"}
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        if ($user->hasRole('client')) {
            return Expense::with('category')
                ->where('user_id', $user->id)
                ->paginate(10);
        }

        if ($user->hasRole('admin')) {
            return Expense::with('category')
                ->paginate(10);
        }

        return response()->json([
            'message' => 'Unauthorized role'
        ], 403);
    }

    /**
     * Create an expense
     *
     * Admins can assign an expense to any user.
     * Clients create expenses for themselves only.
     *
     * @bodyParam amount number required The amount of the expense. Example: 15.50
     * @bodyParam description string required Description of the expense. Example: Lunch
     * @bodyParam category_id int The ID of the category. Example: 1
     * @bodyParam user_id int The ID of the user to assign the expense to (admin only). Example: 5
     *
     * @response 201 scenario="Success" {
     *   "id": 1,
     *   "amount": 15.50,
     *   "description": "Lunch",
     *   "category_id": 1,
     *   "user_id": 5
     * }
     *
     * @response 403 scenario="Unauthorized role" {"message": "Unauthorized role"}
     * @response 422 scenario="Validation error" {"message": "The given data was invalid."}
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        if ($user->hasRole('client')) {

            $validated = $request->validate([
                'amount' => 'required|numeric|min:0',
                'description' => 'nullable|string|max:64',
                'category_id' => 'nullable|exists:categories,id',
            ]);

            if (!empty($validated['category_id'])) {

                $category = Category::where('id', $validated['category_id'])
                    ->where('user_id', $user->id)
                    ->first();

                if (!$category) {
                    return response()->json([
                        'message' => 'Invalid category'
                    ], 422);
                }
            }

            $expense = Expense::create([
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'category_id' => $validated['category_id'] ?? null,
                'user_id' => $user->id,
            ]);

            return response()->json($expense, 201);
        }

        return response()->json([
            'message' => 'Unauthorized role'
        ], 403);
    }


    /**
     * Get an expense
     *
     * Admins can view any expense.
     * Clients can only view their own.
     *
     * @urlParam expense int required The ID of the expense. Example: 1
     *
     * @response scenario="Success" {
     *   "id": 1,
     *   "amount": 15.50,
     *   "description": "Lunch",
     *   "category_id": 1,
     *   "user_id": 5
     * }
     *
     * @response 403 scenario="Client accessing another user's expense" {"message": "Forbidden"}
     * @response 403 scenario="Unauthorized role" {"message": "Unauthorized role"}
     */
    public function show(Expense $expense)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        if ($user->hasRole('client')) {

            if ($expense->user_id !== $user->id) {
                return response()->json([
                    'message' => 'Forbidden'
                ], 403);
            }

            return response()->json($expense);
        }

        return response()->json([
            'message' => 'Unauthorized role'
        ], 403);
    }

    /**
     * Update an expense
     *
     * Admins can update any expense.
     * Clients can only update their own.
     *
     * @urlParam expense int required The ID of the expense. Example: 1
     *
     * @bodyParam amount number The updated expense amount. Example: 25.75
     * @bodyParam description string The updated description. Example: Dinner
     * @bodyParam category_id int The updated category ID. Example: 2
     *
     * @response scenario="Success" {
     *   "id": 1,
     *   "amount": 25.75,
     *   "description": "Dinner",
     *   "category_id": 2,
     *   "user_id": 5
     * }
     *
     * @response 403 scenario="Client updating another user's expense" {"message": "Forbidden"}
     * @response 422 scenario="Validation error" {"message": "The given data was invalid."}
     */
    public function update(Request $request, Expense $expense)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        if ($user->hasRole('client') && $expense->user_id !== $user->id) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:0',
            'description' => 'sometimes|string|max:64',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        if (!empty($validated['category_id'])) {

            $category = Category::where('id', $validated['category_id'])
                ->where('user_id', $expense->user_id)
                ->first();

            if (!$category) {
                return response()->json([
                    'message' => 'Invalid category'
                ], 422);
            }
        }

        $expense->update($validated);

        return response()->json($expense);
    }


    /**
     * Delete an expense
     *
     * Admins can delete any expense.
     * Clients can only delete their own.
     *
     * @urlParam expense int required The ID of the expense. Example: 1
     *
     * @response scenario="Success" {"message": "Deleted"}
     * @response 403 scenario="Client deleting another user's expense" {"message": "Forbidden"}
     */
    public function destroy(Expense $expense)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        if ($user->hasRole('client') && $expense->user_id !== $user->id) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        $expense->delete();

        return response()->json([
            'message' => 'Deleted'
        ]);
    }
}
