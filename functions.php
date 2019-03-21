<?php

/** 
 * Formatando os valores de moeda.
 * 
 * @param float $vlprice 
 * @return number_format
 */
function formatPrice(float $vlprice)
{
    return number_format($vlprice, 2, ",", ".");
}
