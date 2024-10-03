Config file for this component can look like this:

```php
return [
    'db' => require __DIR__ . '/db.php',
    'request' => [
        // your settings
    ],
    'response' => [
        // your settings
    ],
	'telegramBot' => [
        'class' => 'qaev\telegram\TelegramBot',
        // where to send
        'url' => 'https://api.telegram.org/bot000000000:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
        // where '000000000' is your bot's id
        // and 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' token of your bot
        // chats we're using
        // u can use actual id, but in hindsight quick nicknames look easier
        'chats' => [
            // channels
            '-1000000000000' => ['ch', 'channel'],
            // personal
            '100000000'  => ['me', 'spiderman'],
            '90000000'   => ['mf', 'my-friend'],
            '1000000000' => ['gf', 'his-girlfriend'],
        ],
        'contextOptions' => [ // options for file_get_contents (it's used for usual text sending)
            'ssl' => [
                'allow_self_signed' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ],
        'nicknameByDefault' => static fn () => \Yii::$app->params['tgBotDefaultSender'],
         // or
         // 'nicknameByDefault' => static fn () => 'me',	
    ],
];
```