<?php
// Routes
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

$app->group('/voucher', function () use ($app) { 
    
    $app->post('/createOrder', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
        $this->logger->info("exchange.post ".$request->getUri());
        $response->withHeader('Content-type', 'application/json');
        return $response->withJson($this->tangoservicebr->createOrder($request->getBody()->getContents(), $request->getHeader('token')[0]));
    });
   
});


$app->get('/test', function () {
    $this->logger->info("test'/'");
    echo 'Slim 3 Test';
});
