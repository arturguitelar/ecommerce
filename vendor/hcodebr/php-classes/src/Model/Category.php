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
}
