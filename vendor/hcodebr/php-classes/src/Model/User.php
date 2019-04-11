<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;
use Hcode\Mailer;

class User extends Model
{
    const SESSION = "User";
    const ERROR = "UserError";
    const ERROR_REGISTER = "UserErrorRegister";
    const SUCCESS = "UserSuccess";

    /**
     * O instrutor indica que nunca se suba essa chave em um repositório público
     * mas para os propósitos do exercício não há problemas.
     * 
     * Na chave é obrigatório 16 caracteres ou mais, de acordo com o tipo de encriptação.
     */
    const SECRET = "_password_secret";
    const SECRET_IV = "_password_secret";

    /**
     * Retorna o usuário da sessão.
     * 
     * @return User $user
     */
    public static function getFromSession()
    {
        $user = new User();

        // se a sessão está definida...
        if (isset($_SESSION[User::SESSION]) && (int) $_SESSION[User::SESSION]["iduser"] > 0) {
            $user->setData($_SESSION[User::SESSION]);
        }

        // trazendo o campo "desperson"
        $user->get($user->getiduser());
        $user->getdesperson();

        return $user;
    }

    /**
     * Verifica se o usuário está logado na sessão.
     * 
     * Verifica se:
     * 1 - a sessão não foi definida;
     * 2 - a sessão está vazia ou perdeu o valor;
     * 3 - se o id do usuário não é maior que zero;
     * 4 - se está logado como administrador ou não.
     * 
     * @param bool $inAdmin
     */
    public static function checkLogin($inAdmin = true)
    {
        if (
            !isset($_SESSION[User::SESSION]) ||
            !$_SESSION[User::SESSION] ||
            !(int) $_SESSION[User::SESSION]["iduser"] > 0
        ) {
            // usuário não está logado
            return false;
        } else {
            // se for uma rota da administração o usuário precisa ser um administrador de sistema...
            if ($inAdmin === true && (bool) $_SESSION[User::SESSION]["inadmin"] === true) {
                return true;
            } else if ($inAdmin === false){
                // usuário está logado mas não é um administrador do sistema
                return true;
            } else {
                // usuário não está logado
                return false;
            }
        }
    }

