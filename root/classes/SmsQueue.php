<?php
namespace eGamings\WLC;

use eGamings\WLC\Db;
use eGamings\WLC\Logger;
use eGamings\WLC\Config;

class SmsQueue
{
    private const MAX_SMS_SIZE_GSM = 160;
    private const MAX_SMS_SIZE_UNICODE = 70;

    /**
     * Get current email queue log prefix
     *
     * @return string
     */
    private static function getLogPrefix() : string 
    {
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

        $redisKey = _cfg('env') . '_process_' . $status . '_sms';
        $limit = _cfg('mailQueueLimit') ?: 100;

        if ($redis->exists($redisKey)) {
            Logger::log(self::getLogPrefix() . 'Process already running');
            return null;
        }

        if (!$redis->set($redisKey, md5(time()), 120)) {
            Logger::log(self::getLogPrefix() . 'Redis error. Failed set key - ' . $redisKey);
            return null;
        }

        $result = Db::query('SELECT id, phone, message ' .
               'FROM `sms_queue` AS eq ' .
               'WHERE status = "' . $status . '" ' .
               'ORDER BY add_date ASC LIMIT ' . $limit);

        $successfullySendCount = 0;
        $total = 0;

        if (is_object($result)) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $total = count($rows);

            $smsProvider = Sms::getInstance();
            if (!$smsProvider) {
                Logger::log(self::getLogPrefix() . "Failed send. Sms provider not found");
                return false;
            }

            $sender = $smsProvider->getDefaultSender();
            foreach ($rows as $row) {
                try {
                    [$phoneCode, $phoneNumber] = explode('-', $row['phone']);
                    $messages = self::splitMessage($row['message']);
                    foreach ($messages as $message) {
                        $result = $smsProvider->SendOne($phoneNumber, $sender, $message, str_replace('+', '', $phoneCode));
                    }
                    if (!$result['status']) {
                        self::updateStatus($row['id'], 'failed');
                        Logger::log(self::getLogPrefix() . "Failed send. Msg: undefined error");
                        continue;
                    }

                    $successfullySendCount++;

                    self::updateStatus($row['id'], 'sent');
                } catch (\Exception $ex) {
                    self::updateStatus($row['id'], 'failed');
                    Logger::log(self::getLogPrefix() . 'Failed send. Msg: ' . $ex->getMessage());
                    continue;
                }
            }
        }

        $redis->del($redisKey);
        // @codeCoverageIgnoreStart
        Logger::log(self::getLogPrefix() . "Successfully processed - {$successfullySendCount}/{$total} ({$status} sms)", "info"); 
        return true;
        // @codeCoverageIgnoreEnd
    }

    /**
     * Multiply insert emails to DB
     *
     * @param array $rows
     *
     * @return bool
     */
    public static function multiEnqueue($rows) : bool
    {
        if (empty($rows) || !is_array($rows)) {
            return false;
        }

        $values = [];
        $limit = _cfg('mailQueueLimit') ?: 100;

        $query = 'INSERT INTO `sms_queue` (`phone`, `message`, `add_date`) VALUES ';
        foreach ($rows as $row) {
            if (!isset($row['to']) || !isset($row['message'])) {
                continue;
            }

            $values[] = '("' . Db::escape($row['to']) . '","' . Db::escape($row['message']) . '", NOW())';
            
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
     * Update sms status
     *
     * @param int $id
     * @param string $status (queue, sent, failed)
     *
     * @return bool
     */
    public static function updateStatus($id, $status) : bool
    {
        if (!in_array($status, ['queue', 'sent', 'failed'])) {
            return false;
        }

        Db::query('UPDATE `sms_queue` SET `status` = "' . $status . '"'
            . ' WHERE `id` = "' . $id . '"');

        return true;
    }

    /**
     * Delete emails that have status 'sent'
     *
     * @return bool
     */
    public static function clean() : bool
    {
        return Db::query('DELETE FROM `sms_queue` WHERE `status` = "sent" AND add_date < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    }

    /**
     * @param string $message
     * @return array
     */
    public static function splitMessage(string $message) : array
    {
        $splited_message = [];
        if (self::is_gsm0338($message)) {
            $splited_message = str_split($message, self::MAX_SMS_SIZE_GSM);
        } else {
            // split string unicode
            for ($i = 0; $i < mb_strlen($message, "UTF-8"); $i += self::MAX_SMS_SIZE_UNICODE) {
                $splited_message[] = mb_substr($message, $i, self::MAX_SMS_SIZE_UNICODE, "UTF-8");
            }
        }

        return $splited_message;
    }

    /**
     * @param string $message
     * @return bool
     */
    public static function is_gsm0338(string $message) : bool
    {
        $gsm0338 = [
            '@','Δ',' ','0','¡','P','¿','p',
            '£','_','!','1','A','Q','a','q',
            '$','Φ','"','2','B','R','b','r',
            '¥','Γ','#','3','C','S','c','s',
            'è','Λ','¤','4','D','T','d','t',
            'é','Ω','%','5','E','U','e','u',
            'ù','Π','&','6','F','V','f','v',
            'ì','Ψ','\'','7','G','W','g','w',
            'ò','Σ','(','8','H','X','h','x',
            'Ç','Θ',')','9','I','Y','i','y',
            "\n",'Ξ','*',':','J','Z','j','z',
            'Ø',"\x1B",'+',';','K','Ä','k','ä',
            'ø','Æ',',','<','L','Ö','l','ö',
            "\r",'æ','-','=','M','Ñ','m','ñ',
            'Å','ß','.','>','N','Ü','n','ü',
            'å','É','/','?','O','§','o','à'
        ];

        foreach (str_split($message) as $char) {
            if (!in_array($char, $gsm0338)) {
                return false;
            }
        }
    
        return true;
    }
}
