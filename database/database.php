<?php
function cargarVariablesEntorno()
{
    $envFile = __DIR__ . '/../.env';

    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            $esComentario = strpos($line, '#') === 0;
            $tieneIgual = strpos($line, '=') !== false;

            if (!$esComentario && $tieneIgual) {
                $partes = explode('=', $line, 2);
                $nombre = trim($partes[0]);
                $valor = trim($partes[1]);

                putenv($nombre . '=' . $valor);
            }
        }
    }
}
cargarVariablesEntorno();

class Database
{
    public function connect()
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {

            $host = getenv('DB_HOST');
            $user = getenv('DB_USERNAME');
            $pass = getenv('DB_PASSWORD');
            $db = getenv('DB_DATABASE');
            $port = getenv('DB_PORT');


            return new mysqli(
                $host,
                $user,
                $pass,
                $db,
                $port
            );
        } catch (mysqli_sql_exception $e) {
            throw $e;
        }
    }

}
?>