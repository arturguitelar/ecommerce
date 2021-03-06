<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;

class Category extends Model
{
    /** CRUD de categorias */
    /**
     * Lista as categorias.
     * 
     * @return Hcode\DB\Sql $sql
     */ 
    public static function listAll()
    {
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_categories ORDER BY descategory");
    }

    /** 
     * Salvando o registro criado.
    */
    public function save()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_categories_save(:idcategory, :descategory)", 
            array(
                ":idcategory" => $this->getidcategory(),
                ":descategory" => $this->getdescategory()
            )
        );

        $this->setData($results[0]);

        Category::updateFile();
    }

    /**
     * Selecionando registro no banco.
     * 
     * @param int $idcategory
     */
    public function get($idcategory)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory", array(
                ":idcategory" => $idcategory
            )
        );

        $this->setData($results[0]);
    }

    /**
     * Deletando registro do banco.
     */
    public function delete()
    {
        $sql = new Sql();

        $sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory", array(
            ":idcategory" => $this->getidcategory()
        ));

        Category::updateFile();
    }

    /**
     * Faz o update de dados no site.
     */
    public static function updateFile()
    {
        $categories = Category::listAll();

        $html = [];

        foreach ($categories as $row) {
            array_push($html, '<li><a href="/category/' . $row['idcategory'] . '">' . $row['descategory'] . '</a></li>');
        }

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "categories-menu.html", 
            implode("", $html));
    }

    /**
     * Traz produtos relacionados ou não-relacionados com a categoria de acordo com o boolean passado.
     * true = relacionados
     * false = não-relacionados
     * 
     * @param bool $related
     * @return $sql
     */
    public function getProducts($related = true)
    {
        $sql = new Sql();

        if ($related === true) {
            return $sql->select("
                SELECT * FROM tb_products WHERE idproduct IN(
                    SELECT a.idproduct
                    FROM tb_products a
                    INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
                    WHERE b.idcategory = :idcategory
                );
            ", array(
                ":idcategory" => $this->getidcategory()
                )
            ); 
        } else {
            return $sql->select("
                SELECT * FROM tb_products WHERE idproduct NOT IN(
                    SELECT a.idproduct
                    FROM tb_products a
                    INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
                    WHERE b.idcategory = :idcategory
                );
            ", array(
                ":idcategory" => $this->getidcategory()
                )
            );
        }
    }

    /**
     * Traz os produtos com paginação;
     * 
     * @param int $page
     * @param int $itensPerPage
     * 
     * @return array
     */
    public function getProductsPage($page = 1, $itensPerPage = 4)
    {
        $start = ($page - 1) * $itensPerPage;

        $sql = new Sql();

        $results = $sql->select("
            SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_products a
            INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
            INNER JOIN tb_categories c ON c.idcategory = b.idcategory
            WHERE c.idcategory = :idcategory
            LIMIT $start, $itensPerPage;
        ", array(":idcategory" => $this->getidcategory()));

        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

        return [
            "data" => Product::checkList($results),
            "total" => (int)$resultTotal[0]["nrtotal"],
            "pages" => ceil($resultTotal[0]["nrtotal"] / $itensPerPage) // ceil arredonda o valor pra cima
        ];
    }

    /**
     * Adiciona produto na categoria.
     * 
     * @param Hcode\Model\Product $product
     */
    public function addProduct(Product $product)
    {
        $sql = new Sql();

        $sql->query("INSERT INTO tb_productscategories (idcategory, idproduct) VALUES (:idcategory, :idproduct)", array(
            ":idcategory" => $this->getidcategory(),
            ":idproduct" => $product->getidproduct()
        ));
    }

    /**
     * Remove produto da categoria.
     * 
     * @param Hcode\Model\Product $product
     */
    public function removeProduct(Product $product)
    {
        $sql = new Sql();

        $sql->query("DELETE FROM tb_productscategories WHERE idcategory = :idcategory AND idproduct = :idproduct", array(
            ":idcategory" => $this->getidcategory(),
            ":idproduct" => $product->getidproduct()
        ));
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
            FROM tb_categories
            ORDER BY descategory
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
            FROM tb_categories
            WHERE descategory LIKE :search
            ORDER BY descategory
            LIMIT $start, $itensPerPage
        ", array(
            ':search' => '%'.$search.'%'
        ));

        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

        return [
            "data" => $results,
            "total" => (int)$resultTotal[0]["nrtotal"],
            "pages" => ceil($resultTotal[0]["nrtotal"] / $itensPerPage) // ceil arredonda o valor pra cima
        ];
    }
}
