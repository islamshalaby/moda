@extends('admin.app')
@php
    $page_title = App::isLocale('en') ? __('messages.show_delivery_costs') . $data['area']['title_en'] : __('messages.show_delivery_costs') . $data['area']['title_ar'];
@endphp

@section('title' , $page_title)

@section('content')
    <div id="tableSimple" class="col-lg-12 col-12 layout-spacing">
        <div class="statbox widget box box-shadow">
            <div class="widget-header">
            <div class="row">
                <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                    <h4>{{ $page_title }}</h4>
                    <a class="btn btn-primary" href="{{ route('areas.add.delivercost', $data['area']['id']) }}">{{ __('messages.add') }}</a>
                </div>
            </div>
        </div>
        <div class="widget-content widget-content-area">
            <div class="table-responsive"> 
                <table id="without-print" class="table table-hover non-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>{{ __('messages.store') }}</th>
                            <th>{{ __('messages.delivery_cost') }}</th>
                            @if(Auth::user()->update_data) 
                                <th class="text-center">{{ __('messages.edit') }}</th>                          
                            @endif
                            {{-- @if(Auth::user()->delete_data) 
                                <th class="text-center">{{ __('messages.delete') }}</th>                          
                            @endif --}}
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; ?>
                        @foreach ($data['costs'] as $cost)
                            <tr>
                                <td><?=$i;?></td>
                                <td>{{ $cost->store->name }}</td>
                                <td>{{ $cost->delivery_cost . " " . __('messages.dinar') }}</td>
                                @if(Auth::user()->update_data) 
                                    <td class="text-center blue-color" ><a href="{{ route('edit_delivery_cost_get', [$cost->area->id, $cost->id]) }}" ><i class="far fa-edit"></i></a></td>
                                @endif
                                {{-- @if(Auth::user()->delete_data) 
                                    <td class="text-center blue-color" ><a onclick='return confirm("{{ __('messages.are_you_sure') }}");' href="{{ route('areas.delete', $area->id) }}" ><i class="far fa-trash-alt"></i></a></td>
                                @endif                                 --}}
                                <?php $i++; ?>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        {{-- <div class="paginating-container pagination-solid">
            <ul class="pagination">
                <li class="prev"><a href="{{$data['categories']->previousPageUrl()}}">Prev</a></li>
                @for($i = 1 ; $i <= $data['categories']->lastPage(); $i++ )
                    <li class="{{ $data['categories']->currentPage() == $i ? "active" : '' }}"><a href="/admin-panel/categories/show?page={{$i}}">{{$i}}</a></li>               
                @endfor
                <li class="next"><a href="{{$data['categories']->nextPageUrl()}}">Next</a></li>
            </ul>
        </div>   --}}
        
    </div>  

@endsection