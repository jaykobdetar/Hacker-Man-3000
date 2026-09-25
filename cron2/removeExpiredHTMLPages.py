import os as _os
import sys as _sys
_sys.path.insert(0, _os.path.join(_os.path.dirname(_os.path.abspath(__file__)), '..', 'python'))
import gamedb
import os
import time

start_time = time.time()

db = gamedb.connect()
cur = db.cursor()

#Remove /html/profile/id.html pages

expireInterval = 3600 #Perfis atualizados na ultima hora nao sao removidos

cur.execute("""	SELECT userID
				FROM cache_profile 
				WHERE 
					TIMESTAMPDIFF(SECOND, expireDate, NOW()) > """+str(expireInterval)+"""
			""")

for userID in cur.fetchall():

	userID = userID[0]

	try: 
		os.remove(gamedb.path('html/profile/')+str(userID)+'.html')
	except:
		pass

	cur.execute("""	DELETE
					FROM cache_profile 
					WHERE 
						userID = """+str(userID)+"""
				""")

db.commit()

print(time.strftime("%d/%m/%y %H:%M:%S"),' - ',__file__,' - ',round(time.time() - start_time, 4), "s")