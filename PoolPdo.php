<?php 

require "AbstractPool.php";


class PoolPdo extends AbstractPool
{

	// 数据库配置
	protected $config = [
		'host'		=> 'mysql:host=127.0.0.1:3306;dbname=art',
		'port'		=> '3306',
		'user'		=> 'root',
		'password'  => '',
		'chartset'  => 'utf8',
		'timeout'   => 2,
	];

	// 静态单利
	public static $instance;

	/**
	 * 获取静态单利
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new PoolPdo();
		}

		return self::$instance;
	}

	/**
	 * 创建db
	 */
	public function createDb()
	{
		return new PDO($this->config['host'], $this->config['user'], $this->config['password']);
	}

}

$httpServer = new swoole_http_server('0.0.0.0', 9501);

$httpServer->set(
	['worker_num' => 1]
);

$httpServer->on('WorkerStart', function() {
	echo "pool init";
	PoolPdo::getInstance()->init();
});

$httpServer->on('request', function($request, $response) {
	$db = null;
	$obj = PoolPdo::getInstance()->getConnection();
	if (!empty($obj)) {
		$db = $obj ? $obj['db'] : null;
	}
	if ($db) {
		$db->query('select sleep(2)');
		PoolPdo::getInstance()->free($obj);
		echo "query success";
		$response->end(json_encode('return success'));
	} else {
		echo "query faild";
		$response->end(json_encode('return error'));
	}
});


$httpServer->start();






