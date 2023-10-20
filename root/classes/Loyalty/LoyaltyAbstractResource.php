<?php
namespace eGamings\WLC\Loyalty;

abstract class LoyaltyAbstractResource {
    static function localizeRows($rows) {
        $result = [];
        if (is_array($rows)) foreach($rows as $row) {
            $result[] = self::localizeRow($row);
        }
        return $result;
    }

    static function localizeRow($row) {
        $lang_check = array(
            'Name' => _('No bonus name'),
            'Description' => _('No bonus description'),
            'Image' => '',
            'Image_promo' => '',
            'Image_reg' => '',
            'Image_store' => '',
            'Image_dashboard' => '',
            'Image_main' => '',
            'Image_description' => '',
            'Image_deposit' => '',
            'Image_other' => '',
            'Terms' => '',
        );

        foreach ($lang_check as $field => $default) {
            if (!isset($row[$field])) continue;
            $tmp = $row[$field];

            if (is_array($tmp)) {
                if (isset($tmp[_cfg('language')]) && !empty($tmp[_cfg('language')])) {
                    $tmp = $tmp[_cfg('language')];
                } elseif (isset($tmp['en'])) {
                    $tmp = $tmp['en'];
                } else {
                    $tmp = $default;
                }

                $row[$field] = $tmp;
            }
        }

        $terms_translates = array(
            '[registration]' => _('registration'),
            '[verification]' => _('verification'),
            '[sign up]' => _('sign up'),
            '[store]' => _('store'),
            '[login]' => _('login'),
            '[deposit]' => _('deposit'),
            '[deposit first]' => _('deposit first'),
            '[deposit sum]' => _('deposit sum'),
            '[deposit repeated]' => _('deposit repeated'),
            '[bet]' => _('bet'),
            '[bet sum]' => _('bet sum'),
            '[win sum]' => _('win sum'),
            '[loss sum]' => _('loss sum'),
            '[once]' => _('once'),
            '[day]' => _('day'),
            '[week]' => _('week'),
            '[month]' => _('month'),
            '[all]' => _('all'),
            '[bonus]' => _('bonus'),
            '[win]' => _('win'),
            '[winbonus]' => _('win and bonus'),
            '[winevent]' => _('winevent'),
            '[winbonusevent]' => _('winbonusevent'),
            '[none]' => _('none'),
            '[balance]' => _('balance'),
            '[experience]' => _('experience'),
            '[loyalty]' => _('loyalty'),
            '[absolute]' => _('absolute'),
            '[relative]' => _('relative'),
            '[bets]' => _('bets'),
            '[wins]' => _('wins'),
            '[turnovers]' => _('turnover'),
            '[turnovers_loose]' => _('negative turnover'),
            '[unlimited]' => _('unlimited'),
            '[1 day]' => _('one day'),
            '[2 days]' => _('two days'),
            '[3 days]' => _('three days'),
            '[7 days]' => _('seven days'),
            '[10 days]' => _('ten days'),
            '[14 days]' => _('fourteen days'),
            '[30 days]' => _('thirty days'),
            '[60 days]' => _('sixty days'),
            '[90 days]' => _('ninety days'),
            '[1 week]' => _('one week'),
            '[2 weeks]' => _('two weeks'),
            '[1 month]' => _('one month'),
            '[every 1 day]' => _('every day'),
            '[every 1 week]' => _('every week'),
            '[every 2 weeks]' => _('every two weeks'),
            '[every 1 month]' => _('every month'),
            '[turnover]' => _('turnover'),
            '[fee]' => _('fee'),
            '[turnover_fee]' => _('turnover and fee'),
            '[everyday]' => _('everyday'),
            '[Mon]' => _('monday'),
            '[Tue]' => _('tuesday'),
            '[Wed]' => _('wednesday'),
            '[Thu]' => _('thursday'),
            '[Fri]' => _('friday'),
            '[Sat]' => _('saturday'),
            '[Sun]' => _('sunday'),
        );

        if (!empty($row['Terms'])) {
            $row['Terms'] = strtr($row['Terms'], $terms_translates);
        }

        return $row;
    }
}
