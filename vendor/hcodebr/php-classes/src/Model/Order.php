<?php
namespace Hcode\Model;

use Hcode\Model;
use Hcode\DB\Sql;
use Hcode\Model\Cart;

class Order extends Model 
{
    const ERROR = "OrderError";
    const SUCCESS = "OrderSuccess";
    /**
     * Salva os dados utilizando a procedure "sp_orders_save()".
     * 
     *  pidorder INT,
     *  pidcart int(11),
     *  piduser int(11),
     *  pidstatus int(11),
     *  pidaddress int(11),
     *  pvltotal decimal(10,2)
     */
    public function save()
    {
        $sql = new Sql();
        
        $results = $sql->select("CALL sp_orders_save(
            :idorder, :idcart, :iduser, :idstatus, :idaddress, :vltotal
        )", array(
            ':idorder' => $this->getidorder(),
            ':idcart' => $this->getidcart(),
            ':iduser' => $this->getiduser(),
            ':idstatus' => $this->getidstatus(),
            ':idaddress' => $this->getidaddress(),
            ':vltotal' => $this->getvltotal()
            )
        );

        if (count($results) > 0) $this->setData($results[0]);
    }
    
    /**
     * Traz um registro de pedido.
     * 
     * @param int ID do Pedido
     */
    public function get($idorder)
    {
        $sql = new Sql();

        $results = $sql->select("
            SELECT * FROM tb_orders a
            INNER JOIN tb_ordersstatus b USING(idstatus)
            INNER JOIN tb_carts c USING(idcart)
            INNER JOIN tb_users d ON d.iduser = a.iduser
            INNER JOIN tb_addresses e USING(idaddress)
            INNER JOIN tb_persons f ON f.idperson = d.idperson
            WHERE a.idorder = :idorder
        ", array( ':idorder' => $idorder ));

        if (count($results) > 0) $this->setData($results[0]);
    }

    /**
     * Lista todos os registros.
     * 
     * @return array Lista de Pedidos.
     */
    public static function listAll()
    {
        $sql = new Sql();

        return $sql->select("
            SELECT * FROM tb_orders a
            INNER JOIN tb_ordersstatus b USING(idstatus)
            INNER JOIN tb_carts c USING(idcart)
            INNER JOIN tb_users d ON d.iduser = a.iduser
            INNER JOIN tb_addresses e USING(idaddress)
            INNER JOIN tb_persons f ON f.idperson = d.idperson
            ORDER BY a.dtregister DESC
        ");
    }

    /**
     * Deleta um registro.
     */
    public function delete()
    {
        $sql = new Sql();

        $sql->query("DELETE FROM tb_orders WHERE idorder = :idorder", array(
            ':idorder' => $this->getidorder()
        ));
    }

    /**
     * Lista o carrinho que pertence a este pedido.
     * 
     * @return Cart Retorna um carrinho.
     */
    public function getCart() : Cart
    {
        $cart = new Cart();

        $cart->get((int) $this->getidcart());

        return $cart;
    }

    /**
     * Traz todos os registros com paginação;
     * 
     * @param int $page Página Inicial.
     * @param int $itensPerPage Quantos itens por página.
     * 
     * @return array Resultados por página.
     */
    public static function getPage($page = 1, $itensPerPage = 10)
    {
        $start = ($page - 1) * $itensPerPage;

        $sql = new Sql();

        $results = $sql->select("
            SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_orders a
            INNER JOIN tb_ordersstatus b USING(idstatus)
            INNER JOIN tb_carts c USING(idcart)
            INNER JOIN tb_users d ON d.iduser = a.iduser
            INNER JOIN tb_addresses e USING(idaddress)
            INNER JOIN tb_persons f ON f.idperson = d.idperson
            ORDER BY a.dtregister DESC
            LIMIT $start, $itensPerPage
        ");

        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

        return [
            "data" => $results,
            "total" => (int)$resultTotal[0]["nrtotal"],
            "pages" => ceil($resultTotal[0]["nrtotal"] / $itensPerPage) // ceil arredonda o valor pra cima
        ];
    }

    /**
     * Traz os registros da busca com paginação;
     * 
     * @param string $search Busca.
     * @param int $page Página Inicial.
     * @param int $itensPerPage Quantos itens por página.
     * 
     * @return array Resultados por página.
     */
    public static function getPageSearch($search, $page = 1, $itensPerPage = 10)
    {
        $start = ($page - 1) * $itensPerPage;

        $sql = new Sql();

        $results = $sql->select("
            SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_orders a
            INNER JOIN tb_ordersstatus b USING(idstatus)
            INNER JOIN tb_carts c USING(idcart)
            INNER JOIN tb_users d ON d.iduser = a.iduser
            INNER JOIN tb_addresses e USING(idaddress)
            INNER JOIN tb_persons f ON f.idperson = d.idperson
            WHERE a.idorder = :id OR f.desperson LIKE :search
            ORDER BY a.dtregister DESC
            LIMIT $start, $itensPerPage
        ", array(
            ':id' => $search,
            ':search' => '%'.$search.'%'
        ));

        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

        return [
            "data" => $results,
            "total" => (int)$resultTotal[0]["nrtotal"],
            "pages" => ceil($resultTotal[0]["nrtotal"] / $itensPerPage) // ceil arredonda o valor pra cima
        ];
    }

    /** MENSAGENS DE ERRO */
    /**
     * Passa mensagens de erro via session.
     * 
     * @param string $msg Mensagem de erro.
     */
    public static function setMsgError($msg)
    {
        $_SESSION[Order::ERROR] = $msg;
    }

    /**
     * Retorna mensagem de erro.
     * Chama método que limpa a mensagem da sessão antes de retornar a mensagem.
     * 
     * @return string Mensagem de erro. 
     */
    public static function getMsgError()
    {
        $msg = (isset($_SESSION[Order::ERROR])) ? $_SESSION[Order::ERROR] : "";

        Order::clearMsgError();

        return $msg;
    }

    /**
     * Limpa a mensagem de erro da sessão atual.
     */
    public static function clearMsgError()
    {
        $_SESSION[Order::ERROR] = NULL;
    }

    /** MENSAGENS DE SUCESSO */
    /**
     * Passa mensagens de sucesso via session.
     * 
     * @param string $msg Mensagem de sucesso.
     */
    public static function setMsgSuccess($msg)
    {
        $_SESSION[Order::SUCCESS] = $msg;
    }

    /**
     * Retorna mensagem de sucesso caso ela exista.
     * Chama método que limpa a mensagem da sessão antes de retornar a mensagem.
     * 
     * @return string $msg Mensagem de sucesso.
     */
    public static function getMsgSuccess()
    {
        // verifica se o sucesso estiver definido e se não estiver vazio
        $msg = (isset($_SESSION[Order::SUCCESS]) && $_SESSION[Order::SUCCESS]) ? $_SESSION[Order::SUCCESS] : "";

        Order::clearMsgSuccess();

        return $msg;
    }

    /**
     * Limpa a mensagem registrada na sessão atual.
     */
    public static function clearMsgSuccess()
    {
        $_SESSION[Order::SUCCESS] = NULL;
    }
}
