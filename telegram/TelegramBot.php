<?php

namespace q4ev\telegram\TelegramBot;


use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

/**
 * @property array $chats
 */
class TelegramBot extends \yii\base\BaseObject
{
	protected array $_chats = [];

	public ?array $contextOptions = null;
	public ?\Closure $nicknameByDefault = null;

	public string $url;

	protected function __prepareText ($text, bool $rawText = false)
	{
		if (null === $text)
			return '/null/';

		if (false === $text)
			return '/false/';

		if (true === $text)
			return '/true/';

		if ([] === $text)
			return '/[]/';

		if ('' === $text)
			return '/String is empty/';

		if (is_int($text))
			return '/'.$text.'/';

		$json = $rawText
			? \urlencode($text)
			: \json_encode($text, JSON_UNESCAPED_UNICODE);

		if (false === $json)
			throw new \Exception('Cannot JSON-encode '.$text);

		if (mb_strlen($json, 'utf-8') > 4096)
			$json = mb_substr($json, 0, 4090, 'utf-8').'~';

		return $json;
	}

	public function getChat ($nickname)
	{
		// in case $nickname is not a nickname but already a valid chat id
		if (preg_match('/^-?\d*$/', $nickname))
			return $nickname;

		// in case nickname omitted
		if (!$nickname && $f = $this->nicknameByDefault)
			$nickname = $f();

		return $this->_chats[$nickname] ?? null;
	}

	public function getChats (): array
	{
		return $this->_chats;
	}

	public function send ($text, ?string $to = null, bool $rawText = false)
	{
		$json = $this->__prepareText($text, $rawText);

		$result = [];
		foreach ((array)$to as $nickname)
		{
			if (!$chatId = $this->getChat($nickname))
				throw new InvalidArgumentException("Receiver's chat for $nickname not found");

			$context = stream_context_create($this->contextOptions);

			$result[$nickname] = file_get_contents(
				$this->url."/sendMessage?chat_id=$chatId&parse_mode=html&text=$json",
				false,
				$context
			);
		}

		if (!is_array($to))
			return reset($result);

		return $result;
	}

	public function sendPhoto ($filePath, ?string $to = null, ?string $caption = null)
	{
		$result = [];

		$cFile = new \CURLFile($filePath);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url.'/sendPhoto');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		foreach ((array)$to as $nickname)
		{
			if (!$chatId = $this->getChat($nickname))
				throw new InvalidArgumentException("Receiver's chat for $nickname not found");

			// putting data
			$post = [
				'chat_id' => $chatId,
				'photo' => $cFile,
			];
			if ($caption)
				$post['caption'] = $caption;
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

			$result[$nickname] = curl_exec($ch);
		}

		curl_close($ch);

		if (!is_array($to))
			return reset($result);

		return $result;
	}

	public function setChats (array $chats)
	{
		foreach ($chats as $chatId => $nicknames)
		{
			if (!is_array($nicknames))
				throw new InvalidConfigException('Nicknames for chats must be an array');

			foreach ($nicknames as $nickname)
			{
				if ($this->_chats[$nickname])
					throw new InvalidConfigException("Nickname '$nickname' used more than once");

				$this->_chats[$nickname] = $chatId;
			}
		}
	}
}