#!/bin/sh
OSSIM_HOME=/usr/share/ossim/
cd $OSSIM_HOME
rm -f filenames
find www -name "*.php" >> filenames
find include -name "*.php" >> filenames
find www -name "*.inc" >> filenames
find include -name "*.inc" >> filenames
echo "locale/translations.php" >> filenames
xgettext -d ossim -s -o ossim.po -f filenames -L PHP 
for i in `ls $OSSIM_HOME/locale/ | grep -v CVS`; 
do
    echo "Updating $i"; 
    msgmerge -N -s -U $OSSIM_HOME/locale/$i/LC_MESSAGES/ossim.po $OSSIM_HOME/ossim.po;
    sed -i 's/^#~\s//g' $OSSIM_HOME/locale/$i/LC_MESSAGES/ossim.po
    if [ $i == 'zh_TW' ]; then
        php $OSSIM_HOME/locale/$i/LC_MESSAGES/conv.php $OSSIM_HOME/locale/$i/LC_MESSAGES/ossim.po | msgfmt - -o $OSSIM_HOME/locale/$i/LC_MESSAGES/ossim.mo
    else
        msgfmt -c -v -o $OSSIM_HOME/locale/$i/LC_MESSAGES/ossim.mo $OSSIM_HOME/locale/$i/LC_MESSAGES/ossim.po;
    fi
    echo "Finished updating $i"; 
done
