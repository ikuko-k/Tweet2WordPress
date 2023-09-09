<?php

class Tweet
{
    protected string $content;
    protected string $id;
    protected DateTime $created_at;
    public array $media;

    public function __construct($data)
    {
        $this->content = $data['text'];
        $this->id = $data['id'];
        $this->created_at = new DateTime($data['created_at']);
    }

    /**
     * Post Tweet to WordPress
     * @return void
     */
    public function postTweet()
    {
        try {
            // Get Category Object by Category Slug
            $category = get_category_by_slug(TWEET_CATEGORY_SLUG);

            // Search if Tweet is Posted already
            $args = array(
                'category' => $category->term_id,
                'meta_key' => 'TweetId',
                'meta_value' => $this->id
            );
            // If Posted already then throw Exception and end process
            if (!empty(get_posts($args))) {
                throw new \RuntimeException('Tweet Already Posted.');
            }

            // Set Post Attributes and insert Post
            $attr = array(
                'post_content' => $this->content,
                'post_title' => 'Tweet from ' . TWITTER_USER_NAME . '@' . $this->created_at->format('y-m-d H:i:s'),
                'post_date_gmt' => $this->created_at->format('y-m-d H:i:s'),
                'post_category' => array($category->term_id),
                'post_status' => 'publish'
            );
            $postId = wp_insert_post($attr);
            if ($postId === 0 || is_wp_error($postId)) {
                throw new \RuntimeException('Failed to post tweet to WordPress: Could not post Tweet');
            }

            // Add Post Meta to define tweet
            add_post_meta($postId, 'TweetId', $this->id);

            // Upload Medias attached to Tweet and add Post Thumbnail
            if (isset($this->media)) {
                foreach ($this->media as $index => $media) {
                    $attachmentId = $media->uploadMedia($postId);
                    if ($index === 0) {
                        set_post_thumbnail($postId, $attachmentId);
                    }
                }
            } else {
				$uploadDir = wp_upload_dir();
				$defaultThumbnailUrl = $uploadDir->url . getenv(DEFAULT_THUMBNAIL_FILENAME);
				$defaultThumbnailId = attachment_url_to_postid($defaultThumbnailUrl);
				set_post_thumbnail($postId, $defaultThumbnailId);
            }

            // Make Post Content with Media and Links and update Post
            $postContent = $this->makePostContent($postId);
            $postAttr = array(
                'ID' => $postId,
                'post_content' => $postContent,
                'post_title' => 'Tweet from ' . TWITTER_USER_NAME . '@' . $this->created_at->format('y-m-d H:i:s'),
                'post_status' => 'publish'
            );
            $postId = wp_insert_post($postAttr);
            if (is_wp_error($postId)) {
                throw new \RuntimeException('Failed to update post with medias: Could not update post');
            }

        } catch (Exception $exception) {
            error_log($exception->getMessage());
        }
    }

    /**
     * Make Post Content
     * @param $postId
     * @return string|bool
     */
    private function makePostContent($postId)
    {
        try {
            // Get Post attached Medias
            $args = array(
                'post_type' => 'attachment',
                'post_parent' => $postId,
                'post_mime_type' => 'image'
            );
            $attachments = get_children($args);

            // Make Media Content
            $mediaContent = '';
            foreach ($attachments as $attachment) {
                $mediaContent .= wp_get_attachment_image($attachment->ID);
            }

            // Convert url included to link
            $postContent = make_clickable($this->content);
            // Add Media Content and else
            $postContent .= '<br>';
            $postContent .= '<div class="media-content">';
            $postContent .= $mediaContent;
            $postContent .= '</div>';
            $postContent .= '<div class="post-footer">Tweet from <a href="https://twitter.com/"' . TWITTER_USER_NAME . '/" target="_blank">' . TWITTER_USER_NAME . '</a></div>';

            return $postContent;

        } catch (Exception $exception) {
            error_log($exception->getMessage());
            return false;
        }
    }
}