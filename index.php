<?php

header("Content-Type: application/json");

// Incluimos la base de datos (que ejecuta cargarVariablesEntorno) y los controladores
require_once "database/database.php";
require_once "controllers/auxiliar.php";
require_once "controllers/ControladorUsuario.php";
require_once "controllers/ControladorMosca.php";

$metodo = $_SERVER["REQUEST_METHOD"];
$ruta = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$parametros = explode("/", trim($ruta, "/"));

// Leemos el cuerpo JSON de la petición
$json = file_get_contents("php://input");
$datos = json_decode($json, true) ?? [];

try {
    switch ($metodo) {
        case "GET": {
            $endpoint = $parametros[0] ?? '';

            // GET /intentos
            if ($endpoint === "intentos") {
                $usuarioId = Auxiliar::obtenerUsuarioId($datos);
                ControladorMosca::verIntentos($usuarioId);

                // GET /usuarios/
            } else if ($endpoint === "usuarios") {
                if (isset($parametros[1]) && is_numeric($parametros[1])) {
                    // GET /usuarios/{id}
                    $solicitanteId = Auxiliar::obtenerUsuarioId($datos);
                    ControladorUsuario::obtenerPorId($solicitanteId, intval($parametros[1]));
                } else {
                    // GET /usuarios
                    $solicitanteId = Auxiliar::obtenerUsuarioId($datos);
                    ControladorUsuario::obtenerTodos($solicitanteId);
                }

            } else {
                Auxiliar::sendJson(404, ["error" => "Ruta no encontrada", "ok" => false]);
            }
            break;
        }

        case "POST": {
            $endpoint = $parametros[0] ?? '';

            // POST /register
            if ($endpoint === "register") {
                ControladorUsuario::registro(
                    $datos['user'] ?? '',
                    $datos['email'] ?? '',
                    $datos['pass'] ?? '',
                    $datos['edad'] ?? 0
                );

                // POST /login
            } else if ($endpoint === "login") {
                ControladorUsuario::login(
                    $datos['user'] ?? '',
                    $datos['pass'] ?? ''
                );

                // POST /recuperar
            } else if ($endpoint === "recuperar") {
                $email = $datos["email"] ?? null;
                if (!$email) {
                    Auxiliar::sendJson(400, ["error" => "Debes indicar el email para recuperar la contraseña", "ok" => false]);
                } else {
                    ControladorUsuario::recuperarPassword($email);
                }

                // POST /crear
            } else if ($endpoint === "crear") {
                $usuarioId = Auxiliar::obtenerUsuarioId($datos);
                ControladorMosca::crearTablero(
                    $usuarioId,
                    $datos['tamano'] ?? null,
                    $datos['intentos'] ?? null
                );

                // POST /jugar
            } else if ($endpoint === "jugar") {
                $usuarioId = Auxiliar::obtenerUsuarioId($datos);
                if (!isset($datos['casilla']) || !is_numeric($datos['casilla'])) {
                    Auxiliar::sendJson(400, ["error" => "Debe indicar el número de casilla", "ok" => false]);
                }
                ControladorMosca::jugar($usuarioId, intval($datos['casilla']));

            } else if ($endpoint === "register-admin") {
                ControladorUsuario::registroAdmin(
                    $datos['user'] ?? '',
                    $datos['email'] ?? '',
                    $datos['pass'] ?? '',
                    $datos['edad'] ?? 0
                );
            } else {
                Auxiliar::sendJson(404, ["error" => "Ruta no encontrada", "ok" => false]);
            }
            break;
        }

        case "PUT": {
            $endpoint = $parametros[0] ?? '';

            // PUT /rendirse
            if ($endpoint === "rendirse") {
                $usuarioId = Auxiliar::obtenerUsuarioId($datos);
                ControladorMosca::rendirse($usuarioId);

            } // PUT /usuarios/{id}
            else if ($endpoint === "usuarios" && isset($parametros[1]) && is_numeric($parametros[1])) {
                ControladorUsuario::actualizar(
                    intval($parametros[1]),
                    $datos['user'] ?? '',
                    $datos['edad'] ?? 0
                );

            } else {
                Auxiliar::sendJson(404, ["error" => "Ruta no encontrada", "ok" => false]);
            }
            break;
        }

        case "DELETE": {
            $endpoint = $parametros[0] ?? '';

            // DELETE /usuarios/{id}
            if ($endpoint === "usuarios" && isset($parametros[1]) && is_numeric($parametros[1])) {
                $solicitanteId = Auxiliar::obtenerUsuarioId($datos);
                ControladorUsuario::borrar($solicitanteId, intval($parametros[1]));
            } else {
                Auxiliar::sendJson(404, ["error" => "Ruta no encontrada", "ok" => false]);
            }
            break;
        }

        default: {
            Auxiliar::sendJson(405, ["error" => "Método HTTP no permitido", "ok" => false]);
            break;
        }
    }
} catch (Throwable $e) {
    Auxiliar::sendJson(500, ["error" => "Error interno en el servidor: " . $e->getMessage(), "ok" => false]);
}
?>