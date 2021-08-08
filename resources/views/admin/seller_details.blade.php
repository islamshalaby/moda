@extends('admin.app')

@section('title' , __('messages.request_details'))

@section('content')

        <div id="tableSimple" class="col-lg-12 col-12 layout-spacing">
        <div class="statbox widget box box-shadow">
            <div class="widget-header">
            <div class="row">
                <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                    <h4>{{ __('messages.request_details') }}</h4>
                    @if($data['exist'] == false)
                    <a class="btn btn-primary" href="{{ route('shops.add') . "?seller=" . $data['seller']['id'] }}"> {{ __('messages.create_store') }} </a>
                    @endif
                </div>
            </div>
        </div>
        <div class="widget-content widget-content-area">
            <div class="table-responsive"> 
                <table class="table table-bordered mb-4">
                    <tbody>
                        <tr>
                            <td class="label-table" > {{ __('messages.name') }}</td>
                            <td>{{ $data['seller']['name'] }}</td>
                        </tr>
                        <tr>
                            <td class="label-table" > {{ __('messages.phone') }}</td>
                            <td>{{ $data['seller']['phone'] }}</td>
                        </tr>
                        <tr>
                            <td class="label-table" > {{ __('messages.email') }}</td>
                            <td>{{ $data['seller']['email'] }}</td>
                        </tr>
                        <tr>
                            <td class="label-table" > {{ __('messages.shop_name') }}</td>
                            <td>{{ $data['seller']['shop'] }}</td>
                        </tr> 
                        <tr>
                            <td class="label-table" > {{ __('messages.id_number') }}</td>
                            <td>{{ $data['seller']['id_number'] }}</td>
                        </tr> 
                        <tr>
                            <td class="label-table" > {{ __('messages.instagram') }}</td>
                            <td>{{ $data['seller']['instagram'] }}</td>
                        </tr> 
                        <tr>
                            <td class="label-table" > {{ __('messages.bank_name') }}</td>
                            <td>{{ $data['seller']['bank_name'] }}</td>
                        </tr> 

                        <tr>
                            <td class="label-table" > {{ __('messages.account_number') }}</td>
                            <td>{{ $data['seller']['account_number'] }}</td>
                        </tr> 
                        <tr>
                            <td class="label-table" > {{ __('messages.front_image') }}</td>
                            <td>
                                <img src="https://res.cloudinary.com/dk1fceelj/image/upload/w_100,q_100/v1581928924/{{ $data['seller']['front_image'] }}"  />
                            </td>
                        </tr> 
                        <tr>
                            <td class="label-table" > {{ __('messages.back_image') }}</td>
                            <td>
                                <img src="https://res.cloudinary.com/dk1fceelj/image/upload/w_100,q_100/v1581928924/{{ $data['seller']['back_image'] }}"  />
                            </td>
                        </tr> 
                        <tr>
                            <td class="label-table" > {{ __('messages.additional_information') }}</td>
                            <td>
                                {{ $data['seller']['details'] }}
                            </td>
                        </tr> 
                        <tr>
                            <td class="label-table" > {{ __('messages.date') }}</td>
                            <td>{{ $data['seller']['created_at'] }}</td>
                        </tr> 
                    </tbody>
                </table>
            </div>
        </div>
    </div>  

@endsection



