<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class User extends Model
{
    const SESSION = "User";
    const SECRET = "HcodePhp7_Secret";

    public static function login($login, $password)
    {
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(":LOGIN"=>$login));
        if (count($results) === 0)
        {
            throw new \Exception("Usuário inexistente ou senha inválida.");
        }
        $data = $results[0];
        if (password_verify($password, $data["despassword"]) === true)
        {
            echo "fez login";
            $user = new User();
            $user->setData($data);
            $_SESSION[User::SESSION] = $user->getValues();;
        }
        else
        {
            throw new \Exception("Usuário inexistente ou senha inválida.");
        }
    }

    public static function verifyLogin()
    {
        if (!isset($_SESSION[User::SESSION]) ||
            !$_SESSION[User::SESSION] ||
            !(int)$_SESSION[User::SESSION]["iduser"] > 0 ||
            (bool)$_SESSION[User::SESSION]["inadmin"] !== true)
        {
            header("location: /admin/login/");
            exit;
        }
    }

    public static function logout()
    {
        unset($_SESSION[User::SESSION]);
    }

    public static function listAll()
    {
        $sql = new Sql();
        return $sql->select("SELECT * FROM tb_users a
                                INNER JOIN tb_persons b USING(idperson)
                            ORDER BY b.desperson");
    }

    public function save()
    {
        $sql = new Sql();
        $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", 
                                    array(
                                        ":desperson"=>$this->getdesperson(),
                                        ":deslogin"=>$this->getdeslogin(),
                                        ":despassword"=>$this->getdespassword(),
                                        ":desemail"=>$this->getdesemail(),
                                        ":nrphone"=>$this->getnrphone(),
                                        ":inadmin"=>$this->getinadmin()
                                    ));
        $this->setData($results[0]);
    }

    public function get($iduser)
    {
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser",
            array(":iduser"=>$iduser));
        $this->setData($results[0]);
    }

    public function update()
    {
        $sql = new Sql();
        $results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", 
                                    array(
                                        ":iduser"=>$this->getiduser(),
                                        ":desperson"=>$this->getdesperson(),
                                        ":deslogin"=>$this->getdeslogin(),
                                        ":despassword"=>$this->getdespassword(),
                                        ":desemail"=>$this->getdesemail(),
                                        ":nrphone"=>$this->getnrphone(),
                                        ":inadmin"=>$this->getinadmin()
                                    ));
        $this->setData($results[0]);
    }

    public function delete($iduser)
    {
        $sql = new Sql();
        $sql->query("CALL sp_users_delete(:iduser)", array(":iduser"=>$iduser));
    }

    public static function getForgot($email)
    {
        $sql = new Sql();
        $results = $sql->select(
            "SELECT *
            FROM tb_users a
                INNER JOIN tb_persons p USING(idperson)
            WHERE p.desemail = :email;",
            array(":email"=>$email)
        );
        if (count($results) === 0)
        {
            throw new \Exception("Não foi possível recuperar a senha.");
        }
        else
        {
            $data = $results[0];
            $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)",
                array(":iduser"=>$data["iduser"],
                      ":desip"=>$_SERVER["REMOTE_ADDR"])
            );

            if (count($results2) === 0)
            {
                throw new \Exception("Não foi possível recuperar a senha.");
            }
            else
            {
                $dataRecovery = $results2[0];
                $code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET,
                    $dataRecovery["idrecovery"], MCRYPT_MODE_ECB));
                $link = "http://www.ecommerce.com.br/admin/forgot/reset?code=$code";

                $mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinição de senha",
                    "forgot", array(
                                "name"=>$data["desperson"],
                                "link"=>$link
                            )
                );
                $mailer->send();
            }
        }
    }

    public static function validForgotDecrypt($code)
    {
        $idRecovery = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, User::SECRET, base64_decode($code), MCRYPT_MODE_ECB);
        $sql = new Sql();
        $results = $sql->select("SELECT *
                                FROM tb_userspasswordsrecoveries a
                                    INNER JOIN tb_users b USING(iduser)
                                    INNER JOIN tb_persons c ON b.idperson=c.idperson
                                WHERE a.idrecovery = :idrecovery AND a.dtrecovery IS NULL AND
                                    DATE_ADD(a.dtregister, interval 1 hour) >= NOW();",
                                array(
                                    ":idrecovery"=>$idRecovery
                                )
        );
        if (count($results) === 0)
        {
            throw new \Exception("Não foi possível resetar a senha.", 1);
        }
        else
        {
            return $results[0];
        }
    }

    public static function setForgotUsed($idRecovery)
    {
        $sql = new Sql;
        $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery",
            array(
                ":idrecovery"=>$idRecovery
            )
        );
    }

    public function setPassword($password)
    {
        $sql = new Sql();
        $hash = password_hash($password, PASSWORD_DEFAULT, array("cost"=>12));
        $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser",
            array(
                ":password"=>$hash,
                ":iduser"=>$this->getiduser()
            )
        );
    }
}


?>