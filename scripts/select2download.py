#!/usr/bin/python3
# -*- coding:utf-8 -*-

import os
import sys
import transmissionrpc
import operator
import re

address='192.168.1.223'
port=9091
user='KorP'
password='Fp9bk1T'

pattern = r'(e|E)(\d{2})'
downloadedFiles = 3
remove = False
clean = False

hash = sys.argv[3]
client = transmissionrpc.Client(address, port, user, password)
torrent = client.get_torrent(hash)
path = torrent.downloadDir
files = torrent.files()
name = torrent.name

listFiles = files.keys()
tmp_file_list = {}
for i in listFiles:
    number = re.search(pattern, files[i]['name'])
    tmp_file_list.update({i:number.group(2)})

sorted_tmp_file_list = sorted(tmp_file_list.items(), key=operator.itemgetter(1))

count = 1
for i in reversed(sorted_tmp_file_list):
    f_id = i[0]
    if count <= downloadedFiles:
        files[f_id]['selected'] = True
    else:
        files[f_id]['selected'] = False
        if remove:
            if os.path.isfile(path+files[f_id]['name']):
                os.remove(path+files[f_id]['name'])
                
    count += 1

client.set_files({hash: files, })

if clean:
    for f in os.listdir(path+name):
        if re.search('.*.part', f):
            os.remove(os.path.join(path+name, f))
