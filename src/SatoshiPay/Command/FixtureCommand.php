<?php
/**
 * This file is part of the SatoshiPay WordPress plugin.
 *
 * (c) SatoshiPay <hello@satoshipay.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SatoshiPay\Command;

use WP_CLI;
use WP_CLI_Command;

/**
 * Manage fixtures.
 */
class FixtureCommand extends WP_CLI_Command
{
    /**
     * @var integer
     */
    protected $count = 100;

    /**
     * @var array
     */
    protected $statuses = array(
        'draft',
        'publish',
    );

    /**
  	 * Create posts.
  	 *
  	 * ## OPTIONS
  	 *
  	 * [--count=<number>]
  	 * : Count of fixtures to create. Default: 100
  	 *
  	 * ## EXAMPLES
  	 *
  	 *     wp fixture create-posts --count=100
     *
     * @subcommand create-posts
  	 */
    public function createPosts($args, $assoc_args)
    {
        $defaults = array(
            'count' => $this->count,
        );
        // Merge default and cli arguments and create variables with the
        // corrensponding $name=value
        extract(array_merge($defaults, $assoc_args));

        for ($i = 1; $i <= $count; $i++) {
            $response = wp_remote_get('http://loripsum.net/api/5/plaintext');
            $responseData = wp_remote_retrieve_body($response);

            // Check for error
			      if (is_wp_error($responseData)) {
    	          WP_CLI::error($responseData);
                continue;
			      }

            // Remove first sentence because its always the same "Lorem ipsum ..."
            $postContent = preg_replace('/^[^{.?!}]+[{.?!}]/', '', $responseData);
            $postTitle = hash('sha256', uniqid());
            if (preg_match('/^([^{.\?\!}]+)[{.?!}]/', $postContent, $matches)) {
                $postTitle = substr(trim($matches[1]), 0, 50);
            }
            $postStatus = $this->statuses[array_rand($this->statuses)];

      			$postId = wp_insert_post(
                array(
            				'post_type' => 'post',
            				'post_title' => $postTitle,
            				'post_status' => $postStatus,
            				'post_content' => $postContent,
          			),
                true
            );

            // Check for error
      			if (is_wp_error($postId)) {
    				    WP_CLI::warning($postId);
                continue;
      			}

            WP_CLI::success('Created post "' . $postTitle . '" (ID: ' . $postId . ', status: ' . $postStatus . ').');
        }
    }
}

WP_CLI::add_command('fixture', 'SatoshiPay\Command\FixtureCommand');
