<?php

use Hcode\Model\User;

/** 
 * Formatando os valores de moeda.
 * 
 * @param $vlprice Valor a ser formatado
 * @return float Valor formatado para 0000.00
 */
function formatPrice($vlprice)
{
    if (!$vlprice > 0) $vlprice = 0;
    
    return number_format($vlprice, 2, ",", ".");
}

/**
 * Checa se o usuário está logado e se é um admin.
 * 
 * @param bool $inAdmin
 * @return bool
 */
function checkLogin($inAdmin = true)
{
    return User::checkLogin($inAdmin);
}

/**
 * Retorna o nome do usuário atual da sessão.
 * 
 * @return string
 */
function getUserName()
{
    $user = User::getFromSession();
    $user->get($user->getiduser());

    return $user->getdesperson();
}