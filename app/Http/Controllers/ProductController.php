<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Variant;
use Illuminate\Http\Request;

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
        return view('products.edit', compact('variants'));
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
