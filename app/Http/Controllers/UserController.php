<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\user;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Show users list.
     */
    public function show(FormRequest $request)
    {
        $search = $request->query('search', '');
        $page = $request->query('page', 1);
        $pageCount = $request->query('pageCount', 10);
        $sortBy = $request->query('sortBy', 'created_at');
        if (!in_array($sortBy, ['name', 'email', 'created_at'])) {
            $sortBy = 'created_at';
        }

        $users = User::query()
            ->select('users.id', 'email', 'name', 'users.created_at')
            ->selectRaw('count(orders.id) as order_count')
            ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
            ->where('active', true)
            ->where('name', 'like', "%$search%")
            ->orWhere('email', 'like', "%$search%")
            ->skip(($page - 1) * $pageCount)
            ->take($pageCount)
            ->groupBy('users.id', 'email', 'name', 'users.created_at')
            ->orderBy($sortBy)
            ->get();

        return response()->json([
            'search' => $search,
            'page' => $page,
            'pageCount' => $pageCount,
            'sortBy' => $sortBy,
            'users' => $users,
        ], 200);
    }

    /**
     * Store a new user.
     */
    public function store(FormRequest $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|max:255',
            'password' => 'required|string|min:8',
            'name' => 'required|string|min:3|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // $user = User::create($request->all());
        $user = User::create($request->only(['email', 'password', 'name']));
        return response()->json($user, 200);
    }
}
