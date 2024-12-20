<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RiderController extends Controller
{
    public function display_info()
    {
        $users = User::with('role')->get();
        $roles = DB::table('roles')->get();
        $allorders = DB::select('SELECT * FROM all_orders');

        return Inertia::render('RiderInterface', [
            'users' => $users,
            'roles' => $roles,
            'allorders' => $allorders
        ]);
    }


    public function updateStatusTo2(Request $request)
    {
        $orderId = $request->input('orderId');
    
        try {
            // Trigger the PostgreSQL function to update the order status to 2
            DB::select('SELECT update_order_status_to_2(?)', [$orderId]);
    
            return redirect()->back()->with('success', 'Order status updated to 2');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Order status not updated to 2');
        }
    }

    public function updateStatusTo3(Request $request)
    {
        $orderId = $request->input('orderId');
    
        try {
            // Trigger the PostgreSQL function to update the order status to 2
            DB::select('SELECT update_order_status_to_3(?)', [$orderId]);
    
            return redirect()->back()->with('success', 'Order status updated to 2');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Order status not updated to 2');
        }
    }
    
}
