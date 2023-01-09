<?php

require_once ( ABSPATH . 'wp-admin/includes/file.php' );
require_once ( ABSPATH . 'wp-admin/includes/image.php' );

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

    /**
     * Get Media Information from Tweet Media Data
     * @param $medias
     * @return bool
     */
    public function getMediaInformation($medias): bool
    {
        try {
            foreach ($medias as $media) {
                if (($media['media_key'] === $this->key) && $media['type'] === 'photo') {
                    $this->url = $media['url'];
                    $this->width = $media['width'];
                    $this->height = $media['height'];
                }
            }
            return true;

        } catch (Exception $exception) {
            error_log($exception->getMessage());
            return false;
        }
    }

    /**
     * Get Media File from Tweet Media Data and attach to Post
     * @param $postId
     * @return bool|int
     */
    public function uploadMedia($postId): bool|int
    {
        try {
            $path = wp_parse_url($this->url, PHP_URL_PATH);
            $pathParts = pathinfo($path);
            $fileName = $pathParts['basename'];
            $upload_dir = wp_upload_dir();
            $filePath = $upload_dir['path'] . '/' . $fileName;

            $response = wp_remote_get($this->url);
            $media = $response['body'];

            if (!WP_Filesystem()) {
                throw new \RuntimeException('Failed to Load WP_Filesystem: Could not upload media file');
            }
            global $wp_filesystem;
            $wp_filesystem->put_contents($filePath, $media);

            $fileType = wp_check_filetype(basename($filePath), null);
            $attachment = array(
                'post_mime_type' => $fileType['type'],
                'post_status' => 'inherit',
                'post_content' => '',
                'post_title' => $this->key
            );
            $attachmentId = wp_insert_attachment($attachment, $filePath, $postId);

            if (is_wp_error($attachmentId)) {
                throw new \RuntimeException('Failed to insert attachment media file: Could not upload media file');
            }
            $attachData = wp_generate_attachment_metadata($attachmentId, $filePath);
            wp_update_attachment_metadata($attachmentId, $attachData);

            return $attachmentId;

        } catch (Exception $exception) {
            error_log($exception->getMessage());
            return false;
        }
    }
}