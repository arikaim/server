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
use Twig\Node\Expression\Test\NullTest;

/**
 * Abstract server 
 */
abstract class AbstractServer implements ServerInterface
{  
    const DEFAULT_HOST = 'locahost';
    const DEFAULT_PORT = '8080';

    const STATUS_RUNNING = 'run';
    const STATUS_STOP    = 'stop';

    /**
     * Server host
     *
     * @var string
     */
    protected $host;

    /**
     * Server port
     *
     * @var string
     */
    protected $port;

    /**
     * Server options
     *
     * @var array
     */
    protected $options;

    /**
     * Server status
     *
     * @var string|null
     */
    protected $status;

    /**
     * Constructor
     *
     * @param string $host
     * @param string $port
     * @param array $options
     */
    public function __construct(?string $host = null, ?string $port = null, array $options = [])
    {
        $this->host = $host ?? Self::DEFAULT_HOST;
        $this->port = $port ?? Self::DEFAULT_PORT;
        $this->options = $options;
    }

    /**
     * Boot server
     *
     * @return void
    */
    abstract public function boot(): void;

    /**
     * Run server
     *
     * @return void
     */
    abstract public function run(): void;

    /**
     * Stop server
     *    
     * @return void
     */
    public function stop(): void
    {
        $this->status = Self::STATUS_STOP;
    }

    /**
     * Get option
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getOption(string $key, $default = null)
    {
        return $this->options[$key] ?? $default;
    }

    /**
     * Get server host with port
     *
     * @return string
     */
    public function hostToString(): string 
    {
        return $this->host . ':' . $this->port;
    }
}
