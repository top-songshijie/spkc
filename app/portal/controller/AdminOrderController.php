<?php
/*
 * 充值会员订单部分
 * By: Sublime
 * Author: Jie 
 * Datetime: 2018/3/16 10:23 
 **/
namespace app\portal\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class AdminOrderController extends AdminBaseController
{
	public function index()
	{
		$id = $this->request->param('id','','intval');
		$list = Db::name('order')->where(array('uid'=>$id,'status'=>1))->paginate(20);
		$data=$list->items();
	
		foreach ($data as $k => $v) {
			$data[$k]['create_time'] = date('Y-m-d H:i',$v['create_time']);
			$data[$k]['pay_time'] = date('Y-m-d H:i',$v['pay_time']);
		}


		$page = $list->render();
		$this->assign('list',$data);
		$this->assign('page', $page);
		return $this->fetch();
	}

	//删除
	public function delete()
	{
		$id = $this->request->param('id','','intval');
		$res = Db::name('order')->delete($id);
		if($res){
			$this->success('删除成功!');
		}else{
			$this->error('网络拥挤，请稍后重试！');
		}
	}

}