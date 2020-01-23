# Tạo một hệ thống Logging cho nhiều dự án/server

PS: Bài viết góp nhặt từ kinh nghiệm và học hỏi từ các chuyên gia

Chào các bạn !

Cái gì cũng vậy, đều đến từ nhu cầu thực tế, nhu cầu của mình là cần có 1 nơi lưu lại các Log của những webApp/website/server mình đang quản lý và hiển thị báo cáo thành Dashboard cho dễ nhìn.

Ở đây còn mang ý nghĩa là đo lường hiệu năng của các script chạy trên đó như là tốt độ load/lỗi tự nhiên xuất hiện, hoặc tốc độ từng câu SQL, cái này hay gặp mới lên Production thì chạy nhanh, cỡ 6 tháng sau thì cả hệ thống như con rùa là do SQL :p

Đặc biệt mấy cái lỗi này thì không có ai báo bạn biết đâu à, mấy tháng sau mới phát hiện thì căng quá.

Mình cũng đã thử khá nhiều dịch vụ quốc tế như loggly.com, logentries.com,... túm lại là chưa tới đâu, phần là vì chi phí, phần là không đúng nhu cầu.

Cuối cùng sao ? thì theo dõi log bằng cơm o.O xài mấy dịch vụ theo dõi server sống hay chết,...

