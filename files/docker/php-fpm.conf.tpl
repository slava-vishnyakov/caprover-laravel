[www]

php_value[upload_max_filesize] = 50M
php_value[post_max_size] = 50M

pm = dynamic
pm.max_children = 2000
pm.start_servers = 500
pm.min_spare_servers = 32
pm.max_spare_servers = 500
clear_env = no

user = $PHP_USER
group = $PHP_GROUP
listen = $PHP_SOCK_FILE
listen.owner = $PHP_USER
listen.group = $PHP_GROUP
listen.mode = $PHP_MODE
