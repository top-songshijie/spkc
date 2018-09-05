<?php
/*
 * 关于课程
 * By: Sublime
 * Author:Jie
 * Datatime: 2018/4/02 08:36
 **/
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use think\Db;

class ClassController extends HomeBaseController
{
    //获取分类
    public function getCate()
    {
        $list = Db::name('portal_cate')->field('id,name,more')->order('list_order desc')->select()->toArray();
        foreach ($list as $k => $v) {
            $list[$k]['more'] = json_decode($v['more'],true);
            $list[$k]['more'] = $list[$k]['more']['thumbnail'];
            $list[$k]['more'] = cmf_get_image_url($list[$k]['more']);
        }
        if(empty($list)){
            return json(['code'=>400,'msg'=>'没有查到分类！','data'=>'']);
        }
        return json(['code'=>200,'msg'=>'已查到分类！','data'=>$list]);
    }

    //获取精品课程列表
    public function getJpClassList()
    {
        $list = Db::name('portal_goods')
        ->field('id,recommended,more,post_title,post_hits,post_source')
        ->where(array('recommended'=>1))
        ->select()->toArray();
        
        if(empty($list)){
            return json(['code'=>400,'msg'=>'没有查到精品课程！','data'=>'']);
        }
        foreach ($list as $key => $value) {
            $list[$key]['more'] = json_decode($list[$key]['more'],true);
            $list[$key]['more'] = cmf_get_image_url($list[$key]['more']['thumbnail']);
        }
        return json(['code'=>200,'msg'=>'已查到精品课程！','data'=>$list]);
    }

    //获取轮播图
    public function getLunboImgs()
    {
        $list = Db::name('slide_item')
        ->field('slide_id,image,url')
        ->where(array('slide_id'=>1))
        ->select()->toArray();       
        foreach ($list as $key => $value) {
            $list[$key]['image'] = cmf_get_image_url($list[$key]['image']);
        }
        if(empty($list)){
            return json(['code'=>400,'msg'=>'没有上传轮播图！','data'=>'']);
        }
        return json(['code'=>200,'msg'=>'成功获取到轮播图！','data'=>$list]);
    }


    //获取课程详情
    public function getDetail()
    {
        $id = $this->request->param('goods_id','','intval');
        $uid = $this->request->param('uid','','intval');
        if(empty($uid)){
            return json(['code'=>400,'msg'=>'缺少必要参数：UID','data'=>'']);
        }
        if(empty($id)){
            return json(['code'=>400,'msg'=>'缺少必要参数：GOODS_ID！','data'=>'']);
        }
        $if_mai = Db::name('order')->where(array('uid'=>$uid,'goods_id'=>$id,'status'=>1))->find();
        if($if_mai){
            $if_mai = 1;
        }else{
            $if_mai = 0;
        }
        $info = Db::name('portal_goods')
        ->field('id,more,post_title,post_source,post_hits,post_content')
        ->where(array('id'=>$id))
        ->find();
        if(empty($info)){
            return json(['code'=>400,'msg'=>'没有查询到此题目！','data'=>'']);
        }
        $info['more'] = json_decode($info['more'],true); 
        $info['photos'] = $info['more']['photos']; 
        foreach ($info['photos'] as $key => $value) {
            $info['photo'][$key] = $value['url'];
            $info['photo'][$key] = cmf_get_image_url($info['photo'][$key]);
        }
        
        $info['files'] = $info['more']['files'][0]['url'];
        $info['files2'] = $info['more']['files'][1]['url'];
        $info['files'] = cmf_get_image_url($info['files']);
        $info['files2'] = cmf_get_image_url($info['files2']); 
        unset($info['more']); 
        unset($info['photos']); 
        
        //开始查询此课程的评论
        $comment = Db::name('comment')
        ->alias('c')
        ->field('c.user_id,c.create_time,c.content,c.goods_id,u.id,u.user_nickname,u.avatar')
        ->join('__USER__ u','u.id=c.user_id')
        ->where(array('c.goods_id'=>$id))
        ->order('c.id desc')
        ->select()->toArray();
        if(!empty($comment)){
            foreach ($comment as $k => $v) {
                $comment[$k]['create_time'] = date('Y-m-d',$v['create_time']);
            }
            $info['comment'] = $comment; 
        }else{
            $info['comment'] = null;
        }
        // dump($if_mai);exit;
        if($if_mai==1){
            $info['if_mai'] = true;
        }else{
            $info['if_mai'] = false;
        }
        return json(['code'=>200,'msg'=>'成功查询到题目详情以及评论！','data'=>$info]);
    }



