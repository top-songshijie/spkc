<?php
/*
 * 订单相关
 * By: Sublime
 * Author:Jie
 * Datatime: 2018/4/02 08:36
 **/
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use think\Db;

class OrderController extends HomeBaseController
{
    //获取是否有新人优惠
    public function ifNewUser()
    {
    	$id = $this->request->param('uid','','intval');
    	if(empty($id)){
    		return json(['code'=>400,'msg'=>'缺少必要参数：ID！','data'=>'']);exit();
    	}
        $coin = Db::name('user')->where(array('id'=>$id))->value('coin');
        if(empty($coin) || $coin!=10){
        	return json(['code'=>200,'msg'=>'这个人已经没有新人礼券了','data'=>'']);
        }else{
        	return json(['code'=>200,'msg'=>'这个用户是新人，有新人礼券','data'=>$coin]);
        }

    }
    


    //下订单
    public function putOrder()
    {
    
    	$goods_id = $this->request->param('goods_id','','intval');
    	$uid = $this->request->param('uid','','intval');
    	$if_exist = Db::name('order')->where(array('uid'=>$uid,'goods_id'=>$goods_id))->find();
    	if($if_exist){
    		return json(['code'=>200,'msg'=>'此课程已下单成功！','data'=>$if_exist['id']]);exit();
    	}
    	if(empty($goods_id)){
    		return json(['code'=>400,'msg'=>'缺少必要参数：GOODS_ID！','data'=>'']);exit();
    	}
    	if(empty($uid)){
    		return json(['code'=>400,'msg'=>'缺少必要参数：UID！','data'=>'']);exit();
    	}
    	$info = Db::name('portal_goods')->where(array('id'=>$goods_id))->find();
    	if(empty($info)){
    		return json(['code'=>400,'msg'=>'没有查询到此课程！','data'=>'']);exit();
    	}
    	$data['class_name'] = $info['post_title'];
    	$data['class_price'] = $info['post_source'];
    	$data['uid'] = $uid;
    	$data['order_sn'] = date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    	$data['create_time'] = time();
    	$data['goods_id'] = $goods_id;
    	$data['status'] = 0;
    	$res = Db::name('order')->insertGetId($data);
    	if($res){
    		return json(['code'=>200,'msg'=>'添加订单成功,返回订单id！','data'=>$res]);exit();
    	}else{
    		return json(['code'=>400,'msg'=>'下单失败！','data'=>'']);exit();
    	}
    }

