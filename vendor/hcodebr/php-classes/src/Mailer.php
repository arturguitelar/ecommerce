<?php

namespace Hcode;

use Rain\Tpl;

class Mailer
{
    // Email, senha e nome do remetente utilizados para enviar o email para o usuário
    const USERNAME = "algumemail@mail.com";
    const PASSWORD = "algumasenhaaqui";
    const NAME_FROM = "Nome do Remetente";

    private $mail;

    /**
     * @param string $toAddress
     * @param string $toName
     * @param string $subject
     * @param string $tolName
     * @param array $data
     */
    public function __construct($toAddress, $toName, $subject, $tplName, $data = array())
    {
        /* Criando o template */
        $config = array(
            "tpl_dir" => $_SERVER["DOCUMENT_ROOT"] . "/views/email/",
            "cache_dir" => $_SERVER["DOCUMENT_ROOT"] . "/views-cache/",
            "debug" => false
        );

        Tpl::configure($config);

        $tpl = new Tpl;

        foreach ($data as $key => $value) {
            $tpl->assign($key, $value);
        }

        // true para não renderizar na tela e sim passar para a variável
        $html = $tpl->draw($tplName, true);
        
        /* Utilizando o PHPMailer */
        // https://github.com/PHPMailer/PHPMailer

        $this->mail = new \PHPMailer;

        $this->mail->isSMTP();
        
        $this->mail->SMTPDebug = 0;
        
        $this->mail->Host = 'smtp.gmail.com';

        $this->mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $this->mail->Port = 587;
        $this->mail->SMTPSecure = 'tls';
        $this->mail->SMTPAuth = true;

        // use full email address for gmail
        // por quem:
        $this->mail->Username = Mailer::USERNAME;
        $this->mail->Password = Mailer::PASSWORD;
        $this->mail->setFrom(Mailer::USERNAME, Mailer::NAME_FROM);

        // para quem:
        $this->mail->addAddress($toAddress, $toName);
        $this->mail->Subject = $subject;
        
        // o template será renderizado pelo RainTpl
        $this->mail->msgHTML($html);
        
        // Replace the plain text body with one created manually
        $this->mail->AltBody = 'This is a plain-text message body';
    }

    public function send()
    {
        return $this->mail->send();
    }
}