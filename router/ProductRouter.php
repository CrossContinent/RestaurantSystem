<?php

/**
 * Created by PhpStorm.
 * User: Ozodrukh
 * Date: 5/9/17
 * Time: 5:02 PM
 */
class ProductRouter extends ModelRouter
{
    /**
     * ProductRouter constructor.
     */
    public function __construct()
    {
        parent::__construct("Product");
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

    public function query(Request $request, Response $response, Chain $chain)
    {
        $result = $this->queryModels([
            "table" => Product::TABLE,
            "fields" => "product.*",
            "joins" => [
                [
                    "join" => "left",
                    "table" => Category::TABLE,
                    "fields" => "category.name as categoryName",
                    "on" => "category.id = product.categoryId"
                ]
            ],
        ]);

        $response->status(200)->json([
            "status" => "ok",
            "result" => $result
        ]);

        $chain->proceed($request, $response);
    }
}