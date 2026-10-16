<?php
class Auxiliar
{
    public static function sendJson($code, $data = null)
    {
        header("Content-Type: application/json");
        http_response_code($code);
        echo json_encode($data);
    }

    public static function obtenerUsuarioId($datos)
    {
        if (isset($datos['usuario_id']) && is_numeric($datos['usuario_id'])) {
            return intval($datos['usuario_id']);
        }

        self::sendJson(401, [
            "error" => "No identificado: Debes incluir 'usuario_id' en el body de la petición",
            "ok" => false
        ]);
    }
}
?>