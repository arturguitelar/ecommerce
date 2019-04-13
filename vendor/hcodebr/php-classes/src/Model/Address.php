<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;

class Address extends Model
{
    const SESSION_ERROR = "AddressError";
    /**
     * Pega o cep. 
     * Trata a string para manter apenas número.
     * Consulta a api utilizando endereço de api ViaCEP:
     * https://viacep.com.br/ws/$zipcode/json/
     * 
     * Retorna um array com o resultado.
     * 
     * Mais detalhes sobre a api em:
     * https://viacep.com.br/
     * 
     * @param string $zipcode CEP
     * @return array $data Resultado
     */
    public static function getCEP($zipcode)
    {
        $zipcode = str_replace("-", "", $zipcode);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://viacep.com.br/ws/$zipcode/json/");
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $data = json_decode(curl_exec($ch), true);

        curl_close($ch);

        return $data;
    }

    /** 
     * Faz a consulta do CEP passado por parâmetro e os dados no objeto atual.
     * Utiliza o método getCEP para consulta de api.
     * 
     * @param string $zipcode CEP
    */
    public function loadFromCEP($zipcode)
    {
        $data = Address::getCEP($zipcode);

        if (isset($data['logradouro']) && $data['logradouro']) {
            $this->setdesaddress($data['logradouro']);
            $this->setdescomplement($data['complemento']);
            $this->setdesdistrict($data['bairro']);
            $this->setdescity($data['localidade']);
            $this->setdesstate($data['uf']);
            $this->setdescountry('Brasil');
            $this->setdeszipcode($zipcode);
        }
    }

    /**
     * Salva os dados no banco utilizando a procedure 'sp_addresses_save()'.
     * 
     * Parâmetros da procedure:
     *  pidaddress int(11), 
     *  pidperson int(11),
     *  pdesaddress varchar(128),
     *  pdescomplement varchar(32),
     *  pdescity varchar(32),
     *  pdesstate varchar(32),
     *  pdescountry varchar(32),
     *  pdeszipcode char(8),
     *  pdesdistrict varchar(32)
     */
    public function save()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_addresses_save(
                :idaddress, :idperson, :desaddress, :desnumber, :descomplement, :descity, :desstate, :descountry, :deszipcode, :desdistrict
            )", array(
                ':idaddress' => $this->getidaddress(), 
                ':idperson' => $this->getidperson(), 
                ':desaddress' => utf8_decode($this->getdesaddress()), 
                ':desnumber' => $this->getdesnumber(), 
                ':descomplement' => utf8_decode($this->getdescomplement()), 
                ':descity' => utf8_decode($this->getdescity()), 
                ':desstate' => utf8_decode($this->getdesstate()), 
                ':descountry' => utf8_decode($this->getdescountry()), 
                ':deszipcode' => $this->getdeszipcode(), 
                ':desdistrict' => utf8_decode($this->getdesdistrict())
            )
        );

        if (count($results) > 0) $this->setData($results[0]);
    }

    /** MENSAGENS DE ERRO */
    /**
     * Passa mensagens de erro via session.
     * 
     * @param string $msg
     */
    public static function setMsgError($msg)
    {
        $_SESSION[Address::SESSION_ERROR] = $msg;
    }

    /**
     * Retorna mensagem de erro.
     * Chama método que limpa a mensagem da sessão antes de retornar a mensagem.
     * 
     * @return string $msg 
     */
    public static function getMsgError()
    {
        $msg = (isset($_SESSION[Address::SESSION_ERROR])) ? $_SESSION[Address::SESSION_ERROR] : "";

        Address::clearMsgError();

        return $msg;
    }

    /**
     * Limpa a mensagem de erro da sessão atual.
     */
    public static function clearMsgError()
    {
        $_SESSION[Address::SESSION_ERROR] = NULL;
    }
}
