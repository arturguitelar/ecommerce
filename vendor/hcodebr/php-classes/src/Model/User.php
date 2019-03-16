<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;
use Hcode\Mailer;

class User extends Model
{
    const SESSION = "User";

    /**
     * O instrutor indica que nunca se suba essa chave em um repositório público
     * mas para os propósitos do exercício não há problemas.
     * 
     * Na chave é obrigatório 16 caracteres ou mais, de acordo com o tipo de encriptação.
     */
    const SECRET = "_password_secret";
    const SECRET_IV = "_password_secret";

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
     * Verificação de sessão do usuário.
     * 
     * Verifica se:
     * 1 - a sessão não foi definida;
     * 2 - a sessão está vazia ou perdeu o valor;
     * 3 - se o id do usuário não é maior que zero;
     * 4 - se está logado como administrador ou não.
     * 
     * Se não passar na verificação é redirecionado ára a tela de Login.
     */
    public static function verifyLogin($inAdmin = true)
    {
        if (
            !isset($_SESSION[User::SESSION]) ||
            !$_SESSION[User::SESSION] ||
            !(int) $_SESSION[User::SESSION]["iduser"] > 0 ||
            (bool) $_SESSION[User::SESSION]["inadmin"] !== $inAdmin
        ) 
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
                ":desperson" => $this->getdesperson(),
                ":deslogin" => $this->getdeslogin(),
                ":despassword" => $this->getdespassword(),
                ":desemail" => $this->getdesemail(),
                ":nrphone" => $this->getnrphone(),
                ":inadmin" => $this->getinadmin()
            )
        );

        $this->setData($results[0]);
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

        $this->setData($results[0]);
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
            ":desperson" => $this->getdesperson(),
            ":deslogin" => $this->getdeslogin(),
            ":despassword" => $this->getdespassword(),
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
     * @param string $email
     * @return mixed $data
     */
    public static function getForgot($email)
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
                $link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";

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
}
