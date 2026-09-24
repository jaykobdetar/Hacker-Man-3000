import os as _os
import sys as _sys
_sys.path.insert(0, _os.path.join(_os.path.dirname(_os.path.abspath(__file__)), '..', 'python'))
import gamedb
import time
start_time = time.time()

db = gamedb.connect()
cur = db.cursor()

cur.execute("""	DELETE users_expire, users_online, internet_connections
				FROM users_expire
				LEFT JOIN users_online
				ON users_online.id = users_expire.userID
				LEFT JOIN internet_connections
				ON internet_connections.userID = users_expire.userID
				WHERE 
					TIMESTAMPDIFF(SECOND, expireDate, NOW()) > 0
			""")

cur.execute("""	DELETE users_online, internet_connections
				FROM users_online
				LEFT JOIN internet_connections
				ON internet_connections.userID = users_online.id
				WHERE 
					TIMESTAMPDIFF(HOUR, loginTime, NOW()) > 10
			""")

db.commit()

print(time.strftime("%d/%m/%y %H:%M:%S"),' - ',__file__,' - ',round(time.time() - start_time, 4), "s \n")