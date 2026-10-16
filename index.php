<?php

// Establezco la cabecera HTTP para que todas las respuestas de la API se devuelvan estrictamente en formato JSON
header("Content-Type: application/json");

// Incluyo la base de datos (que ejecuta la carga de mi archivo .env) y los controladores/modelos que he creado
require_once "database/database.php";
require_once "controllers/auxiliar.php";
require_once "controllers/ControladorUsuario.php";
require_once "controllers/ControladorMosca.php";

// Obtengo el método HTTP (GET, POST, PUT, DELETE) utilizado en la petición
$metodo = $_SERVER["REQUEST_METHOD"];

// Extraigo la ruta de la URL para determinar qué endpoint está solicitando el cliente
$ruta = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// Separo los segmentos de la URL por barras '/' en un array de parámetros (ej: /usuarios/2 -> ['usuarios', '2'])
$parametros = explode("/", trim($ruta, "/"));

// Leo el cuerpo (Body) de la petición HTTP en formato JSON directamente del flujo de entrada de PHP
$json = file_get_contents("php://input");

// Decodifico el JSON en un array asociativo de PHP
$datos = json_decode($json, true);

// Si no se han enviado datos o el JSON es inválido, inicializo un array vacío mediante la función tradicional array()
if (!$datos) {
    $datos = array();
}

