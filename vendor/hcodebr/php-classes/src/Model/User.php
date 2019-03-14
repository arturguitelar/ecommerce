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

    public static function logout()
    {
        $_SESSION[User::SESSION] = null;
    }
}
