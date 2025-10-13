<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Get inventory overview dashboard data
     */
    public function getInventoryOverview()
    {
        $totalItems = Product::count();
        $lowStockItems = Product::where('stock', '>', 0)->where('stock', '<=', 10)->count();
        $outOfStockItems = Product::where('stock', '=', 0)->count();
        $inStockItems = Product::where('stock', '>', 10)->count();

        // Get total inventory value
        $totalInventoryValue = Product::sum(DB::raw('stock * price'));

        return response()->json([
            'overview' => [
                'total_items' => $totalItems,
                'low_stock' => $lowStockItems,
                'out_of_stock' => $outOfStockItems,
                'in_stock' => $inStockItems,
                'total_inventory_value' => round($totalInventoryValue, 2),
                'formatted_inventory_value' => '₦' . number_format($totalInventoryValue, 2)
            ]
        ]);
    }

    /**
     * Get detailed stock levels for all products
     */
    public function getStockLevels(Request $request)
    {
        $query = Product::with('category');

        // Filter by stock status
        if ($request->has('status')) {
            switch ($request->status) {
                case 'low':
                    $query->where('stock', '>', 0)->where('stock', '<=', 10);
                    break;
                case 'out':
                    $query->where('stock', '=', 0);
                    break;
                case 'in_stock':
                    $query->where('stock', '>', 10);
                    break;
            }
        }

        // Filter by category
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->where('category_id', $request->category_id);
        }

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort by stock level or other fields
        $sortBy = $request->get('sort_by', 'stock');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $allowedSortFields = ['name', 'stock', 'price', 'created_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder === 'desc' ? 'desc' : 'asc');
        }

        $perPage = $request->get('per_page', 20);
        $products = $query->paginate($perPage);

        $formattedProducts = $products->getCollection()->map(function ($product) {
            return $this->formatInventoryItem($product);
        });

        return response()->json([
            'stock_levels' => $formattedProducts,
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
            ]
        ]);
    }

    /**
     * Get low stock alerts
     */
    public function getLowStockAlerts(Request $request)
    {
        $threshold = $request->get('threshold', 10);
        
        $lowStockProducts = Product::with('category')
            ->where('stock', '>', 0)
            ->where('stock', '<=', $threshold)
            ->orderBy('stock', 'asc')
            ->get();

        $formattedProducts = $lowStockProducts->map(function ($product) {
            return $this->formatInventoryItem($product);
        });

        return response()->json([
            'low_stock_alerts' => $formattedProducts,
            'threshold' => $threshold,
            'count' => $lowStockProducts->count()
        ]);
    }

    /**
     * Get out of stock items
     */
    public function getOutOfStockItems()
    {
        $outOfStockProducts = Product::with('category')
            ->where('stock', '=', 0)
            ->orderBy('updated_at', 'desc')
            ->get();

        $formattedProducts = $outOfStockProducts->map(function ($product) {
            return $this->formatInventoryItem($product);
        });

        return response()->json([
            'out_of_stock_items' => $formattedProducts,
            'count' => $outOfStockProducts->count()
        ]);
    }

    /**
     * Update stock for a single product
     */
    public function updateStock(Request $request, Product $product)
    {
        $request->validate([
            'stock' => 'required|integer|min:0',
            'reason' => 'nullable|string|max:255',
            'adjustment_type' => 'required|in:set,add,subtract'
        ]);

        $oldStock = $product->stock;
        $newStock = $request->stock;
        $adjustmentType = $request->adjustment_type;

        switch ($adjustmentType) {
            case 'add':
                $newStock = $oldStock + $request->stock;
                break;
            case 'subtract':
                $newStock = max(0, $oldStock - $request->stock);
                break;
            case 'set':
            default:
                // Use the provided stock value as is
                break;
        }

        $product->update(['stock' => $newStock]);

        // Log the stock change (you might want to create a separate stock_movements table)
        \Log::info("Stock updated for product {$product->id}", [
            'product_name' => $product->name,
            'old_stock' => $oldStock,
            'new_stock' => $newStock,
            'adjustment_type' => $adjustmentType,
            'reason' => $request->reason,
            'updated_by' => auth()->id() ?? 'system'
        ]);

        return response()->json([
            'message' => 'Stock updated successfully',
            'product' => $this->formatInventoryItem($product->fresh()),
            'old_stock' => $oldStock,
            'new_stock' => $newStock,
            'difference' => $newStock - $oldStock
        ]);
    }

    /**
     * Bulk update stock for multiple products
     */
    public function bulkUpdateStock(Request $request)
    {
        $request->validate([
            'updates' => 'required|array',
            'updates.*.product_id' => 'required|exists:products,id',
            'updates.*.stock' => 'required|integer|min:0',
            'updates.*.adjustment_type' => 'required|in:set,add,subtract',
            'reason' => 'nullable|string|max:255'
        ]);

        $updates = $request->updates;
        $updatedProducts = [];
        $errors = [];

        foreach ($updates as $update) {
            try {
                $product = Product::findOrFail($update['product_id']);
                $oldStock = $product->stock;
                $newStock = $update['stock'];
                $adjustmentType = $update['adjustment_type'];

                switch ($adjustmentType) {
                    case 'add':
                        $newStock = $oldStock + $update['stock'];
                        break;
                    case 'subtract':
                        $newStock = max(0, $oldStock - $update['stock']);
                        break;
                    case 'set':
                    default:
                        // Use the provided stock value as is
                        break;
                }

                $product->update(['stock' => $newStock]);

                $updatedProducts[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'difference' => $newStock - $oldStock
                ];

                // Log the stock change
                \Log::info("Bulk stock update for product {$product->id}", [
                    'product_name' => $product->name,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'adjustment_type' => $adjustmentType,
                    'reason' => $request->reason,
                    'updated_by' => auth()->id() ?? 'system'
                ]);

            } catch (\Exception $e) {
                $errors[] = [
                    'product_id' => $update['product_id'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'message' => 'Bulk stock update completed',
            'updated_products' => $updatedProducts,
            'errors' => $errors,
            'success_count' => count($updatedProducts),
            'error_count' => count($errors)
        ]);
    }

    /**
     * Get inventory statistics by category
     */
    public function getInventoryStatsByCategory()
    {
        $stats = Category::with('products')
            ->get()
            ->map(function ($category) {
                $products = $category->products;
                $totalItems = $products->count();
                $totalStock = $products->sum('stock');
                $totalValue = $products->sum(function ($product) {
                    return $product->stock * $product->price;
                });
                $lowStockItems = $products->where('stock', '>', 0)->where('stock', '<=', 10)->count();
                $outOfStockItems = $products->where('stock', '=', 0)->count();

                return [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'total_items' => $totalItems,
                    'total_stock' => $totalStock,
                    'total_value' => round($totalValue, 2),
                    'formatted_total_value' => '₦' . number_format($totalValue, 2),
                    'low_stock_items' => $lowStockItems,
                    'out_of_stock_items' => $outOfStockItems,
                    'in_stock_items' => $totalItems - $outOfStockItems,
                    'average_stock_per_item' => $totalItems > 0 ? round($totalStock / $totalItems, 2) : 0
                ];
            });

        return response()->json([
            'category_stats' => $stats
        ]);
    }

    /**
     * Get inventory movement history (requires a separate movements table)
     */
    public function getInventoryMovements(Request $request)
    {
        // This would require creating a stock_movements table
        // For now, return a placeholder response
        return response()->json([
            'message' => 'Inventory movements tracking requires implementing a stock_movements table',
            'movements' => []
        ]);
    }

    /**
     * Generate inventory report
     */
    public function generateInventoryReport(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $categoryId = $request->get('category_id');

        $query = Product::with('category');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->get();

        $report = [
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'category_id' => $categoryId
            ],
            'summary' => [
                'total_products' => $products->count(),
                'total_stock_units' => $products->sum('stock'),
                'total_inventory_value' => $products->sum(function ($product) {
                    return $product->stock * $product->price;
                }),
                'low_stock_count' => $products->where('stock', '>', 0)->where('stock', '<=', 10)->count(),
                'out_of_stock_count' => $products->where('stock', '=', 0)->count(),
            ],
            'products' => $products->map(function ($product) {
                return $this->formatInventoryItem($product);
            })
        ];

        $report['summary']['formatted_inventory_value'] = '₦' . number_format($report['summary']['total_inventory_value'], 2);

        return response()->json([
            'inventory_report' => $report
        ]);
    }

    /**
     * Set reorder points for products
     */
    public function setReorderPoint(Request $request, Product $product)
    {
        $request->validate([
            'reorder_point' => 'required|integer|min:0',
            'reorder_quantity' => 'nullable|integer|min:1'
        ]);

        // Note: This would require adding reorder_point and reorder_quantity columns to products table
        // For now, we'll just return a response indicating this feature needs database migration

        return response()->json([
            'message' => 'Reorder point functionality requires adding reorder_point and reorder_quantity columns to products table',
            'suggested_migration' => [
                'reorder_point' => $request->reorder_point,
                'reorder_quantity' => $request->reorder_quantity
            ]
        ]);
    }

    /**
     * Format product data for inventory display
     */
    private function formatInventoryItem($product)
    {
        $stockStatus = $this->getStockStatus($product->stock);
        $stockLevel = $this->getStockLevel($product->stock);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => 'PRD-' . str_pad($product->id, 3, '0', STR_PAD_LEFT), // Generate SKU
            'stock' => $product->stock,
            'price' => $product->price,
            'formatted_price' => '₦' . number_format($product->price, 2),
            'total_value' => $product->stock * $product->price,
            'formatted_total_value' => '₦' . number_format($product->stock * $product->price, 2),
            'stock_status' => $stockStatus,
            'stock_level' => $stockLevel,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
            ] : null,
            'last_updated' => $product->updated_at->format('Y-m-d H:i:s'),
            'formatted_last_updated' => $product->updated_at->format('M d, Y g:i A'),
            'warehouse_location' => 'Warehouse A', // Placeholder - would need warehouse management
            'reorder_point' => 10, // Placeholder - would need reorder_point column
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
            return 'Critical';
        } elseif ($stock <= 10) {
            return 'Low Stock';
        } elseif ($stock <= 25) {
            return 'Limited';
        } else {
            return 'In Stock';
        }
    }

    /**
     * Get stock level percentage (for progress bars)
     */
    private function getStockLevel($stock)
    {
        $maxStock = 100; // You could make this configurable per product
        return min(100, ($stock / $maxStock) * 100);
    }
}