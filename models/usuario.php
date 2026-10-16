<?php
class Persona
{
    public $id;
    public $user;
    public $edad;
    public $email;
    public $pass;
    public $rol;

    public function __construct($id = null, $user = "", $edad = 0, $email = "", $pass = "", $rol = "")
    {
        $this->id = $id;
        $this->user = $user;
        $this->edad = $edad;
        $this->email = $email;
        $this->pass = $pass;
        $this->rol = $rol;
    }
}
?>