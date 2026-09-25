import os as _os
import sys as _sys
_sys.path.insert(0, _os.path.join(_os.path.dirname(_os.path.abspath(__file__)), '..', 'python'))
import gamedb
import sys
import time

start_time = time.time()

db = gamedb.connect()
cur = db.cursor()

cur.execute("	DELETE \
				FROM npc_down \
				WHERE TIMESTAMPDIFF(SECOND, NOW(), downUntil) < 0 \
			")

db.commit()

print(time.strftime("%d/%m/%y %H:%M:%S"),' - ',__file__,' - ',round(time.time() - start_time, 4), "s")