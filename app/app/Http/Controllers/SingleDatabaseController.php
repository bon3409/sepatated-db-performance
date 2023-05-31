<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SingleDatabaseController extends Controller
{
    /**
     * Specific user create a new order
     *
     * @param Request $request
     * @return void
     */
    public function createOrder(Request $request)
    {
        if (!$userId = $request->input('user_id')) {
            return response()->json([
                'status'  => false,
                'message' => 'user_id is necessary'
            ], 400);
        }

        if (!DB::connection('mysql::write')->table('users')->find($userId)) {
            return response()->json([
                'status'  => false,
                'message' => 'user not exists'
            ], 404);
        }

        try {
            DB::beginTransaction();

            DB::table('orders')->insert([
                'name' => fake()->name(),
                'user_id' => $request->input('user_id'),
                'amount'  => fake()->numberBetween(100, 99999),
                'created_at' => fake()->dateTime(),
                'updated_at' => fake()->dateTime()
            ]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'From single database: success'
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Specific user get all orders
     *
     * @param Request $request
     * @param integer $userId
     * @return void
     */
    public function getAllOrders(Request $request)
    {
        if (!$userId = $request->input('user_id')) {
            return response()->json([
                'status'  => false,
                'message' => 'user_id is necessary'
            ], 400);
        }

        $data = DB::connection('mysql::write')->table('orders')->select('name', 'amount')->where('user_id', $userId)->get()->toArray();

        return response()->json([
            'status'  => true,
            'data'    => $data,
            'message' => 'From single database: success'
        ]);
    }
}
