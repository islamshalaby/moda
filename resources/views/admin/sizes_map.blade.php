@extends('admin.app')

@section('title' , __('messages.sizes_map'))

@section('content')

<div class="col-lg-12 col-12 layout-spacing">
        <div class="statbox widget box box-shadow">
        <div class="widget-header">
            <div class="row">
                <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                    <h4>{{ __('messages.sizes_map') }}</h4>
             </div>
        </div>

        @if (session('status'))
            <div class="alert alert-danger mb-4" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">x</button>
                <strong>Error!</strong> {{ session('status') }} </button>
            </div> 
        @endif

        <form action="" method="post" enctype="multipart/form-data" >
            @csrf
            <div class="form-group mb-4 english-direction">
                <label for="sizes_map">{{ __('messages.sizes_map') }}</label>
                <textarea id="editor-ck-en" required name="sizes_map" class="form-control" rows="5">{{ $data['setting']['sizes_map'] }}</textarea>
            </div>               
            <input type="submit" value="{{ __('messages.submit') }}" class="btn btn-primary">
        </form>

</div>

@endsection