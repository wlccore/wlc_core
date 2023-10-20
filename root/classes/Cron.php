<?php
namespace eGamings\WLC;

class Cron extends System
{
    public $folder;

    public function __construct()
    {
        $this->folder = _cfg('cache');

        parent::__construct();
    }

    public function cleanOldSqlData()
    {
        Db::multi_query('CALL WlcCleanupOldData()');

        do {
            $r = Db::store_result();
        } while (Db::more_results() && Db::next_result());

        return true;
    }

    public function fetchCountryList()
    {
    	return Classifier::fetchCountryList(true);
    }

    public function fetchStateList()
    {
        return States::fetchStateList(true);
    }

    public function fetchGamesList()
    {
        // @codeCoverageIgnoreStart
        Games::fetchGamesFullList();

        GZ::setForceUpdate();
        GZ::makeMinFiles();
        // @codeCoverageIgnoreEnd
    }

    // @codeCoverageIgnoreStart
    public function clearSortingCache()
    {
        Games::dropGamesSortingCache();
    }

    public function clearSortsCache()
    {
        Games::dropGamesSortsCache();
    }
    // @codeCoverageIgnoreEnd

    /**
     * Updating the emergency files
     *
     * @codeCoverageIgnore
     */
    public function updateEmergencyData()
    {
        EmergencyProxy::rebuildCache();
    }

    public function fetchSiteConfig() {
    	return Config::fetchSiteConfig(true);
    }

    public function fetchBanners() {
        return Banners::fetchBanners('v1');
    }

    public function fetchBannersV2() {
        return Banners::fetchBanners('v2');
    }

    public function fetchSeo() {
        return Seo::fetchSeo();
    }

    public static function processAccounts($Type, $date = null)
    {
        //---
        header('Content-Type: text/plain; charset=utf-8');

        if (is_null($date)) {
            $date = date('Y-m-d', strtotime('-1 day'));
        }

        list($year, $month, $day) = explode('-', $date);
        if (!checkdate($month, $day, $year)) {
            echo 'Invalid date: "' . $date . '"';
            die();
        }

        //---
        switch ($Type) {
            //---
            case 'Unactivated':
                $email = _cfg('unactivatedAccountEmail');
                if (empty($email)) {
                    echo 'Empty email for recieving unactivated account list';
                    return false;
                }

                //---
                $body = "Hi,<br/><br/>Please find unactivated account list for " . _cfg('site') . " on " . $date . " in attachment.<br/><br/>BR, " . _cfg('websiteName') . " robot";
                $subject = '[' . _cfg('env') . '] Unactivated accounts for ' . $date;
                $file_name = mb_strtolower(_cfg('websiteName')) . '_unactivated_accounts_' . $date . '.csv';
                $what = ' unactivated accounts';

                //---
                $result = Db::query('SELECT first_name, last_name, email, phone1, phone2, reg_time FROM users_temp WHERE DATE(reg_time)=DATE("' . Db::escape($date) . '")');
                break;

            //---
            case 'UnverifiedEmails':
                $email = _cfg('unverifiedEmailListRecipient');
                if (empty($email)) {
                    echo 'Empty email for receiving unverified email list';
                    return false;
                }
                $body = "Hi,<br/><br/>Please find unverified email list for " . _cfg('site') . " on " . $date . " in attachment.<br/><br/>BR, " . _cfg('websiteName') . " robot";
                $subject = '[' . _cfg('env') . '] Unverified email list for ' . $date;
                $file_name = mb_strtolower(_cfg('websiteName')) . '_unverified_email_addresses_' . $date . '.csv';
                $what = ' unverified email addresses';

                $result = Db::query('SELECT first_name, last_name, email, phone1, phone2, reg_time FROM users WHERE email_verified=0 AND DATE(reg_time)=DATE("' . Db::escape($date) . '")');
                break;
        }


        $data = Array();

        ob_start();
        $output = fopen('php://output', 'w');

        $i = 0;
        while ($row = $result->fetch_assoc()) {
            if ($i == 0) {
                fputcsv($output, array_keys($row));
            }

            fputcsv($output, $row);

            $i++;
        }

        $csv = ob_get_contents();
        ob_end_clean();

        //---
        if ($i > 0) {
            $attachment = \Swift_Attachment::newInstance($csv, $file_name, 'text/csv');
            $sendResult = Email::send($email, $subject, $body, [$attachment]);

            if ($sendResult) {
                echo 'Email sent with ' . $i . $what . ' for ' . _cfg('site') . ' on ' . $date;
            } else {
                echo 'Failed to send email with ' . $i . $what . ' for ' . _cfg('site') . ' on ' . $date;
            }

            return $sendResult;
        } else {
            echo 'No records to send for ' . $date;
        }

        return true;
    }

    /**
    * Continue unsuccessful registrations #3525
    * Retry FundistAPI user registration requests if they were unsuccessful #5985
    */
    public static function finishRegistration()
    {
        $rows = Db::fetchRows('SELECT `id` FROM `users_temp` WHERE `reg_time`<DATE_SUB(NOW(),INTERVAL 30 SECOND)');
        if (is_object($rows)) foreach ($rows as $row) {
            $user = new User($row->id);
            $user->cronFinishRegistration($row->id);
        }

        $user = new User();
        $user->cronRetryFundistUserRegistrationRequests();
    }

    public static function syncLiveChat()
    {
        $LiveChatProvider = LiveChat::getInstance();

        if ($LiveChatProvider && $LiveChatProvider->getLiveChatSyncCron()) {
            return $LiveChatProvider->syncChatByCron();
        }

        return false;
    }

    /**
     * Start send emails with status 'queue'
     *
     * @return bool
     */
    public function processEmailSend()
    {
        EmailQueue::process();

        return true;
    }

    /**
     * Repeat send emails with status 'failed'
     *
     * @return bool
     */
    public function repeatEmailSend()
    {
        EmailQueue::process('failed');

        return true;
    }

    /**
     * Delete emails with status 'sent'
     *
     * @return bool
     */
    public function cleanEmailQueue()
    {
        EmailQueue::clean();

        return true;
    }

    /**
     * Start send sms with status 'queue'
     * 
     * @return bool
     */
    // @codeCoverageIgnoreStart
    public function processSmsSend() : bool
    {
        SmsQueue::process();
        return true;
    }
    // @codeCoverageIgnoreEnd

    /**
     * Repeat send sms with status 'failed'
     *
     * @return bool
     */
    // @codeCoverageIgnoreStart
    public function repeatSmsSend() : bool
    {
        SmsQueue::process('failed');
        return true;
    }
    // @codeCoverageIgnoreEnd

    /**
     * Delete sms with status 'sent'
     *
     * @return bool
     */
    // @codeCoverageIgnoreStart
    public function cleanSmsQueue() : bool
    {
        SmsQueue::clean();
        return true;
    }
    // @codeCoverageIgnoreEnd
}
