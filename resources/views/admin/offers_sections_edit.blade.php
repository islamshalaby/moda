@extends('admin.app')

@section('title' , __('messages.edit_offers_sections'))
@push('scripts')
    <script>
        var ss = $(".tags").select2({
            tags: true,
        });
    </script>
@endpush

@section('content')
    <div class="col-lg-12 col-12 layout-spacing">
        <div class="statbox widget box box-shadow">
            <div class="widget-header">
                <div class="row">
                    <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                        <h4>{{ __('messages.edit_offers_sections') }}</h4>
                 </div>
        </div>
        <form action="" method="post" enctype="multipart/form-data" >
            @csrf
            <div class="form-group mb-4">
                <label for="">{{ __('messages.current_image') }}</label><br>
                <img src="https://res.cloudinary.com/dk1fceelj/image/upload/w_100,q_100/v1581928924/{{ $data['section']['icon'] }}"  />
            </div>
            <div class="custom-file-container" data-upload-id="myFirstImage">
                <label>{{ __('messages.upload') }} ({{ __('messages.single_image') }}) <a href="javascript:void(0)" class="custom-file-container__image-clear" title="Clear Image">x</a></label>
                <label class="custom-file-container__custom-file" >
                    <input type="file" name="icon" class="custom-file-container__custom-file__custom-file-input" accept="image/*">
                    <input type="hidden" name="MAX_FILE_SIZE" value="10485760" />
                    <span class="custom-file-container__custom-file__custom-file-control"></span>
                </label>
                <div class="custom-file-container__image-preview"></div>
            </div>            
            <div class="form-group mb-4">
                <label for="title_en">{{ __('messages.title_en') }}</label>
                <input required type="text" name="title_en" class="form-control" id="title_en" placeholder="{{ __('messages.title_en') }}" value="{{ $data['section']['title_en'] }}" >
            </div>
            <div class="form-group mb-4">
                <label for="title_ar">{{ __('messages.title_ar') }}</label>
                <input required type="text" name="title_ar" class="form-control" id="title_ar" placeholder="{{ __('messages.title_ar') }}" value="{{ $data['section']['title_ar'] }}" >
            </div>
            <div class="form-group mb-4">
                <label for="sort">{{ __('messages.sort') }}</label>
                <input required type="number" name="sort" class="form-control" id="sort" placeholder="{{ __('messages.sort') }}" value="{{ $data['section']['sort'] }}" >
            </div>
            <div class="form-group" >
                <div class="col-12" >
                    <label> {{ __('messages.products') }} </label>
                </div>
                <select id="categoriesList" name="products[]" class="form-control tags" multiple="multiple">
                    @foreach ($data['products'] as $product)
                    <option {{ in_array($product->id, $data['ids']) ? 'selected' : '' }} value="{{ $product->id }}">{{ App::isLocale('en') ? $product->title_en : $product->title_ar }}</option>
                    @endforeach
                </select>
            </div>
            
            <input type="submit" value="{{ __('messages.submit') }}" class="btn btn-primary">
        </form>
    </div>
@endsection