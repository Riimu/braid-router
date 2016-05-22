<?php

namespace Riimu\Braid\Router;

/**
 * Represents a single matched route.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2016, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class Route
{
    /** @var array The route definition. */
    private $route;

    /** @var string The matched method */
    private $method;

    /** @var string[] matched parameters and their values */
    private $params;

    public function __construct(array $route, $method, array $params)
    {
        $this->route = $route;
        $this->method = (string) $method;
        $this->params = array_map('strval', $params);
    }

    /**
     * Returns the canonical uri path to the matched route.
     * @return string The canonical uri path
     */
    public function getCanonicalPath()
    {
        $path = $this->route['path'] . implode('/', array_map('rawurlencode', $this->params));

        if ($this->route['slash'] && $path !== '/') {
            $path .= '/';
        }

        return $path;
    }

    /**
     * Returns the Handler for the matched route
     * @return string The route handler
     */
    public function getHandler()
    {
        return (string) $this->route['handler'];
    }

    /**
     * Returns the matched parameters from the route.
     * @return string[] Matched parameters from the route
     */
    public function getParams()
    {
        return $this->params;
    }
}
