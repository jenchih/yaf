<?php
use \think\Db;
class IndexController extends \Yaf\Controller_Abstract
{
	public function init()
	{
		// print_r(get_defined_constants());die;
	}

	public function indexAction()
	{
		// $data = UserModel::get(['uid'=>64])->toArray();
		$data = Db::table('zs_user')->find();
		echo json_encode($data);
		// $this->getView()->assign("foo", "bar");
		// $this->getView()->display('index/index.html');
	}

	public function testviewAction()
	{
		echo 2222;
		//默认关闭渲染视图，可以设置自动，或者，若开启，又不想渲染视图输出，则return false
		return false;
	}
}
