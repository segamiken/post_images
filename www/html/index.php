<?php

    //error内容をブラウザ画面にも表示する！
    ini_set('display_errors', 1);

    define('MAX_FILE_SIZE', 1 * 1024 *1024); //1MB
    define('THUMBNAIL_WIDTH', 400);
    define('IMAGES_DIR', __DIR__ . '/images');
    define('THUMBNAIL_DIR', __DIR__ . '/thubms');

    //GDがインストールされているかどうかのチェック
    // if (!function_exists('imagecreatetruecolor')) {
    //     echo 'GD not installed';
    //     exit;
    // }

    function h($s) {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    require 'ImageUploader.php';
    $uploader = new \MyApp\ImageUploader();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $uploader->upload();
    }
    
    $images = $uploader->getImages();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Post Imgage App</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- 画像投稿用のフォーム-->
    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo h(MAX_FILE_SIZE); ?>">
        <input type="file" name="image">
        <input type="submit" value="upload">
    </form>

    <ul>
        <?php foreach ($images as $image) : ?>
        <li>
            <a href="<?php echo h(basename(IMAGES_DIR)) . '/' . basename($image); ?>">
                <img src="<?php echo h($image); ?>">
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>

