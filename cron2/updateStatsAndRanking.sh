#!/bin/sh

/usr/bin/env python3 "$(dirname "$0")/../cron2/updateCurStats.py"; 
/usr/bin/env python3 "$(dirname "$0")/../cron2/updateRanking.py"; 
/usr/bin/env python3 "$(dirname "$0")/../python/rank_generator.py";
