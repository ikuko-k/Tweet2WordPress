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

class Tweet2WordPress
{
    protected string $bearerToken = '';
    protected string $twitterUserName = '';
    protected string $twitterUserId = '';

    protected string $twitterAPIBaseUrl = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        // Read Twitter API Keys
        $this->twitterUserName = getenv('TWITTER_USER_NAME');
        $this->bearerToken = getenv('BEARER_TOKEN');

        // Read Twitter API Endpoints
        $this->twitterAPIBaseUrl = getenv('TWITTER_ENDPOINT');
    }

    private function getRecentTweets($userId)
    {
        try {
            // Make Twitter API Endpoint URL
            $requestUrl = $this->twitterAPIBaseUrl . '/users/' . $userId . '/tweets';

            // Make Request Parameters
            $params = array(
                'tweet.fields' => 'author_Id,created_at,',
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
                throw new \RuntimeException('Failed to get response from twitter api');
            }

            error_log(var_export($responseBody, true));


        } catch (Exception $exception) {
            error_log($exception->getMessage());
        }

    }

    private function getUserIdByUserName() {
        try {
            // Make Twitter API Endpoint URL
            $requestUrl = $this->twitterAPIBaseUrl . 'users/by/username/' . $this->twitterUserName;
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
                throw new \RuntimeException('Failed to get response from twitter api');
            }

            error_log(var_export($responseBody, true));

            return $responseBody['id'];

        } catch (Exception $exception) {
            error_log($exception->getMessage());
        }
    }

    public function index(): void
    {
        $userId = $this->getUserIdByUserName();
        $this->getRecentTweets($userId);
    }
}

add_action('schedule_twitter_request', array('Tweet2WordPress', 'index'));

wp_schedule_event(strtotime('today'), 'hourly', 'schedule_twitter_request');