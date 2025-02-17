<?php

namespace App\Http\Controllers;

use App\Models\SubCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
            'sub_category_title' => $validatedData['sub_category_title'],
            'created_at' => Carbon::now(),
        ]);

        Cache::forget('sub_categories');

        return response()->json(['message' => $sub_category->sub_category_title . ' created successfully', 200]);
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
        $sub_category = Cache::remember('sub_categories', 60, function () {
            return SubCategory::all();
        });

        if ($sub_category) {
            return response()->json(['sub_categories' => $sub_category], 200);
        } else {
            return response()->json(['message' => 'subCategory not found'], 404);
        }
    }

    // delete a category
    public function deleteSubCategory($subCategoryId)
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
            Cache::forget('sub_categories');
            return response()->json(['message' => $subCategory->sub_category_title . ' deleted successfully'], 200);
        } else {
            return response()->json(['error' => 'subCategory not found'], 404);
        }
    }

    // edit category
    public function editSubCategory(Request $request, $subCategoryId)
    {
        $admin = auth('admin')->user();

        // admin or subadmin
        if (!$admin || !$admin->isAdminOrSubAdmin()) {
            return response()->json(['message' => "Unauthorized."], 403);
        }

        // find the category by id
        $subCategory = SubCategory::find($subCategoryId);

        if ($subCategory) {
            $request->validate([
                'sub_category_title' => 'required|string|max:255|unique:sub_categories,sub_category_title,' . $subCategoryId
            ]);

            $subCategory->update([
                'sub_category_title' => $request->sub_category_title,
                'updated_at' => Carbon::now(),
            ]);

            Cache::forget('sub_categories');

            return response()->json(['message' => 'SubCategory Updated.'], 200);
        }
    }

    // get collection subCategory
    public function getCollectionSubCategory()
    {
        // fetch subCategory
        $subCategory = SubCategory::all(['id', 'sub_category_title']);
        return response()->json(['subCategory' => $subCategory]);
    }
}
