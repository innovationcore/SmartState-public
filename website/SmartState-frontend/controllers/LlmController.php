<?php
class LLMController {

    static function queryModel($messages, $maxNewTokens=200, $temperature=0.7): array {
        global $CONFIG;
        $openai_api_key = $CONFIG["llm_api_key"];
        $llm_api_url = $CONFIG["llm_api_url"];
        $model = $CONFIG["llm_model"];
        $header = array(
            "Authorization: Bearer $openai_api_key"
        );

        $postMessage = array(
            "messages" => $messages,
            "model" => $model,
            "max_tokens" => $maxNewTokens,
            "temperature" => $temperature,
        );

        return RootController::makeAPIcallPOST($llm_api_url, "/chat/completions", $postMessage, $header, 120);
    }

    // Callback function for handling POST requests
    static function openAIChatCompletions() {
        global $CONFIG;
        $success = false;
        $error_message = null;
        $response = null;

        $rawJsonData = file_get_contents("php://input");
        $postData = json_decode($rawJsonData, true);
        if (empty($postData)){
            $postData = $_POST;
        }

        $model = $CONFIG['llm_model'];
        $maxNewTokens = 200;
        $temperature = 0.7;

        try {
            if (isset($postData["messages"])){
                $messages = $postData["messages"];
                if (isset($postData["max_tokens"])){
                    $maxNewTokens = $postData["max_tokens"];
                }
                if (isset($postData["temperature"])){
                    $temperature = $postData["temperature"];
                }
                $modelResponse = self::queryModel($messages, $model, $maxNewTokens, $temperature);
                if (isset($modelResponse["success"]) && $modelResponse["success"] && isset($modelResponse["response"])){
                    $response = $modelResponse["response"];
                    $success = true;
                } else if (isset($modelResponse["error_message"])){
                    $error_message = $modelResponse["error_message"];
                } else {
                    $error_message = "Failed to communicate with API server";
                }
            } else {
                $error_message = "Must include messages in request";
            }
        } catch(Exception $e) {
            $error_message = $e->getMessage();
        }

        $ret = array(
            'success' => $success,
            'error_message' => $error_message,
            'response' => $response,
        );
        echo json_encode( (object)array_filter($ret, function ($value) {return $value !== null;}) );
    }
}