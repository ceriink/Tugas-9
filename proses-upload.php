<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file = $_FILES['file_pdf'];

    $filename = $file['name'];
    $tmp_name = $file['tmp_name'];
    $filesize = $file['size'];
    $error = $file['error'];

    echo '<style>
        body {
            font-family: Arial, sans-serif;
            background-color: #fff8c6; /* kuning lembut */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .result-box {
            background-color: #ffffff;
            padding: 25px 35px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            width: 380px;
        }
        h3 { color: #333; margin-bottom: 15px; }
        p { margin: 6px 0; font-size: 14px; color: #444; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        code {
            background: #f1f1f1;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 13px;
        }
        a {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            color: #333;
            background-color: #f4c430;
            padding: 6px 14px;
            border-radius: 6px;
            transition: 0.2s;
            font-weight: bold;
        }
        a:hover {
            background-color: #e6b800;
        }
    </style>';

    echo '<div class="result-box">';

    if ($error === 0) {
        if ($filesize <= 10 * 1024 * 1024) {
            $filetype = mime_content_type($tmp_name);
            if ($filetype === 'application/pdf') {

                $username = "selvi";
                $epochtime = time();
                $new_filename = $username . "_" . $epochtime . "_" . basename($filename);

                $folder = "selvi/";
                if (!file_exists($folder)) {
                    mkdir($folder, 0777, true);
                }

                $destination = $folder . $new_filename;

                if (move_uploaded_file($tmp_name, $destination)) {

                    $db_success = saveToDatabase($destination, $filename);
                    
                    if ($db_success) {
                        echo "<h3 class='success'> File berhasil diupload!</h3>";
                        echo "<p>Nama file: <b>$new_filename</b></p>";
                        echo "<p>Lokasi: <code>$destination</code></p>";
                        echo "<p class='success'>✓ Data tersimpan di database</p>";
                    } else {
                        echo "<h3 class='error'> File berhasil diupload tapi gagal menyimpan ke database.</h3>";
                        echo "<p>Nama file: <b>$new_filename</b></p>";
                        echo "<p>Lokasi: <code>$destination</code></p>";
                    }

                } else {
                    echo "<h3 class='error'> Gagal memindahkan file ke server.</h3>";
                }

            } else {
                echo "<h3 class='error' Hanya file PDF yang diperbolehkan.</h3>";
            }

        } else {
            echo "<h3 class='error'> Ukuran file melebihi 10 MB.</h3>";
        }

    } else {
        echo "<h3 class='error'> Terjadi kesalahan saat mengunggah file.</h3>";
    }

    echo '<a href="form-upload.html">⬅ Kembali ke Form</a>';
    echo '</div>';
}

function saveToDatabase($file_path, $original_name) {
    $db_host = 'localhost';
    $db_user = 'root'; 
    $db_pass = '';
    $db_name = 'dbupload';
    
    try {
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS $db_name");
        $pdo->exec("USE $db_name");

        $create_table_sql = "CREATE TABLE IF NOT EXISTS document (
            id INT AUTO_INCREMENT PRIMARY KEY,
            path VARCHAR(500) NOT NULL,
            name VARCHAR(255) NOT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($create_table_sql);
        
        $sql = "INSERT INTO document (path, name) VALUES (:path, :name)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':path', $file_path);
        $stmt->bindParam(':name', $original_name);
        
        return $stmt->execute();
        
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}
?>