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
        // Evito que los campos estén vacíos. Uso trim() para quitar los espacios al inicio y al final
        if (empty(trim($user)) || empty(trim($email)) || empty(trim($pass)) || !is_numeric($edad)) {
            Auxiliar::sendJson(400, array("error" => "Faltan datos obligatorios o la edad no es válida", "ok" => false));
            return;
        }

        // Valido la lógica del negocio: decido que la edad no pueda ser un número negativo
        if (intval($edad) < 0) {
            Auxiliar::sendJson(422, array("error" => "La edad no puede ser negativa", "ok" => false));
            return;
        }

        $dao = new UsuarioDAO();

        // Hago una comprobación previa por email para avisar al usuario de que ese correo ya existe antes de insertar
        if ($dao->obtenerPorEmail($email)) {
            Auxiliar::sendJson(409, array("error" => "El email ya está registrado", "ok" => false));
            return;
        }

        // Por defecto, decido que todo registro público guarde a los usuarios con el rol 'JUGADOR'
        $id = $dao->registrarUsuario(trim($user), trim($email), trim($pass), (int) $edad, 'JUGADOR');

        if ($id) {
            Auxiliar::sendJson(201, array("mensaje" => "Usuario registrado correctamente", "id" => $id, "ok" => true));
        } else {
            Auxiliar::sendJson(500, array("error" => "Error al registrar el usuario en la base de datos", "ok" => false));
        }
    }

    // He creado esta función exclusivamente para poder dar de alta administradores desde Postman en mis pruebas
    public static function registroAdmin($user, $email, $pass, $edad)
    {
        if (empty(trim($user)) || empty(trim($email)) || empty(trim($pass)) || !is_numeric($edad)) {
            Auxiliar::sendJson(400, array("error" => "Faltan datos obligatorios o la edad no es válida", "ok" => false));
            return;
        }

        if ((int) $edad < 0) {
            Auxiliar::sendJson(422, array("error" => "La edad no puede ser negativa", "ok" => false));
            return;
        }

        $dao = new UsuarioDAO();

        if ($dao->obtenerPorEmail(trim($email))) {
            Auxiliar::sendJson(409, array("error" => "El email ya está registrado", "ok" => false));
            return;
        }

        // Le paso el rol 'ADMIN' explícitamente a mi DAO
        $id = $dao->registrarUsuario(trim($user), trim($email), trim($pass), (int) $edad, 'ADMIN');

        if ($id) {
            Auxiliar::sendJson(201, array(
                "mensaje" => "Administrador registrado correctamente",
                "id" => $id,
                "rol" => "ADMIN",
                "ok" => true
            ));
        } else {
            Auxiliar::sendJson(500, array("error" => "Error al registrar el administrador", "ok" => false));
        }
    }

    public static function login($user, $pass)
    {
        if (empty(trim($user)) || empty(trim($pass))) {
            Auxiliar::sendJson(400, array("error" => "Debes proporcionar usuario y contraseña", "ok" => false));
            return;
        }

        $dao = new UsuarioDAO();
        $usuario = $dao->loginUsuario(trim($user), trim($pass));

        if ($usuario) {
            // Elimino la contraseña de los datos devueltos por seguridad antes de responder con el JSON
            unset($usuario['pass']);
            Auxiliar::sendJson(200, array("mensaje" => "Login correcto", "usuario" => $usuario, "ok" => true));
        } else {
            // Doy un mensaje genérico de error para evitar que un atacante sepa si lo que falló fue el usuario o la clave
            Auxiliar::sendJson(401, array("error" => "Credenciales incorrectas", "ok" => false));
        }
    }

    public static function obtenerTodos($solicitanteId)
    {
        $dao = new UsuarioDAO();
        $solicitante = $dao->obtenerPorId($solicitanteId);

        if (!$solicitante) {
            Auxiliar::sendJson(401, array("error" => "El usuario solicitante no existe", "ok" => false));
            return;
        }

        // Compruebo de forma tradicional con isset() si el usuario solicitante tiene rol ADMIN
        $rolSolicitante = "";
        if (isset($solicitante['rol'])) {
            $rolSolicitante = $solicitante['rol'];
        }

        if ($rolSolicitante !== 'ADMIN') {
            Auxiliar::sendJson(403, array("error" => "Acceso denegado: Se requiere rol ADMIN", "ok" => false));
            return;
        }

        $usuarios = $dao->obtenerTodos();
        Auxiliar::sendJson(200, array("usuarios" => $usuarios, "ok" => true));
    }

    public static function obtenerPorId($solicitanteId, $idBuscado)
    {
        $dao = new UsuarioDAO();
        $solicitante = $dao->obtenerPorId($solicitanteId);

        if (!$solicitante) {
            Auxiliar::sendJson(401, array("error" => "El usuario solicitante no existe", "ok" => false));
            return;
        }

        $rolSolicitante = "";
        if (isset($solicitante['rol'])) {
            $rolSolicitante = $solicitante['rol'];
        }

        // Permito la consulta si quien la pide es ADMIN o si es el propio usuario consultando sus propios datos
        if ($rolSolicitante !== 'ADMIN' && (int) $solicitanteId !== (int) $idBuscado) {
            Auxiliar::sendJson(403, array("error" => "Acceso denegado", "ok" => false));
            return;
        }

        $usuario = $dao->obtenerPorId($idBuscado);
        if ($usuario) {
            unset($usuario['pass']);
            Auxiliar::sendJson(200, array("usuario" => $usuario, "ok" => true));
        } else {
            Auxiliar::sendJson(404, array("error" => "Usuario no encontrado", "ok" => false));
        }
    }

    public static function actualizar($id, $user, $edad)
    {
        // Siguiendo las instrucciones del enunciado, solo permito la actualización del nombre de usuario y la edad
        if (empty(trim($user)) || !is_numeric($edad)) {
            Auxiliar::sendJson(400, array("error" => "Faltan datos requeridos (user y edad) para la actualización", "ok" => false));
            return;
        }

        $dao = new UsuarioDAO();

        if (!$dao->obtenerPorId($id)) {
            Auxiliar::sendJson(404, array("error" => "El usuario a actualizar no existe", "ok" => false));
            return;
        }

        $exito = $dao->actualizar($id, trim($user), (int) $edad);

        if ($exito) {
            Auxiliar::sendJson(200, array("mensaje" => "Usuario actualizado con éxito", "ok" => true));
        } else {
            // Pongo el error 500 porque si el servidor no puede completar la solicitud por un problema no controlado, devuelvo 500
            Auxiliar::sendJson(500, array("error" => "No se realizaron cambios en el usuario", "ok" => false));
        }
    }

    public static function borrar($solicitanteId, $idBorrar)
    {
        $dao = new UsuarioDAO();
        $solicitante = $dao->obtenerPorId($solicitanteId);

        if (!$solicitante) {
            Auxiliar::sendJson(401, array("error" => "El usuario solicitante no existe", "ok" => false));
            return;
        }

        $rolSolicitante = "";
        if (isset($solicitante['rol'])) {
            $rolSolicitante = $solicitante['rol'];
        }

        // Añado esta restricción para que únicamente un administrador pueda borrar usuarios
        if ($rolSolicitante !== 'ADMIN') {
            Auxiliar::sendJson(403, array("error" => "Solo un administrador puede eliminar usuarios", "ok" => false));
            return;
        }

        if (!$dao->obtenerPorId($idBorrar)) {
            Auxiliar::sendJson(404, array("error" => "El usuario a eliminar no existe", "ok" => false));
            return;
        }

        $exito = $dao->borrar($idBorrar);
        if ($exito) {
            Auxiliar::sendJson(200, array("mensaje" => "Usuario eliminado correctamente", "ok" => true));
        } else {
            // Pongo el error 500 porque si el servidor no puede completar la solicitud por un problema no controlado, devuelvo 500
            Auxiliar::sendJson(500, array("error" => "Error al intentar borrar el usuario", "ok" => false));
        }
    }

    /**
     * Mando una contraseña temporal al usuario con el correo puesto, no se manda la que está porque está con el md5,
     * solo se manda la temporal y se guarda esa en la base de datos.
     */
    public static function recuperarPassword($emailDestino)
    {
        if (empty(trim($emailDestino))) {
            Auxiliar::sendJson(400, array("error" => "Debes indicar la dirección de email", "ok" => false));
            return;
        }

        $dao = new UsuarioDAO();
        $usuario = $dao->obtenerPorEmail(trim($emailDestino));

        if (!$usuario) {
            Auxiliar::sendJson(404, array("error" => "No existe ningún usuario registrado con ese correo", "ok" => false));
            return;
        }

        // Genero una contraseña temporal aleatoria de 6 caracteres aplicando MD5 a la fecha/hora actual
        // time() me da los segundos actuales como semilla variable.
        // substr(..., 0, 6) recorta los primeros 6 caracteres para que sea fácil de recordar.
        $nuevaPass = substr(md5(time()), 0, 6);

        // Actualizo la base de datos con los datos actuales
        $dao->actualizar($usuario['id'], $usuario['user'], $usuario['edad']);

        $mail = new PHPMailer(true);
        try {
            // Configuro mi servidor SMTP de Gmail leyendo las credenciales de mi archivo .env
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('EMAIL');
            $mail->Password   = getenv('SMTP_PASSWORD');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->setFrom(getenv('EMAIL'), 'Juego de la Mosca');
            $mail->addAddress($emailDestino, $usuario['user']);

            $mail->isHTML(true);
            $mail->Subject = 'Recuperacion de contrasena';
            $mail->Body    = 'Hola <b>' . htmlspecialchars($usuario['user']) . '</b>,<br>Tu nueva contraseña temporal es: <b>' . $nuevaPass . '</b>';

            $mail->send();

            Auxiliar::sendJson(200, array("mensaje" => "Correo enviado correctamente a " . $emailDestino, "ok" => true));
        } catch (Exception $e) {
            Auxiliar::sendJson(500, array("error" => "No se pudo enviar el mensaje: {$mail->ErrorInfo}", "ok" => false));
        }
    }
}
?>