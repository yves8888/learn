<?php
/**
 * Created by PhpStorm.
 * User: brady
 * Date: 2017/12/6
 * Time: 14:42
 *
 * amqp协议操作类，可以访问rabbitMQ
 * 需先安装php_amqp扩展
 *
 */

class MQ
{
    //配置
    public  $configs = array();

    //交换机名称
    public $exchange_name = '';

    //队列名称
    public  $queue_name = '';

    //路由名称  注意 如果用同一个路由绑定交换机，当推送的时候，会同时推送到这几个key上   $q = new AMQPQueue($channel);  $q->setName('queue3'); $q->setName('queue2'); $q->bind('exchange',$routingkey);
    public $route_key = '';

    //是否持久化 默认true
    public $durable = true;

    /*
     * 是否自动删除
     * exchange is deleted when all queues have finished using it
     * queue is deleted when last consumer unsubscribes
     *
     */
    public $auto_delete = false;

    //镜像队列，打开后消息会在节点之间复制，有master和slave的概念
    public $mirror = false;


    //连接
    private $_conn = NULL;
    //交换机对象
    private $_exchange = NULL;
    //信道对象
    private $_channel = NULL;
    //队列对象
    private $_queue = NULL;

    /**
     * MQ constructor.
     * @configs array('host'=>$host,'port'=>5672,'username'=>$username,'password'=>$password,'vhost'=>'/')
     */
    public function __construct($configs=array(),$exchange_name='',$queue_name='',$route_key='')
    {
        $this->exchange_name = $exchange_name;
        $this->queue_name = $queue_name;
        $this->route_key = $route_key;

        $this->set_configs($configs);
    }

    /**
     * @desc  配置设置
     * @param $configs
     */
    public function set_configs($configs)
    {
        if(empty($configs) || !is_array($configs)){
            throw new Exception("your config is not array");
        }

        if(empty($configs['host']) || empty($configs['username']) || empty($configs['password'])) {
            throw new Exception("your config is error");
        }

        if(empty($configs['vhost'])){
            $configs['vhost'] = '/';
        }

        if(empty($configs['port'])){
            $configs['port'] = '5672';
        }

        $configs['login'] = $configs['username'];
        unset($configs['username']);

        $this->configs = $configs;

    }

    /**
     * 设置是否持久化
     * @param $durable
     */
    public function set_durable($durable)
    {
        $this->durable = $durable;
    }

    /**
     * 设置是否自动删除
     * @param $auto_delete boolean
     */
    public function set_auto_delete($auto_delete)
    {
        $this->auto_delete = $auto_delete;
    }

    /**
     * 设置是否镜像
     * @param $mirror
     */
    public function set_mirror($mirror)
    {
        $this->mirror = $mirror;
    }

    /**
     * 连接初始化
     */
    public function init()
    {
        //没有连接对象，进行连接 有不管  就不用每次都连接和初始化
        if(!$this->_conn){
            $this->_conn = new AMQPConnection($this->configs);
            $this->_conn->connect();
            $this->init_exchange_queue_route();
        }
    }

    /**
     * 初始化 交换机 队列名 路由
     */
    public function init_exchange_queue_route()
    {
        if(empty($this->exchange_name) ||  empty($this->queue_name) || empty($this->route_key)){
            throw new Exception("rabbitMQ  exchage_name or queue_name or route_key is empty, please check is",'500');
        }

        //channel
        $this->_channel = new AMQPChannel($this->_conn);//创建channel

        //exchange
        $this->_exchange = new AMQPExchange($this->_channel);//创建交换机
        $this->_exchange->setName($this->exchange_name);//设置交换机名字
        $this->_exchange->setType(AMQP_EX_TYPE_DIRECT);//交换机方式为direct
        if($this->durable) {
            $this->_exchange->setFlags(AMQP_DURABLE);//是否持久化
        }
        if($this->auto_delete){
            $this->_exchange->setFlags(AMQP_AUTODELETE);//是否自动删除
        }
        $this->_exchange->declareExchange();//申请交换机

        //queue
        $this->_queue = new AMQPQueue($this->_channel);
        $this->_queue->setName($this->queue_name);
        if($this->durable){
            $this->_queue->setFlags(AMQP_DURABLE);
        }
        if($this->auto_delete){
            $this->_queue->setFlags(AMQP_AUTODELETE);
        }
        if($this->mirror){
            $this->_queue->setArgument('x-ha-policy','all');
        }
        $this->_queue->declareQueue();//申请queue

        //绑定交换机
        $this->_queue->bind($this->exchange_name,$this->route_key);
    }

    /*
    * rabbitmq连接不变
    * 重置交换机，队列，路由等配置
    */
    public function reset($exchange_name,$queue_name,$route_key)
    {
        $this->exchange_name = $exchange_name;
        $this->queue_name = $queue_name;
        $this->route_key = $route_key;
        $this->init();
    }

    //没啥用
    public function __sleep() {
        $this->close();
        return array_keys(get_object_vars($this));
    }

    //关闭连接
    public function close()
    {
        if($this->_conn){
            $this->_conn->disconnect();
        }
    }

    //断开连接
    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        $this->close();
    }

    //生产消息
    public function send($msg)
    {
        $this->init();
        if(!$this->_conn){
            throw new Exception("connect RabbitMQ failed when send message");
        }

        if(is_array($msg)) {
            $msg = json_encode($msg);
        } else {
            $msg = trim(strval($msg));
        }

        return $this->_exchange->publish($msg,$this->route_key);
    }



    //消费消息 自动应答模式
    public function run_auto($funcation_name,$auto_ack = true)
    {
        $this->init();
        if(!$funcation_name || !$this->_queue) {
            throw new Exception("auto ack lose function_name or this->_queue");
        }
        while(true){
            if($auto_ack){
                $this->_queue->consume($funcation_name,AMQP_AUTOACK);
            } else {
                $this->_queue->consume($funcation_name);
            }
        }

    }

    //手动应答模式
    public function run_manual()
    {
        $this->init();
        //$data = $this->_queue->get(AMQP_AUTOACK); //如果有传参数，自动应答
        $data = $this->_queue->get(); //如果有传参数，自动应答
        if($data){ //不为false 肯定是有对象的
           return array('queue_obj'=>$this->_queue,'content_boj'=>$data);
        }else {
            return false;
        }

    }



}