<?php

namespace Hcode;

class PageAdmin extends Page
{
    /**
     * O construtor da classe recebe as variáveis que vem
     * com cada rota.
     *
     * @param array $opts Route options
     * @param string $tpl_dir View dir
     */
    public function __construct($opts = array(),  $tpl_dir = "/views/admin/")
    {
        // utilizando o método construtor da classe pai
        parent::__construct($opts, $tpl_dir);
    }
}
