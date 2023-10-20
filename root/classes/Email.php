<?php

namespace eGamings\WLC;

use Html2Text\Html2Text;

class Email
{
    /**
     * @param string $email - Send TO
     * @param string $subject - Subject of email
     * @param string $msg - Body of message (can be html)
     * @param array $files - Array, optional, attachment to email, required full link, data in array
     *                       file['name'] - name of the file with extension
     *                       file['content'] - plain text or plain html, it will be converted into attachment
     *
     * @param string $replyTo - Reply To
     * @param string $eventEmail - TemplateEvent Email
     *
     * @return bool
     */
    public static function send($email, $subject, $msg, $files = [], $replyTo = null, $eventEmail = '')
    {
        if (!_cfg('smtpMailName') || !_cfg('smtpMailPass')) {
            return false;
        }

        if (!empty($eventEmail)) {
            _cfg('smtpMailName', $eventEmail);
        }
        
        if (!_cfg('smtpMailFrom')) {
            _cfg('smtpMailFrom', _cfg('smtpMailName'));
        }

        $security = empty(_cfg('smtpSecurity')) ? null : _cfg('smtpSecurity');

        $from = [];
        if (_cfg('smtpMailFromName')) {
            if (!empty($eventEmail)) {
                $from[$eventEmail] = _cfg('smtpMailFromName');
            } else {
                $from[_cfg('smtpMailFrom')] = _cfg('smtpMailFromName');
            }
        } else {
            $from[_cfg('smtpMailName')] = _cfg('smtpMailFrom');
        }

        if (!$replyTo && _cfg('smtpMailReplyTo')) {
            $replyTo = [_cfg('smtpMailReplyTo') => (_cfg('smtpMailReplyToName') ? _cfg('smtpMailReplyToName') : '')];
        }

        $transport = self::makeTransport(
            _cfg('smtpMailHost'), _cfg('smtpMailPort'), _cfg('smtpMailName'), _cfg('smtpMailPass'), $security
        );

        $message = self::makeMessage($email, $subject, $msg, $from, $files, $replyTo);

        $fails = null;
        //Sending message
        $mailer = \Swift_Mailer::newInstance($transport);
        $mailer->send($message, $fails);

        if ($fails) {
            $_SESSION['mailError'] = $fails;
            return false;
        }

        return true;
    }

    /**
     * Add mail to mail queue
     *
     * @param $email
     * @param $subject
     * @param $msg
     * @param array $files
     * @return bool
     */
    public static function enqueue($email, $subject, $msg, $files = [])
    {
        Db::query(
            'INSERT INTO `email_queue` SET ' .
            '`email` = "' . Db::escape($email) . '",' .
            '`subject` = "' . Db::escape($subject) . '",' .
            '`message` = "' . Db::escape($msg) . '",' .
            '`add_date` = NOW()'
        );

        return true;
    }

    /**
     * Send email over external smtp server
     *
     * @param $smtp - smtp settings
     * @param $email - email to
     * @param $subject - subject
     * @param $msg - message
     * @param array $from - from
     * @param array $files - files to attach
     * @param null $replyTo - email reply to
     * @return bool
     */
    public static function sendOverExternalSmtp($smtp, $email, $subject, $msg, $from = [], $files = [], $replyTo = null)
    {
        if (empty($smtp['host']) || empty($smtp['username']) || empty($smtp['password'])) {
            return false;
        }

        //define smtp port and encryption like popular (gmail.com, mail.ru, yandex.ru, etc)
        $port = !empty($smtp['port']) ? $smtp['port'] : '587';
        $encryption = !empty($smtp['encryption']) ? $smtp['encryption'] : 'tls';

        $transport = self::makeTransport($smtp['host'], $port, $smtp['username'], $smtp['password'], $encryption);
        $message = self::makeMessage($email, $subject, $msg, $from, $files, $replyTo);

        $fails = null;
        //Sending message
        $mailer = \Swift_Mailer::newInstance($transport);
        $mailer->send($message, $fails);

        if ($fails) {
            return false;
        }

        return true;
    }

    /**
     * Make Swift_Message instance and return it
     *
     * @param $to
     * @param $subject
     * @param $body
     * @param array $from
     * @param array $files
     * @param null $replyTo
     * @return mixed
     */
    protected static function makeMessage($to, $subject, $body, $from = [], $files = [], $replyTo = null)
    {
        $from = empty($from) ? array(_cfg('smtpMailName') => _cfg('smtpMailFrom')) : $from;
        $replyTo = $replyTo ? $replyTo : $from;
        $toMails = (!is_array($to)) ? explode(",", $to) : $to;
        $countCopy = count($toMails);

        $message = \Swift_Message::newInstance()
            // Give the message a subject
            ->setSubject($subject)
            // Set the From address with an associative array
            ->setFrom($from)
            ->setReplyTo($replyTo)
            // Set the To addresses with an associative array
            ->setTo($countCopy > 1 ? (['noreply@' . explode('@', _cfg('supportEmail'), 2)[1]]) : $toMails)
            // Give html body part
            ->setBody($body, 'text/html')
            // Give text part
            ->addPart((new Html2Text($body))->getText(), 'text/plain');

        if (_cfg('smtpMailIpPool')) {
            $message->getHeaders()->addTextHeader('X-SMTPAPI', json_encode(['ip_pool' => _cfg('smtpMailIpPool')]));
        }

        // hidden copy
        if ($countCopy > 1) {
            $message->setBcc($toMails);
        }

        // Optionally add any attachments
        if (count($files)) {
            foreach ($files as $attachment) {
                if (get_class($attachment) == 'Swift_Attachment') {
                    $message->attach($attachment);
                }
            }
        }

        return $message;
    }

    /**
     * Make Swift_SmtpTransport instance and return it
     *
     * @param $host
     * @param $port
     * @param $user
     * @param $password
     * @param null $security
     * @return mixed
     */
    protected static function makeTransport($host, $port, $user, $password, $security = null)
    {
        $transport = \Swift_SmtpTransport::newInstance($host, $port, $security);
        $transport->setUsername($user);
        $transport->setPassword($password);

        return $transport;
    }
}
