<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index()
    {
        $variant = request()->get('variant');
        $title = request()->get('title');
        $price_from = request()->get('price_from');
        $price_to = request()->get('price_to');
        $date = request()->get('date');

        if (!is_null($title)) {
            $products = Product::with(['productVariants', 'productVariantPrices'])
                ->orWhere('title', 'like', "%$title%")
                ->paginate(2);
        } elseif (!is_null($date)) {
            $products = Product::with(['productVariants', 'productVariantPrices'])
                ->whereDate('created_at', $date)
                ->paginate(2);
        } elseif (!is_null($variant)) {
            $products = Product::with(['productVariants', 'productVariantPrices'])
                ->whereHas('productVariants', function ($query) use ($variant) {
                    $query->where('variant', $variant);
                })->paginate(2);
        } elseif (!is_null($price_from) && !is_null($price_to)) {
            $products = Product::with(['productVariants', 'productVariantPrices'])
                ->whereHas('productVariantPrices', function ($query) use ($price_from, $price_to) {
                    $query->whereBetween('price', array($price_from, $price_to));
                })->paginate(2);
        } elseif (!is_null($title) && !is_null($date) && !is_null($variant) && !is_null($price_from) && !is_null($price_to)) {
            $products = Product::with(['productVariants', 'productVariantPrices'])
                ->orWhere('title', 'like', "%$title%")
                ->whereDate('created_at', $date)
                ->whereHas('productVariants', function ($query) use ($variant) {
                    $query->where('variant', $variant);
                })->whereHas('productVariantPrices', function ($query) use ($price_from, $price_to) {
                    $query->whereBetween('price', array($price_from, $price_to));
                })->paginate(2);
        } else {
            $products = Product::with(['productVariants', 'productVariantPrices'])->paginate(2);
        }


        $variants = Variant::with('productVariants')->get();
        return view('products.index', compact('products', 'variants'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if ($request->file('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filename = str_replace(' ', '', $filename);
            $file->storeAs('uploads', $filename);

            return response()->json(['message' => 'Product Images Uploaded', 'data' => $filename], 200);
        } else {
            $product = Product::create([
                'title' => $request->title,
                'sku' => $request->sku,
                'description' => $request->description
            ]);

            $productImages = $request->product_image;

            if (!is_null($productImages)) {
                foreach ($productImages as $pi) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'file_path' => Storage::disk('public')->url("uploads/" . $pi),
                        'thumbnail' => true
                    ]);
                }
            }

            $productVariants = $request->product_variant;
            if (!is_null($productVariants)) {
                foreach ($request->product_variant as $prodV) {
                    foreach ($prodV['tags'] as $tag) {
                        ProductVariant::create([
                            'product_id' => $product->id,
                            'variant' => $tag,
                            'variant_id' => $prodV['option']
                        ]);
                    }
                }
            }

            if (!is_null($request->product_variant_prices)) {

                foreach ($request->product_variant_prices as $pvp) {
                    $pvpTitle = array_filter(explode('/', $pvp['title']));

                    if (count($pvpTitle) <= 1) {
                        $product_variant_one = ProductVariant::where([
                            'variant' => $pvpTitle[0],
                            'product_id' => $product->id
                        ])->first();

                        ProductVariantPrice::create([
                            'product_variant_one' => $product_variant_one->id,
                            'price' => $pvp['price'],
                            'stock' => $pvp['stock'],
                            'product_id' => $product->id
                        ]);

                    } elseif (count($pvpTitle) <= 2) {
                        $product_variant_one = ProductVariant::where([
                            'variant' => $pvpTitle[0],
                            'product_id' => $product->id
                        ])->first();

                        $product_variant_two = ProductVariant::where([
                            'variant' => $pvpTitle[1],
                            'product_id' => $product->id
                        ])->first();

                        ProductVariantPrice::create([
                            'product_variant_one' => $product_variant_one->id,
                            'product_variant_two' => $product_variant_two->id,
                            'price' => $pvp['price'],
                            'stock' => $pvp['stock'],
                            'product_id' => $product->id
                        ]);

                    } elseif (count($pvpTitle) <= 3) {
                        $product_variant_one = ProductVariant::where([
                            'variant' => $pvpTitle[0],
                            'product_id' => $product->id
                        ])->first();

                        $product_variant_two = ProductVariant::where([
                            'variant' => $pvpTitle[1],
                            'product_id' => $product->id
                        ])->first();

                        $product_variant_three = ProductVariant::where([
                            'variant' => $pvpTitle[2],
                            'product_id' => $product->id
                        ])->first();

                        ProductVariantPrice::create([
                            'product_variant_one' => $product_variant_one->id,
                            'product_variant_two' => $product_variant_two->id,
                            'product_variant_three' => $product_variant_three->id,
                            'price' => $pvp['price'],
                            'stock' => $pvp['stock'],
                            'product_id' => $product->id
                        ]);
                    }
                }

            }

        }

        return !is_null($product) ? response()->json(['message' => 'Product created successfully', 'data' => $product], 200)
            : response()->json(['message' => 'Something went wrong'], 500);
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $variants = Variant::all();
        $editableProduct = Product::with(['productVariants', 'productVariantPrices'])->findOrFail($product->id);

        return view('products.edit', compact('variants', 'editableProduct'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
