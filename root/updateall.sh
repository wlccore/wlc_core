#!/bin/sh
#locales="ru_RU en_US ar_KW"
loyaltyFileName="loyalty"
wlcCoreFileName="messages"

cd root/
locales=$(cd locale && ls -d */ | cut -f1 -d'/')
for locale in ${locales}
do
    echo "Generate mo file for $locale"
    ./generatemo.sh ${locale} ${loyaltyFileName} || exit 1
    ./generatemo.sh ${locale} ${wlcCoreFileName} || exit 1
done
