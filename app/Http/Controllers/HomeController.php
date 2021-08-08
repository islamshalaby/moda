<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\APIHelpers;
use App\HomeElement;
use App\HomeSection;
use App\Brand;
use App\Category;
use App\Favorite;
use App\Ad;
use App\Product;
use App\ProductImage;
use App\ProductMultiOption;
use App\Slider;
use App\Shop;


class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api' , ['except' => ['getdata']]);
    }

    public function getdata(Request $request){
        $slider = Slider::where('type', 1)->first();
        $data['slider'] = $slider->ads;
        for ($p = 0; $p < count($data['slider']); $p ++) {
            $data['slider'][$p]['content'] = $data['slider'][$p]['content'];
        }
        $stores = Shop::select('id', 'cover', 'logo', 'name')->where('status', 1)->has('products', '>', 0)->get()->makeHidden('custom');
        $store_ads = [];
        if (count($stores) > 0) {
            for ($i = 0; $i < count($stores); $i ++) {
                // if ($stores[$i]->products()->count() > 1) {
                    $stores[$i]['image'] = $stores[$i]['cover'];
                    $stores[$i]['type'] = 1;
                    $stores[$i]['ad_type'] = 1;
                    $stores[$i]['content'] = (string)$stores[$i]['id'];
                    $stores[$i]['name'] = $stores[$i]['name'];
                    $stores[$i]['logo'] = $stores[$i]['logo'];
                    $stores[$i]['content_type'] = 3;
                    $stores[$i]['store_id'] = 0;
                    unset($stores[$i]['cover']);
                    array_push($store_ads, $stores[$i]);
                // }
            }
        }
        
        $ads = Ad::select('id', 'image', 'type', 'content', 'content_type', 'store_id')->where('place', 2)->get();
        $data['content'] = [];

        if (count($store_ads) > 0) {
            $r = 1;
            for ($m = 0; $m < count($store_ads); $m ++) {
                
                array_push($data['content'], $store_ads[$m]);
                 if ($r % 4 == 0) {
                    $ad = Ad::select('id', 'image', 'type', 'content', 'content_type', 'store_id')->where('place', 2)->inRandomOrder()->first();
                    $ad['ad_type'] = 2;
                    
                    array_push($data['content'], $ad);
                
                }
                $r ++;
            }
        }
        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $data , $request->lang);
        return response()->json($response , 200);
    }

    public function getoffers(Request $request){
		$id = $request->id;
		$ids = HomeElement::where('home_id' , $id)->pluck('element_id');
		        if($request->lang == 'en'){
                    $element['data'] = Product::select('id', 'title_en as title' , 'final_price' , 'price_before_offer' , 'offer' , 'offer_percentage' , 'category_id' )->where('deleted' , 0)->where('hidden' , 0)->where('remaining_quantity', '>', 0)->whereIn('id' , $ids)->get();
                }else{
                    $element['data'] = Product::select('id', 'title_ar as title' , 'final_price' , 'price_before_offer' , 'offer' , 'offer_percentage' , 'category_id')->where('deleted' , 0)->where('hidden' , 0)->where('remaining_quantity', '>', 0)->whereIn('id' , $ids)->get();
                }
                
                for($j = 0; $j < count($element['data']) ; $j++){
                    // $element['data'][$j]['favorite'] = false;

                    if(auth()->user()){
                        $user_id = auth()->user()->id;

                        $prevfavorite = Favorite::where('product_id' , $element['data'][$j]['id'])->where('user_id' , $user_id)->first();
                        if($prevfavorite){
                            $element['data'][$j]['favorite'] = true;
                        }else{
                            $element['data'][$j]['favorite'] = false;
                        }

                    }else{
                        $element['data'][$j]['favorite'] = false;
                    }

                    if($request->lang == 'en'){
                        $element['data'][$j]['category_name'] = Category::where('id' , $element['data'][$j]['category_id'])->pluck('title_en as title')->first();
                    }else{
                        $element['data'][$j]['category_name'] = Category::where('id' , $element['data'][$j]['category_id'])->pluck('title_ar as title')->first();
                    }
                    

                    $element['data'][$j]['image'] = ProductImage::where('product_id' , $element['data'][$j]['id'])->pluck('image')->first();
                }
		
				        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $element['data'] , $request->lang);
        return response()->json($response , 200);
		
		
	}

    

}
