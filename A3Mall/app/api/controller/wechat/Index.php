<?php
// +----------------------------------------------------------------------
// | A3Mall
// +----------------------------------------------------------------------
// | Copyright (c) 2020 http://www.a3-mall.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: xzncit <158373108@qq.com>
// +----------------------------------------------------------------------
namespace app\api\controller\wechat;

use mall\basic\Sms;
use mall\library\wechat\chat\WeChat;
use mall\library\wechat\chat\WeChatMessage;
use mall\library\wechat\chat\WeChatPush;
use think\facade\Db;

class Index {

    public function index(){
        try{
            $wechat = new WeChatPush();
            $receive = $wechat->strToLower($wechat->getReceive());

            $WeChatMessage = new WeChatMessage($wechat);
            if (method_exists($WeChatMessage, ($method = $receive['msgtype']))) {
                return $WeChatMessage->$method();
            }
        }catch(\Exception $e){
            return $e->getMessage();
        }

        return 'success';
    }

    public function notify(){
        try{
            $data = WeChat::Payment()->getNotify();
            $order = Db::name("order")->where("order_no",$data["out_trade_no"])->find();
            if(!empty($order)){
                Db::name("order")->where('id',$order["id"])->update([
                    "pay_time"=>time(),
                    "trade_no"=>$data["transaction_id"]
                ]);

                Sms::send(
                    ["mobile"=>$order["mobile"],"order_no"=>$order["order_no"]],
                    "payment_success"
                );
            }
        }catch (\Exception $e){}
        return WeChat::Payment()->getNotifySuccessReply();
    }

    public function config(){
        try{
            $sign = WeChat::Script()->getJsSign();
        }catch(\Exception $e){
            return json([
                "info"=>$e->getMessage(),
                "status"=>0
            ]);
        }

        return json([
            "info"=>"ok",
            "status"=>1,
            "data"=>$sign
        ]);
    }
}
