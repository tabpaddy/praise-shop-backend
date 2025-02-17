<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PhpParser\Node\Stmt\TryCatch;

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

    // count number of product
    public function countProduct()
    {
        $admin = auth('admin')->user();

        // admin or subadmin
        if (!$admin || !$admin->isAdminOrSubAdmin()) {
            return response()->json(['message' => "Unauthorized."], 403);
        }

        // fetch all category
        $products = DB::table('products')->count();


        if ($products) {
            // Clear the cache for products if needed
            Cache::forget('products');

            return response()->json(['product' => $products], 200);
        } else {
            return response()->json(['message' => 'Could not count product'], 404);
        }
    }

    // get single product
    public function getSingleProduct($id)
    {
        // Authenticate admin
        $admin = auth('admin')->user();

        if (!$admin || !$admin->isAdminOrSubAdmin()) {
            return response()->json(['message' => "Unauthorized."], 403);
        }

        // Find the product with its category and sub-category relationships
        $product = Product::with(['category', 'subCategory'])->find($id);

        // Check if product exists
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Map image paths to full URLs using the Storage facade and asset helper
        $product->image1_url = asset(Storage::url($product->image1));
        $product->image2_url = asset(Storage::url($product->image2));
        $product->image3_url = asset(Storage::url($product->image3));
        $product->image4_url = asset(Storage::url($product->image4));
        $product->image5_url = asset(Storage::url($product->image5));

        // Decode sizes (stored as JSON in the database) into an array
        $product->sizes = json_decode($product->sizes);

        // Clear the cache for products if needed
        Cache::forget('products');

        // Return the product in a JSON response
        return response()->json(['product' => $product], 200);
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

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        try {
            //code...
            File::delete(storage_path('app/public/' . $product->image1));
            File::delete(storage_path('app/public/' . $product->image2));
            File::delete(storage_path('app/public/' . $product->image3));
            File::delete(storage_path('app/public/' . $product->image4));
            File::delete(storage_path('app/public/' . $product->image5));

            $product->delete();
            Cache::forget('products');

            return response()->json(['message' => $product->name . ' deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete product: ' . $e->getMessage()
            ], 500);
        }
    }

    // Update product
    public function updateProduct(Request $request, $id)
    {
        // Authenticated admin check
        $admin = auth('admin')->user();
        if (!$admin || !$admin->isAdminOrSubAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Find the product or fail with 404
        $product = Product::findOrFail($id);

        // Validate the request data. Note the unique rule excludes the current product.
        $validationData = $request->validate([
            'name' => 'required|string|max:255|min:5|unique:products,name,' . $product->id,
            'description' => 'required|string|min:10',
            'keyword' => 'required|string|min:10|max:255',
            'price' => 'required|numeric|min:10',
            'category' => 'required|exists:categories,id',
            'subCategory' => 'required|exists:sub_categories,id',
            'sizes' => 'required|array',
            'sizes.*' => 'string|in:S,M,L,XL,XXL',
            'bestseller' => 'required|boolean',
            // For images, use "sometimes" so that they aren’t required if unchanged.
            'image1' => 'sometimes|image|max:10240',
            'image2' => 'sometimes|image|max:10240',
            'image3' => 'sometimes|image|max:10240',
            'image4' => 'sometimes|image|max:10240',
            'image5' => 'sometimes|image|max:10240',
            'updated_at' => Carbon::now(),
        ]);

        // Process file uploads if provided, else use existing values
        if ($request->hasFile('image1')) {
            File::delete(storage_path('app/public/' . $product->image1));
            $image1Path = $request->file('image1')->store('products', 'public');
        } else {
            $image1Path = $product->image1;
        }
        if ($request->hasFile('image2')) {
            File::delete(storage_path('app/public/' . $product->image2));
            $image2Path = $request->file('image2')->store('products', 'public');
        } else {
            $image2Path = $product->image2;
        }
        if ($request->hasFile('image3')) {
            File::delete(storage_path('app/public/' . $product->image3));
            $image3Path = $request->file('image3')->store('products', 'public');
        } else {
            $image3Path = $product->image3;
        }
        if ($request->hasFile('image4')) {
            File::delete(storage_path('app/public/' . $product->image4));
            $image4Path = $request->file('image4')->store('products', 'public');
        } else {
            $image4Path = $product->image4;
        }
        if ($request->hasFile('image5')) {
            File::delete(storage_path('app/public/' . $product->image5));
            $image5Path = $request->file('image5')->store('products', 'public');
        } else {
            $image5Path = $product->image5;
        }

        // Update the product record
        $product->update([
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
            'sizes' => json_encode($validationData['sizes']),
            'bestseller' => $validationData['bestseller'],
            'updated_at' => Carbon::now(),
        ]);

        Cache::forget('product');

        return response()->json(['message' => $product->name . ' updated successfully'], 200);
    }

    // Fetch bestseller products in random order, limited to 5 items
    public function getBestSellers()
    {
        $bestSellers = Product::where('bestseller', true)
            ->inRandomOrder()
            ->limit(5)
            ->get(['id', 'image1', 'price', 'name']);

        // Map over products to include full image URLs
        $bestSellers->transform(function ($product) {
            $product->image1_url = asset(Storage::url($product->image1));
            return $product;
        });

        return response()->json(['bestSellers' => $bestSellers], 200);
    }

    // get home product
    public function getHomeProduct()
    {
        $home = Product::orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'image1', 'price', 'name']);

        $home->transform(function ($product) {
            $product->image1_url = asset(Storage::url($product->image1));
            return $product;
        });

        return response()->json(['home' => $home]);
    }

    // get frontend product
    public function getAllCollection()
    {
        // fetch all product
        $collection = Product::with(['category', 'subCategory'])->inRandomOrder()->get(['id', 'image1', 'price', 'name', 'category_id', 'sub_category_id', 'created_at']);

        // map over products to include full image urls
        $collection->transform(function ($product) {
            $product->image1_url = asset(Storage::url($product->image1));
            return $product;
        });

        return response()->json(['collection' => $collection]);
    }
}
