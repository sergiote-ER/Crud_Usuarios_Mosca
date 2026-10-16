<?php
require_once "database/database.php";
class usuarioDAO
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function registrarUsuario($user, $email, $pass, $edad, $rol)
    {
        $conexion = $this->db->connect();
        $passMd5 = md5($pass);

        $sentencia = $conexion->prepare("INSERT INTO usuarios (user, email, pass, edad, rol) VALUES (?, ?, ?, ?, ?)");
        $sentencia->bind_param("sssis", $user, $email, $passMd5, $edad, $rol);

        $exito = $sentencia->execute();
        $idGenerado = $sentencia->insert_id;

        $sentencia->close();
        $conexion->close();

        return $exito ? $idGenerado : false;
    }
    

    public function loginUsuario($user, $pass)
    {
        $conexion = $this->db->connect();
        $passMd5 = md5($pass);

        // Se corrige la consulta añadiendo "AND pass = ?" para comparar la contraseña MD5
        $sentencia = $conexion->prepare("SELECT id, user, email, pass, edad, rol FROM usuarios WHERE user = ? AND pass = ?");
        $sentencia->bind_param("ss", $user, $passMd5);
        $sentencia->execute();

        $resultado = $sentencia->get_result()->fetch_assoc();

        $sentencia->close();
        $conexion->close();

        return $resultado ?: false;
    }

    public function actualizar($id, $user, $edad)
    {
        $conexion = $this->db->connect();

        $sentencia = $conexion->prepare("UPDATE usuarios SET user = ?, edad = ? WHERE id = ?");
        $sentencia->bind_param("sii", $user, $edad, $id);

        $sentencia->execute();
        $filasAfectadas = $sentencia->affected_rows;

        $sentencia->close();
        $conexion->close();

        return $filasAfectadas >= 0;
    }

    public function eliminarUsuario($id)
    {
        $conexion = $this->db->connect();
        $sentencia = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
        $sentencia->bind_param("i", $id);
        $sentencia->execute();
        $fila = $sentencia->affected_rows;
        $sentencia->close();
        $conexion->close();
        return $fila > 0;
    }

    public function obtenerPorId($id)
    {
        $conexion = $this->db->connect();

        // Se añade la columna 'rol' para que el controlador pueda validar permisos
        $sentencia = $conexion->prepare("SELECT id, user, edad, email, rol FROM usuarios WHERE id = ?");
        $sentencia->bind_param("i", $id);
        $sentencia->execute();

        $resultado = $sentencia->get_result();
        $usuario = $resultado->fetch_assoc();

        $sentencia->close();
        $conexion->close();

        return $usuario;
    }

    /**
     * Obtiene un usuario por el email, esto es para recuperar la contraseña porque en las páginas web se suele poner
     * tu correo electrónico o nombre de usuario para recuperar la contraseña, yo prefiero el email porque así
     * me acuerdo siempre y no se puede cambiar en este programa
     */
    public function obtenerPorEmail($email)
    {
        $conexion = $this->db->connect();
        $sentencia = $conexion->prepare("SELECT id, user, email, pass, edad, rol FROM usuarios WHERE email = ?");
        $sentencia->bind_param("s", $email);
        $sentencia->execute();

        // No sé si debería hacerlo en dos pasos como hago en otras funciones o solo en una, he probado de ambas y ambas funcionan
        $resultado = $sentencia->get_result()->fetch_assoc();

        $sentencia->close();
        $conexion->close();

        return $resultado;
    }

    public function obtenerTodos()
    {
        $conexion = $this->db->connect();

        // Se añade 'rol' a la selección de columnas
        $sentencia = $conexion->prepare("SELECT id, user, edad, email, rol FROM usuarios");
        $sentencia->execute();
        $resultado = $sentencia->get_result();

        $usuarios = [];
        while ($fila = $resultado->fetch_assoc()) {
            $usuarios[] = $fila;
        }

        $sentencia->close();
        $conexion->close();

        return $usuarios;
    }

    public function cambiarRol($id, $nuevoRol)
    {
        $conexion = $this->db->connect();
        $sentencia = $conexion->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
        $sentencia->bind_param("si", $nuevoRol, $id);

        $sentencia->execute();
        $filasAfectadas = $sentencia->affected_rows;

        $sentencia->close();
        $conexion->close();

        return $filasAfectadas > 0;
    }

    public function borrar($id)
    {
        $conexion = $this->db->connect();
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);

        $stmt->execute();
        $afectados = $stmt->affected_rows;
        $stmt->close();
        $conexion->close();

        return $afectados > 0;
    }
}