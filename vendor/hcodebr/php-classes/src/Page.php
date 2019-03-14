<?php

namespace Hcode;

use Rain\Tpl;

class Page
{
    private $tpl;
    private $options = [];
    private $defaults = [
        "data" => []
    ];

    /**
     * O construtor da classe recebe as variáveis que vem
     * com cada rota.
     *
     * @param array $opts Route options
     * @param string $tpl_dir View dir
     */
    public function __construct($opts = array(), $tpl_dir = "/views/")
    {
        // O último array sempre sobreescreve os anteriores.
        $this->options = array_merge($this->defaults, $opts);

        // As views são buscadas através do diretório root do projeto.
        $config = array(
            "tpl_dir"       => $_SERVER["DOCUMENT_ROOT"] . $tpl_dir,
            "cache_dir"     => $_SERVER["DOCUMENT_ROOT"] . "/views-cache/",
            "debug"         => false // set to false to improve the speed
        );

        Tpl::configure( $config );

        $this->tpl = new Tpl;

        $this->setData($this->options["data"]);

        // O Header vai se repetir em todas as páginas. Logo, será o arquivo de entrada.
        $this->tpl->draw("header");
    }

    /**
     * O "conteúdo / corpo" das páginas.
     * 
     * @param $name
     * @param array $data
     * @param bool $returnHTML
     */
    public function setTpl($name, $data = array(), $returnHTML = false)
    {
        $this->setData($data);

        return $this->tpl->draw($name, $returnHTML);
    }

    /**
     * Para atribuição das variáveis que irão aparecer no template.
     * 
     * @param array $data
     */
    private function setData($data = array())
    {
        foreach ($data as $key => $value) {
            $this->tpl->assign($key, $value);
        }
    }

    public function __destruct()
    {
        // o Footer também se repete em todas as páginas.
        $this->tpl->draw("footer");
    }
}