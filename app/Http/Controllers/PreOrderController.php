<?php

namespace App\Http\Controllers;

use App\Models\PreOrder;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PreOrderController extends Controller
{
    /**
     * Display a listing of the pre-orders.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PreOrder::with('category');

        // Add search functionality
        if ($request->has('search')) {
            $searchTerm = $request->get('search');
            $query->where('product_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('specifications', 'LIKE', "%{$searchTerm}%");
        }

        // Add category filtering
        if ($request->has('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        // Add sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $preOrders = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $preOrders,
            'message' => 'Pre-orders retrieved successfully'
        ]);
    }

    /**
     * Store a newly created pre-order.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'pre_order_price' => 'required|numeric|min:0',
            'deposit_percentage' => 'required|numeric|min:0|max:100',
            'expected_availability' => 'required|string|max:255',
            'power_output' => 'nullable|string|max:255',
            'warranty_period' => 'nullable|string|max:255',
            'specifications' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Handle image uploads
        if ($request->hasFile('images')) {
            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('pre-orders', 'public');
                $imagePaths[] = $path;
            }
            $data['images'] = $imagePaths;
        }

        $preOrder = PreOrder::create($data);
        $preOrder->load('category');

        return response()->json([
            'success' => true,
            'data' => $preOrder,
            'message' => 'Pre-order created successfully'
        ], 201);
    }

    /**
     * Display the specified pre-order.
     */
    public function show(PreOrder $preOrder): JsonResponse
    {
        $preOrder->load('category');

        return response()->json([
            'success' => true,
            'data' => $preOrder,
            'message' => 'Pre-order retrieved successfully'
        ]);
    }

    /**
     * Update the specified pre-order.
     */
    public function update(Request $request, PreOrder $preOrder): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_name' => 'sometimes|required|string|max:255',
            'category_id' => 'sometimes|required|exists:categories,id',
            'pre_order_price' => 'sometimes|required|numeric|min:0',
            'deposit_percentage' => 'sometimes|required|numeric|min:0|max:100',
            'expected_availability' => 'sometimes|required|string|max:255',
            'power_output' => 'nullable|string|max:255',
            'warranty_period' => 'nullable|string|max:255',
            'specifications' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Handle image removal
        if ($request->has('remove_images')) {
            $currentImages = $preOrder->images ?? [];
            $imagesToRemove = $request->get('remove_images');
            
            foreach ($imagesToRemove as $imageToRemove) {
                if (in_array($imageToRemove, $currentImages)) {
                    Storage::disk('public')->delete($imageToRemove);
                    $currentImages = array_diff($currentImages, [$imageToRemove]);
                }
            }
            
            $data['images'] = array_values($currentImages);
        }

        // Handle new image uploads
        if ($request->hasFile('images')) {
            $existingImages = $data['images'] ?? $preOrder->images ?? [];
            $newImagePaths = [];
            
            foreach ($request->file('images') as $image) {
                $path = $image->store('pre-orders', 'public');
                $newImagePaths[] = $path;
            }
            
            $data['images'] = array_merge($existingImages, $newImagePaths);
        }

        $preOrder->update($data);
        $preOrder->load('category');

        return response()->json([
            'success' => true,
            'data' => $preOrder,
            'message' => 'Pre-order updated successfully'
        ]);
    }

    /**
     * Remove the specified pre-order.
     */
    public function destroy(PreOrder $preOrder): JsonResponse
    {
        // Delete associated images
        if ($preOrder->images) {
            foreach ($preOrder->images as $imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        $preOrder->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pre-order deleted successfully'
        ]);
    }

    /**
     * Get all categories for the dropdown
     */
    public function getCategories(): JsonResponse
    {
        $categories = Category::select('id', 'name')->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
            'message' => 'Categories retrieved successfully'
        ]);
    }

    /**
     * Get pre-orders by category
     */
    public function byCategory(int $categoryId): JsonResponse
    {
        $preOrders = PreOrder::with('category')
            ->where('category_id', $categoryId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $preOrders,
            'message' => 'Pre-orders retrieved successfully'
        ]);
    }

    /**
     * Search pre-orders
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        
        if (empty($query)) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No search query provided'
            ]);
        }

        $preOrders = PreOrder::with('category')
            ->where('product_name', 'LIKE', "%{$query}%")
            ->orWhere('specifications', 'LIKE', "%{$query}%")
            ->orWhere('power_output', 'LIKE', "%{$query}%")
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $preOrders,
            'message' => 'Search results retrieved successfully'
        ]);
    }
}
