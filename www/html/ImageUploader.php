<?php
    namespace MyApp;

    class ImageUploader {
        
        private $_imageFileName;
        private $_imageType;

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

            } catch (\Exception $e) {
                echo $e->getMessage();
                exit;
            }
            //redirect
            header('Location: http://' . $_SERVER['HTTP_HOST']);
            exit;
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



        //画像の保存先のパスを指定してそのパスを返り値として返す
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