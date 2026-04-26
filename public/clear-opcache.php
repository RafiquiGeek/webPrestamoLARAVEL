<?php
/**
 * Script para limpiar OPcache de PHP
 * Acceder desde: https://grupo.test/clear-opcache.php
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html><head><title>Clear OPcache</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;font-weight:bold;}.error{color:red;font-weight:bold;}</style>";
echo "</head><body>";
echo "<h1>🔧 Limpieza de OPcache</h1>";

// Verificar si OPcache está habilitado
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "<p class='success'>✅ OPcache limpiado exitosamente</p>";

        // Mostrar información de OPcache
        $status = opcache_get_status();
        echo "<h2>Estado de OPcache:</h2>";
        echo "<ul>";
        echo "<li><strong>OPcache habilitado:</strong> Sí</li>";
        echo "<li><strong>Cache lleno:</strong> " . ($status['cache_full'] ? 'Sí' : 'No') . "</li>";
        echo "<li><strong>Scripts en cache:</strong> " . $status['opcache_statistics']['num_cached_scripts'] . "</li>";
        echo "<li><strong>Hits:</strong> " . $status['opcache_statistics']['hits'] . "</li>";
        echo "<li><strong>Misses:</strong> " . $status['opcache_statistics']['misses'] . "</li>";
        echo "</ul>";
    } else {
        echo "<p class='error'>❌ Error al limpiar OPcache</p>";
    }
} else {
    echo "<p class='error'>⚠️ OPcache no está habilitado o no está disponible</p>";
}

echo "<hr>";
echo "<p><strong>Cache de Laravel ya fue limpiado previamente</strong></p>";
echo "<hr>";
echo "<p><a href='/admin/deudas' style='display:inline-block;padding:10px 20px;background:#4CAF50;color:white;text-decoration:none;border-radius:5px;'>Ir a Deudas</a></p>";

echo "</body></html>";
