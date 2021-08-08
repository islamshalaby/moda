<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Document</title>
    
</head>
<body dir="rtl">
    <div class="invoice-box" style="max-width: 800px;margin: auto;padding: 30px;border: 1px solid #eee;box-shadow: 0 0 10px rgba(0, 0, 0, .15);font-size: 16px;line-height: 24px;font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;color: #555;">
        <table cellpadding="0" cellspacing="0" style="width: 100%;line-height: inherit;text-align: left;">
            <tr class="top">
                <td colspan="7" style="padding: 5px;vertical-align: top;">
                    <table style="width: 100%;line-height: inherit;text-align: right;">
                        <tr>
                            <td class="title" style="padding: 5px;vertical-align: top;padding-bottom: 20px;font-size: 45px;line-height: 45px;color: #333;">
                                <img src="https://res.cloudinary.com/dk1fceelj/image/upload/h_200,w_200/v1581928924/{{ $setting['logo'] }}" style="width:100px; max-width:300px;">
                            </td>
                            
                            <td style="padding: 5px;vertical-align: top;text-align: left;padding-bottom: 20px;">
                                {{ __('messages.invoice') }} : {{ $main_order['main_order_number'] }}<br>
                                {{ __('messages.date') }}: {{ $main_order['created_at']->format("d-m-y") }}<br>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr class="information">
                <td colspan="7" style="padding: 5px;vertical-align: top;">
                    <table style="width: 100%;line-height: inherit;text-align: right;">
                        <tr>
                            <td style="padding: 5px;vertical-align: top;padding-bottom: 40px;">
                                <h4 style="margin-bottom: 50px;">{{ __('messages.customer_data') }}</h4><br>
                                {{ $main_order->user->name }}<br>
                                <a style="text-decoration: none" href="https://www.google.com/maps/?q={{ $main_order->address ? $main_order->address->latitude : '' }},{{ $main_order->address ? $main_order->address->longitude : '' }}" target="_blank"> {{ $main_order->address->area ? $main_order->address->area->title_en . ", " . __('messages.st') . " " . $main_order->address->street . ", " . __('messages.piece') . " " . $main_order->address->piece . ", " . __('messages.gaddah') . " " . $main_order->address->gaddah : '' }} <br/> {{ __('messages.home') . " " . $main_order->address->building . ', ' . __('messages.floor') . " "  . $main_order->address->floor . ', ' . __('messages.apartment') . " " . $main_order->address->apartment_number }}</a><br>
                                <br><br>
                                <h6>{{ __('messages.additional_details') }}</h6>
                                {{ $main_order->address->extra_details }}
                            </td>
                            
                            <td style="padding: 5px;vertical-align: top;text-align: left;padding-bottom: 40px;">
                                <h4 style="margin-bottom: 50px;"></h4><br>
                                {{ $main_order->user->phone }}<br>
                                {{ $main_order->user->email }}
                            </td>
                        </tr>
                        
                    </table>
                </td>
            </tr>
            
            
            @foreach ($main_order->orders as $order)
            <tr class="information">
                <td colspan="7" style="padding: 5px;vertical-align: top;">
                    <table style="width: 100%;line-height: inherit;text-align: right;">
                        <tr>
                            <td style="padding: 5px;padding-top:0;vertical-align: top;">
                                <h5>{{ $order->store->name }}</h5>
                            </td>
                            
                            <td style="padding: 5px;text-align: right;">
                                
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 5px;padding-top:0;padding-bottom: 20px;">
                                <h6>{{ __('messages.sub_order_number') }}</h6>
                            </td>
                            
                            <td style="padding: 5px;vertical-align: top;text-align: right;padding-bottom: 20px;">
                                {{ $order->order_number }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr class="heading">
                <td style="padding: 5px;vertical-align: top;text-align:center;background: #eee;border-bottom: 1px solid #ddd;font-weight: bold;">
                    S.No
                </td>
                <td style="padding: 5px;vertical-align: top;text-align:center;background: #eee;border-bottom: 1px solid #ddd;font-weight: bold;">
                    {{ __('messages.items') }}
                </td>
                <td style="padding: 5px;vertical-align: top;text-align:center;background: #eee;border-bottom: 1px solid #ddd;font-weight: bold;">
                    {{ __('messages.quantity') }}
                </td>
                <td style="padding: 5px;vertical-align: top;text-align:center;background: #eee;border-bottom: 1px solid #ddd;font-weight: bold;">
                  {{ __('messages.additional_details') }}
                </td>
                <td style="padding: 5px;vertical-align: top;text-align:center;background: #eee;border-bottom: 1px solid #ddd;font-weight: bold;">
                    {{ __('messages.price_before_discount') }}
                </td>
                <td style="padding: 5px;vertical-align: top;text-align:center;background: #eee;border-bottom: 1px solid #ddd;font-weight: bold;">
                    {{ __('messages.price_after_discount') }}
                </td>
                <td style="padding: 5px;vertical-align: top;text-align:center;background: #eee;border-bottom: 1px solid #ddd;font-weight: bold;">
                    {{ __('messages.total') }}
                </td>
            </tr>
            @foreach ($order->oItems as $item)
            <tr class="item">
                <td style="padding: 5px;vertical-align: top;text-align:center;border-bottom: 1px solid #eee;">
                    {{ $item->product->id }}
                </td>
                
                <td style="padding: 5px;vertical-align: top;text-align: center;border-bottom: 1px solid #eee;">
                    {{ App::isLocale('en') ? $item->product->title_en : $item->product->title_ar }}
                </td>
                <td style="padding: 5px;vertical-align: top;text-align: center;border-bottom: 1px solid #eee;">
                    {{ $item->count }}
                </td>
                <td style="padding: 5px;vertical-align: top;text-align: center;border-bottom: 1px solid #eee;">
                  {{ isset($item->size) ? $item->size->details : '' }}
                </td>
                <td style="padding: 5px;vertical-align: top;text-align: center;border-bottom: 1px solid #eee;">
                    {{ $item->price_before_offer }} {{ __('messages.dinar') }}
                </td>
                <td style="padding: 5px;vertical-align: top;text-align: center;border-bottom: 1px solid #eee;">
                    {{ $item->final_price }} {{ __('messages.dinar') }}
                </td>
                <td style="padding: 5px;vertical-align: top;text-align: center;border-bottom: 1px solid #eee;">
                    {{ (double)$item->final_price * (double)$item->count }} {{ __('messages.dinar') }}
                </td>
                
            </tr>
            @endforeach
            
            
            <tr class="heading">
                <td style="padding: 5px;text-align:center;vertical-align: top;background: #eee;border-bottom: 1px solid #ddd;font-weight: bold;">
                    {{ __('messages.total') }}
                </td>
                <td style="padding: 5px;text-align:center;vertical-align: top;background: #eee;border-bottom: 1px solid #ddd;font-weight: bold;">
                    
                </td>
                <td style="padding: 5px;text-align:center;vertical-align: top;background: #eee;border-bottom: 1px solid #ddd;font-weight: bold;">
                    
                </td>
                <td style="padding: 5px;vertical-align: top;text-align: center;background: #eee;border-bottom: 1px solid #ddd;font-weight: bold;">
                    {{ __('messages.delivery_cost') }}
                </td>
                <td style="padding: 5px;text-align:center;vertical-align: top;background: #eee;border-bottom: 1px solid #ddd;font-weight: bold;">
                    
                </td>
                <td style="padding: 5px;text-align:center;vertical-align: top;background: #eee;border-bottom: 1px solid #ddd;font-weight: bold;">
                    
                </td>
                <td style="padding: 5px;text-align:center;vertical-align: top;background: #eee;border-bottom: 1px solid #ddd;font-weight: bold;">
                    
                </td>
            </tr>
            
            <tr class="details">
                <td style="padding: 5px;vertical-align: top;text-align:center;padding-bottom: 20px;">
                    {{ $order->subtotal_price }} {{ __('messages.dinar') }}
                </td>

                <td style="padding: 5px;vertical-align: top;text-align:center;padding-bottom: 20px;">
                    
                </td>

                <td style="padding: 5px;vertical-align: top;text-align:center;padding-bottom: 20px;">
                    
                </td>
                
                <td style="padding: 5px;vertical-align: top;text-align: left;padding-bottom: 20px;">
                    {{ $order->delivery_cost }} {{ __('messages.dinar') }}
                </td>
                <td style="padding: 5px;vertical-align: top;text-align:center;padding-bottom: 20px;">
                    
                </td>

                <td style="padding: 5px;vertical-align: top;text-align:center;padding-bottom: 20px;">
                    
                </td>
                <td style="padding: 5px;vertical-align: top;text-align:center;padding-bottom: 20px;">
                    
                </td>
            </tr>
            @endforeach
            <tr class="item">
                <td style="padding: 5px;vertical-align: top;text-align:right;border-bottom: 1px solid #eee;">
                    <h5>{{ __('messages.payment_method') }}</h5>
                </td>
                
                <td style="padding: 5px;vertical-align: top;text-align: left;border-bottom: 1px solid #eee;">
                    <p class=" inv-subtitle">
                    @if($main_order->payment_method == 1)
                    {{ __('messages.key_net') }}
                    @else
                    {{ __('messages.cash') }}
                    @endif
                    </p>
                </td>
                <td style="padding: 5px;vertical-align: top;text-align:right;border-bottom: 0px solid #eee;">
                    
                </td>
                <td style="padding: 5px;vertical-align: top;text-align:right;border-bottom: 0px solid #eee;">
                    <h5>{{ __('messages.sub_total') }}</h5>
                </td>
                <td style="padding: 5px;vertical-align: top;text-align:right;border-bottom: 0px solid #eee;">
                    
                </td>
                <td style="padding: 5px;vertical-align: top;text-align:right;border-bottom: 0px solid #eee;">
                    
                </td>
                
                
                <td style="padding: 5px;vertical-align: top;text-align: right;border-bottom: 0px solid #eee;">
                    <p class="">{{ $main_order['subtotal_price'] }} {{ __('messages.dinar') }}</p>
                </td>
            </tr>
            <tr class="item">
                <td style="padding: 5px;vertical-align: top;text-align:right;border-bottom: 0px solid #eee;">
                    
                </td>
                
                <td style="padding: 5px;vertical-align: top;text-align: left;border-bottom: 0px solid #eee;">
                    
                </td>
                <td style="padding: 5px;vertical-align: top;text-align:right;border-bottom: 0px solid #eee;">
                    
                </td>
                <td style="padding: 5px;vertical-align: top;text-align:right;border-bottom: 0px solid #eee;">
                    <h5>{{ __('messages.delivery_cost') }}</h5>
                </td>
                <td style="padding: 5px;vertical-align: top;text-align:right;border-bottom: 0px solid #eee;">
                    
                </td>
                <td style="padding: 5px;vertical-align: top;text-align:right;border-bottom: 0px solid #eee;">
                    
                </td>
                
                
                <td style="padding: 5px;vertical-align: top;text-align: right;border-bottom: 0px solid #eee;">
                    <p class="">{{ $main_order['delivery_cost'] }} {{ __('messages.dinar') }}</p>
                </td>
            </tr>
            
            <tr class="total">
                <td style="padding: 5px;vertical-align: top;text-align:right;"></td>
                <td style="padding: 5px;vertical-align: top;text-align:right;"></td>
                <td style="padding: 5px;vertical-align: top;text-align:right;"></td>
                <td style="padding: 5px;vertical-align: top;text-align: right;border-top: 2px solid #eee;font-weight: bold;">
                    {{ __('messages.invoice_total') }}
                </td>
                <td style="padding: 5px;vertical-align: top;text-align:right;border-top: 2px solid #eee;"></td>
                <td style="padding: 5px;vertical-align: top;text-align:right;border-top: 2px solid #eee;"></td>
                <td style="padding: 5px;vertical-align: top;text-align: right;border-top: 2px solid #eee;font-weight: bold;">
                    {{ $main_order['total_price'] }} {{ __('messages.dinar') }}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>



