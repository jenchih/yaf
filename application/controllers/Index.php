<?php
use think\Db;
class IndexController extends \Yaf\Controller_Abstract {
	public function init()
	{
		// print_r(get_defined_constants());die;
	}

	public function indexAction()
	{
		$data = Db::table('user')->find();
		echo json_encode(['name'=>'leslie']);
		return false;
		// $this->getView()->assign("foo", "bar");
		// $this->getView()->display();
	}

	public function testviewAction()
	{
		echo 2222;
		//默认自动渲染视图，可以设置，或者关闭，若开启，又不想渲染视图输出，则return false
		return false;
	}
}
