<?php
namespace eGamings\WLC;

use eGamings\WLC\Db;
use eGamings\WLC\Logger;
use eGamings\WLC\Config;

class EmailQueue
{
    /**
     * Get current email queue log prefix
     *
     * @return string
     */
    private static function getLogPrefix() {
        return __CLASS__ . ' - ' . Config::get('websiteName') . ' ( ' . Config::get('site') . ' ): ';
    }

    /**
     * Start send emails
     *
     * @param string $status Select mails with current status (queue, failed)
     * @return null|bool
     */
    public static function process($status = 'queue')
    {
        if (!in_array($status, ['queue', 'failed'])) {
            return null;
        }

        set_time_limit(60);

        $redis = System::redis();

        $redisKey = _cfg('env') . '_process_' . $status . '_emails';
        $limit = _cfg('mailQueueLimit') ?: 100;

        if ($redis->exists($redisKey)) {
            Logger::log(self::getLogPrefix() . 'Process already running');
            return null;
        }

        if (!$redis->set($redisKey, md5(time()), 120)) {
            Logger::log(self::getLogPrefix() . 'Redis error. Failed set key - ' . $redisKey);
            return null;
        }

        $result = Db::query('SELECT eq.id, eq.email, eq.subject, eq.message, eqs.host, eqs.username, eqs.password ' .
               'FROM `email_queue` AS eq ' .
               'LEFT JOIN `email_queue_smtp` AS eqs ' .
               'ON eq.smtp_id = eqs.id ' .
               'WHERE eq.status = "' . $status . '" ' .
               'ORDER BY eq.add_date ASC LIMIT ' . $limit);

        $c = 0;
        $total = 0;

        if (is_object($result)) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $total = count($rows);

            foreach ($rows as $row) {
                try {
                    $sent = static::send($row['email'], $row['subject'], $row['message'], [
                        'host' => $row['host'],
                        'username' => $row['username'],
                        'password' => $row['password'],
                    ]);

                    if ($sent !== true) {
                        self::updateStatus($row['id'], 'failed');
                        Logger::log(self::getLogPrefix() . "Failed send. Msg: undefined error");
                        continue;
                    }

                    $c++;

                    self::updateStatus($row['id'], 'sent');
                } catch (\Exception $ex) {
                    self::updateStatus($row['id'], 'failed');
                    Logger::log(self::getLogPrefix() . 'Failed send. Msg: ' . $ex->getMessage());
                    continue;
                }
            }
        }

        $redis->del($redisKey);
        Logger::log(self::getLogPrefix() . "Successfully processed - {$c}/{$total} ({$status} emails)", "info"); 
        return true;
    }

    /**
     * Multiply insert emails to DB
     *
     * @param array $rows
     *
     * @return bool
     */
    public static function multiEnqueue($rows)
    {
        if (empty($rows) || !is_array($rows)) {
            return false;
        }

        $values = [];
        $limit = _cfg('mailQueueLimit') ?: 100;

        $query = 'INSERT INTO `email_queue` (`email`, `subject`, `message`, `smtp_id`, `add_date`) VALUES ';

        foreach ($rows as $row) {
            if (!isset($row['to']) || !isset($row['subject']) || !isset($row['message']) || !isset($row['smtp_id'])) {
                continue;
            }

            $values[] = '("' . Db::escape($row['to']) . '", "' . Db::escape($row['subject']) . '",' .
                '"' . Db::escape($row['message']) . '", "' . Db::escape($row['smtp_id']) . '", NOW())';

             if(count($values) >= $limit) {
                 Db::query($query . implode(',', $values));
                 $values = [];
             }
        }

        if ($values) {
            Db::query($query . implode(',', $values));
        }

        return true;
    }

    /**
     * Add new smtp config to DB
     *
     * @param array $data
     *
     * @return int
     */
    public static function addSmtpConfig($data)
    {
        if (!is_array($data) || empty($data['host']) || empty($data['username']) || empty($data['password'])) {
            return 0;
        }

        $host = Db::escape($data['host']);
        $username = Db::escape($data['username']);
        $password = Db::escape($data['password']);

        $result = Db::query('SELECT `id` FROM `email_queue_smtp`
            WHERE `host` = "' . $host . '"
            AND `username` = "' . $username . '"
            AND `password` = "' . $password . '"
            LIMIT 1');

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            return $row['id'];
        }

        Db::query('INSERT INTO `email_queue_smtp` SET 
            `host` = "' . $host . '",
            `username` = "' . $username . '",
            `password` = "' . $password . '"'
        );

        return Db::lastId();
    }

    /**
     * Update email status
     *
     * @param int $id
     * @param string $status (queue, sent, failed)
     *
     * @return bool
     */
    public static function updateStatus($id, $status)
    {
        if (!in_array($status, ['queue', 'sent', 'failed'])) {
            return false;
        }

        Db::query('UPDATE `email_queue` SET `status` = "' . $status . '"'
            . ' WHERE `id` = "' . $id . '"');

        return true;
    }

    /**
     * Delete emails that have status 'sent'
     *
     * @return bool
     */
    public static function clean()
    {
        return Db::query('DELETE FROM `email_queue` WHERE `status` = "sent" AND add_date < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    }

    /**
     * @param $email
     * @param $subject
     * @param $message
     * @param array $smtp
     * @return bool
     * @throws \Swift_SwiftException
     */
    protected static function send($email, $subject, $message, $smtp = [])
    {
        if (!empty($smtp['host']) && !empty($smtp['username']) && !empty($smtp['password'])) {
            return Email::sendOverExternalSmtp($smtp, $email, $subject, $message, [
                $smtp['username'] => _cfg('smtpMailFromName') ?: _cfg('smtpMailFrom')
            ]);
        }

        return Email::send($email, $subject, $message);
    }
}
