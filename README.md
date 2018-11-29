# Alex-Api-framework

### 文档待Db类完善后添加

### 在workerman的webServer基础上添加了/moudle/controller/action模块化分层的支持；因此你可以像使用传统的MVC框架那样在controller里书写你的业务逻辑。 

### 通过pathinfo实例化相关控制器执行指定方法；实例化过的控制器类保存在成员数组中，因此这些控制器的实例将得到复用，从而减小重复的实例化销毁开销（与nginx php-fpm转发这种模式不同），因此你可以脱离apache；nginx独立运行（如果为了负载均衡以及静态资源最优调度，建议还是将nginx前置），配置好数据库，监听ip和端口，执行win下：php index.php ，linux下：php index.php start (-d 可选，可以daemonize运行) 即可完成指定端口的http监听

### 请求api格式：ip:port/moudle/controller/action    是不是很眼熟？
### plans:接下来主要完成两部分内容：
  1.权衡基于 pdo 的 单例 短连接 | pconnect mysql，会提供主流的mysql orm，最后效果是你可以通过配置实现你在控制器里使用何种mysql封装愉悦的书写你的业务     代码
  
  2.继续添加ws,tcp,udp模块化的支持，使其对指定端口完成监听之后，在业务代码里（通常指控制器里的某个方法）可以随时使用推送
