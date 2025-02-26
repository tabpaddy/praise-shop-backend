<?php

namespace App\Http\Controllers;

use App\Models\Subscribe;
use Illuminate\Http\Request;

class SubscribeController extends Controller
{
    //subscribe email
    public function subscribe(Request $request){
        $validatedData = $request->validate([
            'email' => "required|string|email|unique:subscribes",
            'ip_address' => 'required|ip',
        ]);

        $subscribe = Subscribe::create([
            'email' => $validatedData['email'],
            'ip_address' => $request->ip_address,
        ]);

        if($subscribe){
            return response()->json(['message' => 'Subscription data stored successfully!'], 200);
        }else{
            return response()->json(['message' => 'Subscription data failed to store!'], 500);
        };

    }
}
