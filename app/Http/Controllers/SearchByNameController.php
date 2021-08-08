<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\ProductImage;
use App\Category;
use App\Favorite;
use App\Shop;
use App\Helpers\APIHelpers;

class SearchByNameController extends Controller
{
        public function Search(Request $request)
        {
            $search = $request->query('search');

            if(! $search){
                $response = APIHelpers::createApiResponse(true , 406 , 'Missing Required Fields' , 'بعض الحقول مفقودة' , null, $request->lang);
                return response()->json($response , 406);
            }

            if($request->lang == 'en'){
                $products = Product::select('title_en as title'  , 'id' , 'offer' , 'price_before_offer' , 'final_price' , 'offer_percentage' , 'category_id', 'multi_options')->where('deleted' , 0)->where('hidden' , 0)->where('remaining_quantity', '>', 0)->Where(function($query) use ($search) {
                    $query->Where('title_en', 'like', '%' . $search . '%')->orWhere('title_ar', 'like', '%' . $search . '%');
                })->get()->makeHidden('multiOptions'); 
                if (count($products) > 0) {
                    for($i = 0; $i < count($products); $i ++) {
                        if ($products[$i]['multi_options'] != 0) {
                            $products[$i]['price_before_offer'] = $products[$i]->multiOptions[0]->price_before_offer;
                            $products[$i]['final_price'] = $products[$i]->multiOptions[0]->final_price;
                        }
                        unset($products[$i]['multi_options']);
                    }
                }
            }else{
                $products = Product::select('title_ar as title'  , 'id' , 'offer' , 'price_before_offer' , 'final_price' , 'offer_percentage' , 'category_id', 'multi_options')->where('deleted' , 0)->where('hidden' , 0)->where('remaining_quantity', '>', 0)->Where(function($query) use ($search) {
                    $query->Where('title_en', 'like', '%' . $search . '%')->orWhere('title_ar', 'like', '%' . $search . '%');
                })->get()->makeHidden('multiOptions');
                if (count($products) > 0) {
                    for($i = 0; $i < count($products); $i ++) {
                        if ($products[$i]['multi_options'] != 0) {
                            $products[$i]['price_before_offer'] = $products[$i]->multiOptions[0]->price_before_offer;
                            $products[$i]['final_price'] = $products[$i]->multiOptions[0]->final_price;
                        }
                        unset($products[$i]['multi_options']);
                    }
                }
            }

            for($i =0; $i < count($products); $i++){
                $products[$i]['image'] = ProductImage::where('product_id' , $products[$i]['id'])->pluck('image')->first();
				if($request->lang == 'en'){
				$products[$i]['category_name'] = Category::where('id' , $products[$i]['category_id'])->pluck('title_en')->first();
				}else{
				$products[$i]['category_name'] = Category::where('id' , $products[$i]['category_id'])->pluck('title_ar')->first();
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
            }


            $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $products , $request->lang) ;
            return response()->json($response , 200);
        }
        
        public function Search2(Request $request)
        {
            $search = $request->query('search');

            if(! $search){
                $response = APIHelpers::createApiResponse(true , 406 , 'Missing Required Fields' , 'بعض الحقول مفقودة' , null, $request->lang);
                return response()->json($response , 406);
            }

            if($request->lang == 'en'){
                $products = Product::select('title_en as title'  , 'id' , 'offer' , 'price_before_offer' , 'final_price' , 'offer_percentage' , 'category_id', 'multi_options')->where('deleted' , 0)->where('hidden' , 0)->where('remaining_quantity', '>', 0)->Where(function($query) use ($search) {
                    $query->Where('title_en', 'like', '%' . $search . '%')->orWhere('title_ar', 'like', '%' . $search . '%');
                })->get()->makeHidden('multiOptions'); 
                if (count($products) > 0) {
                    for($i = 0; $i < count($products); $i ++) {
                        if ($products[$i]['multi_options'] != 0) {
                            $products[$i]['price_before_offer'] = $products[$i]->multiOptions[0]->price_before_offer;
                            $products[$i]['final_price'] = $products[$i]->multiOptions[0]->final_price;
                        }
                        unset($products[$i]['multi_options']);
                    }
                }

                
            }else{
                $products = Product::select('title_ar as title'  , 'id' , 'offer' , 'price_before_offer' , 'final_price' , 'offer_percentage' , 'category_id', 'multi_options')->where('deleted' , 0)->where('hidden' , 0)->where('remaining_quantity', '>', 0)->Where(function($query) use ($search) {
                    $query->Where('title_en', 'like', '%' . $search . '%')->orWhere('title_ar', 'like', '%' . $search . '%');
                })->get()->makeHidden('multiOptions');
                if (count($products) > 0) {
                    for($i = 0; $i < count($products); $i ++) {
                        if ($products[$i]['multi_options'] != 0) {
                            $products[$i]['price_before_offer'] = $products[$i]->multiOptions[0]->price_before_offer;
                            $products[$i]['final_price'] = $products[$i]->multiOptions[0]->final_price;
                        }
                        unset($products[$i]['multi_options']);
                    }
                }
            }
            $stores = Shop::select('id', 'cover', 'logo', 'name')->where('status', 1)->where('name', 'like', '%' . $search . '%')->has('products', '>', 0)->get()->makeHidden('custom');
            for($i =0; $i < count($products); $i++){
                $products[$i]['image'] = ProductImage::where('product_id' , $products[$i]['id'])->pluck('image')->first();
				if($request->lang == 'en'){
				$products[$i]['category_name'] = Category::where('id' , $products[$i]['category_id'])->pluck('title_en')->first();
				}else{
				$products[$i]['category_name'] = Category::where('id' , $products[$i]['category_id'])->pluck('title_ar')->first();
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
            }

            $data['products'] = $products;
            $data['stores'] = $stores;

            $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $data , $request->lang) ;
            return response()->json($response , 200);
        }
}
