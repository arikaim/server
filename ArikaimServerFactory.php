<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\Server;

use Arikaim\Core\Server\ServerInterface;

/**
 * Arikaim server factory 
 */
class ArikaimServerFactory 
{  
    const DEFAULT_SERVER_TYPE = 'http';
    const DEFAULT_SERVER_LIB  = 'swoole';

    /**
     * Server classes list
     */
    const SERVERS_LIST = [
        'http' => [
            'swoole'   => 'Arikaim\\Core\\Server\\Swoole\\HttpServer',
            'services' => 'Arikaim\\Core\\Server\\Swoole\\ServicesServer'
        ]
    ];

    /**
     * Server options
     *
     * @var array
     */
    private static $options;

    /**
     * Create server instance
     *
     * @param string|null $type
     * @param string|null $serverLib
     * @return ServerInterface|null
     */
    public static function create(?string $type = null, ?string $serverLib = null): ?ServerInterface
    {
        $type = $type ?? Self::DEFAULT_SERVER_TYPE;
        $serverLib = $serverLib ?? Self::DEFAULT_SERVER_LIB;

        $serverClass = Self::SERVERS_LIST[$type][$serverLib] ?? null;
        if (empty($serverClass) == true) {         
            return null;
        }

        $server = new $serverClass();

        return $server;
    }
}
