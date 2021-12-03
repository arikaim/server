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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpException;

use Arikaim\Core\System\Error\ErrorHandlerInterface;
use Arikaim\Core\System\Error\Renderer\HtmlPageErrorRenderer;
use Arikaim\Core\System\Error\ApplicationError;
use Arikaim\Core\Interfaces\View\HtmlPageInterface;
use Arikaim\Core\Access\AccessDeniedException;
use Throwable;
use PDOException;
use RuntimeException;
use Closure;

/**
 * Servcies server error middleware class
*/
class ServicesErrorMiddleware implements MiddlewareInterface
{
    /**
     * @var bool
     */
    protected $logErrors;

    /**
     * Page
     *
     * @var HtmlPageInterface|null
     */
    protected $page = null;

    /**
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * Page resolver
     *
     * @var Closure
     */
    protected $pageResolver;

    /**
     * Constructor 
     * 
     * @param bool $displayErrorDetails
     * @param bool $logErrors
     */
    public function __construct(Closure $pageResolver, ResponseFactoryInterface $responseFactory, bool $logErrors = true) 
    {       
        $this->pageResolver = $pageResolver;
        $this->responseFactory = $responseFactory; 
        $this->logErrors = $logErrors;       
    }

    /**
     * Get page ref
     *
     * @return HtmlPageInterface
     */
    protected function getPage()
    {
        if (empty($this->page) == true) {
            $this->page = ($this->pageResolver)();
        }

        return $this->page;
    }

    /**
     * Create error handler
     *
     * @return ErrorHandlerInterface
     */
    protected function createErrroHandler()
    {
        $errorRenderer = new HtmlPageErrorRenderer($this->getPage());

        return new ApplicationError($errorRenderer);  
    }

    /**
     * Process middleware
     * 
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        }      
        catch (PDOException $e) {
            return $this->handleException($request,$e,$handler);           
        }  
        catch (RuntimeException $e) {          
            return $this->handleException($request,$e,$handler);
        }
        catch (Throwable $e) {           
            return $this->handleException($request,$e,$handler);
        }
    }

    /**
     * Handle 
     * 
     * @param ServerRequestInterface $request
     * @param Throwable              $exception
     * @return ResponseInterface
     */
    public function handleException(ServerRequestInterface $request, Throwable $exception, $handler): ResponseInterface
    {
        $errorHandler = $this->createErrroHandler();
        $response = $this->responseFactory->createResponse();

        if ($exception instanceof AccessDeniedException) {           
            $status = 404;    
        }
        $status = ($exception instanceof HttpException) ? 404 : 400;          
         
        $output = $errorHandler->renderError($exception,'json');
        $response->getBody()->write($output);
        
        return $response
                ->withStatus($status)
                ->withHeader('Content-Type','application/json');
    }  
}
