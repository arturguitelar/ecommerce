<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;

class Product extends Model
{
    /** CRUD de produtos */
    /**
     * Lista os produtos.
     * 
     * @return Hcode\DB\Sql $sql
     */ 
    public static function listAll()
    {
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_products ORDER BY desproduct");
    }

    /**
     * Checa a lista de produtos. 
     * Retorna os objetos tratados do banco.
     * 
     * @param array $list
     * @return arrayList $list
     */
    public static function checkList($list)
    {
        // &$row = o & significa que irá mudar a variável direto em memória
        foreach ($list as &$row) {
            $product = new Product();

            $product->setData($row);

            $row = $product->getValues();
        }

        return $list;
    }

    /** 
     * Salvando o registro criado.
     *
     * Utilizando a procedure:
     * `sp_products_save`
     * (
     *      pidproduct int(11),
     *      pdesproduct varchar(64),
     *      pvlprice decimal(10,2),
     *      pvlwidth decimal(10,2),
     *      pvlheight decimal(10,2),
     *      pvllength decimal(10,2),
     *      pvlweight decimal(10,2),
     *      pdesurl varchar(128)
     *  )
     */
    public function save()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_products_save(
            :idproduct, :desproduct, :vlprice, :vlwidth, 
            :vlheight, :vllength, :vlweight, :desurl
            )", array(
                ":idproduct" => $this->getidproduct(),
                ":desproduct" => $this->getdesproduct(),
                ":vlprice" => $this->getvlprice(),
                ":vlwidth" => $this->getvlwidth(),
                ":vlheight" => $this->getvlheight(),
                ":vllength" => $this->getvllength(),
                ":vlweight" => $this->getvlweight(),
                ":desurl" => $this->getdesurl(),
            )
        );

        $this->setData($results[0]);
    }

    /**
     * Selecionando o registro no banco.
     * 
     * @param int $idproduct
     */
    public function get($idproduct)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_products WHERE idproduct = :idproduct", array(
                ":idproduct" => $idproduct
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

        $sql->query("DELETE FROM tb_products WHERE idproduct = :idproduct", array(
            ":idproduct" => $this->getidproduct()
        ));
    }

    /**
     * Adicionando uma coluna a mais para envio de foto.
     * 
     * @return $values
     */
    public function getValues()
    {
        $this->checkPhoto();
        $values = parent::getValues();

        return $values;
    }

    /**
     * Checagem de foto.
     * Caso a foto não exista, utiliza uma imagem padrão.
     * 
     * @return $url
     */
    public function checkPhoto()
    {
        $filepath = $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . 
            "res" . DIRECTORY_SEPARATOR . 
            "site" . DIRECTORY_SEPARATOR . 
            "img" . DIRECTORY_SEPARATOR . 
            "products" .DIRECTORY_SEPARATOR;

        $filename = $this->getidproduct() . ".jpg";
        
        if (file_exists($filepath . $filename)) {
            $url = "/res/site/img/products/" . $filename;
        } else {
            $url = "/res/site/img/product.jpg";
        }

        return $this->setdesphoto($url);
    }

    /**
     * Subindo uma foto para o server.
     * 
     * @param $_FILE["name"] $file
     */
    public function setPhoto($file)
    {
        // detectando a extensão do arquivo
        $extension = explode(".", $file["name"]);
        $extension = end($extension);

        // utilizando funções da lib GD para mudar a extensão

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($file["tmp_name"]); // nome temporáro do arquivo que está no servidor
                break;

            case 'gif':
                $image = imagecreatefromgif($file["tmp_name"]); // nome temporáro do arquivo que está no servidor
                break;

            case 'png':
                $image = imagecreatefrompng($file["tmp_name"]); // nome temporáro do arquivo que está no servidor
                break;
        }

        // imagejpg( imagem, destino )
        $filepath = $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . 
            "res" . DIRECTORY_SEPARATOR . 
            "site" . DIRECTORY_SEPARATOR . 
            "img" . DIRECTORY_SEPARATOR . 
            "products" .DIRECTORY_SEPARATOR;

        $filename = $this->getidproduct() . ".jpg";

        imagejpeg($image, $filepath . $filename);

        imagedestroy($image);

        $this->checkPhoto();
    }

    /**
     * Traz o registro através da url.
     * 
     * @param string $desurl
     */
    public function getFromURL($desurl)
    {
        $sql = new Sql();

        // LIMIT 1 garante o retorno de apenas uma linha
        $rows = $sql->select("SELECT * FROM tb_products WHERE desurl = :desurl LIMIT 1", array(
            ":desurl" => $desurl
        ));

        $this->setData($rows[0]);
    }

    /**
     * Traz as categorias registradas neste produto
     */
    public function getCategories()
    {
        $sql = new Sql();

        return $sql->select("
            SELECT * FROM tb_categories a 
            INNER JOIN tb_productscategories b
            ON a.idcategory = b.idcategory
            WHERE b.idproduct = :idproduct
        ", array(
            ":idproduct" => $this->getidproduct()
            )
        );
    }
}
