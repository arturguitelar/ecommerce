<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;
use Hcode\Model\User;

class Cart extends Model
{
    /** Deve-se guardar os dados do carrinho em uma sessão. */
    const SESSION = "Cart";

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

    /** CRUD básico co carrinho */

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
     * @return Sql $sql
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
    }

    /**
     * Remove produto do carrinho.
     * Verifica se estão sendo removidos um ou todos os produtos do mesmo tipo.
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
    }
}
