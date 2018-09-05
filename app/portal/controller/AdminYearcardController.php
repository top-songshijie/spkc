<?php
/*
 * 年卡部分
 * By: Sublime
 * Author: Jie 
 * Datetime: 2018/4/19 15:18 
 **/
namespace app\portal\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class AdminYearcardController extends AdminBaseController
{
	public function index()
	{
		$info = Db::name('somedata')->find(1);
		// dump($info);
		$this->assign('info',$info);
		return $this->fetch();
	}

	public function edit_post()
	{
		$id = $this->request->param('id','','intval');
		$price = $this->request->param('price','');
		if(empty($id)){
			$this->error('缺少必要参数：ID');
		}
		$data = array(
			'price' => $price,
			'updatetime' => time(),
			'id' => $id
		);
		$res =Db::name('somedata')->update($data);
		if($res){
			$this->success('修改成功！');
		}else{
			$this->error('修改失败！');
		}
	}


}