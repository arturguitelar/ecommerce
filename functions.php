<?php

use Hcode\Model\User;
use Hcode\Model\Cart;

/** 
 * Formata os valores de moeda.
 * 
 * @param $vlprice Valor a ser formatado.
 * @return float Valor formatado para 0000.00.
 */
function formatPrice($vlprice)
{
    if (!$vlprice > 0) $vlprice = 0;
    
    return number_format($vlprice, 2, ",", ".");
}

/**
 * Formata a data.
 * 
 * @param string $date Data a ser dormatada.
 * @return date Data formatada.
 */
function formatDate($date)
{
    return date('d/m/Y', strtotime($date));
}

/**
 * Checa se o usuário está logado e se é um admin.
 * 
 * @param bool $inAdmin
 * @return bool Está como admin? True / False.
 */
function checkLogin($inAdmin = true)
{
    return User::checkLogin($inAdmin);
}

/**
 * Retorna o nome do usuário atual da sessão.
 * 
 * @return string Nome do usuário.
 */
function getUserName()
{
    $user = User::getFromSession();

    return $user->getdesperson();
}

/**
 * Retorna quantidade de produtos no carrinho.
 * 
 * @return string Total de produtos no carrinho.
 */
function getCartNrQtd()
{
    $cart = Cart::getFromSession();

    $totals = $cart->getProductsTotals();

    return $totals['nrqtd'];
}

/**
 * Retorna o valor total dos produtos no carrinho sem o frete.
 * 
 * @return string Total de valores dos produtos no carrinho.
 */
function getCarVlSubtotal()
{
    $cart = Cart::getFromSession();

    $totals = $cart->getProductsTotals();

    return formatPrice($totals['vlprice']);
}