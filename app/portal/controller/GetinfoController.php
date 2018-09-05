<?php
/*
 * 获取信息
 * By: Sublime
 * Author:Jie
 * Datatime: 2018/3/28 16:39
 **/
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use think\Db;

class GetinfoController extends HomeBaseController
{
    //获取用户信息
    public function getUserinfo()
    {
        $code = $this->request->param('code','');
        $uid = $this->request->param('uid','');
        $user_nickname = $this->request->param('user_nickname','');
        $avatar = $this->request->param('avatar','');
        if(empty($code) and empty($uid)){
            return json(['code'=>400,'msg'=>'缺少必要参数：CODE，UID其中一个','data'=>'']);exit();
        }
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.\think\Config::get('WX_APPID').'&secret='.\think\Config::get('WX_APP_SECRET').'&js_code='.$code.'&grant_type=authorization_code';
 
        $ret = file_get_contents($url);
        $row = json_decode($ret,true);
        if(!empty($row['openid'])){
            $openid = $row['openid'];
        }
        
        
        if(!empty($uid)){
            $openid = Db::name('user')->where(array('id'=>$uid))->value('openid');
        }

        $info = Db::name('user')->where(array('openid'=>$openid))->find();
        if(empty($info) and empty($user_nickname) and empty($avatar)){
            //没有注册并且没有获取到用户头像和昵称
            $data = array(
                'user_nickname' => "",
                'avatar' => "",
                'openid' => $openid,
                'create_time' => time(),
            );
            $res = Db::name('user')->insertGetId($data);
            if($res){
                $userinfo = array(
                    'uid' => $res,
                    'if_member' => false
                );
                return json(['code'=>200,'msg'=>'只通过openid注册成功！','data'=>$userinfo]);exit();
            } 
        }elseif(empty($info) and !empty($user_nickname) and !empty($avatar)){
            //没有注册获取到用户头像和昵称
            $data = array(
                'user_nickname' => $user_nickname,
                'avatar' => $avatar,
                'openid' => $openid,
                'create_time' => time(),
            );
            $res = Db::name('user')->insertGetId($data);
            if($res){
                $userinfo = array(
                    'uid' => $res,
                    'if_member' => false
                );
                return json(['code'=>200,'msg'=>'信息完整注册成功！','data'=>$userinfo]);exit();
            } 
        }elseif(!empty($info) and !empty($user_nickname) and !empty($avatar)){
            if($info['user_nickname'] == "" and $info['avatar']==""){
                //已注册更新用户头像和昵称
                $data = array(
                    'user_nickname' => $user_nickname,
                    'avatar' => $avatar,
                );
                Db::name('user')->where(array('openid'=>$openid))->update($data);
            }

            
            $res = Db::name('user')->where(array('openid'=>$openid))->find();
            if($res){
                // return json(['code'=>200,'msg'=>'更新用户头像昵称成功！','data'=> $info['member_time']]);exit();
                if(time() > $info['member_time']){
                    $if_member = false;
                }else{
                    $if_member = true;
                }
                $userinfo = array(
                    'uid' => $info['id'],
                    'if_member' => $if_member
                );
                return json(['code'=>200,'msg'=>'更新用户头像昵称成功！','data'=>$userinfo]);exit();


            }else{
                if(time() > $info['member_time']){
                    $if_member = false;
                }else{
                    $if_member = true;
                }
                $userinfo = array(
                    'uid' => $info['id'],
                    'if_member' => $if_member
                );
                return json(['code'=>400,'msg'=>'已更新，只需传code即可','data'=>$userinfo]);exit();
            } 
        }else{
            if(time() > $info['member_time']){
                $if_member = false;
            }else{
                $if_member = true;
            }
            $userinfo = array(
                    'uid' => $info['id'],
                    'if_member' => $if_member
            );
            return json(['code'=>200,'msg'=>'获取用户信息成功！','data'=>$userinfo]);exit();
        }
        
    }


    //获取新人礼券
    public function getNewCoin()
    {
        $uid = $this->request->param('uid','','intval');
        if(empty($uid)){
            return json(['code'=>400,'msg'=>'缺少必要参数：UID','data'=>'']);exit();
        }
        $coin_status = Db::name('user')->where(array('id'=>$uid))->value('coin_status');

        if($coin_status==1){
            return json(['code'=>400,'msg'=>'已领取过新人礼券！','data'=>'']);exit();
        }

        $res = Db::name('user')->where(array('id'=>$uid))->data(array('coin'=>10,'coin_status'=>1))->update();
        if($res){
            return json(['code'=>200,'msg'=>'成功领取十礼券！','data'=>'']);exit();
        }else{
            return json(['code'=>400,'msg'=>'SQL错误','data'=>'']);exit();
        }
    }


    //获取个人信息
    public function getUserMessage()
    {
        $uid = $this->request->param('uid','','intval');
        if(empty($uid)){
            return json(['code'=>400,'msg'=>'缺少必要参数：UID','data'=>'']);exit();
        }
        $info = Db::name('user')->field('id,user_nickname,avatar,mobile,coin_status,create_time')->find($uid);

        if($info){
            return json(['code'=>200,'msg'=>'获取成功！','data'=>$info]);exit();
        }else{
            return json(['code'=>400,'msg'=>'获取失败！','data'=>'']);exit();
        }
    }

    //获取小贴士
    public function getXiaoTieShi()
    {
        $where['parent_id'] = array('neq',8);
        $list = Db::name('portal_post')
        ->field('id,post_title')
        ->where($where)
        ->select()
        ->toArray();
        if(empty($list)){
            return json(['code'=>400,'msg'=>'没有内容！','data'=>'']);exit();
        }
        return json(['code'=>200,'msg'=>'获取成功！','data'=>$list]);exit();
    }

    //获取小贴士详情
    public function getXiaoTieShiDetail()
    {
        $id = $this->request->param('id','');
        $info = Db::name('portal_post')->field('post_content')->find($id);
        if(empty($id)){
            return json(['code'=>400,'msg'=>'没有内容！','data'=>'']);exit();
        }
        return json(['code'=>200,'msg'=>'获取成功！','data'=>$info]);exit();
    }

    //获取温馨提示
    public function getWenXinTiShi()
    {
        $where['post_type'] = array('eq',8);
        $info = Db::name('portal_post')->field('post_content')->find(36);
        if(empty($info)){
            return json(['code'=>400,'msg'=>'没有内容！','data'=>'']);exit();
        }
        return json(['code'=>200,'msg'=>'获取成功！','data'=>$info]);exit();
    }  

    //获取年卡价格
    public function getCarPrice()
    {
        $price = Db::name('somedata')->where(array('id'=>1))->value('price');
        if(empty($price)){
            return json(['code'=>400,'msg'=>'没有设置价格！','data'=>'']);exit();
        }
         return json(['code'=>200,'msg'=>'获取成功！','data'=>$price]);exit();
    }
 
}
