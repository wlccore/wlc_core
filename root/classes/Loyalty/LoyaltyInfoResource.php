<?php
namespace eGamings\WLC\Loyalty;

use eGamings\WLC\Cache;
use eGamings\WLC\Loyalty;

class LoyaltyInfoResource extends LoyaltyAbstractResource {
    /**
     * @return array
     */
    public static function UserLevels(): array
    {
        $key = 'LoyaltyInfoResource::UserLevels';

        return Cache::result($key, function() {
            $path = 'Loyalty/Levels/Get';
            $language = _cfg('language');
            $params = [];

            $levels = Loyalty::Request($path, $params);

            foreach($levels as &$level) {
                $level['Name'] = !empty($level['Name'][$language])
                    ? $level['Name'][$language]
                    : $level['Name']['en'];

                if (is_array($level['Description'])) {
                    $level['Description'] = !empty($level['Description'][$language])
                        ? $level['Description'][$language]
                        : $level['Description']['en'] ?? null;
                }

                if (is_array($level['Image'])) {
                    $level['Image'] = !empty($level['Image'][$language])
                        ? $level['Image'][$language]
                        : $level['Image']['en'] ?? null;
                }
            }
            return $levels;
        }, 60, ['language' => _cfg('language')]);
    }
}
