<?php
/*
 * 会员部分
 * By: Sublime
 * Author: Jie 
 * Datetime: 2018/3/16 9:20 
 **/
namespace app\portal\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class AdminUserController extends AdminBaseController
{
	public function index()
	{
		$keyword = $this->request->param('keyword','');
		if(!empty($keyword)){
            $where['user_nickname'] = ['like', "%$keyword%"];
            // $where['mobile']    = ['like', "%$keyword%"];
		}
		$map['id'] = array('neq',1);
		$list = Db::name('user')
		->field('id,user_nickname,avatar,member_time,mobile')
		// ->whereOr($where)
		// ->where($where)
		->order('id desc')
		->where($map)
		->paginate(20);
		// echo Db()->getLastSql();
		$page = $list->render();
		$this->assign('list',$list);
		$this->assign('page', $page);
		return $this->fetch();
	}

	//删除
	public function delete()
	{
		$id = $this->request->param('id','','intval');
		$res = Db::name('user')->delete($id);
		if($res){
			$this->success('删除成功！');
		}else{
			$this->error('网络拥挤，请稍后重试！');
		}
	}

	//编辑
	public function edit()
	{
		$id = $this->request->param('id','','intval');
		$info = Db::name('user')
		->field('id,member_time')
		->where(array('id'=>$id))
		->find();
		if(!empty($info['member_time'])){
			$info['member_time'] = date('Y-m-d H:i',$info['member_time']);
		}
		
		$this->assign('info',$info);
		return $this->fetch();
	}

	//编辑提交
	public function editPost()
	{
		$post = $this->request->param();
		$post['member_time'] = strtotime($post['member_time']);
		$res = Db::name('user')->update($post);
		if($res){
			$this->success('操作成功！');
		}else{
			$this->error('网络拥挤，请稍后重试！');
		}
	}
}