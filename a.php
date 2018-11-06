<?php
/**
 * Created by IntelliJ IDEA.
 * User: houziqiang
 * Date: 2018/11/5
 * Time: 7:47 PM
 */

/bin/etcd --data-dir=/etcd-data --name node-master --initial-advertise-peer-urls http://172.16.222.100:2380 --listen-peer-urls http://0.0.0.0:2380 --advertise-client-urls http://172.16.222.100:2379 --listen-client-urls http://0.0.0.0:2379 --initial-cluster node-master=http://172.16.222.100:2380,node-slave1=http://172.16.222.101:2380,node-slave2=http://172.16.222.102:2380 --initial-cluster-state new --initial-cluster-token docker-etcd