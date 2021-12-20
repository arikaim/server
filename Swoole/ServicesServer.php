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
use Arikaim\Core\Server\Swoole\RequestConverter;
use Arikaim\Core\Server\Swoole\ResponseConverter;
use Arikaim\Core\Server\ServerInterface;
use Arikaim\Core\Framework\Middleware\BodyParsingMiddleware;
use Arikaim\Core\Arikaim;

/**
 * Arikaim services swoole server 
 */
class ServicesServer extends AbstractServer implements ServerInterface
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
        $this->consoleMsg('Server boot ...');
        $this->server = new Server($this->host,$this->port);

        Arikaim::$app->addMiddleware(BodyParsingMiddleware::class);   

        $middlewares = Arikaim::config()->get('middleware',[]); 
        foreach ($middlewares as $item) {
            if (empty($item['handler'] ?? '') == false) {
                Arikaim::$app->addMiddleware($item['handler'],$item['options'] ?? []);
            }           
        }  
       
        $factory = Arikaim::$app->getFactory();
                                      
        // server start
        $this->server->on('start',function (Server $server) {
            $this->consoleMsg('Services server is started at ' . $this->hostToString());           
        });

        // server request
        $this->server->on('request',function(Request $request, Response $response) use($factory) {          
            $GLOBALS['APP_START_TIME'] = \microtime(true);

            $psrRequest = RequestConverter::convert($request,$factory);
            $psrResponse = Arikaim::$app->handleRequest($psrRequest);                    
            ResponseConverter::convert($psrResponse,$response)->end();     
        });

        // server stop
        $this->server->on('shutdown',function($server, $workerId) {
            $this->consoleMsg('Servcies server shutdown.');          
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

    /**
     * Stop server
     *    
     * @return void
     */
    public function stop(): void
    {
        $this->server->stop();
    }
}
