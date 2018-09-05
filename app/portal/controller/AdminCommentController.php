<?php
/*
 * 评论部分
 * By: Sublime
 * Author: Ssj
 * Datetime: 2018/3/15 17:14
 **/
namespace app\portal\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class AdminCommentController extends AdminBaseController
{
   public function index()
   {
        $id = $this->request->param('id','', 'intval');
        $list = Db::name('comment')
        ->alias('c')
        ->field('c.id,c.user_id,c.content,c.goods_id,u.user_nickname,u.avatar')
        ->join('__USER__ u','c.user_id = u.id','left')
        ->where('c.goods_id',$id)
        ->select();
 
        $this->assign('list', $list);
        return $this->fetch();
   }

   //删除
   public function delete()
   {
        $id = $this->request->param('id','','intval');
        $res = Db::name('comment')->delete($id);
        if($res){
            $this->success('删除成功！');
        }else{
            $this->error('网络拥挤，请稍后重试！');
        }
   }
}
