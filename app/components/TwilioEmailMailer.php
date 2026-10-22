<?php

namespace app\components;

use app\modules\admin\models\Integrations;
use Yii;
use yii\helpers\Html;
use yii\mail\BaseMailer;
use yii\symfonymailer\Message;

/**
 * Sends mail through Twilio's Email API (https://comms.twilio.com/v1/Emails) over plain HTTP, replacing the
 * earlier SMTP-to-SendGrid setup (app\components\DashboardMailer). Authenticated with an API Key (SID +
 * Secret) -- this endpoint rejects the account's main Auth Token (HTTP 401, "invalid username"). The
 * Account SID is still needed separately, to build the sandbox From address. All three come from the
 * dashboard: Integrations > Twilio (app\modules\admin\models\Integrations, fields twilio_account_sid /
 * twilio_api_key_sid / twilio_api_key_secret).
 *
 * The 'from' address must be on a domain verified as a Sender Identity in the Twilio Console, UNLESS it is
 * Twilio's own built-in sandbox address "<AccountSID>@twilio.email", which needs no verification but is
 * meant for testing -- check Twilio's current limits (e.g. verified-recipient-only sending, and content
 * must match an approved template) before relying on it for real users. Falls back to that sandbox address
 * only when no 'from' was set on the message.
 *
 * Twilio's Email API requires content.html on every send (content.text alone is rejected); a text-only
 * message is wrapped in a minimal <p> so it still goes through.
 */
class TwilioEmailMailer extends BaseMailer
{
    public $messageClass = Message::class;

    const API_URL = 'https://comms.twilio.com/v1/Emails';

    /**
     * @param Message $message
     * @return bool
     */
    protected function sendMessage($message)
    {
        $accountSid = Integrations::value('twilio_account_sid');
        $apiKeySid = Integrations::value('twilio_api_key_sid');
        $apiKeySecret = Integrations::value('twilio_api_key_secret');

        if ($accountSid === '' || $apiKeySid === '' || $apiKeySecret === '') {
            Yii::error('Twilio Email: account SID, API key SID or API key secret is not configured (Integrations > Twilio).', 'mail');
            return false;
        }

        $email = $message->getSymfonyEmail();

        $from = $email->getFrom()[0] ?? null;
        $fromAddress = $from ? $from->getAddress() : ($accountSid . '@twilio.email');
        $fromName = $from ? $from->getName() : '';

        $to = [];
        foreach ($email->getTo() as $address) {
            $entry = ['address' => $address->getAddress()];
            if ($address->getName() !== '') {
                $entry['name'] = $address->getName();
            }
            $to[] = $entry;
        }
        if (empty($to)) {
            Yii::error('Twilio Email: message has no recipient.', 'mail');
            return false;
        }

        $content = ['subject' => (string) $email->getSubject()];
        $html = $email->getHtmlBody();
        $text = $email->getTextBody();
        if (is_string($text) && $text !== '') {
            $content['text'] = $text;
        }
        if (is_string($html) && $html !== '') {
            $content['html'] = $html;
        } elseif (is_string($text) && $text !== '') {
            // Twilio's Email API requires content.html on every send; text alone is rejected.
            $content['html'] = '<p>' . nl2br(Html::encode($text)) . '</p>';
        }
        if (!isset($content['html'])) {
            Yii::error('Twilio Email: message has no body.', 'mail');
            return false;
        }

        $payload = [
            'from' => $fromName !== '' ? ['address' => $fromAddress, 'name' => $fromName] : ['address' => $fromAddress],
            'to' => $to,
            'content' => $content,
        ];

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_USERPWD => $apiKeySid . ':' . $apiKeySecret,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 15,
        ]);
        $responseBody = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false) {
            Yii::error('Twilio Email: HTTP request failed: ' . $curlError, 'mail');
            return false;
        }

        if ($status < 200 || $status >= 300) {
            Yii::error("Twilio Email: send failed (HTTP $status): $responseBody", 'mail');
            return false;
        }

        return true;
    }
}
