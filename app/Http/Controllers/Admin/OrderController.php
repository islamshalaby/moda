<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\MainOrder;
use App\MultiOption;
use App\OrderItem;
use App\Area;
use App\UserAddress;
use App\SizeDetail;


class OrderController extends AdminController{
    // get all orders
    public function show(Request $request){
        $data['orders'] = MainOrder::orderBy('id' , 'desc')->get();
        $data['areas'] = Area::where('deleted', 0)->orderBy('id', 'desc')->get();
        $data['sum_price'] = MainOrder::sum('subtotal_price');
        $data['sum_delivery'] = MainOrder::sum('delivery_cost');
        $data['sum_total'] = MainOrder::sum('total_price');
        
        return view('admin.orders' , ['data' => $data]);
    }

    // cancel | delivered order
    public function action_order(MainOrder $order, $status) {
        $order->update(['status' => $status]);
        foreach($order->orders as $sub_order) {
            $sub_order->status = $status;
            $sub_order->save;
            foreach($sub_order->oItems as $item) {
                $item->status = $status;
                $item->save();
                if ($status == 3) {
                    if ($order->status == 2) {
                        $item->product->sold_count = $item->product->sold_count - $item->count;
                    }
                    $item->product->remaining_quantity = $item->product->remaining_quantity + $item->count;
                    $item->product->save();
                    if ($item->option_id != 0) {
                        $item->product->mOptionsWhere($item->option_id)
                        ->update(['remaining_quantity' => $item->product->mOptionsWhere($item->option_id)->remaining_quantity + $item->count]);
                    }
                }
                if ($status == 2) {
                    $item->product->sold_count = $item->product->sold_count + $item->count;
                    $item->product->save();
                }
            }
        }

        return redirect()->back();
    }

    

    // details
    public function details(MainOrder $order) {
        $data['order'] = $order;
        $data['m_option'] = MultiOption::find(8);
        // dd($data['order']->orders[0]->oItems[0]);

        return view('admin.order_details', ['data' => $data]);
    }

    // order items actions
    public function order_items_actions(OrderItem $item, $status) {
        $item->update(['status' => $status]);
        $order = MainOrder::where('id', $item->order->main_id)->first();
        if ($status == 3) {
            if ($order->status == 2) {
                $item->product->sold_count = $item->product->sold_count - $item->count;
            }
            if ($item->option_id != 0) {
                $item->product->remaining_quantity = $item->product->remaining_quantity + $item->count;
                $item->product->mOptionsWhere($item->option_id)->remaining_quantity = $item->product->mOptionsWhere($item->option_id)->remaining_quantity + $item->count;
                $item->product->mOptionsWhere($item->option_id)->update(['remaining_quantity' => $item->product->mOptionsWhere($item->option_id)->remaining_quantity + $item->count]);
                $order->update(['subtotal_price' => $order->subtotal_price - ($item->product->mOptionsWhere($item->option_id)->final_price * $item->count), 'total_price' => $order->total_price - ($item->product->mOptionsWhere($item->option_id)->final_price * $item->count) ]);
                $item->order->update(['subtotal_price' => $item->order->subtotal_price - ($item->product->mOptionsWhere($item->option_id)->final_price * $item->count), 'total_price' => $item->order->total_price - ($item->product->mOptionsWhere($item->option_id)->final_price * $item->count)]);
            }else {
                $item->product->remaining_quantity = $item->product->remaining_quantity + $item->count;
                $order->update(['subtotal_price' => $order->subtotal_price - ($item->product->final_price * $item->count), 'total_price' => $order->total_price - ($item->product->final_price * $item->count)]);
                $item->order->update(['subtotal_price' => $item->order->subtotal_price - ($item->product->final_price * $item->count), 'total_price' => $item->order->total_price - ($item->product->final_price * $item->count)]);
            }
            $item->product->save();
            
        }

        if ($status == 2) {
            $item->product->update(['sold_count' => $item->product->sold_count + $item->count]);
        }
        
        $sub_orders_status = [];
        
        
        foreach($order->orders as $sub_order) {
            $sub_order->status = $status;
            $sub_order->save();
            $item_status = $sub_order->oItems->pluck('status');
            for ($i = 0; $i < count($item_status); $i ++) {
                array_push($sub_orders_status, $item_status[$i]);
            }
        }

        if (in_array(1, $sub_orders_status)) {
            $order->update(['status' => 1]);
        }else if(in_array(3, $sub_orders_status) && array_count_values($sub_orders_status)[3] == count($sub_orders_status)) {
            $order->update(['status' => 3]);
        }else {
            $order->update(['status' => 2]);
        }

        return redirect()->back();
    }

