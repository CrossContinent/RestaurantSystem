<?php

use Firebase\JWT\JWT as Token;

define('AUTH_ALG', 'HS256');

/**
 * Routing of /users
 * @method callRoleCreatesUser()
 * @method callSignIn()
 * @method callSignUp()
 */
class UserRouter extends BaseRouter
{
    /**
     * Dispatches Router create event
     *
     * Register all routes in {@code $router} that will later
     * registered as sub routing in your Application
     *
     * @param RouterDispatcher $dispatcher Router
     */
    public function didRouterCreated(RouterDispatcher $dispatcher): void
    {
        $roles = new Roles();
        $roles->setRoleObservable(function (Request $request) {
            return Roles::from($request->variable('token'));
        });

        $dispatcher = new RouterDispatcher();
        $dispatcher->get("", $this->callSignIn());
        $dispatcher->post("", $this->callSignUp());

        $dispatcher->post("create", UserRouter::verifyAuthorization());
        $dispatcher->post("create", $roles->hasRole('admin'));
        $dispatcher->post("create", $this->callRoleCreatesUser());
    }

    private static function createToken(Account $account): string
    {
        Log::debug("Debug", $account);
        if (empty($account->role)) {
            throw new InvalidArgumentException("Account `role` field must present");
        } else if (empty($account->id)) {
            throw new InvalidArgumentException("Account `id` field must present");
        }

        return Token::encode([
            "userId" => str_pad($account->id, 10, '0'),
            "role" => $account->role], AUTH_KEY);
    }

    /**
     * Checks whether Authorization Token is set and tries to decode
     * and update Request and set token variable
     *
     * @throws AuthenticationException
     */
    public static function verifyAuthorization()
    {
        return function (Request $req, Response $res, Chain $chain) {
            $bearer = $req->header("Authorization");
            if ($bearer !== null && "Bearer" === substr($bearer, 0, 6)) {
                $tokenEncoded = substr($bearer, 6, strlen($bearer));

                try {
                    $req->setVariable('token', Token::decode($tokenEncoded, AUTH_KEY), false);
                    $chain->proceed($req, $res);
                } catch (Exception $e) {
                    throw new AuthenticationException("Not authorized", 401);
                }
            }
            throw new AuthenticationException("Not authorized", 401);
        };
    }

    public function signUp(Request $req, Response $res, Chain $chain)
    {

        $account = Account::fromRequestBody($req->body());

        if (strlen($account->password) < 8) {
            throw new Exception('password length must have at least 8 characters', 400);
        }

        // by default role is simple `user`
        $account->role = 'user';
        // simple password hashing, from leaks
        $account->password = md5($account->password);

        $database = new DataSource();
        if (!$database->insert($account)) {
            throw new Exception('Name already exists', 400);
        }

        $account->id = $database->getLastInsertedId();

        if ($database->getDatabase()->errorCode() !== PDO::ERR_NONE) {
            throw new RuntimeException($database->getDatabase()->errorInfo(), 500);
        }

        $res->status(201)
            ->setHeader('Authorization', 'Bearer ' . self::createToken($account))
            ->json([
                'status' => "ok",
                "result" => ["id" => str_pad($account->id, 10, '0')]
            ]);

        $chain->proceed($req, $res);
    }

    /**
     * User sign in
     *
     * @param Request $req
     * @param Response $res
     * @param Chain $chain
     */
    public function signIn(Request $req, Response $res, Chain $chain)
    {

        $conditions = [
            "name" => $req->body()["name"],
            "password" => md5($req->body()["password"])
        ];

        $database = new DataSource();
        $accounts = $database->fetchAll(Account::TABLE, $conditions, ["id", "role"], function () {
            return PersistentModel::create('Account', ['id', 'role'], func_get_args());
        });

        Log::debug("Debug", $accounts);

        if (count($accounts) == 1) {
            $res->status(200)
                ->setHeader('Authorization', 'Bearer ' . self::createToken($accounts[0]))
                ->json(["status" => "ok"]);
        }

        $chain->proceed($req, $res);
    }

    public function roleCreatesUser(Request $req, Response $res, Chain $chain)
    {

        $res->status(201)
            ->setHeader('Authorization', 'Bearer ' . self::createToken($accounts[0]))
            ->json(["status" => "ok"]);
    }
}

?>