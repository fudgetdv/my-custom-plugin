<?php
/*
 Plugin Name: My Custom Plugin
 Description: Securely manages API key and OpenAI functionality.
 Version: 1.0
 Author: Your Name
*/

function my_custom_openai_request($prompt) {
    $api_key = get_option('my_api_key');
    error_log('API Key: ' . $api_key);
    if (!$prompt) {
        return '';
    }
    if ($api_key && $prompt) {
        $response = wp_remote_post('https://gab.ai/v1/chat/completions', [
            'headers' => ['Authorization' => 'Bearer ' . $api_key, 'Content-Type' => 'application/json'],
            'body' => json_encode([
                'model' => 'gab-ai',
                'messages' => [
                    ['role' => 'system', 'content' => 'Respond with a varied one-word adjective about the flame movement (e.g., fierce, gentle, flickering, roaring, dancing, calm, wild, steady) followed by a concise one sentence mood of the room.

'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 50,
            ]),
            'timeout' => 30,
        ]);
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            $response_text = trim($data['choices'][0]['message']['content']);
            // Validate format: one-word adjective, comma, sentence
            if (strpos($response_text, ',') !== false) {
                list($adjective_part, $sentence) = explode(',', $response_text, 2);
                $adjective = trim($adjective_part);
                $sentence = trim($sentence);
                return "Flame is $adjective, $sentence.";
            }
        }
    }
    return 'Flame is sleeping, silence'; // Default if API fails or response is invalid
}

add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/openai/', [
        'methods' => 'POST',
        'callback' => function ($request) {
            $prompt = sanitize_text_field($request->get_param('prompt'));
            return ['message' => my_custom_openai_request($prompt)];
        },
        'permission_callback' => '__return_true',
    ]);
});