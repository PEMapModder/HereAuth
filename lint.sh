#!/bin/bash

GOOD=0
BAD=0
for file in `find .`; do
	EXTENSION="${file##*.}"
	if [ "$EXTENSION" == "php" ]; then
		RESULTS=`php -l "$file"`
		if [ "$RESULTS" != "No syntax errors detected in $file" ]; then
			echo "$RESULTS"
			((BAD++))
		else
			((GOOD++))
		fi
	fi
done

echo -n "Scanned $GOOD good file(s)"
if [ ${BAD} -gt 0 ]; then
	echo " and $BAD bad file(s)"
	exit 1
fi
echo
exit 0