    //购买年卡下订单
    public function maiYearCar()
    {
        $uid = $this->request->param('uid','','intval');
        if(empty($uid)){
            return json(['code'=>400,'msg'=>'缺少必要参数：ID','data'=>'']);exit();
        }
        $price = Db::name('somedata')->where(array('id'=>1))->value('price');
        if(empty($price)){
            return json(['code'=>400,'msg'=>'没有设置价格！','data'=>'']);exit();
        }
        $data = array(
            'uid' => $uid,
            'createtime' => date('Y-m-d H:i:s',time()),
            'status' => 0,
            'order_sn' => date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8),
            'price' => $price
        );
        $res = Db::name('member_order')->insertGetId($data);
        if($res){
            return json(['code'=>200,'msg'=>'年卡下单成功！','data'=>$res]);exit();
        }else{
            return json(['code'=>400,'msg'=>'年卡下单失败！','data'=>'']);exit();
        }
    }


    //获取支付参数
    public function getPaydata()
    {
        $id = $this->request->param('id','','intval');//订单id
        $ordertype = $this->request->param('orderType','','intval');//1课程 2会员
        
        if(empty($id)){
        	return json(['code'=>400,'msg'=>'缺少必要参数：ID','data'=>'']);exit();
        }
        if(empty($ordertype)){
            return json(['code'=>400,'msg'=>'缺少必要参数：ORDERTYPE','data'=>'']);exit();
        }
        if($ordertype == 1){
            $uid = Db::name('order')->where(array('id'=>$id))->value('uid');
            $coin = Db::name('user')->where(array('id'=>$uid))->value('coin');
            if(empty($coin) || $coin!=10){
                $coin = 0;
            }else{
                $coin = 10;
            }
        // dump($coin);exit();
            $info = Db::name('order')->where(array('id'=>$id))->find();
            if(empty($info)){
                return json(['code'=>400,'msg'=>'未查询到订单信息！','data'=>'']);
            }
            $order_sn = $info['order_sn'];
            $price = $info['class_price'] - $coin;
            if($price<0){
                $price = $info['class_price'];
                $coin = 0;
            }

        }else{
            $uid = Db::name('member_order')->where(array('id'=>$id))->value('uid');
            $coin = Db::name('user')->where(array('id'=>$uid))->value('coin');
            if(empty($coin) || $coin!=10){
                $coin = 0;
            }else{
                $coin = 10;
            }
            // dump($coin);exit();
            $info = Db::name('member_order')->where(array('id'=>$id))->find();
            if(empty($info)){
                return json(['code'=>400,'msg'=>'未查询到订单信息！','data'=>'']);
            }
            $order_sn = $info['order_sn'];
            // dump($info['price']);
            // dump($coin);
            $price = $info['price'] - $coin;
            $price = sprintf("%.2f",$price);
            // dump($price);exit();
            if($price<0){
                $price = $info['price'];
                $coin = 0;
            }
        }
// dump($price);exit();
        // return json(['code'=>400,'msg'=>'价格不可理喻','data'=>$price]);

        // $price = 0;
        $openid = Db::name('user')->where(array('id'=>$info['uid']))->value('openid');
        if(empty($openid)){
            return json(['code'=>400,'msg'=>'未能获取openid','data'=>'']);exit();
        }

        include VENDOR_PATH.'WxpayAPI/WeixinPay.php';
        $appid = \think\Config::get('WX_APPID');
        $mch_id = \think\Config::get('MCH_ID');
        $key = \think\Config::get('KEY');
        $out_trade_no = $info['order_sn'];
        $body = '视频课程小程序';
        $total_fee = $price*100;

        // echo $appid;echo "<br />";
        // echo $openid;echo "<br />";
        // echo $mch_id;echo "<br />";
        // echo $key;echo "<br />";
        // echo $out_trade_no;echo "<br />";
        // echo $body;echo "<br />";
        // echo $total_fee;echo "<br />";
        // exit();

        $weixinpay = new \WeixinPay($appid,$openid,$mch_id,$key,$out_trade_no,$body,$total_fee);
        $return = $weixinpay->pay();
        return json(['code'=>200,'msg'=>'获取数据成功！','data'=>$return,'if_use'=>$coin]);
    }

    // //支付回调
    // public function notify()
    // {
    //     //接收微信参数
    //     $postXml = $GLOBALS["HTTP_RAW_POST_DATA"];
    //     if (empty($postXml)) {
    //         return false;
    //     }
    //     //将xml格式转换成数组
    //     libxml_disable_entity_loader(true);
    //     $xmlstring = simplexml_load_string($postXml, 'SimpleXMLElement', LIBXML_NOCDATA);
    //     $val = json_decode(json_encode($xmlstring), true);
    //     // F('redddd',$val);
    //     $order_sn = $val['out_trade_no'];
    //     $data = array(
    //         'status' => 1,
    //         'pay_time' => time()
    //     );
    //     $res = Db::name('order')->where(array('order_sn'=>$order_sn))->data($data)->update();
    //     if($res){
    //     	return json(['code'=>200,'msg'=>'支付成功！','data'=>'']);
    //     }else{
    //         return json(['code'=>400,'msg'=>'支付失败！','data'=>'']);
    //     }
    // }

    //课程订单回调
    public function classNotify()
    {
        //订单id
        $id = $this->request->param('id','','intval');
        $status = Db::name('order')->where(array('id'=>$id))->value('status');
        if($status == 1){
            return json(['code'=>400,'msg'=>'该订单已被支付！','data'=>'']);exit();
        }
        $if_use = $this->request->param('if_use','');
        if($if_use == "10"){
            $uid = Db::name('order')->where(array('id'=>$id))->value('uid');
            $resxh = Db::name('user')->where(array('id'=>$uid))->data(array('coin'=>0))->update();
        }
        if(empty($id)){
            return json(['code'=>400,'msg'=>'缺少必要参数：ID','data'=>'']);exit();
        }
        $data =  array(
            'status' => 1,
            'pay_time' => time()
        );
        $res = Db::name('order')->where(array('id'=>$id))->data($data)->update();
        if($res){
            return json(['code'=>200,'msg'=>'支付成功！','data'=>'']);exit(); 
        }else{
            return json(['code'=>400,'msg'=>'状态修改失败！','data'=>'']);exit();
        }
    }

    //年卡订单回调
    public function carNotify()
    {
        //订单id
        $id = $this->request->param('id','','intval');
        $if_use = $this->request->param('if_use','');
        $status = Db::name('member_order')->where(array('id'=>$id))->value('status');
        if($if_use == "10"){
            $uid = Db::name('member_order')->where(array('id'=>$id))->value('uid');
            $resxh = Db::name('user')->where(array('id'=>$uid))->data(array('coin'=>0))->update();
        }
        $uid = Db::name('member_order')->where(array('id'=>$id))->value('uid');
        if($status == 1){
            return json(['code'=>400,'msg'=>'该订单已被支付！','data'=>'']);exit();
        }
        $uid = Db::name('member_order')->where(array('id'=>$id))->value('uid');

        $member_time = Db::name('user')->where(array('id'=>$uid))->value('member_time');

        if(empty($member_time) or $member_time<time()){
            //没有会员或者会员已过期
            $nowtime = time();
            $oneyear = 365*86400;
            $data = array('member_time'=>$nowtime+$oneyear);
            $res = Db::name('user')->where(array('id'=>$uid))->data($data)->update();

            $data2 = array(
                'paytime' => time(),
                'status' => 1
            );
            $res2 = Db::name('member_order')->where(array('id'=>$id))->data($data2)->update();
            if($res and $res2){
                return json(['code'=>200,'msg'=>'充值成功！','data'=>'']);exit(); 
            }else{
                return json(['code'=>400,'msg'=>'状态修改失败！','data'=>'']);exit();
            }
        }else{
            //现在是会员，续费操作
            $oneyear = 365*86400;
            $data = array('member_time'=>$member_time+$oneyear);
            $res = Db::name('user')->where(array('id'=>$uid))->data($data)->update();
            $data2 = array(
                'paytime' => time(),
                'status' => 1
            );
            $res2 = Db::name('member_order')->where(array('id'=>$id))->data($data2)->update();
            if($res and $res2){
                return json(['code'=>200,'msg'=>'续费成功！','data'=>'']);exit(); 
            }else{
                return json(['code'=>400,'msg'=>'状态修改失败！','data'=>'']);exit();
            }
        }
    }
 
}
