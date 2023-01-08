<?php

class TweetMedia
{
    protected string $key;
    protected string $url;
    protected string $width;
    protected string $height;

    public function __construct($key)
    {
        $this->key = $key;
    }

    public function getMediaInformation($medias)
    {
        try {
            $attachmentId = array();
            foreach ($medias as $index => $media) {
                if (($media['media_key'] === $this->key) && $media['type'] === 'photo') {
                    $this->url = $media['url'];
                    $this->width = $media['width'];
                    $this->height = $media['height'];
                }
            }

            return $attachmentId;

        } catch (Exception $exception) {
            error_log($exception->getMessage());
        }
    }

    public function uploadMedia($postId): WP_Error|int
    {
        try {
            $path = wp_parse_url($this->url, PHP_URL_PATH);
            $pathParts = pathinfo($path);
            $fileName = $this->key . '.' . $pathParts['extension'];
            $filePath = './uploads/' . $fileName;
            $args = array(
                'stream' => true,
                'filename' => $filePath
            );
            $response = wp_remote_get($this->url, $args);

            $fileType = wp_check_filetype(basename($filePath), null);
            $upload_dir = wp_upload_dir();
            $attachment = array(
                'guid' => $upload_dir['url'] . '/' . $fileName,
                'post_mime_type' => $fileType['type'],
                'post_status' => 'inherit',
                'post_title' => $this->key
            );
            return wp_insert_attachment($attachment, $filePath, $postId);

        } catch (Exception $exception) {
            error_log($exception->getMessage());
        }
    }
}