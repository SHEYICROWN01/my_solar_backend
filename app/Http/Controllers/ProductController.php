<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of products with enhanced filtering and search
     */
    public function index(Request $request)
    {
        $query = Product::with('category');

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('power', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by price range
        if ($request->has('min_price') && !empty($request->min_price)) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price') && !empty($request->max_price)) {
            $query->where('price', '<=', $request->max_price);
        }

        // Filter by stock availability
        if ($request->has('in_stock') && $request->in_stock == 'true') {
            $query->where('stock', '>', 0);
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validate sort parameters
        $allowedSortFields = ['name', 'price', 'stock', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        // Pagination
        $perPage = $request->get('per_page', 20); // Default 20 products per page
        $perPage = min($perPage, 100); // Maximum 100 products per page

        if ($request->has('paginated') && $request->paginated === 'false') {
            // Return all products without pagination
            $products = $query->get();
            
            $formattedProducts = $products->map(function ($product) {
                return $this->formatProduct($product);
            });

            return response()->json([
                'products' => $formattedProducts,
                'total' => $products->count(),
                'paginated' => false,
            ]);
        } else {
            // Return paginated results
            $products = $query->paginate($perPage);
            
            $formattedProducts = $products->getCollection()->map(function ($product) {
                return $this->formatProduct($product);
            });

            return response()->json([
                'products' => $formattedProducts,
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ],
                'paginated' => true,
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Debug: Log incoming request data
        \Log::info('Product store request data:', [
            'has_images' => $request->hasFile('images'),
            'images_count' => $request->hasFile('images') ? count($request->file('images')) : 0,
            'request_all' => $request->all(),
            'php_max_upload' => ini_get('upload_max_filesize'),
            'php_max_post' => ini_get('post_max_size'),
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'required|string',
            'power' => 'nullable|string|max:255',
            'warranty' => 'nullable|string|max:255',
            'specifications' => 'nullable|array',
            'images' => 'nullable|array|max:10',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $data = $request->only([
            'name', 'category_id', 'price', 'stock', 'description', 'power', 'warranty', 'specifications'
        ]);

        // Handle image uploads with better error handling
        $imagePaths = [];
        $uploadErrors = [];
        
        if ($request->hasFile('images')) {
            $images = $request->file('images');
            
            // Ensure images is an array
            if (!is_array($images)) {
                $images = [$images];
            }

            \Log::info('Processing ' . count($images) . ' images for upload');

            foreach ($images as $index => $image) {
                \Log::info("Processing image {$index}:", [
                    'original_name' => $image ? $image->getClientOriginalName() : 'null',
                    'size' => $image ? $image->getSize() : 'null',
                    'mime_type' => $image ? $image->getMimeType() : 'null',
                    'is_valid' => $image ? $image->isValid() : false,
                    'error_code' => $image ? $image->getError() : 'null'
                ]);

                // Check if image exists and is valid
                if ($image && $image->isValid()) {
                    try {
                        // Additional size check (5MB = 5120KB)
                        $maxSize = 5 * 1024 * 1024; // 5MB in bytes
                        if ($image->getSize() > $maxSize) {
                            $uploadErrors[] = "Image " . ($index + 1) . " (" . $image->getClientOriginalName() . "): File size " . round($image->getSize() / 1024 / 1024, 2) . "MB exceeds maximum allowed size of 5MB";
                            \Log::warning("Image {$index} exceeds size limit: " . $image->getSize() . " bytes");
                            continue;
                        }

                        $filename = time() . '_' . $index . '_' . $image->getClientOriginalName();
                        $path = $image->storeAs('products', $filename, 'public');
                        $imagePaths[] = $path;
                        
                        \Log::info("Successfully stored image {$index}: {$path}");
                    } catch (\Exception $e) {
                        $uploadErrors[] = "Image " . ($index + 1) . " (" . ($image->getClientOriginalName() ?? 'unknown') . "): " . $e->getMessage();
                        \Log::error("Failed to store image {$index}: " . $e->getMessage());
                    }
                } else {
                    if ($image) {
                        $error = $image->getError();
                        $errorMessage = $this->getUploadErrorMessage($error);
                        $fileName = $image->getClientOriginalName() ?? 'unknown';
                        $uploadErrors[] = "Image " . ($index + 1) . " ({$fileName}): {$errorMessage}";
                        \Log::error("Image {$index} upload error: {$errorMessage} (Error code: {$error})");
                    } else {
                        $uploadErrors[] = "Image " . ($index + 1) . ": No file received or file is null";
                        \Log::warning("Image {$index} is null or empty");
                    }
                }
            }
        }

        $data['images'] = $imagePaths;

        \Log::info('Final image paths to store:', $imagePaths);
        \Log::info('Upload errors:', $uploadErrors);

        $product = Product::create($data);

        $response = [
            'message' => 'Product created successfully',
            'product' => $product->load('category'),
            'images_uploaded' => count($imagePaths),
            'total_images_processed' => $request->hasFile('images') ? count($request->file('images')) : 0,
        ];

        if (!empty($uploadErrors)) {
            $response['upload_errors'] = $uploadErrors;
            $response['message'] = 'Product created with some image upload issues';
        }

        return response()->json($response, 201);
    }

    /**
     * Get human-readable upload error message
     */
    private function getUploadErrorMessage($errorCode)
    {
        switch ($errorCode) {
            case UPLOAD_ERR_OK:
                return 'Upload successful';
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'File too large';
            case UPLOAD_ERR_PARTIAL:
                return 'File only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return response()->json([
            'product' => $product->load('category'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        // Debug: Log incoming request data
        \Log::info('Product update request data:', [
            'has_images' => $request->hasFile('images'),
            'images_count' => $request->hasFile('images') ? count($request->file('images')) : 0,
            'all_files' => $request->allFiles(),
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'required|string',
            'power' => 'nullable|string|max:255',
            'warranty' => 'nullable|string|max:255',
            'specifications' => 'nullable|array',
            'images' => 'nullable|array|max:10', // Allow up to 10 images
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $data = $request->only([
            'name', 'category_id', 'price', 'stock', 'description', 'power', 'warranty', 'specifications'
        ]);

        // Handle image uploads
        if ($request->hasFile('images')) {
            // Delete old images
            if ($product->images) {
                foreach ($product->images as $oldImage) {
                    Storage::disk('public')->delete($oldImage);
                }
            }

            $imagePaths = [];
            $images = $request->file('images');
            
            // Ensure images is an array
            if (!is_array($images)) {
                $images = [$images];
            }

            foreach ($images as $index => $image) {
                if ($image && $image->isValid()) {
                    $filename = time() . '_' . $index . '_' . $image->getClientOriginalName();
                    $path = $image->storeAs('products', $filename, 'public');
                    $imagePaths[] = $path;
                    
                    \Log::info("Updated/Stored image {$index}: {$path}");
                }
            }
            
            $data['images'] = $imagePaths;
            \Log::info('Final image paths to update:', $imagePaths);
        }

        $product->update($data);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product->load('category'),
            'images_uploaded' => isset($data['images']) ? count($data['images']) : 0,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        // Delete images
        if ($product->images) {
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }

    /**
     * Get featured or recommended products
     */
    public function featured(Request $request)
    {
        $limit = $request->get('limit', 8);
        
        // Get products with highest stock or most recent
        $products = Product::with('category')
            ->where('stock', '>', 0)
            ->orderBy('stock', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $formattedProducts = $products->map(function ($product) {
            return $this->formatProduct($product);
        });

        return response()->json([
            'featured_products' => $formattedProducts,
        ]);
    }

    /**
     * Get products by category
     */
    public function byCategory(Request $request, $categoryId)
    {
        $perPage = $request->get('per_page', 20);
        
        $products = Product::with('category')
            ->where('category_id', $categoryId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $formattedProducts = $products->getCollection()->map(function ($product) {
            return $this->formatProduct($product);
        });

        return response()->json([
            'products' => $formattedProducts,
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Get products by category slug
     */
    public function byCategorySlug(Request $request, $slug)
    {
        // Find category by slug
        $category = \App\Models\Category::where('slug', $slug)->first();
        
        if (!$category) {
            return response()->json([
                'error' => 'Category not found',
                'slug' => $slug
            ], 404);
        }

        $perPage = $request->get('per_page', 20);
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Build query for products in this category
        $query = Product::with('category')
            ->where('category_id', $category->id);

        // Apply search if provided
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('power', 'like', "%{$search}%");
            });
        }

        // Apply price filters
        if ($request->has('min_price') && !empty($request->min_price)) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price') && !empty($request->max_price)) {
            $query->where('price', '<=', $request->max_price);
        }

        // Filter by stock availability
        if ($request->has('in_stock') && $request->in_stock == 'true') {
            $query->where('stock', '>', 0);
        }

        // Apply sorting
        $allowedSortFields = ['name', 'price', 'stock', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        // Check if pagination is requested
        if ($request->has('paginated') && $request->paginated === 'false') {
            // Return all products without pagination
            $products = $query->get();
            
            $formattedProducts = $products->map(function ($product) {
                return $this->formatProduct($product);
            });

            return response()->json([
                'products' => $formattedProducts,
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                ],
                'total' => $products->count(),
                'paginated' => false,
            ]);
        } else {
            // Return paginated results
            $products = $query->paginate($perPage);
            
            $formattedProducts = $products->getCollection()->map(function ($product) {
                return $this->formatProduct($product);
            });

            return response()->json([
                'products' => $formattedProducts,
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                ],
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ],
                'paginated' => true,
            ]);
        }
    }

    /**
     * Search products with autocomplete suggestions
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 10);

        if (empty($query) || strlen($query) < 2) {
            return response()->json([
                'suggestions' => [],
                'products' => [],
            ]);
        }

        // Get product suggestions
        $products = Product::with('category')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('power', 'like', "%{$query}%");
            })
            ->where('stock', '>', 0)
            ->limit($limit)
            ->get();

        $formattedProducts = $products->map(function ($product) {
            return $this->formatProduct($product);
        });

        // Generate search suggestions
        $suggestions = $products->pluck('name')->unique()->take(5)->values();

        return response()->json([
            'suggestions' => $suggestions,
            'products' => $formattedProducts,
            'query' => $query,
        ]);
    }

    /**
     * Format product data for API response
     */
    private function formatProduct($product)
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'formatted_price' => '₦' . number_format($product->price, 2),
            'stock' => $product->stock,
            'in_stock' => $product->stock > 0,
            'stock_status' => $this->getStockStatus($product->stock),
            'power' => $product->power,
            'warranty' => $product->warranty,
            'specifications' => $product->specifications ?? [],
            'images' => $product->images ? array_map(function($image) {
                return url('storage/' . $image);
            }, $product->images) : [],
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug ?? null,
            ] : null,
            'created_at' => $product->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $product->updated_at->format('Y-m-d H:i:s'),
            'formatted_date' => $product->created_at->format('M d, Y'),
            'is_new' => $product->created_at->diffInDays(now()) <= 30, // Consider new if created within 30 days
        ];
    }

    /**
     * Get stock status text
     */
    private function getStockStatus($stock)
    {
        if ($stock <= 0) {
            return 'Out of Stock';
        } elseif ($stock <= 5) {
            return 'Low Stock';
        } elseif ($stock <= 20) {
            return 'Limited Stock';
        } else {
            return 'In Stock';
        }
    }
}
