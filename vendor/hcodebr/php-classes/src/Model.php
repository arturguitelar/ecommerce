<?php

namespace Hcode;

class Model
{
    // A classe fica encarregada de criar os métodos setters e getters dinâmicamente
    private $values = [];

    /** 
     * Utilizando método mágico para saber quando um objeto é chamado.
     * 
     * @param string $name
     * @param mixed $args
    */
    public function __call($name, $args)
    {
        /**
         * Nota:
         * Basicamente a lógica verifica se os 3 primeiros caracteres
         * do método chamado tem o valor "get" ou "set" e então cria o
         * método getter ou setter baseado neste nome.
         */
        $method = substr($name, 0, 3);
        $fieldName = substr($name, 3, strlen($name));


        /**
         * Caso o $method contenha "get", procuramos o campo dentro do $this->values
         * se existe algum valor que identifique isso e retornamos.
         * 
         * Caso o $method contenha "set", o valor $args é atribuído.
         */
        switch ($method)
        {
            case "get":
                return isset($this->values[$fieldName]) ? $this->values[$fieldName] : null;
            break;

            case "set":
                $this->values[$fieldName] = $args[0];
            break;
        }
    }

    /** 
     * Método que criar os sets de acordo com os registros no banco de dados.
     * Pra cada campo retornado da consullta será criado um atributo com o valor 
     * dessas informações.
     * 
     * @param array $data
    */
    public function setData($data = array())
    {
        foreach ($data as $key => $value) {
            // criando o nome do método dinamicamente.
            // neste caso as {} servem para esta função.
            $this->{"set".$key}($value);
        }
    }

    /**
     * Retorna um array com valores de registro.
     * 
     * @return array $values
     */
    public function getValues()
    {
        return $this->values;
    }
}
