<?php
require_once "database/database.php";
class MoscaDAO
{
    private $db;
    public function __construct()
    {
        $this->db = new Database();
    }
    public function crearPartida($usuarioId, $tablero, $intentos, $posMosca, $activo = 1)
    {
        $conexion = $this->db->connect();
        $stringTablero = implode(",", $tablero);

        $sentencia = $conexion->prepare("INSERT INTO partidas (usuario_id, tablero, intentos, pos_mosca, activo) VALUES (?, ?, ?, ?, ?)");
        $sentencia->bind_param("isiii", $usuarioId, $stringTablero, $intentos, $posMosca, $activo);

        $exito = $sentencia->execute();
        $idGenerado = $sentencia->insert_id;

        $sentencia->close();
        $conexion->close();

        return $exito ? $idGenerado : false;
    }

    public function actualizarPartida($id, $tablero, $intentos, $posMosca, $activo)
    {
        $conexion = $this->db->connect();

        // También hacemos el implode al actualizar por si la mosca se ha movido
        $stringTablero = is_array($tablero) ? implode(",", $tablero) : $tablero;

        $sentencia = $conexion->prepare("UPDATE partidas SET tablero = ?, intentos = ?, pos_mosca = ?, activo = ? WHERE id = ?");
        $sentencia->bind_param("siiii", $stringTablero, $intentos, $posMosca, $activo, $id);

        $exito = $sentencia->execute();

        $sentencia->close();
        $conexion->close();

        return $exito;
    }

    public function obtenerPartidaActiva($usuarioId)
    {
        $conexion = $this->db->connect();
        $sentencia = $conexion->prepare("SELECT * FROM partidas WHERE usuario_id = ? AND activo = 1 LIMIT 1");
        $sentencia->bind_param("i", $usuarioId);
        $sentencia->execute();

        $res = $sentencia->get_result()->fetch_assoc();

        $sentencia->close();
        $conexion->close();

        return $res;
    }
}