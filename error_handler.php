<?php
function logErrorToDatabase($errno, $errstr, $errfile, $errline) {
    require 'config.php'; // Use your actual DB connection file

    $error_type = match ($errno) {
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        default => 'Unknown',
    };

    $stmt = $conn->prepare("INSERT INTO error_logs (error_message, error_file, error_line, error_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $errstr, $errfile, $errline, $error_type);
    $stmt->execute();
    $stmt->close();
}

set_error_handler("logErrorToDatabase");

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        logErrorToDatabase($error['type'], $error['message'], $error['file'], $error['line']);
    }
});
?>