    //评论课程
    public function writeComment()
    {
        //用户id
        $user_id = $this->request->param('uid','','intval');
        //课程id
        $goods_id = $this->request->param('goods_id','','intval');
        //评论内容
        $content = $this->request->param('content','');
        if(empty($user_id)){
            return json(['code'=>400,'msg'=>'缺少必要参数：UID！','data'=>'']);
        }
        if(empty($goods_id)){
            return json(['code'=>400,'msg'=>'缺少必要参数：GOODS_ID！','data'=>'']);
        }
        if(empty($content)){
            return json(['code'=>400,'msg'=>'评论内容不能为空！','data'=>'']);
        }
        $data = array(
            'user_id' => $user_id,
            'goods_id' => $goods_id,
            'content' => $content,
            'create_time' => time()
        );
        $res = Db::name('comment')->insert($data);
        $res2 = Db::name('portal_goods')->where(array('id'=>$goods_id))->setInc('post_hits',1);
        if($res){
            return json(['code'=>200,'msg'=>'添加成功！','data'=>'']);
        }else{
            return json(['code'=>400,'msg'=>'添加失败！','data'=>'']);
        }
    }

    //记录我听过的课程
    public function recordListened()
    {
        $uid = $this->request->param('uid','','intval');
        $goods_id = $this->request->param('goods_id','','intval');
        $if_exist = Db::name('listened')->where(array('uid'=>$uid,'goods_id'=>$goods_id))->find();
        if(!empty($if_exist)){
            $res = Db::name('listened')->where(array('id'=>$if_exist['id']))->data(array('create_time'=>time()))->update();
            if($res){
                return json(['code'=>200,'msg'=>'更新记录成功！','data'=>'']);
            }else{
                return json(['code'=>400,'msg'=>'更新失败！','data'=>'']);
            }
        }
        if(empty($uid)){
            return json(['code'=>400,'msg'=>'缺少必要参数：UID','data'=>'']);
        }
        if(empty($goods_id)){
            return json(['code'=>400,'msg'=>'缺少必要参数：GOODS_ID！','data'=>'']);
        }
        $data = array(
            'uid' => $uid,
            'goods_id' => $goods_id,
            'create_time' => time(),
        );
        $res = Db::name('listened')->insert($data);
        if($res){
            return json(['code'=>200,'msg'=>'添加记录成功！','data'=>'']);
        }else{
            return json(['code'=>400,'msg'=>'添加失败！！','data'=>'']);
        }
    }


    //获取我的听课记录
    public function listenList()
    {
        $uid = $this->request->param('uid','','intval');
        if(empty($uid)){
            return json(['code'=>400,'msg'=>'缺少必要参数：UID','data'=>'']);exit();
        }
        $list = Db::name('listened')
        ->alias('l')
        ->field('l.uid,l.goods_id,p.post_title,p.more,p.post_source,post_hits')
        ->join('__PORTAL_GOODS__ p','l.goods_id=p.id')
        ->where(array('uid'=>$uid))->select()->toArray();
        foreach ($list as $k => $v) {
            $list[$k]['more'] = json_decode($list[$k]['more'],true);
            $list[$k]['more'] = cmf_get_image_url($list[$k]['more']['thumbnail']);
        }
        return json(['code'=>200,'msg'=>'获取成功！','data'=>$list]);
    }

    //根据分类获取课程列表
    public function getClassList()
    {
        //分类id
        $id = $this->request->param('id','','intval');
        if(empty($id)){
            return json(['code'=>400,'msg'=>'缺少必要参数：ID','data'=>'']);exit();
        }
        $post_id = Db::name('portal_cate_goods')->where(array('category_id'=>$id))->column('post_id');
        $where['id'] = array('in',$post_id);
        $list = Db::name('portal_goods')->where($where)->select()->toArray();
        if(empty($list)){
            return json(['code'=>400,'msg'=>'此分类下没有课程！','data'=>'']);exit();
        }
        foreach ($list as $key => $value) {
            $list[$key]['more'] = json_decode($list[$key]['more'],true);
            $list[$key]['more'] = cmf_get_image_url($list[$key]['more']['thumbnail']);
        }
        return json(['code'=>200,'msg'=>'获取成功！','data'=>$list]);
    }

