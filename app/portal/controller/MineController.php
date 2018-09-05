<?php
/*
 * 我的
 * By: Sublime
 * Author:Jie
 * Datatime: 2018/4/04 15:39
 **/
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use think\Db;

class MineController extends HomeBaseController
{
    //获取会员剩余时间
    public function getMemberTime()
    {
        $uid = $this->request->param('uid','','intval');
        if(empty($uid)){
            return json(['code'=>400,'msg'=>'缺少必要参数：UID','data'=>'']);
        }
        $member_time = Db::name('user')->where(array('id'=>$uid))->value('member_time');
        $last_time = $member_time - time();
        $last_time = intval($last_time/86400);
        if($last_time < 0){
            return json(['code'=>200,'msg'=>'会员已过期！','data'=>0]);
        }
        return json(['code'=>200,'msg'=>'获取成功，单位（天）！','data'=>$last_time]);
    }


    //修改昵称
    public function editNickname()
    {
        $uid = $this->request->param('uid','','intval');
        $user_nickname = $this->request->param('user_nickname');
        if(empty($uid)){
            return json(['code'=>400,'msg'=>'缺少必要参数：UID','data'=>'']);
        }
        if(empty($user_nickname)){
            return json(['code'=>400,'msg'=>'昵称不能为空！','data'=>'']);
        }
        $res = Db::name('user')->where(array('id'=>$uid))->update(array('user_nickname'=>$user_nickname));
        if($res){
            return json(['code'=>200,'msg'=>'修改成功！','data'=>$user_nickname]);
        }else{
            return json(['code'=>400,'msg'=>'修改失败！','data'=>'']);
        }
    }

    //修改手机号
    public function editMobile()
    {
        $uid = $this->request->param('uid','','intval');
        $mobile = $this->request->param('mobile');
        if(empty($uid)){
            return json(['code'=>400,'msg'=>'缺少必要参数：UID','data'=>'']);
        }
        if(empty($mobile)){
            return json(['code'=>400,'msg'=>'手机号不能为空！','data'=>'']);
        }
        $res = Db::name('user')->where(array('id'=>$uid))->update(array('mobile'=>$mobile));
        if($res){
            return json(['code'=>200,'msg'=>'修改成功！','data'=>'']);
        }else{
            return json(['code'=>400,'msg'=>'修改失败！','data'=>'']);
        }
    }

    

    //小程序图片上传
    public function uploadPic(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('avatar');
        $uid = $this->request->param('uid','','intval');
        if(empty($uid)){
            return json(['code'=>400,'msg'=>'缺少必要参数：UID！','data'=>'']);
        }
        if(empty($file)){
            return json(['code'=>400,'msg'=>'上传文件不能为空！','data'=>'']);
        }
        // 移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'upload');
            if($info){
                $savename = $info->getSaveName();
                $savename = cmf_get_image_url($savename);

                $res = Db::name('user')->where(array('id'=>$uid))->update(array('avatar'=>$savename));
                if($res){
                    return json(['code'=>200,'msg'=>'编辑成功！','data'=>$savename]);
                }else{
                   return json(['code'=>400,'msg'=>'编辑失败！','data'=>'']); 
                }
            
            }else{
                // 上传失败获取错误信息
                $error = $file->getError();
                return json(['code'=>400,'msg'=>'上传失败！','data'=>'']);
            }
        }
    }


}
