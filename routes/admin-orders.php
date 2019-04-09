<?php

use Hcode\PageAdmin;
use Hcode\Model\User;
use Hcode\Model\Order;
use Hcode\Model\OrderStatus;

// edita status
$app->get("/admin/orders/:idorder/status", function($idorder) {
    User::verifyLogin();

    $order = new Order();

    $order->get((int) $idorder);

    $page =  new PageAdmin();

    $page->setTpl("order-status", [
        'order' => $order->getValues(),
        'status' => OrderStatus::listAll(),
        'msgError' => Order::getMsgError(),
        'msgSuccess' => Order::getMsgSuccess()
    ]);
});

$app->post("/admin/orders/:idorder/status", function($idorder){
    User::verifyLogin();

    $idStatus = $_POST['idstatus'];
    // valida o status
    if (!isset($idStatus) || !(int) $idStatus > 0) {
        
        Order::setMsgError("Informe o status atual.");
    } else {
        $order = new Order();

        $order->get((int) $idorder);

        $order->setidstatus((int) $idStatus);

        $order->save();

        Order::setMsgSuccess("Status atualizado.");
    }

    header("Location: /admin/orders/$idorder/status");
    exit;
});

// delete
$app->get("/admin/orders/:idorder/delete", function($idorder) {
    User::verifyLogin();

    $order = new Order();

    $order->get((int) $idorder);

    $order->delete();

    header("Location: /admin/orders");
    exit;
});

// lista por id
$app->get("/admin/orders/:idorder", function($idorder) {
    User::verifyLogin();

    $order = new Order();

    $order->get((int) $idorder);

    $cart = $order->getCart();

    $page = new PageAdmin();

    $page->setTpl("order", [
        'order' => $order->getValues(),
        'cart' => $cart->getValues(),
        'products' => $cart->getProducts()
    ]);
});

// lista todos
$app->get("/admin/orders", function() {
    User::verifyLogin();

    $page = new PageAdmin();

    $page->setTpl("orders", [
        'orders' => Order::listAll()
    ]);
});