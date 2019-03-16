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
     * Salvando a categoria criada.
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
    }

    /**
     * Pegando id da categoria que será deletada do banco.
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
    }
}
