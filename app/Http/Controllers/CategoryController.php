<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    //add category
    public function addCategory(Request $request)
    {
        // authenticated admin
        $admin = auth('admin')->user();
        if (!$admin) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // admin or subadmin
        if (!$admin->isAdminOrSubAdmin()) {
            return response()->json(['message' => "Unauthorized."], 403);
        }

        // validate the request data
        $validatedData = $request->validate([
            'category_title' => 'required|string|max:255|unique:categories',
        ]);

        // create the category
        $category = Category::create([
            'category_title' => $validatedData['category_title'],
            'created_at' => Carbon::now(),
        ]);

        Cache::forget('categories');

        return response()->json(['message' => $category->category_title . ' created successfully', 200]);
    }

    // get all category
    public function getAllCategory()
    {
        $admin = auth('admin')->user();

        // admin or subadmin
        if (!$admin || !$admin->isAdminOrSubAdmin()) {
            return response()->json(['message' => "Unauthorized."], 403);
        }

        // fetch all category
        $category = Cache::remember('categories', 60, function (){
            return Category::all();
        });

        if ($category) {
            return response()->json(['categories' => $category]);
        } else {
            return response()->json(['message' => 'category not found'], 404);
        }
    }

    // delete a category
    public function deleteCategory($categoryId)
    {
        $admin = auth('admin')->user();

        // admin or subadmin
        if (!$admin || !$admin->isAdminOrSubAdmin()) {
            return response()->json(['message' => "Unauthorized."], 403);
        }

        // find the category by id
        $category = Category::find($categoryId);

        if ($category) {
            $category->delete();
            Cache::forget('categories');
            return response()->json(['message' => 'Category deleted successfully'], 200);
        } else {
            return response()->json(['error' => 'Category not found'], 404);
        }
    }

    // edit category
    public function editCategory(Request $request, $categoryId)
    {
        $admin = auth('admin')->user();

        // admin or subadmin
        if (!$admin || !$admin->isAdminOrSubAdmin()) {
            return response()->json(['message' => "Unauthorized."], 403);
        }

        // find the category by id
        $category = Category::find($categoryId);

        if ($category) {
            $request->validate([
                'category_title' => 'required|string|max:255|unique:categories,category_title,' . $categoryId
            ]);

            $category->update([
                'category_title' => $request->category_title,
                'updated_at' => Carbon::now(),
            ]);

            Cache::forget('categories');

            return response()->json(['message' => 'Category Updated.'], 200);
        }
    }
}