try {
    switch ($metodo) {
        case "GET": {
            // Compruebo si existe el primer parámetro en la ruta sin utilizar operadores complejos
            $endpoint = "";
            if (isset($parametros[0])) {
                $endpoint = $parametros[0];
            }

            // GET /intentos -> Permito consultar los intentos restantes de la partida activa
            if ($endpoint === "intentos") {
                // Obtengo el usuario_id del solicitante a través del Body JSON
                $usuarioId = Auxiliar::obtenerUsuarioId($datos);
                ControladorMosca::verIntentos($usuarioId);

            } else if ($endpoint === "usuarios") {
                // Obtengo el usuario_id para validar si es ADMIN o el propio usuario
                $solicitanteId = Auxiliar::obtenerUsuarioId($datos);

                if (isset($parametros[1]) && is_numeric($parametros[1])) {
                    // GET /usuarios/{id} -> Muestro un usuario específico por su ID
                    ControladorUsuario::obtenerPorId($solicitanteId, intval($parametros[1]));
                } else {
                    // GET /usuarios -> Listo todos los usuarios de la base de datos
                    ControladorUsuario::obtenerTodos($solicitanteId);
                }
            } else {
                // Si la ruta no coincide con ningún endpoint de mi sistema, devuelvo un error 404
                Auxiliar::sendJson(404, array("error" => "Ruta no encontrada", "ok" => false));
            }
            break;
        }

        case "POST": {
            $endpoint = "";
            if (isset($parametros[0])) {
                $endpoint = $parametros[0];
            }

            // POST /register -> Registro público de un nuevo usuario con rol JUGADOR
            if ($endpoint === "register") {
                $user = "";
                if (isset($datos['user'])) {
                    $user = $datos['user'];
                }

                $email = "";
                if (isset($datos['email'])) {
                    $email = $datos['email'];
                }

                $pass = "";
                if (isset($datos['pass'])) {
                    $pass = $datos['pass'];
                }

                $edad = 0;
                if (isset($datos['edad'])) {
                    $edad = $datos['edad'];
                }

                ControladorUsuario::registro($user, $email, $pass, $edad);

                // POST /login -> Autenticación de usuarios contra mi base de datos
            } else if ($endpoint === "login") {
                $user = "";
                if (isset($datos['user'])) {
                    $user = $datos['user'];
                }

                $pass = "";
                if (isset($datos['pass'])) {
                    $pass = $datos['pass'];
                }

                ControladorUsuario::login($user, $pass);

                // POST /recuperar -> Envío de contraseña temporal usando PHPMailer
            } else if ($endpoint === "recuperar") {
                $email = null;
                if (isset($datos['email'])) {
                    $email = $datos['email'];
                }

                if (!$email) {
                    Auxiliar::sendJson(400, array("error" => "Debes indicar el email para recuperar la contraseña", "ok" => false));
                } else {
                    ControladorUsuario::recuperarPassword($email);
                }

                // POST /crear -> Creación de una nueva partida del Juego de la Mosca
            } else if ($endpoint === "crear") {
                $usuarioId = Auxiliar::obtenerUsuarioId($datos);

                $tamano = null;
                if (isset($datos['tamano'])) {
                    $tamano = $datos['tamano'];
                }

                $intentos = null;
                if (isset($datos['intentos'])) {
                    $intentos = $datos['intentos'];
                }

                ControladorMosca::crearTablero($usuarioId, $tamano, $intentos);

                // POST /jugar -> Proceso un golpe/manotazo en una casilla del tablero
            } else if ($endpoint === "jugar") {
                $usuarioId = Auxiliar::obtenerUsuarioId($datos);

                if (!isset($datos['casilla']) || !is_numeric($datos['casilla'])) {
                    Auxiliar::sendJson(400, array("error" => "Debe indicar el número de casilla", "ok" => false));
                }

                ControladorMosca::jugar($usuarioId, intval($datos['casilla']));

                // POST /register-admin -> He añadido este endpoint exclusivo para crear administradores fácilmente en las pruebas
            } else if ($endpoint === "register-admin") {
                $user = "";
                if (isset($datos['user'])) {
                    $user = $datos['user'];
                }

                $email = "";
                if (isset($datos['email'])) {
                    $email = $datos['email'];
                }

                $pass = "";
                if (isset($datos['pass'])) {
                    $pass = $datos['pass'];
                }

                $edad = 0;
                if (isset($datos['edad'])) {
                    $edad = $datos['edad'];
                }

                ControladorUsuario::registroAdmin($user, $email, $pass, $edad);
            } else {
                Auxiliar::sendJson(404, array("error" => "Ruta no encontrada", "ok" => false));
            }
            break;
        }

        case "PUT": {
            $endpoint = "";
            if (isset($parametros[0])) {
                $endpoint = $parametros[0];
            }

            // PUT /rendirse -> Permito desactivar la partida actual marcándola como perdida
            if ($endpoint === "rendirse") {
                $usuarioId = Auxiliar::obtenerUsuarioId($datos);
                ControladorMosca::rendirse($usuarioId);

            } // PUT /usuarios/{id} -> Actualizo únicamente el nombre de usuario y la edad como pide el enunciado
            else if ($endpoint === "usuarios" && isset($parametros[1]) && is_numeric($parametros[1])) {
                $user = "";
                if (isset($datos['user'])) {
                    $user = $datos['user'];
                }

                $edad = 0;
                if (isset($datos['edad'])) {
                    $edad = $datos['edad'];
                }

                ControladorUsuario::actualizar(
                    intval($parametros[1]),
                    $user,
                    $edad
                );

            } else {
                Auxiliar::sendJson(404, array("error" => "Ruta no encontrada", "ok" => false));
            }
            break;
        }

        case "DELETE": {
            $endpoint = "";
            if (isset($parametros[0])) {
                $endpoint = $parametros[0];
            }

            // DELETE /usuarios/{id} -> Borro un usuario (restringido únicamente al rol ADMIN)
            if ($endpoint === "usuarios" && isset($parametros[1]) && is_numeric($parametros[1])) {
                $solicitanteId = Auxiliar::obtenerUsuarioId($datos);
                ControladorUsuario::borrar($solicitanteId, intval($parametros[1]));
            } else {
                Auxiliar::sendJson(404, array("error" => "Ruta no encontrada", "ok" => false));
            }
            break;
        }

        default: {
            // Si el cliente usa un método no soportado (ej. PATCH, OPTIONS), devuelvo un error 405
            Auxiliar::sendJson(405, array("error" => "Método HTTP no permitido", "ok" => false));
            break;
        }
    }
} catch (Throwable $e) {
    // Capturo cualquier excepción no controlada en mi servidor y respondo con un error HTTP 500 genérico
    Auxiliar::sendJson(500, array("error" => "Error interno en el servidor: " . $e->getMessage(), "ok" => false));
}
?>