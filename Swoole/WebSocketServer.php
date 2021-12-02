<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\Server\Swoole;

use Swoole\HTTP\Request;
use Swoole\WebSocket\Server;

use Arikaim\Core\Server\AbstractServer;
use Arikaim\Core\Server\ServerInterface;
use Arikaim\Core\Server\WebSocketAppInterface;
use Arikaim\Core\Arikaim;
use Exception;

/**
 * Arikaim web socket swoole server 
 */
class WebSocketServer extends AbstractServer implements ServerInterface
{  
    /**
     * Http server swoole instance
     *
     * @var Swoole\HTTP\Server|null
     */
    private $server;

    /**
     * Web socket app instance
     *
     * @var WebSocketAppInterface|null
     */
    private $webSocketApp;

    /**
     * Constructor
     *
     * @param string $host
     * @param string $port
     * @param array $options
     */
    public function __construct(?string $host = null, ?string $port = null, array $options = [])
    {
        parent::__construct($host,$port,$options);

        $appClass = $this->getOption('appClass');

        if (empty($appClass) == true) {
            throw new Exception('Not valid web socket app class',1);
        }

        $this->webSocketApp = new $appClass();
        if (($this->webSocketApp instanceof WebSocketAppInterface) == false) {
            throw new Exception('Web socket app instance not implement interface WebSocketAppInterface',1);
        }
    }

    /**
     * Boot server
     *
     * @return void
    */
    public function boot(): void
    {
        $this->server = new Server($this->host,$this->port);
     
        // server start
        $this->server->on('start',function($server) {
            echo 'WebSocket server is started at ' . $this->hostToString() . PHP_EOL;
        });

        // connection open
        $this->server->on('open',function($server, $request) {     
            $this->webSocketApp->onOpen($server,$request);                    
        });

        // received message
        $this->server->on('message',function($server, $frame) {        
            $this->webSocketApp->onMessage($server,$frame);      
        });

        // colse connection
        $this->server->on('close',function($server, int $fd) {   
            $this->webSocketApp->onClose($server,$fd);              
        });

        // disconnected
        $this->server->on('disconnect',function($server, int $fd) {  
            $this->webSocketApp->onDisconnect($server,$fd);           
        });
    }

    /**
     * Run server
     *
     * @return void
     */
    public function run(): void
    {
        $this->server->start();
    } 
}
