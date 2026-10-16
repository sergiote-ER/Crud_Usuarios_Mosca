<?php
require_once 'models/moscaDAO.php';
require_once 'models/tablero.php';
require_once 'models/usuarioDAO.php';
require_once 'controllers/auxiliar.php';

class ControladorMosca
{
    public static function crearTablero($usuarioId, $tamano, $intentos)
    {
        // Consulto en mi base de datos al usuario para comprobar su rol
        $usuarioDao = new UsuarioDAO();
        $usuario = $usuarioDao->obtenerPorId($usuarioId);

        if (!$usuario) {
            Auxiliar::sendJson(401, array("error" => "El usuario no existe", "ok" => false));
            return;
        }

        // Compruebo con isset() el rol del usuario: un administrador no puede jugar
        $rol = "";
        if (isset($usuario['rol'])) {
            $rol = $usuario['rol'];
        }

        if ($rol === 'ADMIN') {
            Auxiliar::sendJson(403, array("error" => "Los administradores no tienen permitido jugar partidas", "ok" => false));
            return;
        }

        $dao = new MoscaDAO();

        // Compruebo que el usuario no tenga ya un tablero activo
        if ($dao->obtenerPartidaActiva($usuarioId)) {
            Auxiliar::sendJson(409, array("error" => "Ya tienes un tablero activo", "ok" => false));
            return;
        }

        $tamanoFinal = (is_numeric($tamano) && $tamano > 0) ? (int)$tamano : 10;
        $intentosFinales = (is_numeric($intentos) && $intentos > 0) ? (int)$intentos : 5;

        // Instancio mi objeto Tablero (ubica la mosca y genera el array del tablero)
        $tableroObj = new Tablero($tamanoFinal, $intentosFinales);

        // Guardo la nueva partida en la base de datos
        $id = $dao->crearPartida(
            $usuarioId,
            $tableroObj->tablero,
            $intentosFinales,
            $tableroObj->posMosca
        );

        // Respondo confirmando la creación sin saturar con datos innecesarios
        Auxiliar::sendJson(201, array(
            "mensaje" => "Tablero creado correctamente",
            "partida_id" => $id,
            "tamano" => $tamanoFinal,
            "intentos" => $intentosFinales
        ));
    }

    public static function jugar($usuarioId, $casilla)
    {
        $dao = new MoscaDAO();
        $partida = $dao->obtenerPartidaActiva($usuarioId);

        if (!$partida) {
            Auxiliar::sendJson(400, array("error" => "No tienes ningún tablero activo", "ok" => false));
            return;
        }

        $tamano = (int)$partida['tamano'];

        // Valido que la casilla elegida esté dentro de los límites de mi tablero
        if ($casilla < 0 || $casilla >= $tamano) {
            Auxiliar::sendJson(422, array("error" => "Casilla fuera de los límites del tablero", "ok" => false));
            return;
        }

        // Reconstruyo el objeto Tablero recuperando los datos guardados en MySQL
        $tableroObj = new Tablero($tamano, (int)$partida['intentos']);
        $tableroObj->posMosca = (int)$partida['pos_mosca'];
        $tableroObj->tablero = explode(",", $partida['tablero']);

        // Ejecuto el manotazo en la casilla solicitada
        $resManotazo = $tableroObj->darManotazo($casilla);

        if ($resManotazo === 1) {
            $resultado = "cazada";
            $activo = 0; // Victoria: desactivo la partida
        } else if ($resManotazo === 2) {
            $resultado = "casi le das";
            $activo = ($tableroObj->intentos <= 0) ? 0 : 1;
        } else {
            $resultado = "ni se entera";
            $activo = ($tableroObj->intentos <= 0) ? 0 : 1;
        }

        // Actualizo la partida en mi base de datos
        $dao->actualizarPartida(
            $partida['id'],
            $tableroObj->tablero,
            $tableroObj->intentos,
            $tableroObj->posMosca,
            $activo
        );

        // Muestro únicamente el resultado y los intentos restantes (elimino el parámetro 'activo')
        Auxiliar::sendJson(200, array(
            "resultado" => $resultado,
            "intentos_restantes" => $tableroObj->intentos
        ));
    }

    public static function verIntentos($usuarioId)
    {
        $dao = new MoscaDAO();
        $partida = $dao->obtenerPartidaActiva($usuarioId);

        if (!$partida) {
            Auxiliar::sendJson(400, array("error" => "No tienes ningún tablero activo", "ok" => false));
            return;
        }

        Auxiliar::sendJson(200, array("intentos_restantes" => (int)$partida['intentos']));
    }

    public static function rendirse($usuarioId)
    {
        $dao = new MoscaDAO();
        $partida = $dao->obtenerPartidaActiva($usuarioId);

        if (!$partida) {
            Auxiliar::sendJson(400, array("error" => "No tienes ningún tablero activo", "ok" => false));
            return;
        }

        // Al rendirse, desactivo la partida (activo = 0)
        $dao->actualizarPartida(
            $partida['id'],
            $partida['tablero'],
            $partida['intentos'],
            $partida['pos_mosca'],
            0
        );

        Auxiliar::sendJson(200, array(
            "mensaje" => "Te has rendido",
            "pos_mosca" => (int)$partida['pos_mosca']
        ));
    }
}
?>