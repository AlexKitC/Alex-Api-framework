# Alex-Api-framework

### 文档待Db类完善后添加

### 在workerman的webServer基础上添加了常见的/moudle/controller/action分层的方式；通过pathinfo实例化相关控制器执行指定方法；实例化过的控制器类保存在成员数组中，因此这些控制器的实例将得到复用，从而减小开销（与nginx php-fpm转发这种模式不同）

### 不介入mysql;本地单机单进程ab压测 -n 1000 -c 200 能得到 8200 RPS的不俗表现；远远高于lamp（apache2.4+php7.2）的265 RPS，lnmp的960 RPS；能达到已编译中间码的spring boot的80%

### 介入mysql;本地单机单进程demo表同样查询 ab压测 -n 1000 -c 200 能得到 3300 RPS的不俗表现；远远高于lamp（apache2.4+php7.2）的65 RPS，lnmp的260 RPS
