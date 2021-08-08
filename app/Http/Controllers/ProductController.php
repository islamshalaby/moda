<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\APIHelpers;
use App\Product;
use App\Category;
use App\Brand;
use App\SubCategory;
use App\ProductImage;
use App\Option;
use App\ProductOption;
use App\Favorite;
use App\ProductMultiOption;
use App\PropertiesCategory;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api' , ['except' => ['getdetails' , 'getproducts' , 'getbrandproducts', 'get_sub_category_products']]);
    }


    public function getdetails(Request $request){
        $id = $request->id;
        if($request->lang == 'en'){
            $data['product'] = Product::select('id' , 'title_en as title' , 'description_en as description' , 'offer' , 'price_before_offer' , 'final_price' , 'offer_percentage' , 'category_id', 'multi_options', 'video', 'store_id', 'type', 'video_image')->find($id)->makeHidden(['category', 'multiOptions', 'values', 'mOptionsValuesEn', 'mOptions', 'mOptionsValuesAr', 'storeWithLogoNameOnly']);
            // dd($data['product']->store);
            if (isset($data['product']['title'])) {
                if ($data['product']['multi_options'] == 1) {
                    $multi_options = [];
                    $multi_options['option_name'] = $data['product']->mOptions[0]['title_en'];
                    $multi_options['option_values'] = $data['product']->mOptionsValuesEn;
                    for($k = 0; $k < count($multi_options["option_values"]); $k ++) {
                        $product_m_option = ProductMultiOption::select('final_price', 'price_before_offer', 'total_quatity', 'remaining_quantity', 'barcode', 'stored_number', 'multi_option_value_id as option_value_id', 'product_multi_options.id as option_id')->where('product_id', $data['product']['id'])->where('multi_option_value_id', $multi_options["option_values"][$k]['option_value_id'])->first();
                        $multi_options["option_values"][$k]['option_data'] = $product_m_option;
                    }
                    $data['product']['multiple_option'] = $multi_options;
                }
                
                $data['product']['category_name'] = Category::select('title_en')->find($data['product']['category_id'])->title_en;
                
                $product_options = $data['product']->values;
                $product_property = [];
                
                if (count($product_options) > 0) {
                    $property_categories = PropertiesCategory::where('deleted', 0)->get();
                    
                    for ($p = 0; $p < count($property_categories); $p ++) {
                        $product_property[$p]['category_name'] = $property_categories[$p]['title_en'];
                        $prop = [];
                        $product_property[$p]['options']=[];
                        for($i = 0 ; $i < count($product_options) ; $i++){
                            if ($property_categories[$p]['id'] == $product_options[$i]['option']['property_category_id']) {
                                $prop[$i]['key'] = $product_options[$i]['option']['title_en'];
                                $prop[$i]['value'] = $product_options[$i]['value_en'];
                                array_push($product_property[$p]['options'], $prop[$i]);
                            }
                            
                        }
                        
                    }
                    
                }
            }else {
                $response = APIHelpers::createApiResponse(true , 406 , 'Product does not exist' , 'المنتج غير موجود' , null , $request->lang);
                return response()->json($response , 406);
            }

        }else{
            $data['product'] = Product::select('id' , 'title_ar as title' , 'description_ar as description' , 'offer' , 'price_before_offer' , 'final_price' , 'offer_percentage' , 'category_id', 'multi_options', 'video', 'store_id', 'type', 'video_image')->find($id)->makeHidden(['category', 'multiOptions', 'values', 'mOptionsValuesEn', 'mOptions', 'mOptionsValuesAr', 'storeWithLogoNameOnly']);
            if (isset($data['product']['title'])) {
                if ($data['product']['multi_options'] == 1) {
                    $multi_options = [];
                    $multi_options['option_name'] = $data['product']->mOptions[0]['title_ar'];
                    $multi_options['option_values'] = $data['product']->mOptionsValuesAr;
                    for($k = 0; $k < count($multi_options["option_values"]); $k ++) {
                        $product_m_option = ProductMultiOption::select('final_price', 'price_before_offer', 'total_quatity', 'remaining_quantity', 'barcode', 'stored_number', 'multi_option_value_id as option_value_id', 'product_multi_options.id as option_id')->where('product_id', $data['product']['id'])->where('multi_option_value_id', $multi_options["option_values"][$k]['option_value_id'])->first();
                        $multi_options["option_values"][$k]['option_data'] = $product_m_option;
                    }
                    $data['product']['multiple_option'] = $multi_options;
                }
                $data['product']['category_name'] = Category::select('title_ar')->find($data['product']['category_id'])->title_ar;
    
                $product_options = $data['product']->values;
                $product_property = [];
                
                if (count($product_options) > 0) {
                    $property_categories = PropertiesCategory::where('deleted', 0)->get();
                    
                    for ($p = 0; $p < count($property_categories); $p ++) {
                        $product_property[$p]['category_name'] = $property_categories[$p]['title_ar'];
                        $prop = [];
                        $product_property[$p]['options']=[];
                        for($i = 0 ; $i < count($product_options) ; $i++){
                            if ($property_categories[$p]['id'] == $product_options[$i]['option']['property_category_id']) {
                                $prop[$i]['key'] = $product_options[$i]['option']['title_ar'];
                                $prop[$i]['value'] = $product_options[$i]['value_ar'];
                                array_push($product_property[$p]['options'], $prop[$i]);
                            }
                            
                        }
                        
                    }
                    
                }
            }else {
                $response = APIHelpers::createApiResponse(true , 410 , 'Product is not exist' , 'المنتج غير موجود' , null , $request->lang);
                return response()->json($response , 410);
            }
            
        }
        
        $all = [];
        $images = ProductImage::where('product_id' , $data['product']['id'])->pluck('image');
		$data['product']['images'] = $images;
        if (count($images) > 0) {
            for ($i =0; $i < count($images); $i ++) {
                $nImages = (object)[
                    "link" => "",
                    "image" => $images[$i]
                ];
                array_push($all, $nImages);
            }
        }
        
        if (!empty($data['product']['video'])) {
            $video_image = (object)[
                "link" => $data['product']['video'],
                "image" => $data['product']['video_image']
            ];

            array_push($all, $video_image);
        }
        $data['product']['product_images'] = $all;
        $data['store'] = $data['product']->storeWithLogoNameOnly->makeHidden('custom');
        if(auth()->user()){
            $user_id = auth()->user()->id;

            $prevfavorite = Favorite::where('product_id' , $data['product']['id'])->where('user_id' , $user_id)->first();
            if($prevfavorite){
                $data['product']['favorite'] = true;
            }else{
                $data['product']['favorite'] = false;
            }

        }else{
            $data['product']['favorite'] = false;
        }

        $data['product']['properties'] = $product_property;
        
        if($request->lang == 'en'){
            $data['related'] = Product::select('id', 'title_en as title' , 'final_price' , 'price_before_offer' , 'offer' , 'offer_percentage' , 'category_id' )->where('deleted' , 0)->where('category_id' , $data['product']['category_id'])->where('id' , '!=' , $data['product']['id'])->get();
        }else{
            $data['related'] = Product::select('id', 'title_ar as title' , 'final_price' , 'price_before_offer' , 'offer' , 'offer_percentage' , 'category_id')->where('deleted' , 0)->where('category_id' , $data['product']['category_id'])->where('id' , '!=' , $data['product']['id'])->get();
        }
        
        for($j = 0; $j < count($data['related']) ; $j++){
            if ($data['related'][$j]['multi_options'] == 1) {
                $data['related'][$j]['final_price'] = $data['related'][$j]['multiOptions'][0]['final_price'];
                $data['related'][$j]['price_before_offer'] = $data['related'][$j]['multiOptions'][0]['price_before_offer'];
            }

            if(auth()->user()){
                $user_id = auth()->user()->id;
    
                $prevfavorite = Favorite::where('product_id' , $data['related'][$j]['id'])->where('user_id' , $user_id)->first();
                if($prevfavorite){
                    $data['related'][$j]['favorite'] = true;
                }else{
                    $data['related'][$j]['favorite'] = false;
                }
    
            }else{
                $data['related'][$j]['favorite'] = false;
            }


            if($request->lang == 'en'){
                $data['related'][$j]['category_name'] = Category::where('id' , $data['related'][$j]['category_id'])->pluck('title_en as title')->first();
            }else{
                $data['related'][$j]['category_name'] = Category::where('id' , $data['related'][$j]['category_id'])->pluck('title_ar as title')->first();
            }
            

            $data['related'][$j]['image'] = ProductImage::where('product_id' , $data['related'][$j]['id'])->pluck('image')->first();;
        }


        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $data , $request->lang);
        return response()->json($response , 200);
    }

    public function getproducts(Request $request){
        $validator = Validator::make($request->all(), [
            'category_id' => 'required',
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 , 'Missing Required Fields' , 'بعض الحقول مفقودة' , null , $request->lang);
            return response()->json($response , 406);
        }

        $category_id = $request->category_id;
        $sub_category_id = $request->sub_category_id;

        // if($request->lang == 'en'){
        //     $categories = Category::where('deleted' , 0)->select('id' , 'title_en as title' , 'image')->get();   
        // }else{
        //     $categories = Category::where('deleted' , 0)->select('id' , 'title_ar as title' , 'image')->get();   
        // }

        // for($i = 0; $i < count($categories); $i++){
            // if($categories[$i]['id'] == $request->category_id){
            //     $categories[$i]['selected'] = 1;
                if($request->lang == 'en'){
                    $subcategories = SubCategory::where('deleted' , 0)->where('category_id' , $request->category_id)->select('id' , 'title_en as title')->get()->toArray();
                    $all_element = array();
                    $all_element['id'] = 0;
                    $all_element['title'] = 'All';
                    array_unshift($subcategories , $all_element);
                }else{
                    $subcategories = SubCategory::where('deleted' , 0)->where('category_id' , $request->category_id)->select('id' , 'title_en as title')->get()->toArray();
                    $all_element = array();
                    $all_element['id'] = 0;
                    $all_element['title']  = 'الكل';
                    array_unshift($subcategories , $all_element);
                }

                for($j =0; $j < count($subcategories); $j++){
                    if($subcategories[$j]['id'] == $request->sub_category_id){
                        $subcategories[$j]['selected'] = 1;
                    }else{
                        $subcategories[$j]['selected'] = 0;
                    }

                }

                // $categories[$i]['subcategories'] = $subcategories;
                
            // }else{
            //     $categories[$i]['selected'] = 0;
            // }
        // }

        $data['sub_categories'] = $subcategories;

        if($request->sub_category_id == 0){
            if($request->lang == 'en'){
                $products = Product::select('id', 'title_en as title' , 'final_price' , 'price_before_offer' , 'offer' , 'offer_percentage' , 'category_id' )->where('deleted' , 0)->where('hidden' , 0)->where('remaining_quantity', '>', 0)->where('category_id' , $request->category_id)->simplePaginate(16);
            }else{
                $products = Product::select('id', 'title_ar as title' , 'final_price' , 'price_before_offer' , 'offer' , 'offer_percentage' , 'category_id' )->where('deleted' , 0)->where('hidden' , 0)->where('remaining_quantity', '>', 0)->where('category_id' , $request->category_id)->simplePaginate(16);
            }
        }else{
            if($request->lang == 'en'){
                $products = Product::select('id', 'title_en as title' , 'final_price' , 'price_before_offer' , 'offer' , 'offer_percentage' , 'category_id' )->where('deleted' , 0)->where('hidden' , 0)->where('remaining_quantity', '>', 0)->where('category_id' , $request->category_id)->where('sub_category_id' , $request->sub_category_id)->simplePaginate(16);
            }else{
                $products = Product::select('id', 'title_ar as title' , 'final_price' , 'price_before_offer' , 'offer' , 'offer_percentage' , 'category_id' )->where('deleted' , 0)->where('hidden' , 0)->where('remaining_quantity', '>', 0)->where('category_id' , $request->category_id)->where('sub_category_id' , $request->sub_category_id)->simplePaginate(16);
            }
        }

        for($i = 0; $i < count($products); $i++){
            
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

            if($request->lang == 'en'){
                $products[$i]['category_name'] = Category::where('id' , $products[$i]['category_id'])->pluck('title_en as title')->first();
            }else{
                $products[$i]['category_name'] = Category::where('id' , $products[$i]['category_id'])->pluck('title_ar as title')->first();
            }
            
            $products[$i]['image'] = ProductImage::where('product_id' , $products[$i]['id'])->pluck('image')->first();
        }
        
        $data['products'] = $products;
        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $data , $request->lang);
        return response()->json($response , 200);
    }

    public function getbrandproducts(Request $request){
        if($request->lang == 'en'){
            $products = Product::select('id', 'title_en as title' , 'final_price' , 'price_before_offer' , 'offer' , 'offer_percentage' , 'category_id' )->where('deleted' , 0)->where('hidden' , 0)->where('remaining_quantity', '>', 0)->where('brand_id' , $request->brand_id)->simplePaginate(16);
        }else{
            $products = Product::select('id', 'title_ar as title' , 'final_price' , 'price_before_offer' , 'offer' , 'offer_percentage' , 'category_id' )->where('deleted' , 0)->where('hidden' , 0)->where('remaining_quantity', '>', 0)->where('brand_id' , $request->brand_id)->simplePaginate(16);
        }


        for($i = 0; $i < count($products); $i++){
            
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

            if($request->lang == 'en'){
                $products[$i]['category_name'] = Category::where('id' , $products[$i]['category_id'])->pluck('title_en as title')->first();
            }else{
                $products[$i]['category_name'] = Category::where('id' , $products[$i]['category_id'])->pluck('title_ar as title')->first();
            }
            
            $products[$i]['image'] = ProductImage::where('product_id' , $products[$i]['id'])->pluck('image')->first();
        }
        
        $data['products'] = $products;
        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $data , $request->lang);
        return response()->json($response , 200);

    }

    public function get_sub_category_products(Request $request){
        $validator = Validator::make($request->all(), [
            'sub_category_id' => 'required',
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 , 'Missing Required Fields' , 'بعض الحقول مفقودة' , null , $request->lang);
            return response()->json($response , 406);
        }

        $sub_category_id = $request->sub_category_id;

        
        
        if($request->lang == 'en'){
            $data['sub_categories_name'] = SubCategory::where('deleted' , 0)->where('id' , $sub_category_id)->pluck('title_en as title')->first();
            $products = Product::select('id', 'title_en as title' , 'offer' , 'offer_percentage', 'multi_options' )->where('deleted' , 0)->where('hidden' , 0)->where('sub_category_id' , $request->sub_category_id)->where('sub_category_id' , $request->sub_category_id)->simplePaginate(16);
            $products->makeHidden(['multiOptions']);
        }else{
            $data['sub_categories_name'] = SubCategory::where('deleted' , 0)->where('id' , $sub_category_id)->pluck('title_ar as title')->first();
            $products = Product::select('id', 'title_ar as title' , 'offer' , 'offer_percentage', 'multi_options' )->where('deleted' , 0)->where('hidden' , 0)->where('sub_category_id' , $request->sub_category_id)->where('sub_category_id' , $request->sub_category_id)->simplePaginate(16);
            $products->makeHidden(['multiOptions']);
        }
        

        for($i = 0; $i < count($products); $i++){
            if ($products[$i]['multi_options'] == 1) {
                if (count($products[$i]['multiOptions']) > 0) {
                    $products[$i]['final_price'] = $products[$i]['multiOptions'][0]['final_price'];
                    $products[$i]['price_before_offer'] = $products[$i]['multiOptions'][0]['price_before_offer'];
                    unset($products[$i]['multi_options']);
                }
            }else {
                if($request->lang == 'en'){
                    $products[$i] = Product::select('id', 'title_en as title' , 'offer' , 'final_price', 'price_before_offer', 'offer_percentage' )->where('id', $products[$i]['id'])->where('remaining_quantity', '>', 0)->first();
                    
                }else {
                    $products[$i] = Product::select('id', 'title_ar as title' , 'offer' , 'final_price', 'price_before_offer', 'offer_percentage' )->where('id', $products[$i]['id'])->where('remaining_quantity', '>', 0)->first();
                }
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
            
            $products[$i]['image'] = ProductImage::where('product_id' , $products[$i]['id'])->pluck('image')->first();
        }
        
        // dd($products);
        $data['products'] = $products;
        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $data , $request->lang);
        return response()->json($response , 200);
    }

    

}