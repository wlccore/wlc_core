<?php
namespace eGamings\WLC;

class Utils {
    public static $atomicReplace = true;
    public static $isMobileOverride = null;

    /**
     * @param string $filePath
     * @param mixed $data
     * @param string $error
     * @return bool
     */
    public static function atomicFileReplace(string $filePath, $data, string &$error = ''): bool
    {
        // @codeCoverageIgnoreStart
        if (!self::$atomicReplace) {
            Logger::log(sprintf('%s: atomicReplace flag is off', __METHOD__), 'info');
            return true;
        }

        $error = '';

        $tmpFilePath = '';
        $tmpGZFilePath = '';

        $tmpFilePointer = null;
        $tmpGZFilePointer = null;

        do {
            if (file_exists($filePath) && !is_writable($filePath)) {
                $error = sprintf('File "%s" is not writable!', $filePath);
                break;
            }

            $fileDir = dirname($filePath);
            $fileName = basename($filePath);

            $tmpFilePath = tempnam($fileDir, 'tmp_' . $fileName);
            $tmpGZFilePath = tempnam($fileDir, 'gz_' . $fileName);

            $tmpFilePointer = fopen($tmpFilePath, 'w');

            if (!$tmpFilePointer) {
                $error = 'Can\'t create temporary file';
                break;
            }

            $tmpGZFilePointer = gzopen($tmpGZFilePath, 'wb9');

            if ($tmpGZFilePointer === false) {
                $error = 'Can\'t create GZ file';
                break;
            }

            if (gzwrite($tmpGZFilePointer, (string)$data) === false) {
                $error = 'Can\'t compress to GZ';
                break;
            }

            gzclose($tmpGZFilePointer);
            $tmpGZFilePointer = null;

            if (fwrite($tmpFilePointer, (string)$data) === false) {
                $error = 'Can\'t write to temp file';
                break;
            }

            fclose($tmpFilePointer);
            $tmpFilePointer = null;

            if (!rename($tmpGZFilePath, $filePath . '.gz') || !rename($tmpFilePath, $filePath)) {
                $error = 'Can\'t replace original';
                break;
            }
        } while (false);

        if ($error) {
            Logger::log(sprintf('%s: %s', __METHOD__, $error));
        }

        if ($tmpFilePointer) {
            fclose($tmpFilePointer);
        }

        if ($tmpGZFilePointer) {
            gzclose($tmpGZFilePointer);
        }

        if ($tmpFilePath && file_exists($tmpFilePath)) {
            unlink($tmpFilePath);
        }

        if ($tmpGZFilePath && file_exists($tmpGZFilePath)) {
            unlink($tmpGZFilePath);
        }

        return !$error;
        // @codeCoverageIgnoreEnd
    }

    static function obfuscatePassword($data = []) {
        foreach ($data as $key => &$val) {
            if(stripos(strtolower($key),'pass') !== false){
                $val = (is_numeric( $val) ? 'numeric(' : 'string(') . strlen($val) . ')';
            }
        }

        return $data;
    }

    /**
     * obfuscate parameters in url
     * @param string $url
     * @param array $keys (by default passport fields)
     * @return string
     */
    static function obfuscateUrl($url, $keys = false)
    {
        if (!$keys){
            $keys = [
                'IDNumber',
                'IDIssueDate',
                'IDIssuer',
                // #9890
                'Additional\[net_account\]',
                'Additional\[secure_id\]',
                'UHash',
                'Pincode'
            ];
        }
        $keys = implode('|', $keys);
        $re = '/([?&])(' . $keys . ')=([-+_a-zA-Z0-9А-Яа-я%.]*)/u';
        $url = preg_replace($re, '${1}${2}=OBFUSCATED', $url);
        return $url;
    }

    /**
     * Obfuscate email
     *
     * @param string $email
     * @return string
     */
    public static function obfuscateEmail($email)
    {
        if (strlen($email) && strpos($email, '@')) {
            list($name, $domain) = explode('@', $email);
            $name = self::obfuscateString($name);
            $domain = self::obfuscateString($domain, 'left');
            $email = $name . '@' . $domain;
        }
        else {
            $email = '';
        }
        return $email;
    }

    /**
     * Obfuscate string with direction
     *
     * @param $string
     * @param string $direction
     * @return string
     */
    public static function obfuscateString($string, $direction = 'right')
    {
        $str_len = floor(mb_strlen($string) / 2);

        if('right' == $direction)
            $string = mb_substr($string, 0, $str_len) . str_repeat('*', $str_len);
        else
            $string = str_repeat('*', $str_len) . mb_substr($string, $str_len);

        return $string;
    }

    public static function joinPaths(string ...$parts): string
    {
        $paths = [];

        foreach ($parts as $part) {
            if ($part !== '') $paths[] = $part;
        }

        return preg_replace('@/+@', '/', join('/', $paths));
    }

    public static function isMobile(): bool
    {
        return self::$isMobileOverride ?? _cfg('mobile') || _cfg('mobileDetected');
    }

    public static function either(...$operands): bool
    {
        $iterator = (new \ArrayObject($operands))->getIterator();
        while ($iterator->valid() && !(bool) $iterator->current()) {
            $iterator->next();
        }
        return $iterator->valid() && (bool) $iterator->current();
    }

    public static function encodeURIComponent(string $str): string
    {
        return rawurlencode($str);
    }

    public static function hideStringWithWildcards(string $str, int $length = 10): string
    {
        $delim = (int) floor($length / 2);
        $str = mb_substr($str, 0, $delim ?: mb_strlen($str));
        $strLength = mb_strlen($str);

        if ($strLength < $length) {
            return $str . str_repeat('*', $length - mb_strlen($str));
        } else {
            return $str . str_repeat('*', $delim);
        }
    }

    public static function generateSid(): string
    {
        return sprintf( '%03x-%03x-%03x-%03x',
            mt_rand( 0, 0xfff ),
            mt_rand( 0, 0xfff ),
            mt_rand( 0, 0xfff ),
            mt_rand( 0, 0xfff )
        );
    }

    /**
     * @param string $input
     * @param int $pad_length
     * @param string $pad_string
     * @param int $pad_type
     * @return string
     */
    public static function mb_str_pad(string $input, int $pad_length, string $pad_string = ' ', int $pad_type = STR_PAD_RIGHT): string
    {
        $diff = strlen($input) - mb_strlen($input);
        return str_pad($input, $pad_length + $diff, $pad_string, $pad_type);
    }

    /**
     * Decode unicode characters in field
     *
     * @param string $field
     * @return string
     */
    public static function decodeUnicode(string $field): string
    {
        return preg_replace_callback('/\\\\[uU]([0-9a-fA-F]{4})/', function($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UTF-16BE');
        }, $field);
    }
}
