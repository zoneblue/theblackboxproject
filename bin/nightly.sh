#!/bin/bash

#THIS COPIES YESTERDAYS NEWMODBUSD DAY FILE FROM THE RAMDISK TO THE SD CARD
#you may need to edit this for file locations

#get yesterays date
DATE=`/bin/date -d "yesterday 13:00 " '+%Y-%m-%d'`

#bzip
/bin/bzip2 -f /var/tmp/blackbox/$DATE.txt

#relocate
/bin/mv  /var/tmp/blackbox/$DATE.txt.bz2  /home/data/daily/$DATE.txt.bz2

