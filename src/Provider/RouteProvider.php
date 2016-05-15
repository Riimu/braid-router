<?php

namespace Riimu\Braid\Router\Provider;

/**
 * Interface for route providers.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2016, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface RouteProvider
{
    /**
     * Returns the grouped route definitions.
     *
     * Each route definition must be an array that consists of following keys:
     *
     *   - path: The base path of the route
     *   - slash: Whether the canonical route ends in a forward slash or not
     *   - params: Associate array of parameters and their regular expressions
     *   - methods: Array of methods accepted by the route in upper case
     *   - handler: The route handler as a string
     *
     * The returned array must be grouped by the route base paths. In other
     * words, each key must be the base path and the value must be an array of
     * route definitions matching that base path.
     *
     * @return array[] The parsed route definitions
     */
    public function getRoutes();
}