    /* Login Auth */
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
        } else {
            throw new \Exception("Usuário inexistente ou senha inválida.");
        }
    }

    /**
     * Verifica se o usuário é ou não admin.
     * Se não passar na verificação é redirecionado para a tela de Login.
     * 
     * @param bool $inAdmin
     */
    public static function verifyLogin($inAdmin = true)
    {
        if (!User::checkLogin($inAdmin)) 
        {
            if($inAdmin) {
                header("Location: /admin/login");
            } else {
                header("Location: /login");
            }
            exit;
        }
    }

    /**
     * Verifica se o login já existe no banco de dados.
     * 
     * @param string $login Login Usuário
     * 
     * @return bool
     * True ou False para o resultado da consulta no banco.
     */
    public static function checkLoginExist($login)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :deslogin", array(
            ":deslogin" => $login
        ));

        return (count($results) > 0);
    }

    /** 
     * Sai da sessão.
     */ 
    public static function logout()
    {
        $_SESSION[User::SESSION] = null;
    }

    /** CRUD de usuários */
    /**
     * Lista todos os usuários.
     * b.desperson = nome da pessoa na tb_persons
     * 
     * @return Hcode\DB\Sql $sql
     */ 
    public static function listAll()
    {
        $sql = new Sql();

        return $sql->select("
            SELECT * FROM tb_users a 
            INNER JOIN tb_persons b USING(idperson) 
            ORDER BY b.desperson
        ");
    }

    /**
     * Os dados são salvos no banco utilizando a procedure pre-criada chamada "sp_users_save"
     * 
     * Ordem dos parâmetros na procedure:
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

        $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", 
            array(
                ":desperson" => utf8_decode($this->getdesperson()),
                ":deslogin" => $this->getdeslogin(),
                ":despassword" => User::getPasswordHash($this->getdespassword()),
                ":desemail" => $this->getdesemail(),
                ":nrphone" => $this->getnrphone(),
                ":inadmin" => $this->getinadmin()
            )
        );

        $this->setData($results[0]);
    }

    /**
     * Cria hash para o password.
     * 
     * @param string $password
     * @return string
     */
    public static function getPasswordHash($password)
    {
        return password_hash($password, PASSWORD_DEFAULT, [
            "cost" => 12
        ]);
    }

    /** 
     * Pegando usuário no banco pelo id.
     * 
     * @param int $iduser
    */
    public function get($iduser)
    {
        $sql = new Sql();

        $results = $sql->select("
                SELECT * FROM tb_users a 
                INNER JOIN tb_persons b USING(idperson) 
                WHERE a.iduser = :iduser
            ", array(
                ":iduser" => $iduser
            )
        );

        $data = $results[0];
        $data["desperson"] = utf8_encode($data["desperson"]);

        $this->setData($data);
    }

    /**
     * Fazendo update no registro de usuário.
     * 
     * Da mesma foram que o método save, utiliza-se a procedure no banco que será acessada
     * para fazer o update dos dados.
     * A diferença é que desta vez enviamos o iduser.
     */
    public function update()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":iduser" => $this->getiduser(),
            ":desperson" => utf8_decode($this->getdesperson()),
            ":deslogin" => $this->getdeslogin(),
            ":despassword" => User::getPasswordHash($this->getdespassword()),
            ":desemail" => $this->getdesemail(),
            ":nrphone" => $this->getnrphone(),
            ":inadmin" => $this->getinadmin()
        ));

        $this->setData($results[0]);
    }

    /**
     * Deletando usuário.
     * 
     * Também será usada uma procedure definida previamente no banco.
     */
    public function delete()
    {
        $sql = new Sql();

        $sql->query("CALL sp_users_delete(:iduser)", array(
            ":iduser" => $this->getiduser()
        ));
    }

    /* Forgot Password */
    /**
     * Verificando se o email está cadastrado no banco de dados.
     * 
     * @param string $email Email
     * @param bool $inadmin É um usuário admin?
     * @return mixed $data Dados de recuperação.
     */
    public static function getForgot($email, $inadmin = true)
    {
        $sql = new Sql();

        $results = $sql->select("
            SELECT *
            FROM tb_persons a
            INNER JOIN tb_users b USING(idperson)
            WHERE a.desemail = :email
        ", array( ":email" => $email ));

        // verificando se o email existe ou não no banco
        if (count($results) === 0) {
            throw new \Exception("Não foi possível recuperar a senha.");
        } else {
            $data = $results[0];

            // Utlizando a procedure sp_userspasswordsrecoveries_create
            $resultsRecovery = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
                ":iduser" => $data["iduser"],
                ":desip" => $_SERVER["REMOTE_ADDR"] // pega o id da sessão do usuário
            ));

            if (count($resultsRecovery) === 0) {
                throw new \Exception("Não foi possível recuperar a senha.");
            } else {
                $dataRecovery = $resultsRecovery[0];

                // gerando o código criptografado
                // http://php.net/manual/pt_BR/function.openssl-encrypt.php
                $code = base64_encode(
                    openssl_encrypt(
                        $dataRecovery["idrecovery"],
                        "AES-128-CBC", User::SECRET,
                        OPENSSL_RAW_DATA, User::SECRET_IV
                    )
                );

                // endereço que receberá o código e será enviado por email
                if ($inadmin === true) {
                    $link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";
                } else {
                    $link = "http://www.hcodecommerce.com.br/forgot/reset?code=$code";
                }

                // enviando por email via PHPMailer
                $subject = "Redefinir senha da Hcode Store";
                $mailer = new Mailer(
                    $data["desemail"], $data["desperson"], $subject, "forgot", array(
                        "name" => $data["desperson"],
                        "link" => $link
                    )
                );

                $mailer->send();

                return $data;
            }
        }
    }

    /**
     * @param string $code
     * 
     * @return array $results
     * @throws \Exception
     */
    public static function validForgotDecrypt($code)
    {
        // desencriptando a senha
        // http://php.net/manual/pt_BR/function.openssl-decrypt.php
        $idrecovery = openssl_decrypt(
            base64_decode($code),
            "AES-128-CBC", User::SECRET,
            OPENSSL_RAW_DATA, User::SECRET_IV
        );

        $sql = new Sql();

        $results = $sql->select("
            SELECT * FROM tb_userspasswordsrecoveries a
            INNER JOIN tb_users b USING(iduser)
            INNER JOIN tb_persons c USING(idperson)
            WHERE a.idrecovery = :idrecovery 
            AND a.dtrecovery IS NULL
            AND DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW()
        ", array( ":idrecovery" => $idrecovery ));

        if (count($results) === 0) {
            throw new \Exception("Não foi possível recuperar a senha.");
        } else {
            return $results[0];
        }
    }

    /**
     * @param string $idrecovery
     */
    public static function setForgotUsed($idrecovery)
    {
        $sql = new Sql();

        $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
            ":idrecovery" => $idrecovery
        ));
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $sql = new Sql();

        $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
            ":password" => $password,
            ":iduser" => $this->getiduser()
        ));
    }

    /**
     * Busca os pedidos pendentes.
     * 
     * @return array $results Lista de resultados.
     */
    public function getOrders()
    {
        $sql = new Sql();

        $results = $sql->select("
            SELECT * FROM tb_orders a
            INNER JOIN tb_ordersstatus b USING(idstatus)
            INNER JOIN tb_carts c USING(idcart)
            INNER JOIN tb_users d ON d.iduser = a.iduser
            INNER JOIN tb_addresses e USING(idaddress)
            INNER JOIN tb_persons f ON f.idperson = d.idperson
            WHERE a.iduser = :iduser
        ", array( ':iduser' => $this->getiduser() ));

        return $results;
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
            FROM tb_users a
            INNER JOIN tb_persons b USING(idperson)
            ORDER BY b.desperson
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
            FROM tb_users a
            INNER JOIN tb_persons b USING(idperson)
            WHERE b.desperson LIKE :search OR b.desemail = :search OR a.deslogin LIKE :search
            ORDER BY b.desperson
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

    /** MENSAGENS DE ERRO */
    /**
     * Passa mensagens de erro via session.
     * 
     * @param string $msg
     */
    public static function setMsgError($msg)
    {
        $_SESSION[User::ERROR] = $msg;
    }

    /**
     * Retorna mensagem de erro caso ela exista em ERROR.
     * Chama método que limpa a mensagem da sessão antes de retornar a mensagem.
     * 
     * @return string $msg 
     */
    public static function getMsgError()
    {
        // verifica se o erro estiver definido e se não estiver vazio
        $msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : "";

        User::clearMsgError();

        return $msg;
    }

    /**
     * Limpa a mensagem registrada na sessão atual.
     */
    public static function clearMsgError()
    {
        $_SESSION[User::ERROR] = NULL;
    }

     /** MENSAGENS DE SUCESSO */
    /**
     * Passa mensagens de sucesso via session.
     * 
     * @param string $msg
     */
    public static function setMsgSuccess($msg)
    {
        $_SESSION[User::SUCCESS] = $msg;
    }

    /**
     * Retorna mensagem de sucesso caso ela exista em SUCCESS.
     * Chama método que limpa a mensagem da sessão antes de retornar a mensagem.
     * 
     * @return string $msg 
     */
    public static function getMsgSuccess()
    {
        // verifica se o sucesso estiver definido e se não estiver vazio
        $msg = (isset($_SESSION[User::SUCCESS]) && $_SESSION[User::SUCCESS]) ? $_SESSION[User::SUCCESS] : "";

        User::clearMsgSuccess();

        return $msg;
    }

    /**
     * Limpa a mensagem registrada na sessão atual.
     */
    public static function clearMsgSuccess()
    {
        $_SESSION[User::SUCCESS] = NULL;
    }

    /** MÉTODOS DE ERRO PARA O FORM */
    /**
     * Registra mensagem de erro.
     * 
     * @param string $msg Mensagem de Erro.
     */
    public static function setErrorRegister($msg)
    {
        $_SESSION[User::ERROR_REGISTER] = $msg;
    }

    /**
     * Retorna mensagem de erro caso ela exista em ERROR_REGISTER.
     * Chama método que limpa a mensagem de ERROR_REGISTER antes de retornar a mensagem.
     * 
     * @return string $msg Mensagem de Erro.
     */
    public static function getErrorRegister()
    {
        $msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : "";

        User::clearErrorRegister();

        return $msg;
    }

    /**
     * Limpa mensagem registrada em ERROR_REGISTER.
     */
    public static function clearErrorRegister()
    {
        $_SESSION[User::ERROR_REGISTER] = NULL;
    }
}
