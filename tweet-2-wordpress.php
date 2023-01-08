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

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tweet2WordPress {
    public $conf = array(
    );

    protected $consumerKey = '';
    protected $consumerSecret = '';
    protected $accessToken = '';
    protected $accessTokenSecret = '';
    protected $twitterUserId = '';

    /**
     * Constructor
     */
    public function __construct () {
        // Read Twitter API Keys
        $consumerKey = getenv('CONSUMER_KEY');
        $consumerSecret = getenv('CONSUMER_SECRET_KEY');
        $accessToken = getenv('ACCESS_TOKEN');
        $accessTokenSecret = getenv('ACCESS_TOKEN_SECRET');
        $twitterUserId = getenv('TWITTER_USER_ID');



    }


    private function challengeOAuth2PKCE() {

    }

    private function generateCodeVerifier() {

    }
}