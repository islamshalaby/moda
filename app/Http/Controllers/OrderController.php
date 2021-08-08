<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\UserAddress;
use App\Area;
use App\Visitor;
use App\Product;
use App\ProductImage;
use App\Cart;
use App\Order;
use App\OrderItem;
use App\DeliveryArea;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Helpers\APIHelpers;
use App\Shop;
use App\ProductMultiOption;
use App\MainOrder;
use App\SizeDetail;
use App\Setting;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api' , ['except' => ['excute_pay' , 'pay_sucess' , 'pay_error']]);
    }
    
    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'unique_id' => 'required',
            'address_id' => 'required',
            'payment_method' => 'required'
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 , 'Missing Required Fields' , 'بعض الحقول مفقودة'  , null , $request->lang);
            return response()->json($response , 406);
        }

        $user_id = auth()->user()->id;
        if (auth()->user()->active == 0) {
            $response = APIHelpers::createApiResponse(true , 406 , 'User is not active' , 'تم حظر المستخدم'  , null , $request->lang);
            return response()->json($response , 406);
        }
        $visitor  = Visitor::where('unique_id' , $request->unique_id)->first();
        $user_id_unique_id = $visitor->user_id;
        $visitor_id = $visitor->id;
        $cart = Cart::where('visitor_id' , $visitor_id)->get();
        
		//dd(count($cart));
        if(count($cart) == 0){
            $response = APIHelpers::createApiResponse(true , 406 , 'Missing Required Fields' , 'بعض الحقول مفقودة'  , null , $request->lang);
            return response()->json($response , 406);
        }
        $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $main_order_number = substr(str_shuffle(uniqid() . $str) , -9);
        $address = UserAddress::where('id', auth()->user()->main_address_id)->first();
        
        $stores = Shop::join('products', 'products.store_id', '=', 'shops.id')
            ->where('carts.visitor_id', $visitor_id)
            ->leftjoin('carts', function($join) {
                $join->on('carts.product_id', '=', 'products.id');
            })
            ->pluck('shops.id')
            ->toArray();
        $unrepeated_stores1 = array_unique($stores);
        $unrepeated_stores = [];
        foreach ($unrepeated_stores1 as $key => $value) {
			array_push($unrepeated_stores, $value); 
		}
        //dd($unrepeated_stores);
        if($request->payment_method == 3){
            $main_order = MainOrder::create([
                'user_id' => auth()->user()->id,
                'address_id' => $request->address_id,
                'payment_method' => $request->payment_method,
                'main_order_number' => $main_order_number
            ]);
            if (count($stores) > 0) {
				
                for ($i = 0; $i < count($unrepeated_stores); $i ++) {
                    $store_products = Cart::where('store_id', $unrepeated_stores[$i])->where('visitor_id', $visitor_id)->get();
                    
                    $pluck_products = Cart::where('store_id', $unrepeated_stores[$i])->pluck('product_id')->toArray();
                    if (count($store_products) > 0) {
                        $subtotal_price = 0;
                        for ($n = 0; $n < count($store_products); $n ++) {
                            if($store_products[$n]->product->remaining_quantity < $store_products[$n]['count']){
                                $d_main_order = MainOrder::find($main_order['id']);
                                $d_main_order->delete();
                                $response = APIHelpers::createApiResponse(true , 406 , 'The remaining amount of the product is not enough' , 'الكميه المتبقيه من المنتج غير كافيه'  , null , $request->lang);
                                return response()->json($response , 406);
                            }
                            $single_product = Product::select('id', 'remaining_quantity')->where('id', $store_products[$n]['product_id'])->first();
                            $single_product->remaining_quantity = $single_product->remaining_quantity - $store_products[$n]['count'];
                            $single_product->save();
                            if ($store_products[$n]['option_id'] != 0) {
                                $m_option = ProductMultiOption::find($store_products[$n]['option_id']);
                                $subtotal_price = $subtotal_price + ($m_option['final_price'] * $store_products[$n]['count']);
                                $m_option->remaining_quantity = $m_option->remaining_quantity - $store_products[$n]['count'];
								$m_option->save();
                            }else {
                                $subtotal_price = $subtotal_price + ($store_products[$n]->product->final_price * $store_products[$n]['count']);
                            }
                        }
                    }
    
                    $max_period = Product::join('carts', 'carts.product_id', '=', 'products.id')
                    ->whereIn('products.id', $pluck_products)
                    ->where('carts.visitor_id', $visitor_id)
                    ->select('products.id', DB::raw('MAX(products.order_period) AS max_period'), 'carts.count')
                    ->groupBy('products.id')
                    ->groupBy('carts.count')
                    ->orderBy('max_period', 'desc')
                    ->first();
    
                    $min_period = Product::join('carts', 'carts.product_id', '=', 'products.id')
                    ->whereIn('products.id', $pluck_products)
                    ->where('carts.visitor_id', $visitor_id)
                    ->select('products.id', DB::raw('MIN(products.order_period) AS min_period'), 'carts.count')
                    ->groupBy('products.id')
                    ->groupBy('carts.count')
                    ->orderBy('min_period', 'asc')
                    ->first();
    
                    $today = Carbon::now();
                    $current_day = Carbon::now();
                    $max_total_period = $max_period['count'] * $max_period['max_period'];
                    $min_total_period = $min_period['count'] * $min_period['min_period'];
                    if ($max_total_period > $min_total_period) {
                        $to_deliver_date = $today->addDays($max_total_period)->format('Y-m-d');
                        $from_deliver_date = $current_day->addDays($min_total_period)->format('Y-m-d');
                    }else if($max_total_period < $min_total_period) {
                        $from_deliver_date = $today->addDays($max_total_period)->format('Y-m-d');
                        $to_deliver_date = $current_day->addDays($min_total_period)->format('Y-m-d');
                    }else if($max_total_period == $min_total_period) {
                        $from_deliver_date = $today->addDays(1)->format('Y-m-d');
                        $to_deliver_date = $current_day->addDays($max_total_period)->format('Y-m-d');
                    }
                    // dd($to_deliver_date);
                    $delivery = DeliveryArea::select('delivery_cost')->where('area_id', $address['area_id'])->where('store_id', $unrepeated_stores[$i])->first();
                    // dd($delivery);
                    if (!isset($delivery['delivery_cost'])) {
                        $delivery = Setting::find(1);
                    }
                    $total_cost = $delivery['delivery_cost'] + $subtotal_price;
                    
                    $order = Order::create([
                        'user_id' => auth()->user()->id,
                        'address_id' => $request->address_id,
                        'payment_method' => $request->payment_method,
                        'subtotal_price' => $subtotal_price,
                        'delivery_cost' => $delivery['delivery_cost'],
                        'total_price' => $total_cost,
                        'order_number' => substr(str_shuffle(uniqid() . $str) , -9),
                        'store_id' => $unrepeated_stores[$i],
                        'from_deliver_date' => $from_deliver_date,
                        'to_deliver_date' => $to_deliver_date,
                        'main_id' => $main_order['id']
                        ]);
                        
                        for($k = 0; $k < count($store_products); $k++){
                            $option_en = "";
                            $option_ar = "";
                            $val_en = "";
                            $val_ar = "";
                            if ($store_products[$k]['option_id'] != 0) {
                                $product_data = ProductMultiOption::where('id', $store_products[$k]['option_id'])->first();
                                $option_en = $product_data['option_en'];
                                $option_ar = $product_data['option_ar'];
                                $val_en = $product_data['val_en'];
                                $val_ar = $product_data['val_ar'];
                            }else {
                                $product_data = Product::select('final_price', 'price_before_offer')->where('id', $store_products[$k]['product_id'])->first();
                            }
                            $order_item =  OrderItem::create([
                                'order_id' => $order->id,
                                'product_id' => $store_products[$k]['product_id'],
                                'option_id' => $store_products[$k]['option_id'],
                                'option_en' => $option_en,
                                'option_ar' => $option_ar,
                                'val_en' => $val_en,
                                'val_ar' => $val_ar,
                                'price_before_offer' => $product_data['price_before_offer'],
                                'final_price' => $product_data['final_price'],
                                'count' => $store_products[$k]['count']
                            ]);
                            $size_details = SizeDetail::where('cart_id', $store_products[$k]['id'])->where('product_id', $store_products[$k]['product_id'])->first();
                                if ($size_details){
                                    $size_details->update(['order_id' => $order_item['id']]);
                                }
							$cartItem = Cart::find($store_products[$k]['id']);
							$cartItem->delete();  
                        }
                }
            }
            $u_main_order = MainOrder::find($main_order['id']);
            $u_main_order->update([
                'subtotal_price' => $main_order->orders->sum('subtotal_price'),
                'delivery_cost' => $main_order->orders->sum('delivery_cost'),
                'total_price' => $main_order->orders->sum('total_price')
            ]);
			$data=(object)['url' => ''];
            $mailData['main_order'] = $u_main_order;
            $mailData['setting'] = Setting::where('id', 1)->first();
            $userData = auth()->user();
            Mail::send('invoice_mail', $mailData, function($message) use ($userData) {
                
                $message->to(['q8m000da@gmail.com', $userData->email])->subject
            ('Invoice');
                $message->from('modaapp9@gmail.com','moda-kw.com');
                
            });
            $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $data , $request->lang);
            return response()->json($response , 200);
        }else {
            
            if (count($stores) > 0) {
                $total_price = 0;
                for ($i = 0; $i < count($unrepeated_stores); $i ++) {
                    $store_products = Cart::where('visitor_id' , $visitor_id)->where('store_id', $unrepeated_stores[$i])->get();
                    
                    $pluck_products = Cart::where('visitor_id' , $visitor_id)->where('store_id', $unrepeated_stores[$i])->pluck('product_id')->toArray();
                    if (count($store_products) > 0) {
                        $subtotal_price = 0;
                        for ($n = 0; $n < count($store_products); $n ++) {
                            if($store_products[$n]->product->remaining_quantity < $store_products[$n]['count']){
                                $response = APIHelpers::createApiResponse(true , 406 , 'The remaining amount of the product is not enough' , 'الكميه المتبقيه من المنتج غير كافيه'  , null , $request->lang);
                                return response()->json($response , 406);
                            }
                            
                            if ($store_products[$n]['option_id'] != 0) {
                                $m_option = ProductMultiOption::find($store_products[$n]['option_id']);
                                $subtotal_price = $subtotal_price + ($m_option['final_price'] * $store_products[$n]['count']);
                                $m_option->remaining_quantity = $m_option->remaining_quantity - $store_products[$n]['count'];
                            }else {
                                $subtotal_price = $subtotal_price + ($store_products[$n]->product->final_price * $store_products[$n]['count']);
                            }
                        }
                    }
    
                    $max_period = Product::join('carts', 'carts.product_id', '=', 'products.id')
                    ->whereIn('products.id', $pluck_products)
                    ->where('carts.visitor_id', $visitor_id)
                    ->select('products.id', DB::raw('MAX(products.order_period) AS max_period'), 'carts.count')
                    ->groupBy('products.id')
                    ->groupBy('carts.count')
                    ->orderBy('max_period', 'desc')
                    ->first();
    
                    $min_period = Product::join('carts', 'carts.product_id', '=', 'products.id')
                    ->whereIn('products.id', $pluck_products)
                    ->where('carts.visitor_id', $visitor_id)
                    ->select('products.id', DB::raw('MIN(products.order_period) AS min_period'), 'carts.count')
                    ->groupBy('products.id')
                    ->groupBy('carts.count')
                    ->orderBy('min_period', 'asc')
                    ->first();
    
                    $today = Carbon::now();
                    $current_day = Carbon::now();
                    $max_total_period = $max_period['count'] * $max_period['max_period'];
                    $min_total_period = $min_period['count'] * $min_period['min_period'];
                    if ($max_total_period > $min_total_period) {
                        $to_deliver_date = $today->addDays($max_total_period)->format('Y-m-d');
                        $from_deliver_date = $current_day->addDays($min_total_period)->format('Y-m-d');
                    }else if($max_total_period < $min_total_period) {
                        $from_deliver_date = $today->addDays($max_total_period)->format('Y-m-d');
                        $to_deliver_date = $current_day->addDays($min_total_period)->format('Y-m-d');
                    }else if($max_total_period == $min_total_period) {
                        $from_deliver_date = $today->addDays(1)->format('Y-m-d');
                        $to_deliver_date = $current_day->addDays($max_total_period)->format('Y-m-d');
                    }
                    // dd($to_deliver_date);
                    $delivery = DeliveryArea::select('delivery_cost')->where('area_id', $address['area_id'])->where('store_id', $unrepeated_stores[$i])->first();
                    if (!isset($delivery['delivery_cost'])) {
                        $delivery = Setting::find(1);
                    }
                    $total_cost = $delivery['delivery_cost'] + $subtotal_price;
                    $total_price = $total_price + $total_cost;
                }
            }
            
            $root_url = $request->root();
        	$user = auth()->user();
    		
            $path='https://api.myfatoorah.com/v2/SendPayment';
			$token="bearer PxF4q1UaAq9h4reeZyns6s5I40YEGgGmZ4-nGCsLSGjBVf1NHtHQd4-XN7zEdGGeLGEFYM8MxSDfiwIQufpoy4mY6jbmFOtBTcLQtqQ8XCUhqq3KRsDQRoO8XL6ZWWiTxG-yroyF2G-NqDdsVJpz89pnsrFlrep6mZez8326wYum8KmzrQJ4r3IN5rTksTd09n3QiLqvbNEroafweU1MePpLh_PV2x_aYvK3jOh2Qd-5FSXgsjnhVjtxdc-3tIwrjwoZ0GNFabf6e_N_m2HN2_gCyInUjvUpKk2op3Cb-2fNtANQvRIPouJA6a0PCCFD1r0XSPlMBgvyX1R9kJaf2LUHL4u89pQiTr8dtta--TWf79Hf56b7-oEpcP5dQx6Vd4bsHQRA9sq-OIlBid7WHoXvkRwH5CVopnk-wUFIURNyDR0WBe5c-Wx9Qu-iBECEBdWQvE9-q6ObEcvyDn1ih0tEKnOov4WTV0U3j08vuq3AeEGF7XxJDzIqggqC7Z1hvKcJxXF0CqRcgikfy_ezWCE3PdH4VcUKtf3-nzAQ7SpZYC8iDsebp7stNQ44Su3i_ChTv3X_ohSqyf7LwlJUoX2m_Bq4hLSCu-JS9WVIS_KmOHtq3fp9jkPdl8sJ9F-Xui7S3hOhaqL1DWkC728iagYD99uZ5bKFBAZ0xLN2i_O8pXML4xvXI9xLEHPs8tuVUS-04TdOMX-trAA7cc08hfsP7l1kqSnyAoEHRw2BjwT974ke";

        $headers = array(
            'Authorization:' .$token,
            'Content-Type:application/json'
        );
        
            $price = $total_price;
            $call_back_url = $root_url."/api/excute_pay?user_id=".$user->id."&unique_id=".$request->unique_id."&address_id=".$request->address_id."&payment_method=".$request->payment_method;
            $error_url = $root_url."/api/pay/error";
            $fields =array(
				"CustomerName" => $user->name,
				"NotificationOption" => "LNK",
				"InvoiceValue" => $price,
				"CallBackUrl" => $call_back_url,
				"ErrorUrl" => $error_url,
				"Language" => "AR",
				"CustomerEmail" => $user->email
        	); 
    
            $payload =json_encode($fields);
            $curl_session =curl_init();
            curl_setopt($curl_session,CURLOPT_URL, $path);
            curl_setopt($curl_session,CURLOPT_POST, true);
            curl_setopt($curl_session,CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl_session,CURLOPT_RETURNTRANSFER,true);
            curl_setopt($curl_session,CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl_session,CURLOPT_IPRESOLVE, CURLOPT_IPRESOLVE);
            curl_setopt($curl_session,CURLOPT_POSTFIELDS, $payload);
			
            $result=curl_exec($curl_session);
			//dd($result);
            curl_close($curl_session);
            $result = json_decode($result);
            // dd($result);
            $data['url'] = $result->Data->InvoiceURL;
            
            $response = APIHelpers::createApiResponse(false , 200 ,  '' , '' , $data , $request->lang );
            return response()->json($response , 200); 
        }
        
    }

    public function excute_pay(Request $request){
        $user = User::find($request->user_id);
        $user_id = $user->id;
        $visitor  = Visitor::where('unique_id' , $request->unique_id)->first();
        $user_id_unique_id = $visitor->user_id;
        $visitor_id = $visitor->id;
        $cart = Cart::where('visitor_id' , $visitor_id)->get();

        $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $main_order_number = substr(str_shuffle(uniqid() . $str) , -9);
        $address = UserAddress::select('area_id')->find($request->address_id);
        $stores = Shop::join('products', 'products.store_id', '=', 'shops.id')
            ->where('carts.visitor_id', $visitor_id)
            ->leftjoin('carts', function($join) {
                $join->on('carts.product_id', '=', 'products.id');
            })
            ->pluck('shops.id')
            ->toArray();
        $unrepeated_stores1 = array_unique($stores);
        $unrepeated_stores = [];
        foreach ($unrepeated_stores1 as $key => $value) {
			array_push($unrepeated_stores, $value); 
		}
        $main_order = MainOrder::create([
            'user_id' => auth()->user()->id,
            'address_id' => $request->address_id,
            'payment_method' => $request->payment_method,
            'main_order_number' => $main_order_number
        ]);
        if (count($stores) > 0) {
            for ($i = 0; $i < count($unrepeated_stores); $i ++) {
                $store_products = Cart::where('store_id', $unrepeated_stores[$i])->where('visitor_id', $visitor_id)->get();
                
                $pluck_products = Cart::where('store_id', $unrepeated_stores[$i])->pluck('product_id')->toArray();
                if (count($store_products) > 0) {
                    $subtotal_price = 0;
                    for ($n = 0; $n < count($store_products); $n ++) {
                        if($store_products[$n]->product->remaining_quantity < $cart[$n]['count']){
                            $d_main_order = MainOrder::find($main_order['id']);
                            $d_main_order->delete();
                            $response = APIHelpers::createApiResponse(true , 406 , 'The remaining amount of the product is not enough' , 'الكميه المتبقيه من المنتج غير كافيه'  , null , $request->lang);
                            return response()->json($response , 406);
                        }
                        $single_product = Product::select('id', 'remaining_quantity')->where('id', $store_products[$n]['product_id'])->first();
                        $single_product->remaining_quantity = $single_product->remaining_quantity - $store_products[$n]['count'];
                        $single_product->save();
                        if ($store_products[$n]['option_id'] != 0) {
                            $m_option = ProductMultiOption::find($store_products[$n]['option_id']);
                            $subtotal_price = $subtotal_price + ($m_option['final_price'] * $store_products[$n]['count']);
                            $m_option->remaining_quantity = $m_option->remaining_quantity - $store_products[$n]['count'];
                        }else {
                            $subtotal_price = $subtotal_price + ($store_products[$n]->product->final_price * $store_products[$n]['count']);
                        }
                    }
                }

                $max_period = Product::join('carts', 'carts.product_id', '=', 'products.id')
                ->whereIn('products.id', $pluck_products)
                ->where('carts.visitor_id', $visitor_id)
                ->select('products.id', DB::raw('MAX(products.order_period) AS max_period'), 'carts.count')
                ->groupBy('products.id')
                ->groupBy('carts.count')
                ->orderBy('max_period', 'desc')
                ->first();

                $min_period = Product::join('carts', 'carts.product_id', '=', 'products.id')
                ->whereIn('products.id', $pluck_products)
                ->where('carts.visitor_id', $visitor_id)
                ->select('products.id', DB::raw('MIN(products.order_period) AS min_period'), 'carts.count')
                ->groupBy('products.id')
                ->groupBy('carts.count')
                ->orderBy('min_period', 'asc')
                ->first();

                $today = Carbon::now();
                $current_day = Carbon::now();
                $max_total_period = $max_period['count'] * $max_period['max_period'];
                $min_total_period = $min_period['count'] * $min_period['min_period'];
                if ($max_total_period > $min_total_period) {
                    $to_deliver_date = $today->addDays($max_total_period)->format('Y-m-d');
                    $from_deliver_date = $current_day->addDays($min_total_period)->format('Y-m-d');
                }else if($max_total_period < $min_total_period) {
                    $from_deliver_date = $today->addDays($max_total_period)->format('Y-m-d');
                    $to_deliver_date = $current_day->addDays($min_total_period)->format('Y-m-d');
                }else if($max_total_period == $min_total_period) {
                    $from_deliver_date = $today->addDays(1)->format('Y-m-d');
                    $to_deliver_date = $current_day->addDays($max_total_period)->format('Y-m-d');
                }
                // dd($to_deliver_date);
                $delivery = DeliveryArea::select('delivery_cost')->where('area_id', $address['area_id'])->where('store_id', $unrepeated_stores[$i])->first();
                if (!isset($delivery['delivery_cost'])) {
                    $delivery = Setting::find(1);
                }
                $total_cost = $delivery['delivery_cost'] + $subtotal_price;
                
                $order = Order::create([
                    'user_id' => auth()->user()->id,
                    'address_id' => $request->address_id,
                    'payment_method' => $request->payment_method,
                    'subtotal_price' => $subtotal_price,
                    'delivery_cost' => $delivery['delivery_cost'],
                    'total_price' => $total_cost,
                    'order_number' => substr(str_shuffle(uniqid() . $str) , -9),
                    'store_id' => $unrepeated_stores[$i],
                    'from_deliver_date' => $from_deliver_date,
                    'to_deliver_date' => $to_deliver_date,
                    'main_id' => $main_order['id']
                    ]);

                    for($k = 0; $k < count($store_products); $k++){
                        $option_en = "";
                        $option_ar = "";
                        $val_en = "";
                        $val_ar = "";
                        if ($store_products[$k]['option_id'] != 0) {
                            $product_data = ProductMultiOption::where('id', $store_products[$k]['option_id'])->first();
                            $option_en = $product_data['option_en'];
                            $option_ar = $product_data['option_ar'];
                            $val_en = $product_data['val_en'];
                            $val_ar = $product_data['val_ar'];
                        }else {
                            $product_data = Product::select('final_price', 'price_before_offer')->where('id', $store_products[$k]['product_id'])->first();
                        }
                        $order_item =  OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $store_products[$k]['product_id'],
                            'option_id' => $store_products[$k]['option_id'],
                            'option_en' => $option_en,
                            'option_ar' => $option_ar,
                            'val_en' => $val_en,
                            'val_ar' => $val_ar,
                            'price_before_offer' => $product_data['price_before_offer'],
                            'final_price' => $product_data['final_price'],
                            'count' => $store_products[$k]['count']
                        ]);
                        $size_details = SizeDetail::where('cart_id', $store_products[$k]['id'])->where('product_id', $store_products[$k]['product_id'])->first();
                            if($size_details) {
                                $size_details->update(['order_id' => $order_item['id']]);
                            }
						$cartItem = Cart::find($store_products[$k]['id']);
						$cartItem->delete();
                        
                                               
                    }
            }
        }
        $u_main_order = MainOrder::find($main_order['id']);
        $u_main_order->update([
            'subtotal_price' => $main_order->orders->sum('subtotal_price'),
            'delivery_cost' => $main_order->orders->sum('delivery_cost'),
            'total_price' => $main_order->orders->sum('total_price')
        ]);

        $mailData['main_order'] = $u_main_order;
        $mailData['setting'] = Setting::where('id', 1)->first();
        $userData = $user;
        Mail::send('invoice_mail', $mailData, function($message) use ($userData) {
            
            $message->to(['q8m000da@gmail.com', $userData->email])->subject
        ('Invoice');
            $message->from('modaapp9@gmail.com','moda-kw.com');
            
        });


        return redirect('api/pay/success'); 
    }

    public function getorders(Request $request){
        $user_id = auth()->user()->id;
        $orders = MainOrder::where('user_id' , $user_id)->select('id' , 'total_price' , 'main_order_number' , 'created_at as date')->orderBy('id' , 'desc')->get();
        $orderDates = MainOrder::where('user_id' , $user_id)->pluck('created_at')->toArray();
        for ($k = 0; $k < count($orderDates); $k ++) {
            $ordersDays[$k] = date_format(date_create($orderDates[$k]) , "d-m-Y");
        }
		
        $unrepeated_days1 = array_unique($ordersDays);
		$unrepeated_days = [];
        foreach ($unrepeated_days1 as $key => $value) {
			array_push($unrepeated_days, $value); 
        }
        $data = [];
        
        for ($n = 0; $n < count($unrepeated_days); $n ++) {
            $dayOrders = [];
            
            for($i = 0; $i < count($orders); $i++){
                if ($unrepeated_days[$n] == date_format(date_create($orders[$i]['date']), "d-m-Y")) {
                    $items = OrderItem::join('orders','orders.id', '=','order_items.order_id')
                    ->where('main_orders.id', $orders[$i]['id'])
                    ->leftjoin('main_orders', function($join) {
                        $join->on('main_orders.id', '=', 'orders.main_id');
                    })
                    ->select(DB::raw('SUM(order_items.count) as cnt'), 'order_items.product_id as pId')
                    ->groupBy('order_items.count')
                    ->groupBy('order_items.product_id')
                    ->get();
                    $images = [];
                    for ($k = 0; $k < count($items); $k ++) {
                        $product = Product::select('id')->where('id', $items[$k]['pId'])->first();
        
                        array_push($images, $product->mainImage['image']);
                    }
                    $orders[$i]['images'] = $images;
                    $orders[$i]['count'] = $items->sum('cnt');
                    $date = date_create($orders[$i]['date']);
                    $orderDate = date_format($date , "d-m-Y");
                    $dayOrder = (object)[
                        'id' => $orders[$i]['id'],
                        'images' => $images,
                        'count' => count($items),
                        'date' => $orderDate,
                        'total_price' => $orders[$i]['total_price'],
                        'main_order_number' => $orders[$i]['main_order_number']
                    ];

                    array_push($dayOrders, $dayOrder);
                }
                 
            }
            
            $data[$n]['day'] = $unrepeated_days[$n];
            $data[$n]['orders'] = $dayOrders;
        }
        
        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $data , $request->lang);
        return response()->json($response , 200);
    }

    public function pay_sucess(){
        return "Please wait ...";
    }

    public function pay_error(){
        return "Please wait ...";
    }
    
    public function orderdetails(Request $request){
        $order_id = $request->id;
        $order = MainOrder::select('id', 'payment_method', 'subtotal_price', 'delivery_cost', 'total_price', 'status', 'main_order_number', 'address_id', 'created_at')->where('id', $order_id)->first()->makeHidden(['address_id', 'orders_with_select', 'created_at']);
        $mainOrderDate = date_create($order['created_at']);
        $order['date'] = date_format($mainOrderDate , "d-m-Y");
        $address = UserAddress::find($order['address_id'])->makeHidden(['area_id', 'area_with_select', 'created_at', 'updated_at']);
        $data['order'] = $order;
        $stores = $order->orders_with_select->makeHidden(['store', 'oItems', 'from_deliver_date', 'to_deliver_date']);
        if (count($stores) > 0) {
            for ($i = 0; $i < count($stores); $i ++) {
                
                $stores[$i]['store_name'] = $stores[$i]->store->name;
                $from_date = date_create($stores[$i]['from_deliver_date']);
                $to_date = date_create($stores[$i]['to_deliver_date']);
				$orderDate = date_create($stores[$i]['created_at']);
                $stores[$i]['date'] = date_format($orderDate , "d-m-Y");
                $stores[$i]['from_delivery_date'] = date_format($from_date , "d-M-Y");
                $stores[$i]['to_delivery_date'] = date_format($to_date , "d-M-Y");
                // dd($stores[$i]);
                $details = (object)[
                    "store_name" => $stores[$i]->store->name,
                    "date" => date_format($orderDate , "d-m-Y"),
                    "from_delivery_date" => date_format($from_date , "d-M-Y"),
                    "to_delivery_date" => date_format($to_date , "d-M-Y"),
                    "subtotal_price" => $stores[$i]['subtotal_price'],
                    "delivery_cost" => $stores[$i]['delivery_cost'],
                    "total_price" => $stores[$i]['total_price'],
                    "order_number" => $stores[$i]['order_number'],
                    "store_id" => $stores[$i]['store_id'],
                    "main_id" => $stores[$i]['main_id'],
                    "id" => $stores[$i]['id']
                ];
                $products = [];
                if (count($stores[$i]->oItems) > 0) {
                    for ($n = 0; $n < count($stores[$i]->oItems); $n ++) {
                        $stores[$i]->oItems[$n]['product'] = $stores[$i]->oItems[$n]->product_with_select->makeHidden(['mainImage', 'multi_options']);
                        $stores[$i]->oItems[$n]['product']['count'] = $stores[$i]->oItems[$n]['count'];
                        $stores[$i]->oItems[$n]['product']['status'] = $stores[$i]->oItems[$n]['status'];
                        $stores[$i]->oItems[$n]['product']['image'] = $stores[$i]->oItems[$n]->product_with_select->mainImage['image'];
                        $stores[$i]->oItems[$n]['product']['size_prop'] = "";
                        $stores[$i]->oItems[$n]['product']['size_value'] = "";
                        if ($stores[$i]->oItems[$n]['option_id'] != 0) {
                            $size = ProductMultiOption::find($stores[$i]->oItems[$n]['option_id']);
                            $stores[$i]->oItems[$n]['product']['size_prop'] = $size->multiOption->title_ar;
                            $stores[$i]->oItems[$n]['product']['size_value'] = $size->multiOptionValue->value_ar;
                        }
                        array_push($products, $stores[$i]->oItems[$n]->product_with_select);
                    }
                }
                array_unshift($products, $details);
                
                $stores[$i]['products'] = $products;
                // array_push($stores[$i]['products'], $details);
                // dd($stores[$i]['products']);
            }
        }

        $data['stores'] = $stores;
        
        if($address){
            $address['area'] = $address->area_with_select['title'];
            $data['address'] = $address;
        }else{
            $data['address'] = new \stdClass();
        }
        
        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $data , $request->lang);
        return response()->json($response , 200);

    }

}