<?php
/**
 * Created by PhpStorm.
 * User: 梁杰熙
 * Date: 2021/1/20
 * Time: 10:43
 */
namespace Mqtt\Client;

use Mqtt\protocol\Mqtt;

class Client
{

    protected $config;
    protected $client;
    protected $msgId=0;

    /*
     * 构造函数
     */
    public function __construct(array $config)
    {
        //拼接客户端配置
        $this->config=config('client');
        $this->config = array_replace_recursive($this->config, $config);
        //建立TCP连接
        $this->client = new \Swoole\Client(SWOOLE_SOCK_TCP|SWOOLE_KEEP);
        $this->client->set(array(
            'open_mqtt_protocol' => true,
        ));
        if (!$this->client->connect($this->config['host'], $this->config['port'],$this->config['time_out'])){
            //尝试重连
            $this->reConnect();
        }
    }

    /**
     * 连接.
     *
     * @param bool $clean 是否清除会话
     * @param array $will 遗嘱消息
     * @return mixed
     */
    public function connect(bool $clean = true, array $will = [])
    {
        $data = [
            'cmd' => Mqtt::CONNECT, // 1
            'protocol_name' => 'MQTT',
            'protocol_level' => 4,
            'clean_session' => $clean ? 0 : 1,
            'client_id' => $this->config['client_id'],
            'keepalive' => $this->config['keepalive'] ?? 0,
        ];
        if (isset($this->config['username'])) {
            $data['username'] = $this->config['username'];
        }
        if (isset($this->config['password'])) {
            $data['password'] = $this->config['password'];
        }
        if (! empty($will)) {
            $data['will'] = $will;
        }
        return $this->sendBuffer($data);
    }

    /**
     * 发送数据信息.
     * @param array $data
     * @param bool $response 需要响应
     * @return mixed
     */
    public function sendBuffer($data, $response = true)
    {
        $buffer = Mqtt::encode($data);
        $this->client->send($buffer);
        if ($response) {
            $response = $this->client->recv();
            if ($this->config['debug'] && strlen($response) > 0) {
                Mqtt::printStr($response);
            }
            return Mqtt::decode($response);
        }
        return true;
    }

    /**
     * 订阅主题.
     *
     * @param array $topics 主题列表
     * @return mixed
     */
    public function subscribe(array $topics)
    {
        $data = [
            'cmd' => MQTT::SUBSCRIBE, // 8
            'message_id' => $this->getMsgId(),
            'topics' => $topics,
        ];
        return $this->sendBuffer($data);
    }

    /**
     * 取消订阅主题.
     *
     * @param array $topics 主题列表
     * @return mixed
     */
    public function unSubscribe(array $topics)
    {
        $data = [
            'cmd' => MQTT::UNSUBSCRIBE, // 10
            'message_id' => $this->getMsgId(),
            'topics' => $topics,
        ];
        return $this->sendBuffer($data);
    }

    /**
     * 客户端发布消息.
     *
     * @param string $topic 主题
     * @param string $content 消息内容
     * @param int $qos 服务质量等级
     * @param int $dup
     * @param int $retain 保留标志
     * @return mixed
     */
    public function publish($topic, $content, $qos = 0, $dup = 0, $retain = 0)
    {
        $response = ($qos > 0) ? true : false;
        return $this->sendBuffer([
            'cmd' => MQTT::PUBLISH, // 3
            'message_id' => $this->getMsgId(),
            'topic' => $topic,
            'content' => $content,
            'qos' => $qos,
            'dup' => $dup,
            'retain' => $retain,
        ], $response);
    }

    /**
     * 客户端发布消息确认
     * @param $mssageId
     * @return mixed
     */
    public function puback($mssageId)
    {
        return $this->sendBuffer([
            'cmd' => MQTT::PUBACK, // 4
            'message_id' => $mssageId,
        ]);
    }

    /**
     * PUBREC
     * @param $mssageId
     * @return mixed
     */
    public function pubrec($mssageId)
    {
        return $this->sendBuffer([
            'cmd' => MQTT::PUBREC, // 5
            'message_id' => $mssageId,
        ]);
    }

    /**
     * PUBREL
     * @param $mssageId
     * @return mixed
     */
    public function pubrel($mssageId)
    {
        return $this->sendBuffer([
            'cmd' => MQTT::PUBREL, // 6
            'message_id' => $mssageId,
        ]);
    }

    /**
     * PUBCOMP
     * @param $mssageId
     * @return mixed
     */
    public function pubcomp($mssageId)
    {
        return $this->sendBuffer([
            'cmd' => MQTT::PUBCOMP, // 7
            'message_id' => $mssageId,
        ]);
    }

    /**
     * 接收订阅的消息.
     *
     * @return array|bool|string
     */
    public function recv()
    {
        $response = $this->client->recv();

        if ($response === '') {
            $this->reConnect();
        } elseif ($response === false) {

        } elseif (strlen($response) > 0) {
            return MQTT::decode($response);
        }

        return true;
    }

    /**
     * 发送心跳包.
     *
     * @return mixed
     */
    public function ping()
    {
        return $this->sendBuffer(['cmd' => MQTT::PINGREQ]); // 12
    }

    /**
     * 断开连接.
     *
     * @return mixed
     */
    public function close()
    {
        $this->sendBuffer(['cmd' => MQTT::DISCONNECT], false); // 14
        return $this->client->close();
    }

    /**
     * 获取当前消息id条数.
     *
     * @return int
     */
    public function getMsgId()
    {
        return ++$this->msgId;
    }

    /**
     * 重连.
     */
    private function reConnect()
    {
        //重连
        $this->client->connect($this->config['host'], $this->config['port'], $this->config['time_out']);
        $this->connect();
    }
}