<?php
/**
 * Created by PhpStorm.
 * User: xuxiaodao
 * Date: 2017/11/4
 * Time: 下午1:21
 */

namespace App\Http\Controllers\Auth;


use App\Http\Controllers\Controller;
use App\Http\Logic\Token;
use App\Http\Logic\UserLogic;
use App\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use League\Flysystem\Exception;

class TokenController extends Controller
{

    /**
     * 获取token
     *
     * @author yezi
     *
     * @return mixed
     * @throws Exception
     */
    public function createToken()
    {
        $openId = request()->input('open_id');
        $userInfo = request()->input('user_info');

        if (empty($openId) || empty($userInfo)){
            throw new Exception('用户信息不用为空');
        }

        try{
            DB::beginTransaction();

            $user = User::where(User::FIELD_ID_OPENID,$openId)->first();
            if(!$user){
                $userLogin = new UserLogic();
                $user = $userLogin->createWeChatUser($openId,$userInfo);
            }

            $tokenCreate = new Token();

            $token = $tokenCreate->getWecChatToken($user);

            DB::commit();

        }catch (Exception $e){
            DB::rollBack();
        }

        return $token;
    }

    public function getAccessToken()
    {

        $http = new Client;
        $response = $http->get('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env('WE_CHAT_APP_ID').'&secret='.env('WE_CHAT_SECRET'));

        $result = json_decode((string) $response->getBody(), true);

        $accessToken = $result['access_token'];

        $response = $http->get('https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$accessToken.'&openid=orTQA0YMb1nAZG95aoCwA4D8I7tI&lang=zh_CN');

        $userInfo = json_decode((string) $response->getBody(), true);

        return $userInfo;
    }

}