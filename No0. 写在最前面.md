## 集群搭建前
#### 配置hostname
```.env
/etc/sysconfig/network
NETWORKING=yes
HOSTNAME=pg-k8s-1-184
NETWORKING_IPV6=off

cat > /etc/sysconfig/network<<EOF
NETWORKING=yes
HOSTNAME=pg-k8s-1-184
NETWORKING_IPV6=off
EOF

cat > /etc/sysconfig/network<<EOF
NETWORKING=yes
HOSTNAME=pg-k8s-1-185
NETWORKING_IPV6=off
EOF

cat > /etc/sysconfig/network<<EOF
NETWORKING=yes
HOSTNAME=pg-k8s-1-186
NETWORKING_IPV6=off
EOF
```

#### 配置hosts
```.env
vi /etc/hosts

10.20.1.184 pg-k8s-1-184
10.20.1.185 pg-k8s-1-185
10.20.1.186 pg-k8s-1-186

cat >> /etc/hosts<<EOF
10.20.1.184 pg-k8s-1-184
10.20.1.185 pg-k8s-1-185
10.20.1.186 pg-k8s-1-186
EOF

```

#### 关闭防火墙
```
systemctl disable firewalld
```
手动关闭
```.env
systemctl stop firewalld 
```
查看关闭状态
```.env
systemctl status firewalld 
```
#### 禁用selinux
永久方式

修改/etc/selinux/config文件中设置SELINUX=disabled ，然后重启服务器。

临时方式
```
setenforce 0
```
查看状态
```
sestatus
```
#### 关闭swap
 
```
swapoff -a
或者修改/etc/fstab
```

#### 开启forward
- Docker从1.13版本开始调整了默认的防火墙规则
- 禁用了iptables filter表中FOWARD链,这样会引起Kubernetes集群中跨Node的Pod无法通信
```
iptables -P FORWARD ACCEPT
```

#### 配置转发相关参数，否则可能会出错
```
cat <<EOF >  /etc/sysctl.d/k8s.conf
net.bridge.bridge-nf-call-ip6tables = 1
net.bridge.bridge-nf-call-iptables = 1
vm.swappiness=0
EOF
```
```
sysctl --system
```

#### 加载ipvs相关内核模块,如果重新开机，需要重新加载
```
modprobe ip_vs
modprobe ip_vs_rr
modprobe ip_vs_wrr
modprobe ip_vs_sh
modprobe nf_conntrack_ipv4
lsmod | grep ip_vs
```
