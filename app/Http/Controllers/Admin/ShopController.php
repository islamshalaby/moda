<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\Request;
use JD\Cloudder\Facades\Cloudder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Shop;
use App\Seller;

class ShopController extends AdminController{
    // get create shop
    public function AddGet(Request $request) {
        $seller_ids = Shop::pluck('seller_id')->toArray();
        $data['sellers'] = Seller::select('id', 'shop')->whereNotIn('id', $seller_ids)->get();
        if (isset($request->seller)) {
            $data['seller'] = $request->seller;
        }

        return view('admin.shop_form', ['data' => $data]);
    }

    // post create shop
    public function AddPost(Request $request) {
        $post = $request->all();
        $request->validate([
            // 'email' => 'required|unique:shops,email',
            'password' => 'required',
            'seller_id' => 'required|unique:shops,seller_id'
        ]);
        
        if($request->file('logo')){
            $image_name = $request->file('logo')->getRealPath();
            Cloudder::upload($image_name, null);
            $imagereturned = Cloudder::getResult();
            $image_id = $imagereturned['public_id'];
            $image_format = $imagereturned['format'];    
            $image_new_name = $image_id.'.'.$image_format;
            $post['logo'] = $image_new_name;
        }
        if($request->file('cover')){
            $cover_name = $request->file('cover')->getRealPath();
            Cloudder::upload($cover_name, null);
            $coverreturned = Cloudder::getResult();
            $cover_id = $coverreturned['public_id'];
            $cover_format = $coverreturned['format'];    
            $cover_new_name = $cover_id.'.'.$cover_format;
            $post['cover'] = $cover_new_name;
        }
        $seller = Seller::select('shop', 'email')->where('id', $request->seller_id)->first();
        // dd($seller);
        $post['email'] = $seller->email;
        $post['name'] = $seller['shop'];
        $post['logo'] = $image_new_name;
        $post['password'] = Hash::make($request->password);
        Shop::create($post);

        return redirect()->route('shops.index');
    }

    public function AddGetNormal(Request $request) {

        return view('admin.shop_normal_form');
    }

    public function AddPostNormal(Request $request) {
        $post = $request->all();
        $request->validate([
            'email' => 'required|unique:shops,email',
            'password' => 'required',
            'front_image' => 'required',
            'back_image' => 'required',
            'owner_name' => "required",
            'shop_name' => "required",
            'id_number' => 'required',
            'bank_name' => 'required',
            'account_number' => 'required',
            'phone' => 'required|unique:sellers,phone',
            'instagram' => 'unique:sellers,instagram'
        ]);
        if($request->file('front_image')){
            $cover_name = $request->file('front_image')->getRealPath();
            Cloudder::upload($cover_name, null);
            $coverreturned = Cloudder::getResult();
            $cover_id = $coverreturned['public_id'];
            $cover_format = $coverreturned['format'];    
            $cover_new_name = $cover_id.'.'.$cover_format;
            $post['front_image'] = $cover_new_name;
        }

        if($request->file('back_image')){
            $cover_name = $request->file('back_image')->getRealPath();
            Cloudder::upload($cover_name, null);
            $coverreturned = Cloudder::getResult();
            $cover_id = $coverreturned['public_id'];
            $cover_format = $coverreturned['format'];    
            $cover_new_name = $cover_id.'.'.$cover_format;
            $post['back_image'] = $cover_new_name;
        }
        $post['name'] = $post['owner_name'];
        $post['seen'] = 1;
        $seller = Seller::create($post);
        
        if($request->file('logo')){
            $image_name = $request->file('logo')->getRealPath();
            Cloudder::upload($image_name, null);
            $imagereturned = Cloudder::getResult();
            $image_id = $imagereturned['public_id'];
            $image_format = $imagereturned['format'];    
            $image_new_name = $image_id.'.'.$image_format;
            $post['logo'] = $image_new_name;
        }
        if($request->file('cover')){
            $cover_name = $request->file('cover')->getRealPath();
            Cloudder::upload($cover_name, null);
            $coverreturned = Cloudder::getResult();
            $cover_id = $coverreturned['public_id'];
            $cover_format = $coverreturned['format'];    
            $cover_new_name = $cover_id.'.'.$cover_format;
            $post['cover'] = $cover_new_name;
        }
        $post['name'] = $post['shop_name'];
        $post['logo'] = $image_new_name;
        $post['seller_id'] = $seller['id'];
        $post['password'] = Hash::make($request->password);
        Shop::create($post);

        return redirect()->route('shops.index');
    }

    // edit get
    public function EditGet(Shop $store) {
        $data['store'] = $store;

        return view('admin.shop_edit', ['data' => $data]);
    }

    // edit post
    public function EditPost(Request $request, Shop $store) {
        $post = $request->all();
        $request->validate([
            'email' => 'required|unique:shops,email,' . $store->id
        ]);

        if($request->file('logo')){
            $logo = $store->logo;
            $publicId = substr($logo, 0 ,strrpos($logo, "."));    
            Cloudder::delete($publicId);
            $image_name = $request->file('logo')->getRealPath();
            Cloudder::upload($image_name, null);
            $imagereturned = Cloudder::getResult();
            $image_id = $imagereturned['public_id'];
            $image_format = $imagereturned['format'];    
            $image_new_name = $image_id.'.'.$image_format;
            $post['logo'] = $image_new_name;
        }
        if($request->file('cover')){
            $cover = $store->cover;
            $publicId = substr($cover, 0 ,strrpos($cover, "."));    
            Cloudder::delete($publicId);
            $cover_name = $request->file('cover')->getRealPath();
            Cloudder::upload($cover_name, null);
            $coverreturned = Cloudder::getResult();
            $cover_id = $coverreturned['public_id'];
            $cover_format = $coverreturned['format'];    
            $cover_new_name = $cover_id.'.'.$cover_format;
            $post['cover'] = $cover_new_name;
        }

        if (isset($request->password) && !empty($request->password)) {
            $post['password'] = Hash::make($request->password);
        }else {
            $post['password'] = $store->password;
        }

        $store->update($post);

        return redirect()->route('shops.index');
    }

    // store details
    public function details(Shop $store) {
        $data['store'] = $store;

        return view('admin.shop_details', ['data' => $data]);
    }

    // action
    public function action(Shop $store, $status) {
        $store->update(['status' => $status]);

        return redirect()->back();
    }

    // show shops
    public function index() {
        $data['shops'] = Shop::get();

        return view('admin.shops', ['data' => $data]);
    }
}