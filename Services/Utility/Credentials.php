<?php

class Credentials{

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $hashedPassword;

    public function __construct($email = null, $hashedPassword = null){
        $this->email = $email;
        $this->hashedPassword = $hashedPassword;
    }

    /**
     * @param $email
     * @return $this
     */
    public function setEmail($email){
        $this->email = $email;
        return $this;
    }

    /**
     * @param $hashedPassword
     * @return $this
     */
    public function setHashedPassword($hashedPassword){
        $this->hashedPassword = $hashedPassword;
        return $this;
    }

    /**
     * @return null
     */
    public function getEmail(){
        return $this->email;
    }

    /**
     * @return null
     */
    public function getHashedPassword(){
        return $this->hashedPassword;
    }
}
