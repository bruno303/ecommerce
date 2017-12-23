<?php

namespace Hcode;
use Rain\Tpl;

class Mailer
{
    const USERNAME = "bruno_bru_sp@hotmail.com";
    const PASSWORD = "pc782134";
    const FROMNAME = "Bruno Oliveira";

    private $mail;
    
    public function __construct($toAddress, $toName, $subject, $tplName, $data = array())
    {
        $config = array(
            "tpl_dir"       => $_SERVER['DOCUMENT_ROOT'] . "/views/email/",
            "cache_dir"     => $_SERVER['DOCUMENT_ROOT'] . "/views-cache/",
            "debug"         => false // set to false to improve the speed
           );

        Tpl::configure( $config );
        //Tpl::registerPlugin( new \Tpl\Plugin\PathReplace() );
        $tpl = new Tpl;

        foreach ($data as $key => $value) {
            $tpl->assign($key, $value);
        }

        $html = $tpl->draw($tplName, true);

        $this->mail = new \PHPMailer;
        $this->mail->isSMTP();
        $this->mail->SMTPDebug = 0;
        //$this->mail->Host = 'smtps.bol.com.br';
        $this->mail->Host = 'smtp-mail.outlook.com';
        $this->mail->Port = 587;
        $this->mail->SMTPSecure = 'tls';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = Mailer::USERNAME;
        $this->mail->Password = Mailer::PASSWORD;
        $this->mail->setFrom(Mailer::USERNAME, Mailer::FROMNAME);
        //$mail->addReplyTo('b.oliveira303@bol.com.br', 'PHP 7');
        $this->mail->addAddress($toAddress, $toName);
        $this->mail->Subject = utf8_decode($subject);
        $this->mail->msgHTML($html);
        //$this->mail->AltBody = 'Teste de envio de e-mail com PHPMailer.';
    }

    public function send()
    {
        return $this->mail->send();
    }
}

?>