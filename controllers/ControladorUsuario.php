<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'phpmailer/src/Exception.php';
require_once 'phpmailer/src/PHPMailer.php';
require_once 'phpmailer/src/SMTP.php';
require_once 'models/usuarioDAO.php';

class ControladorUsuario
{
    public static function registro($user, $email, $pass, $edad)
    {
        // Evito que los campos estén vacíos, el trim es para que quite los espacios y no detecte espacios en blanco como datos válidos, porque no lo son
        if (empty(trim($user)) || empty(trim($email)) || empty(trim($pass)) || !is_numeric($edad)) {
            Auxiliar::sendJson(400, ["error" => "Faltan datos obligatorios o la edad no es válida", "ok" => false]);
            return;
        }

        if (intval($edad) < 0) {
            Auxiliar::sendJson(422, ["error" => "La edad no puede ser negativa", "ok" => false]);
            return;
        }

        $dao = new UsuarioDAO();

        // Comprobación previa de duplicados para dar un error específico porque no puede haber emails repetidos, como en una página de verdad que los emails están registrados
        if ($dao->obtenerPorEmail($email)) {
            Auxiliar::sendJson(409, ["error" => "El email ya está registrado", "ok" => false]);
            return;
        }

        $id = $dao->registrarUsuario(trim($user), trim($email), trim($pass), (int) $edad, 'JUGADOR');

        if ($id) {
            Auxiliar::sendJson(201, ["mensaje" => "Usuario registrado correctamente", "id" => $id, "ok" => true]);
        } else {
            Auxiliar::sendJson(500, ["error" => "Error al registrar el usuario en la base de datos", "ok" => false]);
        }
    }

    // Esta función existe exclusivamente para hacer admins, de normal se hacen usuarios normales

    public static function registroAdmin($user, $email, $pass, $edad)
    {
        if (empty(trim($user)) || empty(trim($email)) || empty(trim($pass)) || !is_numeric($edad)) {
            Auxiliar::sendJson(400, ["error" => "Faltan datos obligatorios o la edad no es válida", "ok" => false]);
            return;
        }

        if ((int) $edad < 0) {
            Auxiliar::sendJson(422, ["error" => "La edad no puede ser negativa", "ok" => false]);
            return;
        }

        $dao = new UsuarioDAO();

        if ($dao->obtenerPorEmail(trim($email))) {
            Auxiliar::sendJson(409, ["error" => "El email ya está registrado", "ok" => false]);
            return;
        }

        // Le pasamos 'ADMIN' explícitamente en el parámetro de rol
        $id = $dao->registrarUsuario(trim($user), trim($email), trim($pass), (int) $edad, 'ADMIN');

        if ($id) {
            Auxiliar::sendJson(201, [
                "mensaje" => "Administrador registrado correctamente",
                "id" => $id,
                "rol" => "ADMIN",
                "ok" => true
            ]);
        } else {
            Auxiliar::sendJson(500, ["error" => "Error al registrar el administrador", "ok" => false]);
        }
    }

    public static function login($user, $pass)
    {
        if (empty(trim($user)) || empty(trim($pass))) {
            Auxiliar::sendJson(400, ["error" => "Debes proporcionar usuario y contraseña", "ok" => false]);
            return;
        }

        $dao = new UsuarioDAO();

        $usuario = $dao->loginUsuario(trim($user), trim($pass));

        if ($usuario) {
            Auxiliar::sendJson(200, ["mensaje" => "Login correcto", "usuario" => $usuario, "ok" => true]);
        } else {
            // Se da un mensaje correcto para no dar pistas por si el mensaje lo lee un atacante y no una persona normal, una persona de bien
            Auxiliar::sendJson(401, ["error" => "Credenciales incorrectas", "ok" => false]);
        }
    }

    public static function obtenerTodos($solicitanteId)
    {
        $dao = new UsuarioDAO();
        $solicitante = $dao->obtenerPorId($solicitanteId);

        if (!$solicitante) {
            Auxiliar::sendJson(401, ["error" => "El usuario solicitante no existe", "ok" => false]);
            return;
        }

        if (($solicitante['rol'] ?? '') !== 'ADMIN') {
            Auxiliar::sendJson(403, ["error" => "Acceso denegado: Se requiere rol ADMIN", "ok" => false]);
            return;
        }

        $usuarios = $dao->obtenerTodos();
        Auxiliar::sendJson(200, ["usuarios" => $usuarios, "ok" => true]);
    }

