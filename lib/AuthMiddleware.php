<?php

require_once '../api/vendor/Slim/Middleware.php';

class AuthMiddleware extends \Slim\Middleware {

    public function call() {
        $app = $this->app;
        $valid = 'x9EuDf1jGemi9tIjSB9JxNmb3XuhcVJABjkdIfc7';
        $access = $app->request->headers('access-token');
        if (strcmp($access, $valid)) {
            $this->next->call();
        } else {
            $app->response->setBody(array('status' => 'failure', 'message' => 'not authenticated'));
            return;
        }
    }

}
