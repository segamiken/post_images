<?php
    namespace MyApp;

    class ImageUploader {
        
        private $_imageFileName;
        private $_imageType;

        //index.phpから最初に呼ばれるメソッド
        public function upload() {
            try {
                //error check
                $this->_validateUpload();

                //type check
                $ext = $this->_validateImageType();

                //save
                $savePath = $this->_save($ext);

                //create thumbnail
                $this->_createThumbnail($savePath);

                $_SESSION['success'] = 'Upload Done!!';

            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                exit;
            }
            //redirect
            header('Location: http://' . $_SERVER['HTTP_HOST']);
            exit;
        }

        //サクセス、エラーを取得
        public function getResults() {
            $success = null;
            $error = null;
            if (isset($_SESSION['success'])) {
                $success = $_SESSION['success'];
                unset($_SESSION['success']);
            }
            if (isset($_SESSION['error'])) {
                $error = $_SESSION['error'];
                unset($_SESSION['error']);
            }
            return [$success, $error];
        }

        //表示する画像のデータを取得して$imagesを返す
        public function getImages() {
            $images = [];
            $files = [];
            $imageDir = opendir(IMAGES_DIR);
            while (false !== ($file = readdir($imageDir))) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $files[] = $file;
                if (file_exists(THUMBNAIL_DIR . '/' . $file)) {
                    $images[] = basename(THUMBNAIL_DIR) . '/' . $file;
                } else {
                    $images[] = basename(IMAGES_DIR) . '/' . $file;
                }
                
            }
            array_multisort($files, SORT_DESC, $images);
            return $images;
        }

        //サムネイルを作成 400pxより大きければMainメソッドに処理を渡す
        private function _createThumbnail($savePath) {
            $imagesize = getimagesize($savePath);
            $width = $imagesize[0];
            $height = $imagesize[1];
            if ($width > THUMBNAIL_WIDTH) {
                $this->_createThumbnailMain($savePath, $width, $height);
            }
        }

        //ファイルタイプに応じてサムネイルを作成して保存する！
        private function _createThumbnailMain($savePath, $width, $height) {
            switch($this->_imageType) {
                case IMAGETYPE_GIF:
                    $srcImage = imagecreatefromgif($savePath);
                    break;
                case IMAGETYPE_JPEG:
                    $srcImage = imagecreatefromjpeg($savePath);
                    break;
                case IMAGETYPE_PNG:
                    $srcImage = imagecreatefrompng($savePath);
                    break;
            }
            $thumbHeight = round($height * THUMBNAIL_WIDTH / $width);
            $thumbImage = imagecreatetruecolor(THUMBNAIL_WIDTH, $thumbHeight);
            imagecopyresampled($thumbImage, $srcImage, 0, 0, 0, 0, THUMBNAIL_WIDTH,
                                $thumbHeight, $width, $height);

            switch($this->_imageType) {
                case IMAGETYPE_GIF:
                    imagegif($thumbImage, THUMBNAIL_DIR . '/' . $this->_imageFileName);
                    break;
                case IMAGETYPE_JPEG:
                    imagejpeg($thumbImage, THUMBNAIL_DIR . '/' . $this->_imageFileName);
                    break;
                case IMAGETYPE_PNG:
                    imagepng($thumbImage, THUMBNAIL_DIR . '/' . $this->_imageFileName);
                    break;
            }
        }


        //画像の保存先のパスを指定してそのパス($savePath)を返り値として返す
        private function _save($ext) {
            $this->_imageFileName = sprintf(
                '%s_%s.%s',
                time(),
                sha1(uniqid(mt_rand(), true)),
                $ext
            );
            $savePath = IMAGES_DIR . '/' . $this->_imageFileName;
            $res = move_uploaded_file($_FILES['image']['tmp_name'], $savePath);
            if ($res === false) {
                throw new \Exception('Could not upload');
            }
            return $savePath;
        }

        //ファイルのタイプをチャックしてエラー文を返す！
        //TO DO::exid_imagetypeがno method error.....php.iniを編集するも解決せず。
        private function _validateImageType() {
            $this->_imageType = exif_imagetype($_FILES['image']['tmp_name']);
            switch($this->_imageType) {
                case IMAGETYPE_GIF:
                    return 'gif';
                case IMAGETYPE_JPEG:
                    return 'jpg';
                case IMAGETYPE_PNG:
                    return 'png';
                default:
                    throw new \Exception('PNG/JPEF/PNG only!');
            }
        }

        //ファイルの大きさ等をチャックしてエラー分を返す！
        private function _validateUpload() {
            if (!isset($_FILES['image']) ||
                !isset($_FILES['image']['error'])) {
                throw new \Exception('Upload Error!');
            }

            switch($_FILES['image']['error']) {
                case UPLOAD_ERR_OK:
                    return true;
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new \Exception('File too large!');
                default:
                    throw new \Exception('Err!: '. $_FILES['image']['error']);
            }
        }
    }
?>