    public static function obtenerPorId($solicitanteId, $idBuscado)
    {
        $dao = new UsuarioDAO();
        $solicitante = $dao->obtenerPorId($solicitanteId);

        if (!$solicitante) {
            Auxiliar::sendJson(401, ["error" => "El usuario solicitante no existe", "ok" => false]);
            return;
        }

        // Permite la consulta si es ADMIN o si es el propio usuario consultando su perfil
        if (($solicitante['rol'] ?? '') !== 'ADMIN' && (int)$solicitanteId !== (int)$idBuscado) {
            Auxiliar::sendJson(403, ["error" => "Acceso denegado", "ok" => false]);
            return;
        }

        $usuario = $dao->obtenerPorId($idBuscado);
        if ($usuario) {
            Auxiliar::sendJson(200, ["usuario" => $usuario, "ok" => true]);
        } else {
            Auxiliar::sendJson(404, ["error" => "Usuario no encontrado", "ok" => false]);
        }
    }

    public static function actualizar($id, $user, $edad)
    {
        if (empty(trim($user)) || !is_numeric($edad)) {
            Auxiliar::sendJson(400, ["error" => "Faltan datos requeridos (user y edad) para la actualización", "ok" => false]);
            return;
        }

        $dao = new UsuarioDAO();

        if (!$dao->obtenerPorId($id)) {
            Auxiliar::sendJson(404, ["error" => "El usuario a actualizar no existe", "ok" => false]);
            return;
        }

        $exito = $dao->actualizar($id, trim($user), (int)$edad);

        if ($exito) {
            Auxiliar::sendJson(200, ["mensaje" => "Usuario actualizado con éxito", "ok" => true]);
        } else {
            // Pongo el error 500 porque si el servidor no puede completar la solicitud del usuario por un problema inesperado/no controlado, en teoría se da el error 500
            Auxiliar::sendJson(500, ["error" => "No se realizaron cambios en el usuario", "ok" => false]);
        }
    }

    public static function borrar($solicitanteId, $idBorrar)
    {
        $dao = new UsuarioDAO();
        $solicitante = $dao->obtenerPorId($solicitanteId);

        if (!$solicitante) {
            Auxiliar::sendJson(401, ["error" => "El usuario solicitante no existe", "ok" => false]);
            return;
        }

        if (($solicitante['rol'] ?? '') !== 'ADMIN') {
            Auxiliar::sendJson(403, ["error" => "Solo un administrador puede eliminar usuarios", "ok" => false]);
            return;
        }

        if (!$dao->obtenerPorId($idBorrar)) {
            Auxiliar::sendJson(404, ["error" => "El usuario a eliminar no existe", "ok" => false]);
            return;
        }

        $exito = $dao->borrar($idBorrar);
        if ($exito) {
            Auxiliar::sendJson(200, ["mensaje" => "Usuario eliminado correctamente", "ok" => true]);
        } else {
            // Pongo el error 500 porque si el servidor no puede completar la solicitud del usuario por un problema inesperado/no controlado, en teoría se da el error 500
            Auxiliar::sendJson(500, ["error" => "Error al intentar borrar el usuario", "ok" => false]);
        }
    }

    public static function recuperarPassword($emailDestino)
    {
        if (empty(trim($emailDestino))) {
            Auxiliar::sendJson(400, ["error" => "Debes indicar la dirección de email", "ok" => false]);
            return;
        }

        $dao = new UsuarioDAO();
        $usuario = $dao->obtenerPorEmail(trim($emailDestino));

        if (!$usuario) {
            Auxiliar::sendJson(404, ["error" => "No existe ningún usuario registrado con ese correo", "ok" => false]);
            return;
        }

        $mail = new PHPMailer(true);
        try {

            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = getenv('EMAIL');
            $mail->Password = getenv('SMTP_PASSWORD');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom(getenv('EMAIL'), 'Juego de la Mosca');
            $mail->addAddress($emailDestino, $usuario['user']);

            $mail->isHTML(true);
            $mail->Subject = 'Recuperacion de contrasena';
            $mail->Body = 'Hola <b>' . htmlspecialchars($usuario['user']) . '</b>,<br>Tu contraseña registrada es: <b>' . htmlspecialchars($usuario['pass']) . '</b>';

            $mail->send();

            Auxiliar::sendJson(200, ["mensaje" => "Correo enviado correctamente a " . $emailDestino, "ok" => true]);
        } catch (Exception $e) {
            Auxiliar::sendJson(500, ["error" => "No se pudo enviar el mensaje: {$mail->ErrorInfo}", "ok" => false]);
        }
    }
}