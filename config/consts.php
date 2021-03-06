<?php

define('PROVIDER', 2);
define('DRIVER', 3);
define('USER', 4);
define('ADMIN', 1);

define('USER_UPDATED_ORDER',1);
define('USER_NOT_UPDATED_ORDER' , 0);

define('PROVIDER_INACTIVE', 0);
define('PROVIDER_ACTIVE', 1);

define('DELIVERY_INACTIVE', 0);
define('DELIVERY_ACTIVE', 1);

define('DELIVERY_UNAVAILABLE', 0);
define('DELIVERY_AVAILABLE', 1);

define('CATEGORY_INACTIVE', 0);
define('CATEGORY_ACTIVE', 1);

define('COUNTRY_INACTIVE', 0);
define('COUNTRY_ACTIVE', 1);

define('CITY_INACTIVE', 0);
define('CITY_ACTIVE', 1);

define('SERVICE_TYPE_INACTIVE', 0);
define('SERVICE_TYPE_ACTIVE', 1);

define('MAIN_SERVICE_TYPE', 1);
define('EXTRA_SERVICE_TYPE', 2);


define('PAYMENT_TYPE_INACTIVE', 0);
define('PAYMENT_TYPE_ACTIVE', 1);

define('LANGUAGE_INACTIVE', 0);
define('LANGUAGE_ACTIVE', 1);

define('LANGUAGE_NOT_DEFAULT', 0);
define('DEFAULT_LANGUAGE', 1);

// Order status
define('ORDER_STATUS_NEW', 0);
define('ORDER_STATUS_PROVIDER_CANCELLED', 1);
define('ORDER_STATUS_DELIVERY_CANCELLED', 2);
define('ORDER_STATUS_DELIVERY_ASSIGNED', 3);
define('ORDER_STATUS_DELIVERY_ACCEPTED', 4);
define('ORDER_STATUS_DELIVERY_LOADING', 5);
define('ORDER_STATUS_DELIVERY_CONFIRMED', 6);
define('ORDER_STATUS_DELIVERY_USER_REFUSE', 7);
define('ORDER_STATUS_ADMIN_REFUSE', 8);

define('WEB',1);
define('ANDROID',2);
define('IOS',3);

define('NOTIFICATION_IMG' , 'http://ratb.li/delivery/dist/img/logo.png');

?>