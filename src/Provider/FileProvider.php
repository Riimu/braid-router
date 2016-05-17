<?php

namespace Riimu\Braid\Router\Provider;

/**
 * Provides routes by reading a simple array defined in a PHP file.
 *
 * The file based route provider is provided with two file paths in the
 * constructor. The first is a path to a php file that returns an array with
 * simple route definitions and the second is a path to a file that is used to
 * cache the parsed route entries.
 *
 * The route definition array consists a simple route arrays. Each route array
 * consists of 3 values. The accepted request methods (or '*' for any), the
 * route path and the route handler. The path may define parsed parameters by
 * surrounding the path segment with curly braces and giving the parameter a
 * name, e.g. "/user/{id}/". An optional regular expression for the parameter
 * can be defined by appending the parameter name with a colon and the regular
 * expression, e.g. "/user/{id:\d+}/".
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2016, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class FileProvider implements RouteProvider
{
    /** @var array The parsed route definitions */
    private $routes;

    /** @var string[] List of known HTTP request methods */
    private static $httpMethods = ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'TRACE', 'PATCH'];

    /**
     * FileProvider constructor.
     * @param string $routeFile Path to the route definition file
     * @param string $cacheFile Path to the cache file
     */
    public function __construct($routeFile, $cacheFile)
    {
        if (file_exists($cacheFile) && filemtime($cacheFile) > filemtime($routeFile)) {
            $this->routes = require $cacheFile;
        } else {
            $this->routes = $this->parseRoutes(require $routeFile);
            $this->saveCache($cacheFile, $this->routes);
        }
    }

    /**
     * Parses the routes from the route definition array.
     * @param array[] $definitions List of simple route definitions
     * @return array[] Parsed routes
     */
    private function parseRoutes(array $definitions)
    {
        $routes = [];

        foreach ($definitions as list($methods, $path, $handler)) {
            $route = $this->parsePath($path);
            $route['methods'] = $this->canonizeMethods($methods);
            $route['handler'] = (string) $handler;

            $routes[rawurldecode($route['path'])][] = $route;
        }

        return $routes;
    }

    /**
     * Parses a route path into a route definition array.
     * @param string $path The route path to parse
     * @return array The route definition array
     */
    private function parsePath($path)
    {
        $segments = array_filter(explode('/', $path), 'strlen');
        $route = [
            'path' => '/',
            'slash' => substr($path, -1) === '/',
            'params' => [],
        ];

        while ($segments && current($segments)[0] !== '{') {
            $route['path'] .= rawurlencode(array_shift($segments)) . '/';
        }

        foreach ($segments as $segment) {
            if ($segment[0] !== '{') {
                $route['params'][] = sprintf('/^%s$/', preg_quote($segment));
                continue;
            }

            if (!preg_match('/^\\{([\\w]+})(:([^}\\\\]++|\\\\\\\\|\\\\})+)?}$/', $segment, $match)) {
                throw new \InvalidArgumentException("Invalid route path '$path'");
            }

            if (isset($route['params'][$match[1]])) {
                throw new \InvalidArgumentException("Duplicate path parameter in '$path'");
            }

            $regexp = strlen($match[2]) ? sprintf('/%s/', $match[2]) : '/.*/';
            $route['params'][$match[1]] = $regexp;
        }

        return $route;
    }

    /**
     * Canonizes the list of methods to a standard format.
     * @param string|string[] $methods Single method or an array of methods
     * @return string[] List of accepted methods
     */
    private function canonizeMethods($methods)
    {
        $methods = array_map('strtoupper', is_array($methods) ? $methods : [$methods]);

        if (in_array(current($methods), ['', '*', 'ANY'])) {
            return self::$httpMethods;
        }

        $methods = array_intersect($methods, self::$httpMethods);
        $synonyms = ['GET', 'HEAD'];

        // Any method that responds to GET must also respond to HEAD
        if (array_intersect($synonyms, $methods)) {
            $methods = array_merge($methods, array_diff($synonyms, $methods));
        }

        return $methods;
    }

    /**
     * Saves the parsed routes into a cache file.
     * @param string $file Path to the cache file
     * @param array[] $routes The parsed route definitions
     */
    private function saveCache($file, array $routes)
    {
        $php = sprintf('<?php return %s;', var_export($routes, true));
        file_put_contents($file, $php, LOCK_EX);
    }

    /**
     * Returns the parsed route definitions.
     * @return array[] The parsed route definitions
     */
    public function getRoutes()
    {
        return $this->routes;
    }
}
