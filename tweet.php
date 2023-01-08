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

    public function postTweet(): void
    {
        try {
            $category = get_category_by_slug(TWEET_CATEGORY_SLUG);
            $args = array(
                'category' => $category->term_id,
                'meta_key' => 'TweetId',
                'meta_value' => $this->id
            );
            if (!empty(get_posts($args))) {
               throw new \RuntimeException('Tweet Already Posted.');
            }
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

            add_post_meta($postId, 'TweetId', $this->id);
            if (isset($this->media)) {
                foreach ($this->media as $index => $media) {
                    $attachmentId = $media->uploadMedia($postId);
                    if ($index === 0) {
                        set_post_thumbnail($postId, $attachmentId);
                    }
                }
            }
        } catch (Exception $exception) {
            error_log($exception->getMessage());
        }
    }
}