<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\APIHelpers;
use Illuminate\Support\Facades\DB;
use App\Shop;
use App\Slider;
use App\Category;
use App\Product;
use App\Favorite;


class ShopController extends Controller {
    // store categories
    public function store_categories(Request $request, $id) {
        $data['store'] = Shop::select('id', 'cover', 'logo', 'name')->where('id', $id)->first()->makeHidden('custom');
        if (!isset($data['store']['id'])) {
            $response = APIHelpers::createApiResponse(true , 406 , 'Store does not exist' , 'هذا المتجر غير موجود' , null , $request->lang);
            return response()->json($response , 406);
        }
        $products = Product::where('store_id', $id)->where('deleted' , 0)->where('hidden' , 0)->where('remaining_quantity', '>', 0)->pluck('category_id')->toArray();
        if($request->lang == 'en'){
            $data['categories'] = Category::select('id', 'title_en as title', 'image')->where('deleted', 0)->whereIn('id', $products)
            ->orderBy('id', 'desc')->get();
        }else {
            $data['categories'] = Category::select('id', 'title_ar as title', 'image')->where('deleted', 0)->whereIn('id', $products)
            ->orderBy('id', 'desc')->get();
        }

        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $data , $request->lang);
        return response()->json($response , 200);
    }

    // get store products
    public function get_store_products(Request $request, $storeId) {
        $categoryId = $request->query('category_id');
        $size = $request->query('size_id');
        $type = $request->query('type');
        $from = $request->query('from');
        $to = $request->query('to');
        $slider = Slider::where('type', 2)->first();
        $data['slider'] = $slider->ads;
        $productsArr = Product::where('deleted', 0)->where('hidden', 0)->where('store_id' , $storeId)->pluck("category_id");
        if($request->lang == 'en'){
            $data['categories'] = Category::where('deleted', 0)->whereIn('id', $productsArr)->select('id', 'title_en as title')
            ->orderBy('id', 'desc')->get();
        }else {
            $data['categories'] = Category::where('deleted', 0)->whereIn('id', $productsArr)->select('id', 'title_ar as title')
            ->orderBy('id', 'desc')->get();
        }
        
            if ($request->lang == 'en') {
                $products = Product::select('id', 'title_en as title' , 'category_id', 'final_price', 'price_before_offer', 'offer' , 'type', 'remaining_quantity', 'offer_percentage', 'multi_options' )->where('deleted', 0)->where('hidden', 0)->where('store_id' , $storeId);
            }else {
                $products = Product::select('id', 'title_ar as title' , 'category_id', 'final_price', 'price_before_offer', 'offer' , 'type', 'remaining_quantity', 'offer_percentage', 'multi_options' )->where('deleted', 0)->where('hidden', 0)->where('store_id' , $storeId);
            }
            
            if (!empty($categoryId) && $categoryId != 0) {
                $products = $products->where('category_id', $categoryId);
            }

            if (!empty($size) && $size != 0) {
                $products = $products->whereHas('multiOptions', function($q) use ($size) {
                    $q->where('multi_option_value_id', $size);
                });
            }
            if (!empty($type) && $type != 0) {
                $products = $products->where('type', $type);
            }
            if ((!empty($from) && $from != 0) && (!empty($to) && $to != 0)) {
                $products = $products->whereBetween('final_price', [$from , $to]);
            }
            
        
        $products = $products->simplePaginate(10);
        $products->makeHidden(['multiOptions', 'mainImage']);
            
        if (count($products) > 0) {
            for ($i = 0; $i < count($products); $i ++) {
                $products[$i]['image'] = "";
                if (!empty($products[$i]->mainImage)) {
                    $products[$i]['image'] = $products[$i]->mainImage['image'];
                }
				if(auth()->user()){
                    $user_id = auth()->user()->id;
        
                    $prevfavorite = Favorite::where('product_id' , $products[$i]['id'])->where('user_id' , $user_id)->first();
                    if($prevfavorite){
                        $products[$i]['favorite'] = true;
                    }else{
                        $products[$i]['favorite'] = false;
                    }
        
                }else{
                    $products[$i]['favorite'] = false;
                }
                if ($products[$i]['multi_options'] == 1) {
                    if (count($products[$i]['multiOptions']) > 0) {
                        $products[$i]['final_price'] = $products[$i]['multiOptions'][0]['final_price'];
                        $products[$i]['price_before_offer'] = $products[$i]['multiOptions'][0]['price_before_offer'];
                    }
                }
                unset($products[$i]['multi_options']);
            }
        }

        if (count($data['categories']) > 0) {
            for ($n = 0; $n < count($data['categories']); $n ++) {
                $data['categories'][$n]['selected'] = false;
                if ($data['categories'][$n]['id'] == $categoryId) {
                    $data['categories'][$n]['selected'] = true;
                }
            }
        }
       
        
        $data['store'] = Shop::where('id', $storeId)->select('id', 'name', 'logo')->first()->makeHidden('custom');
    
        $data['products'] = $products;
        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $data , $request->lang);
        return response()->json($response , 200);
    }
}