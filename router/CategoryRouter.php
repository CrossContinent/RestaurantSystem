<?php

/**
 * Created by PhpStorm.
 * User: Ozodrukh
 * Date: 5/9/17
 * Time: 5:02 PM
 */
class CategoryRouter extends ModelRouter
{
    /**
     * CategoryRouter constructor.
     */
    public function __construct()
    {
        parent::__construct('Category');
    }


    /**
     * Dispatches Router create event
     *
     * Register all routes in {@code $router} that will later
     * registered as sub routing in your Application
     *
     * @param Router $router Router
     */
    public function didRouterCreated(Router $router): void
    {
        $router->get('/', $this->callQuery());
        $router->post('/', $this->callCreate());
        $router->delete('/', $this->callDelete());
    }
}