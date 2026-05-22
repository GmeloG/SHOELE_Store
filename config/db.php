<?php
/**
 * Configuração e ligação à base de dados via PDO
 */

define('DB_HOST',    getenv('DB_HOST') ?: 'db');
define('DB_NAME',    getenv('DB_NAME') ?: 'sneakerstock');
define('DB_USER',    getenv('DB_USER') ?: 'sneaker_user');
define('DB_PASS',    getenv('DB_PASS') ?: 'sneaker_pass');
define('DB_CHARSET', 'utf8mb4');

/**
 * Devolve a instância PDO (singleton)
 * Usa prepared statements e modo de erro por exceção
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Em produção, evitar expor detalhes do erro
            error_log('DB Connection Error: ' . $e->getMessage());
            die('<div style="padding:2rem;font-family:sans-serif;color:#c00">
                <h2>Erro de ligação à base de dados</h2>
                <p>O servidor de base de dados não está disponível. Verifique se o Docker está a correr.</p>
                </div>');
        }
    }

    return $pdo;
}
