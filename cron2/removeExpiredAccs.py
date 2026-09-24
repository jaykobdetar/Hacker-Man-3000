import os as _os
import sys as _sys
_sys.path.insert(0, _os.path.join(_os.path.dirname(_os.path.abspath(__file__)), '..', 'python'))
import gamedb
import sys
import time

start_time = time.time()

db = gamedb.connect()
cur = db.cursor()

cur.execute("	SELECT accID \
				FROM bankaccounts_expire \
				WHERE TIMESTAMPDIFF(SECOND, NOW(), expireDate) < 0 \
			")

totalRemoved = 0

for accID in cur.fetchall():

	accID = str(accID[0])

	cur.execute("	DELETE FROM bankAccounts \
					WHERE bankAcc = "+accID+" \
				")

	totalRemoved += 1

db.commit()

print(time.strftime("%d/%m/%y %H:%M:%S"),' - ',__file__,' - ',round(time.time() - start_time, 4), "s")