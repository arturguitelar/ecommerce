<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;
use Hcode\Model\User;

class Cart extends Model
{
    /** Deve-se guardar os dados do carrinho em uma sessão. */
    const SESSION = "Cart";

    const SESSION_ERROR = "CartError";

    /**
     * Sobreescrevendo o método getValues para adicionar mais valores sobre o carrinho.
     * Chama método que faz update do calculo total.
     * 
     * @return array
     */
    public function getValues()
    {
        // Nota: como este é um método de sobreescrita, mesmo tendo sido adicionado depois
        // achei melhor colocar no começo do script
        
        // já faz os cálculos necessários
        $this->getCalculateTotals();

        return Parent::getValues();
    }

    /**
     * Verifica se é preciso inserir um novo carrinho, se é necessário pegar da sessão
     * caso o carrinho já exista ou se a sessão já foi perdida mas o session id ainda persiste.
     * 
     * @return Cart $cart
     */
    public static function getFromSession()
    {
        $cart = new Cart();

        // se o carrinho já está a sessão e já foi inserido no banco...
        if (isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]["idcart"] > 0) {
            $cart->get((int)$_SESSION[Cart::SESSION]["idcart"]);
        } else {
            // caso o carrinho ainda não exista...
            // tenta carregar o carrinho a partir do session id
            $cart->getFromSessionID();

            // se não conseguir é necessário criar um carrinho novo
            if (!(int) $cart->getidcart() > 0) {
                $data = [ "dessessionid" => session_id() ];

                // usuário não-admin está logado
                if (User::checkLogin(false)) {
                   
                    $user = User::getFromSession();

                    $data["iduser"] = $user->getiduser();
                }

                $cart->setData($data);

                $cart->save();

                $cart->setToSession();
            }
        }
        
