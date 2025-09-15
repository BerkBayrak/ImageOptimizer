<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    header('Content-Type: application/json');

    $uploadedFiles = $_FILES['images'];
    $quality = isset($_POST['quality']) ? intval($_POST['quality']) : 75;
    $quality = max(10, min(100, $quality));

    $optimizedDir = 'downloads';
    if (!file_exists($optimizedDir)) mkdir($optimizedDir, 0777, true);

    $zip = new ZipArchive();
    $zipName = $optimizedDir . '/optimized_images.zip';
    if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create ZIP archive.']);
        exit;
    }

    $totalFiles = count($uploadedFiles['tmp_name']);
    $addedFiles = 0;
    $progressData = [];

    foreach ($uploadedFiles['tmp_name'] as $index => $tmpName) {
        $originalName = pathinfo($uploadedFiles['name'][$index], PATHINFO_FILENAME);
        $imageType = exif_imagetype($tmpName);
        if (!$imageType) continue;

        switch ($imageType) {
            case IMAGETYPE_JPEG: $image = imagecreatefromjpeg($tmpName); break;
            case IMAGETYPE_PNG: $image = imagecreatefrompng($tmpName); break;
            case IMAGETYPE_GIF: $image = imagecreatefromgif($tmpName); break;
            default: continue 2;
        }

        $webpPath = $optimizedDir . '/' . $originalName . '.webp';
        imagewebp($image, $webpPath, $quality);
        imagedestroy($image);

        $zip->addFile($webpPath, basename($webpPath));
        $addedFiles++;

        $progressData[] = [
            'file' => $originalName,
            'current' => $addedFiles,
            'total' => $totalFiles
        ];
    }

    $zip->close();

    if ($addedFiles > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'All images converted to WebP and added to ZIP',
            'zip' => $zipName,
            'progress' => $progressData
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No valid images were uploaded.']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Image Optimizer</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {
    background-color: #fff;
    color: #000;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    font-family: 'Segoe UI', sans-serif;
    padding: 15px;
}

.card {
    background-color: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    max-width: 500px;
    width: 100%;
    padding: 20px;
}

.form-control {
    margin-bottom: 15px;
}

.form-control:focus {
    outline: none;
    box-shadow: none;
    border-color: #ff0000;
}

.btn-primary {
    background-color: #fb0000ff;
    color: #fff;
    font-weight: bold;
    border-radius: 8px;
    padding: 10px 0;
    transition: background 0.3s ease;
    border: 1px solid #000;
}

.btn-primary:hover {
    background-color: #d30f0fff;
}

.progress {
    height: 20px;
    border-radius: 10px;
    background-color: #f0f0f0;
    margin-bottom: 10px;
}

.progress-bar {
    border-radius: 10px;
    background-color: #ff0000;
    color: #fff;
    font-weight: bold;
    transition: width 0.3s ease;
}

label,h4 {
    color: #000;
}



#notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background-color: #fff;
    border-radius: 10px;
    padding: 15px 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    display: none;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    min-width: 250px;
}

.download-btn {
    width: 100%;
    justify-content: center;
    text-align: center;
    background-color: #ff4d4d;
    padding: 8px 0;
    border-radius: 8px;
    color: #fff;
    font-weight: bold;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: background 0.3s ease;
}

.download-btn:hover {
    background-color: #ff0000;
}
</style>
</head>
<body>

<div class="card shadow-sm">
    <div class="card-body">
        <h4>Upload Images & Optimize</h4>
        <form id="optimizerForm" enctype="multipart/form-data">
            <input type="file" name="images[]" multiple accept="image/*" class="form-control" required>
            <label for="quality" class="form-label">WebP Quality (%):</label>
            <input type="number" name="quality" value="75" min="10" max="100" class="form-control">
            <div id="progressWrapper" style="display:none;">
                <div class="progress">
                    <div id="progressBar" class="progress-bar" style="width:0%">0%</div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Optimize</button>
        </form>
    </div>
</div>

<div id="notification">
    <span id="notifMessage"></span>
    <a id="downloadLink" href="#" class="download-btn"><i class="bi bi-download"></i> Download ZIP</a>
</div>

<script>
const form = document.getElementById('optimizerForm');
const progressWrapper = document.getElementById('progressWrapper');
const progressBar = document.getElementById('progressBar');
const notification = document.getElementById('notification');
const notifMessage = document.getElementById('notifMessage');
const downloadLink = document.getElementById('downloadLink');

form.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(form);
    progressWrapper.style.display = 'block';
    progressBar.style.width = '0%';
    progressBar.textContent = '0%';
    notification.style.display = 'none';
    downloadLink.style.display = 'none';

    fetch('', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const total = data.progress.length;
            if (total > 0) {
                data.progress.forEach((item, index) => {
                    setTimeout(() => {
                        const percent = Math.round(((index + 1) / total) * 100);
                        progressBar.style.width = percent + '%';
                        progressBar.textContent = percent + '%';
                    }, 200 * index);
                });
                setTimeout(() => {
                    notifMessage.textContent = data.message;
                    notification.style.display = 'flex';
                    downloadLink.href = data.zip;
                    downloadLink.style.display = 'inline-flex';
                    progressBar.style.width = '100%';
                    progressBar.textContent = '100%';
                }, total * 200 + 300);
            }
        } else {
            notifMessage.textContent = data.message;
            notification.style.display = 'flex';
            progressBar.style.width = '0%';
            progressBar.textContent = '0%';
        }
    })
    .catch(err => {
        notifMessage.textContent = 'An error occurred.';
        notification.style.display = 'flex';
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';
        console.error(err);
    });
});
</script>

</body>
</html>
