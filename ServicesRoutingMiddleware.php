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
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Slim\Interfaces\RouteParserInterface;
use Slim\Interfaces\RouteResolverInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Routing\RoutingResults;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;

use Arikaim\Core\Interfaces\RoutesInterface;
use Arikaim\Core\App\SystemRoutes;
use Arikaim\Core\Models\Users;
use Arikaim\Core\Models\AccessTokens;
use Arikaim\Core\Http\Url;
use Arikaim\Core\Access\AuthFactory;
use Arikaim\Core\Routes\MiddlewareFactory;
use Arikaim\Core\Routes\RouteType;
use RuntimeException;
use Exception;

/**
 * Services server routing middleware
 */
class ServicesRoutingMiddleware implements MiddlewareInterface
{
    /**
     * @var RouteResolverInterface
     */
    protected $routeResolver;

    /**
     * @var RouteParserInterface
     */
    protected $routeParser;

    /**
     * Route collector
     *
     * @var RouteCollectorInterface
     */
    protected $routeCollector;

    /**
     * Routes storage
     *
     * @var RoutesInterface|null
     */
    protected $routes = null;

    /**
     * @param RouteResolverInterface $routeResolver
     * @param RouteCollectorInterface   $routeCollector
     */
    public function __construct(RouteResolverInterface $routeResolver, RouteCollectorInterface $routeCollector, $routes = null)
    {
        $this->routeResolver = $routeResolver;
        $this->routeParser = $routeCollector->getRouteParser();
        $this->routeCollector = $routeCollector;
        $this->routes = $routes;

        // load api routes
        $this->mapRoutes('',RoutesInterface::API);
    }

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     *
     * @throws HttpNotFoundException
     * @throws HttpMethodNotAllowedException
     * @throws RuntimeException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute('routeParser', $this->routeParser);
        $request = $this->performRouting($request);

        return $handler->handle($request);
    }

    /**
     * Perform routing
     *
     * @param  ServerRequestInterface $request PSR7 Server Request
     * @return ServerRequestInterface
     *
     * @throws HttpNotFoundException
     * @throws HttpMethodNotAllowedException
     * @throws RuntimeException
     */
    public function performRouting(ServerRequestInterface $request): ServerRequestInterface
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
      
        $routingResults = $this->routeResolver->computeRoutingResults($path,$method);
        $routeStatus = $routingResults->getRouteStatus();

        $request = $request->withAttribute('routingResults',$routingResults);
        
        switch ($routeStatus) {
            case RoutingResults::FOUND:
                $routeArguments = $routingResults->getRouteArguments();
                $routeIdentifier = $routingResults->getRouteIdentifier() ?? '';
                $route = $this->routeResolver->resolveRoute($routeIdentifier)->prepare($routeArguments);
            
                return $request                                                     
                            ->withAttribute('route',$route)
                            ->withAttribute('current_path',$path);

            case RoutingResults::NOT_FOUND:
                throw new HttpNotFoundException($request);

            case RoutingResults::METHOD_NOT_ALLOWED:
                $exception = new HttpMethodNotAllowedException($request);
                $exception->setAllowedMethods($routingResults->getAllowedMethods());
                throw $exception;

            default:
                throw new RuntimeException('Routing error.');
        }
    }

    /**
     * Map system routes
     *
     * @param string $method
     * @return void
     */
    protected function mapSystemRoutes(string $method): void
    {       
        $routes = SystemRoutes::$routes[$method] ?? false;
        if ($routes === false) {
            return;
        }
      
        if (RouteType::isApiInstallRequest() == false) {
            $user = new Users();
            $middleware = AuthFactory::createMiddleware('session',$user,[]);
        } else {
            // get only install routes
            $routes = SystemRoutes::$installRoutes[$method] ?? false;
        }
       
        foreach($routes as $item) {          
            $route = $this->routeCollector->map([$method],$item['pattern'],$item['handler']);
            if (empty($item['middleware']) == false) {
                // add middleware 
                $route->add($middleware);
            }      
        }     
    } 

    /**
     * Map extensons and templates routes
     *     
     * @param string $method
     * @param int|null $type
     * @return boolean
     * 
     * @throws Exception
     */
    public function mapRoutes(string $method, ?int $type = null): bool
    {      
        try {   
            $routes = $this->routes->searchRoutes($method,$type);                
                    
            AuthFactory::setUserProvider('session',new Users());
            AuthFactory::setUserProvider('token',new AccessTokens());
        } catch(Exception $e) {
            return false;
        }
       
        foreach($routes as $item) {          
            $route = $this->routeCollector->map([$item['method']],$item['pattern'],$item['handler_class'] . ':' . $item['handler_method']);

            $route->setArgument('route_options',$item['options'] ?? '');
            $route->setArgument('route_page_name',$item['page_name'] ?? '');
            $route->setArgument('route_extension_name',$item['extension_name'] ?? '');

            // auth middleware
            if (empty($item['auth']) == false) {
                $options['redirect'] = (empty($item['redirect_url']) == false) ? Url::BASE_URL . $item['redirect_url'] : null;                      
                $authMiddleware = AuthFactory::createMiddleware($item['auth'],null,$options);                
                if ($authMiddleware != null) {
                    // add middleware 
                    $route->add($authMiddleware);
                }
            } 
    
            $middlewares = (\is_string($item['middlewares']) == true) ? \json_decode($item['middlewares'],true) : $item['middlewares'] ?? [];
            // add middlewares                        
            foreach ($middlewares as $class) {
                $instance = MiddlewareFactory::create($class);
                if ($instance != null) {   
                    // add middleware                 
                    $route->add($instance);
                }                   
            }                                                                 
        }    
        
        return true;
    }
}
