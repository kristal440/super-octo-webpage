<?php
/**
 * Setup script to create all necessary upload directories
 * Run this file once to set up the folder structure
 */

// Define all required directories
$directories = [
    'uploads',
    'uploads/materials',
    'uploads/submissions',
];

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Setup Folders</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
    .success { color: #27ae60; background: #e8f5e9; padding: 10px; margin: 10px 0; border-radius: 5px; }
    .error { color: #e74c3c; background: #ffebee; padding: 10px; margin: 10px 0; border-radius: 5px; }
    .info { color: #3498db; background: #e3f2fd; padding: 10px; margin: 10px 0; border-radius: 5px; }
    h1 { color: #2c3e50; }
    .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
    .btn:hover { background: #2980b9; }
</style>";
echo "</head><body>";
echo "<h1>🗂️ Nastavitev map za nalaganje datotek</h1>";

$all_success = true;

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "<div class='success'>✓ Mapa ustvarjena: <strong>{$dir}</strong></div>";
            // Try to set permissions explicitly
            @chmod($dir, 0777);
        } else {
            echo "<div class='error'>✗ Napaka pri ustvarjanju mape: <strong>{$dir}</strong></div>";
            echo "<div class='info'>Poskusite ročno ustvariti mapo z ukazom: <code>mkdir -p {$dir} && chmod 777 {$dir}</code></div>";
            $all_success = false;
        }
    } else {
        echo "<div class='info'>ℹ️ Mapa že obstaja: <strong>{$dir}</strong></div>";
        // Try to ensure it's writable
        if (is_writable($dir)) {
            echo "<div class='success'>✓ Mapa je zapisljiva</div>";
        } else {
            echo "<div class='error'>✗ Mapa ni zapisljiva! Nastavite dovoljenja.</div>";
            echo "<div class='info'>Uporabite ukaz: <code>chmod 777 {$dir}</code></div>";
            $all_success = false;
        }
    }
}

echo "<hr>";

if ($all_success) {
    echo "<div class='success'><strong>✓ Vse mape so uspešno nastavljene!</strong></div>";
    echo "<p>Sedaj lahko učitelji nalagajo gradiva in učenci oddajajo naloge.</p>";
} else {
    echo "<div class='error'><strong>⚠️ Nekatere mape niso pravilno nastavljene.</strong></div>";
    echo "<p>Poskusite ročno ustvariti mape z naslednjimi ukazi v terminalu:</p>";
    echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
    echo "mkdir -p uploads/materials\n";
    echo "mkdir -p uploads/submissions\n";
    echo "chmod -R 777 uploads/\n";
    echo "</pre>";
}

echo "<h2>Informacije o sistemu</h2>";
echo "<div class='info'>";
echo "<strong>Trenutna mapa:</strong> " . getcwd() . "<br>";
echo "<strong>PHP verzija:</strong> " . PHP_VERSION . "<br>";
echo "<strong>Največja velikost naložene datoteke:</strong> " . ini_get('upload_max_filesize') . "<br>";
echo "<strong>Največja velikost POST podatkov:</strong> " . ini_get('post_max_size') . "<br>";
echo "</div>";

echo "<a href='admin_dashboard.php' class='btn'>← Nazaj na Admin Dashboard</a>";
echo "<a href='login.php' class='btn'>← Nazaj na Prijavo</a>";

echo "</body></html>";
?>
