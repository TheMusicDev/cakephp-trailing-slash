<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Http\Response;

/**
 * A page the test app serves, so the integration test can tell "served" from "redirected".
 */
class PingController extends Controller
{
    /**
     * @return \Cake\Http\Response
     */
    public function index(): Response
    {
        return $this->response->withType('text')->withStringBody('pong');
    }
}
