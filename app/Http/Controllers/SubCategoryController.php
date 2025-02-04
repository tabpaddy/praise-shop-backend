<?php

namespace App\Http\Controllers;

use App\Models\SubCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SubCategoryController extends Controller
{
    //add category
    public function addSubCategory(Request $request)
    {
        //authenticated admin
        $admin = auth('admin')->user();
        if (!$admin) {
            return response()->json(['message' => 'Unauthenticated.', 401]);
        }

        // admin or subadmin
        if (!$admin->isAdminOrSubAdmin()) {
            return response()->json(['message' => "Unauthorized."], 403);
        }

        // validate the request data
        $validatedData = $request->validate([
            'sub_category_title' => 'required|string|max:255|unique:sub_categories',
        ]);

        // create the category
        $sub_category = SubCategory::create([
            'sub_category_title' => $validatedData['category_title'],
            'created_at' => Carbon::now(),
        ]);

        return response()->json(['message' => $sub_category[0] . ' created successfully', 200]);
    }

    // get all category
    public function getAllSubCategory()
    {
        $admin = auth('admin')->user();

        // admin or subadmin
        if (!$admin || !$admin->isAdminOrSubAdmin()) {
            return response()->json(['message' => "Unauthorized."], 403);
        }

        // fetch all category
        $sub_category = SubCategory::all();

        if ($sub_category) {
            return response()->json(['categories' => $sub_category], 200);
        } else {
            return response()->json(['message' => 'subCategory not found'], 404);
        }
    }

    // delete a category
    public function deleteCategory($subCategoryId)
    {
        $admin = auth('admin')->user();

        // admin or subadmin
        if (!$admin || !$admin->isAdminOrSubAdmin()) {
            return response()->json(['message' => "Unauthorized."], 403);
        }

        // find the category by id
        $subCategory = SubCategory::find($subCategoryId);

        if ($subCategory) {
            $subCategory->delete();
            return response()->json(['message' => $subCategory->sub_category_title . ' deleted successfully'], 200);
        } else {
            return response()->json(['error' => 'subCategory not found'], 404);
        }
    }
}
