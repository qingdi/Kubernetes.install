
## 创建coreDNS
```
kuberetes文件
/root/kubernetes/cluster/addons/dashboard
/root/kubernetes/cluster/addons/dns/coredns
cp coredns.yaml.base coredns.yaml
vi coredns.yaml
修改文档中 clusterIP地址：
selector:
    k8s-app: kube-dns
  clusterIP: 10.254.0.10
  ports:
  - name: dns
    port: 53
    protocol: UDP
  - name: dns-tcp
    port: 53
    protocol: TCP
kubectl apply -f coredns.yml

一般国内镜像下载不下来
修改：coredns.yaml 为
image: registry.cn-shenzhen.aliyuncs.com/acs/coredns:1.1.3

/root/kubernetes/cluster/addons/dashboard/dashboard-service.yaml

apiVersion: v1
kind: Service
metadata:
  name: kubernetes-dashboard
  namespace: kube-system
  labels:
    k8s-app: kubernetes-dashboard
    kubernetes.io/cluster-service: "true"
    addonmanager.kubernetes.io/mode: Reconcile
spec:
  type: NodePort
  selector:
    k8s-app: kubernetes-dashboard
  ports:
  - port: 443
    targetPort: 8443
    nodePort: 30001
    
vi /root/kubernetes/cluster/addons/dashboard/dashboard-controller.yaml
image: registry.cn-hangzhou.aliyuncs.com/kube_containers/kubernetes-dashboard-amd64:v1.8.3
# 查看
kubectl get pods -n kube-system
kubectl get svc -n kube-system
```

#### 到kubernetes/cluster/addons/dashboard目录，create dashboard相关的yaml

## 创建dashboard的token
访问：https://10.20.1.184:30001  选择token，然后使用以下命令获取
```
curl -s https://raw.githubusercontent.com/mritd/ktool/master/k8s/addons/dashborad/create_dashboard_sa.sh | bash
```
#### 脚本内容
```
#!/bin/bash

if kubectl get sa dashboard-admin -n kube-system &> /dev/null;then
    echo -e "\033[33mWARNING: ServiceAccount dashboard-admin exist!\033[0m"
else
    kubectl create sa dashboard-admin -n kube-system
    kubectl create clusterrolebinding dashboard-admin --clusterrole=cluster-admin --serviceaccount=kube-system:dashboard-admin
fi

kubectl describe secret -n kube-system $(kubectl get secrets -n kube-system | grep dashboard-admin | cut -f1 -d ' ') | grep -E '^token'

```
