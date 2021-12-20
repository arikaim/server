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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

use Arikaim\Core\System\Error\Renderer\JsonErrorRenderer;
use Arikaim\Core\System\Error\PhpError;
use ErrorException;
use Throwable;

/**
 * Server error handler
 */
class ServerErrorHandler
{
    /**
     * Error renderer
     *
     * @var object|null
     */
    protected $renderer = null;

    /**
     * Constructor
     *   
     * @param object|null $renderer
     */
    public function __construct($container = null,$renderer = null)
    {               
        $this->container = $container;
        $this->renderer = ($renderer == null) ? new JsonErrorRenderer() : $renderer;         
    }

    /**
     * Handle php app errors
     *
     * @param mixed $num
     * @param mixed $message
     * @param mixed $file
     * @param mixed $line
     * @return void
     */
    public function handleError($num, $message, $file, $line)
    {
        throw new ErrorException($message,0,$num,$file,$line);
    }

    /**
     * Handle route error
     *
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function handleRouteError(ResponseInterface $response): ResponseInterface
    {
        return $response;
    } 

    /**
     * Render exception
     *
     * @param Throwable $exception
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function renderExecption(Throwable $exception, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {        
        $output = $this->renderer->render(PhpError::toArray($exception));
        $response->getBody()->write($output);

        return $response->withStatus(400);      
    }   
}
