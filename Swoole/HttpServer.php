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
use Swoole\HTTP\Response;
use Swoole\HTTP\Server;

use Arikaim\Core\Server\AbstractServer;
use Arikaim\Core\Arikaim;
use Arikaim\Core\Utils\DateTime;
use Arikaim\Core\Http\ApiResponse;
use Arikaim\Core\Server\ServerInterface;
use Exception;

/**
 * Http swoole server 
 */
class HttpServer extends AbstractServer implements ServerInterface
{  
    /**
     * Http server swoole instance
     *
     * @var Swoole\HTTP\Server|null
     */
    private $server;

    /**
     * Boot server
     *
     * @return void
    */
    public function boot(): void
    {
        $this->server = new Server($this->host,$this->port);

        // server start
        $this->server->on('start',function (Server $server) {
            echo 'Http server is started at ' . $this->hostToString() . PHP_EOL;
        });

        // server request
        $this->server->on('request',function (Request $request, Response $response) {
            $response->header("Content-Type", "text/plain");
            $response->end("Hello World\n");
        });

        // server stop
        $this->server->on('shutdown',function($server, $workerId) {
            echo 'Http server shutdown ' . PHP_EOL;
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
