<?php

    namespace Core\ExternalServices;

    class ChatGPT
    {
        private string $token;

        private const BASE_URL = 'https://api.openai.com/v1/chat/completions';

        public function __construct(string $token = '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')
        {
            $this->token = $token;
        }

        public function ask(string $question, $context = [])
        {
            $url = 'https://api.openai.com/v1/chat/completions';

            $headers = [
                'Authorization: Bearer ' .$this->token,
                //"OpenAI-Organization: YOUR-Organization-ID",
                'Content-Type: application/json',
            ];

            // Define messages
            $messages   = [];
            $messages[] = ['role' => 'user', 'content' => $question];
            $messages   = array_merge($context, $messages);



            $data = [
                'model'       => 'gpt-3.5-turbo',
                'messages'    => $messages,
                'max_tokens'  => 1600,
                'temperature' => 0.5,
            ];

            // init curl
            $curl = curl_init(self::BASE_URL);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);//
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $result = curl_exec($curl);
            $error = curl_errno($curl);
            curl_close($curl);

            if ($error) {
                return 'Error:' . $error;
            }

            return json_decode($result,true);
        }
    }