    //搜索
    // public function searchList()
    // {
    //     $param = $this->request->param();

    //     if($param['kind']==0){
    //         $order = "pg.id desc";
    //     }
    //     if($param['kind']==1){
    //         $order = "pg.post_hits desc";
    //     }

    //     if(!empty($param['keywords'])){
    //         $keywords = $param['keywords'];
    //         $where2['name'] = array('like',"%$keywords%");
    //         //分类id
    //         $id = Db::name('portal_cate')->where($where2)->value('id');

    //         if(empty($id)){
    //             $where['pg.post_title'] = array('like',"%$keywords%");
    //             $list = Db::name('portal_goods')
    //             ->alias('pg')
    //             ->order($order)
    //             ->where($where)
    //             ->select()
    //             ->toArray();

    //         }else{
    //             $join = [
    //                 ['__PORTAL_CATE_GOODS__ pcg', 'pcg.post_id = pg.id'],
    //             ];
    //             $where['pcg.category_id'] = $id;
    //             $list = Db::name('portal_goods')
    //             ->alias('pg')
    //             ->order($order)
    //             ->where($where)
    //             ->join($join)
    //             ->select()
    //             ->toArray();

    //         }
            
    //     }else{
    //         $list = Db::name('portal_goods')
    //             ->alias('pg')
    //             ->order($order)
    //             ->select()
    //             ->toArray();
                
    //     }
        
    //     foreach ($list as $key => $value) {
    //         $list[$key]['more'] = json_decode($list[$key]['more'],true);
    //         $list[$key]['more'] = cmf_get_image_url($list[$key]['more']['thumbnail']);
    //     }
    //     return json(['code'=>200,'msg'=>'ok！','data'=>$list]);exit();
        
    // }

    //搜索
    public function searchList()
    {
        //分类id
        $cid = $this->request->param('id','','intval');
        $join = [
                    ['__PORTAL_CATE_GOODS__ pcg', 'pcg.post_id = pg.id'],
                ];
        //关键词
        $keywords = $this->request->param('keywords','');
        $where2['pcg.category_id'] = $cid;
        if(!empty($keywords)){
            $where['pg.post_title'] = array('like',"%$keywords%");
        }else{
            $where = "";
        }
        //0最新，1最热
        $kind = $this->request->param('kind','0','intval');
        if($kind=="0"){
            $order = "pg.id desc";
        }
        if($kind=="1"){
            $order = "pg.post_hits desc";
        }
        $list = Db::name('portal_goods')
                ->alias('pg')
                ->join($join)
                ->where($where)
                ->where($where2)
                ->order($order)
                ->select()
                ->toArray();
        // echo Db()->getLastSql();
        // dump($list);
        foreach ($list as $key => $value) {
            $list[$key]['more'] = json_decode($list[$key]['more'],true);
            $list[$key]['more'] = cmf_get_image_url($list[$key]['more']['thumbnail']);
        }
        return json(['code'=>200,'msg'=>'ok！','data'=>$list]);exit();
    }


    public function sendMail()
    {
        $mail = $this->request->param('mail','2490795520@qq.com');
        $class_id =  $this->request->param('class_id','36');
        if(empty($mail)){
            return json(['code'=>400,'msg'=>'缺少必要参数：MAIL','data'=>'']);exit();
        }
        if(empty($class_id)){
            return json(['code'=>400,'msg'=>'缺少必要参数：CLASS_ID','data'=>'']);exit();
        }
        $more = Db('portal_goods')->where(array('id'=>$class_id))->value('more');
        $more = json_decode($more,true);
        $file = cmf_get_image_url($more['files'][0]['url']);
        // dump($file);exit(); 
        $result = cmf_send_email($mail, "视频资料", $file);
        if ($result && empty($result['error'])) {
            return json(['code'=>200,'msg'=>'发送成功！','data'=>'']);exit();
        } else {
            return json(['code'=>400,'msg'=>'发送失败！','data'=>'']);exit();
        }
    }

    //获取客服电话
    public function getPhone()
    {
        $info = Db::name('qqbb')->field('phone')->find(1);
        return json(['code'=>200,'msg'=>'这是客服电话！','data'=>$info]);exit();
    }
 
}
