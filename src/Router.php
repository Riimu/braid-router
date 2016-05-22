<?php

namespace Riimu\Braid\Router;

use Riimu\Braid\Router\Provider\ProviderInterface;

/**
 * Matches provided uris to the respective routes.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2016, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class Router
{
    /** @var ProviderInterface The route provider */
    private $provider;

    /**
     * Router constructor.
     * @param ProviderInterface $provider The route provider for the router
     */
    public function __construct(ProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Routes the requested uri to the appropriate route.
     * @param string $method The request method or empty string for any
     * @param string $uri The requested uri to route
     * @return null|Route The requested route or null if not found
     */
    public function route($method, $uri)
    {
        $routes = $this->provider->getRoutes();
        $urlPath = rawurldecode(parse_url((string) $uri, PHP_URL_PATH));
        $segments = array_filter(explode('/', $urlPath), 'strlen');
        $path = '/';

        do {
            if (isset($routes[$path])) {
                $route = $this->matchRoutes($routes[$path], $method, $segments);

                if ($route) {
                    return $route;
                }
            }

            $path .= array_shift($segments) . '/';
        } while ($segments);

        return null;
    }

    /**
     * Matches the set of route definitions to provided parameters.
     * @param array[] $routes The list of route definitions
     * @param string $method The request method or empty string for any
     * @param string[] $params The parameters from the path
     * @return null|Route The matched route or null if none matches
     */
    private function matchRoutes(array $routes, $method, array $params)
    {
        $params = array_values($params);
        $method = strtoupper((string) $method);

        foreach ($routes as $route) {
            if (count($params) !== count($route['params'])) {
                continue;
            } elseif ($method && !in_array($method, $route['methods'])) {
                continue;
            }

            foreach (array_values($route['params']) as $i => $regexp) {
                if (!preg_match($regexp, $params[$i])) {
                    continue;
                }
            }

            $values = array_combine(array_keys($route['params']), $params);
            $values = array_filter($values, 'is_string', ARRAY_FILTER_USE_KEY);

            return new Route($route, $method, $values);
        }

        return null;
    }
}
