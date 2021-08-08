<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Offer;
use App\Product;
use App\Category;
use App\OffersSection;
use App\ProductImage;
use App\Favorite;
use App\ControlOffer;
use App\Slider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Helpers\APIHelpers;


class OfferController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api' , ['except' => ['getoffers' , 'getoffersandroid', 'get_offers']]);
    }

    public function getoffers(Request $request){
        $offers_before = Offer::orderBy('sort' , 'ASC')->get();
        $offers = [];
        
        for($i = 0; $i < count($offers_before); $i++){
            if($offers_before[$i]['type'] == 1){
                $result = Product::find($offers_before[$i]['target_id']);
                if($result['deleted'] == 0 && $result['hidden'] == 0){
                    array_push($offers , $offers_before[$i]);
                }
            }else{
                $result = Category::find($offers_before[$i]['target_id']);
                if($result['deleted'] == 0 ){
                    array_push($offers , $offers_before[$i]);
                }
            }


        }

        $new_offers = [];
        for($i = 0; $i < count($offers); $i++){
            array_push($new_offers , $offers[$i]);
            if($offers[$i]->size == 3){
                if(count($offers) > 1 ){
                    if($offers[$i-1]->size != 3){
                        if(count($offers) > $i+1 ){
                            if($offers[$i+1]->size != 3){
                                $offer_element = new \stdClass();
                                $offer_element->id = 0;
                                $offer_element->image  = '';
                                $offer_element->size = 3;
                                $offer_element->type = 0;
                                $offer_element->target_id = 0;
                                $offer_element->sort = 0;
                                $offer_element->created_at = "";
                                $offer_element->updated_at = "";
                                array_push($new_offers , $offer_element);
                            }
                        }
                    }

                }
            }                        
        }
        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $new_offers , $request->lang);
        return response()->json($response , 200);
    }

    public function getoffersandroid(Request $request){

        $offers_before = Offer::orderBy('sort' , 'ASC')->get();
        $offers = [];
        
        for($i = 0; $i < count($offers_before); $i++){
            if($offers_before[$i]['type'] == 1){
                $result = Product::find($offers_before[$i]['target_id']);
                if($result['deleted'] == 0 && $result['hidden'] == 0){
                    array_push($offers , $offers_before[$i]);
                }
            }else{
                $result = Category::find($offers_before[$i]['target_id']);
                if($result['deleted'] == 0){
                    array_push($offers , $offers_before[$i]);
                }
            }



        }

        $new_offers = [];
        for($i = 0; $i < count($offers); $i++){
            if($offers[$i]->size == 1 || $offers[$i]->size == 2 ){
                $count = count($new_offers);
                $new_offers[$count] = [];
                array_push($new_offers[$count] , $offers[$i]);
                $offer_element = new \stdClass();
                $offer_element->id = 0;
                $offer_element->image  = '';
                $offer_element->size = $offers[$i]->size;
                $offer_element->type = 0;
                $offer_element->target_id = 0;
                $offer_element->sort = 0;
                $offer_element->created_at = "";
                $offer_element->updated_at = "";
                array_push($new_offers[$count] , $offer_element);
            }

            if($offers[$i]->size == 3){

                if(count($offers) > 1 ){

                    $count_offers = count($new_offers);

                    $last_count = count($new_offers[$count_offers - 1]);
                    
                    if($last_count == 2){
                        $new_offers[$count_offers] = [];
                        array_push($new_offers[$count_offers] , $offers[$i]);
                        if(count($offers) > $i+1 ){
                             if($offers[$i+1]->size != 3){
                                $offer_element = new \stdClass();
                                $offer_element->id = 0;
                                $offer_element->image  = '';
                                $offer_element->size = 3;
                                $offer_element->type = 0;
                                $offer_element->target_id = 0;
                                $offer_element->sort = 0;
                                $offer_element->created_at = "";
                                $offer_element->updated_at = "";
                                array_push($new_offers[$count_offers] , $offer_element);
                            }
                        }else{
                            $offer_element = new \stdClass();
                            $offer_element->id = 0;
                            $offer_element->image  = '';
                            $offer_element->size = 3;
                            $offer_element->type = 0;
                            $offer_element->target_id = 0;
                            $offer_element->sort = 0;
                            $offer_element->created_at = "";
                            $offer_element->updated_at = "";
                            array_push($new_offers[$count_offers] , $offer_element);
                        }
                    }else{
                        array_push($new_offers[$count_offers - 1] , $offers[$i]);
                    }

                }else{
                    $count = count($new_offers);
                    $new_offers[$count] = [];
                    array_push($new_offers[$count] , $offers[$i]);
                    $offer_element = new \stdClass();
                    $offer_element->id = 0;
                    $offer_element->image  = '';
                    $offer_element->size = $offers[$i]->size;
                    $offer_element->type = 0;
                    $offer_element->target_id = 0;
                    $offer_element->sort = 0;
                    $offer_element->created_at = "";
                    $offer_element->updated_at = "";
                    array_push($new_offers[$count] , $offer_element);
                }
                
            }

        }

        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $new_offers , $request->lang);
        return response()->json($response , 200);

    }

    public function get_offers(Request $request) {
        $offers_sections = OffersSection::orderBy('sort', 'asc')->get();
        $offers = [];
        $data = [];
        $slider = Slider::where('type', 3)->first();
        $data['slider'] = $slider->ads;
        for($i = 0; $i < count($offers_sections); $i++){
            $element = [];
            $element['icon'] = $offers_sections[$i]['icon'];
            if($request->lang == 'en'){
                $element['title'] = $offers_sections[$i]['title_en'];
            }else{
                $element['title'] = $offers_sections[$i]['title_ar'];
            }
            $ids = ControlOffer::where('offers_section_id' , $offers_sections[$i]['id'])->pluck('offer_id');
            if($request->lang == 'en'){
                $element['ads'] = Product::with('multiOptions')->select('id', 'title_en as title' , 'offer' , 'offer_percentage' , 'multi_options', 'final_price', 'price_before_offer', 'type')->where('deleted' , 0)->where('hidden' , 0)->where('remaining_quantity', '>', 0)->whereIn('id' , $ids)->get()->makeHidden(['multiOptions']);
            }else{
                $element['ads'] = Product::with('multiOptions')->select('id', 'title_ar as title' , 'offer' , 'offer_percentage' , 'multi_options', 'final_price', 'price_before_offer', 'type')->where('deleted' , 0)->where('hidden' , 0)->where('remaining_quantity', '>', 0)->whereIn('id' , $ids)->get()->makeHidden(['multiOptions']);
            }
            
            for($j = 0; $j < count($element['ads']) ; $j++){
                if ($element['ads'][$j]['multi_options'] == 1) {
                    $element['ads'][$j]['final_price'] = $element['ads'][$j]['multiOptions'][0]['final_price'];
                    $element['ads'][$j]['price_before_offer'] = $element['ads'][$j]['multiOptions'][0]['price_before_offer'];
                }
                unset($element['ads'][$j]['multi_options']);
                
                if(auth()->user()){
                    $user_id = auth()->user()->id;

                    $prevfavorite = Favorite::where('product_id' , $element['ads'][$j]['id'])->where('user_id' , $user_id)->first();
                    if($prevfavorite){
                        $element['ads'][$j]['favorite'] = true;
                    }else{
                        $element['ads'][$j]['favorite'] = false;
                    }

                }else{
                    $element['ads'][$j]['favorite'] = false;
                }

                
                

                $element['ads'][$j]['image'] = ProductImage::where('product_id' , $element['ads'][$j]['id'])->pluck('image')->first();
            }

            array_push($offers , $element);
        }
        $data['offers'] = $offers;

        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $data , $request->lang);
        return response()->json($response , 200);
    }

}