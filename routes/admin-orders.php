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

    // busca de produtos
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	
	// página atual
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	// filtro da query
	// Lista os produtos do banco com paginação.
	// Leva em consideração se o usuário digitou algo na caixa de busca.
	if ($search != '') {
		$pagination = Order::getPageSearch($search, $page, 10);
	} else {
		$pagination =  Order::getPage($page, 10);
	}

	// todas as páginas
	$pages = [];
	for ($i = 0; $i < $pagination['pages']; $i++) {
		array_push($pages, [
			'href' => '/admin/orders?'.http_build_query([
				'page' => $i + 1,
				'search' => $search
			]),
			'text' => $i + 1
		]);
	}

    $page = new PageAdmin();

    $page->setTpl("orders", [
        'orders' => $pagination['data'],
		"search" => $search,
		"pages" => $pages
    ]);
});