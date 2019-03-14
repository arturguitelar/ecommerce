<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;

class User extends Model
{
    const SESSION = "User";

    /**
     * @param string $login User Login
     * @param string $password User Password
     * 
     * @return $user
     * @throws \Exception    
     */
    public static function login($login, $password)
    {
        // selecionando usuário do banco
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
            ":LOGIN" => $login
        ));

        // verificando se o usuário existe no banco
        if (count($results) === 0) {
            throw new \Exception("Usuário inexistente ou senha inválida.");
        }

        // verificando a senha do usuário
        $data = $results[0];

        if (password_verify($password, $data["despassword"]) === true) {
            $user = new User();

            // explicação para este método dentro da classe Model
            $user->setData($data);

            // sessão para autenticação do usuário
            $_SESSION[User::SESSION] = $user->getValues();

            return $user;
            exit;
        } else {
            throw new \Exception("Usuário inexistente ou senha inválida.");
        }
    }

    /**
     * Verificação de sessão do usuário.
     * 
     * @return 
     */
    public static function verifyLogin($inAdmin = true)
    {
        if (! isset($_SESSION[User::SESSION]) ||
            ! $_SESSION[User::SESSION] ||
            ! (int) $_SESSION[User::SESSION]["iduser"] > 0 ||
            (bool) $_SESSION[User::SESSION]["inadmin"] !== $inAdmin) 
        {
            header("Location: /admin/login");
            exit;
        }
    }

    // Lougout da sessão.
    public static function logout()
    {
        $_SESSION[User::SESSION] = null;
    }

    /**
     * Lista todos os usuários.
     * b.desperson = nome da pessoa na tb_persons
     * 
     * @return Hcode\DB\Sql $sql
     */ 
    public static function listAll()
    {
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
    }

    /**
     * Os dados são salvos no banco utilizando a procedure pre-criada chamada "sp_users_save"
     * 
     * Ordem dos dados na procedure:
     * pdesperson VARCHAR(64), 
     * pdeslogin VARCHAR(64), 
     * pdespassword VARCHAR(256), 
     * pdesemail VARCHAR(128), 
     * pnrphone BIGINT, 
     * pinadmin TINYINT
     */
    public function save()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":desperson" => $this->getdesperson(),
            ":deslogin" => $this->getdeslogin(),
            ":despassword" => $this->getdespassword(),
            ":desemail" => $this->getdesemail(),
            ":nrphone" => $this->getnrphone(),
            ":inadmin" => $this->getinadmin()
        ));

        $this->setData($results[0]);
    }
}
