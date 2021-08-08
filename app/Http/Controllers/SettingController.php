<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\APIHelpers;
use JD\Cloudder\Facades\Cloudder;
use App\Setting;
use App\Seller;


class SettingController extends Controller
{
	public function __construct()
    {
        $this->middleware('auth:api' , ['except' => ['joinRequest']]);
    }
    public function getappnumber(Request $request){
        $setting = Setting::select('phone')->find(1);
        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $setting['phone'] , $request->lang);
        return response()->json($response , 200);
    }

    public function getwhatsapp(Request $request){
        $setting = Setting::select('app_phone')->find(1);
        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $setting['app_phone'] , $request->lang);
        return response()->json($response , 200);
    }

    // seller join request
    public function joinRequest(Request $request) {
        $post = $request->all();
        $validator = Validator::make($post, [
            'name' => 'required',
            'shop' => 'required',
            'phone' => 'required|unique:sellers,phone',
            'id_number' => 'required|unique:sellers,id_number',
            'instagram' => 'required|unique:sellers,instagram',
            'account_number' => 'required|unique:sellers,account_number',
            'front_image' => 'required',
            'back_image' => 'required'
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 , $validator->messages()->first() , $validator->messages()->first() , null , $request->lang);
            return response()->json($response , 406);
        }
        // dd();
        $image = $request->front_image;  // your base64 encoded
        $image = str_replace('data:image/png;base64,', '', $image);
        $image = str_replace(' ', '+', $image);
        // dd($image);
        $front_image = $request->front_image;
        Cloudder::upload($front_image, null);
        // $front_image_name = $front_image->getRealPath();
        $front_imageereturned = Cloudder::getResult();
        $front_image_id = $front_imageereturned['public_id'];
        $front_image_format = $front_imageereturned['format'];    
        $front_image_new_name = $front_image_id.'.'.$front_image_format;
        $post['front_image'] = $front_image_new_name;

        $back_image = $request->back_image;
        Cloudder::upload($back_image, null);
        $iback_imagereturned = Cloudder::getResult();
        $iback_image_id = $iback_imagereturned['public_id'];
        $back_image_format = $iback_imagereturned['format'];    
        $back_image_new_name = $iback_image_id.'.'.$back_image_format;
        $post['back_image'] = $back_image_new_name;
        Seller::create($post);

        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , null , $request->lang);
        return response()->json($response , 200);
    }

    // social media links
    public function social_media(Request $request) {
        $data = Setting::select('instegram', 'twitter', 'snap_chat')->find(1);
        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $data , $request->lang);
        return response()->json($response , 200);
    }
}