<?php
	public function get_news_discord()
	{
		$newsChannelID = ['5656xxxxxxxxxxx676', '8660xxxxxxxxxx6770'];
		require_once(DISCORD_BWS_PATH . "includes/lib/discord/Autoload.php");
		$configs = include(DISCORD_BWS_PATH . "includes/lib/discord/Configs.php");
		$discord = new \Discord\Client(new \Discord\Configs($configs));

		$channel_last_mmesage_id = get_option('bws_news_last_message');
		$dbOption = unserialize($channel_last_mmesage_id);
		if (!is_array($dbOption))
			$dbOption = [];

		foreach ($newsChannelID as $ChannelID) {

			$channelsOBJ = new \Discord\Client\Channels($discord, $ChannelID);
			$apiLastMessage = null;

			if ($dbOption[$ChannelID])
				$apiLastMessage = $dbOption[$ChannelID];

			$messages = $channelsOBJ->messages(NULL, $apiLastMessage);
			$messages = array_reverse($messages);

			foreach ($messages as $message) {
				$post_id = $this->bws_get_post_id_by_meta_key_and_value('discord_message_id', $message->id);
				if ($post_id) {
					$dbOption[$ChannelID] = $message->id;

					if (!has_post_thumbnail($post_id)) {
						$imageURL = isset($message->embeds[0]->image->url) ? $message->embeds[0]->image->url : $message->embeds[0]->thumbnail->url;
						if (isset($imageURL)) {
							$this->upload_image($imageURL, $post_id);
						}
					}
				} else {
					$post_id = wp_insert_post(array(
						'post_type' => 'post',
						'post_title' => $message->embeds[0]->title,
						'post_content' => $message->embeds[0]->description,
						'post_status' => 'publish',
						'comment_status' => 'closed',
						'ping_status' => 'closed',
					));

					update_post_meta($post_id, 'discord_message_id', $message->id);
					update_post_meta($post_id, 'discord_message_user_is', $message->author->id);
					update_post_meta($post_id, 'discord_message_user_name', $message->author->username);

					$imageURL = isset($message->embeds[0]->image->url) ? $message->embeds[0]->image->url : $message->embeds[0]->thumbnail->url;

					if (isset($imageURL)) {
						$this->upload_image($imageURL, $post_id);
					} else {
						$dbOption[$ChannelID] = $message->id;
					}
					wp_set_post_tags($post_id, 'stock');
				}
			}
		}
		update_option('bws_news_last_message', serialize($dbOption));
	}
