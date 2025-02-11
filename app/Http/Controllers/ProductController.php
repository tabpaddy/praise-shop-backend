<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    //add product
    public function addProduct(Request $request)
    {
        // Authenticated admin check
        $admin = auth('admin')->user();
        if (!$admin || !$admin->isAdminOrSubAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Validate the request data
        $validationData = $request->validate([
            'name' => 'required|string|max:255|min:5|unique:products',
            'description' => 'required|string|min:10',
            'keyword' => 'required|string|min:10|max:255',
            'price' => 'required|numeric|min:10',
            'image1' => 'required|image|max:10240',
            'image2' => 'required|image|max:10240',
            'image3' => 'required|image|max:10240',
            'image4' => 'required|image|max:10240',
            'image5' => 'required|image|max:10240',
            'category' => 'required|exists:categories,id',
            'subCategory' => 'required|exists:sub_categories,id',
            'sizes' => 'required|array',
            'sizes.*' => 'string|in:S,M,L,XL,XXL', // Add array validation
            'bestseller' => 'required|boolean',
        ]);

        // Process file uploads
        $image1Path = $request->file('image1')->store('products', 'public');
        $image2Path = $request->file('image2')->store('products', 'public');
        $image3Path = $request->file('image3')->store('products', 'public');
        $image4Path = $request->file('image4')->store('products', 'public');
        $image5Path = $request->file('image5')->store('products', 'public');

        // Create the product record
        $product = Product::create([
            'name' => $validationData['name'],
            'description' => $validationData['description'],
            'keyword' => $validationData['keyword'],
            'price' => $validationData['price'],
            'image1' => $image1Path,
            'image2' => $image2Path,
            'image3' => $image3Path,
            'image4' => $image4Path,
            'image5' => $image5Path,
            'category_id' => $validationData['category'],
            'sub_category_id' => $validationData['subCategory'],
            'sizes' => json_encode($validationData['sizes']), // Save as JSON
            'bestseller' => $validationData['bestseller'],
            'created_at' => Carbon::now()
        ]);

        Cache::forget('product');

        return response()->json(['message' => $product->name . ' created successfully'], 200);
    }

    // get all product
    public function getAllProduct()
    {
        $admin = auth('admin')->user();

        // admin or subadmin
        if (!$admin || !$admin->isAdminOrSubAdmin()) {
            return response()->json(['message' => "Unauthorized."], 403);
        }

        // fetch all category
        $products = Cache::remember('products', 60, function () {
            return Product::with(['category', 'subCategory'])->get();
        });

        // Map over products to include full image URLs
        $products->transform(function ($product) {
            $product->image1_url = asset(Storage::url($product->image1));
            $product->image2_url = asset(Storage::url($product->image2));
            $product->image3_url = asset(Storage::url($product->image3));
            $product->image4_url = asset(Storage::url($product->image4));
            $product->image5_url = asset(Storage::url($product->image5));
            // Decode sizes from JSON to an array if needed.
            $product->sizes = json_decode($product->sizes);
            return $product;
        });

        if ($products) {
            return response()->json(['product' => $products], 200);
        } else {
            return response()->json(['message' => 'Product not found'], 404);
        }
    }

    // product delete
    public function deleteProduct($productId)
    {
        $admin = auth('admin')->user();

        // admin or subadmin
        if (!$admin || !$admin->isAdminOrSubAdmin()) {
            return response()->json(['message' => "Unauthorized."], 403);
        }

        // find the product by id
        $product = Product::find($productId);

        if ($product) {
            File::delete(storage_path('app/public/products' . $product->image1));
            File::delete(storage_path('app/public/products' . $product->image2));
            File::delete(storage_path('app/public/products' . $product->image3));
            File::delete(storage_path('app/public/products' . $product->image4));
            File::delete(storage_path('app/public/products' . $product->image5));
            $product->delete();
            Cache::forget('products');
            return response()->json(['message' => $product->name . ' deleted successfully']);
        } else {
            return response()->json(['error' => 'subCategory not found'], 404);
        }
    }
}
