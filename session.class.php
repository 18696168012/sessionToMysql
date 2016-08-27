<?php
    /*
     * 说明
     * 1.数据库保存,IP,时间,数据,session_id,可以扩展其他字段
     * 2.需要修改php.ini配置文件session.save_handler = user
     * 3.在需要用到session时,加载此类
     * 4.如需扩展,请自行添加相应的功能
     * */
     class Session{
        //保存IP
         private static $ip;
         //保存对象
        private static $handle;
        //php.ini垃圾回收时间(gc)
        private static $gc_time;
        //当前时间
        private static $time;
        //初始化方法
        private static function init($hand){
            //初始化ip
            self::$ip=!empty($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'noIp';
            //保存资源
            self::$handle=$hand;
            //初始化垃圾回收时间
            self::$gc_time=ini_get('session.gc_maxlifetime');
            //初始化当前时间
            self::$time=time();
        }
        //类的入口
        static function start(PDO $PDO){
            //调用初始化方法
           self::init($PDO);
           //对session进行自定义处理(根据不同的session状态,会调用相应的方法)
           session_set_save_handler(
               array(__CLASS__,'open'),
               array(__CLASS__,'close'),
               array(__CLASS__,'read'),
               array(__CLASS__,'write'),
               array(__CLASS__,'destroy'),
               array(__CLASS__,'gc')
         );
           //开启session
           session_start();//这也是必须的，打开session，必须在session_set_save_handler后面执行
       }
       //在运行session_start()时执行
       static function open(){
           //echo '我是open<br/>';
           return true;
       }
       //在脚本执行完成或调用session_write_close() 或 session_destroy()时被执行,即在所有session操作完后被执行 
       static function close(){
           //echo '我是close<br/>';
           return true;
       }
       //在运行session_start()时执行,因为在session_start时,会去read当前session数据
       static function read($PHPSESSID){
          // echo '我是read<br/>';
           $sql='select * from session where id=?';
           $stmt=self::$handle->prepare($sql);
           $stmt->execute(array($PHPSESSID));
           $result=$stmt->fetch(PDO::FETCH_ASSOC);
           //判断是否有结果集
           if(!$result){
               //echo '结果集为空<br/>';
               return '';
           }
           //判断ip是否匹配
           if($result['client_ip']!=self::$ip){
               //echo 'IP不匹配<br/>';
               return '';
           }
           //var_dump($result);
           //返货数据
           return $result['data'];
       }
       //此方法在脚本结束和使用session_write_close()强制提交SESSION数据时执行
       static function write($PHPSESSID,$data){
           //echo '我是write<br/>';
           $sql='select * from session where id=?';
           $stmt=self::$handle->prepare($sql);
           $stmt->execute(array($PHPSESSID));
           $result=$stmt->fetch(PDO::FETCH_ASSOC);
           if($result){
               //如果有值,判断是否过期，或者session数据是否有变化
               if($result['data']!=$data || ($result['update_time']+30)<self::$time){
                   $sql='update session set update_time=?,data=? where id=?';
                   $stm=self::$handle->prepare($sql);
                   $stm->execute(array(self::$time,$data,$PHPSESSID));
               }
           }else{
               //插入一条新记录
               $sql='insert into session(id,update_time,client_ip,data) values(?,?,?,?)';
               $st=self::$handle->prepare($sql);
               $aa=$st->execute(array($PHPSESSID,self::$time,self::$ip,$data));
               var_dump($aa);
           }
           return true;
       }
       //在运行session_destroy()时执行
       static function destroy($id){
           //echo '我是destroy<br/>';
           $sql='delete form session where id=?';
           $stmt=self::$handle->prepare($sql);
           $stmt->execute(array($id));
           return true;
       }
       //执行概率由session.gc_probability 和 session.gc_divisor的值决定,时机是在open,read之后,session_start会相继执行open,read和gc
       static function gc(){
           //echo '我是gc<br/>';
           $sql='delete from session where update_time<?';
           $stmt=self::$handle->prepare($sql);
           $stmt->execute(array(self::$time-self::$gc_time));
           return true;
       }
     }
     try{
         $pdo=new PDO('mysql:host=localhost;dbname=session', 'root','');
         //var_dump($pdo);
     }catch(PDOException $e){
         echo $e->getMessage();
     }
     Session::start($pdo);
     
     
     
     
     
     
     
     
     
     
     
     
     
     
     
     
     
     
     
     
     
     
     
     
     
     
     
     