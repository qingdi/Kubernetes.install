### 基础条件
##### 0、基本
Flannel的设计目的就是为集群中的所有节点重新规划IP地址的使用规则，从而使得不同节点上的容器能够获得“同属一个内网”且”不重复的”IP地址，并让属于不同节点上的容器能够直接通过内网IP通信。
Flannel实质上是一种“覆盖网络(overlay network)”，也就是将TCP数据包装在另一种网络包里面进行路由转发和通信，目前已经支持UDP、VxLAN、AWS VPC、hostgw和GCE路由等数据转发方式。 

Flannel是一种虚拟网卡，用于多节点ip分配和数据分发。

##### pod1与pod2不在同一台主机
下面是从pod1 ping pod2的数据包流向
1. pod1(10.0.14.15)向pod2(10.0.5.150)发送ping，查找pod1路由表，把数据包发送到cni0(10.0.14.1)
2. cni0查找host1路由，把数据包转发到flannel.1
3. flannel.1虚拟网卡再把数据包转发到它的驱动程序flannel
4. flannel程序使用VXLAN协议封装这个数据包，向api-server查询目的IP所在的主机IP,称为host2(不清楚什么时候查询)
5. flannel向查找到的host2 IP的UDP端口8472传输数据包
6. host2的flannel收到数据包后，解包，然后转发给flannel.1虚拟网卡
7. flannel.1虚拟网卡查找host2路由表，把数据包转发给cni0网桥，cni0网桥再把数据包转发给pod2
8. pod2响应给pod1的数据包与1-7步类似

##### pod1与pod2在同一台主机
pod1和pod2在同一台主机的话，由cni0网桥直接转发请求到pod2，不需要经过flannel。

##### 1、下载cffsl等命令
```
# 下载
wget https://pkg.cfssl.org/R1.2/cfssl_linux-amd64
wget https://pkg.cfssl.org/R1.2/cfssljson_linux-amd64
wget https://pkg.cfssl.org/R1.2/cfssl-certinfo_linux-amd64

# 安装
mv cfssl-certinfo_linux-amd64 /usr/local/bin/cfssl-certinfo
mv cfssl_linux-amd64 /usr/local/bin/cfssl
mv cfssljson_linux-amd64 /usr/local/bin/cfssljson
chmod +x /usr/local/bin/cfssl*

```




#### flannel 专用证书
```
cat > flanneld-csr.json <<EOF
{
  "CN": "flanneld",
  "hosts": [],
  "key": {
    "algo": "rsa",
    "size": 2048
  },
  "names": [
    {
      "C": "CN",
      "ST": "BeiJing",
      "L": "BeiJing",
      "O": "k8s",
      "OU": "4Paradigm"
    }
  ]
}
EOF
```

生成证书
```
cfssl gencert -ca=ca.pem -ca-key=ca-key.pem -config=ca-config.json -profile=kubernetes flanneld-csr.json |cfssljson -bare flanneld

mkdir /etc/flanneld/ssl -pv && \
cp flanneld*.pem /etc/flanneld/ssl/

# 复制到其他节点
cd /etc/flanneld && tar cvzf flanneld-ssl.tgz ssl/

scp flanneld-ssl.tgz root@10.20.1.181:/root

mkdir -pv /etc/flanneld/ssl && \
tar -zxvf /root/flanneld-ssl.tgz -C /etc/flanneld
```



向etcd写入集群信息
```
etcdctl \
  --endpoints=https://127.0.0.1:2379 \
  --ca-file=/etc/etcd/ssl/etcd-ca.pem \
  --cert-file=/etc/flanneld/ssl/flanneld.pem \
  --key-file=/etc/flanneld/ssl/flanneld-key.pem \
  set /kube-centos/network/config '{"Network":"'172.30.0.0/16'", "SubnetLen": 24, "Backend": {"Type": "host-gw"}}'
 ```
 
#### 得到如下反馈信息
```
{"Network":"172.30.0.0/16", "SubnetLen": 24, "Backend": {"Type": "host-gw"}}
```

#### 安装flannel
```
yum install -y flannel
```


#### flannel的配置
```
vi /etc/sysconfig/flanneld

FLANNEL_ETCD_ENDPOINTS="https://192.168.44.138:2379,https://192.168.44.139:2379,https://192.168.44.140:2379"
FLANNEL_ETCD_PREFIX="/kube-centos/network"
FLANNEL_OPTIONS="-etcd-cafile=/etc/etcd/ssl/etcd-ca.pem -etcd-certfile=/etc/flanneld/ssl/flanneld.pem -etcd-keyfile=/etc/flanneld/ssl/flanneld-key.pem"

```

#### 启动
```.env
systemctl start flanneld.service

#查看是否启动
ps -ef | grep flanneld
```


