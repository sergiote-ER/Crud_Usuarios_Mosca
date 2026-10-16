<?php
require_once 'models/moscaDAO.php';
require_once 'models/tablero.php';
require_once 'controllers/auxiliar.php';

class ControladorMosca
{
    public static function crearTablero($usuarioId, $tamano, $intentos)
    {
        $dao = new MoscaDAO();

        // No se puede crear un tablero si el usuario ya tiene uno activo, tiene que jugar con el que ya tiene creado antes, o se rinde, o pierde o gana
        if ($dao->obtenerPartidaActiva($usuarioId)) {
            Auxiliar::sendJson(409, ["error" => "Ya tienes un tablero activo", "ok" => false]);
        }

        $tamanoFinal = (is_numeric($tamano) && $tamano > 0) ? (int)$tamano : 10;
        $intentosFinales = (is_numeric($intentos) && $intentos > 0) ? (int)$intentos : 5;

        // Se instancia el objeto Tablero (ubica la mosca y genera el array $tableroObj->tablero)
        $tableroObj = new Tablero($tamanoFinal, $intentosFinales);

        // Se le pasa el array directamente al DAO para que ejecute el implode interno
        $id = $dao->crearPartida(
            $usuarioId,
            $tableroObj->tablero,
            $intentosFinales,
            $tableroObj->posMosca
        );

        Auxiliar::sendJson(201, [
            "mensaje" => "Tablero creado correctamente",
            "partida_id" => $id,
            "tamano" => $tamanoFinal,
            "intentos" => $intentosFinales,
            "activo" => 1
        ]);
    }

    public static function jugar($usuarioId, $casilla)
    {
        // Esta funcion ya estaba desde el juego de la mosca, solo se ha adaptado para que use el id del usuario
        $dao = new MoscaDAO();
        $partida = $dao->obtenerPartidaActiva($usuarioId);

        if (!$partida) {
            Auxiliar::sendJson(400, ["error" => "No tienes ningún tablero activo", "ok" => false]);
        }

        $tamano = (int)$partida['tamano'];

        // Validar que la casilla pertenezca al rango del tablero
        if ($casilla < 0 || $casilla >= $tamano) {
            Auxiliar::sendJson(422, ["error" => "Casilla fuera de los límites del tablero", "ok" => false]);
        }

        // Reconstruimos el objeto Tablero en memoria recuperando el array con explode
        $tableroObj = new Tablero($tamano, (int)$partida['intentos']);
        $tableroObj->posMosca = (int)$partida['pos_mosca'];
        $tableroObj->tablero = explode(",", $partida['tablero']);

        // Se ejecuta el golpe
        $resManotazo = $tableroObj->darManotazo($casilla);

        if ($resManotazo === 1) {
            $resultado = "cazada";
            $activo = 0; // Victoria: se desactiva la partida
        } else if ($resManotazo === 2) {
            $resultado = "casi le das";
            $activo = ($tableroObj->intentos <= 0) ? 0 : 1;
        } else {
            $resultado = "ni se entera";
            $activo = ($tableroObj->intentos <= 0) ? 0 : 1;
        }

        // Pasamos el array $tableroObj->tablero al DAO y actualizamos
        $dao->actualizarPartida(
            $partida['id'],
            $tableroObj->tablero,
            $tableroObj->intentos,
            $tableroObj->posMosca,
            $activo
        );

        Auxiliar::sendJson(200, [
            "resultado" => $resultado,
            "intentos_restantes" => $tableroObj->intentos,
            "activo" => $activo
        ]);
    }

    public static function verIntentos($usuarioId)
    {
        $dao = new MoscaDAO();
        $partida = $dao->obtenerPartidaActiva($usuarioId);

        if (!$partida) {
            Auxiliar::sendJson(400, ["error" => "No tienes ningún tablero activo", "ok" => false]);
        }

        Auxiliar::sendJson(200, ["intentos_restantes" => (int)$partida['intentos']]);
    }

    public static function rendirse($usuarioId)
    {
        $dao = new MoscaDAO();
        $partida = $dao->obtenerPartidaActiva($usuarioId);

        if (!$partida) {
            Auxiliar::sendJson(400, ["error" => "No tienes ningún tablero activo", "ok" => false]);
        }

        // Al rendirse la partida pasa a inactiva (activo = 0)
        $dao->actualizarPartida(
            $partida['id'],
            $partida['tablero'],
            $partida['intentos'],
            $partida['pos_mosca'],
            0
        );

        Auxiliar::sendJson(200, [
            "mensaje" => "Te has rendido",
            "activo" => 0,
            "pos_mosca" => (int)$partida['pos_mosca']
        ]);
    }
}
?>