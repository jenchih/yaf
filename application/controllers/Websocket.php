<?php
/**
 *  websocket客户端调度方法控制器
 */
class WebsocketController extends \Yaf\Controller_Abstract {

	private $server;
	private $fd;
	private $clientData;

	public function init()
	{
		$req              = $this->getRequest();
		$params           = $req->getParams();
		$this->server     = $params['_server'];
		$this->fd         = $params['_fd'];
		// print_r(get_defined_constants());die;
	}


	public function indexAction()
	{
		$data = $this->getRequest()->getParam('name')?:'leslie';
		$this->server->push( $this->fd, $data );
	}
}
