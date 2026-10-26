<?php

namespace app\components;

use app\modules\admin\models\Integrations;
use Symfony\Component\Mailer\Transport\TransportInterface;
use yii\symfonymailer\Mailer;

/**
 * The application's mailer, with one difference: the mailbox account pasted in the dashboard (Integrations >
 * E-mail (SMTP)) is used first. The values written in the configuration stay as the alternative, so nothing
 * stops working where the credentials are only set on the server.
 * */
class DashboardMailer extends Mailer
{
    /** @var array|null the transport as configured (before the dashboard credentials are put in) */
    private $configuredTransport;

    // [doc:start] setTransport - Puts the dashboard's mailbox credentials in the transport settings when there are any.
    public function setTransport(array|TransportInterface $transport): void
    {
        if (is_array($transport)) {
            $this->configuredTransport = $transport;
            $transport = $this->withCredentials($transport);
        }
        parent::setTransport($transport);
    }
    // [doc:end] setTransport

    private function withCredentials(array $transport)
    {
        try {
            $username = Integrations::value('mail_username');
            $password = Integrations::value('mail_password');
        } catch (\Throwable $e) {
            $username = '';
            $password = '';
        }
        if ($username !== '' && $password !== '') {
            $transport['username'] = $username;
            $transport['password'] = $password;
        }

        return $transport;
    }
}
