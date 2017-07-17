<?php

namespace Gundy\Rbac;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Symfony\Component\Console\Input\InputOption;
use Gundy\Rbac\DocParser;

class PermissionGenerateCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'permission:generate';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates permissions by routes';

    /**
     * The router instance.
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * An array of all the registered routes.
     *
     * @var \Illuminate\Routing\RouteCollection
     */
    protected $routes;

    /**
     *
     * @param  \Illuminate\Routing\Router $router
     * @return void
     */
    public function __construct(Router $router)
    {
        parent::__construct();
        $this->router = $router;
        $this->routes = $router->getRoutes();

    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if (count($this->routes) == 0) {
            return $this->error("Your application doesn't have any routes.");
        }

        $routes = $this->getRoutes();
        foreach ($routes as $key => $val) {
            $reflection = new \ReflectionClass ($val['controller']);
            $method = $reflection->getMethod($val['controllerMethod']);
            $doc = $method->getDocComment();
            $info = (new DocParser ())->parse($doc);
            $permission = [
                'name' => $val['method'] . '-' . $val['uri'],
                'display_name' => array_get($info, 'func', ''),
                'description' => array_get($info, 'description', ''),
            ];
            $model = '\\' . Config::get('entrust.permission');
            $model::create($permission);
        }
        dump('执行完成！');
    }

    /**
     * Compile the routes into a displayable format.
     *
     * @return array
     */
    protected function getRoutes()
    {
        $routes = collect($this->routes)->map(function ($route) {
            return $this->getRouteInformation($route);
        })->all();

        if ($sort = $this->option('sort')) {
            $routes = $this->sortRoutes($sort, $routes);
        }

        if ($this->option('reverse')) {
            $routes = array_reverse($routes);
        }

        return array_filter($routes);
    }

    /**
     * Get the route information for a given route.
     *
     * @param  \Illuminate\Routing\Route $route
     * @return array
     */
    protected function getRouteInformation(Route $route)
    {
        return $this->filterRoute([
            'host' => $route->domain(),
            'method' => implode('|', $route->methods()),
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'action' => $route->getActionName(),
            'ActionS' => $route->getAction(),
            'controller' => $route->getController(),
            'middleware' => $this->getMiddleware($route),
            'controllerMethod' => Str::parseCallback($route->getActionName())[1],
        ]);
    }

    /**
     * Sort the routes by a given element.
     *
     * @param  string $sort
     * @param  array $routes
     * @return array
     */
    protected function sortRoutes($sort, $routes)
    {
        return Arr::sort($routes, function ($route) use ($sort) {
            return $route[$sort];
        });
    }

    /**
     * Display the route information on the console.
     *
     * @param  array $routes
     * @return void
     */
    protected function displayRoutes(array $routes)
    {
        $this->table($this->headers, $routes);
    }

    /**
     * Get before filters.
     *
     * @param  \Illuminate\Routing\Route $route
     * @return string
     */
    protected function getMiddleware($route)
    {
        return collect($route->gatherMiddleware())->map(function ($middleware) {
            return $middleware instanceof Closure ? 'Closure' : $middleware;
        })->implode(',');
    }

    /**
     * Filter the route by URI and / or name.
     *
     * @param  array $route
     * @return array|null
     */
    protected function filterRoute(array $route)
    {
        if (($this->option('name') && !Str::contains($route['name'], $this->option('name'))) ||
            $this->option('path') && !Str::contains($route['uri'], $this->option('path')) ||
            $this->option('method') && !Str::contains($route['method'], $this->option('method'))
        ) {
            return;
        }

        return $route;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['method', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by method.'],

            ['name', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by name.'],

            ['path', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by path.'],

            ['reverse', 'r', InputOption::VALUE_NONE, 'Reverse the ordering of the routes.'],

            ['sort', null, InputOption::VALUE_OPTIONAL, 'The column (host, method, uri, name, action, middleware) to sort by.', 'uri'],
        ];
    }
}