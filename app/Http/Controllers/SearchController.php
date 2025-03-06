<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    //
    // In ProductController.php
public function searchProducts(Request $request)
{
    $query = $request->input('query');

    // If query is empty or null, return an empty array
    if (!$query) {
        return response()->json(['products' => []], 200);
    }

    // Fetch products that match the query in name, keyword, or description
    $products = Product::where('name', 'LIKE', '%'.$query.'%')
        ->orWhere('keyword', 'LIKE', '%'.$query.'%')
        ->orWhere('description', 'LIKE', '%'.$query.'%')
        ->get();

    // Transform image paths to full URLs
    $products->transform(function ($prod) {
        $prod->image1_url = asset(\Illuminate\Support\Facades\Storage::url($prod->image1));
        return $prod;
    });

    return response()->json(['products' => $products], 200);
}

}
