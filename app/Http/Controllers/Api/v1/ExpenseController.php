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
     * List expenses
     *
     * Admins receive all expenses paginated. Clients only receive their own.
     *
     * @response scenario="Admin: all expenses" {
     *   "success": true,
     *   "message": "Fetch expense list successful.",
     *   "data": {
     *     "expenses": {
     *       "current_page": 1,
     *       "data": [{
     *         "id": 1,
     *         "amount": 15.50,
     *         "description": "Lunch",
     *         "category_id": 1,
     *         "user_id": 5
     *       }],
     *       "per_page": 10,
     *       "total": 50
     *     }
     *   },
     *   "response_code": 200
     * }
     *
     * @response scenario="Client: own expenses" {
     *   "success": true,
     *   "message": "Fetch expense list successful.",
     *   "data": {
     *     "expenses": {
     *       "current_page": 1,
     *       "data": [{
     *         "id": 1,
     *         "amount": 15.50,
     *         "description": "Lunch",
     *         "category_id": 1,
     *         "user_id": 3
     *       }],
     *       "per_page": 10,
     *       "total": 5
     *     }
     *   },
     *   "response_code": 200
     * }
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('client')) {
            return response()->json(
                [
                    'success' => true,
                    'message' => 'Fetch expense list successful.',
                    'data' => Expense::with('category')
                        ->where('user_id', $user->id)
                        ->paginate(10),
                    'response_code' => 200
                ], 200);
        }

        if ($user->hasRole('admin')) {
            return response()->json(
                [
                    'success' => true,
                    'message' => 'Fetch expense list successful.',
                    'data' => Expense::with('category')
                        ->paginate(10),
                    'response_code' => 200
                ], 200);
        }
    }

    /**
     * Create an expense
     *
     * Admins can assign an expense to any user.
     * Clients create expenses for themselves only.
     *
     * @bodyParam amount number required The amount of the expense. Example: 15.50
     * @bodyParam description string nullable Description of the expense. Example: Lunch
     * @bodyParam category_id int nullable The ID of the category. Example: 1
     * @bodyParam user_id int optional The ID of the user to assign the expense to (admin only). Example: 5
     *
     * @response scenario="Client success" {
     *   "success": true,
     *   "message": "Expense created successfully.",
     *   "data": {
     *     "amount": 15.5,
     *     "description": "Lunch",
     *     "category_id": 1
     *   },
     *   "response_code": 201
     * }
     *
     * @response 422 scenario="Invalid category" {
     *   "success": false,
     *   "message": "Invalid category",
     *   "errors": {
     *     "detail": "That category does not exist."
     *   },
     *   "response_code": 422
     * }
     */
    public function store(Request $request)
    {
        $user = $request->user();

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
                        'success' => false,
                        'message' => 'Invalid category',
                        'errors' =>
                            [
                                'detail'=> 'That category does not exist.',
                            ],
                        'response_code' => 422,
                    ], 422);
                }
            }

            $expense = Expense::create([
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'category_id' => $validated['category_id'] ?? null,
                'user_id' => $user->id,
            ]);

            $expense_return = [
                'amount' => $expense['amount'],
                'description' => $expense['description'],
                'category_id' => $expense['category_id'],
                ];

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Fetch expense list successful.',
                    'data' => $expense_return,
                    'response_code' => 201
                ], 201);
        }
    }


    /**
     * Get an expense
     *
     * Clients can only view their own expenses.
     *
     * @urlParam expense int required The ID of the expense. Example: 1
     *
     * @response scenario="Success" {
     *   "success": true,
     *   "message": "Show expense successful.",
     *   "data": {
     *     "id": 1,
     *     "amount": 15.50,
     *     "description": "Lunch",
     *     "category_id": 1,
     *     "user_id": 5
     *   },
     *   "response_code": 200
     * }
     *
     * @response 403 scenario="Forbidden" {
     *   "success": false,
     *   "message": "Forbidden.",
     *   "errors": {
     *     "detail": "This Expense does not belong to you"
     *   },
     *   "response_code": 403
     * }
     */
    public function show(Expense $expense)
    {
        $user = auth()->user();

        if ($user->hasRole('client')) {

            if ($expense->user_id !== $user->id) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Forbidden.',
                        'errors' => [
                            'detail' => 'This Expense does not belong to you',
                        ],
                        'response_code' => 403
                    ], 403);
            }

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Show expense successful.',
                    'data' => $expense,
                    'response_code' => 200
                ], 200);
        }
    }

    /**
     * Update an expense
     *
     * Admins can update any expense.
     * Clients can only update their own.
     *
     * @urlParam expense int required The ID of the expense. Example: 1
     *
     * @bodyParam amount number nullable Updated amount. Example: 25.75
     * @bodyParam description string nullable Updated description. Example: Dinner
     * @bodyParam category_id int nullable Updated category ID. Example: 2
     *
     * @response scenario="Success" {
     *   "success": true,
     *   "message": "Update expense successful.",
     *   "data": {
     *     "id": 1,
     *     "amount": 25.75,
     *     "description": "Dinner",
     *     "category_id": 2,
     *     "user_id": 5
     *   },
     *   "response_code": 200
     * }
     *
     * @response 403 scenario="Forbidden" {
     *   "success": false,
     *   "message": "Forbidden.",
     *   "errors": {
     *     "detail": "This Expense does not belong to you"
     *   },
     *   "response_code": 403
     * }
     *
     * @response 422 scenario="Invalid category" {
     *   "success": false,
     *   "message": "Invalid category",
     *   "errors": {
     *     "detail": "That category does not exist."
     *   },
     *   "response_code": 422
     * }
     */
    public function update(Request $request, Expense $expense)
    {
        $user = $request->user();

        if ($user->hasRole('client') && $expense->user_id !== $user->id) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Forbidden.',
                    'errors' => [
                        'detail' => 'This Expense does not belong to you',
                    ],
                    'response_code' => 403
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
                    'success' => false,
                    'message' => 'Invalid category',
                    'errors' =>
                        [
                            'detail'=> 'That category does not exist.',
                        ],
                    'response_code' => 422,
                ], 422);
            }
        }

        $expense->update($validated);

        return response()->json(
            [
                'success' => true,
                'message' => 'Update expense successful.',
                'data' => $expense,
                'response_code' => 200
            ], 200);
    }


    /**
     * Delete an expense
     *
     * Admins can delete any expense.
     * Clients can only delete their own.
     *
     * @urlParam expense int required The ID of the expense. Example: 1
     *
     * @response scenario="Success" {
     *   "message": "Deleted"
     * }
     *
     * @response 403 scenario="Forbidden" {
     *   "success": false,
     *   "message": "Forbidden.",
     *   "errorss": {
     *     "detail": "This Expense does not belong to you"
     *   },
     *   "response_code": 403
     * }
     */
    public function destroy(Expense $expense)
    {
        $user = auth()->user();

        if ($user->hasRole('client') && $expense->user_id !== $user->id) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Forbidden.',
                    'errorss' => [
                        'detail' => 'This Expense does not belong to you',
                    ],
                    'response_code' => 403
                ], 403);
        }

        $expense->delete();

        return response()->json([], 204);
    }
}
