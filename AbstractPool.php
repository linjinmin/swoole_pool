<?php 

use Swoole\Coroutine\Channel;

abstract class AbstractPool 
{

	private $min; // 最小连接数
	private $max; // 最大连接数
	private $count; // 当前连接数
	private $pool; // 当前连接词组
	private $timeout; // 管道等待设置时间
	protected $spareTime; // 空闲连接回收

	// 是否初始化
	private $inited = false;

	// 创建连接
	protected abstract function createDB();

	/**
	 * 初始化
	 */
	public function __construct()
	{
		$this->min = 10;
		$this->max = 100;
		$this->spareTime = 10 * 3600;
		$this->timeout = 3;
		$this->pool = new Channel($this->max + 1);
	}

	// 创建对象
	protected function createObject()
	{
		$obj = null;
		$db  = $this->createDb();
		if ($db) {
			$obj = [
				'last_used_time' => time(),
				'db' => $db,
			];
		}

		return $obj;
	}

	/**
	 * 初始化最小数量的连接池
	 */
	public function init()
	{
		if ($this->inited) {
			return $this;
		}

		for($i=0; $i<$this->min; $i++) {
			$obj = $this->createObject();
			$this->count++;
			$this->pool->push($obj);
		}

		return $this;
	}

	/**
	 * 获取连接
	 */
	public function getConnection()
	{	
		$obj = null;
		if ($this->pool->isEmpty()) {
			// 如果连接池为空，则判断能否申请连接
			if ($this->count < $this->max) {
				$obj = $this->createObject();
				$this->count++;
			}

		} else {
			// 从管道中等待
			$obj = $this->pool->pop($this->timeout);
		}

		return $obj;
	}

	/**
	 * 释放连接
	 */
	public function free($obj)
	{
		$this->pool->push($obj);
	}

	/**
	 * 处理空闲链接
	 */
	public function gcSpareObject()
	{
		swoole_time_track(120000, function() {

			if ($this->pool->length() < intval($this->min * 0.5)) { 
				echo "暂不回收";
				return ;
			}

			while(true) {
				if ($this->pool->length() > $this->min) {
					$obj = $this->pool->pop(0.001);
					$this->count--;
				} else {
					break;
				}
			}

		});
	}

}







