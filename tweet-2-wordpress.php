<?php
/**
 * @package     Tweet 2 WordPress
 * @version     1.0
 * @author      Ikuko KAI <ichan.kai@gmail.com>
 * @license     GPL-2.0+
 * @link        https://blog.seitou.jp
 * @copyright   2023 Ikuko KAI
 *
 * @wordpress-plugin
 * Plugin Name:     Tweet 2 WordPress
 * Description:     Post Tweet contents to WordPress
 * Version:         1.0
 * Plugin URI:      https://github.com/
 * Author:          Ikuko KAI <ichan.kai@gmail.com>
 * Author URI:      https://blog.seitou.jp/
 * License:         GPLv2
 * Text Domain:     tweet-2-wordpress
 * Domain Path:     /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once( 'tweet2wp-config.php' );
require_once( 'tweet.php' );
require_once( 'tweet-media.php' );

class Tweet2WordPress
{
    protected string $bearerToken = BEARER_TOKEN;
    protected string $twitterUserName = TWITTER_USER_NAME;
    protected string $twitterAPIBaseUrl = 'https://api.twitter.com/2';

    /**
     * Constructor
     */
    public function __construct()
    {
    }


    /**
     * getRecentTweets
     * Get Recent Tweets by User
     * @param $userId
     * @return void
     */
    private function getRecentTweets($userId): void
    {
        try {
            // Make Twitter API Endpoint URL
            $requestUrl = $this->twitterAPIBaseUrl . '/users/' . $userId . '/tweets?';

            // Make Request Parameters
            $params = array(
                'tweet.fields' => 'author_id,created_at',
                'media.fields' => 'url,preview_image_url,width,height,alt_text',
                'expansions' => 'attachments.media_keys',
            );
            // Make Request Arguments
            $args = array(
                'headers' => array(
                    'authorization' => 'Bearer ' . $this->bearerToken,
                ),
                'user-agent' => 'tweet2wp'
            );

            // Get Response from Twitter API
            $response = wp_remote_get($requestUrl . build_query($params), $args);
            if (200 === wp_remote_retrieve_response_code($response)) {
                $responseBody = json_decode(wp_remote_retrieve_body($response), true, 512, JSON_THROW_ON_ERROR);
            } else {
                throw new \RuntimeException('Failed to get response from twitter api: Could not get tweets');
            }

            $tweetData = $responseBody['data'];
            $tweetMedias = $responseBody['includes']['media'];

            // Proceed Tweets and Post to WordPress
            foreach ($tweetData as $data) {
                $tweet = new Tweet($data);
                if (!empty($data['attachments']['media_keys'])) {
                    foreach ($data['attachments']['media_keys'] as $index => $key) {
                        $tweet->media[$index] = new TweetMedia($key);
                        $tweet->media[$index]->getMediaInformation($tweetMedias);
                    }
                }
                $tweet->postTweet();
            }

        } catch (Exception $exception) {
            error_log($exception->getMessage());
        }
    }

    /**
     * getUserIdByUserName
     * Get Twitter UserID by User Name
     * @return mixed|void
     */
    private function getUserIdByUserName()
    {
        try {
            // Make Twitter API Endpoint URL
            $requestUrl = $this->twitterAPIBaseUrl . '/users/by/username/' . $this->twitterUserName;
            // Make Request Arguments
            $args = array(
                'headers' => array(
                    'authorization' => 'Bearer ' . $this->bearerToken,
                ),
                'user-agent' => 'tweet2wp'
            );

            // Get response from Twitter API
            $response = wp_remote_get($requestUrl, $args);
            if (200 === wp_remote_retrieve_response_code($response)) {
                $responseBody = json_decode(wp_remote_retrieve_body($response), true, 512, JSON_THROW_ON_ERROR);
            } else {
                throw new \RuntimeException('Failed to get response from twitter api; Could not get user information');
            }

            return $responseBody['data']['id'];

        } catch (Exception $exception) {
            error_log($exception->getMessage());
        }
    }

    /**
     * Main
     * @return void
     */
    public static function index(): void
    {
        $self = new Tweet2WordPress();
        $userId = $self->getUserIdByUserName();
        $self->getRecentTweets($userId);
    }
}


if (!(wp_next_scheduled('schedule_twitter_request'))) {
    add_action('schedule_twitter_request', array('Tweet2WordPress', 'index'));
    wp_schedule_event(strtotime('2023-01-08 10:20:00'), 'hourly', 'schedule_twitter_request');
}

add_action('test_twitter_request', array('Tweet2WordPress', 'index'));
wp_schedule_single_event(strtotime('2023-01-08 13:45:00'), 'test_twitter_request');