    // filter orders
    public function filter_orders(Request $request, $status) {
        if (isset($request->area_id)) {
            $addresses = UserAddress::with('orders')->where('area_id', $request->area_id)->get();
            $data['sum_price'] = 0;
            $data['sum_delivery'] = 0;
            $data['sum_total'] = 0;
            $orders = [];
            if (count($addresses) > 0) {
                foreach ($addresses as $address) {
                    if (count($address->orders) > 0) {
                        foreach($address->orders as $order) {
                            if ($order->status == $status) {
                                $data['sum_price'] += $order->subtotal_price;
                                $data['sum_delivery'] += $order->delivery_cost;
                                $data['sum_total'] += $order->total_price;
                                array_push($orders, $order);
                            }
                        }
                    }
                }
            }
            $data['orders'] = $orders;
            $data['areas'] = Area::where('deleted', 0)->orderBy('id', 'desc')->get();
            $data['area'] = Area::findOrFail($request->area_id);
        }elseif(isset($request->from)) {
            $data['orders'] = MainOrder::where('status', $status)->whereBetween('created_at', array($request->from, $request->to))->get();
            $data['areas'] = Area::where('deleted', 0)->orderBy('id', 'desc')->get();
            $data['sum_price'] = MainOrder::whereBetween('created_at', array($request->from, $request->to))->sum('subtotal_price');
            $data['sum_delivery'] = MainOrder::whereBetween('created_at', array($request->from, $request->to))->sum('delivery_cost');
            $data['sum_total'] = MainOrder::whereBetween('created_at', array($request->from, $request->to))->sum('total_price');
        }else if(isset($request->method)) {
            $data['orders'] = MainOrder::where('status', $status)->where('payment_method', $request->method)->get();
            $data['areas'] = Area::where('deleted', 0)->orderBy('id', 'desc')->get();
            $data['sum_price'] = MainOrder::where('status', $status)->where('payment_method', $request->method)->sum('subtotal_price');
            $data['sum_delivery'] = MainOrder::where('status', $status)->where('payment_method', $request->method)->sum('delivery_cost');
            $data['sum_total'] = MainOrder::where('status', $status)->where('payment_method', $request->method)->sum('total_price');
            $data['method'] = $request->method;
        }else if(isset($request->sub_number)) {
            $data['orders'] = MainOrder::where('status', $status)->whereHas('orders', function($q) use($request) {
                $q->where('order_number', 'like','%' . $request->sub_number . '%');
            })->get();
            $data['areas'] = Area::where('deleted', 0)->orderBy('id', 'desc')->get();
            $data['sum_price'] = MainOrder::where('status', $status)->whereHas('orders', function($q) use($request) {
                $q->where('order_number', 'like','%' . $request->sub_number . '%');
            })->sum('subtotal_price');
            $data['sum_delivery'] = MainOrder::where('status', $status)->whereHas('orders', function($q) use($request) {
                $q->where('order_number', 'like','%' . $request->sub_number . '%');
            })->sum('delivery_cost');
            $data['sum_total'] = MainOrder::where('status', $status)->whereHas('orders', function($q) use($request) {
                $q->where('order_number', 'like','%' . $request->sub_number . '%');
            })->sum('total_price');
        }else if(isset($request->user_phone)) {
            $data['orders'] = MainOrder::where('status', $status)->whereHas('user', function($q) use($request) {
                $q->where('phone', 'like','%' . $request->user_phone . '%');
            })->get();
            $data['areas'] = Area::where('deleted', 0)->orderBy('id', 'desc')->get();
            $data['sum_price'] = MainOrder::where('status', $status)->whereHas('user', function($q) use($request) {
                $q->where('phone', 'like','%' . $request->user_phone . '%');
            })->sum('subtotal_price');
            $data['sum_delivery'] = MainOrder::where('status', $status)->whereHas('user', function($q) use($request) {
                $q->where('phone', 'like','%' . $request->user_phone . '%');
            })->sum('delivery_cost');
            $data['sum_total'] = MainOrder::where('status', $status)->whereHas('user', function($q) use($request) {
                $q->where('phone', 'like','%' . $request->user_phone . '%');
            })->sum('total_price');
        }else {
            $data['orders'] = MainOrder::where('status', $status)->get();
            $data['areas'] = Area::where('deleted', 0)->orderBy('id', 'desc')->get();
            $data['sum_price'] = MainOrder::where('status', $status)->sum('subtotal_price');
            $data['sum_delivery'] = MainOrder::where('status', $status)->sum('delivery_cost');
            $data['sum_total'] = MainOrder::where('status', $status)->sum('total_price');
        }
        

        return view('admin.orders' , ['data' => $data]);
    }

