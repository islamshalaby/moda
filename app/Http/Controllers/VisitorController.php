<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Helpers\APIHelpers;
use App\Visitor;
use App\Cart;
use App\Favorite;
use App\Product;
use App\ProductImage;
use App\ProductMultiOption;
use App\SizeDetail;
use App\Shop;
use App\UserAddress;
use App\DeliveryArea;
use App\Setting;
use App\Area;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class VisitorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api' , ['except' => ['create' , 'add' , 'delete' , 'get' , 'changecount' , 'getcartcount']]);
    }

    // create visitor 
    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'unique_id' => 'required',
			'fcm_token' => "required",
            'type' => 'required' // 1 -> iphone ---- 2 -> android
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 , 'Missing Required Fields' , 'بعض الحقول مفقودة' , null , $request->lang);
            return response()->json($response , 406);
        }

        $last_visitor = Visitor::where('unique_id' , $request->unique_id)->first();
        if($last_visitor){
			$last_visitor->fcm_token = $request->fcm_token;
            $last_visitor->save();
            $visitor = $last_visitor;
        }else{
            $visitor = new Visitor();
            $visitor->unique_id = $request->unique_id;
			$visitor->fcm_token = $request->fcm_token;
            $visitor->type = $request->type;
            $visitor->save();
        }


        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $visitor , $request->lang);
        return response()->json($response , 200);
    }

    // add to cart
    public function add(Request $request){
        $validator = Validator::make($request->all(), [
            'unique_id' => 'required',
            'product_id' => 'required|exists:products,id',
            'product_number' => 'required|numeric|min:0|not_in:0'
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 , 'Missing Required Fields or product does not exist' , 'بعض الحقول مفقودة او المنتج غير موجود' , null , $request->lang);
            return response()->json($response , 406);
        }

        $product = Product::find($request->product_id);

        if ( $product->type == 2 && ( !isset($request->tall) && empty($request->tall) && 
            !isset($request->shoulder_width) && empty($request->shoulder_width) &&
            !isset($request->chest) && empty($request->chest) &&
            !isset($request->waist) && empty($request->waist) &&
            !isset($request->buttocks) && empty($request->buttocks) &&
            !isset($request->sleeve) && empty($request->sleeve) ) ) {
                $response = APIHelpers::createApiResponse(true , 406 , 'Missing Required Fields' , 'بعض الحقول مفقودة' , null , $request->lang);
                return response()->json($response , 406);
        }
        

        $visitor = Visitor::where('unique_id' , $request->unique_id)->first();
        if($visitor){
            $option_id = 0;
            if (isset($request->option_id) && $request->option_id != 0) {
                $option_id = $request->option_id;
                $cart = Cart::where('visitor_id' , $visitor->id)->where('product_id' , $request->product_id)->where('option_id', $request->option_id)->first();
                $product_m_option = ProductMultiOption::select('remaining_quantity')->where('id', $request->option_id)->first();
                if ($product_m_option->remaining_quantity < 1) {
                    $response = APIHelpers::createApiResponse(true , 406 , 'The remaining amount of the product is not enough' , 'الكميه المتبقيه من المنتج غير كافيه'  , null , $request->lang);
                    return response()->json($response , 406);
                }
            }else {
                $cart = Cart::where('visitor_id' , $visitor->id)->where('product_id' , $request->product_id)->first();
                if($product->remaining_quantity < 1){
                    $response = APIHelpers::createApiResponse(true , 406 , 'The remaining amount of the product is not enough' , 'الكميه المتبقيه من المنتج غير كافيه'  , null , $request->lang);
                    return response()->json($response , 406);
                }
            }

            // dd($cart);
            
            if($cart){
                $count = $cart->count;
                $cart->count = $count + $request->product_number;
                $cart->save();
            }else{
                $cart = new Cart();
                $cart->count = $request->product_number;
                $cart->product_id = $request->product_id;
                $cart->option_id = $option_id;
                $cart->visitor_id = $visitor->id;
                $cart->store_id = $product->store_id;
                $cart->save();
            }

            if ( $product->type == 2) {
                $details = "";
                if (isset($request->details) && !empty($request->details)) {
                    $details = $request->details;
                }
                SizeDetail::create([
                    'tall' => $request->tall,
                    'shoulder_width' => $request->shoulder_width,
                    'chest' => $request->chest,
                    'waist' => $request->waist,
                    'buttocks' => $request->buttocks,
                    'sleeve' => $request->sleeve,
                    'details' => $details,
                    'product_id' => $request->product_id,
                    'cart_id' => $cart->id
                ]);
            }else {
                $details = "";
                if (isset($request->details) && !empty($request->details)) {
                    $details = $request->details;
                    SizeDetail::create([
                        'details' => $details,
                        'type' => 1,
                        'cart_id' => $cart->id,
						'product_id' => $request->product_id
                    ]);
                }
            }
            

            $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $cart , $request->lang);
            return response()->json($response , 200);

        }else{
            $response = APIHelpers::createApiResponse(true , 406 , 'This Unique Id Not Registered' , 'This Unique Id Not Registered' , null , $request->lang);
            return response()->json($response , 406);
        }

    }

    // remove from cart
    public function delete(Request $request){
        $validator = Validator::make($request->all(), [
            'unique_id' => 'required',
            'product_id' => 'required'
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 , 'Missing Required Fields' , 'بعض الحقول مفقودة' , null , $request->lang);
            return response()->json($response , 406);
        }

        $visitor = Visitor::where('unique_id' , $request->unique_id)->first();
        if($visitor){
            if (isset($request->option_id)) {
                $cart = Cart::where('product_id' , $request->product_id)->where('visitor_id' , $visitor->id)->where('option_id', $request->option_id)->first();
            }else {
                $cart = Cart::where('product_id' , $request->product_id)->where('visitor_id' , $visitor->id)->first();
            }
            $cart->delete();

            $response = APIHelpers::createApiResponse(false , 200 , '' , '' , null , $request->lang);
            return response()->json($response , 200);

        }else{
            $response = APIHelpers::createApiResponse(true , 406 , 'This Unique Id Not Registered' , 'This Unique Id Not Registered' , null , $request->lang);
            return response()->json($response , 406);
        }
    }

    // get cart
    public function get(Request $request){
        $validator = Validator::make($request->all(), [
            'unique_id' => 'required'
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 , 'Missing Required Fields' , 'بعض الحقول مفقودة' , null , $request->lang);
            return response()->json($response , 406);
        }

        $visitor = Visitor::where('unique_id' , $request->unique_id)->first();
        if($visitor){
            $visitor_id =  $visitor['id'];
            $cart = Cart::where('visitor_id' , $visitor_id)->select('product_id as id' , 'count', 'option_id')->get();
            $data['subtotal_price'] = 0;
            for($i = 0; $i < count($cart); $i++){
                if($request->lang == 'en'){
                    $product = Product::with('multiOptions', 'store')->select('title_en as title' , 'final_price' , 'price_before_offer', 'id', 'type', 'store_id')->where('id', $cart[$i]['id'])->first();
                }else{
                    $product = Product::with('multiOptions', 'store')->select('title_ar as title' , 'final_price' , 'price_before_offer', 'id', 'type', 'store_id')->where('id', $cart[$i]['id'])->first();
                }
                
                if(auth()->user()){
                    $user_id = auth()->user()->id;
                    $prevfavorite = Favorite::where('product_id' , $cart[$i]['id'])->where('user_id' , $user_id)->first();
                    if($prevfavorite){
                        $cart[$i]['favorite'] = true;
                    }else{
                        $cart[$i]['favorite'] = false;
                    }
    
                }else{
                    $cart[$i]['favorite'] = false;
                }

                if ($cart[$i]['option_id'] != 0) {
                    for ($k = 0; $k < count($product->multiOptions); $k ++) {
                        if ($product->multiOptions[$k]->id == $cart[$i]['option_id']) {
                            if ($request->lang == 'en'){
                                $cart[$i]['size_name'] = $product->multiOptions[$k]->multiOption->title_en;
                                $cart[$i]['size_value'] = $product->multiOptions[$k]->multiOptionValue->value_en;
                            }else {
                                $cart[$i]['size_name'] = $product->multiOptions[$k]->multiOption->title_ar;
                                $cart[$i]['size_value'] = $product->multiOptions[$k]->multiOptionValue->value_ar;
                            }
                            $cart[$i]['final_price'] = $product->multiOptions[$k]->final_price;
                            $cart[$i]['price_before_offer'] = $product->multiOptions[$k]->price_before_offer;
                            $data['subtotal_price'] = $data['subtotal_price'] + ($product->multiOptions[$k]->final_price * $cart[$i]['count']);
                        }
                    }
                }else {
                    $cart[$i]['size_name'] = "";
                    $cart[$i]['size_value'] = "";
                    $cart[$i]['final_price'] = $product['final_price'];
                    $cart[$i]['price_before_offer'] = $product['price_before_offer'];
                    $data['subtotal_price'] = $data['subtotal_price'] + ($product['final_price'] * $cart[$i]['count']);
                }
                
                $cart[$i]['title'] = $product['title'];
                $cart[$i]['type'] = $product['type'];
                $cart[$i]['store_name'] = $product->store->name;
                $cart[$i]['store_id'] = $product->store->id;
                $cart[$i]['image'] = ProductImage::select('image')->where('product_id' , $cart[$i]['id'])->first()['image'];
            }
            
            $data['cart'] = $cart;
            $data['count'] = count($cart);
            
            $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $data , $request->lang);
            return response()->json($response , 200);

        }else{
            $response = APIHelpers::createApiResponse(true , 406 , 'This Unique Id Not Registered' , 'This Unique Id Not Registered' , null , $request->lang);
            return response()->json($response , 406);
        }
        

    }

    // get cart count 
    public function getcartcount(Request $request){
        $validator = Validator::make($request->all(), [
            'unique_id' => 'required',
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 , 'Missing Required Fields' , 'بعض الحقول مفقودة' , null , $request->lang);
            return response()->json($response , 406);
        }

        $visitor = Visitor::where('unique_id' , $request->unique_id)->first();
        if($visitor){
            $visitor_id =  $visitor['id'];
            $cart = Cart::where('visitor_id' , $visitor_id)->select('product_id as id' , 'count')->get();
            $count['count'] = count($cart);

            $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $count , $request->lang);
            return response()->json($response , 200);

        }else{
            $response = APIHelpers::createApiResponse(true , 406 , 'This Unique Id Not Registered' , 'This Unique Id Not Registered' , null , $request->lang);
            return response()->json($response , 406);
        }
    }

    // change count
    public function changecount(Request $request){
 
        $validator = Validator::make($request->all(), [
            'unique_id' => 'required',
            'product_id' => 'required|exists:products,id',
            'new_count' => 'required'
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 , 'Missing Required Fields or product does not exist' , 'بعض الحقول مفقودة او المنتج غير موجود'  , null , $request->lang);
            return response()->json($response , 406);
        }

        $visitor = Visitor::where('unique_id' , $request->unique_id)->first();
        if (isset($request->option_id)) {
            $product_m_option = ProductMultiOption::select('remaining_quantity')->where('id', $request->option_id)->first();
            if($product_m_option->remaining_quantity < $request->new_count){
                $response = APIHelpers::createApiResponse(true , 406 , 'The remaining amount of the product is not enough' , 'الكميه المتبقيه من المنتج غير كافيه'  , null , $request->lang);
                return response()->json($response , 406);
            }
        }else {
            $product = Product::find($request->product_id);
            if($product->remaining_quantity < $request->new_count){
                $response = APIHelpers::createApiResponse(true , 406 , 'The remaining amount of the product is not enough' , 'الكميه المتبقيه من المنتج غير كافيه'  , null , $request->lang);
                return response()->json($response , 406);
            }
        }
        

        if($visitor){
            if (isset($request->option_id)) {
                $cart = Cart::where('product_id' , $request->product_id)->where('visitor_id' , $visitor->id)->where('option_id', $request->option_id)->first();
            }else {
                $cart = Cart::where('product_id' , $request->product_id)->where('visitor_id' , $visitor->id)->first();
            }
            
            if (isset($cart->count)) {
                $cart->count = $request->new_count;
                $cart->save();
                $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $cart , $request->lang);
                return response()->json($response , 200);
            }else {
                $response = APIHelpers::createApiResponse(true , 406 , 'This product is not exist in cart' , 'هذا المنتج غير موجود بالعربة' , null , $request->lang);
                return response()->json($response , 406);
            }
        }else{
            $response = APIHelpers::createApiResponse(true , 406 , 'This Unique Id Not Registered' , 'This Unique Id Not Registered' , null , $request->lang);
            return response()->json($response , 406);
        }
        
    }

    // get cart before order
    public function get_cart_before_order(Request $request) {
        $validator = Validator::make($request->all(), [
            'unique_id' => 'required'
        ]);
        

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 , 'Missing Required Fields' , 'بعض الحقول مفقودة' , null , $request->lang);
            return response()->json($response , 406);
        }

        
        //dd(auth()->user()->id);
        $visitor = Visitor::where('unique_id' , $request->unique_id)->first();
        $address = UserAddress::where('id', auth()->user()->main_address_id)->first();
		$area = Area::where('id', $address['area_id'])->select('title_' . $request->lang . ' as area')->first()['area'];
        
        if($visitor){
            $visitor_id =  $visitor['id'];
            $cart = Cart::where('visitor_id' , $visitor_id)->select('product_id' , 'count', 'option_id')->get();
            $stores = [];
            for($i = 0; $i < count($cart); $i++){
                array_push($stores, $cart[$i]->product->store_id);
            }
            $get_stores = Shop::select('name', 'id')->whereIn('id', $stores)->get();
            $sub_total_price = 0;
            $delivery_cost = 0;
            for ($n = 0; $n < count($get_stores); $n ++) {
                $data['cart'][$n]['store_name'] = $get_stores[$n]['name'];
                $data['cart'][$n]['store_id'] = $get_stores[$n]['id'];
                $delivery = DeliveryArea::select('delivery_cost')->where('area_id', $address['area_id'])->where('store_id', $get_stores[$n]['id'])->first();
                
                if (!isset($delivery['delivery_cost'])) {
                    $delivery = Setting::find(1);
                }
                // $data['cart'][$n]['delivery_cost'] = $delivery['delivery_cost'];
                $data['cart'][$n]['products'] = [];
                $store_products = [];
                $max_period = 0;
                $delivery_cost = $delivery_cost + $delivery['delivery_cost'];
                $storeTotalPrice = 0;
                for($k = 0; $k < count($cart); $k++){
                    if ($request->lang == 'en') {
                        $product = Product::select('id', 'order_period', 'title_en', 'store_id', 'type', 'final_price', 'price_before_offer')->where('id', $cart[$k]['product_id'])->first()->makeHidden(['multiOptions', 'store', 'mainImage']);
                        // setlocale(LC_TIME, 'ar_EG.UTF-8');
                        Carbon::setLocale('en');
                    }else {
                        $product = Product::select('id', 'order_period', 'title_en', 'store_id', 'type', 'final_price', 'price_before_offer')->where('id', $cart[$k]['product_id'])->first()->makeHidden(['multiOptions', 'store', 'mainImage']);
                        setlocale(LC_TIME, 'ar_EG.UTF-8');
                        Carbon::setLocale('ar');
                    }

                    
                    $product['count'] = $cart[$k]['count'];
                    $product['store_name'] = $product->store->name;
                    if (isset($product->mainImage->image)) {
                        $product['image'] = $product->mainImage->image;
                    }else {
                        $product['image'] = "";
                    }
                    if ($product['store_id'] == $get_stores[$n]['id']) {
						if ($cart[$k]['option_id'] != 0) {
							for ($m = 0; $m < count($product->multiOptions); $m ++) {
								if ($product->multiOptions[$m]['id'] == $cart[$k]['option_id']) {
									$product['final_price'] = $product->multiOptions[$m]['final_price'];
									$sub_total_price = $sub_total_price + ($product->multiOptions[$m]['final_price'] * $cart[$k]['count']);
									$storeTotalPrice = $storeTotalPrice + ($product->multiOptions[$m]['final_price'] * $cart[$k]['count']);
									//var_dump($product->multiOptions[$m]['final_price']);
								}
							}
						}else {
							$sub_total_price = $sub_total_price + ($product['final_price'] * $cart[$k]['count']);
							$storeTotalPrice = $storeTotalPrice + ($product['final_price'] * $cart[$k]['count']);
						}
					}
					if ($product['store_id'] == $get_stores[$n]['id']) {
                        array_push($store_products, $product['id']);
                        array_push($data['cart'][$n]['products'], $product);
                    }
                }
                $data['cart'][$n]['total_cost'] = $storeTotalPrice;
				
                $today = Carbon::now();
                $current_day = Carbon::now();
                $max_period = Product::join('carts', 'carts.product_id', '=', 'products.id')
                ->whereIn('products.id', $store_products)
                ->where('carts.visitor_id', $visitor_id)
                ->select('products.id', DB::raw('MAX(products.order_period) AS max_period'), 'carts.count')
                ->groupBy('products.id')
                ->groupBy('carts.count')
                ->orderBy('max_period', 'desc')
                ->first();

                $min_period = Product::join('carts', 'carts.product_id', '=', 'products.id')
                ->whereIn('products.id', $store_products)
                ->where('carts.visitor_id', $visitor_id)
                ->select('products.id', DB::raw('MIN(products.order_period) AS min_period'), 'carts.count')
                ->groupBy('products.id')
                ->groupBy('carts.count')
                ->orderBy('min_period', 'asc')
                ->first();

                $max_total_period = $max_period['count'] * $max_period['max_period'];
                $min_total_period = $min_period['count'] * $min_period['min_period'];
                
                if ($request->lang == 'ar') {
                    if ($max_total_period > $min_total_period) {
                        $data['cart'][$n]['max_estimated_time_day'] = $today->addDays($max_total_period)->locale('ar_EG')->formatLocalized('%d');
                        $data['cart'][$n]['max_estimated_time_month'] = $today->addDays($max_total_period)->locale('ar_EG')->formatLocalized('%B');
                        $data['cart'][$n]['min_estimated_time_day'] = $current_day->addDays($min_total_period)->locale('ar_EG')->formatLocalized('%d');
                        $data['cart'][$n]['min_estimated_time_month'] = $current_day->addDays($min_total_period)->locale('ar_EG')->formatLocalized('%B');
                    }else if($max_total_period < $min_total_period) {
                        $data['cart'][$n]['max_estimated_time_day'] = $today->addDays($max_total_period)->locale('ar_EG')->formatLocalized('%d');
                        $data['cart'][$n]['max_estimated_time_month'] = $today->addDays($max_total_period)->locale('ar_EG')->formatLocalized('%B');
                        $data['cart'][$n]['min_estimated_time_day'] = $current_day->addDays($min_total_period)->locale('ar_EG')->formatLocalized('%d');
                        $data['cart'][$n]['min_estimated_time_month'] = $current_day->addDays($min_total_period)->locale('ar_EG')->formatLocalized('%B');
                    }else if($max_total_period == $min_total_period) {
                        $data['cart'][$n]['max_estimated_time_day'] = $current_day->addDays($max_total_period)->locale('ar_EG')->formatLocalized('%d');
                        $data['cart'][$n]['max_estimated_time_month'] = $current_day->addDays($max_total_period)->locale('ar_EG')->formatLocalized('%B');
                        $data['cart'][$n]['min_estimated_time_day'] = $today->addDays(1)->locale('ar_EG')->formatLocalized('%d');
                        $data['cart'][$n]['min_estimated_time_month'] = $today->addDays(1)->locale('ar_EG')->formatLocalized('%B');
                    }
                }else {
                    if ($max_total_period > $min_total_period) {
                        $data['cart'][$n]['max_estimated_time_day'] = $today->addDays($max_total_period)->formatLocalized('%d');
                        $data['cart'][$n]['max_estimated_time_month'] = $today->addDays($max_total_period)->formatLocalized('%B');
                        $data['cart'][$n]['min_estimated_time_day'] = $current_day->addDays($min_total_period)->formatLocalized('%d');
                        $data['cart'][$n]['min_estimated_time_month'] = $current_day->addDays($min_total_period)->formatLocalized('%B');
                    }else if($max_total_period < $min_total_period) {
                        $data['cart'][$n]['max_estimated_time_day'] = $today->addDays($max_total_period)->formatLocalized('%d');
                        $data['cart'][$n]['max_estimated_time_month'] = $today->addDays($max_total_period)->formatLocalized('%B');
                        $data['cart'][$n]['min_estimated_time_day'] = $current_day->addDays($min_total_period)->formatLocalized('%d');
                        $data['cart'][$n]['min_estimated_time_month'] = $current_day->addDays($min_total_period)->formatLocalized('%B');
                    }else if($max_total_period == $min_total_period) {
                        $data['cart'][$n]['max_estimated_time_day'] = $current_day->addDays($max_total_period)->formatLocalized('%d');
                        $data['cart'][$n]['max_estimated_time_month'] = $current_day->addDays($max_total_period)->formatLocalized('%B');
                        $data['cart'][$n]['min_estimated_time_day'] = $today->addDays(1)->formatLocalized('%d');
                        $data['cart'][$n]['min_estimated_time_month'] = $today->addDays(1)->formatLocalized('%B');
                    }
                }
                
            }
            $data['count'] = count($cart);
            $data['subtotal_price'] = $sub_total_price;
            $data['delivery_cost'] = $delivery_cost;
            // dd($data['delivery_cost']);
            $data['total_cost'] = $delivery_cost + $sub_total_price;
            $data['address'] = $address;
			$data['address']['area'] = $area;
            
            $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $data , $request->lang);
            return response()->json($response , 200);

        }else{
            $response = APIHelpers::createApiResponse(true , 406 , 'This Unique Id Not Registered' , 'This Unique Id Not Registered' , null , $request->lang);
            return response()->json($response , 406);
        }
    }
 

}