Cho đến 1 ngày, idol của mình là bạn Võ Duy Tuấn, làm 1 buổi meetup về [Xây dựng hệ thống Log cho Microservices](http://bloghoctap.com/technology/xay-dung-he-thong-log-cho-microservices.html)

Đúng bài luôn :D đi xin source mãi mà ảnh không cho, nên đành chịu, tự xem theo cấu trúc hệ thống mà tự dựng vậy Y_Y

Làm xong rồi, chạy rồi, nên mình mới viết bài này, vừa lưu lại, vừa ôn bài, vừa share cho anh em đồng đạo nếu có nhu cầu tương tự.

## Mô tả cấu trúc hệ thống (hình của anh Tuấn)

![Cấu trúc hệ thống Log](/assets/images/structure.png "Cấu trúc hệ thống Log")

## Thứ tự việc cần làm

1. Log hệ thống bằng NodeQuery.com
2. Tạo server chứa và nhận Log
3. Cài đặt RabbitMQ
4. Cài đặt ClickHouse
5. Cài UDP Log Server
6. Cài UDP Log Worker
7. Cài Supervisor
8. Cài syslog-ng và gởi log remote
9. App Log và PHP Error
10. Grafana vẽ Dashboard
11. Đoạn kết


## Log hệ thống bằng NodeQuery

[NodeQuery.com](https://nodequery.com)

Ok đăng ký 30s, gõ lệnh 5s, là bạn đã có 1 hệ thống theo dõi Ram, HDD, CPU cho server của mình.

Free 10 server và có cả tính năng Alert qua e-mail

Tool quá xịn, mong chờ bấy lâu :D đặc biệt vụ hết HDD là hay gặp ở server Cloud

## Tạo server chứa và nhận Log

Mình chọn [Digital Ocean](https://www.digitalocean.com/) giá hạt dẻ 5$/tháng RAM 1 GB, 1 vCPU, 1 TB traffic, 25 GB SSD

Cách dễ là chọn Image Ubuntu LAMP 18.04 là xong. Hoặc thuần Ubnuntu 18.04 rồi cài thêm PHP.

## Cài đặt RabbitMQ

Mình để sẵn các command line hoặc xem [link](https://computingforgeeks.com/how-to-install-latest-rabbitmq-server-on-ubuntu-18-04-lts/)

Cài Erlang
> sudo apt-get update

> sudo apt-get upgrade

> cd ~
wget http://packages.erlang-solutions.com/site/esl/esl-erlang/FLAVOUR_1_general/esl-erlang_20.1-1~ubuntu~xenial_amd64.deb
sudo dpkg -i esl-erlang_20.1-1\~ubuntu\~xenial_amd64.deb

Cài RabbitMQ
> wget -O- https://dl.bintray.com/rabbitmq/Keys/rabbitmq-release-signing-key.asc | sudo apt-key add -

> wget -O- https://www.rabbitmq.com/rabbitmq-release-signing-key.asc | sudo apt-key add -

> echo "deb https://dl.bintray.com/rabbitmq/debian $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/rabbitmq.list

> sudo apt update

> sudo apt -y install rabbitmq-server

> sudo systemctl status  rabbitmq-server.service

> systemctl is-enabled rabbitmq-server.service

Kiểm tra ok chưa
> sudo rabbitmqctl status

Bật chế độ xem giao diện quản lý RabbitMQ bằng web browser
> sudo rabbitmq-plugins enable rabbitmq_management

> sudo chown -R rabbitmq:rabbitmq /var/lib/rabbitmq/

Tạo tài khoản vào RabbitMQ (default là guest / guest)

XXXpasswordXXX là mật khẩu của admin
> sudo rabbitmqctl add_user admin XXXpasswordXXX

> sudo rabbitmqctl set_user_tags admin administrator

> sudo rabbitmqctl set_permissions -p / admin ".\*" ".\*" ".\*"

Tạo user log, dùng để UDP Worker lấy log về Database

> sudo rabbitmqctl add_user log XXXpasswordXXX

> sudo rabbitmqctl set_user_tags log administrator

> sudo rabbitmqctl set_permissions -p / log ".\*" ".\*" ".\*"

> sudo ufw allow proto tcp from any to any port 5672,15672

Done rồi

Giờ truy cập vào địa chỉ http://[IP Log server]:15672/

Nếu thấy hình này thì là ok nha. Không thì xem lại link tài liệu ở trên.

![RabbitMQ](/assets/images/rabbitMQ.png "RabbitMQ")

Cách sử dụng thì các bạn xem tài liệu của [RabbitMQ tại đây](https://www.rabbitmq.com/)

## Cài đặt ClickHouse

Theo anh Tuấn giới thiệu thì Database ClickHouse query xử lý tính bằng trăm triệu records. Hoạt động trên cơ chế column-oriented thay vì row-oriented như MySQL, xem thêm sự khác nhau [ở đây](https://www.geeksforgeeks.org/difference-between-row-oriented-and-column-oriented-data-stores-in-dbms/)

Xem thêm hướng dẫn cài đặt [ở đây](https://www.digitalocean.com/community/tutorials/how-to-install-and-use-clickhouse-on-ubuntu-18-04)

> sudo apt-key adv --keyserver keyserver.ubuntu.com --recv E0C56BD4

> echo "deb http://repo.yandex.ru/clickhouse/deb/stable/ main/" | sudo tee /etc/apt/sources.list.d/clickhouse.list

> sudo apt-get update

> sudo apt-get install -y clickhouse-server clickhouse-client

> sudo service clickhouse-server start

> sudo service clickhouse-server status

> sudo ufw allow proto tcp from any to any port 8123,9000

Mình dùng App [DBeaver](https://dbeaver.io/) để kết nối vào ClickHouse

Schema để trong thư mục clickhouse (anh Tuấn share)

Chi tiết cách query, select/insert/update/delete thì các bạn đọc tài liệu ClickHouse nhé.

## Cài UDP Log Server

Trước tiên xác định là mình sử dụng PHP command line để tạo 1 UDP server, nhớ giấu IP server Log này hoặc bạn tự tìm hiểu thêm cách bảo vệ nhé.

Sử dụng extension Swoole

> pecl install swoole

> touch /etc/php/7.2/cli/conf.d/swoole.ini && echo 'extension=swoole.so' > /etc/php/7.2/cli/conf.d/swoole.ini

Kiểm tra
> php -m | grep swoole

Source code server UDP có ở thư mục php nhé.
Bạn đưa lên server ví dụ /var/www cũng được

Chạy command line sau ở thư mục php

> composer install

> sudo ufw allow proto udp from any to any port 9502,9501

Chạy thử
> php /var/www/udp_server.php

## Cài UDP Log Worker

Cái này chỉ là 1 script PHP dùng để đọc dữ liệu trong queue trong RabbitMQ và insert vào database ClickHouse

Các bạn chú ý trong file này có chia ra 3 loại log (còn thiếu SQL chưa viết xong :p) tương ứng đưa vào 3 table khác nhau.

- Log từ App (file app.log)
- Log từ PHP Error (Warning, Fatal Error,...)
- Log từ syslog khác

Worker và UDP Server đều chạy thường trực.

Chạy thử
> php /var/www/udp_worker.php

Để kiểm tra hoặc giữ cho script luôn chạy thì mình dùng Supervisor

## Cài Supervisor

[Link cài đặt](https://flotz-chronicles.com/script/php/2015/08/18/running-php-script-forever-with-supervisor.html)

> sudo apt-get install supervisor

Copy 2 file udp_server.conf và udp_worker.conf vào thư mục /etc/supervisor/conf.d/

> sudo service supervisor restart

Nếu bạn có nhu cầu để xem trên Browser làm cái này
![Supervisor](/assets/images/supervisor.png "Supervisor")
Copy file supervisor/http_server.conf vào thư mục /etc/supervisor/conf.d/

> sudo ufw allow proto tcp from any to any port 9001

## Cài syslog-ng và gởi log remote trên server dự án

Cài đặt syslog-ng và cấu hình để gởi log đến server UDP của mình.
*Lưu ý chỗ này là server dự án chứ không phải server log*

> sudo apt-get install syslog-ng -y

Điền IP Server UDP vào chỗ IP_UDP_LOG_SERVER trong file syslog-ng/remote_host.conf

Trong file này, bạn sẽ thấy mình lấy access.log, error.log và app.log
Trong đó access.log và error.log là của Apache tạo, còn app.log là do web application của bạn tạo.

Copy file remote_host.conf vào /etc/syslog-ng/conf.d/

> service syslog-ng restart

Chỗ này mình gặp 1 vấn đề là PHP không tạo được file log trong /var/log/apache2, vì vậy mình cần để log của app ở nơi khác, chỗ mà user www-data truy cập được.

> mkdir /var/www/log

> chown www-data:www-data /var/www/log

> nano /etc/logrotate.d/my-app

Copy nội dung file logrotate/myapp

Ý nghĩa việc này là cấp quyền user www-data được đọc file log và có chế độ rotate log

## App Log và PHP Error

Đây là log chủ động, tức là log do Application của bạn tạo ra. Ở đây mình dùng Monolog và tạo ra file app.log

Format template của Log do bạn quy định, sau đó ở UDP Worker bạn explode() để lấy các thông số mình cần.

Bạn hãy xem file test_app_log.php để thấy cách mình làm.

Ở file này sẽ log lại thời gian bắt đầu/kết thúc của script, kèm memory.

Format Template dạng:
> [2020-01-15T15:05:54.681827+07:00] APP-v1.0.INFO: [START2020-01-15 2020-01-15_08:05:54 08 05 APP-v1.0 index.php GET success 0.075027942657471 6291456 118.69.76.177END] [] []

Về PHP Error, thì Apache sẽ báo lỗi 500, và sẽ lưu log về error.log

Trong file udp_worker.php có phần detect PHP Error log để đưa vào bảng log_php_error.

Sau đó chúng ta bắt đầu vẽ Dashboard bằng cách kết nối vào ClickHouse và query các Chart mình cần.


## Grafana vẽ Dashboard

Tuấn có đề cập đến việc sử dụng tự dev ReactJS, hoặc dùng Grafana, Jaeger UI

Trong bài này, mình chỉ đề cập đến [Grafana.com](https://grafana.com)

Install Grafana, bạn có thể install trên server khác và kết nối vào ClickHouse

> sudo apt-get install -y apt-transport-https

> sudo apt-get install -y software-properties-common wget

> wget -q -O - https://packages.grafana.com/gpg.key | sudo apt-key add -

> sudo add-apt-repository "deb https://packages.grafana.com/oss/deb stable main"

> sudo apt-get update

> sudo apt-get install grafana

Start Grafana Server

> sudo systemctl daemon-reload

> sudo systemctl start grafana-server

> sudo systemctl status grafana-server

Mở port firewall

> sudo ufw allow proto tcp from any to any port 3000

Truy cập http://[IP_LOG_SERVER]:3000

Username mặc định là admin / admin, sẽ có yêu cầu đổi khi bạn đăng nhập vào.

Cách sử dụng Grafana thì bạn xem trên website [Grafana.com](https://grafana.com)

Hình demo của Tuấn
![Grafana](/assets/images/grafana.png "Grafana")

Ghi chú: Vì ghi log của rất nhiều server khác nhau, vì vậy trong Grafana có tính năng Variable dùng để filter theo tên Application khác tốt.

## Đoạn kết

Trên đây là các kinh nghiệm đã làm qua, hy vọng sẽ giúp ích với các bạn có nhu cầu.

Theo như idol Tuấn đề cập, thì bạn ấy cũng đã thử nhiều cách khác nhau mới chọn ra được cách này, mang lại sự ổn định nhất cho đến hiện tại. Số log lưu lại tính bằng trăm triệu records mà hệ thống vẫn chạy ổn định, kể cả server Log.