<?php

class Auxiliar
{
    /**
     * Creo esta función estática para enviar respuestas estructuradas en formato JSON.
     * Configuro el código de estado HTTP adecuado e interrompo la ejecución con exit()
     * para asegurarme de que el script no continúe procesando código involuntariamente.
     */
    public static function sendJson($code, $data = null)
    {
        header("Content-Type: application/json");
        http_response_code($code);
        echo json_encode($data);
        exit(); // Corto el flujo del programa tras responder al cliente
    }

    /**
     * Obtengo el identificador del usuario (usuario_id) directamente desde el Body JSON de la petición.
     * Como mi API es stateless (sin estado/sesiones), exijo que cada petición se identifique individualmente.
     */
    public static function obtenerUsuarioId($datos)
    {
        // Compruebo con isset() si el campo usuario_id viene en los datos recibidos y si es numérico
        if (isset($datos['usuario_id']) && is_numeric($datos['usuario_id'])) {
            return intval($datos['usuario_id']);
        }

        // Si no me envían el usuario_id, devuelvo un error de no autorizado (401)
        self::sendJson(401, array(
            "error" => "No identificado: Debes incluir 'usuario_id' en el body de la petición",
            "ok" => false
        ));
    }
}
?>