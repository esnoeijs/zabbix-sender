<?php

namespace Disc\Zabbix;

class Sender
{
    /**
     * Zabbix host
     *
     * @var string
     */
    protected $server;

    /**
     * Zabbix port
     *
     * @var int
     */
    protected $port;

    /**
    * Request data
    * 
    * @var array
    */
    protected $data = [];

    /**
     * Last response body
     *
     * @var array
     */
    protected $response = [];

    /**
     * Zabbix constructor.
     *
     * @param string $server Zabbix host
     * @param int $port Zabbix port
     */
    public function __construct(string $server, int $port = 10051)
    {
        $this->server = $server;
        $this->port = $port;
    }

    /**
     * Send data to Zabbix
     *
     * @param string $host Host
     * @param string $key Key
     * @param mixed $value Value
     * @param int $clock Timestamp
     * @return \Disc\Zabbix\Sender
     */
    public function addData(
        string $host, 
        string $key, 
        $value, 
        $clock = null
    ): Sender {

        $data = [
            'host' => $host,
            'key' => $key,
            'value' => $value,
        ];

        if ($clock) {
            $data['clock'] = $clock;
        }

        $this->data[] = $data;

        return $this;
    }

    /**
     * Send data to Zabbix
     *
     * @return \Disc\Zabbix\Sender
     */
    public function send(): Sender
    {
        $this->sendData(
            $this->buildRequestBody()
        );

        $this->clearData();

        return $this;
    }

    /**
     * Returns response array
     *
     * @return array
     */
    public function getResponse(): array
    {
        return $this->response;
    }

    /**
     * Returns array of data
     *
     * @return array
     */
    protected function getData(): array
    {
        return $this->data;
    }

    /**
     * Returns json encoded request

     * @return string
     */
    protected function buildRequestBody(): string
    {
        return json_encode([
            'request' => 'sender data',
            'data' => $this->getData(),
        ]);
    }

    /**
     * Send data to zabbix by socket
     *
     * @param string $body Request body
     * @return void
     */
    protected function sendData(string $body): void
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        socket_connect($socket, $this->server, $this->port);

        socket_send($socket, $body, strlen($body), 0);

        $this->parseResponse($socket);

        socket_close($socket);
    }

    /**
     * Parse response from socket
     *
     * @param resource $socket
     * @return \Disc\Zabbix\Sender
     */
    protected function parseResponse($socket): Sender
    {
        // Get 1024 bytes from socket
        socket_recv($socket, $response, 1024, 0);

        // Length of header in response 13 bytes
        $headerLength = 13;

        if ($response) {
            $this->response = json_decode(mb_substr($response, $headerLength), true);
        }

        return $this;
    }

    /**
     * Clear request data
     *
     * @return \Disc\Zabbix\Sender
     */
    protected function clearData(): Sender
    {
        $this->data = [];

        return $this;
    }
}