        return $cart;
    }

    /**
     * Insere carrinho na sessão.
     */
    public function setToSession()
    {
        $_SESSION[Cart::SESSION] = $this->getValues();
    }

    /**
     * Pega o carrinho pelo id.
     * 
     * @param int $idcart
     */
    public function get(int $idcart)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart", array(
            ":idcart" => $idcart
        ));

        // necessário prevenir se o retorno da consulta é vazio
        if (count($results) > 0) $this->setData($results[0]);
    }

    /**
     * Tenta carregar o carrinho a partir do session id.
     */
    public function getFromSessionID()
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid", array(
            ":dessessionid" => session_id()
        ));

        // necessário prevenir se o retorno da consulta é vazio
        if (count($results) > 0) $this->setData($results[0]);
    }

    /** CRUD básico do carrinho */

    /**
     * Lista os produtos dentro do carrinho.
     * 
     * Retorna:
     * Agrupados por => idproduct, desproduct, vlprice,  vlwidth, vlheight, vllength, vlweight, desurl
     * Ordenados por => desproduct
     * Valores somados.
     * 
     * Utiliza o método de verificar as imagens.
     * 
     * @return array Lista de produtos agrupados.
     */
    public function getProducts()
    {
        $sql = new Sql();

        $rows = $sql->select("
            SELECT b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl,  
            COUNT(*) AS nrqtd,
            SUM(b.vlprice) AS vltotal
            FROM tb_cartsproducts a
            INNER JOIN tb_products b
            ON a.idproduct = b.idproduct
            WHERE a.idcart = :idcart
            AND a.dtremoved IS NULL
            GROUP BY b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl 
            ORDER BY b.desproduct
        ", array( 
            ":idcart" => $this->getidcart() 
            )
        );

        return Product::checkList($rows);
    }

    /**
     * Traz:
     * . soma dos valores dos produtos.
     * . soma das medidas dos produtos.
     * . soma do peso total dos produtos.
     * . quantidade de produtos no carrinho.
     * 
     * @return array Valor total, medida, peso e quantidade dos produtos.
     */
    public function getProductsTotals()
    {
        $sql = new Sql();

        $results = $sql->select("
            SELECT SUM(vlprice) AS vlprice, 
            SUM(vlwidth) AS vlwidth, SUM(vlheight) AS vlheight, 
            SUM(vllength) AS vllength, SUM(vlweight) AS vlweight,
            COUNT(*) AS nrqtd
            FROM tb_products a 
            INNER JOIN tb_cartsproducts b
            ON a.idproduct = b.idproduct
            WHERE b.idcart = :idcart
            AND dtremoved IS NULL
        ", array(
            ":idcart" => $this->getidcart()
            )
        );

        if (count($results) > 0) return $results[0];

        return [];
    }

    /**
     * Salva o carrinho.
     * 
     * Utiliza a procedure 'sp_carts_save' do banco de dados para salvar os dados.
     */
    public function save()
    {
        $sql = new Sql();

        /** Utiliza a procedure 'sp_carts_save' do banco de dados. */
        $results = $sql->select("
            CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)
        ", array(
            ":idcart" => $this->getidcart(),
            ":dessessionid" => $this->getdessessionid(),
            ":iduser" => $this->getiduser(),
            ":deszipcode" => $this->getdeszipcode(),
            ":vlfreight" => $this->getvlfreight(),
            ":nrdays" => $this->getnrdays(),
        ));

        $this->setData($results[0]);
    }

    /**
     * Adiciona produto ao carrinho.
     * Chama método que faz update do calculo total.
     * 
     * @param Product $product
     */
    public function addProduct(Product $product)
    {
        $sql = new Sql();

        $sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES (:idcart, :idproduct)", array(
            ":idcart" => $this->getidcart(),
            ":idproduct" => $product->getidproduct()
        ));

        $this->getCalculateTotals();
    }

    /**
     * Remove produto do carrinho.
     * Verifica se estão sendo removidos um ou todos os produtos do mesmo tipo.
     * Chama método que faz update do calculo total.
     * 
     * @param Product $product
     * @param bool $all
    */
    public function removeProduct(Product $product, $all = false)
    {
        $sql = new Sql();

        if ($all) {
            // remove todos do mesmo produto
            $sql->query("
                    UPDATE tb_cartsproducts 
                    SET dtremoved = NOW() 
                    WHERE idcart = :idcart 
                    AND idproduct = :idproduct
                    AND dtremoved IS NULL
                ", array(
                ":idcart" => $this->getidcart(),
                ":idproduct" => $product->getidproduct()
            ));
        } else {
            // remove apenas um produto do mesmo tipo
            $sql->query("
                    UPDATE tb_cartsproducts 
                    SET dtremoved = NOW() 
                    WHERE idcart = :idcart 
                    AND idproduct = :idproduct
                    AND dtremoved IS NULL
                    LIMIT 1
                ", array(
                ":idcart" => $this->getidcart(),
                ":idproduct" => $product->getidproduct()
            ));
        }

        $this->getCalculateTotals();
    }

    /**
     * Cálculo de frete.
     * 
     * @param string $nrzipcode
     * 
     * @return object $result
     */
    public function setFreight($nrzipcode)
    {
        $nrzipcode = str_replace("-", "", $nrzipcode);

        $totals = $this->getProductsTotals();

        if ($totals["nrqtd"] > 0) {
            /** Nota: Os dados da api dos Correios são retornados em xml */
            // Nota2: Os dados estão no "Manual de Imprementação do Cálculo remoto de Preços e Prazos".pdf
            
            // Nota3: A api pede largura > 11cm, altura > 2cm, comprimento > 16 cm
            if ($totals["vlwidth"] < 11) $totals["vlwidth"] = 11;
            if ($totals["vlheight"] < 2) $totals["vlheight"] = 2;
            if ($totals["vllength"] < 16) $totals["vllength"] = 16;

            // montando a query string com todos os dados que serão passados para a url
            $qs = http_build_query(
                [
                    "nCdEmpresa" => "",
                    "sDsSenha" => "",
                    "nCdServico" => "40010",
                    "sCepOrigem" => "09853120", // cep da Hcode para teste
                    "sCepDestino" => $nrzipcode,
                    "nVlPeso" => $totals["vlweight"],
                    "nCdFormato" => 1,
                    "nVlComprimento" => $totals["vllength"],
                    "nVlAltura" => $totals["vlheight"],
                    "nVlLargura" => $totals["vlwidth"],
                    "nVlDiametro" => 0,
                    "sCdMaoPropria" => "S",
                    "nVlValorDeclarado" => $totals["vlprice"],
                    "sCdAvisoRecebimento" => "S",
                ]
            );

            // buscando o xml da url da api
            $xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?".$qs);

            /**
             * Para fins de teste.
             * Utilizando o cep da Hcode => 09853120 como Origem.
             * Dados retornados com o cep => 05266-020 (Parque Esperança - São Paulo) como destino.
             * 
             * Após o casting para array e json_decode:
             * 
             *  "Servicos": {
             *      "cServico": {
             *      "Codigo": "40010",
             *      "Valor": "352,36",
             *      "PrazoEntrega": "8",
             *      "ValorMaoPropria": "6,80",
             *      "ValorAvisoRecebimento": "5,75",
             *      "ValorValorDeclarado": "166,41",
             *      "EntregaDomiciliar": "S",
             *      "EntregaSabado": "S",
             *      "Erro": "011",
             *      "MsgErro": "O CEP de destino est\u00e1 sujeito a condi\u00e7\u00f5es especiais de entrega  pela  ECT e ser\u00e1 realizada com o acr\u00e9scimo de at\u00e9 7 (sete) dias \u00fateis ao prazo regular.",
             *      "ValorSemAdicionais": "94,40",
             *      "obsFim": "O CEP de destino est\u00e1 sujeito a condi\u00e7\u00f5es especiais de entrega  pela  ECT e ser\u00e1 realizada com o acr\u00e9scimo de at\u00e9 7 (sete) dias \u00fateis ao prazo regular."
             *      }
             *  }
             */
            $result = $xml->Servicos->cServico; // neste caso, traz referência como objeto

            // verificando erros e passando mensagens via sessão
            if ($result->MsgErro != "") {
                Cart::setMsgError($result->MsgErro);
            } else {
                Cart::clearMsgError();
            }

            // setando as informações no objeto e salvando no banco
            $this->setnrdays($result->PrazoEntrega);
            $this->setvlfreight(Cart::formatValueToDecimal($result->Valor));
            $this->setdeszipcode($nrzipcode);

            $this->save();

            return $result;
        } else {
            $this->setnrdays(0);
            $this->setvlfreight(0);
            $this->setdeszipcode(0);

            $this->save();
            return $this->getProductsTotals();
        }
    }

    /**
     * Atualiza o frete.
     */
    public function updateFreight()
    {
        // necessário verificar se existe um cep
        if ($this->getdeszipcode() != "")
            $this->setFreight($this->getdeszipcode());
    }

    /**
     * Cálculos totais de frete.
     * Chama método que faz o update do frete.
     */
    public function getCalculateTotals()
    {
        $this->updateFreight();

        $totals = $this->getProductsTotals();

        // valor total dos produtos
        $vlprice = (isset($totals["vlprice"])) ? $totals["vlprice"] : 0;
        $this->setvlsubtotal($vlprice);

        // valor total dos produtos + frete
        $vltotal = $vlprice + $this->getvlfreight();
        $this->setvltotal($vltotal);
    }

    /**
     * Formata valores de preço para serem inseridos no banco.
     * 
     * @param mixed $value
     * 
     * @return float $value
     */
    public static function formatValueToDecimal($value) : float
    {
        $value = str_replace(".", "", $value);

        return str_replace(",", ".", $value);
    }
    
    /** MENSAGENS DE ERRO */
    /**
     * Passa mensagens de erro via session.
     * 
     * @param string $msg
     */
    public static function setMsgError($msg)
    {
        $_SESSION[Cart::SESSION_ERROR] = $msg;
    }

    /**
     * Retorna mensagem de erro.
     * Chama método que limpa a mensagem da sessão antes de retornar a mensagem.
     * 
     * @return string $msg 
     */
    public static function getMsgError()
    {
        $msg = (isset($_SESSION[Cart::SESSION_ERROR])) ? $_SESSION[Cart::SESSION_ERROR] : "";

        Cart::clearMsgError();

        return $msg;
    }

    /**
     * Limpa a mensagem de erro da sessão atual.
     */
    public static function clearMsgError()
    {
        $_SESSION[Cart::SESSION_ERROR] = NULL;
    }
}