    // fetch orders by area
    public function fetch_orders_by_area(Request $request) {
        $addresses = UserAddress::with('orders')->where('area_id', $request->area_id)->get();
        
        $orders = [];
        $data['sum_price'] = 0;
        $data['sum_delivery'] = 0;
        $data['sum_total'] = 0;
        if (count($addresses) > 0) {
            foreach ($addresses as $address) {
                if (count($address->orders) > 0) {
                    foreach($address->orders as $order) {
                        $data['sum_price'] += $order->subtotal_price;
                        $data['sum_delivery'] += $order->delivery_cost;
                        $data['sum_total'] += $order->total_price;
                        array_push($orders, $order);
                    }
                }
            }
        }
        $data['orders'] = $orders;
        $data['areas'] = Area::where('deleted', 0)->orderBy('id', 'desc')->get();
        $data['area'] = Area::findOrFail($request->area_id);
        return view('admin.orders' , ['data' => $data]);
    }

    // fetch order date range
    public function fetch_orders_date(Request $request) {
        $data['orders'] = MainOrder::whereBetween('created_at', array($request->from, $request->to))->get();
        $data['areas'] = Area::where('deleted', 0)->orderBy('id', 'desc')->get();
        $data['from'] = '';
        $data['to'] = '';
        if (isset($request->from)) {
            $data['from'] = $request->from;
            $data['to'] = $request->to;
        }
        $data['sum_price'] = MainOrder::whereBetween('created_at', array($request->from, $request->to))->sum('subtotal_price');
        $data['sum_delivery'] = MainOrder::whereBetween('created_at', array($request->from, $request->to))->sum('delivery_cost');
        $data['sum_total'] = MainOrder::whereBetween('created_at', array($request->from, $request->to))->sum('total_price');
        return view('admin.orders' , ['data' => $data]);
    }

    // fetch order payment method
    public function fetch_order_payment_method(Request $request) {
        $data['orders'] = MainOrder::where('payment_method', $request->method)->get();
        $data['areas'] = Area::where('deleted', 0)->orderBy('id', 'desc')->get();
        $data['sum_price'] = MainOrder::where('payment_method', $request->method)->sum('subtotal_price');
        $data['sum_delivery'] = MainOrder::where('payment_method', $request->method)->sum('delivery_cost');
        $data['sum_total'] = MainOrder::where('payment_method', $request->method)->sum('total_price');
        $data['method'] = $request->method;

        return view('admin.orders' , ['data' => $data]);
    }

    // fetch order by sub sorder number
    public function fetch_order_by_sub_order_number(Request $request) {
        $data['orders'] = MainOrder::whereHas('orders', function($q) use($request) {
            $q->where('order_number', 'like','%' . $request->sub_number . '%');
        })->get();
        $data['areas'] = Area::where('deleted', 0)->orderBy('id', 'desc')->get();
        $data['sum_price'] = MainOrder::whereHas('orders', function($q) use($request) {
            $q->where('order_number', 'like','%' . $request->sub_number . '%');
        })->sum('subtotal_price');
        $data['sum_delivery'] = MainOrder::whereHas('orders', function($q) use($request) {
            $q->where('order_number', 'like','%' . $request->sub_number . '%');
        })->sum('delivery_cost');
        $data['sum_total'] = MainOrder::whereHas('orders', function($q) use($request) {
            $q->where('order_number', 'like','%' . $request->sub_number . '%');
        })->sum('total_price');

        return view('admin.orders' , ['data' => $data]);
    }

    // fetch order by user phone
    public function fetch_order_by_user_phone(Request $request) {
        $data['orders'] = MainOrder::whereHas('user', function($q) use($request) {
            $q->where('phone', 'like','%' . $request->user_phone . '%');
        })->get();
        $data['areas'] = Area::where('deleted', 0)->orderBy('id', 'desc')->get();
        $data['sum_price'] = MainOrder::whereHas('user', function($q) use($request) {
            $q->where('phone', 'like','%' . $request->user_phone . '%');
        })->sum('subtotal_price');
        $data['sum_delivery'] = MainOrder::whereHas('user', function($q) use($request) {
            $q->where('phone', 'like','%' . $request->user_phone . '%');
        })->sum('delivery_cost');
        $data['sum_total'] = MainOrder::whereHas('user', function($q) use($request) {
            $q->where('phone', 'like','%' . $request->user_phone . '%');
        })->sum('total_price');

        return view('admin.orders' , ['data' => $data]);
    }

    // get invoice
    public function getInvoice(MainOrder $order) {
        $data['order'] = $order;
        $data['m_option'] = MultiOption::find(8);

        return view('admin.invoice', ['data' => $data]);
    }

    // order size details
    public function order_size_details(OrderItem $item) {
        $data['size'] = $item->size;

        return view('admin.size_details', ['data' => $data]);
    }
}