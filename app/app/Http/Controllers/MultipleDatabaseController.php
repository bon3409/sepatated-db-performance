<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Test for master / slave database performance
 */
class MultipleDatabaseController extends Controller
{
    /**
     * Specific user create a new order
     *
     * @param Request $request
     * @return void
     */
    public function createOrder(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'user_id' => 'required|exists:users,id'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first()
            ], 400);
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
                'message' => 'From multiple database: success'
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
        $data = DB::table('orders')->select('name', 'amount')->where('user_id', $request->input('user_id'))->get()->toArray();

        return response()->json([
            'status' => true,
            'data'   => $data,
            'message' => 'From multiple database: success'
        ]);
    }
}
