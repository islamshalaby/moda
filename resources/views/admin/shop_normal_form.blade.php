@extends('admin.app')

@section('title' , __('messages.add_new_shop'))

@section('content')
    <div class="col-lg-12 col-12 layout-spacing">
        <div class="statbox widget box box-shadow">
            <div class="widget-header">
                <div class="row">
                    <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                        <h4>{{ __('messages.add_new_shop') }}</h4>
                 </div>
        </div>
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="list-unstyled mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form action="" method="post" enctype="multipart/form-data" >
            @csrf
            <div class="custom-file-container" data-upload-id="myFirstImage">
                <label>{{ __('messages.upload') }} ({{ __('messages.logo') }}) <a href="javascript:void(0)" class="custom-file-container__image-clear" title="Clear Image">x</a></label>
                <label class="custom-file-container__custom-file" >
                    <input type="file" required name="logo" class="custom-file-container__custom-file__custom-file-input" accept="image/*">
                    <input type="hidden" name="MAX_FILE_SIZE" value="10485760" />
                    <span class="custom-file-container__custom-file__custom-file-control"></span>
                </label>
                <div class="custom-file-container__image-preview"></div>
            </div>     
            <div class="custom-file-container" data-upload-id="mySecondImage">
                <label>{{ __('messages.upload') }} ({{ __('messages.cover') }}) <a href="javascript:void(0)" class="custom-file-container__image-clear" title="Clear Image">x</a> ( 349x133 )</label>
                <label class="custom-file-container__custom-file" >
                    <input type="file" name="cover" class="custom-file-container__custom-file__custom-file-input" accept="image/*">
                    <input type="hidden" name="MAX_FILE_SIZE" value="10485760" />
                    <span class="custom-file-container__custom-file__custom-file-control"></span>
                </label>
                <div class="custom-file-container__image-preview"></div>
            </div>   
            <div class="custom-file-container" data-upload-id="myThirdImage">
                <label>{{ __('messages.upload') }} ({{ __('messages.front_image') }}) <a href="javascript:void(0)" class="custom-file-container__image-clear" title="Clear Image">x</a></label>
                <label class="custom-file-container__custom-file" >
                    <input type="file" name="front_image" class="custom-file-container__custom-file__custom-file-input" accept="image/*">
                    <input type="hidden" name="MAX_FILE_SIZE" value="10485760" />
                    <span class="custom-file-container__custom-file__custom-file-control"></span>
                </label>
                <div class="custom-file-container__image-preview"></div>
            </div>  
            <div class="custom-file-container" data-upload-id="myFourthImage">
                <label>{{ __('messages.upload') }} ({{ __('messages.front_image') }}) <a href="javascript:void(0)" class="custom-file-container__image-clear" title="Clear Image">x</a></label>
                <label class="custom-file-container__custom-file" >
                    <input type="file" name="back_image" class="custom-file-container__custom-file__custom-file-input" accept="image/*">
                    <input type="hidden" name="MAX_FILE_SIZE" value="10485760" />
                    <span class="custom-file-container__custom-file__custom-file-control"></span>
                </label>
                <div class="custom-file-container__image-preview"></div>
            </div>  
            <div class="form-group mb-4">
                <label for="owner_name">{{ __('messages.owner_name') }}</label>
                <input required type="text" name="owner_name" class="form-control" id="owner_name" placeholder="{{ __('messages.owner_name') }}" value="" >
            </div>  
            <div class="form-group mb-4">
                <label for="shop_name">{{ __('messages.shop_name') }}</label>
                <input required type="text" name="shop_name" class="form-control" id="shop_name" placeholder="{{ __('messages.shop_name') }}" value="" >
            </div> 
            <div class="form-group mb-4">
                <label for="id_number">{{ __('messages.id_number') }}</label>
                <input required type="number" name="id_number" class="form-control" id="id_number" placeholder="{{ __('messages.id_number') }}" value="" >
            </div> 
            <div class="form-group mb-4">
                <label for="bank_name">{{ __('messages.bank_name') }}</label>
                <input required type="text" name="bank_name" class="form-control" id="bank_name" placeholder="{{ __('messages.bank_name') }}" value="" >
            </div> 
            <div class="form-group mb-4">
                <label for="account_number">{{ __('messages.account_number') }}</label>
                <input required type="text" name="account_number" class="form-control" id="account_number" placeholder="{{ __('messages.account_number') }}" value="" >
            </div> 
            <div class="form-group mb-4">
                <label for="phone">{{ __('messages.phone') }}</label>
                <input required type="text" name="phone" class="form-control" id="phone" placeholder="{{ __('messages.phone') }}" value="" >
            </div>
            
            <div class="form-group mb-4">
                <label for="email">{{ __('messages.email') }}</label>
                <input required type="text" name="email" class="form-control" id="email" placeholder="{{ __('messages.email') }}" value="" >
            </div>
            <div class="form-group mb-4">
                <label for="password">{{ __('messages.password') }}</label>
                <input required type="password" class="form-control" id="password" name="password" placeholder="{{ __('messages.password') }}" value="" >
            </div>
            <div class="form-group mb-4">
                <label for="instegram">{{ __('messages.instegram') }}</label>
                <input type="text" name="instagram" class="form-control" id="instegram" placeholder="{{ __('messages.instegram') }}" value="" >
            </div>
            <div class="form-group mb-4 arabic-direction">
                <label for="details">{{ __('messages.details') }}</label>
                <textarea id="details" name="details"  class="form-control"  rows="5"></textarea>
            </div>  
            
            <input type="submit" value="{{ __('messages.submit') }}" class="btn btn-primary">
        </form>
    </div>
@endsection